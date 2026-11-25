<?php
declare(strict_types=1);
// Buffer test output so session headers can still be managed during tests.
ob_start();
// SDUT Swap test suite — runs against an in-memory SQLite database.
// Usage: php tests/run.php
// Covers: validators, auth hashing, listing CRUD, favorites, verification,
// chat access control (IDOR), tickets/reports, admin authorization, CSRF,
// XSS encoding, SQLi resistance, upload validation.

define('SDUTSWAP_NO_DISPATCH', true);
define('SDUTSWAP_TESTING', true);
require dirname(__DIR__) . '/app/bootstrap.php';

use SdutSwap\Core\Config;
use SdutSwap\Core\Csrf;
use SdutSwap\Core\Database;
use SdutSwap\Core\Router;
use SdutSwap\Core\Upload;
use SdutSwap\Core\Validator;
use SdutSwap\Controllers\AdminController;
use SdutSwap\Middleware\Middleware;
use SdutSwap\Middleware\RequireAdmin;
use SdutSwap\Middleware\RequireAuth;
use SdutSwap\Middleware\RequireCsrf;
use SdutSwap\Middleware\RequireRecentAuth;
use SdutSwap\Repositories\BlockRepository;
use SdutSwap\Repositories\SessionRepository;
use SdutSwap\Services\AlertService;
use SdutSwap\Services\BlockService;
use SdutSwap\Services\ReauthService;
use SdutSwap\Services\RetentionService;
use SdutSwap\Services\SessionService;
use SdutSwap\Services\VerificationService;
use SdutSwap\Repositories\ChatRepository;
use SdutSwap\Repositories\ListingRepository;
use SdutSwap\Repositories\SupportRepository;
use SdutSwap\Repositories\TokenRepository;
use SdutSwap\Repositories\UserRepository;
use SdutSwap\Repositories\VerificationRepository;

$pass = 0; $fail = 0;
function check(string $name, bool $cond): void
{
    global $pass, $fail;
    if ($cond) { $pass++; echo "  PASS {$name}\n"; }
    else { $fail++; echo "  FAIL {$name}\n"; }
}

// --- Setup: SQLite in-memory with minimal schema ---
$pdo = new PDO('sqlite::memory:');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
Database::setPdo($pdo);

$pdo->exec("CREATE TABLE users (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT, email TEXT UNIQUE, password_hash TEXT, role TEXT DEFAULT 'student', university TEXT, bio TEXT, avatar_path TEXT, is_verified INTEGER DEFAULT 0, verification_status TEXT DEFAULT 'not_submitted', email_verified_at TEXT, suspended_at TEXT, suspension_reason TEXT, last_login_at TEXT, created_at TEXT DEFAULT CURRENT_TIMESTAMP, updated_at TEXT DEFAULT CURRENT_TIMESTAMP)");
$pdo->exec("CREATE TABLE notifications (id INTEGER PRIMARY KEY AUTOINCREMENT, user_id INTEGER NOT NULL, ticket_id INTEGER, conversation_id INTEGER, type TEXT NOT NULL, message TEXT NOT NULL, read_at TEXT, created_at TEXT DEFAULT CURRENT_TIMESTAMP)");
$pdo->exec("CREATE TABLE email_verification_tokens (id INTEGER PRIMARY KEY AUTOINCREMENT, user_id INTEGER, token_hash TEXT UNIQUE, expires_at TEXT, used_at TEXT, created_at TEXT DEFAULT CURRENT_TIMESTAMP)");
$pdo->exec("CREATE TABLE password_reset_tokens (id INTEGER PRIMARY KEY AUTOINCREMENT, user_id INTEGER, token_hash TEXT UNIQUE, expires_at TEXT, used_at TEXT, created_at TEXT DEFAULT CURRENT_TIMESTAMP)");
$pdo->exec("CREATE TABLE categories (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT UNIQUE, slug TEXT UNIQUE, sort_order INTEGER DEFAULT 0, created_at TEXT DEFAULT CURRENT_TIMESTAMP)");
$pdo->exec("CREATE TABLE listings (id INTEGER PRIMARY KEY AUTOINCREMENT, seller_id INTEGER, title TEXT, description TEXT, price REAL, currency TEXT DEFAULT 'CNY', category_id INTEGER, item_condition TEXT DEFAULT 'good', campus TEXT, status TEXT DEFAULT 'draft', moderation_reason TEXT, views INTEGER DEFAULT 0, created_at TEXT DEFAULT CURRENT_TIMESTAMP, updated_at TEXT DEFAULT CURRENT_TIMESTAMP)");
$pdo->exec("CREATE TABLE listing_images (id INTEGER PRIMARY KEY AUTOINCREMENT, listing_id INTEGER, file_path TEXT, sort_order INTEGER DEFAULT 0, created_at TEXT DEFAULT CURRENT_TIMESTAMP)");
$pdo->exec("CREATE TABLE favorites (id INTEGER PRIMARY KEY AUTOINCREMENT, user_id INTEGER, listing_id INTEGER, created_at TEXT DEFAULT CURRENT_TIMESTAMP, UNIQUE(user_id, listing_id))");
$pdo->exec("CREATE TABLE student_verifications (id INTEGER PRIMARY KEY AUTOINCREMENT, user_id INTEGER, full_name TEXT, student_number_hash TEXT, student_number_last4 TEXT, university TEXT, card_image_path TEXT, card_purged_at TEXT, active_hash TEXT UNIQUE, status TEXT DEFAULT 'pending', rejection_reason TEXT, reviewed_by INTEGER, reviewed_at TEXT, created_at TEXT DEFAULT CURRENT_TIMESTAMP, updated_at TEXT DEFAULT CURRENT_TIMESTAMP)");
$pdo->exec("CREATE TABLE conversations (id INTEGER PRIMARY KEY AUTOINCREMENT, listing_id INTEGER, created_by INTEGER, created_at TEXT DEFAULT CURRENT_TIMESTAMP, updated_at TEXT DEFAULT CURRENT_TIMESTAMP)");
$pdo->exec("CREATE TABLE conversation_participants (id INTEGER PRIMARY KEY AUTOINCREMENT, conversation_id INTEGER, user_id INTEGER, last_read_at TEXT, created_at TEXT DEFAULT CURRENT_TIMESTAMP, UNIQUE(conversation_id, user_id))");
$pdo->exec("CREATE TABLE messages (id INTEGER PRIMARY KEY AUTOINCREMENT, conversation_id INTEGER, sender_id INTEGER, body TEXT, created_at TEXT DEFAULT CURRENT_TIMESTAMP)");
$pdo->exec("CREATE TABLE support_tickets (id INTEGER PRIMARY KEY AUTOINCREMENT, user_id INTEGER, ticket_type TEXT DEFAULT 'help', category TEXT, subject TEXT, status TEXT DEFAULT 'open', priority TEXT DEFAULT 'normal', created_at TEXT DEFAULT CURRENT_TIMESTAMP, updated_at TEXT DEFAULT CURRENT_TIMESTAMP)");
$pdo->exec("CREATE TABLE ticket_messages (id INTEGER PRIMARY KEY AUTOINCREMENT, ticket_id INTEGER, author_id INTEGER, body TEXT, attachment_path TEXT, created_at TEXT DEFAULT CURRENT_TIMESTAMP)");
$pdo->exec("CREATE TABLE reports (id INTEGER PRIMARY KEY AUTOINCREMENT, reporter_id INTEGER, target_type TEXT, target_id INTEGER, report_type TEXT, description TEXT, status TEXT DEFAULT 'open', moderator_notes TEXT, created_at TEXT DEFAULT CURRENT_TIMESTAMP, updated_at TEXT DEFAULT CURRENT_TIMESTAMP)");
$pdo->exec("CREATE TABLE report_evidence (id INTEGER PRIMARY KEY AUTOINCREMENT, report_id INTEGER, file_path TEXT, created_at TEXT DEFAULT CURRENT_TIMESTAMP)");
$pdo->exec("CREATE TABLE admin_audit_logs (id INTEGER PRIMARY KEY AUTOINCREMENT, admin_id INTEGER, action TEXT, target_type TEXT, target_id INTEGER, details TEXT, ip_hash TEXT, created_at TEXT DEFAULT CURRENT_TIMESTAMP)");
$pdo->exec("CREATE TABLE security_events (id INTEGER PRIMARY KEY AUTOINCREMENT, event_type TEXT, severity TEXT DEFAULT 'low', user_id INTEGER, ip_hash TEXT, details TEXT, created_at TEXT DEFAULT CURRENT_TIMESTAMP)");
$pdo->exec("CREATE TABLE rate_limits (id INTEGER PRIMARY KEY AUTOINCREMENT, bucket TEXT UNIQUE, attempts INTEGER DEFAULT 0, window_start TEXT DEFAULT CURRENT_TIMESTAMP, blocked_until TEXT)");
$pdo->exec("CREATE TABLE user_sessions (id INTEGER PRIMARY KEY AUTOINCREMENT, user_id INTEGER, session_id_hash TEXT UNIQUE, ip_hash TEXT, user_agent_hash TEXT, device_label TEXT, created_at TEXT DEFAULT CURRENT_TIMESTAMP, last_seen_at TEXT DEFAULT CURRENT_TIMESTAMP, expires_at TEXT, revoked_at TEXT)");
$pdo->exec("CREATE TABLE user_blocks (id INTEGER PRIMARY KEY AUTOINCREMENT, blocker_id INTEGER, blocked_id INTEGER, created_at TEXT DEFAULT CURRENT_TIMESTAMP, UNIQUE(blocker_id, blocked_id))");
$pdo->exec("CREATE TABLE ticket_attachments (id INTEGER PRIMARY KEY AUTOINCREMENT, ticket_message_id INTEGER, file_path TEXT, created_at TEXT DEFAULT CURRENT_TIMESTAMP)");
$pdo->exec("INSERT INTO categories (name, slug) VALUES ('Textbooks','textbooks'),('Electronics','electronics')");

