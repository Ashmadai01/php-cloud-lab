<?php
declare(strict_types=1);

use SdutSwap\Core\Config;

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function appBaseUrl(): string
{
    // Absolute base for non-browser contexts (email links). Prefers the current
    // request host when available; falls back to APP_URL for CLI.
    $host = $_SERVER['HTTP_X_FORWARDED_HOST'] ?? $_SERVER['HTTP_HOST'] ?? '';
    if ($host !== '' && !str_contains($host, 'localhost') && !str_contains($host, '127.0.0.1')) {
        $scheme = 'http';
        if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
            $scheme = strtolower((string) $_SERVER['HTTP_X_FORWARDED_PROTO']);
        } elseif (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
            $scheme = 'https';
        }
        return $scheme . '://' . $host;
    }
    return rtrim(Config::get('APP_URL', ''), '/');
}

function absUrl(string $path): string
{
    return appBaseUrl() . '/' . ltrim($path, '/');
}

// Browser-facing URLs are always relative so they resolve correctly behind any
// proxy/preview host (and avoid mixed-content issues) without host detection.
function url(string $path): string
{
    return '/' . ltrim($path, '/');
}

function redirect(string $path): never
{
    header('Location: ' . url($path));
    exit;
}

function flash(string $type, string $message): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) session_start();
    $_SESSION['_flash'][] = ['type' => $type, 'message' => $message];
}

function flashes(): array
{
    if (session_status() !== PHP_SESSION_ACTIVE) session_start();
    $f = $_SESSION['_flash'] ?? [];
    unset($_SESSION['_flash']);
    return $f;
}

function old(string $key, string $default = ''): string
{
    return (string) ($_SESSION['_old'][$key] ?? $default);
}

function setOld(array $data): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) session_start();
    $_SESSION['_old'] = $data;
}

function clearOld(): void
{
    unset($_SESSION['_old']);
}

function formatMoney(string|float|int $amount, string $currency = 'CNY'): string
{
    // The marketplace is CNY-only. Any legacy/unknown code is shown with its code, never as ¥.
    $symbols = ['CNY' => '¥'];
    $sym = $symbols[strtoupper($currency)] ?? (strtoupper($currency) . ' ');
    return $sym . number_format((float) $amount, 2);
}

function timeAgo(?string $datetime): string
{
    if (!$datetime) return '';
    $ts = strtotime($datetime . ' UTC');
    if ($ts === false) return $datetime;
    $diff = time() - $ts;
    if ($diff < 60) return 'just now';
    if ($diff < 3600) return floor($diff / 60) . ' min ago';
    if ($diff < 86400) return floor($diff / 3600) . ' hr ago';
    if ($diff < 2592000) return floor($diff / 86400) . ' days ago';
    return date('M j, Y', $ts);
}

function displayDate(?string $datetime): string
{
    if (!$datetime) return '';
    $ts = strtotime($datetime . ' UTC');
    if ($ts === false) return $datetime;
    // Stored in UTC, shown in the campus time zone (configurable; default China Standard Time).
    try {
        $tz = new DateTimeZone(Config::get('DISPLAY_TIMEZONE', 'Asia/Shanghai'));
    } catch (\Throwable) {
        $tz = new DateTimeZone('UTC');
    }
    return (new DateTimeImmutable('@' . $ts))->setTimezone($tz)->format('M j, Y g:i A');
}

function csrfField(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(\SdutSwap\Core\Csrf::token()) . '">';
}

function csrfMeta(): string
{
    return '<meta name="csrf-token" content="' . e(\SdutSwap\Core\Csrf::token()) . '">';
}

function asset(string $path): string
{
    return url('assets/' . ltrim($path, '/'));
}

/** Public URL of a listing's cover photo, or the local placeholder when it has none. */
function listingCoverUrl(?string $storedPath): string
{
    if ($storedPath !== null && $storedPath !== '') {
        return \SdutSwap\Core\Upload::publicPath($storedPath);
    }
    return asset('img/placeholder.svg');
}

function verificationLabel(?string $status): string
{
    return match ($status) {
        'pending' => 'Pending review',
        'verified' => 'Verified',
        'rejected' => 'Rejected',
        default => 'Not submitted',
    };
}

function isVerifiedUser(): bool
{
    $u = \SdutSwap\Core\Auth::user();
    return $u !== null && !empty($u['is_verified']);
}

function can(string $ability): bool
{
    $u = \SdutSwap\Core\Auth::user();
    if ($u === null) return false;
    if (($u['role'] ?? '') === 'admin') return true;
    return false;
}

function abort(int $code, string $message = 'Not found'): never
{
    http_response_code($code);
    $view = Config::basePath() . '/app/Views/errors/' . $code . '.php';
    if (is_file($view)) {
        $title = $message;
        require $view;
    } else {
        echo '<!doctype html><html><head><title>' . $code . '</title></head><body><h1>' . $code . '</h1><p>' . e($message) . '</p></body></html>';
    }
    exit;
}
