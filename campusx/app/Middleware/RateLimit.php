<?php
declare(strict_types=1);
namespace SdutSwap\Middleware;

use SdutSwap\Core\RateLimiter;

/** Per-IP rate limit for one named action, e.g. new RateLimit('user_block', 20, 3600, 3600). */
final class RateLimit implements Middleware
{
    public function __construct(
        private string $action,
        private int $max,
        private int $window,
        private int $block = 900,
    ) {
    }

    public function handle(): void
    {
        if (!RateLimiter::check(RateLimiter::ipBucket($this->action), $this->max, $this->window, $this->block)) {
            abort(429, 'Too many requests. Please wait and try again.');
        }
    }
}
