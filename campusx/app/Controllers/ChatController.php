<?php
declare(strict_types=1);
namespace SdutSwap\Controllers;

use SdutSwap\Core\Auth;
use SdutSwap\Core\Config;
use SdutSwap\Repositories\BlockRepository;
use SdutSwap\Repositories\ChatRepository;
use SdutSwap\Repositories\ListingRepository;
use SdutSwap\Repositories\UserRepository;
use SdutSwap\Services\BlockService;

final class ChatController extends Controller
{
    public function inbox(): void
    {
        Auth::requireLogin();
        $repo = new ChatRepository();
        $this->view('chat/inbox', ['title' => 'Messages', 'conversations' => $repo->inbox(Auth::id())]);
    }

    public function start(int $listingId): void
    {
        Auth::requireLogin();
        $this->requireCsrf();
        if (Config::bool('REQUIRE_VERIFIED_TO_CHAT') && !isVerifiedUser() && !Auth::isAdmin()) {
            flash('error', 'Please verify your student identity to message sellers.');
            redirect('/verify');
        }
        $listings = new ListingRepository();
        $listing = $listings->findById($listingId);
        if (!$listing || $listing['status'] !== 'active') abort(404, 'Listing not found');
        if ((int) $listing['seller_id'] === Auth::id()) {
            flash('error', 'You cannot message yourself about your own listing.');
            redirect('/listings/' . $listingId);
        }
        if (!(new BlockService(new BlockRepository(), new UserRepository()))->canMessage(Auth::id(), (int) $listing['seller_id'])) {
            flash('error', 'You cannot send messages to this seller.');
            redirect('/listings/' . $listingId);
        }
        $repo = new ChatRepository();
        $convId = $repo->findOrCreateConversation($listingId, Auth::id(), (int) $listing['seller_id']);
        redirect('/messages/' . $convId);
    }

    public function thread(int $id): void
    {
        Auth::requireLogin();
        $repo = new ChatRepository();
        if (!$repo->isParticipant($id, Auth::id())) {
            \SdutSwap\Core\Audit::security('chat_idor_attempt', 'high', "User attempted to access conversation {$id}");
            abort(403, 'Forbidden');
        }
        $repo->markRead($id, Auth::id());
        $conv = $repo->conversation($id);
        $other = $repo->otherParticipantId($id, Auth::id());
        $otherPresence = $other !== null
    ? $repo->presence($other)
    : ['online' => false, 'last_seen' => null];
        $blocks = new BlockRepository();
        $this->view('chat/thread', [
            'title' => 'Conversation',
            'conversation' => $conv,
            'participants' => $repo->participants($id),
            'messages' => $repo->messages($id),
            'otherUserId' => $other,
            'otherPresence' => $otherPresence,
            'iBlocked' => $other !== null && $blocks->hasBlocked(Auth::id(), $other),
            'messagingBlocked' => $other !== null && $blocks->isBlockedEitherWay(Auth::id(), $other),
        ]);
    }

    public function send(int $id): void
    {
        Auth::requireLogin();
        $this->requireCsrf();
        $repo = new ChatRepository();
        if (!$repo->isParticipant($id, Auth::id())) abort(403, 'Forbidden');
        $other = $repo->otherParticipantId($id, Auth::id());
        if ($other !== null && !(new BlockService(new BlockRepository(), new UserRepository()))->canMessage(Auth::id(), $other)) {
            flash('error', 'Messaging is turned off in this conversation.');
            redirect('/messages/' . $id);
        }
        $this->rateLimit('chat_send', 30, 60, 300);
        if ($repo->recentMessageCount(Auth::id(), 5) > 50) {
            abort(429, 'You are sending messages too quickly. Please slow down.');
        }
        $body = $this->input('body');
        if (mb_strlen($body) < 1 || mb_strlen($body) > 2000) {
            flash('error', 'Message must be between 1 and 2000 characters.');
            redirect('/messages/' . $id);
        }
        // Plain text only; output-encoded in views. Block obvious contact-detail spam patterns minimally.
        $repo->send($id, Auth::id(), $body);
        redirect('/messages/' . $id);
    }


    public function poll(int $id): void
{
    Auth::requireLogin();

    $repo = new ChatRepository();
    $userId = Auth::id();

    if (!$repo->isParticipant($id, $userId)) {
        $this->json(['error' => 'Forbidden'], 403);
        return;
    }

    // Mark messages in this conversation as read by the current user.
    $repo->markRead($id, $userId);

    $after = (int) $this->input('after', '0');
    $msgs = $repo->messages($id, $after > 0 ? $after : null, 50);

    $out = array_map(fn($m) => [
        'id' => (int) $m['id'],
        'sender_id' => (int) $m['sender_id'],
        'sender_name' => $m['sender_name'],
        'body' => $m['body'],
        'created_at' => $m['created_at'],
        'is_read' => (bool) $m['is_read'],
    ], $msgs);

    $other = $repo->otherParticipantId($id, $userId);
    $typing = $other !== null
    ? $repo->isTyping($id, $other)
    : false;

$this->json([
    'messages' => $out,
    'statuses' => $repo->readStatuses($id, $userId),
    'presence' => $other !== null
        ? $repo->presence($other)
        : ['online' => false, 'last_seen' => null],
    'typing' => $typing,
]);

}
    public function typing(int $id): void
{
    Auth::requireLogin();

    $repo = new ChatRepository();
    $userId = Auth::id();

    if (!$repo->isParticipant($id, $userId)) {
        $this->json(['error' => 'Forbidden'], 403);
        return;
    }

    $repo->updateTyping($id, $userId);

    $this->json(['success' => true]);
}
    public function unreadStatus(): void
{
    Auth::requireLogin();

    $this->json(
        (new ChatRepository())->notificationStatus(Auth::id())
    );
}
}
