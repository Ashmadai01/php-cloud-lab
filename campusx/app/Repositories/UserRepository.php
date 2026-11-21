<?php
declare(strict_types=1);
namespace SdutSwap\Repositories;

use SdutSwap\Core\Database;
use PDO;

final class UserRepository
{
    private PDO $db;
    public function __construct(?PDO $db = null) { $this->db = $db ?? Database::pdo(); }

    public function findById(int $id): ?array
    {
        $s = $this->db->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
        $s->execute([$id]);
        $r = $s->fetch();
        return $r ?: null;
    }

    public function findPublicProfile(int $id): ?array
    {
        $s = $this->db->prepare(
            'SELECT id, name, university, bio, avatar_path, is_verified, created_at
             FROM users
             WHERE id = ? AND suspended_at IS NULL
             LIMIT 1'
        );
        $s->execute([$id]);
        $r = $s->fetch();

        return $r ?: null;
    }

    public function findByEmail(string $email): ?array
    {
        $s = $this->db->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
        $s->execute([strtolower(trim($email))]);
        $r = $s->fetch();
        return $r ?: null;
    }

    public function create(string $name, string $email, string $passwordHash): int
    {
        $s = $this->db->prepare('INSERT INTO users (name, email, password_hash, role) VALUES (?, ?, ?, \'student\')');
        $s->execute([$name, strtolower(trim($email)), $passwordHash]);
        return (int) $this->db->lastInsertId();
    }

    public function updateProfile(int $id, string $name, ?string $university, ?string $bio): void
    {
        $s = $this->db->prepare('UPDATE users SET name = ?, university = ?, bio = ? WHERE id = ?');
        $s->execute([$name, $university, $bio, $id]);
    }

    public function updateAvatar(int $id, ?string $path): void
    {
        $s = $this->db->prepare('UPDATE users SET avatar_path = ? WHERE id = ?');
        $s->execute([$path, $id]);
    }

    public function updatePassword(int $id, string $hash): void
    {
        $s = $this->db->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
        $s->execute([$hash, $id]);
    }

    public function setVerified(int $id, bool $v): void
    {
        $s = $this->db->prepare('UPDATE users SET is_verified = ? WHERE id = ?');
        $s->execute([$v ? 1 : 0, $id]);
    }

    public function setEmailVerified(int $id): void
    {
        $s = $this->db->prepare('UPDATE users SET email_verified_at = CURRENT_TIMESTAMP WHERE id = ?');
        $s->execute([$id]);
    }

    public function touchLogin(int $id): void
    {
        $s = $this->db->prepare('UPDATE users SET last_login_at = CURRENT_TIMESTAMP WHERE id = ?');
        $s->execute([$id]);
    }

    public function suspend(int $id, ?string $reason): void
    {
        $s = $this->db->prepare('UPDATE users SET suspended_at = CURRENT_TIMESTAMP, suspension_reason = ? WHERE id = ?');
        $s->execute([$reason, $id]);
    }

    public function restore(int $id): void
    {
        $s = $this->db->prepare('UPDATE users SET suspended_at = NULL, suspension_reason = NULL WHERE id = ?');
        $s->execute([$id]);
    }

    public function setRole(int $id, string $role): void
    {
        $s = $this->db->prepare('UPDATE users SET role = ? WHERE id = ?');
        $s->execute([$role, $id]);
    }

    public function countAdmins(): int
    {
        $s = $this->db->prepare("SELECT COUNT(*) FROM users WHERE role = 'admin'");
        $s->execute();
        return (int) $s->fetchColumn();
    }

    public function listAll(int $page = 1, int $perPage = 20, string $search = ''): array
    {
        $page = max(1, $page); $perPage = min(100, max(1, $perPage));
        $offset = ($page - 1) * $perPage;
        if ($search !== '') {
            $s = $this->db->prepare('SELECT * FROM users WHERE name LIKE ? OR email LIKE ? ORDER BY created_at DESC LIMIT ? OFFSET ?');
            $s->bindValue(1, "%{$search}%", PDO::PARAM_STR);
            $s->bindValue(2, "%{$search}%", PDO::PARAM_STR);
            $s->bindValue(3, $perPage, PDO::PARAM_INT);
            $s->bindValue(4, $offset, PDO::PARAM_INT);
            $s->execute();
        } else {
            $s = $this->db->prepare('SELECT * FROM users ORDER BY created_at DESC LIMIT ? OFFSET ?');
            $s->bindValue(1, $perPage, PDO::PARAM_INT);
            $s->bindValue(2, $offset, PDO::PARAM_INT);
            $s->execute();
        }
        return $s->fetchAll();
    }

    public function stats(): array
    {
        $q = $this->db->query("SELECT
            (SELECT COUNT(*) FROM users) AS total_users,
            (SELECT COUNT(*) FROM users WHERE is_verified = 1) AS verified,
            (SELECT COUNT(*) FROM student_verifications WHERE status = 'pending') AS pending_verification,
            (SELECT COUNT(*) FROM listings WHERE status = 'active') AS active_listings,
            (SELECT COUNT(*) FROM support_tickets WHERE status IN ('open','in_review','waiting_for_user')) AS open_tickets,
            (SELECT COUNT(*) FROM reports WHERE status IN ('open','under_review')) AS open_reports");
        return $q->fetch() ?: [];
    }
}
