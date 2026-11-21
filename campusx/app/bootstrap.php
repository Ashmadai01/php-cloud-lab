<?php
declare(strict_types=1);

use SdutSwap\Core\Config;
use SdutSwap\Core\Router;
use SdutSwap\Core\Session;
use SdutSwap\Controllers\AdminController;
use SdutSwap\Controllers\AuthController;
use SdutSwap\Controllers\BlockController;
use SdutSwap\Controllers\ChatController;
use SdutSwap\Controllers\HomeController;
use SdutSwap\Controllers\ListingController;
use SdutSwap\Controllers\ProfileController;
use SdutSwap\Controllers\SupportController;
use SdutSwap\Controllers\UploadController;
use SdutSwap\Controllers\VerificationController;
use SdutSwap\Middleware\RequireAdmin;
use SdutSwap\Middleware\RequireAuth;
use SdutSwap\Middleware\RequireCsrf;
use SdutSwap\Middleware\RequireRecentAuth;

require __DIR__ . '/Core/helpers.php';

spl_autoload_register(function (string $class): void {
    $prefix = 'SdutSwap\\';
    if (!str_starts_with($class, $prefix)) return;
    $relative = substr($class, strlen($prefix));
    $file = __DIR__ . '/' . str_replace('\\', '/', $relative) . '.php';
    if (is_file($file)) require $file;
});

Config::load(dirname(__DIR__) . '/.env');

$debug = Config::bool('APP_DEBUG', false);
if (Config::get('APP_ENV') === 'production') $debug = false;
ini_set('display_errors', $debug ? '1' : '0');
ini_set('display_startup_errors', $debug ? '1' : '0');
error_reporting($debug ? E_ALL : E_ALL & ~E_DEPRECATED);
date_default_timezone_set('UTC');
header_remove('X-Powered-By');

// Security headers
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');
header("Content-Security-Policy: default-src 'self'; img-src 'self' data:; style-src 'self' 'unsafe-inline'; script-src 'self'; font-src 'self'; frame-ancestors 'none'; base-uri 'self'; form-action 'self'");
header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
if (Config::get('APP_ENV') === 'production') {
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
}

Session::start();

$router = new Router();
$home = new HomeController();
$auth = new AuthController();
$listings = new ListingController();
$profile = new ProfileController();
$verify = new VerificationController();
$chat = new ChatController();
$support = new SupportController();
$admin = new AdminController();
$uploads = new UploadController();
$blocks = new BlockController();

// Route-level middleware (centralized access control). Controllers still re-check as defense in depth.
$mAuth = new RequireAuth();                 // must be signed in
$mAdmin = new RequireAdmin();               // must be an admin (server-side)
$mCsrf = new RequireCsrf();                 // valid CSRF token on state-changing requests
$mReauth = new RequireRecentAuth();         // admin re-entered password recently (sensitive actions)

// Public
$router->get('/', fn() => $home->index());
$router->get('/about', fn() => $home->page('about'));
$router->get('/guidelines', fn() => $home->page('guidelines'));
$router->get('/privacy', fn() => $home->page('privacy'));
$router->get('/terms', fn() => $home->page('terms'));
$router->get('/safety', fn() => $home->page('safety'));
$router->get('/marketplace', fn() => $listings->browse());
$router->get('/listings/{id}', fn($id) => $listings->show((int) $id));
$router->get('/uploads/{path}', fn($path) => $uploads->serve(urldecode($path)));

// Auth
$router->get('/register', fn() => $auth->showRegister());
$router->post('/register', fn() => $auth->register(), [$mCsrf]);
$router->get('/login', fn() => $auth->showLogin());
$router->post('/login', fn() => $auth->login(), [$mCsrf]);
$router->post('/logout', fn() => $auth->logout(), [$mCsrf]);
$router->get('/forgot-password', fn() => $auth->showForgot());
$router->post('/forgot-password', fn() => $auth->forgot(), [$mCsrf]);
$router->get('/reset-password', fn() => $auth->showReset());
$router->post('/reset-password', fn() => $auth->reset(), [$mCsrf]);
$router->get('/verify-email', fn() => $auth->verifyEmail());
$router->post('/resend-verification', fn() => $auth->resendVerification(), [$mCsrf]);

// Account
$router->get('/profile', fn() => $profile->show(), [$mAuth]);
$router->post('/profile', fn() => $profile->update(), [$mAuth, $mCsrf]);
$router->post('/profile/password', fn() => $profile->changePassword(), [$mAuth, $mCsrf]);
$router->post('/profile/sessions/revoke-others', fn() => $profile->revokeOtherSessions(), [$mAuth, $mCsrf]);
$router->post('/profile/sessions/{id}/revoke', fn($id) => $profile->revokeSession((int) $id), [$mAuth, $mCsrf]);
$router->get('/verify', fn() => $verify->show(), [$mAuth]);
$router->post('/verify', fn() => $verify->submit(), [$mAuth, $mCsrf]);
$router->post('/users/{id}/block', fn($id) => $blocks->block((int) $id), [$mAuth, $mCsrf]);
$router->post('/users/{id}/unblock', fn($id) => $blocks->unblock((int) $id), [$mAuth, $mCsrf]);

