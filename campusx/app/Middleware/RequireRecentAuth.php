<?php
declare(strict_types=1);
namespace SdutSwap\Middleware;

use SdutSwap\Core\Auth;
use SdutSwap\Services\ReauthService;

/**
 * Admin step-up authentication. Sensitive admin routes (suspending users, moderating
 * listings, deciding verifications, opening Student Card images) require the admin to
 * have re-entered their password within ADMIN_REAUTH_TTL seconds. Otherwise the admin
 * is sent to /admin/confirm and then returned to the page they came from.
 */
final class RequireRecentAuth implements Middleware
{
    public function handle(): void
    {
        Auth::requireAdmin();
        if (ReauthService::isFresh($_SESSION, time(), ReauthService::ttl())) {
            return;
        }
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        if ($method === 'GET') {
            $next = (string) (parse_url($_SERVER['REQUEST_URI'] ?? '/admin', PHP_URL_PATH) ?: '/admin');
            flash('info', 'Please confirm your password to continue.');
        } else {
            // A POST body would be lost in the redirect, so return to the page that hosted the form.
            $ref = (string) (parse_url($_SERVER['HTTP_REFERER'] ?? '', PHP_URL_PATH) ?: '/admin');
            $next = $ref;
            flash('info', 'Please confirm your password, then repeat the action.');
        }
        redirect('/admin/confirm?next=' . rawurlencode(ReauthService::safeNext($next)));
    }
}
