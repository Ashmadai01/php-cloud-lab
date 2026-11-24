<?php
declare(strict_types=1);
// SDUT Swap — upgrade an EXISTING database to the current schema (idempotent).
// Usage: php tools/upgrade.php
//
// What it does (each step is skipped if already applied):
//   - creates user_blocks and ticket_attachments
//   - adds users.verification_status, user_sessions.device_label,
//     student_verifications.active_hash / card_purged_at
//   - adds the unique index that prevents one student number being used twice
//   - switches the marketplace currency to CNY (¥)
//
// Needs a DB account with ALTER/CREATE/INDEX privileges. Set DB_MIGRATE_USER and
// DB_MIGRATE_PASS in .env (or the environment) to use a separate admin account;
// otherwise DB_USER / DB_PASS are used. The app's own least-privilege account
// (SELECT/INSERT/UPDATE/DELETE) is NOT enough for this script.

define('SDUTSWAP_NO_DISPATCH', true);
require dirname(__DIR__) . '/app/bootstrap.php';

use SdutSwap\Core\Config;

if (PHP_SAPI !== 'cli') { fwrite(STDERR, "CLI only.\n"); exit(1); }

$host = Config::get('DB_HOST', '127.0.0.1');
$port = Config::get('DB_PORT', '3306');
$db   = Config::get('DB_NAME', 'campusx');
$user = Config::get('DB_MIGRATE_USER', '') ?: Config::get('DB_USER', 'campusx_app');
$pass = Config::get('DB_MIGRATE_PASS', '') ?: Config::get('DB_PASS', '');

