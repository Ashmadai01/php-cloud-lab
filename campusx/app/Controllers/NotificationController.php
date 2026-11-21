<?php
declare(strict_types=1);

namespace SdutSwap\Controllers;

use SdutSwap\Core\Auth;
use SdutSwap\Repositories\NotificationRepository;

final class NotificationController extends Controller
{
    public function index(): void
    {
        Auth::requireLogin();

        $repo = new NotificationRepository();

        $this->view('notifications/index', [
            'title' => 'Notifications',
            'notifications' => $repo->forUser((int) Auth::id(), 50),
            'unreadCount' => $repo->unreadCount((int) Auth::id()),
        ]);
    }


    public function open(int $id): void
    {
        Auth::requireLogin();

        $repo = new NotificationRepository();
        $ticketId = $repo->markAsReadAndGetTicketId($id, (int) Auth::id());

        if ($ticketId !== null) {
            redirect('/support/tickets/' . $ticketId);
        }

        redirect('/notifications');
    }

    public function markRead(int $id): void
    {
        Auth::requireLogin();
        $this->requireCsrf();

        $repo = new NotificationRepository();
        $repo->markAsRead($id, (int) Auth::id());

        redirect('/notifications');
    }

    public function markAllRead(): void
    {
        Auth::requireLogin();
        $this->requireCsrf();

        $repo = new NotificationRepository();
        $repo->markAllAsRead((int) Auth::id());

        redirect('/notifications');
    }
}