echo "== Validators ==\n";
$v = new Validator();
$v->email('email', 'not-an-email');
check('invalid email rejected', $v->fails());
$v2 = new Validator();
$v2->email('email', 'student@university.edu')->password('password', 'Str0ngPassw0rd');
check('valid email+password accepted', !$v2->fails());
$v3 = new Validator();
$v3->password('password', 'weak');
check('weak password rejected', $v3->fails());
$v4 = new Validator();
$v4->inList('status', 'hacked', ['active', 'draft']);
check('allowlist rejects unknown value', $v4->fails());

echo "== Auth hashing ==\n";
$hash = password_hash('Str0ngPassw0rd', PASSWORD_ARGON2ID, ['memory_cost' => 65536, 'time_cost' => 4, 'threads' => 2]);
if ($hash === false) $hash = password_hash('Str0ngPassw0rd', PASSWORD_BCRYPT, ['cost' => 12]);
check('password_verify succeeds', password_verify('Str0ngPassw0rd', (string) $hash));
check('password_verify rejects wrong password', !password_verify('WrongPass123', (string) $hash));

echo "== Users & tokens ==\n";
$users = new UserRepository($pdo);
$aliceId = $users->create('Alice', 'alice@example.com', (string) $hash);
$bobId = $users->create('Bob', 'bob@example.com', (string) $hash);
check('user created', $aliceId > 0);
check('duplicate email lookup works', $users->findByEmail('alice@example.com') !== null);
$tokens = new TokenRepository($pdo);
$rawToken = bin2hex(random_bytes(32));
$tokens->createEmailVerification($aliceId, $rawToken, 24);
check('email token remains valid until explicit confirmation', $tokens->consumeEmailVerification($rawToken) === $aliceId);
check('email token single-use enforced', $tokens->consumeEmailVerification($rawToken) === null);
$resetToken = bin2hex(random_bytes(32));
$tokens->createPasswordReset($bobId, $resetToken, 60);
check('reset token consumes', $tokens->consumePasswordReset($resetToken) === $bobId);
check('reset token single-use enforced', $tokens->consumePasswordReset($resetToken) === null);

echo "== Listings CRUD ==\n";
$listings = new ListingRepository($pdo);
$lid = $listings->create(['seller_id' => $aliceId, 'title' => 'Test Textbook', 'description' => 'A great book for testing.', 'price' => 19.99, 'currency' => 'CNY', 'category_id' => 1, 'item_condition' => 'good', 'campus' => 'North', 'status' => 'active']);
check('listing created', $lid > 0);
$found = $listings->findById($lid);
check('listing found with seller', $found !== null && $found['seller_name'] === 'Alice');
$results = $listings->search(['q' => 'Textbook'], 1, 12);
check('search finds listing', count($results) === 1);
$results2 = $listings->search(['q' => "' OR 1=1 --"], 1, 12);
check('SQLi search returns no injection', count($results2) === 0);
$listings->setStatus($lid, 'sold');
check('listing status updated', $listings->findById($lid)['status'] === 'sold');
$listings->setStatus($lid, 'active');

