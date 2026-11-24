<?php
declare(strict_types=1);
namespace SdutSwap\Core;

final class Session
{
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) return;
        ini_set('session.use_strict_mode', '1');
        $secure = Config::get('APP_ENV') === 'production';
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'secure' => $secure,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_name('SDUTSWAPSID');
        session_start();
        // Idle timeout
        $idle = Config::int('SESSION_IDLE_TIMEOUT', 1800);
        if (isset($_SESSION['_last_activity']) && (time() - (int) $_SESSION['_last_activity']) > $idle) {
            \SdutSwap\Services\SessionService::revokeCurrent();
            self::destroy();
            session_start();
        }
        $_SESSION['_last_activity'] = time();
        // Absolute lifetime
        $life = Config::int('SESSION_LIFETIME', 7200);
        if (isset($_SESSION['_created_at']) && (time() - (int) $_SESSION['_created_at']) > $life) {
            \SdutSwap\Services\SessionService::revokeCurrent();
            self::destroy();
            session_start();
        }
        if (!isset($_SESSION['_created_at'])) $_SESSION['_created_at'] = time();
    }

    /**
     * Regenerate the PHP session ID.
     * When tracking is required, the existing server-side row must rotate too.
     */
    public static function regenerate(bool $requireTracked = false): bool
    {
        if (session_status() !== PHP_SESSION_ACTIVE) return false;
        $old = hash('sha256', (string) session_id());
        if (!session_regenerate_id(true)) return false;
        $_SESSION['_created_at'] = time();
        if ($requireTracked) {
            $new = hash('sha256', (string) session_id());
            if (!\SdutSwap\Services\SessionService::rotate($old, $new)) return false;
        }
        return true;
    }

    public static function destroy(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION = [];
            if (ini_get('session.use_cookies')) {
                $p = session_get_cookie_params();
                setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
            }
            session_destroy();
        }
    }
}
