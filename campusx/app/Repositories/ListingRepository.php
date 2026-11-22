<?php
declare(strict_types=1);
namespace SdutSwap\Repositories;

use SdutSwap\Core\Database;
use PDO;

final class ListingRepository
{
    private PDO $db;
    public function __construct(?PDO $db = null) { $this->db = $db ?? Database::pdo(); }

    public function categories(): array
    {
        return $this->db->query('SELECT * FROM categories ORDER BY sort_order, name')->fetchAll();
    }

    public function findById(int $id): ?array
    {
        $s = $this->db->prepare('SELECT l.*, (SELECT li.file_path FROM listing_images li WHERE li.listing_id = l.id ORDER BY li.sort_order, li.id LIMIT 1) AS cover_path, c.name AS category_name, u.name AS seller_name, u.is_verified AS seller_verified, u.university AS seller_university, u.avatar_path AS seller_avatar FROM listings l LEFT JOIN categories c ON c.id = l.category_id JOIN users u ON u.id = l.seller_id WHERE l.id = ? LIMIT 1');
        $s->execute([$id]);
        $r = $s->fetch();
        return $r ?: null;
    }

    public function images(int $listingId): array
    {
        $s = $this->db->prepare('SELECT * FROM listing_images WHERE listing_id = ? ORDER BY sort_order, id');
        $s->execute([$listingId]);
        return $s->fetchAll();
    }

    public function addImage(int $listingId, string $path, int $order): void
    {
        $s = $this->db->prepare('INSERT INTO listing_images (listing_id, file_path, sort_order) VALUES (?, ?, ?)');
        $s->execute([$listingId, $path, $order]);
    }

    public function deleteImage(int $imageId): ?string
    {
        $s = $this->db->prepare('SELECT file_path FROM listing_images WHERE id = ? LIMIT 1');
        $s->execute([$imageId]);
        $row = $s->fetch();
        if (!$row) return null;
        $this->db->prepare('DELETE FROM listing_images WHERE id = ?')->execute([$imageId]);
        return (string) $row['file_path'];
    }

    public function nextImageOrder(int $listingId): int
    {
        $s = $this->db->prepare('SELECT COALESCE(MAX(sort_order), -1) + 1 FROM listing_images WHERE listing_id = ?');
        $s->execute([$listingId]);
        return (int) $s->fetchColumn();
    }

