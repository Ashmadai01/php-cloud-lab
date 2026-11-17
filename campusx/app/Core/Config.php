<?php
declare(strict_types=1);
namespace SdutSwap\Core;

final class Config
{
    private static array $data = [];

    public static function load(string $envFile): void
    {
        $defaults = [
            'APP_ENV' => 'local', 'APP_DEBUG' => 'true', 'APP_URL' => 'http://localhost:8080',
            'APP_NAME' => 'SDUT Swap', 'DB_HOST' => '127.0.0.1', 'DB_PORT' => '3306',
            'DB_NAME' => 'campusx', 'DB_USER' => 'campusx_app', 'DB_PASS' => '',
            'DB_CHARSET' => 'utf8mb4', 'SESSION_LIFETIME' => '7200',
            'SESSION_IDLE_TIMEOUT' => '1800', 'MAIL_ENABLED' => 'false',
            'MAIL_HOST' => '', 'MAIL_PORT' => '587', 'MAIL_USERNAME' => '',
            'MAIL_PASSWORD' => '', 'MAIL_FROM' => 'noreply@example.com',
            'MAIL_FROM_NAME' => 'SDUT Swap', 'UPLOAD_MAX_SIZE' => '5242880',
            'UPLOAD_DIR' => 'storage/uploads', 'PRIVATE_DIR' => 'storage/private',
            'REQUIRE_VERIFIED_TO_LIST' => 'true', 'REQUIRE_VERIFIED_TO_CHAT' => 'true',
            'REQUIRE_EMAIL_VERIFICATION' => 'true', 'ALLOWED_EMAIL_DOMAINS' => '',
            'TRUSTED_PROXIES' => '',
            'DB_MIGRATE_USER' => '', 'DB_MIGRATE_PASS' => '',
            'ADMIN_REAUTH_TTL' => '600',
            'CARD_RETENTION_DAYS' => '30', 'EVIDENCE_RETENTION_DAYS' => '180',
            'SECURITY_ALERT_EMAIL' => '',
            'DISPLAY_TIMEZONE' => 'Asia/Shanghai',
        ];
        $fileData = [];
        if (is_file($envFile)) {
            foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
                $line = trim($line);
                if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) continue;
                [$k, $v] = explode('=', $line, 2);
                $k = trim($k); $v = trim($v);
                if (strlen($v) >= 2 && (($v[0] === '"' && str_ends_with($v, '"')) || ($v[0] === "'" && str_ends_with($v, "'")))) {
                    $v = substr($v, 1, -1);
                }
                $fileData[$k] = $v;
            }
        }
        self::$data = array_merge($defaults, $fileData);
        // Server environment wins over .env file.
        foreach (self::$data as $k => $v) {
            $env = getenv($k);
            if ($env !== false && $env !== '') self::$data[$k] = $env;
        }
        if (self::get('APP_ENV') === 'production') {
            foreach (['DB_PASS'] as $required) {
                if (empty(self::$data[$required])) {
                    throw new \RuntimeException("Missing required production config: {$required}");
                }
            }
        }
    }

    public static function get(string $key, string $default = ''): string
    {
        return self::$data[$key] ?? $default;
    }

    public static function bool(string $key, bool $default = false): bool
    {
        $v = strtolower(trim(self::get($key, $default ? 'true' : 'false')));
        return in_array($v, ['1', 'true', 'yes', 'on'], true);
    }

    public static function int(string $key, int $default = 0): int
    {
        return (int) self::get($key, (string) $default);
    }

    public static function basePath(): string
    {
        return dirname(__DIR__, 2);
    }
}
