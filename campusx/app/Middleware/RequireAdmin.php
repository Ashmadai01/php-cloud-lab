<?php
declare(strict_types=1);
namespace SdutSwap\Middleware;

use SdutSwap\Core\Auth;

/** Server-side admin gate: 302 to /login when signed out, 403 (and a security event) for non-admins. */
final class RequireAdmin implements Middleware
{
    public function handle(): void
    {
        Auth::requireAdmin();
    }
}
