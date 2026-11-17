<?php
declare(strict_types=1);
namespace SdutSwap\Middleware;

use SdutSwap\Core\Auth;

final class RequireAuth implements Middleware
{
    public function handle(): void
    {
        Auth::requireLogin();
    }
}
