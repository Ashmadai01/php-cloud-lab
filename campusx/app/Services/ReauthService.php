<?php
declare(strict_types=1);
namespace SdutSwap\Services;

use SdutSwap\Core\Config;

/** Logic for admin step-up authentication. Pure functions where possible so they can be unit-tested. */
final class ReauthService
{
    public const SESSION_KEY = 'admin_reauth_at';

    public static function ttl(): int
    {
        return max(60, Config::int('ADMIN_REAUTH_TTL', 600));
    }

    /** True when the session holds a confirmation timestamp no older than $ttl seconds (and not from the future). */
    public static function isFresh(array $session, int $now, int $ttl): bool
    {
        $at = $session[self::SESSION_KEY] ?? null;
        return is_int($at) && $at <= $now && ($now - $at) <= $ttl;
    }

    public static function mark(array &$session, int $now): void
    {
        $session[self::SESSION_KEY] = $now;
    }

    public static function clear(array &$session): void
    {
        unset($session[self::SESSION_KEY]);
    }

    /**
     * Only same-site admin paths may be used as the post-confirmation destination.
     * Anything else (absolute URLs, //host, javascript:, encoded tricks) falls back to /admin.
     */
    public static function safeNext(?string $next): string
    {
        $next = (string) $next;
        if ($next === '' || str_contains($next, '//') || str_contains($next, '\\')) {
            return '/admin';
        }
        if (!preg_match('#^/admin(/[A-Za-z0-9_\-/]*)?$#D', $next)) {
            return '/admin';
        }
        return $next;
    }
}
