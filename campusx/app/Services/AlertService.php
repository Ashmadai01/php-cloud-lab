<?php
declare(strict_types=1);
namespace SdutSwap\Services;

use SdutSwap\Core\Config;
use SdutSwap\Core\Mailer;
use SdutSwap\Core\RateLimiter;

/**
 * Optional e-mail alerts for serious security events. Enabled by setting SECURITY_ALERT_EMAIL.
 * Alerts fire for high-severity events and for rate-limit lockouts, at most once per event type
 * per 15 minutes, and never contain passwords, tokens or message contents (the events don't carry them).
 */
final class AlertService
{
    private const ALERT_EVENT_TYPES = ['rate_limit_block'];
    private static bool $busy = false;

    public static function shouldAlert(string $eventType, string $severity): bool
    {
        return $severity === 'high' || in_array($eventType, self::ALERT_EVENT_TYPES, true);
    }

    public static function maybeAlert(string $eventType, string $severity, ?string $details): void
    {
        $to = Config::get('SECURITY_ALERT_EMAIL');
        if ($to === '' || self::$busy || !self::shouldAlert($eventType, $severity)) {
            return;
        }
        self::$busy = true; // the throttle below may itself log a security event; do not recurse
        try {
            if (!RateLimiter::check('alert:' . $eventType, 1, 900, 900)) {
                return;
            }
            $body = "A security event was recorded on SDUT Swap.\n\n"
                . "Type: {$eventType}\nSeverity: {$severity}\nTime (UTC): " . gmdate('c') . "\n"
                . 'Details: ' . ($details ?? '-') . "\n\nReview all events: " . absUrl('/admin/security') . "\n";
            Mailer::send($to, "[SDUT Swap] Security alert: {$eventType}", $body);
        } catch (\Throwable $e) {
            error_log('Security alert failed: ' . $e->getMessage());
        } finally {
            self::$busy = false;
        }
    }
}