echo "== Favorites ==\n";
check('favorite toggled on', $listings->toggleFavorite($bobId, $lid) === true);
check('isFavorite true', $listings->isFavorite($bobId, $lid) === true);
check('favorite toggled off', $listings->toggleFavorite($bobId, $lid) === false);

echo "== Verification ==\n";
$verif = new VerificationRepository($pdo);
$vid = $verif->create($aliceId, 'Alice Smith', '2024001234', 'Example University', 'cards/test.jpg');
check('verification created pending', $vid > 0);
$latest = $verif->latestForUser($aliceId);
check('student number hashed (not plaintext)', $latest['student_number_hash'] !== '2024001234' && strlen($latest['student_number_hash']) === 64);
check('last4 stored for display', $latest['student_number_last4'] === '1234');
$verif->decide($vid, 'verified', $bobId);
check('verification approved', $verif->latestForUser($aliceId)['status'] === 'verified');

echo "== Chat access control (IDOR/BOLA) ==\n";
$chat = new ChatRepository($pdo);
$convId = $chat->findOrCreateConversation($lid, $bobId, $aliceId);
check('conversation created', $convId > 0);
check('buyer is participant', $chat->isParticipant($convId, $bobId) === true);
check('seller is participant', $chat->isParticipant($convId, $aliceId) === true);
$charlieId = $users->create('Charlie', 'charlie@example.com', (string) $hash);
check('outsider is NOT participant (IDOR blocked)', $chat->isParticipant($convId, $charlieId) === false);
$chat->send($convId, $bobId, 'Is this still available?');
$msgs = $chat->messages($convId);
check('message sent and stored', count($msgs) === 1 && $msgs[0]['body'] === 'Is this still available?');
check('unread count for seller', $chat->unreadCount($aliceId) === 1);
$chat->markRead($convId, $aliceId);
check('unread cleared after read', $chat->unreadCount($aliceId) === 0);

echo "== Tickets & reports ==\n";
$support = new SupportRepository($pdo);
$tid = $support->createTicket($aliceId, 'help', 'Account', 'Cannot log in', 'normal', 'I cannot log in since yesterday.');
check('ticket created', $tid > 0);
$ticket = $support->findTicket($tid);
check('ticket owner correct', (int) $ticket['user_id'] === $aliceId);
$support->reply($tid, $aliceId, 'Additional details.');
check('ticket reply added', count($support->ticketMessages($tid)) === 2);
$rid = $support->createReport($bobId, 'listing', $lid, 'scam', 'This listing looks like a scam.');
check('report created', $rid > 0);
$support->setReportStatus($rid, 'under_review', 'Investigating.');
check('report status updated', $support->findReport($rid)['status'] === 'under_review');

echo "== Currency (CNY only) ==\n";
check('formatMoney defaults to yuan', formatMoney(25) === '¥25.00');
check('formatMoney CNY explicit', formatMoney('45.5', 'CNY') === '¥45.50');
check('legacy foreign code is never shown as yuan', formatMoney(10, 'USD') === 'USD 10.00');

echo "== Listing cover photo & placeholder ==\n";
$listings->addImage($lid, 'listings/cover-test.jpg', 0);
$cards = $listings->search(['q' => 'Textbook'], 1, 12);
check('search returns cover_path of first image', ($cards[0]['cover_path'] ?? null) === 'listings/cover-test.jpg');
check('cover URL for a photo points at /uploads', str_contains(listingCoverUrl('listings/cover-test.jpg'), '/uploads/listings/cover-test.jpg'));
check('missing photo falls back to local placeholder', str_ends_with(listingCoverUrl(null), 'assets/img/placeholder.svg'));
check('placeholder file exists locally (no hotlink)', is_file(Config::basePath() . '/public/assets/img/placeholder.svg'));

echo "== Verification status & unique student number ==\n";
check('approval syncs users.verification_status', $users->findById($aliceId)['verification_status'] === 'verified');
check('approval sets is_verified', (int) $users->findById($aliceId)['is_verified'] === 1);
check('new user starts as not_submitted', $users->findById($charlieId)['verification_status'] === 'not_submitted');
check('student number normalisation', VerificationRepository::normalizeStudentNumber('ab-12 34') === 'AB1234');
$vsvc = new VerificationService($verif, $users);
check('same number (dash variant) blocked for another user', $vsvc->precheck($bobId, '2024-001234') === VerificationService::DUPLICATE_NUMBER);
check('verified user cannot resubmit', $vsvc->precheck($aliceId, '9999999999') === VerificationService::ALREADY_VERIFIED);
check('fresh number allowed', $vsvc->precheck($bobId, '5550001111') === VerificationService::OK);
check('submission created', $vsvc->create($bobId, 'Bob Jones', '5550001111', 'Example University', 'cards/bob.jpg') === VerificationService::OK);
check('submitter becomes pending', $users->findById($bobId)['verification_status'] === 'pending');
check('pending user cannot submit again', $vsvc->precheck($bobId, '5550002222') === VerificationService::PENDING);
check('unique index rejects a racing duplicate', $vsvc->create($charlieId, 'Charlie', '5550001111', 'Example University', 'cards/c.jpg') === VerificationService::DUPLICATE_NUMBER);
check('failed duplicate left the other user untouched', $users->findById($charlieId)['verification_status'] === 'not_submitted');
$bobVid = (int) $verif->latestForUser($bobId)['id'];
$verif->decide($bobVid, 'rejected', $aliceId, 'Blurry photo');
check('rejection recorded on the user', $users->findById($bobId)['verification_status'] === 'rejected');
check('rejected user is not verified', (int) $users->findById($bobId)['is_verified'] === 0);
check('rejection releases the student number', $vsvc->precheck($charlieId, '5550001111') === VerificationService::OK);

