<?php
declare(strict_types=1);

// Run from campusx/: php tests/rate_limiter_failure.php

define('SDUTSWAP_NO_DISPATCH', true);
define('SDUTSWAP_TESTING', true);
require dirname(__DIR__) . '/app/bootstrap.php';

use SdutSwap\\Core\\Database;
use SdutSwap\\Core\\RateLimiter;
use SdutSwap\\Repositories\\RateLimitRepository;

if (!in_array('sqlite', PDO::getAvailableDrivers(), true)) {
    fwrite(STDERR, "SKIP: PDO SQLite driver is not installed.\\n");
    exit(2);
}

$pass = 0;
$fail = 0;
$check = static function (string $name, bool $condition) use (&$pass, &$fail): void {
    if ($condition) {
        $pass++;
        echo "PASS: {$name}\\n";
    } else {
        $fail++;
        fwrite(STDERR, "FAIL: {$name}\\n");
    }
};

$pdo = new PDO('sqlite::memory:');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
Database::setPdo($pdo);
$check(
    'Database::setPdo configures SQLite busy_timeout',
    (int) $pdo->query('PRAGMA busy_timeout')->fetchColumn() === 5000
);

$repository = new RateLimitRepository($pdo);

// Missing table simulates an infrastructure failure; the protected action must be denied.
$check('database failure is fail-closed', RateLimiter::check('failure-test', 5, 60) === false);

$pdo->exec('CREATE TABLE rate_limits (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    bucket TEXT NOT NULL UNIQUE,
    attempts INTEGER NOT NULL DEFAULT 0,
    window_start TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    blocked_until TEXT NULL
)');

$first = $repository->consume('allow-test', 2, 60, 30);
$second = $repository->consume('allow-test', 2, 60, 30);
$check('requests within the configured limit are allowed',
    $first['allowed'] && !$first['blocked'] && $second['allowed']
);
$row = $pdo->query("SELECT attempts FROM rate_limits WHERE bucket = 'allow-test'")->fetchColumn();
$check('allowed attempts are counted atomically', (int) $row === 2);

$third = $repository->consume('allow-test', 2, 60, 30);
$check('attempt beyond the limit is denied and newly blocks the bucket',
    !$third['allowed'] && $third['blocked'] && $third['newly_blocked']
);
$fourth = $repository->consume('allow-test', 2, 60, 30);
$check('requests during an active block remain denied',
    !$fourth['allowed'] && $fourth['blocked'] && !$fourth['newly_blocked']
);

$pdo->exec("INSERT INTO rate_limits (bucket, attempts, window_start, blocked_until)
            VALUES ('window-test', 9, datetime('now', '-120 seconds'), NULL)");
$window = $repository->consume('window-test', 2, 60, 30);
$windowRow = $pdo->query("SELECT attempts, blocked_until FROM rate_limits WHERE bucket = 'window-test'")->fetch(PDO::FETCH_ASSOC);
$check('expired window resets counter and allows the current request',
    $window['allowed'] && (int) $windowRow['attempts'] === 1 && $windowRow['blocked_until'] === null
);

$pdo->exec("INSERT INTO rate_limits (bucket, attempts, window_start)
            VALUES ('rollback-test', 1, CURRENT_TIMESTAMP)");
$pdo->exec("CREATE TRIGGER reject_rate_limit_update
            BEFORE UPDATE ON rate_limits
            WHEN OLD.bucket = 'rollback-test'
            BEGIN SELECT RAISE(ABORT, 'simulated write failure'); END");
$rollbackFailed = false;
try {
    $repository->consume('rollback-test', 5, 60, 30);
} catch (Throwable) {
    $rollbackFailed = true;
}
$rollbackRow = $pdo->query("SELECT attempts FROM rate_limits WHERE bucket = 'rollback-test'")->fetchColumn();
$check('failed database update throws and rolls back without consuming an attempt',
    $rollbackFailed && (int) $rollbackRow === 1 && !$pdo->inTransaction()
);

$pdo->beginTransaction();
$externalTransactionPreserved = false;
try {
    $repository->consume('external-tx-test', 5, 60, 30);
} catch (RuntimeException) {
    $externalTransactionPreserved = $pdo->inTransaction();
}
if ($pdo->inTransaction()) {
    $pdo->rollBack();
}
$check('repository refuses nested transaction without rolling back caller transaction',
    $externalTransactionPreserved && !$pdo->inTransaction()
);

echo "Results: {$pass} passed, {$fail} failed\\n";
exit($fail > 0 ? 1 : 0);
