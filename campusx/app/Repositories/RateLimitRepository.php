<?php
declare(strict_types=1);
namespace SdutSwap\Repositories;

use SdutSwap\Core\Database;
use PDO;

final class RateLimitRepository
{
    private PDO $db;
    public function __construct(?PDO $db = null) { $this->db = $db ?? Database::pdo(); }

    public function get(string $bucket): ?array
    {
        $s = $this->db->prepare('SELECT * FROM rate_limits WHERE bucket = ? LIMIT 1');
        $s->execute([$bucket]);
        $r = $s->fetch();
        return $r ?: null;
    }

    public function create(string $bucket): void
    {
        if ($this->db->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite') {
            $sql = 'INSERT INTO rate_limits (bucket, attempts, window_start) VALUES (?, 1, CURRENT_TIMESTAMP) ON CONFLICT(bucket) DO UPDATE SET attempts = attempts + 1';
        } else {
            // Keep the production MySQL upsert unchanged.
            $sql = 'INSERT INTO rate_limits (bucket, attempts, window_start) VALUES (?, 1, CURRENT_TIMESTAMP) ON DUPLICATE KEY UPDATE attempts = attempts + 1';
        }
        $this->db->prepare($sql)->execute([$bucket]);
    }

    public function increment(string $bucket, int $attempts): void
    {
        $this->db->prepare('UPDATE rate_limits SET attempts = ? WHERE bucket = ?')->execute([$attempts, $bucket]);
    }

    public function reset(string $bucket): void
    {
        $this->db->prepare('UPDATE rate_limits SET attempts = 1, window_start = CURRENT_TIMESTAMP, blocked_until = NULL WHERE bucket = ?')->execute([$bucket]);
    }

    public function block(string $bucket, int $attempts, int $blockSeconds): void
    {
        $until = gmdate('Y-m-d H:i:s', time() + $blockSeconds);
        $this->db->prepare('UPDATE rate_limits SET attempts = ?, blocked_until = ? WHERE bucket = ?')->execute([$attempts, $until, $bucket]);
    }
}
