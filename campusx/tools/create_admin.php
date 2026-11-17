<?php
declare(strict_types=1);
// SDUT Swap — create the first admin account via CLI (never via public registration).
// Usage: php tools/create_admin.php "Admin Name" admin@example.com
// The password is prompted interactively (no default admin password).

define('SDUTSWAP_NO_DISPATCH', true);
require dirname(__DIR__) . '/app/bootstrap.php';

use SdutSwap\Core\Database;
use SdutSwap\Repositories\UserRepository;

if (PHP_SAPI !== 'cli') { fwrite(STDERR, "CLI only.\n"); exit(1); }

$name = $argv[1] ?? '';
$email = strtolower(trim($argv[2] ?? ''));
if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    fwrite(STDERR, "Usage: php tools/create_admin.php \"Admin Name\" admin@example.com\n");
    exit(1);
}

fwrite(STDOUT, "Enter admin password (min 12 chars): ");
$password = trim((string) fgets(STDIN));
if (strlen($password) < 12) { fwrite(STDERR, "Password too short.\n"); exit(1); }

$users = new UserRepository();
$existing = $users->findByEmail($email);
$hash = password_hash($password, PASSWORD_ARGON2ID, ['memory_cost' => 65536, 'time_cost' => 4, 'threads' => 2]);
if ($hash === false) $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

if ($existing) {
    $users->updatePassword((int) $existing['id'], (string) $hash);
    $users->setRole((int) $existing['id'], 'admin');
    $users->setEmailVerified((int) $existing['id']);
    fwrite(STDOUT, "Existing user promoted to admin.\n");
} else {
    $id = $users->create($name, $email, (string) $hash);
    $users->setRole($id, 'admin');
    $users->setEmailVerified($id);
    fwrite(STDOUT, "Admin created (id {$id}).\n");
}
echo "Done. Sign in at /login.\n";
