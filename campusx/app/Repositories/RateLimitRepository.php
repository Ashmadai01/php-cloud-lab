<?php
declare(strict_types=1);

namespace SdutSwap\Repositories;

use SdutSwap\Core\Database;
use PDO;
use RuntimeException;
use Throwable;

final class RateLimitRepository
{
    private PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Database::pdo();
    }

    /**
     * Atomically consumes one rate-limit attempt.
     *
     * @return array{allowed: bool, blocked: bool, newly_blocked: bool}
     */
    public function consume(
        string $bucket,
        int $maxAttempts,
        int $windowSeconds,
        int $blockSeconds
    ): array {
        if (
            $bucket === ''
            || strlen($bucket) > 120
            || $maxAttempts < 1
            || $windowSeconds < 1
            || $blockSeconds < 1
        ) {
            throw new RuntimeException('Invalid rate-limit parameters.');
        }

        if ($this->db->inTransaction()) {
            throw new RuntimeException('Rate limiter cannot run inside an existing transaction.');
        }

        $driver = $this->db->getAttribute(PDO::ATTR_DRIVER_NAME);
        if (!in_array($driver, ['mysql', 'sqlite'], true)) {
            throw new RuntimeException('Unsupported rate-limit database driver.');
        }

        $transactionStarted = false;

        try {
            $this->db->beginTransaction();
            $transactionStarted = true;

            if ($driver === 'sqlite') {
                $this->db->prepare(
                    'INSERT OR IGNORE INTO rate_limits (bucket, attempts, window_start)
                     VALUES (?, 0, CURRENT_TIMESTAMP)'
                )->execute([$bucket]);
            } else {
                $this->db->prepare(
                    'INSERT INTO rate_limits (bucket, attempts, window_start)
                     VALUES (?, 0, CURRENT_TIMESTAMP)
                     ON DUPLICATE KEY UPDATE id = LAST_INSERT_ID(id)'
                )->execute([$bucket]);
            }

            $sql = 'SELECT attempts, window_start, blocked_until
                    FROM rate_limits WHERE bucket = ? LIMIT 1';
            if ($driver === 'mysql') {
                $sql .= ' FOR UPDATE';
            }

            $stmt = $this->db->prepare($sql);
            $stmt->execute([$bucket]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$row) {
                throw new RuntimeException('Rate-limit bucket could not be loaded.');
            }

            $now = time();
            if ($row['blocked_until'] !== null && $row['blocked_until'] !== '') {
                $blockedUntil = strtotime((string) $row['blocked_until'] . ' UTC');
                if ($blockedUntil === false) {
                    throw new RuntimeException('Invalid blocked_until value.');
                }
                if ($blockedUntil > $now) {
                    $this->db->commit();
                    $transactionStarted = false;
                    return ['allowed' => false, 'blocked' => true, 'newly_blocked' => false];
                }
            }

            $windowStart = strtotime((string) $row['window_start'] . ' UTC');
            if ($windowStart === false) {
                throw new RuntimeException('Invalid window_start value.');
            }

            if ($now - $windowStart >= $windowSeconds) {
                $this->updateOne(
                    'UPDATE rate_limits
                     SET attempts = 1, window_start = CURRENT_TIMESTAMP, blocked_until = NULL
                     WHERE bucket = ?',
                    [$bucket]
                );
                $this->db->commit();
                $transactionStarted = false;
                return ['allowed' => true, 'blocked' => false, 'newly_blocked' => false];
            }

            $attempts = (int) $row['attempts'] + 1;
            if ($attempts > $maxAttempts) {
                $until = gmdate('Y-m-d H:i:s', $now + $blockSeconds);
                $this->updateOne(
                    'UPDATE rate_limits SET attempts = ?, blocked_until = ? WHERE bucket = ?',
                    [$attempts, $until, $bucket]
                );
                $this->db->commit();
                $transactionStarted = false;
                return ['allowed' => false, 'blocked' => true, 'newly_blocked' => true];
            }

            $this->updateOne(
                'UPDATE rate_limits SET attempts = ? WHERE bucket = ?',
                [$attempts, $bucket]
            );
            $this->db->commit();
            $transactionStarted = false;

            return ['allowed' => true, 'blocked' => false, 'newly_blocked' => false];
        } catch (Throwable $e) {
            if ($transactionStarted && $this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    private function updateOne(string $sql, array $params): void
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        if ($stmt->rowCount() !== 1) {
            throw new RuntimeException('Rate-limit state update did not affect exactly one row.');
        }
    }
}
