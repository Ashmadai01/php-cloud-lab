<?php
declare(strict_types=1);
namespace SdutSwap\Controllers;

use SdutSwap\Core\Audit;
use SdutSwap\Core\Auth;
use SdutSwap\Repositories\BlockRepository;
use SdutSwap\Repositories\UserRepository;
use SdutSwap\Services\BlockService;

final class BlockController extends Controller
{
    private function service(): BlockService
    {
        return new BlockService(new BlockRepository(), new UserRepository());
    }

    /** Only the conversation page and the profile page may be used as the redirect target. */
    private function safeReturn(): string
    {
        $r = $this->input('return');
        return preg_match('#^/(messages/[0-9]+|profile)$#D', $r) === 1 ? $r : '/profile';
    }

    public function block(int $targetId): void
    {
        Auth::requireLogin();
        $this->requireCsrf();
        $this->rateLimit('user_block', 20, 3600, 3600);
        $result = $this->service()->block((int) Auth::id(), $targetId);
        if ($result === BlockService::NOT_FOUND) abort(404, 'User not found');
        if ($result === BlockService::SELF) {
            flash('error', 'You cannot block yourself.');
        } elseif ($result === BlockService::ADMIN) {
            flash('error', 'Support staff cannot be blocked. Use Report if you have a concern.');
        } else {
            Audit::security('user_blocked', 'low', 'User ' . Auth::id() . " blocked user {$targetId}");
            flash('success', 'User blocked. You and this user can no longer message each other.');
        }
        redirect($this->safeReturn());
    }

    public function unblock(int $targetId): void
    {
        Auth::requireLogin();
        $this->requireCsrf();
        $this->rateLimit('user_block', 20, 3600, 3600);
        if ($this->service()->unblock((int) Auth::id(), $targetId)) {
            Audit::security('user_unblocked', 'low', 'User ' . Auth::id() . " unblocked user {$targetId}");
            flash('success', 'User unblocked.');
        }
        redirect($this->safeReturn());
    }
}
