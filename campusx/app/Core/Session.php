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

    public static function regenerate(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            $old = hash('sha256', (string) session_id());
            session_regenerate_id(true);
            $_SESSION['_created_at'] = time();
            // Keep the "sessions & devices" tracking row pointing at the new session ID.
            \SdutSwap\Services\SessionService::rotate($old, hash('sha256', (string) session_id()));
        }
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
