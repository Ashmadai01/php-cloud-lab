<?php
declare(strict_types=1);
namespace SdutSwap\Repositories;

use SdutSwap\Core\Database;
use PDO;

final class SupportRepository
{
    private PDO $db;
    public function __construct(?PDO $db = null) { $this->db = $db ?? Database::pdo(); }

    /** @param list<string> $attachmentPaths private-storage paths of already-validated images */
    public function createTicket(int $userId, string $type, string $category, string $subject, string $priority, string $body, array $attachmentPaths = []): int
    {
        $this->db->beginTransaction();
        try {
            $this->db->prepare('INSERT INTO support_tickets (user_id, ticket_type, category, subject, priority) VALUES (?, ?, ?, ?, ?)')
                ->execute([$userId, $type, $category, $subject, $priority]);
            $id = (int) $this->db->lastInsertId();
            $this->db->prepare('INSERT INTO ticket_messages (ticket_id, author_id, body) VALUES (?, ?, ?)')
                ->execute([$id, $userId, $body]);
            $messageId = (int) $this->db->lastInsertId();
            $this->addAttachments($messageId, $attachmentPaths);
            $this->db->commit();
            return $id;
        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            throw $e;
        }
    }

    /** @param list<string> $paths */
    private function addAttachments(int $messageId, array $paths): void
    {
        if ($paths === []) return;
        $ins = $this->db->prepare('INSERT INTO ticket_attachments (ticket_message_id, file_path) VALUES (?, ?)');
        foreach ($paths as $path) {
            $ins->execute([$messageId, $path]);
        }
    }

    public function findAttachment(int $id): ?array
    {
        $s = $this->db->prepare('SELECT ta.*, tm.ticket_id, t.user_id AS owner_id FROM ticket_attachments ta JOIN ticket_messages tm ON tm.id = ta.ticket_message_id JOIN support_tickets t ON t.id = tm.ticket_id WHERE ta.id = ? LIMIT 1');
        $s->execute([$id]);
        $r = $s->fetch();
        return $r ?: null;
    }

    public function ticketsForUser(int $userId): array
    {
        $s = $this->db->prepare('SELECT * FROM support_tickets WHERE user_id = ? ORDER BY updated_at DESC');
        $s->execute([$userId]);
        return $s->fetchAll();
    }

    public function findTicket(int $id): ?array
    {
        $s = $this->db->prepare('SELECT t.*, u.name AS user_name, u.email AS user_email FROM support_tickets t JOIN users u ON u.id = t.user_id WHERE t.id = ? LIMIT 1');
        $s->execute([$id]);
        $r = $s->fetch();
        return $r ?: null;
    }

    /** Messages of a ticket, each with an 'attachments' list of attachment IDs (files are served by an authorized endpoint). */
    public function ticketMessages(int $ticketId): array
    {
        $s = $this->db->prepare('SELECT tm.*, u.name AS author_name, u.role AS author_role FROM ticket_messages tm JOIN users u ON u.id = tm.author_id WHERE tm.ticket_id = ? ORDER BY tm.created_at ASC, tm.id ASC');
        $s->execute([$ticketId]);
        $messages = $s->fetchAll();
        $a = $this->db->prepare('SELECT ta.id, ta.ticket_message_id FROM ticket_attachments ta JOIN ticket_messages tm ON tm.id = ta.ticket_message_id WHERE tm.ticket_id = ? ORDER BY ta.id');
        $a->execute([$ticketId]);
        $byMessage = [];
        foreach ($a->fetchAll() as $row) {
            $byMessage[(int) $row['ticket_message_id']][] = (int) $row['id'];
        }
        foreach ($messages as &$m) {
            $m['attachments'] = $byMessage[(int) $m['id']] ?? [];
        }
        unset($m);
        return $messages;
    }

    /** @param list<string> $attachmentPaths */
    public function reply(int $ticketId, int $authorId, string $body, array $attachmentPaths = []): int
    {
        $this->db->beginTransaction();
        try {
            $this->db->prepare('INSERT INTO ticket_messages (ticket_id, author_id, body) VALUES (?, ?, ?)')->execute([$ticketId, $authorId, $body]);
            $messageId = (int) $this->db->lastInsertId();
            $this->addAttachments($messageId, $attachmentPaths);
            $this->db->prepare('UPDATE support_tickets SET updated_at = CURRENT_TIMESTAMP WHERE id = ?')->execute([$ticketId]);
            $this->db->commit();
            return $messageId;
        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            throw $e;
        }
    }

    public function setTicketStatus(int $id, string $status): void
    {
        $this->db->prepare('UPDATE support_tickets SET status = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?')->execute([$status, $id]);
    }

    public function ticketQueue(string $status = '', int $page = 1, int $perPage = 20): array
    {
        $page = max(1, $page); $offset = ($page - 1) * $perPage;
        $allowed = ['open', 'in_review', 'waiting_for_user', 'resolved', 'closed'];
        if (in_array($status, $allowed, true)) {
            $s = $this->db->prepare('SELECT t.*, u.name AS user_name, u.email AS user_email FROM support_tickets t JOIN users u ON u.id = t.user_id WHERE t.status = ? ORDER BY t.updated_at DESC LIMIT ? OFFSET ?');
            $s->bindValue(1, $status, PDO::PARAM_STR);
            $s->bindValue(2, $perPage, PDO::PARAM_INT);
            $s->bindValue(3, $offset, PDO::PARAM_INT);
            $s->execute();
        } else {
            $s = $this->db->prepare('SELECT t.*, u.name AS user_name, u.email AS user_email FROM support_tickets t JOIN users u ON u.id = t.user_id ORDER BY t.updated_at DESC LIMIT ? OFFSET ?');
            $s->bindValue(1, $perPage, PDO::PARAM_INT);
            $s->bindValue(2, $offset, PDO::PARAM_INT);
            $s->execute();
        }
        return $s->fetchAll();
    }

