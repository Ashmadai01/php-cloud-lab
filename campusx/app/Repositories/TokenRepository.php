<?php
declare(strict_types=1);
namespace SdutSwap\Repositories;

use SdutSwap\Core\Database;
use PDO;

final class TokenRepository
{
    private PDO $db;
    public function __construct(?PDO $db = null) { $this->db = $db ?? Database::pdo(); }

    private function hash(string $token): string { return hash('sha256', $token); }

    public function createEmailVerification(int $userId, string $token, int $ttlHours = 24): void
    {
        $this->db->prepare('DELETE FROM email_verification_tokens WHERE user_id = ? AND used_at IS NULL')->execute([$userId]);
        $expires = gmdate('Y-m-d H:i:s', time() + $ttlHours * 3600);
        $s = $this->db->prepare('INSERT INTO email_verification_tokens (user_id, token_hash, expires_at) VALUES (?, ?, ?)');
        $s->execute([$userId, $this->hash($token), $expires]);
    }

    public function consumeEmailVerification(string $token): ?int
    {
        $s = $this->db->prepare('SELECT id, user_id FROM email_verification_tokens WHERE token_hash = ? AND used_at IS NULL AND expires_at > CURRENT_TIMESTAMP LIMIT 1');
        $s->execute([$this->hash($token)]);
        $row = $s->fetch();
        if (!$row) return null;
        $this->db->prepare('UPDATE email_verification_tokens SET used_at = CURRENT_TIMESTAMP WHERE id = ?')->execute([(int) $row['id']]);
        return (int) $row['user_id'];
    }

    public function createPasswordReset(int $userId, string $token, int $ttlMinutes = 60): void
    {
        $this->db->prepare('DELETE FROM password_reset_tokens WHERE user_id = ? AND used_at IS NULL')->execute([$userId]);
        $expires = gmdate('Y-m-d H:i:s', time() + $ttlMinutes * 60);
        $s = $this->db->prepare('INSERT INTO password_reset_tokens (user_id, token_hash, expires_at) VALUES (?, ?, ?)');
        $s->execute([$userId, $this->hash($token), $expires]);
    }

    public function consumePasswordReset(string $token): ?int
    {
        $s = $this->db->prepare('SELECT id, user_id FROM password_reset_tokens WHERE token_hash = ? AND used_at IS NULL AND expires_at > CURRENT_TIMESTAMP LIMIT 1');
        $s->execute([$this->hash($token)]);
        $row = $s->fetch();
        if (!$row) return null;
        $this->db->prepare('UPDATE password_reset_tokens SET used_at = CURRENT_TIMESTAMP WHERE id = ?')->execute([(int) $row['id']]);
        return (int) $row['user_id'];
    }
}
