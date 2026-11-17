<?php
declare(strict_types=1);
namespace SdutSwap\Repositories;

use SdutSwap\Core\Database;
use PDO;

final class AuditRepository
{
    private PDO $db;
    public function __construct(?PDO $db = null) { $this->db = $db ?? Database::pdo(); }

    public function log(int $adminId, string $action, ?string $targetType, ?int $targetId, ?string $details, ?string $ipHash): void
    {
        $this->db->prepare('INSERT INTO admin_audit_logs (admin_id, action, target_type, target_id, details, ip_hash) VALUES (?, ?, ?, ?, ?, ?)')
            ->execute([$adminId, $action, $targetType, $targetId, $details, $ipHash]);
    }

    public function securityEvent(string $type, string $severity, ?int $userId, ?string $details, ?string $ipHash): void
    {
        $this->db->prepare('INSERT INTO security_events (event_type, severity, user_id, details, ip_hash) VALUES (?, ?, ?, ?, ?)')
            ->execute([$type, $severity, $userId, $details, $ipHash]);
    }

    public function auditLog(int $page = 1, int $perPage = 30): array
    {
        $page = max(1, $page); $offset = ($page - 1) * $perPage;
        $s = $this->db->prepare('SELECT a.*, u.name AS admin_name FROM admin_audit_logs a JOIN users u ON u.id = a.admin_id ORDER BY a.created_at DESC LIMIT ? OFFSET ?');
        $s->bindValue(1, $perPage, PDO::PARAM_INT);
        $s->bindValue(2, $offset, PDO::PARAM_INT);
        $s->execute();
        return $s->fetchAll();
    }

    public function securityEvents(int $page = 1, int $perPage = 30, string $severity = ''): array
    {
        $page = max(1, $page); $offset = ($page - 1) * $perPage;
        $allowed = ['low', 'medium', 'high'];
        if (in_array($severity, $allowed, true)) {
            $s = $this->db->prepare('SELECT * FROM security_events WHERE severity = ? ORDER BY created_at DESC LIMIT ? OFFSET ?');
            $s->bindValue(1, $severity, PDO::PARAM_STR);
            $s->bindValue(2, $perPage, PDO::PARAM_INT);
            $s->bindValue(3, $offset, PDO::PARAM_INT);
            $s->execute();
        } else {
            $s = $this->db->prepare('SELECT * FROM security_events ORDER BY created_at DESC LIMIT ? OFFSET ?');
            $s->bindValue(1, $perPage, PDO::PARAM_INT);
            $s->bindValue(2, $offset, PDO::PARAM_INT);
            $s->execute();
        }
        return $s->fetchAll();
    }
}