// Listings (user)
$router->get('/sell', fn() => $listings->create(), [$mAuth]);
$router->post('/sell', fn() => $listings->store(), [$mAuth, $mCsrf]);
$router->get('/my-listings', fn() => $listings->myListings(), [$mAuth]);
$router->get('/listings/{id}/edit', fn($id) => $listings->edit((int) $id), [$mAuth]);
$router->post('/listings/{id}/edit', fn($id) => $listings->update((int) $id), [$mAuth, $mCsrf]);
$router->post('/listings/{id}/delete', fn($id) => $listings->delete((int) $id), [$mAuth, $mCsrf]);
$router->post('/listings/{id}/favorite', fn($id) => $listings->toggleFavorite((int) $id), [$mAuth, $mCsrf]);
$router->get('/favorites', fn() => $listings->favorites(), [$mAuth]);
$router->post('/listings/{lid}/images/{iid}/delete', fn($lid, $iid) => $listings->deleteImage((int) $lid, (int) $iid), [$mAuth, $mCsrf]);

// Chat
$router->get('/messages', fn() => $chat->inbox(), [$mAuth]);
$router->post('/listings/{id}/message', fn($id) => $chat->start((int) $id), [$mAuth, $mCsrf]);
$router->get('/messages/unread', fn() => $chat->unreadStatus(), [$mAuth]);
$router->get('/messages/{id}', fn($id) => $chat->thread((int) $id), [$mAuth]);
$router->post('/messages/{id}/send', fn($id) => $chat->send((int) $id), [$mAuth, $mCsrf]);
$router->get('/messages/{id}/poll', fn($id) => $chat->poll((int) $id), [$mAuth]);

// Support
$router->get('/support', fn() => $support->faq());
$router->get('/support/my', fn() => $support->myTickets(), [$mAuth]);
$router->get('/support/tickets/{id}', fn($id) => $support->showTicket((int) $id), [$mAuth]);
$router->get('/support/attachments/{id}', fn($id) => $support->attachment((int) $id), [$mAuth]);
$router->get('/support/new', fn() => $support->createTicket(), [$mAuth]);
$router->post('/support/new', fn() => $support->storeTicket(), [$mAuth, $mCsrf]);
$router->post('/support/tickets/{id}/reply', fn($id) => $support->replyTicket((int) $id), [$mAuth, $mCsrf]);
$router->get('/report/{type}/{id}', fn($type, $id) => $support->reportForm($type, (int) $id), [$mAuth]);
$router->post('/report', fn() => $support->storeReport(), [$mAuth, $mCsrf]);

// Admin (every route requires an admin; sensitive actions also require a recent password confirmation)
$router->get('/admin', fn() => $admin->dashboard(), [$mAdmin]);
$router->get('/admin/confirm', fn() => $admin->showConfirm(), [$mAdmin]);
$router->post('/admin/confirm', fn() => $admin->confirm(), [$mAdmin, $mCsrf]);
$router->get('/admin/users', fn() => $admin->users(), [$mAdmin]);
$router->post('/admin/users/{id}/suspend', fn($id) => $admin->suspendUser((int) $id), [$mAdmin, $mCsrf, $mReauth]);
$router->post('/admin/users/{id}/restore', fn($id) => $admin->restoreUser((int) $id), [$mAdmin, $mCsrf, $mReauth]);
$router->get('/admin/listings', fn() => $admin->listings(), [$mAdmin]);
$router->post('/admin/listings/{id}/moderate', fn($id) => $admin->moderateListing((int) $id), [$mAdmin, $mCsrf, $mReauth]);
$router->get('/admin/verifications', fn() => $admin->verifications(), [$mAdmin]);
$router->post('/admin/verifications/{id}/decide', fn($id) => $admin->decideVerification((int) $id), [$mAdmin, $mCsrf, $mReauth]);
$router->get('/admin/verifications/{id}/card', fn($id) => $admin->viewCard((int) $id), [$mAdmin, $mReauth]);
$router->get('/admin/tickets', fn() => $admin->tickets(), [$mAdmin]);
$router->get('/admin/tickets/{id}', fn($id) => $admin->ticketDetail((int) $id), [$mAdmin]);
$router->post('/admin/tickets/{id}/reply', fn($id) => $admin->replyTicket((int) $id), [$mAdmin, $mCsrf]);
$router->get('/admin/reports', fn() => $admin->reports(), [$mAdmin]);
$router->get('/admin/reports/{id}', fn($id) => $admin->reportDetail((int) $id), [$mAdmin]);
$router->get('/admin/reports/{id}/evidence/{eid}', fn($id, $eid) => $admin->evidence((int) $id, (int) $eid), [$mAdmin]);
$router->post('/admin/reports/{id}/decide', fn($id) => $admin->decideReport((int) $id), [$mAdmin, $mCsrf]);
$router->get('/admin/audit', fn() => $admin->audit(), [$mAdmin]);
$router->get('/admin/security', fn() => $admin->securityEvents(), [$mAdmin]);

if (defined('SDUTSWAP_NO_DISPATCH')) {
    return;
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
try {
    $router->dispatch($method, $uri);
} catch (\Throwable $e) {
    error_log('Unhandled error: ' . $e->getMessage());
    if ($debug) {
        echo '<pre>' . e($e->getMessage()) . '</pre>';
    } else {
        abort(500, 'Something went wrong. Please try again later.');
    }
}
