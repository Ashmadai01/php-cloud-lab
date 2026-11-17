<?php
declare(strict_types=1);
// SDUT Swap — apply database/schema.sql. Usage: php tools/migrate.php
// Requires a working .env with DB credentials. Uses a privileged connection
// only if DB_USER has CREATE privileges; otherwise create the DB manually.

define('SDUTSWAP_NO_DISPATCH', true);
require dirname(__DIR__) . '/app/bootstrap.php';

use SdutSwap\Core\Config;

if (PHP_SAPI !== 'cli') { fwrite(STDERR, "CLI only.\n"); exit(1); }

$schema = dirname(__DIR__) . '/database/schema.sql';
if (!is_file($schema)) { fwrite(STDERR, "Schema not found.\n"); exit(1); }

$host = Config::get('DB_HOST', '127.0.0.1');
$port = Config::get('DB_PORT', '3306');
$user = Config::get('DB_USER', 'campusx_app');
$pass = Config::get('DB_PASS', '');
$db = Config::get('DB_NAME', 'campusx');

try {
    $pdo = new PDO("mysql:host={$host};port={$port};charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    $sql = file_get_contents($schema);
    // Split on semicolons at line ends (simple, schema has no procedures).
    $statements = array_filter(array_map('trim', explode(";\n", (string) $sql)));
    foreach ($statements as $stmt) {
        if ($stmt === '' || str_starts_with($stmt, '--')) continue;
        $pdo->exec($stmt);
    }
    echo "Migration applied successfully.\n";
} catch (Throwable $e) {
    fwrite(STDERR, 'Migration failed: ' . $e->getMessage() . "\n");
    fwrite(STDERR, "Tip: create the database manually first:\n  mysql -u root -p -e \"CREATE DATABASE {$db} CHARACTER SET utf8mb4;\"\n  mysql -u root -p < database/schema.sql\n");
    exit(1);
}
