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
                if (!empty($row['blocked_until'])) {
                    $blockedUntil = strtotime((string) $row['blocked_until'] . ' UTC');
                    // Invalid limiter state must not silently disable the protection.
                    if ($blockedUntil === false) {
                        error_log('Rate limiter rejected request: invalid blocked_until for bucket ' . $bucket);
                        return false;
                    }
                    if ($blockedUntil > $now) {
                        return false;
                    }
                }

                $windowStart = strtotime((string) $row['window_start'] . ' UTC');
                if ($windowStart === false) {
                    error_log('Rate limiter rejected request: invalid window_start for bucket ' . $bucket);
                    return false;
                }
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
        } catch (\Throwable $e) {
            // Fail closed: if the limiter cannot be checked or updated, do not
            // allow the protected action to proceed without rate-limit enforcement.
            error_log('Rate limiter infrastructure failure; request denied: ' . $e->getMessage());
            return false;
        }
    }

    public static function ipBucket(string $action): string
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        return $action . ':' . hash('sha256', $ip);
    }
}
