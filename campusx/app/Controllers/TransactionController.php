<?php
declare(strict_types=1);

namespace SdutSwap\Controllers;

use SdutSwap\Core\Auth;
use SdutSwap\Repositories\TransactionRepository;
use SdutSwap\Repositories\NotificationRepository;

final class TransactionController extends Controller
{
    public function request(int $conversationId): void
    {
        Auth::requireLogin();
        $this->requireCsrf();

        try {
            $repo = new TransactionRepository();
            $transactionId = $repo->createRequest($conversationId, (int) Auth::id());
            $transaction = $repo->findForUser($transactionId, (int) Auth::id());

            if ($transaction) {
                $this->notify(
                    (int) $transaction['seller_id'],
                    'purchase_request',
                    'You have received a new purchase request for "' . ($transaction['listing_title'] ?? 'your listing') . '".',
                    $conversationId
                );
            }

            flash('success', 'Purchase request sent successfully.');
        } catch (\DomainException $e) {
            flash('error', $e->getMessage());
        } catch (\Throwable $e) {
            flash('error', 'Unable to send the purchase request. Please try again.');
        }

        redirect('/messages/' . $conversationId);
    }

    public function decide(int $transactionId): void
    {
        Auth::requireLogin();
        $this->requireCsrf();

        $repo = new TransactionRepository();
        $transaction = $repo->findForUser($transactionId, (int) Auth::id());

        if (!$transaction) {
            abort(404, 'Transaction not found');
        }

        $conversationId = (int) $transaction['conversation_id'];

        if ((int) $transaction['seller_id'] !== (int) Auth::id()) {
            abort(403, 'Forbidden');
        }

        $decision = $this->input('decision');

        if (!in_array($decision, ['accept', 'reject'], true)) {
            flash('error', 'Invalid purchase decision.');
            redirect('/messages/' . $conversationId);
        }

        try {
            $repo->decideRequest(
                $transactionId,
                (int) Auth::id(),
                $decision === 'accept'
            );

            $this->notify(
                (int) $transaction['buyer_id'],
                $decision === 'accept' ? 'purchase_accepted' : 'purchase_rejected',
                $decision === 'accept'
                    ? 'Your purchase request for "' . ($transaction['listing_title'] ?? 'a listing') . '" was accepted.'
                    : 'Your purchase request for "' . ($transaction['listing_title'] ?? 'a listing') . '" was rejected.',
                $conversationId
            );

            flash('success', $decision === 'accept'
                ? 'Purchase request accepted.'
                : 'Purchase request rejected.');
        } catch (\DomainException $e) {
            flash('error', $e->getMessage());
        } catch (\Throwable $e) {
            flash('error', 'Unable to process this purchase request.');
        }

        redirect('/messages/' . $conversationId);
    }

    public function confirm(int $transactionId): void
    {
        Auth::requireLogin();
        $this->requireCsrf();

        $repo = new TransactionRepository();
        $transaction = $repo->findForUser($transactionId, (int) Auth::id());

        if (!$transaction) {
            abort(404, 'Transaction not found');
        }

        $conversationId = (int) $transaction['conversation_id'];

        if ((int) $transaction['buyer_id'] !== (int) Auth::id()) {
            abort(403, 'Forbidden');
        }

        try {
            $repo->confirmReceipt($transactionId, (int) Auth::id());

            $this->notify(
                (int) $transaction['seller_id'],
                'purchase_completed',
                'The buyer confirmed receipt of "' . ($transaction['listing_title'] ?? 'your listing') . '".',
                $conversationId
            );

            flash('success', 'Receipt confirmed. The listing is now marked as sold.');
        } catch (\DomainException $e) {
            flash('error', $e->getMessage());
        } catch (\Throwable $e) {
            flash('error', 'Unable to confirm receipt. Please try again.');
        }

        redirect('/messages/' . $conversationId);
    }

    public function review(int $transactionId): void
    {
        Auth::requireLogin();
        $this->requireCsrf();

        $repo = new TransactionRepository();
        $transaction = $repo->findForUser($transactionId, (int) Auth::id());

        if (!$transaction) {
            abort(404, 'Transaction not found');
        }

        $conversationId = (int) $transaction['conversation_id'];

        if ((int) $transaction['buyer_id'] !== (int) Auth::id()) {
            abort(403, 'Forbidden');
        }

        $rating = filter_var(
            $this->input('rating'),
            FILTER_VALIDATE_INT
        );

        $comment = $this->input('comment');

        if ($rating === false || $rating < 1 || $rating > 5) {
            flash('error', 'Please select a rating from 1 to 5.');
            redirect('/messages/' . $conversationId);
        }

        try {
            $repo->createReview(
                $transactionId,
                (int) Auth::id(),
                $rating,
                $comment !== '' ? $comment : null
            );

            flash('success', 'Your review has been submitted.');
        } catch (\DomainException $e) {
            flash('error', $e->getMessage());
        } catch (\Throwable $e) {
            flash('error', 'Unable to submit your review. Please try again.');
        }

        redirect('/messages/' . $conversationId);
    }

    private function notify(
        int $userId,
        string $type,
        string $message,
        int $conversationId
    ): void {
        try {
            (new NotificationRepository())->create(
                $userId,
                null,
                $type,
                $message,
                $conversationId
            );
        } catch (\Throwable $e) {
            error_log('Transaction notification failed: ' . $e->getMessage());
        }
    }

}
