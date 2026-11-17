<?php
declare(strict_types=1);
namespace SdutSwap\Repositories;

use SdutSwap\Core\Database;
use PDO;

final class BlockRepository
{
    private PDO $db;
    public function __construct(?PDO $db = null) { $this->db = $db ?? Database::pdo(); }

    /** Idempotent: blocking twice keeps a single row. Returns true if a new block was created. */
    public function block(int $blockerId, int $blockedId): bool
    {
        if ($this->hasBlocked($blockerId, $blockedId)) return false;
        $this->db->prepare('INSERT INTO user_blocks (blocker_id, blocked_id) VALUES (?, ?)')->execute([$blockerId, $blockedId]);
        return true;
    }

    public function unblock(int $blockerId, int $blockedId): bool
    {
        $s = $this->db->prepare('DELETE FROM user_blocks WHERE blocker_id = ? AND blocked_id = ?');
        $s->execute([$blockerId, $blockedId]);
        return $s->rowCount() > 0;
    }

    /** Did $blockerId block $blockedId? (one direction) */
    public function hasBlocked(int $blockerId, int $blockedId): bool
    {
        $s = $this->db->prepare('SELECT 1 FROM user_blocks WHERE blocker_id = ? AND blocked_id = ? LIMIT 1');
        $s->execute([$blockerId, $blockedId]);
        return (bool) $s->fetch();
    }

    /** Is there a block between the two users in EITHER direction? */
    public function isBlockedEitherWay(int $a, int $b): bool
    {
        $s = $this->db->prepare('SELECT 1 FROM user_blocks WHERE (blocker_id = ? AND blocked_id = ?) OR (blocker_id = ? AND blocked_id = ?) LIMIT 1');
        $s->execute([$a, $b, $b, $a]);
        return (bool) $s->fetch();
    }

    /** Users that $blockerId has blocked, for the "Blocked users" list. */
    public function listBlockedBy(int $blockerId): array
    {
        $s = $this->db->prepare('SELECT u.id, u.name, b.created_at FROM user_blocks b JOIN users u ON u.id = b.blocked_id WHERE b.blocker_id = ? ORDER BY b.created_at DESC');
        $s->execute([$blockerId]);
        return $s->fetchAll();
    }
}
