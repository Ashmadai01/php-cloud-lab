<?php
declare(strict_types=1);

// SDUT Swap — notifications system upgrade (CLI only).

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
        CREATE TABLE IF NOT EXISTS notifications (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id BIGINT UNSIGNED NOT NULL,
            ticket_id BIGINT UNSIGNED NULL,
            type VARCHAR(50) NOT NULL,
            message VARCHAR(255) NOT NULL,
            read_at TIMESTAMP NULL DEFAULT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

            CONSTRAINT fk_notifications_user
                FOREIGN KEY (user_id) REFERENCES users(id)
                ON DELETE CASCADE,

            CONSTRAINT fk_notifications_ticket
                FOREIGN KEY (ticket_id) REFERENCES support_tickets(id)
                ON DELETE CASCADE,

            INDEX idx_notifications_user_unread (user_id, read_at, created_at),
            INDEX idx_notifications_ticket (ticket_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    echo "Notifications migration completed successfully.\n";

} catch (Throwable $e) {
    fwrite(STDERR, "Notifications migration failed: " . $e->getMessage() . "\n");
    exit(1);
}
