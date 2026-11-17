<?php
declare(strict_types=1);
namespace SdutSwap\Core;

use SdutSwap\Repositories\AuditRepository;

final class Audit
{
    public static function log(string $action, ?string $targetType = null, ?int $targetId = null, ?string $details = null): void
    {
        try {
            $adminId = Auth::id();
            if ($adminId === null) return;
            (new AuditRepository())->log($adminId, $action, $targetType, $targetId, $details, self::ipHash());
        } catch (\Throwable) {
            // Never break the request for audit failures.
        }
    }

    public static function security(string $eventType, string $severity = 'low', ?string $details = null, ?int $userId = null): void
    {
        try {
            (new AuditRepository())->securityEvent($eventType, $severity, $userId ?? Auth::id(), $details, self::ipHash());
            \SdutSwap\Services\AlertService::maybeAlert($eventType, $severity, $details);
        } catch (\Throwable) {
        }
    }

    private static function ipHash(): string
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        return hash('sha256', $ip . '|' . (Config::get('APP_NAME', 'SDUT Swap')));
    }
}