try {
    $pdo = new PDO("mysql:host={$host};port={$port};dbname={$db};charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (Throwable $e) {
    fwrite(STDERR, 'Could not connect: ' . $e->getMessage() . "\n");
    exit(1);
}

function tableExists(PDO $pdo, string $table): bool
{
    $s = $pdo->prepare('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?');
    $s->execute([$table]);
    return (int) $s->fetchColumn() > 0;
}

function columnExists(PDO $pdo, string $table, string $column): bool
{
    $s = $pdo->prepare('SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?');
    $s->execute([$table, $column]);
    return (int) $s->fetchColumn() > 0;
}

function indexExists(PDO $pdo, string $table, string $index): bool
{
    $s = $pdo->prepare('SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = ? AND index_name = ?');
    $s->execute([$table, $index]);
    return (int) $s->fetchColumn() > 0;
}

function step(string $label, callable $fn): void
{
    try {
        $result = $fn();
        echo ($result === false ? '  skip  ' : '  done  ') . $label . "\n";
    } catch (Throwable $e) {
        fwrite(STDERR, "  FAIL  {$label}: " . $e->getMessage() . "\n");
        exit(1);
    }
}

echo "Upgrading database '{$db}'...\n";

step('table user_blocks', function () use ($pdo) {
    if (tableExists($pdo, 'user_blocks')) return false;
    $pdo->exec('CREATE TABLE user_blocks (
      id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
      blocker_id BIGINT UNSIGNED NOT NULL,
      blocked_id BIGINT UNSIGNED NOT NULL,
      created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
      UNIQUE KEY uq_block (blocker_id, blocked_id),
      CONSTRAINT fk_block_blocker FOREIGN KEY (blocker_id) REFERENCES users(id) ON DELETE CASCADE,
      CONSTRAINT fk_block_blocked FOREIGN KEY (blocked_id) REFERENCES users(id) ON DELETE CASCADE,
      INDEX idx_block_blocked (blocked_id)
    ) ENGINE=InnoDB');
    return true;
});

step('table ticket_attachments', function () use ($pdo) {
    if (tableExists($pdo, 'ticket_attachments')) return false;
    $pdo->exec('CREATE TABLE ticket_attachments (
      id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
      ticket_message_id BIGINT UNSIGNED NOT NULL,
      file_path VARCHAR(255) NOT NULL,
      created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
      CONSTRAINT fk_ta_message FOREIGN KEY (ticket_message_id) REFERENCES ticket_messages(id) ON DELETE CASCADE,
      INDEX idx_ta_message (ticket_message_id)
    ) ENGINE=InnoDB');
    // Carry over single attachments stored on the old column.
    if (columnExists($pdo, 'ticket_messages', 'attachment_path')) {
        $pdo->exec("INSERT INTO ticket_attachments (ticket_message_id, file_path)
                    SELECT id, attachment_path FROM ticket_messages WHERE attachment_path IS NOT NULL AND attachment_path <> ''");
    }
    return true;
});

step('users.verification_status', function () use ($pdo) {
    if (columnExists($pdo, 'users', 'verification_status')) return false;
    $pdo->exec("ALTER TABLE users ADD COLUMN verification_status ENUM('not_submitted','pending','verified','rejected') NOT NULL DEFAULT 'not_submitted' AFTER is_verified");
    // Backfill from the latest submission, and from is_verified.
    $pdo->exec("UPDATE users u JOIN student_verifications sv ON sv.id = (
                    SELECT id FROM student_verifications WHERE user_id = u.id ORDER BY created_at DESC, id DESC LIMIT 1
                ) SET u.verification_status = sv.status");
    $pdo->exec("UPDATE users SET verification_status = 'verified' WHERE is_verified = 1");
    return true;
});

step('user_sessions.device_label', function () use ($pdo) {
    if (columnExists($pdo, 'user_sessions', 'device_label')) return false;
    $pdo->exec('ALTER TABLE user_sessions ADD COLUMN device_label VARCHAR(120) NULL AFTER user_agent_hash');
    return true;
});

step('student_verifications.card_purged_at', function () use ($pdo) {
    if (columnExists($pdo, 'student_verifications', 'card_purged_at')) return false;
    $pdo->exec('ALTER TABLE student_verifications ADD COLUMN card_purged_at TIMESTAMP NULL AFTER card_image_path');
    return true;
});

step('student_verifications.active_hash + unique index', function () use ($pdo) {
    $changed = false;
    if (!columnExists($pdo, 'student_verifications', 'active_hash')) {
        $pdo->exec('ALTER TABLE student_verifications ADD COLUMN active_hash CHAR(64) NULL AFTER card_purged_at');
        $changed = true;
    }
    if (!indexExists($pdo, 'student_verifications', 'uq_sv_active_hash')) {
        // active_hash holds the hash only while a submission is pending or verified.
        // If old data already has the same number twice, keep the earliest and leave
        // the later ones NULL (they will show up in the report below).
        $pdo->exec("UPDATE student_verifications SET active_hash = NULL");
        $pdo->exec("UPDATE student_verifications sv
                    JOIN (SELECT student_number_hash AS h, MIN(id) AS keep_id
                          FROM student_verifications WHERE status IN ('pending','verified')
                          GROUP BY student_number_hash) k ON k.h = sv.student_number_hash AND k.keep_id = sv.id
                    SET sv.active_hash = sv.student_number_hash");
        $pdo->exec('ALTER TABLE student_verifications ADD UNIQUE KEY uq_sv_active_hash (active_hash)');
        $changed = true;
    }
    return $changed;
});

step('table notifications', function () use ($pdo) {
    if (tableExists($pdo, 'notifications')) return false;
    $pdo->exec('CREATE TABLE notifications (
      id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
      user_id BIGINT UNSIGNED NOT NULL,
      ticket_id BIGINT UNSIGNED NULL,
      conversation_id BIGINT UNSIGNED NULL,
      type VARCHAR(50) NOT NULL,
      message VARCHAR(500) NOT NULL,
      read_at TIMESTAMP NULL,
      created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
      CONSTRAINT fk_notifications_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
      CONSTRAINT fk_notifications_ticket FOREIGN KEY (ticket_id) REFERENCES support_tickets(id) ON DELETE CASCADE,
      CONSTRAINT fk_notifications_conversation FOREIGN KEY (conversation_id) REFERENCES conversations(id) ON DELETE CASCADE,
      INDEX idx_notifications_user_created (user_id, created_at),
      INDEX idx_notifications_user_read (user_id, read_at)
    ) ENGINE=InnoDB');
    return true;
});

step('currency -> CNY', function () use ($pdo) {
    $pdo->exec("ALTER TABLE listings MODIFY currency CHAR(3) NOT NULL DEFAULT 'CNY'");
    $n = $pdo->exec("UPDATE listings SET currency = 'CNY' WHERE currency <> 'CNY'");
    if ($n) echo "        ({$n} listing(s) relabelled to CNY — amounts were NOT converted)\n";
    return true;
});

$dups = $pdo->query("SELECT student_number_hash, COUNT(*) AS c FROM student_verifications
                     WHERE status IN ('pending','verified') GROUP BY student_number_hash HAVING c > 1")->fetchAll();
if ($dups) {
    echo "\nNOTE: " . count($dups) . " student number(s) were already used by more than one pending/verified\n";
    echo "submission. Only the earliest keeps the uniqueness slot; review the others in the admin queue.\n";
}

echo "\nUpgrade complete.\n";