    public function create(array $data): int
    {
        $s = $this->db->prepare('INSERT INTO listings (seller_id, title, description, price, currency, category_id, item_condition, campus, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $s->execute([$data['seller_id'], $data['title'], $data['description'], $data['price'], $data['currency'], $data['category_id'] ?: null, $data['item_condition'], $data['campus'] ?: null, $data['status']]);
        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $s = $this->db->prepare('UPDATE listings SET title = ?, description = ?, price = ?, currency = ?, category_id = ?, item_condition = ?, campus = ?, status = ? WHERE id = ?');
        $s->execute([$data['title'], $data['description'], $data['price'], $data['currency'], $data['category_id'] ?: null, $data['item_condition'], $data['campus'] ?: null, $data['status'], $id]);
    }

    public function setStatus(int $id, string $status, ?string $reason = null): void
    {
        $s = $this->db->prepare('UPDATE listings SET status = ?, moderation_reason = ? WHERE id = ?');
        $s->execute([$status, $reason, $id]);
    }

    public function incrementViews(int $id): void
    {
        $this->db->prepare('UPDATE listings SET views = views + 1 WHERE id = ?')->execute([$id]);
    }

    public function search(array $filters, int $page = 1, int $perPage = 12): array
    {
        $page = max(1, $page); $perPage = min(50, max(1, $perPage));
        $offset = ($page - 1) * $perPage;
        $where = ['l.status = \'active\''];
        $params = [];
        if (!empty($filters['q'])) {
            $where[] = '(l.title LIKE ? OR l.description LIKE ?)';
            $params[] = "%{$filters['q']}%"; $params[] = "%{$filters['q']}%";
        }
        if (!empty($filters['category_id'])) { $where[] = 'l.category_id = ?'; $params[] = (int) $filters['category_id']; }
        if (!empty($filters['condition'])) { $where[] = 'l.item_condition = ?'; $params[] = $filters['condition']; }
        if (isset($filters['min_price']) && $filters['min_price'] !== '') { $where[] = 'l.price >= ?'; $params[] = (float) $filters['min_price']; }
        if (isset($filters['max_price']) && $filters['max_price'] !== '') { $where[] = 'l.price <= ?'; $params[] = (float) $filters['max_price']; }
        $sortMap = ['newest' => 'l.created_at DESC', 'oldest' => 'l.created_at ASC', 'price_asc' => 'l.price ASC', 'price_desc' => 'l.price DESC', 'popular' => 'l.views DESC'];
        $sort = $sortMap[$filters['sort'] ?? 'newest'] ?? 'l.created_at DESC';
        $sql = 'SELECT l.*, (SELECT li.file_path FROM listing_images li WHERE li.listing_id = l.id ORDER BY li.sort_order, li.id LIMIT 1) AS cover_path, c.name AS category_name, u.name AS seller_name, u.is_verified AS seller_verified, u.avatar_path AS seller_avatar FROM listings l LEFT JOIN categories c ON c.id = l.category_id JOIN users u ON u.id = l.seller_id WHERE ' . implode(' AND ', $where) . " ORDER BY {$sort} LIMIT ? OFFSET ?";
        $s = $this->db->prepare($sql);
        $i = 1;
        foreach ($params as $p) { $s->bindValue($i++, $p, is_int($p) ? PDO::PARAM_INT : PDO::PARAM_STR); }
        $s->bindValue($i++, $perPage, PDO::PARAM_INT);
        $s->bindValue($i++, $offset, PDO::PARAM_INT);
        $s->execute();
        return $s->fetchAll();
    }

    public function countSearch(array $filters): int
    {
        $where = ['l.status = \'active\''];
        $params = [];
        if (!empty($filters['q'])) { $where[] = '(l.title LIKE ? OR l.description LIKE ?)'; $params[] = "%{$filters['q']}%"; $params[] = "%{$filters['q']}%"; }
        if (!empty($filters['category_id'])) { $where[] = 'l.category_id = ?'; $params[] = (int) $filters['category_id']; }
        if (!empty($filters['condition'])) { $where[] = 'l.item_condition = ?'; $params[] = $filters['condition']; }
        if (isset($filters['min_price']) && $filters['min_price'] !== '') { $where[] = 'l.price >= ?'; $params[] = (float) $filters['min_price']; }
        if (isset($filters['max_price']) && $filters['max_price'] !== '') { $where[] = 'l.price <= ?'; $params[] = (float) $filters['max_price']; }
        $s = $this->db->prepare('SELECT COUNT(*) FROM listings l WHERE ' . implode(' AND ', $where));
        $s->execute($params);
        return (int) $s->fetchColumn();
    }

    public function latest(int $limit = 8): array
    {
        $s = $this->db->prepare('SELECT l.*, (SELECT li.file_path FROM listing_images li WHERE li.listing_id = l.id ORDER BY li.sort_order, li.id LIMIT 1) AS cover_path, c.name AS category_name, u.name AS seller_name, u.is_verified AS seller_verified FROM listings l LEFT JOIN categories c ON c.id = l.category_id JOIN users u ON u.id = l.seller_id WHERE l.status = \'active\' ORDER BY l.created_at DESC LIMIT ?');
        $s->bindValue(1, $limit, PDO::PARAM_INT);
        $s->execute();
        return $s->fetchAll();
    }

    public function featured(int $limit = 8): array
    {
        $s = $this->db->prepare('SELECT l.*, (SELECT li.file_path FROM listing_images li WHERE li.listing_id = l.id ORDER BY li.sort_order, li.id LIMIT 1) AS cover_path, c.name AS category_name, u.name AS seller_name, u.is_verified AS seller_verified FROM listings l LEFT JOIN categories c ON c.id = l.category_id JOIN users u ON u.id = l.seller_id WHERE l.status = \'active\' ORDER BY l.views DESC, l.created_at DESC LIMIT ?');
        $s->bindValue(1, $limit, PDO::PARAM_INT);
        $s->execute();
        return $s->fetchAll();
    }

    public function bySeller(int $sellerId, int $page = 1, int $perPage = 20): array
    {
        $page = max(1, $page); $offset = ($page - 1) * $perPage;
        $s = $this->db->prepare('SELECT l.*, (SELECT li.file_path FROM listing_images li WHERE li.listing_id = l.id ORDER BY li.sort_order, li.id LIMIT 1) AS cover_path, c.name AS category_name FROM listings l LEFT JOIN categories c ON c.id = l.category_id WHERE l.seller_id = ? ORDER BY l.created_at DESC LIMIT ? OFFSET ?');
        $s->bindValue(1, $sellerId, PDO::PARAM_INT);
        $s->bindValue(2, $perPage, PDO::PARAM_INT);
        $s->bindValue(3, $offset, PDO::PARAM_INT);
        $s->execute();
        return $s->fetchAll();
    }

    public function toggleFavorite(int $userId, int $listingId): bool
    {
        $s = $this->db->prepare('SELECT id FROM favorites WHERE user_id = ? AND listing_id = ? LIMIT 1');
        $s->execute([$userId, $listingId]);
        if ($s->fetch()) {
            $this->db->prepare('DELETE FROM favorites WHERE user_id = ? AND listing_id = ?')->execute([$userId, $listingId]);
            return false;
        }
        $this->db->prepare('INSERT INTO favorites (user_id, listing_id) VALUES (?, ?)')->execute([$userId, $listingId]);
        return true;
    }

    public function isFavorite(int $userId, int $listingId): bool
    {
        $s = $this->db->prepare('SELECT 1 FROM favorites WHERE user_id = ? AND listing_id = ? LIMIT 1');
        $s->execute([$userId, $listingId]);
        return (bool) $s->fetch();
    }

    public function favoritesOf(int $userId): array
    {
        $s = $this->db->prepare('SELECT l.*, (SELECT li.file_path FROM listing_images li WHERE li.listing_id = l.id ORDER BY li.sort_order, li.id LIMIT 1) AS cover_path, c.name AS category_name, u.name AS seller_name FROM listings l LEFT JOIN categories c ON c.id = l.category_id JOIN users u ON u.id = l.seller_id JOIN favorites f ON f.listing_id = l.id WHERE f.user_id = ? ORDER BY f.created_at DESC');
        $s->execute([$userId]);
        return $s->fetchAll();
    }

    public function listAllAdmin(int $page = 1, int $perPage = 20, string $status = ''): array
    {
        $page = max(1, $page); $offset = ($page - 1) * $perPage;
        $allowed = ['draft', 'active', 'sold', 'hidden', 'removed'];
        if (in_array($status, $allowed, true)) {
            $s = $this->db->prepare('SELECT l.*, u.name AS seller_name, u.email AS seller_email FROM listings l JOIN users u ON u.id = l.seller_id WHERE l.status = ? ORDER BY l.created_at DESC LIMIT ? OFFSET ?');
            $s->bindValue(1, $status, PDO::PARAM_STR);
            $s->bindValue(2, $perPage, PDO::PARAM_INT);
            $s->bindValue(3, $offset, PDO::PARAM_INT);
            $s->execute();
        } else {
            $s = $this->db->prepare('SELECT l.*, u.name AS seller_name, u.email AS seller_email FROM listings l JOIN users u ON u.id = l.seller_id ORDER BY l.created_at DESC LIMIT ? OFFSET ?');
            $s->bindValue(1, $perPage, PDO::PARAM_INT);
            $s->bindValue(2, $offset, PDO::PARAM_INT);
            $s->execute();
        }
        return $s->fetchAll();
    }
}