echo "== Blocking users ==\n";
$blockRepo = new BlockRepository($pdo);
$bsvc = new BlockService($blockRepo, $users);
check('messaging allowed before any block', $bsvc->canMessage($aliceId, $bobId));
check('block succeeds', $bsvc->block($aliceId, $bobId) === BlockService::OK);
check('blocking twice keeps one row', $bsvc->block($aliceId, $bobId) === BlockService::OK && count($blockRepo->listBlockedBy($aliceId)) === 1);
check('blocker cannot message blocked user', !$bsvc->canMessage($aliceId, $bobId));
check('blocked user cannot message blocker', !$bsvc->canMessage($bobId, $aliceId));
check('third parties are unaffected', $bsvc->canMessage($charlieId, $aliceId));
check('blocked user does not see the blocker in their list', count($blockRepo->listBlockedBy($bobId)) === 0);
check('cannot block yourself', $bsvc->block($aliceId, $aliceId) === BlockService::SELF);
check('cannot block a user that does not exist', $bsvc->block($aliceId, 9999) === BlockService::NOT_FOUND);
$adminId = $users->create('Admin', 'admin@example.com', (string) $hash);
$users->setRole($adminId, 'admin');
check('staff accounts cannot be blocked', $bsvc->block($aliceId, $adminId) === BlockService::ADMIN);
check('other participant lookup works', $chat->otherParticipantId($convId, $bobId) === $aliceId);
check('unblock restores messaging', $bsvc->unblock($aliceId, $bobId) && $bsvc->canMessage($aliceId, $bobId));
$firstMsg = $chat->messages($convId)[0];
$found = $chat->findMessage((int) $firstMsg['id']);
check('findMessage returns conversation and sender', $found !== null && (int) $found['conversation_id'] === $convId && (int) $found['sender_id'] === $bobId);

echo "== Sessions & devices ==\n";
$sess = new SessionRepository($pdo);
$future = gmdate('Y-m-d H:i:s', time() + 3600);
$sid1 = hash('sha256', 'sid-1'); $sid2 = hash('sha256', 'sid-2');
$s1 = $sess->create($aliceId, $sid1, null, null, 'Chrome on Windows', $future);
$s2 = $sess->create($aliceId, $sid2, null, null, 'Safari on iOS', $future);
$sess->create($bobId, hash('sha256', 'sid-b'), null, null, 'Firefox on Linux', $future);
$sess->create($aliceId, hash('sha256', 'sid-old'), null, null, 'Old browser', gmdate('Y-m-d H:i:s', time() - 60));
$now = gmdate('Y-m-d H:i:s');
check('only active, unexpired sessions are listed', count($sess->activeForUser($aliceId, $now)) === 2);
check("cannot revoke another user's session", $sess->revokeOwned($s1, $bobId) === false);
check('owner can revoke own session', $sess->revokeOwned($s2, $aliceId) === true);
check('revoked session leaves the list', count($sess->activeForUser($aliceId, $now)) === 1);
check('revoked row is marked', $sess->findByHash($sid2)['revoked_at'] !== null);
$sid1b = hash('sha256', 'sid-1b');
check('session rotation succeeds for an existing session', SessionService::rotate($sid1, $sid1b) === true);
check('session-ID rotation keeps the tracking row', $sess->findByHash($sid1b) !== null && $sess->findByHash($sid1) === null);
check('session rotation fails for a missing session', SessionService::rotate(hash('sha256', 'missing-sid'), hash('sha256', 'new-sid')) === false);
$sess->create($aliceId, hash('sha256', 'sid-3'), null, null, 'Edge on Windows', $future);
// Two rows change: sid-3 and the expired-but-unrevoked sid-old. The current device (sid-1b) is kept.
check('"sign out others" keeps the current device', $sess->revokeAllForUser($aliceId, hash('sha256', 'sid-1b')) === 2 && count($sess->activeForUser($aliceId, $now)) === 1);
check('"sign out everywhere" revokes the rest', $sess->revokeAllForUser($aliceId) === 1 && count($sess->activeForUser($aliceId, $now)) === 0);
check("other users' sessions untouched", count($sess->activeForUser($bobId, $now)) === 1);
check('old session rows can be purged', $sess->purgeOld(gmdate('Y-m-d H:i:s', time() + 7200)) >= 1);
$currentSidHash = hash('sha256', session_id());
check('session registration succeeds', SessionService::register($aliceId) === true);
check('registered session is stored', $sess->findByHash($currentSidHash) !== null);
check('duplicate session registration fails safely', SessionService::register($aliceId) === false);
check('tracked session validates for its owner', SessionService::validate($aliceId) === true);
check('tracked session is rejected for a different user', SessionService::validate($bobId) === false);
check('unknown session is rejected', SessionService::validate($charlieId) === false);
$validPdo = $pdo;
Database::setPdo(new PDO('sqlite::memory:'));
check('session validation fails closed when tracking storage is unavailable', SessionService::validate($aliceId) === false);
Database::setPdo($validPdo);
check('device label: Chrome on Windows', SessionService::deviceLabel('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36') === 'Chrome on Windows');
check('device label: Edge on Windows', SessionService::deviceLabel('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36 Edg/120.0.0.0') === 'Edge on Windows');
check('device label: Safari on iOS', SessionService::deviceLabel('Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Mobile/15E148 Safari/604.1') === 'Safari on iOS');
check('device label: WeChat on Android', SessionService::deviceLabel('Mozilla/5.0 (Linux; Android 13; Pixel 7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0 Mobile Safari/537.36 MicroMessenger/8.0.40') === 'WeChat on Android');
check('device label: empty UA is generic (raw UA never stored)', SessionService::deviceLabel('') === 'Unknown device');

