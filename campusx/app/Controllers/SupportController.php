<?php
declare(strict_types=1);
namespace SdutSwap\Controllers;

use SdutSwap\Core\Auth;
use SdutSwap\Core\Config;
use SdutSwap\Core\Upload;
use SdutSwap\Core\Validator;
use SdutSwap\Core\Audit;
use SdutSwap\Repositories\ChatRepository;
use SdutSwap\Repositories\ListingRepository;
use SdutSwap\Repositories\SupportRepository;
use SdutSwap\Repositories\UserRepository;

final class SupportController extends Controller
{
    public function faq(): void
    {
        $this->view('support/faq', ['title' => 'Help & FAQ']);
    }

    public function myTickets(): void
    {
        Auth::requireLogin();
        $repo = new SupportRepository();
        $this->view('support/my_tickets', [
            'title' => 'My Support Tickets',
            'tickets' => $repo->ticketsForUser(Auth::id()),
            'reports' => $repo->reportsForUser(Auth::id()),
        ]);
    }

    public function showTicket(int $id): void
    {
        Auth::requireLogin();
        $repo = new SupportRepository();
        $ticket = $repo->findTicket($id);
        if (!$ticket) abort(404, 'Ticket not found');
        if ((int) $ticket['user_id'] !== Auth::id() && !Auth::isAdmin()) {
            \SdutSwap\Core\Audit::security('ticket_idor_attempt', 'high', "User attempted ticket {$id}");
            abort(403, 'Forbidden');
        }
        $this->view('support/ticket_show', ['title' => 'Ticket #' . $id, 'ticket' => $ticket, 'messages' => $repo->ticketMessages($id)]);
    }

    public function createTicket(): void
    {
        Auth::requireLogin();
        $this->view('support/new_ticket', ['title' => 'Contact Support']);
    }

    public function storeTicket(): void
    {
        Auth::requireLogin();
        $this->requireCsrf();
        $this->rateLimit('ticket_create', 10, 3600, 3600);
        $v = new Validator();
        $type = $this->input('ticket_type', 'help');
        $category = $this->input('category');
        $subject = $this->input('subject');
        $priority = $this->input('priority', 'normal');
        $body = $this->input('body');
        $v->inList('ticket_type', $type, ['report', 'help']);
        $v->required('category', $category, 2, 80)->required('subject', $subject, 5, 200)->required('body', $body, 10, 5000);
        $v->inList('priority', $priority, ['low', 'normal', 'high']);
        if ($v->fails()) {
            $this->view('support/new_ticket', ['title' => 'Contact Support', 'errors' => $v->errors()], 422);
        }
        $files = Upload::storePrivateImages($_FILES['attachments'] ?? null, 'support', Config::int('UPLOAD_MAX_SIZE', 5242880), 3);
        $repo = new SupportRepository();
        $id = $repo->createTicket(Auth::id(), $type, $category, $subject, $priority, $body, $files['paths']);
        if ($files['errors'] !== []) {
            flash('error', 'Some attachments were not saved: ' . implode(' ', $files['errors']));
        }
        flash('success', "Ticket #{$id} submitted. We will respond within 2 business days.");
        redirect('/support/tickets/' . $id);
    }

    public function replyTicket(int $id): void
    {
        Auth::requireLogin();
        $this->requireCsrf();

        $isAjax = str_contains(
            strtolower($_SERVER['HTTP_ACCEPT'] ?? ''),
            'application/json'
        );

        $repo = new SupportRepository();
        $ticket = $repo->findTicket($id);

        if (!$ticket) abort(404, 'Ticket not found');
        if ((int) $ticket['user_id'] !== Auth::id() && !Auth::isAdmin()) {
            abort(403, 'Forbidden');
        }

        $body = $this->input('body');

        if (mb_strlen($body) < 1 || mb_strlen($body) > 5000) {
            if ($isAjax) {
                $this->json(['ok' => false, 'error' => 'Reply must be between 1 and 5000 characters.'], 422);
            }

            flash('error', 'Reply must be between 1 and 5000 characters.');
            redirect('/support/tickets/' . $id);
        }

        $files = Upload::storePrivateImages(
            $_FILES['attachments'] ?? null,
            'support',
            Config::int('UPLOAD_MAX_SIZE', 5242880),
            3
        );

        $repo->reply($id, Auth::id(), $body, $files['paths']);
        $repo->addTicketActivity($id, (int) Auth::id(), 'ticket_replied', null, 'Reply added');

        if (!Auth::isAdmin() && $ticket['status'] !== 'open') {
            $repo->setTicketStatus($id, 'open');
            $repo->addTicketActivity($id, (int) Auth::id(), 'status_changed', (string) $ticket['status'], 'open');
        }

        if ($isAjax) {
            $messages = $repo->ticketMessages($id);
            $message = end($messages);

            $this->json([
                'ok' => true,
                'message' => $message,
                'attachmentErrors' => $files['errors'],
            ]);
        }

        if ($files['errors'] !== []) {
            flash('error', 'Some attachments were not saved: ' . implode(' ', $files['errors']));
        }

        flash('success', 'Reply sent.');
        redirect('/support/tickets/' . $id);
    }

