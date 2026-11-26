<?php
declare(strict_types=1);

namespace SdutSwap\\Core;

use SdutSwap\\Repositories\\RateLimitRepository;

final class RateLimiter
{
    public static function check(
        string $bucket,
        int $maxAttempts,
        int $windowSeconds,
        int $blockSeconds = 900
    ): bool {
        try {
            $result = (new RateLimitRepository())->consume(
                $bucket,
                $maxAttempts,
                $windowSeconds,
                $blockSeconds
            );

            // Audit only after consume() has committed; audit/alert paths may use the limiter.
            if ($result['newly_blocked']) {
                try {
                    Audit::security('rate_limit_block', 'medium', "Bucket {$bucket} blocked");
                } catch (\\Throwable $auditError) {
                    error_log('Rate limiter audit failure: ' . $auditError->getMessage());
                }
            }

            return $result['allowed'];
        } catch (\\Throwable $e) {
            error_log(
                'Rate limiter infrastructure failure; request denied: '
                . $e->getMessage()
            );
            return false;
        }
    }

    public static function ipBucket(string $action): string
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        return $action . ':' . hash('sha256', $ip);
    }
}
