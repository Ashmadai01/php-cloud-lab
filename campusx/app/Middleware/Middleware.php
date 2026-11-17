<?php
declare(strict_types=1);
namespace SdutSwap\Middleware;

/**
 * A route-level check that runs before the controller.
 * handle() returns normally when the request may proceed; otherwise it must
 * end the request itself (abort()/redirect() both exit).
 */
interface Middleware
{
    public function handle(): void;
}
