<?php
declare(strict_types=1);
namespace SdutSwap\Core;

final class Csrf
{
    public static function token(): string
    {
        if (session_status() !== PHP_SESSION_ACTIVE) Session::start();
        if (empty($_SESSION['_csrf'])) {
            $_SESSION['_csrf'] = bin2hex(random_bytes(32));
        }
        return (string) $_SESSION['_csrf'];
    }

    public static function validate(?string $token): bool
    {
        if (session_status() !== PHP_SESSION_ACTIVE) Session::start();
        $expected = $_SESSION['_csrf'] ?? '';
        if (!is_string($token) || $token === '' || $expected === '') return false;
        return hash_equals($expected, $token);
    }

    public static function rotate(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION['_csrf'] = bin2hex(random_bytes(32));
        }
    }
}
