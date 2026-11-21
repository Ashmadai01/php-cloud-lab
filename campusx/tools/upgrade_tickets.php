<?php
declare(strict_types=1);

// SDUT Swap — ticket system upgrade (CLI only).

define('SDUTSWAP_NO_DISPATCH', true);
require dirname(__DIR__) . '/app/bootstrap.php';

use SdutSwap\Core\Config;

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "CLI only.\n");
    exit(1);
}

$host = Config::get('DB_HOST', '127.0.0.1');
$port = Config::get('DB_PORT', '3306');
$db   = Config::get('DB_NAME', 'campusx');
$user = Config::get('DB_MIGRATE_USER', '') ?: Config::get('DB_USER', 'campusx_app');
$pass = Config::get('DB_MIGRATE_PASS', '') ?: Config::get('DB_PASS', '');

try {
    $pdo = new PDO(
        "mysql:host={$host};port={$port};dbname={$db};charset=utf8mb4",
        $user,
        $pass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );

    $pdo->exec("
        ALTER TABLE support_tickets
        MODIFY priority ENUM('low','normal','high','urgent')
        NOT NULL DEFAULT 'normal'
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS ticket_activity (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            ticket_id BIGINT UNSIGNED NOT NULL,
            actor_id BIGINT UNSIGNED NOT NULL,
            action VARCHAR(50) NOT NULL,
            old_value VARCHAR(255) NULL,
            new_value VARCHAR(255) NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            CONSTRAINT fk_ticket_activity_ticket
                FOREIGN KEY (ticket_id) REFERENCES support_tickets(id)
                ON DELETE CASCADE,
            CONSTRAINT fk_ticket_activity_actor
                FOREIGN KEY (actor_id) REFERENCES users(id)
                ON DELETE CASCADE,
            INDEX idx_ticket_activity_ticket (ticket_id, created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    echo "Ticket migration completed successfully.\n";

} catch (Throwable $e) {
    fwrite(STDERR, "Ticket migration failed: " . $e->getMessage() . "\n");
    exit(1);
}
