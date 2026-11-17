<?php
declare(strict_types=1);
namespace SdutSwap\Repositories;

use SdutSwap\Core\Database;
use PDO;

final class ChatRepository
{
    private PDO $db;
    public function __construct(?PDO $db = null) { $this->db = $db ?? Database::pdo(); }

    public function findOrCreateConversation(int $listingId, int $buyerId, int $sellerId): int
    {
        // Find existing conversation for this listing + buyer.
        $s = $this->db->prepare('SELECT c.id FROM conversations c JOIN conversation_participants cp ON cp.conversation_id = c.id WHERE c.listing_id = ? AND cp.user_id = ? LIMIT 1');
        $s->execute([$listingId, $buyerId]);
        $row = $s->fetch();
        if ($row) return (int) $row['id'];
        $this->db->beginTransaction();
        try {
            $this->db->prepare('INSERT INTO conversations (listing_id, created_by) VALUES (?, ?)')->execute([$listingId, $buyerId]);
            $convId = (int) $this->db->lastInsertId();
            $p = $this->db->prepare('INSERT INTO conversation_participants (conversation_id, user_id) VALUES (?, ?)');
            $p->execute([$convId, $buyerId]);
            $p->execute([$convId, $sellerId]);
            $this->db->commit();
            return $convId;
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function isParticipant(int $convId, int $userId): bool
    {
        $s = $this->db->prepare('SELECT 1 FROM conversation_participants WHERE conversation_id = ? AND user_id = ? LIMIT 1');
        $s->execute([$convId, $userId]);
        return (bool) $s->fetch();
    }

    /** The other participant of a two-person conversation (null if none). */
    public function otherParticipantId(int $convId, int $userId): ?int
    {
        $s = $this->db->prepare('SELECT user_id FROM conversation_participants WHERE conversation_id = ? AND user_id <> ? LIMIT 1');
        $s->execute([$convId, $userId]);
        $id = $s->fetchColumn();
        return $id === false ? null : (int) $id;
    }

    public function findMessage(int $messageId): ?array
    {
        $s = $this->db->prepare('SELECT id, conversation_id, sender_id FROM messages WHERE id = ? LIMIT 1');
        $s->execute([$messageId]);
        $r = $s->fetch();
        return $r ?: null;
    }

    public function participants(int $convId): array
    {
        $s = $this->db->prepare('SELECT u.id, u.name, u.is_verified FROM conversation_participants cp JOIN users u ON u.id = cp.user_id WHERE cp.conversation_id = ?');
        $s->execute([$convId]);
        return $s->fetchAll();
    }

    public function conversation(int $convId): ?array
    {
        $s = $this->db->prepare('SELECT c.*, l.title AS listing_title, l.price AS listing_price, l.status AS listing_status FROM conversations c LEFT JOIN listings l ON l.id = c.listing_id WHERE c.id = ? LIMIT 1');
        $s->execute([$convId]);
        $r = $s->fetch();
        return $r ?: null;
    }

    public function inbox(int $userId): array
    {
        $s = $this->db->prepare('SELECT c.id, c.listing_id, c.updated_at, l.title AS listing_title, l.price AS listing_price,
            (SELECT body FROM messages m WHERE m.conversation_id = c.id ORDER BY m.created_at DESC LIMIT 1) AS last_message,
            (SELECT created_at FROM messages m WHERE m.conversation_id = c.id ORDER BY m.created_at DESC LIMIT 1) AS last_at,
            (SELECT COUNT(*) FROM messages m WHERE m.conversation_id = c.id AND m.sender_id != ? AND m.created_at > COALESCE(cp.last_read_at, \'1970-01-01\')) AS unread
            FROM conversations c
            JOIN conversation_participants cp ON cp.conversation_id = c.id AND cp.user_id = ?
            LEFT JOIN listings l ON l.id = c.listing_id
            ORDER BY c.updated_at DESC');
        $s->execute([$userId, $userId]);
        return $s->fetchAll();
    }

    public function unreadCount(int $userId): int
    {
        $s = $this->db->prepare('SELECT COUNT(*) FROM messages m JOIN conversation_participants cp ON cp.conversation_id = m.conversation_id AND cp.user_id = ? WHERE m.sender_id != ? AND m.created_at > COALESCE(cp.last_read_at, \'1970-01-01\')');
        $s->execute([$userId, $userId]);
        return (int) $s->fetchColumn();
    }

    public function messages(int $convId, ?int $afterId = null, int $limit = 100): array
    {
        if ($afterId !== null) {
            $s = $this->db->prepare('SELECT m.*, u.name AS sender_name FROM messages m JOIN users u ON u.id = m.sender_id WHERE m.conversation_id = ? AND m.id > ? ORDER BY m.id ASC LIMIT ?');
            $s->bindValue(1, $convId, PDO::PARAM_INT);
            $s->bindValue(2, $afterId, PDO::PARAM_INT);
            $s->bindValue(3, $limit, PDO::PARAM_INT);
            $s->execute();
        } else {
            $s = $this->db->prepare('SELECT m.*, u.name AS sender_name FROM messages m JOIN users u ON u.id = m.sender_id WHERE m.conversation_id = ? ORDER BY m.id ASC LIMIT ?');
            $s->bindValue(1, $convId, PDO::PARAM_INT);
            $s->bindValue(2, $limit, PDO::PARAM_INT);
            $s->execute();
        }
        return $s->fetchAll();
    }

    public function send(int $convId, int $senderId, string $body): int
    {
        $this->db->prepare('INSERT INTO messages (conversation_id, sender_id, body) VALUES (?, ?, ?)')->execute([$convId, $senderId, $body]);
        $id = (int) $this->db->lastInsertId();
        $this->db->prepare('UPDATE conversations SET updated_at = CURRENT_TIMESTAMP WHERE id = ?')->execute([$convId]);
        return $id;
    }

    public function markRead(int $convId, int $userId): void
    {
        $this->db->prepare('UPDATE conversation_participants SET last_read_at = CURRENT_TIMESTAMP WHERE conversation_id = ? AND user_id = ?')->execute([$convId, $userId]);
    }

    public function recentMessageCount(int $userId, int $minutes = 5): int
    {
        $since = gmdate('Y-m-d H:i:s', time() - $minutes * 60);
        $s = $this->db->prepare('SELECT COUNT(*) FROM messages WHERE sender_id = ? AND created_at > ?');
        $s->execute([$userId, $since]);
        return (int) $s->fetchColumn();
    }
}