// Exercise the real AdminController action for successful and failed revocation.
echo "== Admin suspension integration ==\n";
$admin = $users->findById($adminId);
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
check('admin session can be established for controller test', $admin !== null && \SdutSwap\Core\Auth::login($admin));
$csrf = Csrf::token();
$_POST = ['_csrf' => $csrf, 'reason' => 'Integration test'];
$successTargetId = $users->create('Suspend Success', 'suspend-success@example.com', (string) $hash);
$sess->create($successTargetId, hash('sha256', 'suspend-success-session'), null, null, 'Test device', $future);
$redirected = null;
try { (new AdminController())->suspendUser($successTargetId); }
catch (RuntimeException $e) { $redirected = $e->getMessage(); }
$successFlash = $_SESSION['_flash'][array_key_last($_SESSION['_flash'] ?? [])] ?? [];
check('controller redirects after successful suspension', $redirected === 'Test redirect: /admin/users');
check('controller success path suspends target account', !empty($users->findById($successTargetId)['suspended_at']));
check('controller success path revokes target sessions', count($sess->activeForUser($successTargetId, gmdate('Y-m-d H:i:s'))) === 0);
check('controller success path flashes revoked session count', str_contains((string) ($successFlash['message'] ?? ''), '1 active session(s) revoked.'));
$failureUserId = $users->create('Suspension Failure', 'suspension-failure@example.com', (string) $hash);
$sess->create($failureUserId, hash('sha256', 'suspension-failure-session'), null, null, 'Test device', $future);
$pdo->exec("CREATE TRIGGER fail_session_revocation BEFORE UPDATE OF revoked_at ON user_sessions BEGIN SELECT RAISE(FAIL, 'simulated session revocation failure'); END");
$_POST = ['_csrf' => $csrf, 'reason' => 'Integration failure test'];
$redirected = null;
try { (new AdminController())->suspendUser($failureUserId); }
catch (RuntimeException $e) { $redirected = $e->getMessage(); }
$failureFlash = $_SESSION['_flash'][array_key_last($_SESSION['_flash'] ?? [])] ?? [];
check('controller redirects after failed revocation', $redirected === 'Test redirect: /admin/users');
check('controller still suspends account when revocation fails', !empty($users->findById($failureUserId)['suspended_at']));
check('controller reports incomplete device sign-out to admin', str_contains((string) ($failureFlash['message'] ?? ''), 'could not confirm that all devices were signed out'));
$failureEvent = $pdo->prepare("SELECT COUNT(*) FROM security_events WHERE event_type = 'session_revocation_failed' AND severity = 'high' AND details LIKE ?");
$failureEvent->execute(['%user ' . $failureUserId]);
check('controller records high-severity revocation failure event', (int) $failureEvent->fetchColumn() === 1);
$pdo->exec('DROP TRIGGER fail_session_revocation');
\SdutSwap\Core\Auth::reset();
\SdutSwap\Core\Auth::logout();

echo "== Admin re-authentication ==\n";
check('no confirmation means not fresh', ReauthService::isFresh([], 1000, 600) === false);
$rs = [];
ReauthService::mark($rs, 1000);
check('fresh within the TTL', ReauthService::isFresh($rs, 1500, 600) === true);
check('stale after the TTL', ReauthService::isFresh($rs, 1601, 600) === false);
check('timestamp from the future is rejected', ReauthService::isFresh(['admin_reauth_at' => 2000], 1000, 600) === false);
check('non-integer timestamp is rejected', ReauthService::isFresh(['admin_reauth_at' => '1000'], 1100, 600) === false);
ReauthService::clear($rs);
check('confirmation can be cleared', ReauthService::isFresh($rs, 1000, 600) === false);
check('safeNext keeps a normal admin path', ReauthService::safeNext('/admin/users') === '/admin/users');
check('safeNext keeps the card path', ReauthService::safeNext('/admin/verifications/5/card') === '/admin/verifications/5/card');
foreach (['https://evil.example/x', '//evil.example', '/admin//evil', '/administrator', '/admin/../etc/passwd', '/admin?x=1', "/admin\n", 'javascript:alert(1)', '/profile', '', '/admin\\evil'] as $bad) {
    check('safeNext rejects ' . json_encode($bad), ReauthService::safeNext($bad) === '/admin');
}
check('safeNext handles null', ReauthService::safeNext(null) === '/admin');

echo "== Security alerts ==\n";
check('low-severity events do not alert', AlertService::shouldAlert('login_failed', 'low') === false);
check('high-severity events alert', AlertService::shouldAlert('chat_idor_attempt', 'high') === true);
check('rate-limit lockouts alert', AlertService::shouldAlert('rate_limit_block', 'medium') === true);

echo "== Private upload path boundaries ==\n";
$privateRoot = Config::basePath() . '/' . trim(Config::get('PRIVATE_DIR', 'storage/private'), '/');
$privateReal = realpath($privateRoot);
$privateTag = bin2hex(random_bytes(4));
$insideRel = "path-test-{$privateTag}.jpg";
$insideFile = $privateRoot . '/' . $insideRel;
file_put_contents($insideFile, 'private');
check('privateFullPath accepts a file inside private storage', Upload::privateFullPath($insideRel) === realpath($insideFile));
$siblingRoot = dirname($privateRoot) . '/' . basename($privateRoot) . '-escape';
if (!is_dir($siblingRoot)) mkdir($siblingRoot, 0775, true);
$siblingFile = $siblingRoot . "/outside-{$privateTag}.jpg";
file_put_contents($siblingFile, 'outside');
$siblingRel = '../' . basename($siblingRoot) . '/' . basename($siblingFile);
check('privateFullPath rejects a sibling directory sharing the private path prefix', Upload::privateFullPath($siblingRel) === null);
@unlink($insideFile);
@unlink($siblingFile);
@rmdir($siblingRoot);

$moveTestDir = Config::basePath() . '/storage/uploads/move-failure-test';
if (!is_dir($moveTestDir)) mkdir($moveTestDir, 0775, true);
$stagedPublicFile = $moveTestDir . '/staged-' . bin2hex(random_bytes(4)) . '.jpg';
$privateDestination = Config::basePath() . '/storage/private/move-failure-test/destination.jpg';
file_put_contents($stagedPublicFile, 'staged image bytes');
$moveMethod = new ReflectionMethod(Upload::class, 'moveFile');
$moveSucceeded = $moveMethod->invoke(null, $stagedPublicFile, $privateDestination, static fn(string $from, string $to): bool => false);
check('failed private move is reported and staged public file is removed', $moveSucceeded === false && !is_file($stagedPublicFile));
@rmdir($moveTestDir);

echo "== Multi-file uploads ==\n";
$multi = ['name' => ['a.jpg', 'b.png'], 'type' => ['', ''], 'tmp_name' => ['/t/a', '/t/b'], 'error' => [0, 4], 'size' => [10, 0]];
$norm = Upload::normalizeMulti($multi);
check('multi-upload field is flattened', count($norm) === 2 && $norm[0]['name'] === 'a.jpg' && $norm[1]['error'] === 4);
check('single-file field is one entry', count(Upload::normalizeMulti(['name' => 'a.jpg', 'type' => '', 'tmp_name' => '/t', 'error' => 0, 'size' => 1])) === 1);
$none = Upload::storePrivateImages(['name' => [''], 'type' => [''], 'tmp_name' => [''], 'error' => [4], 'size' => [0]], 'x', 1000);
check('empty upload field yields nothing and no error', $none['paths'] === [] && $none['errors'] === []);
$four = ['name' => ['1', '2', '3', '4'], 'type' => ['', '', '', ''], 'tmp_name' => ['/t/1', '/t/2', '/t/3', '/t/4'], 'error' => [0, 0, 0, 0], 'size' => [5, 5, 5, 5]];
$res4 = Upload::storePrivateImages($four, 'x', 1000, 3);
check('more than 3 files is reported; fake uploads are rejected', $res4['paths'] === [] && count($res4['errors']) === 4 && str_contains($res4['errors'][0], 'at most 3'));

