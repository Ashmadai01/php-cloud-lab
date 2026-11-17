<?php
declare(strict_types=1);
namespace SdutSwap\Middleware;

use SdutSwap\Core\Audit;
use SdutSwap\Core\Csrf;

/** Validates the synchronizer token on every state-changing request it is attached to. */
final class RequireCsrf implements Middleware
{
    public function handle(): void
    {
        $token = $_POST['_csrf'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
        if (!Csrf::validate(is_string($token) ? $token : null)) {
            Audit::security('csrf_failure', 'medium', 'CSRF validation failed');
            abort(419, 'Session expired. Please refresh and try again.');
        }
    }
}
