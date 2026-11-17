<?php
declare(strict_types=1);
namespace SdutSwap\Core;

use SdutSwap\Repositories\RateLimitRepository;

final class RateLimiter
{
    public static function check(string $bucket, int $maxAttempts, int $windowSeconds, int $blockSeconds = 900): bool
    {
        try {
            $repo = new RateLimitRepository();
            $row = $repo->get($bucket);
            $now = time();
            if ($row) {
                if (!empty($row['blocked_until']) && strtotime((string) $row['blocked_until'] . ' UTC') > $now) {
                    return false;
                }
                $windowStart = strtotime((string) $row['window_start'] . ' UTC');
                if ($now - $windowStart > $windowSeconds) {
                    $repo->reset($bucket);
                    return true;
                }
                $attempts = (int) $row['attempts'] + 1;
                if ($attempts > $maxAttempts) {
                    $repo->block($bucket, $attempts, $blockSeconds);
                    Audit::security('rate_limit_block', 'medium', "Bucket {$bucket} blocked");
                    return false;
                }
                $repo->increment($bucket, $attempts);
                return true;
            }
            $repo->create($bucket);
            return true;
        } catch (\Throwable) {
            return true; // Fail open for rate limiter infra errors to avoid lockout; auth still enforced.
        }
    }

    public static function ipBucket(string $action): string
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        return $action . ':' . hash('sha256', $ip);
    }
}