echo "== Ticket attachments & report evidence ==\n";
$aid = $support->createTicket($aliceId, 'help', 'Account', 'With files', 'normal', 'See the screenshots please.', ['support/a.jpg', 'support/b.jpg']);
$tmsgs = $support->ticketMessages($aid);
check('first message has both attachments', count($tmsgs[0]['attachments']) === 2);
$att = $support->findAttachment((int) $tmsgs[0]['attachments'][0]);
check('attachment resolves to its ticket owner', $att !== null && (int) $att['owner_id'] === $aliceId && (int) $att['ticket_id'] === $aid);
$support->reply($aid, $aliceId, 'More detail.');
check('reply without files has no attachments', $support->ticketMessages($aid)[1]['attachments'] === []);
$rid2 = $support->createReport($bobId, 'listing', $lid, 'spam', 'Spam listing right here.', ['evidence/x.jpg', 'evidence/y.jpg']);
$evs = $support->reportEvidence($rid2);
check('report keeps several evidence files', count($evs) === 2);
$ev1 = $support->findEvidence((int) $evs[0]['id']);
check('evidence belongs to its report', $ev1 !== null && (int) $ev1['report_id'] === $rid2);

echo "== IDOR ownership regression tests ==\n";

// Two independent ticket owners, with one private attachment each.
$ownerTicketA = $support->createTicket($aliceId, 'help', 'Account', 'Alice private ticket', 'normal', 'Alice private details.', ['support/idor-alice.jpg']);
$ownerTicketB = $support->createTicket($bobId, 'help', 'Account', 'Bob private ticket', 'normal', 'Bob private details.', ['support/idor-bob.jpg']);
$aliceTickets = $support->ticketsForUser($aliceId);
$bobTickets = $support->ticketsForUser($bobId);
$aliceAttachment = $support->ticketMessages($ownerTicketA)[0]['attachments'][0] ?? 0;
$bobAttachment = $support->ticketMessages($ownerTicketB)[0]['attachments'][0] ?? 0;
$aliceAttachmentRow = $support->findAttachment((int) $aliceAttachment);
$bobAttachmentRow = $support->findAttachment((int) $bobAttachment);
check('ticket list is scoped to Alice and excludes Bob ticket', count(array_filter($aliceTickets, fn(array $t): bool => (int) $t['id'] === $ownerTicketA)) === 1 && count(array_filter($aliceTickets, fn(array $t): bool => (int) $t['id'] === $ownerTicketB)) === 0);
check('ticket list is scoped to Bob and excludes Alice ticket', count(array_filter($bobTickets, fn(array $t): bool => (int) $t['id'] === $ownerTicketB)) === 1 && count(array_filter($bobTickets, fn(array $t): bool => (int) $t['id'] === $ownerTicketA)) === 0);
check('Alice attachment resolves only to Alice ticket owner', $aliceAttachmentRow !== null && (int) $aliceAttachmentRow['owner_id'] === $aliceId && (int) $aliceAttachmentRow['ticket_id'] === $ownerTicketA);
check('Bob attachment resolves only to Bob ticket owner', $bobAttachmentRow !== null && (int) $bobAttachmentRow['owner_id'] === $bobId && (int) $bobAttachmentRow['ticket_id'] === $ownerTicketB);
check('attachment identifiers are distinct across ticket owners', (int) $aliceAttachment !== (int) $bobAttachment);

// A message target is reportable only by a participant who did not send it.
$reportMessageId = $chat->send($convId, $bobId, 'Message for report authorization test.');
$reportMessage = $chat->findMessage($reportMessageId);
check('message report target resolves to its exact conversation and sender', $reportMessage !== null && (int) $reportMessage['conversation_id'] === $convId && (int) $reportMessage['sender_id'] === $bobId);
check('message reporter participant other than sender is eligible', $reportMessage !== null && $chat->isParticipant((int) $reportMessage['conversation_id'], $aliceId) && (int) $reportMessage['sender_id'] !== $aliceId);
check('message sender cannot report own message', $reportMessage !== null && $chat->isParticipant((int) $reportMessage['conversation_id'], $bobId) && (int) $reportMessage['sender_id'] === $bobId);
check('non-participant cannot report another conversation message', $reportMessage !== null && !$chat->isParticipant((int) $reportMessage['conversation_id'], $charlieId));

// Evidence IDs must remain bound to their parent report; swapping report IDs is rejected.
$aliceReportWithEvidence = $support->createReport($aliceId, 'message', $reportMessageId, 'harassment', 'Please review this message.', ['evidence/idor-alice.jpg']);
$bobReportWithEvidence = $support->createReport($bobId, 'listing', $lid, 'spam', 'Please review this listing.', ['evidence/idor-bob.jpg']);
$aliceEvidence = $support->reportEvidence($aliceReportWithEvidence);
$bobEvidence = $support->reportEvidence($bobReportWithEvidence);
$aliceEvidenceRow = $support->findEvidence((int) ($aliceEvidence[0]['id'] ?? 0));
$bobEvidenceRow = $support->findEvidence((int) ($bobEvidence[0]['id'] ?? 0));
check('Alice report evidence belongs to Alice report', $aliceEvidenceRow !== null && (int) $aliceEvidenceRow['report_id'] === $aliceReportWithEvidence);
check('Bob report evidence belongs to Bob report', $bobEvidenceRow !== null && (int) $bobEvidenceRow['report_id'] === $bobReportWithEvidence);
check('Alice evidence cannot be paired with Bob report ID', $aliceEvidenceRow !== null && (int) $aliceEvidenceRow['report_id'] !== $bobReportWithEvidence);
check('Bob evidence cannot be paired with Alice report ID', $bobEvidenceRow !== null && (int) $bobEvidenceRow['report_id'] !== $aliceReportWithEvidence);

