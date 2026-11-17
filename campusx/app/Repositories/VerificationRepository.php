<?php
declare(strict_types=1);
namespace SdutSwap\Repositories;

use SdutSwap\Core\Database;
use PDO;

final class VerificationRepository
{
    private PDO $db;
    public function __construct(?PDO $db = null) { $this->db = $db ?? Database::pdo(); }

    public function latestForUser(int $userId): ?array
    {
        $s = $this->db->prepare('SELECT * FROM student_verifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 1');
        $s->execute([$userId]);
        $r = $s->fetch();
        return $r ?: null;
    }

    /** Letters and digits only, upper-cased, so "2024-001 234" and "2024001234" count as the same number. */
    public static function normalizeStudentNumber(string $studentNumber): string
    {
        return strtoupper((string) preg_replace('/[^A-Za-z0-9]/', '', $studentNumber));
    }

    public static function hashStudentNumber(string $studentNumber): string
    {
        return hash('sha256', self::normalizeStudentNumber($studentNumber));
    }

    /**
     * Is this student number held by ANOTHER user's pending or verified submission?
     * (Rejected submissions release the number.)
     */
    public function studentNumberInUse(string $studentNumber, int $exceptUserId): bool
    {
        $s = $this->db->prepare('SELECT 1 FROM student_verifications WHERE active_hash = ? AND user_id <> ? LIMIT 1');
        $s->execute([self::hashStudentNumber($studentNumber), $exceptUserId]);
        return (bool) $s->fetch();
    }

    /**
     * Creates a pending submission and marks the user as "pending".
     * Throws PDOException (SQLSTATE 23000) if the number is already held by a pending/verified
     * submission - the unique index on active_hash is what makes this race-proof.
     */
    public function create(int $userId, string $fullName, string $studentNumber, string $university, string $cardPath): int
    {
        $normalized = self::normalizeStudentNumber($studentNumber);
        $hash = hash('sha256', $normalized);
        $last4 = substr($normalized, -4);
        $this->db->beginTransaction();
        try {
            $s = $this->db->prepare('INSERT INTO student_verifications (user_id, full_name, student_number_hash, student_number_last4, university, card_image_path, active_hash, status) VALUES (?, ?, ?, ?, ?, ?, ?, \'pending\')');
            $s->execute([$userId, $fullName, $hash, $last4, $university, $cardPath, $hash]);
            $id = (int) $this->db->lastInsertId();
            $this->db->prepare('UPDATE users SET verification_status = \'pending\' WHERE id = ?')->execute([$userId]);
            $this->db->commit();
            return $id;
        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            throw $e;
        }
    }

    public function pendingQueue(int $page = 1, int $perPage = 20): array
    {
        $page = max(1, $page); $offset = ($page - 1) * $perPage;
        $s = $this->db->prepare('SELECT sv.*, u.name AS user_name, u.email AS user_email FROM student_verifications sv JOIN users u ON u.id = sv.user_id WHERE sv.status = \'pending\' ORDER BY sv.created_at ASC LIMIT ? OFFSET ?');
        $s->bindValue(1, $perPage, PDO::PARAM_INT);
        $s->bindValue(2, $offset, PDO::PARAM_INT);
        $s->execute();
        return $s->fetchAll();
    }

    public function findById(int $id): ?array
    {
        $s = $this->db->prepare('SELECT sv.*, u.name AS user_name, u.email AS user_email FROM student_verifications sv JOIN users u ON u.id = sv.user_id WHERE sv.id = ? LIMIT 1');
        $s->execute([$id]);
        $r = $s->fetch();
        return $r ?: null;
    }

    /**
     * Records the decision, keeps users.verification_status / is_verified in step, and frees the
     * student number when a submission is rejected. Only a PENDING submission can be decided.
     */
    public function decide(int $id, string $status, int $reviewerId, ?string $reason = null): void
    {
        $this->db->beginTransaction();
        try {
            $s = $this->db->prepare('UPDATE student_verifications SET status = ?, rejection_reason = ?, reviewed_by = ?, reviewed_at = CURRENT_TIMESTAMP, active_hash = CASE WHEN ? = \'rejected\' THEN NULL ELSE active_hash END WHERE id = ? AND status = \'pending\'');
            $s->execute([$status, $reason, $reviewerId, $status, $id]);
            if ($s->rowCount() > 0) {
                $u = $this->db->prepare('UPDATE users SET verification_status = ?, is_verified = ? WHERE id = (SELECT user_id FROM student_verifications WHERE id = ?)');
                $u->execute([$status, $status === 'verified' ? 1 : 0, $id]);
            }
            $this->db->commit();
        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            throw $e;
        }
    }

    /** Submissions whose card image may be deleted: already decided, reviewed before $cutoffUtc, file not yet purged. */
    public function purgeCandidates(string $cutoffUtc): array
    {
        $s = $this->db->prepare("SELECT id, card_image_path FROM student_verifications WHERE status IN ('verified','rejected') AND reviewed_at IS NOT NULL AND reviewed_at < ? AND card_purged_at IS NULL AND card_image_path <> ''");
        $s->execute([$cutoffUtc]);
        return $s->fetchAll();
    }

    public function markCardPurged(int $id): void
    {
        $this->db->prepare("UPDATE student_verifications SET card_purged_at = CURRENT_TIMESTAMP, card_image_path = '' WHERE id = ?")->execute([$id]);
    }
}
