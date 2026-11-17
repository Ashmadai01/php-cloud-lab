<?php
declare(strict_types=1);
namespace SdutSwap\Controllers;

use SdutSwap\Core\Auth;
use SdutSwap\Core\Csrf;
use SdutSwap\Core\RateLimiter;

abstract class Controller
{
    protected function view(string $template, array $data = [], int $status = 200): void
    {
        http_response_code($status);
        $data['user'] = Auth::user();
        $data['flashes'] = flashes();
        $layout = \SdutSwap\Core\Config::basePath() . '/app/Views/layout.php';
        $viewFile = \SdutSwap\Core\Config::basePath() . '/app/Views/' . $template . '.php';
        if (!is_file($viewFile)) abort(404, 'Page not found');
        extract($data, EXTR_SKIP);
        ob_start();
        require $viewFile;
        $content = (string) ob_get_clean();
        require $layout;
        exit;
    }

    protected function json(array $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        exit;
    }

    protected function requireCsrf(): void
    {
        $token = $_POST['_csrf'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
        if (!Csrf::validate(is_string($token) ? $token : null)) {
            \SdutSwap\Core\Audit::security('csrf_failure', 'medium', 'CSRF validation failed');
            abort(419, 'Session expired. Please refresh and try again.');
        }
    }

    protected function rateLimit(string $action, int $max, int $window, int $block = 900): void
    {
        if (!RateLimiter::check(RateLimiter::ipBucket($action), $max, $window, $block)) {
            abort(429, 'Too many requests. Please wait and try again.');
        }
    }

    protected function input(string $key, string $default = ''): string
    {
        $v = $_POST[$key] ?? $_GET[$key] ?? $default;
        return is_string($v) ? trim($v) : $default;
    }

    /** Streams an image from private storage (authorization must already have been checked by the caller). */
    protected function sendPrivateImage(string $path): never
    {
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($path) ?: 'application/octet-stream';
        if (!in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)) abort(404, 'File not found');
        header('Content-Type: ' . $mime);
        header('Content-Disposition: inline; filename="attachment"');
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: private, no-store');
        readfile($path);
        exit;
    }
}
