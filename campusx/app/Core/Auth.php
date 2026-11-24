<?php
declare(strict_types=1);
namespace SdutSwap\Core;

use SdutSwap\Repositories\UserRepository;
use SdutSwap\Services\SessionService;

final class Auth
{
    private static ?array $user = null;
    private static bool $resolved = false;

    public static function user(): ?array
    {
        if (self::$resolved) return self::$user;
        self::$resolved = true;
        if (session_status() !== PHP_SESSION_ACTIVE) Session::start();
        $id = $_SESSION['user_id'] ?? null;
        if (!$id) { self::$user = null; return null; }
        $u = (new UserRepository())->findById((int) $id);
        if (!$u || !empty($u['suspended_at'])) {
            self::logout();
            self::$user = null;
            return null;
        }
        // The browser session must still be a known, un-revoked session of this user.
        if (!SessionService::validate((int) $id)) {
            self::logout();
            self::$user = null;
            return null;
        }
        self::$user = $u;
        return $u;
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    public static function id(): ?int
    {
        $u = self::user();
        return $u ? (int) $u['id'] : null;
    }

    public static function isAdmin(): bool
    {
        $u = self::user();
        return $u !== null && ($u['role'] ?? '') === 'admin';
    }

    public static function login(array $user): bool
    {
        if (session_status() !== PHP_SESSION_ACTIVE) Session::start();
        // A guest session has no user_sessions row to rotate.
        if (!Session::regenerate(false)) {
            self::failLoginClosed();
            return false;
        }
        $_SESSION['user_id'] = (int) $user['id'];
        if (!SessionService::register((int) $user['id'])) {
            self::failLoginClosed();
            return false;
        }
        Csrf::rotate();
        self::$user = $user;
        self::$resolved = true;
        (new UserRepository())->touchLogin((int) $user['id']);
        Audit::log('login_success', 'user', (int) $user['id']);
        return true;
    }

    private static function failLoginClosed(): void
    {
        SessionService::revokeCurrent();
        self::$user = null;
        self::$resolved = true;
        Session::destroy();
    }

    public static function logout(): void
    {
        SessionService::revokeCurrent();
        if (session_status() === PHP_SESSION_ACTIVE) {
            unset($_SESSION['user_id']);
        }
        self::$user = null;
        self::$resolved = true;
        Session::destroy();
    }

    public static function requireLogin(string $redirectTo = '/login'): void
    {
        if (!self::check()) {
            flash('error', 'Please sign in to continue.');
            redirect($redirectTo);
        }
    }

    public static function requireAdmin(): void
    {
        if (!self::check()) {
            flash('error', 'Please sign in to continue.');
            redirect('/login');
        }
        if (!self::isAdmin()) {
            Audit::security('unauthorized_admin_access', 'high', 'Non-admin attempted admin route');
            abort(403, 'Forbidden');
        }
    }

    public static function reset(): void
    {
        self::$user = null;
        self::$resolved = false;
    }
}
