<?php
declare(strict_types=1);
namespace SdutSwap\Controllers;

use SdutSwap\Core\Audit;
use SdutSwap\Core\Auth;
use SdutSwap\Core\Config;
use SdutSwap\Core\Upload;
use SdutSwap\Core\Validator;
use SdutSwap\Repositories\BlockRepository;
use SdutSwap\Repositories\ListingRepository;
use SdutSwap\Repositories\UserRepository;
use SdutSwap\Repositories\VerificationRepository;
use SdutSwap\Services\SessionService;

final class ProfileController extends Controller
{
    /** Renders the profile page with everything it shows (details, verification, devices, blocked users). */
    private function renderProfile(array $extra = [], int $status = 200): void
    {
        $uid = (int) Auth::id();
        $this->view('profile/show', array_merge([
            'title' => 'My Profile',
            'profile' => Auth::user(),
            'verification' => (new VerificationRepository())->latestForUser($uid),
            'sessions' => SessionService::listForUser($uid),
            'blockedUsers' => (new BlockRepository())->listBlockedBy($uid),
        ], $extra), $status);
    }

    public function show(): void
    {
        Auth::requireLogin();
        $this->renderProfile();
    }

    public function publicProfile(int $id): void
    {
        $profile = (new UserRepository())->findPublicProfile($id);

        if (!$profile) {
            abort(404, 'Profile not found');
        }

        $listingRepository = new ListingRepository();
        $stats = $listingRepository->publicStatsForSeller($id);
        $listings = $listingRepository->publicActiveBySeller($id, 6);

        $transactionRepository = new \SdutSwap\Repositories\TransactionRepository();
        $sellerRating = $transactionRepository->sellerRating($id);
        $sellerReviews = $transactionRepository->reviewsForSeller($id, 10);

        $this->view('profile/public', [
            'title' => $profile['name'] . ' - Profile',
            'profile' => $profile,
            'stats' => $stats,
            'listings' => $listings,
            'sellerRating' => $sellerRating,
            'sellerReviews' => $sellerReviews,
        ]);
    }

    public function update(): void
    {
        Auth::requireLogin();
        $this->requireCsrf();
        $v = new Validator();
        $name = $this->input('name');
        $university = $this->input('university');
        $bio = $this->input('bio');
        $v->required('name', $name, 2, 100);
        if (mb_strlen($university) > 150) $v->add('university', 'University must be at most 150 characters.');
        if (mb_strlen($bio) > 1000) $v->add('bio', 'Bio must be at most 1000 characters.');
        if ($v->fails()) {
            $this->renderProfile(['errors' => $v->errors()], 422);
        }
        $repo = new UserRepository();
        $repo->updateProfile(Auth::id(), $name, $university ?: null, $bio ?: null);
        if (!empty($_FILES['avatar']) && ($_FILES['avatar']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            $res = Upload::storeImage($_FILES['avatar'], 'avatars', Config::int('UPLOAD_MAX_SIZE', 5242880));
            if ($res['ok']) {
                $old = Auth::user()['avatar_path'] ?? null;
                $repo->updateAvatar(Auth::id(), (string) $res['path']);
                if ($old) Upload::deletePublic((string) $old);
            } else {
                flash('error', $res['error']);
            }
        }
        Auth::reset();
        flash('success', 'Profile updated.');
        redirect('/profile');
    }

    public function changePassword(): void
    {
        Auth::requireLogin();
        $this->requireCsrf();
        $this->rateLimit('password_change', 5, 3600, 3600);
        $current = (string) ($_POST['current_password'] ?? '');
        $new = (string) ($_POST['new_password'] ?? '');
        $confirm = (string) ($_POST['new_password_confirm'] ?? '');
        $user = Auth::user();
        $v = new Validator();
        $v->password('new_password', $new);
        if ($new !== $confirm) $v->add('new_password_confirm', 'Passwords do not match.');
        if (!password_verify($current, (string) $user['password_hash'])) $v->add('current_password', 'Current password is incorrect.');
        if ($v->fails()) {
            $this->renderProfile(['errors' => $v->errors()], 422);
        }
        $hash = password_hash($new, PASSWORD_ARGON2ID, ['memory_cost' => 65536, 'time_cost' => 4, 'threads' => 2]);
        if ($hash === false) $hash = password_hash($new, PASSWORD_BCRYPT, ['cost' => 12]);
        if ($hash === false || !(new UserRepository())->updatePassword((int) Auth::id(), $hash)) {
            $this->renderProfile(['errors' => ['new_password' => ['The password could not be updated. Please try again.']]], 500);
        }
        if (!\SdutSwap\Core\Session::regenerate(true)) {
            SessionService::revokeAllForUser((int) Auth::id());
            Auth::logout();
            flash('error', 'Your password was changed, but your session could not be secured. Please sign in again.');
            redirect('/login');
        }
        $closed = SessionService::revokeOthers((int) Auth::id());
        if ($closed === null) {
            SessionService::revokeAllForUser((int) Auth::id());
            Auth::logout();
            flash('error', 'Your password was changed, but device sessions could not be verified. Please sign in again.');
            redirect('/login');
        }
        Audit::security('password_changed', 'medium', "Password changed; {$closed} other session(s) revoked");
        flash('success', $closed > 0 ? "Password changed. {$closed} other device(s) were signed out." : 'Password changed.');
        redirect('/profile');
    }

    public function revokeSession(int $sessionId): void
    {
        Auth::requireLogin();
        $this->requireCsrf();
        $this->rateLimit('session_revoke', 30, 3600, 3600);
        // Only the user's own sessions can be revoked (ownership enforced in the query).
        $ok = (new \SdutSwap\Repositories\SessionRepository())->revokeOwned($sessionId, (int) Auth::id());
        if ($ok) {
            Audit::security('session_revoked', 'low', "Session {$sessionId} revoked by its owner");
            flash('success', 'Device signed out.');
        } else {
            flash('error', 'That session could not be found.');
        }
        redirect('/profile');
    }

    public function revokeOtherSessions(): void
    {
        Auth::requireLogin();
        $this->requireCsrf();
        $this->rateLimit('session_revoke', 30, 3600, 3600);
        $closed = SessionService::revokeOthers((int) Auth::id());
        if ($closed === null) {
            flash('error', 'Device sessions could not be updated. Please try again.');
            redirect('/profile');
        }
        Audit::security('sessions_revoked_others', 'low', "{$closed} other session(s) revoked by their owner");
        flash('success', $closed > 0 ? "Signed out of {$closed} other device(s)." : 'There were no other devices signed in.');
        redirect('/profile');
    }
}
