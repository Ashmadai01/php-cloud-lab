<?php
declare(strict_types=1);

namespace SdutSwap\Repositories;

use SdutSwap\Core\Database;
use PDO;

final class NotificationRepository
{
    private PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Database::pdo();
    }

    public function create(
        int $userId,
        ?int $ticketId,
        string $type,
        string $message
    ): int {
        $s = $this->db->prepare(
            'INSERT INTO notifications (user_id, ticket_id, type, message)
             VALUES (?, ?, ?, ?)'
        );

        $s->execute([$userId, $ticketId, $type, $message]);

        return (int) $this->db->lastInsertId();
    }

    public function createForAdmins(
        ?int $ticketId,
        string $type,
        string $message
    ): void {
        $s = $this->db->prepare(
            "INSERT INTO notifications (user_id, ticket_id, type, message)
             SELECT id, ?, ?, ?
             FROM users
             WHERE role = 'admin'"
        );

        $s->execute([$ticketId, $type, $message]);
    }

    public function forUser(int $userId, int $limit = 20): array
    {
        $limit = max(1, min(100, $limit));

        $s = $this->db->prepare(
            'SELECT id, ticket_id, type, message, read_at, created_at
             FROM notifications
             WHERE user_id = ?
             ORDER BY created_at DESC, id DESC
             LIMIT ?'
        );

        $s->bindValue(1, $userId, PDO::PARAM_INT);
        $s->bindValue(2, $limit, PDO::PARAM_INT);
        $s->execute();

        return $s->fetchAll();
    }

    public function unreadCount(int $userId): int
    {
        $s = $this->db->prepare(
            'SELECT COUNT(*) FROM notifications
             WHERE user_id = ? AND read_at IS NULL'
        );

        $s->execute([$userId]);

        return (int) $s->fetchColumn();
    }

    public function markAsRead(int $notificationId, int $userId): void
    {
        $s = $this->db->prepare(
            'UPDATE notifications
             SET read_at = CURRENT_TIMESTAMP
             WHERE id = ? AND user_id = ? AND read_at IS NULL'
        );

        $s->execute([$notificationId, $userId]);
    }


    public function markAsReadAndGetTicketId(int $notificationId, int $userId): ?int
    {
        $s = $this->db->prepare(
            'SELECT ticket_id FROM notifications
             WHERE id = ? AND user_id = ?
             LIMIT 1'
        );
        $s->execute([$notificationId, $userId]);

        $ticketId = $s->fetchColumn();

        if ($ticketId === false) {
            return null;
        }

        $this->markAsRead($notificationId, $userId);

        return $ticketId === null ? null : (int) $ticketId;
    }

    public function markAllAsRead(int $userId): void
    {
        $s = $this->db->prepare(
            'UPDATE notifications
             SET read_at = CURRENT_TIMESTAMP
             WHERE user_id = ? AND read_at IS NULL'
        );

        $s->execute([$userId]);
    }
}
