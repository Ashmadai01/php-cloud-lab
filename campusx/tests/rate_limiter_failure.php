<?php
declare(strict_types=1);

// Independent regression test for fail-closed behavior and SQLite compatibility.
// Run from campusx/: php tests/rate_limiter_failure.php

define('SDUTSWAP_NO_DISPATCH', true);
define('SDUTSWAP_TESTING', true);
require dirname(__DIR__) . '/app/bootstrap.php';

use SdutSwap\Core\Database;
use SdutSwap\Core\RateLimiter;

if (!in_array('sqlite', PDO::getAvailableDrivers(), true)) {
    fwrite(STDERR, "SKIP: PDO SQLite driver is not installed.\n");
    exit(2);
}

$pdo = new PDO('sqlite::memory:');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
Database::setPdo($pdo);
$busyTimeout = (int) $pdo->query('PRAGMA busy_timeout')->fetchColumn();
if ($busyTimeout !== 5000) {
    fwrite(STDERR, "FAIL: SQLite busy_timeout was not configured through Database::setPdo().\\n");
    exit(1);
}
echo "PASS: SQLite busy_timeout is configured through Database::setPdo().\\n";

// Missing table simulates a repository/database failure. Protected action must be denied.
$denied = RateLimiter::check('failure-test', 5, 60);
if ($denied !== false) {
    fwrite(STDERR, "FAIL: rate limiter allowed a request when its table was unavailable.\n");
    exit(1);
}
echo "PASS: database failure is fail-closed.\n";

// The normal SQLite path must work independently of MySQL's production upsert syntax.
$pdo->exec('CREATE TABLE rate_limits (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    bucket TEXT NOT NULL UNIQUE,
    attempts INTEGER NOT NULL DEFAULT 0,
    window_start TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    blocked_until TEXT NULL
)');
$allowed = RateLimiter::check('sqlite-test', 5, 60);
$row = $pdo->query("SELECT attempts FROM rate_limits WHERE bucket = 'sqlite-test'")->fetchColumn();
if ($allowed !== true || (int) $row !== 1) {
    fwrite(STDERR, "FAIL: SQLite rate-limit insert did not create the expected row.\n");
    exit(1);
}
echo "PASS: SQLite rate-limit insert/upsert path works.\n";

echo "Results: 2 passed, 0 failed\n";
