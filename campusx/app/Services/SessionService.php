<?php
declare(strict_types=1);
namespace SdutSwap\Services;

use SdutSwap\Core\Config;
use SdutSwap\Repositories\SessionRepository;

/**
 * Server-side tracking of sign-ins ("sessions & devices").
 * A PHP session only counts as signed in while it has a matching, un-revoked row in user_sessions.
 * Infrastructure errors fail OPEN (logged) so a DB hiccup does not lock everyone out; the user
 * check in Auth::user() still runs on every request.
 */
final class SessionService
{
    public static function sidHash(): string
    {
        return hash('sha256', (string) session_id());
    }

    public static function register(int $userId): void
    {
        try {
            $ua = (string) ($_SERVER['HTTP_USER_AGENT'] ?? '');
            $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
            $life = max(60, Config::int('SESSION_LIFETIME', 7200));
            (new SessionRepository())->create(
                $userId,
                self::sidHash(),
                hash('sha256', $ip . '|' . Config::get('APP_NAME', 'SDUT Swap')),
                $ua !== '' ? hash('sha256', $ua) : null,
                self::deviceLabel($ua),
                gmdate('Y-m-d H:i:s', time() + $life)
            );
        } catch (\Throwable $e) {
            error_log('Session register failed: ' . $e->getMessage());
        }
    }

    /** True when this browser session is a known, active session of $userId. */
    public static function validate(int $userId): bool
    {
        try {
            $repo = new SessionRepository();
            $row = $repo->findByHash(self::sidHash());
            if ($row === null) return false;
            if ((int) $row['user_id'] !== $userId) return false;
            if (!empty($row['revoked_at'])) return false;
            $expires = strtotime((string) $row['expires_at'] . ' UTC');
            if ($expires === false || $expires <= time()) return false;
            $seen = strtotime((string) $row['last_seen_at'] . ' UTC');
            if ($seen === false || (time() - $seen) > 60) $repo->touch((int) $row['id']);
            return true;
        } catch (\Throwable $e) {
            error_log('Session validate failed (failing open): ' . $e->getMessage());
            return true;
        }
    }

    public static function rotate(string $oldHash, string $newHash): void
    {
        try {
            (new SessionRepository())->rehash($oldHash, $newHash);
        } catch (\Throwable $e) {
            error_log('Session rotate failed: ' . $e->getMessage());
        }
    }

    public static function revokeCurrent(): void
    {
        try {
            if (session_status() === PHP_SESSION_ACTIVE) {
                (new SessionRepository())->revokeByHash(self::sidHash());
            }
        } catch (\Throwable $e) {
            error_log('Session revoke failed: ' . $e->getMessage());
        }
    }

    public static function revokeOthers(int $userId): int
    {
        try {
            return (new SessionRepository())->revokeAllForUser($userId, self::sidHash());
        } catch (\Throwable $e) {
            error_log('Session revokeOthers failed: ' . $e->getMessage());
            return 0;
        }
    }

    public static function revokeAllForUser(int $userId): int
    {
        try {
            return (new SessionRepository())->revokeAllForUser($userId, null);
        } catch (\Throwable $e) {
            error_log('Session revokeAll failed: ' . $e->getMessage());
            return 0;
        }
    }

    /** Active sessions for display. The session-id hash never leaves the server; only is_current is exposed. */
    public static function listForUser(int $userId): array
    {
        try {
            $current = self::sidHash();
            $idle = max(60, Config::int('SESSION_IDLE_TIMEOUT', 1800));
            $out = [];
            foreach ((new SessionRepository())->activeForUser($userId, gmdate('Y-m-d H:i:s')) as $row) {
                // A browser that has been idle past the timeout is no longer signed in, even if its row has not expired yet.
                $seen = strtotime((string) $row['last_seen_at'] . ' UTC');
                $isCurrent = hash_equals((string) $row['session_id_hash'], $current);
                if (!$isCurrent && ($seen === false || (time() - $seen) > $idle)) continue;
                $out[] = [
                    'id' => (int) $row['id'],
                    'device_label' => (string) ($row['device_label'] ?: 'Unknown device'),
                    'created_at' => $row['created_at'],
                    'last_seen_at' => $row['last_seen_at'],
                    'is_current' => $isCurrent,
                ];
            }
            return $out;
        } catch (\Throwable $e) {
            error_log('Session list failed: ' . $e->getMessage());
            return [];
        }
    }

    /** A short human label ("Chrome on Windows"). The raw User-Agent is never stored. */
    public static function deviceLabel(string $ua): string
    {
        $ua = substr($ua, 0, 400);
        if ($ua === '') return 'Unknown device';
        $os = 'Unknown OS';
        foreach (['Windows' => 'Windows', 'Android' => 'Android', 'iPhone' => 'iOS', 'iPad' => 'iOS', 'Mac OS X' => 'macOS', 'Macintosh' => 'macOS', 'Linux' => 'Linux'] as $needle => $name) {
            if (stripos($ua, $needle) !== false) { $os = $name; break; }
        }
        $browser = 'Browser';
        foreach (['MicroMessenger' => 'WeChat', 'QQBrowser' => 'QQ Browser', 'Edg/' => 'Edge', 'OPR/' => 'Opera', 'Firefox/' => 'Firefox', 'CriOS' => 'Chrome', 'Chrome/' => 'Chrome', 'Safari/' => 'Safari'] as $needle => $name) {
            if (stripos($ua, $needle) !== false) { $browser = $name; break; }
        }
        return $browser . ' on ' . $os;
    }
}