// Static policy guards tie the fixtures to the controller authorization conditions.
$supportControllerSource = file_get_contents(Config::basePath() . '/app/Controllers/SupportController.php');
$adminControllerSource = file_get_contents(Config::basePath() . '/app/Controllers/AdminController.php');
$showTicketStart = strpos($supportControllerSource, 'public function showTicket(');
$replyTicketStart = strpos($supportControllerSource, 'public function replyTicket(');
$attachmentStart = strpos($supportControllerSource, 'public function attachment(');
$reportableStart = strpos($supportControllerSource, 'private function assertReportable(');
$showTicketSource = substr($supportControllerSource, $showTicketStart, $replyTicketStart - $showTicketStart);
$replyTicketSource = substr($supportControllerSource, $replyTicketStart, $attachmentStart - $replyTicketStart);
$attachmentSource = substr($supportControllerSource, $attachmentStart);
$reportableSource = substr($supportControllerSource, $reportableStart);
$adminEvidenceStart = strpos($adminControllerSource, 'public function evidence(');
$adminEvidenceEnd = strpos($adminControllerSource, 'public function decideReport(', $adminEvidenceStart);
$adminEvidenceSource = substr($adminControllerSource, $adminEvidenceStart, $adminEvidenceEnd - $adminEvidenceStart);
check('ticket view allows owner and admin but rejects other users', str_contains($showTicketSource, "(int) \$ticket['user_id'] !== Auth::id()") && str_contains($showTicketSource, '!Auth::isAdmin()') && str_contains($showTicketSource, 'abort(403'));
check('ticket reply allows owner and admin but rejects other users', str_contains($replyTicketSource, "(int) \$ticket['user_id'] !== Auth::id()") && str_contains($replyTicketSource, '!Auth::isAdmin()') && str_contains($replyTicketSource, 'abort(403'));
check('attachment access is owner-or-admin and denies other users', str_contains($attachmentSource, "(int) \$att['owner_id'] !== (int) Auth::id()") && str_contains($attachmentSource, '!Auth::isAdmin()') && str_contains($attachmentSource, 'abort(403'));
check('conversation report requires participation', str_contains($reportableSource, 'isParticipant($id, $me)'));
check('message report requires participation and excludes sender', str_contains($reportableSource, "isParticipant((int) \$msg['conversation_id'], \$me)") && str_contains($reportableSource, "(int) \$msg['sender_id'] !== \$me"));
check('admin evidence endpoint rejects mismatched parent report ID', str_contains($adminEvidenceSource, "(int) \$ev['report_id'] !== \$reportId") && str_contains($adminEvidenceSource, 'abort(404'));
check('admin evidence endpoint requires admin role', str_contains($adminEvidenceSource, 'Auth::requireAdmin()'));

echo "== Retention policy ==\n";
$priv = Config::basePath() . '/' . trim(Config::get('PRIVATE_DIR', 'storage/private'), '/');
foreach (['cards', 'support', 'evidence'] as $d) { if (!is_dir($priv . '/' . $d)) mkdir($priv . '/' . $d, 0775, true); }
$tag = bin2hex(random_bytes(4));
$mk = function (string $rel) use ($priv): string { file_put_contents($priv . '/' . $rel, 'x'); return $rel; };
$oldCard = $mk("cards/t-old-{$tag}.jpg"); $newCard = $mk("cards/t-new-{$tag}.jpg"); $pendCard = $mk("cards/t-pend-{$tag}.jpg");
$long = gmdate('Y-m-d H:i:s', time() - 60 * 86400);
$ins = $pdo->prepare("INSERT INTO student_verifications (user_id, full_name, student_number_hash, student_number_last4, university, card_image_path, status, reviewed_at, created_at) VALUES (?, 'T', ?, '0000', 'U', ?, ?, ?, ?)");
$ins->execute([$charlieId, 'h1' . $tag, $oldCard, 'verified', $long, $long]);
$oldVid = (int) $pdo->lastInsertId();
$ins->execute([$charlieId, 'h2' . $tag, $newCard, 'verified', gmdate('Y-m-d H:i:s'), gmdate('Y-m-d H:i:s')]);
$newVid = (int) $pdo->lastInsertId();
$ins->execute([$charlieId, 'h3' . $tag, $pendCard, 'pending', null, $long]);
$oldAttFile = $mk("support/t-old-{$tag}.jpg"); $openAttFile = $mk("support/t-open-{$tag}.jpg");
$tOld = $support->createTicket($aliceId, 'help', 'Account', 'Old closed ticket', 'normal', 'Old ticket body text.', [$oldAttFile]);
$tOpen = $support->createTicket($aliceId, 'help', 'Account', 'Still open ticket', 'normal', 'Open ticket body text.', [$openAttFile]);
$support->setTicketStatus($tOld, 'closed');
$pdo->prepare('UPDATE support_tickets SET updated_at = ? WHERE id = ?')->execute([gmdate('Y-m-d H:i:s', time() - 400 * 86400), $tOld]);
$oldEvFile = $mk("evidence/t-old-{$tag}.jpg");
$rOld = $support->createReport($bobId, 'listing', $lid, 'spam', 'Old resolved report text.', [$oldEvFile]);
$support->setReportStatus($rOld, 'resolved', 'done');
$pdo->prepare('UPDATE reports SET updated_at = ? WHERE id = ?')->execute([gmdate('Y-m-d H:i:s', time() - 400 * 86400), $rOld]);
$retention = new RetentionService($verif, $support, $sess);
$dry = $retention->run(time(), 30, 180, true);
check('dry run reports work but deletes nothing', $dry['cards'] === 1 && $dry['attachments'] === 1 && $dry['evidence'] === 1 && is_file($priv . '/' . $oldCard));
$done = $retention->run(time(), 30, 180, false);
check('old reviewed Student Card is deleted', !is_file($priv . '/' . $oldCard) && $done['cards'] === 1);
check('purged card row is marked and path cleared', $pdo->query("SELECT card_purged_at FROM student_verifications WHERE id = {$oldVid}")->fetchColumn() !== null && $pdo->query("SELECT card_image_path FROM student_verifications WHERE id = {$oldVid}")->fetchColumn() === '');
check('recently reviewed card is kept', is_file($priv . '/' . $newCard) && $pdo->query("SELECT card_purged_at FROM student_verifications WHERE id = {$newVid}")->fetchColumn() === null);
check('PENDING card is never purged, however old', is_file($priv . '/' . $pendCard));
check('closed old ticket attachment deleted (file and row)', !is_file($priv . '/' . $oldAttFile) && $done['attachments'] === 1);
check('open ticket attachment kept', is_file($priv . '/' . $openAttFile));
check('old resolved report evidence deleted (file and row)', !is_file($priv . '/' . $oldEvFile) && $done['evidence'] === 1 && $support->reportEvidence($rOld) === []);
check('second run is a no-op (idempotent)', $retention->run(time(), 30, 180, false)['cards'] === 0);
foreach ([$newCard, $pendCard, $openAttFile] as $leftover) { @unlink($priv . '/' . $leftover); }