    public function reportForm(string $targetType, int $targetId): void
    {
        Auth::requireLogin();
        if (!in_array($targetType, ['user', 'listing', 'message', 'conversation'], true)) abort(400, 'Invalid report target');
        $this->assertReportable($targetType, $targetId);
        $this->view('support/report', ['title' => 'Report', 'targetType' => $targetType, 'targetId' => $targetId]);
    }

    public function storeReport(): void
    {
        Auth::requireLogin();
        $this->requireCsrf();
        $this->rateLimit('report_create', 10, 3600, 3600);
        $targetType = $this->input('target_type');
        $targetId = (int) $this->input('target_id', '0');
        $reportType = $this->input('report_type');
        $description = $this->input('description');
        $v = new Validator();
        $v->inList('target_type', $targetType, ['user', 'listing', 'message', 'conversation']);
        $v->required('report_type', $reportType, 2, 80)->required('description', $description, 10, 5000);
        if ($targetId <= 0) $v->add('target_id', 'Invalid target.');
        if ($v->fails()) {
            $this->view('support/report', ['title' => 'Report', 'targetType' => $targetType, 'targetId' => $targetId, 'errors' => $v->errors()], 422);
        }
        // The target must exist, and message/conversation reports may only come from a participant.
        $this->assertReportable($targetType, $targetId);
        $files = Upload::storePrivateImages($_FILES['evidence'] ?? null, 'evidence', Config::int('UPLOAD_MAX_SIZE', 5242880), 3);
        $repo = new SupportRepository();
        $id = $repo->createReport(Auth::id(), $targetType, $targetId, $reportType, $description, $files['paths']);
        if ($files['errors'] !== []) {
            flash('error', 'Some evidence files were not saved: ' . implode(' ', $files['errors']));
        }
        \SdutSwap\Core\Audit::security('report_submitted', 'low', "Report {$id} submitted ({$targetType} {$targetId})");
        flash('success', "Report #{$id} submitted. Thank you for keeping SDUT Swap safe.");
        redirect('/support/my');
    }

    /** 404 unless the report target exists and the reporter is allowed to report it. */
    private function assertReportable(string $type, int $id): void
    {
        $me = (int) Auth::id();
        $ok = false;
        if ($type === 'user') {
            $ok = $id !== $me && (new UserRepository())->findById($id) !== null;
        } elseif ($type === 'listing') {
            $ok = (new ListingRepository())->findById($id) !== null;
        } elseif ($type === 'conversation') {
            $ok = (new ChatRepository())->isParticipant($id, $me);
        } elseif ($type === 'message') {
            $chat = new ChatRepository();
            $msg = $chat->findMessage($id);
            $ok = $msg !== null && $chat->isParticipant((int) $msg['conversation_id'], $me) && (int) $msg['sender_id'] !== $me;
        }
        if (!$ok) {
            // Same 404 whether it does not exist or is not the reporter's to report: no existence leak.
            abort(404, 'Nothing to report here');
        }
    }

    /** Serves a ticket attachment to the ticket's owner or to an admin. Never publicly reachable. */
    public function attachment(int $id): void
    {
        Auth::requireLogin();
        $att = (new SupportRepository())->findAttachment($id);
        if ($att === null) abort(404, 'File not found');
        if ((int) $att['owner_id'] !== (int) Auth::id() && !Auth::isAdmin()) {
            Audit::security('attachment_idor_attempt', 'high', "User attempted attachment {$id}");
            abort(403, 'Forbidden');
        }
        $path = Upload::privateFullPath((string) $att['file_path']);
        if ($path === null) abort(404, 'File not found (it may have been deleted under the retention policy)');
        if (Auth::isAdmin() && (int) $att['owner_id'] !== (int) Auth::id()) {
            Audit::log('ticket_attachment_viewed', 'ticket', (int) $att['ticket_id']);
        }
        $this->sendPrivateImage($path);
    }
}
