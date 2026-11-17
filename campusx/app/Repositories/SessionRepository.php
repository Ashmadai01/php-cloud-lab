<?php
declare(strict_types=1);
namespace SdutSwap\Repositories;

use SdutSwap\Core\Database;
use PDO;

/** Tracks signed-in browser sessions so users can see and revoke their own devices. */
final class SessionRepository
{
    private PDO $db;
    public function __construct(?PDO $db = null) { $this->db = $db ?? Database::pdo(); }

    public function create(int $userId, string $sidHash, ?string $ipHash, ?string $uaHash, ?string $deviceLabel, string $expiresAt): int
    {
        $s = $this->db->prepare('INSERT INTO user_sessions (user_id, session_id_hash, ip_hash, user_agent_hash, device_label, expires_at) VALUES (?, ?, ?, ?, ?, ?)');
        $s->execute([$userId, $sidHash, $ipHash, $uaHash, $deviceLabel, $expiresAt]);
        return (int) $this->db->lastInsertId();
    }

    public function findByHash(string $sidHash): ?array
    {
        $s = $this->db->prepare('SELECT * FROM user_sessions WHERE session_id_hash = ? LIMIT 1');
        $s->execute([$sidHash]);
        $r = $s->fetch();
        return $r ?: null;
    }

    public function touch(int $id): void
    {
        $this->db->prepare('UPDATE user_sessions SET last_seen_at = CURRENT_TIMESTAMP WHERE id = ?')->execute([$id]);
    }

    /** Session IDs are regenerated on login/password change; keep the tracking row pointing at the new ID. */
    public function rehash(string $oldHash, string $newHash): void
    {
        $this->db->prepare('UPDATE user_sessions SET session_id_hash = ? WHERE session_id_hash = ? AND revoked_at IS NULL')->execute([$newHash, $oldHash]);
    }

    public function revokeByHash(string $sidHash): void
    {
        $this->db->prepare('UPDATE user_sessions SET revoked_at = CURRENT_TIMESTAMP WHERE session_id_hash = ? AND revoked_at IS NULL')->execute([$sidHash]);
    }

    /** Revokes one session, but only if it belongs to $userId. Returns whether a row changed. */
    public function revokeOwned(int $id, int $userId): bool
    {
        $s = $this->db->prepare('UPDATE user_sessions SET revoked_at = CURRENT_TIMESTAMP WHERE id = ? AND user_id = ? AND revoked_at IS NULL');
        $s->execute([$id, $userId]);
        return $s->rowCount() > 0;
    }

    /** Revokes every active session of the user, optionally keeping one (the current device). */
    public function revokeAllForUser(int $userId, ?string $exceptHash = null): int
    {
        if ($exceptHash !== null) {
            $s = $this->db->prepare('UPDATE user_sessions SET revoked_at = CURRENT_TIMESTAMP WHERE user_id = ? AND revoked_at IS NULL AND session_id_hash <> ?');
            $s->execute([$userId, $exceptHash]);
        } else {
            $s = $this->db->prepare('UPDATE user_sessions SET revoked_at = CURRENT_TIMESTAMP WHERE user_id = ? AND revoked_at IS NULL');
            $s->execute([$userId]);
        }
        return $s->rowCount();
    }

    public function activeForUser(int $userId, string $nowUtc): array
    {
        $s = $this->db->prepare('SELECT id, session_id_hash, device_label, created_at, last_seen_at FROM user_sessions WHERE user_id = ? AND revoked_at IS NULL AND expires_at > ? ORDER BY last_seen_at DESC');
        $s->execute([$userId, $nowUtc]);
        return $s->fetchAll();
    }

    /** Housekeeping: drop rows that expired or were revoked more than $days ago. */
    public function purgeOld(string $cutoffUtc): int
    {
        $s = $this->db->prepare('DELETE FROM user_sessions WHERE expires_at < ? OR (revoked_at IS NOT NULL AND revoked_at < ?)');
        $s->execute([$cutoffUtc, $cutoffUtc]);
        return $s->rowCount();
    }
}