    /** @param list<string> $evidencePaths private-storage paths of already-validated images */
    public function createReport(int $reporterId, string $targetType, int $targetId, string $reportType, string $description, array $evidencePaths = []): int
    {
        $this->db->beginTransaction();
        try {
            $this->db->prepare('INSERT INTO reports (reporter_id, target_type, target_id, report_type, description) VALUES (?, ?, ?, ?, ?)')
                ->execute([$reporterId, $targetType, $targetId, $reportType, $description]);
            $id = (int) $this->db->lastInsertId();
            $ins = $this->db->prepare('INSERT INTO report_evidence (report_id, file_path) VALUES (?, ?)');
            foreach ($evidencePaths as $path) {
                $ins->execute([$id, $path]);
            }
            $this->db->commit();
            return $id;
        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            throw $e;
        }
    }

    public function findEvidence(int $evidenceId): ?array
    {
        $s = $this->db->prepare('SELECT * FROM report_evidence WHERE id = ? LIMIT 1');
        $s->execute([$evidenceId]);
        $r = $s->fetch();
        return $r ?: null;
    }

    /** Ticket attachments of resolved/closed tickets last touched before $cutoffUtc. */
    public function purgeableAttachments(string $cutoffUtc): array
    {
        $s = $this->db->prepare("SELECT ta.id, ta.file_path FROM ticket_attachments ta JOIN ticket_messages tm ON tm.id = ta.ticket_message_id JOIN support_tickets t ON t.id = tm.ticket_id WHERE t.status IN ('resolved','closed') AND t.updated_at < ?");
        $s->execute([$cutoffUtc]);
        return $s->fetchAll();
    }

    public function deleteAttachmentRow(int $id): void
    {
        $this->db->prepare('DELETE FROM ticket_attachments WHERE id = ?')->execute([$id]);
    }

    /** Evidence of resolved/dismissed reports last touched before $cutoffUtc. */
    public function purgeableEvidence(string $cutoffUtc): array
    {
        $s = $this->db->prepare("SELECT re.id, re.file_path FROM report_evidence re JOIN reports r ON r.id = re.report_id WHERE r.status IN ('resolved','dismissed') AND r.updated_at < ?");
        $s->execute([$cutoffUtc]);
        return $s->fetchAll();
    }

    public function deleteEvidenceRow(int $id): void
    {
        $this->db->prepare('DELETE FROM report_evidence WHERE id = ?')->execute([$id]);
    }

    public function reportsForUser(int $userId): array
    {
        $s = $this->db->prepare('SELECT * FROM reports WHERE reporter_id = ? ORDER BY created_at DESC');
        $s->execute([$userId]);
        return $s->fetchAll();
    }

    public function findReport(int $id): ?array
    {
        $s = $this->db->prepare('SELECT r.*, u.name AS reporter_name, u.email AS reporter_email FROM reports r JOIN users u ON u.id = r.reporter_id WHERE r.id = ? LIMIT 1');
        $s->execute([$id]);
        $r = $s->fetch();
        return $r ?: null;
    }

    public function reportEvidence(int $reportId): array
    {
        $s = $this->db->prepare('SELECT * FROM report_evidence WHERE report_id = ?');
        $s->execute([$reportId]);
        return $s->fetchAll();
    }

    public function setReportStatus(int $id, string $status, ?string $notes = null): void
    {
        $this->db->prepare('UPDATE reports SET status = ?, moderator_notes = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?')->execute([$status, $notes, $id]);
    }

    public function reportQueue(string $status = '', int $page = 1, int $perPage = 20): array
    {
        $page = max(1, $page); $offset = ($page - 1) * $perPage;
        $allowed = ['open', 'under_review', 'resolved', 'dismissed'];
        if (in_array($status, $allowed, true)) {
            $s = $this->db->prepare('SELECT r.*, u.name AS reporter_name FROM reports r JOIN users u ON u.id = r.reporter_id WHERE r.status = ? ORDER BY r.created_at DESC LIMIT ? OFFSET ?');
            $s->bindValue(1, $status, PDO::PARAM_STR);
            $s->bindValue(2, $perPage, PDO::PARAM_INT);
            $s->bindValue(3, $offset, PDO::PARAM_INT);
            $s->execute();
        } else {
            $s = $this->db->prepare('SELECT r.*, u.name AS reporter_name FROM reports r JOIN users u ON u.id = r.reporter_id ORDER BY r.created_at DESC LIMIT ? OFFSET ?');
            $s->bindValue(1, $perPage, PDO::PARAM_INT);
            $s->bindValue(2, $offset, PDO::PARAM_INT);
            $s->execute();
        }
        return $s->fetchAll();
    }
}