echo "== Middleware & route policy ==\n";
$order = new ArrayObject();
$mkMw = fn(string $name, bool $fail = false): Middleware => new class($order, $name, $fail) implements Middleware {
    public function __construct(private ArrayObject $log, private string $name, private bool $fail) {}
    public function handle(): void { $this->log[] = $this->name; if ($this->fail) throw new RuntimeException('blocked'); }
};
$r1 = new Router();
$r1->get('/x', function () use ($order) { $order[] = 'handler'; }, [$mkMw('mw1'), $mkMw('mw2')]);
$r1->dispatch('GET', '/x');
check('middleware runs in order, then the handler', $order->getArrayCopy() === ['mw1', 'mw2', 'handler']);
$order->exchangeArray([]);
$r2 = new Router();
$r2->get('/y', function () use ($order) { $order[] = 'handler'; }, [$mkMw('deny', true)]);
$blocked = false;
try { $r2->dispatch('GET', '/y'); } catch (RuntimeException) { $blocked = true; }
check('a failing middleware stops the handler from running', $blocked && $order->getArrayCopy() === ['deny']);
$table = $router->routeTable();
$adminRoutes = array_filter($table, fn(array $r): bool => str_starts_with($r['pattern'], '/admin'));
check('admin routes are registered', count($adminRoutes) >= 18);
check('EVERY /admin route has RequireAdmin', count(array_filter($adminRoutes, fn(array $r): bool => !in_array(RequireAdmin::class, $r['middleware'], true))) === 0);
check('EVERY POST route has RequireCsrf', count(array_filter($table, fn(array $r): bool => $r['method'] === 'POST' && !in_array(RequireCsrf::class, $r['middleware'], true))) === 0);
$find = function (string $method, string $pattern) use ($table): ?array {
    foreach ($table as $r) { if ($r['method'] === $method && $r['pattern'] === $pattern) return $r; }
    return null;
};
foreach ([['POST', '/admin/users/{id}/suspend'], ['POST', '/admin/users/{id}/restore'], ['POST', '/admin/listings/{id}/moderate'], ['POST', '/admin/verifications/{id}/decide'], ['GET', '/admin/verifications/{id}/card']] as [$m, $pat]) {
    $row = $find($m, $pat);
    check("sensitive admin action {$pat} requires recent password confirmation", $row !== null && in_array(RequireRecentAuth::class, $row['middleware'], true));
}
$verifyGet = $find('GET', '/verify-email');
$verifyPost = $find('POST', '/verify-email');
check('email verification GET only displays confirmation', $verifyGet !== null && $find('POST', '/verify-email') !== null);
check('email verification POST requires CSRF', $verifyPost !== null && in_array(RequireCsrf::class, $verifyPost['middleware'], true));
$authControllerSource = file_get_contents(Config::basePath() . '/app/Controllers/AuthController.php');
$showVerifySource = substr($authControllerSource, strpos($authControllerSource, 'public function showVerifyEmail('), strpos($authControllerSource, 'public function verifyEmail(') - strpos($authControllerSource, 'public function showVerifyEmail('));
$verifyPostSource = substr($authControllerSource, strpos($authControllerSource, 'public function verifyEmail('), strpos($authControllerSource, 'public function resendVerification(') - strpos($authControllerSource, 'public function verifyEmail('));
check('GET verification handler does not consume token', !str_contains($showVerifySource, 'consumeEmailVerification('));
check('POST verification handler validates CSRF before consuming token', strpos($verifyPostSource, 'requireCsrf()') < strpos($verifyPostSource, 'consumeEmailVerification('));
check('notification open is POST, not GET', $find('POST', '/notifications/{id}/open') !== null && $find('GET', '/notifications/{id}/open') === null);
check('notification open POST requires CSRF', $find('POST', '/notifications/{id}/open') !== null && in_array(RequireCsrf::class, $find('POST', '/notifications/{id}/open')['middleware'], true));
check('chat status update is POST, not GET', $find('POST', '/messages/{id}/status') !== null && $find('GET', '/messages/{id}/status') === null);
check('chat status update POST requires CSRF', $find('POST', '/messages/{id}/status') !== null && in_array(RequireCsrf::class, $find('POST', '/messages/{id}/status')['middleware'], true));
$chatControllerSource = file_get_contents(Config::basePath() . '/app/Controllers/ChatController.php');
$threadSource = substr($chatControllerSource, strpos($chatControllerSource, 'public function thread('), strpos($chatControllerSource, 'public function send(') - strpos($chatControllerSource, 'public function thread('));
$pollSource = substr($chatControllerSource, strpos($chatControllerSource, 'public function poll('), strpos($chatControllerSource, 'public function updateStatus(') - strpos($chatControllerSource, 'public function poll('));
check('chat thread GET does not mark messages read', !str_contains($threadSource, 'markRead('));
check('chat poll GET does not update delivery or read status', !str_contains($pollSource, 'markDelivered(') && !str_contains($pollSource, 'markRead('));
foreach ([['GET', '/profile'], ['GET', '/sell'], ['GET', '/messages/{id}'], ['GET', '/support/attachments/{id}'], ['POST', '/users/{id}/block'], ['POST', '/profile/sessions/{id}/revoke']] as [$m, $pat]) {
    $row = $find($m, $pat);
    check("{$m} {$pat} requires sign-in", $row !== null && in_array(RequireAuth::class, $row['middleware'], true));
}

echo "== CSRF & XSS ==\n";
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
$csrfToken = Csrf::token();
check('CSRF token generated', strlen($csrfToken) === 64);
check('CSRF validates correct token', Csrf::validate($csrfToken) === true);
check('CSRF rejects wrong token', Csrf::validate('wrong-token') === false);
check('CSRF rejects empty token', Csrf::validate('') === false);
$xss = '<script>alert(1)</script>';
check('XSS payload escaped', e($xss) === '&lt;script&gt;alert(1)&lt;/script&gt;');
check('quotes escaped', e('"\'') === '&quot;&#039;');

echo "== Upload validation ==\n";
$tmp = tempnam(sys_get_temp_dir(), 'upl');
file_put_contents($tmp, '<?php echo "pwned"; ?>');
$finfo = new finfo(FILEINFO_MIME_TYPE);
$mime = $finfo->file($tmp);
check('PHP file detected as non-image', !in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true));
unlink($tmp);

echo "\nResults: {$pass} passed, {$fail} failed\n";
exit($fail > 0 ? 1 : 0);
