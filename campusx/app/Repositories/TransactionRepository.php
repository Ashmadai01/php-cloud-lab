<?php
declare(strict_types=1);

namespace SdutSwap\Repositories;

use PDO;
use SdutSwap\Core\Database;

final class TransactionRepository
{
    private PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Database::pdo();
    }

    /**
     * Create a purchase request from a conversation.
     * The listing row is locked to prevent concurrent purchase requests.
     */
    public function createRequest(int $conversationId, int $buyerId): int
    {
        $this->db->beginTransaction();

        try {
            $conversation = $this->db->prepare(
                'SELECT listing_id FROM conversations WHERE id = ?'
            );
            $conversation->execute([$conversationId]);
            $listingId = $conversation->fetchColumn();

            if ($listingId === false) {
                throw new \DomainException('Conversation not found.');
            }

            $listing = $this->db->prepare(
                'SELECT seller_id, status FROM listings WHERE id = ? FOR UPDATE'
            );
            $listing->execute([(int) $listingId]);
            $row = $listing->fetch();

            if (!$row || $row['status'] !== 'active') {
                throw new \DomainException('This listing is no longer available.');
            }

            $listingId = (int) $listingId;
            $sellerId = (int) $row['seller_id'];

            if ($buyerId === $sellerId) {
                throw new \DomainException('You cannot buy your own listing.');
            }

            $participants = $this->db->prepare(
                'SELECT user_id
                 FROM conversation_participants
                 WHERE conversation_id = ?
                   AND user_id IN (?, ?)'
            );
            $participants->execute([$conversationId, $buyerId, $sellerId]);
            $participantIds = array_map('intval', $participants->fetchAll(PDO::FETCH_COLUMN));

            if (!in_array($buyerId, $participantIds, true)
                || !in_array($sellerId, $participantIds, true)) {
                throw new \DomainException('Both buyer and seller must belong to this conversation.');
            }

            $existing = $this->db->prepare(
                "SELECT id
                 FROM transactions
                 WHERE listing_id = ?
                   AND status IN ('pending', 'accepted')
                 LIMIT 1"
            );
            $existing->execute([$listingId]);

            if ($existing->fetch()) {
                throw new \DomainException('This listing already has an active purchase request.');
            }

            $insert = $this->db->prepare(
                "INSERT INTO transactions
                    (listing_id, conversation_id, buyer_id, seller_id, status)
                 VALUES (?, ?, ?, ?, 'pending')"
            );
            $insert->execute([$listingId, $conversationId, $buyerId, $sellerId]);

            $transactionId = (int) $this->db->lastInsertId();
            $this->db->commit();

            return $transactionId;
        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Seller accepts or rejects a pending request.
     */
    public function decideRequest(int $transactionId, int $sellerId, bool $accept): void
    {
        $this->db->beginTransaction();

        try {
            $stmt = $this->db->prepare(
                'SELECT id, listing_id, seller_id, status
                 FROM transactions
                 WHERE id = ?
                 FOR UPDATE'
            );
            $stmt->execute([$transactionId]);
            $transaction = $stmt->fetch();

            if (!$transaction || (int) $transaction['seller_id'] !== $sellerId) {
                throw new \DomainException('Transaction not found.');
            }

            if ($transaction['status'] !== 'pending') {
                throw new \DomainException('This request is no longer pending.');
            }

            if ($accept) {
                $listing = $this->db->prepare(
                    'SELECT status FROM listings WHERE id = ? FOR UPDATE'
                );
                $listing->execute([(int) $transaction['listing_id']]);

                if ($listing->fetchColumn() !== 'active') {
                    throw new \DomainException('This listing is no longer available.');
                }

                $update = $this->db->prepare(
                    "UPDATE transactions SET status = 'accepted'
                     WHERE id = ? AND status = 'pending'"
                );
            } else {
                $update = $this->db->prepare(
                    "UPDATE transactions SET status = 'rejected'
                     WHERE id = ? AND status = 'pending'"
                );
            }

            $update->execute([$transactionId]);

            if ($update->rowCount() !== 1) {
                throw new \DomainException('The request could not be updated.');
            }

            $this->db->commit();
        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Buyer confirms receipt. Completion and listing status change are atomic.
     */
    public function confirmReceipt(int $transactionId, int $buyerId): void
    {
        $this->db->beginTransaction();

        try {
            $stmt = $this->db->prepare(
                'SELECT id, listing_id, buyer_id, status
                 FROM transactions
                 WHERE id = ?
                 FOR UPDATE'
            );
            $stmt->execute([$transactionId]);
            $transaction = $stmt->fetch();

            if (!$transaction || (int) $transaction['buyer_id'] !== $buyerId) {
                throw new \DomainException('Transaction not found.');
            }

            if ($transaction['status'] !== 'accepted') {
                throw new \DomainException('Only accepted transactions can be completed.');
            }

            $listing = $this->db->prepare(
                'SELECT status FROM listings WHERE id = ? FOR UPDATE'
            );
            $listing->execute([(int) $transaction['listing_id']]);

            if ($listing->fetchColumn() !== 'active') {
                throw new \DomainException('This listing is no longer available.');
            }

            $complete = $this->db->prepare(
                "UPDATE transactions
                 SET status = 'completed', completed_at = UTC_TIMESTAMP()
                 WHERE id = ? AND status = 'accepted'"
            );
            $complete->execute([$transactionId]);

            if ($complete->rowCount() !== 1) {
                throw new \DomainException('The transaction could not be completed.');
            }

            $sold = $this->db->prepare(
                "UPDATE listings
                 SET status = 'sold', moderation_reason = NULL
                 WHERE id = ? AND status = 'active'"
            );
            $sold->execute([(int) $transaction['listing_id']]);

            if ($sold->rowCount() !== 1) {
                throw new \DomainException('The listing status could not be updated.');
            }

            // Close any other active purchase requests for this listing.
            $closeOthers = $this->db->prepare(
                "UPDATE transactions
                 SET status = 'rejected'
                 WHERE listing_id = ?
                   AND id <> ?
                   AND status IN ('pending', 'accepted')"
            );
            $closeOthers->execute([
                (int) $transaction['listing_id'],
                $transactionId,
            ]);

            $this->db->commit();
        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Buyer may review the seller once, and only after completion.
     */
    public function createReview(
        int $transactionId,
        int $buyerId,
        int $rating,
        ?string $comment = null
    ): int {
        if ($rating < 1 || $rating > 5) {
            throw new \InvalidArgumentException('Rating must be between 1 and 5.');
        }

        $comment = $comment !== null ? trim($comment) : null;
        if ($comment === '') {
            $comment = null;
        }
        if ($comment !== null && mb_strlen($comment) > 1000) {
            throw new \InvalidArgumentException('Review comment is too long.');
        }

        $this->db->beginTransaction();

        try {
            $stmt = $this->db->prepare(
                'SELECT buyer_id, seller_id, status
                 FROM transactions
                 WHERE id = ?
                 FOR UPDATE'
            );
            $stmt->execute([$transactionId]);
            $transaction = $stmt->fetch();

            if (!$transaction || (int) $transaction['buyer_id'] !== $buyerId) {
                throw new \DomainException('Transaction not found.');
            }

            if ($transaction['status'] !== 'completed') {
                throw new \DomainException('You can review only completed transactions.');
            }

            $existingReview = $this->db->prepare(
                'SELECT id FROM reviews WHERE transaction_id = ? LIMIT 1'
            );
            $existingReview->execute([$transactionId]);

            if ($existingReview->fetchColumn() !== false) {
                throw new \DomainException('You have already reviewed this transaction.');
            }

            $insert = $this->db->prepare(
                'INSERT INTO reviews
                    (transaction_id, reviewer_id, reviewed_user_id, rating, comment)
                 VALUES (?, ?, ?, ?, ?)'
            );
            $insert->execute([
                $transactionId,
                $buyerId,
                (int) $transaction['seller_id'],
                $rating,
                $comment,
            ]);

            $reviewId = (int) $this->db->lastInsertId();
            $this->db->commit();

            return $reviewId;
        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    public function findForUser(int $transactionId, int $userId): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT t.*, l.title AS listing_title,
                    buyer.name AS buyer_name,
                    seller.name AS seller_name,
                    r.id AS review_id, r.rating AS review_rating,
                    r.comment AS review_comment
             FROM transactions t
             LEFT JOIN listings l ON l.id = t.listing_id
             JOIN users buyer ON buyer.id = t.buyer_id
             JOIN users seller ON seller.id = t.seller_id
             LEFT JOIN reviews r ON r.transaction_id = t.id
             WHERE t.id = ?
               AND (t.buyer_id = ? OR t.seller_id = ?)
             LIMIT 1'
        );
        $stmt->execute([$transactionId, $userId, $userId]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function forUser(int $userId): array
    {
        $stmt = $this->db->prepare(
            'SELECT t.*, l.title AS listing_title,
                    buyer.name AS buyer_name,
                    seller.name AS seller_name,
                    r.id AS review_id, r.rating AS review_rating
             FROM transactions t
             LEFT JOIN listings l ON l.id = t.listing_id
             JOIN users buyer ON buyer.id = t.buyer_id
             JOIN users seller ON seller.id = t.seller_id
             LEFT JOIN reviews r ON r.transaction_id = t.id
             WHERE t.buyer_id = ? OR t.seller_id = ?
             ORDER BY t.created_at DESC'
        );
        $stmt->execute([$userId, $userId]);

        return $stmt->fetchAll();
    }

    public function sellerRating(int $sellerId): array
    {
        $stmt = $this->db->prepare(
            'SELECT COUNT(*) AS review_count,
                    COALESCE(AVG(rating), 0) AS average_rating
             FROM reviews
             WHERE reviewed_user_id = ?'
        );
        $stmt->execute([$sellerId]);
        $row = $stmt->fetch();

        return [
            'review_count' => (int) $row['review_count'],
            'average_rating' => round((float) $row['average_rating'], 2),
        ];
    }

    public function reviewsForSeller(int $sellerId, int $limit = 20): array
    {
        $limit = max(1, min(100, $limit));

        $stmt = $this->db->prepare(
            'SELECT r.id, r.rating, r.comment, r.created_at,
                    u.id AS reviewer_id, u.name AS reviewer_name,
                    u.avatar_path AS reviewer_avatar
             FROM reviews r
             JOIN users u ON u.id = r.reviewer_id
             WHERE r.reviewed_user_id = ?
             ORDER BY r.created_at DESC
             LIMIT ?'
        );
        $stmt->bindValue(1, $sellerId, PDO::PARAM_INT);
        $stmt->bindValue(2, $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function latestForConversation(int $conversationId, int $userId): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT t.*, l.title AS listing_title,
                    buyer.name AS buyer_name,
                    seller.name AS seller_name,
                    r.id AS review_id, r.rating AS review_rating,
                    r.comment AS review_comment
             FROM transactions t
             LEFT JOIN listings l ON l.id = t.listing_id
             JOIN users buyer ON buyer.id = t.buyer_id
             JOIN users seller ON seller.id = t.seller_id
             LEFT JOIN reviews r ON r.transaction_id = t.id
             WHERE t.conversation_id = ?
               AND (t.buyer_id = ? OR t.seller_id = ?)
             ORDER BY t.created_at DESC, t.id DESC
             LIMIT 1'
        );
        $stmt->execute([$conversationId, $userId, $userId]);
        $row = $stmt->fetch();

        return $row ?: null;
    }
}
