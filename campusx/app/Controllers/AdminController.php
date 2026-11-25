<?php
declare(strict_types=1);
namespace SdutSwap\Controllers;

use SdutSwap\Core\Auth;
use SdutSwap\Core\Audit;
use SdutSwap\Core\Upload;
use SdutSwap\Services\ReauthService;
use SdutSwap\Services\SessionService;
use SdutSwap\Repositories\AuditRepository;
use SdutSwap\Repositories\ChatRepository;
use SdutSwap\Repositories\ListingRepository;
use SdutSwap\Repositories\SupportRepository;
use SdutSwap\Repositories\UserRepository;
use SdutSwap\Repositories\VerificationRepository;

final class AdminController extends Controller
{
    public function showConfirm(): void
    {
        Auth::requireAdmin();
        $next = ReauthService::safeNext($this->input('next'));
        $this->view('admin/confirm', ['title' => 'Confirm your password', 'next' => $next]);
    }

    public function confirm(): void
    {
        Auth::requireAdmin();
        $this->requireCsrf();
        $this->rateLimit('admin_reauth', 5, 900, 900);
        $next = ReauthService::safeNext($this->input('next'));
        $password = (string) ($_POST['password'] ?? '');
        $user = Auth::user();
        if ($user === null || !password_verify($password, (string) $user['password_hash'])) {
            Audit::security('admin_reauth_failed', 'medium', 'Admin re-authentication failed');
            flash('error', 'Incorrect password.');
            redirect('/admin/confirm?next=' . rawurlencode($next));
        }
        ReauthService::mark($_SESSION, time());
        Audit::log('admin_reauth_success');
        redirect($next);
    }

    public function dashboard(): void
    {
        Auth::requireAdmin();
        $users = new UserRepository();
        $this->view('admin/dashboard', ['title' => 'Admin Dashboard', 'stats' => $users->stats()]);
    }

    public function users(): void
    {
        Auth::requireAdmin();
        $repo = new UserRepository();
        $page = max(1, (int) $this->input('page', '1'));
        $search = $this->input('q');
        $this->view('admin/users', ['title' => 'Manage Users', 'users' => $repo->listAll($page, 20, $search), 'page' => $page, 'q' => $search]);
    }

    public function suspendUser(int $id): void
    {
        Auth::requireAdmin();
        $this->requireCsrf();
        $repo = new UserRepository();
        $target = $repo->findById($id);
        if (!$target) abort(404, 'User not found');
        if ((int) $target['id'] === Auth::id()) abort(400, 'You cannot suspend yourself.');
        if (($target['role'] ?? '') === 'admin') abort(400, 'Cannot suspend another admin.');
        $reason = $this->input('reason');
        if (mb_strlen($reason) > 500) $reason = mb_substr($reason, 0, 500);
        $repo->suspend($id, $reason ?: null);
        Audit::log('user_suspended', 'user', $id, $reason);

        $revoked = SessionService::revokeAllForUser($id);
        if ($revoked === null) {
            // The account-level suspension is authoritative in Auth::user(), so
            // protected requests will still reject this user if session cleanup fails.
            error_log('User suspension completed but session revocation failed for user ' . $id);
            Audit::security(
                'session_revocation_failed',
                'high',
                'Session revocation failed after account suspension for user ' . $id
            );
            flash('error', 'User suspended, but we could not confirm that all devices were signed out. The account remains blocked. Please review the security events.');
            redirect('/admin/users');
        }

        flash('success', 'User suspended. ' . $revoked . ' active session(s) revoked.');
        redirect('/admin/users');
    }

    public function restoreUser(int $id): void
    {
        Auth::requireAdmin();
        $this->requireCsrf();
        (new UserRepository())->restore($id);
        Audit::log('user_restored', 'user', $id);
        flash('success', 'User restored.');
        redirect('/admin/users');
    }

    public function listings(): void
    {
        Auth::requireAdmin();
        $repo = new ListingRepository();
        $page = max(1, (int) $this->input('page', '1'));
        $status = $this->input('status');
        $this->view('admin/listings', ['title' => 'Manage Listings', 'listings' => $repo->listAllAdmin($page, 20, $status), 'page' => $page, 'status' => $status]);
    }

    public function moderateListing(int $id): void
    {
        Auth::requireAdmin();
        $this->requireCsrf();
        $action = $this->input('action');
        $reason = $this->input('reason');
        $repo = new ListingRepository();
        $listing = $repo->findById($id);
        if (!$listing) abort(404, 'Listing not found');
        if ($action === 'hide') { $repo->setStatus($id, 'hidden', $reason ?: null); Audit::log('listing_hidden', 'listing', $id, $reason); }
        elseif ($action === 'remove') { $repo->setStatus($id, 'removed', $reason ?: null); Audit::log('listing_removed', 'listing', $id, $reason); }
        elseif ($action === 'restore') { $repo->setStatus($id, 'active'); Audit::log('listing_restored', 'listing', $id); }
        elseif ($action === 'sold') { $repo->setStatus($id, 'sold'); Audit::log('listing_marked_sold', 'listing', $id); }
        else abort(400, 'Invalid action');
        flash('success', 'Listing updated.');
        redirect('/admin/listings');
    }

    public function verifications(): void
    {
        Auth::requireAdmin();
        $repo = new VerificationRepository();
        $page = max(1, (int) $this->input('page', '1'));
        $this->view('admin/verifications', ['title' => 'Verification Queue', 'queue' => $repo->pendingQueue($page, 20), 'page' => $page]);
    }

    public function decideVerification(int $id): void
    {
        Auth::requireAdmin();
        $this->requireCsrf();
        $decision = $this->input('decision');
        $reason = $this->input('reason');
        $repo = new VerificationRepository();
        $item = $repo->findById($id);
        if (!$item || ($item['status'] ?? '') !== 'pending') abort(404, 'Verification not found');
        if ($decision === 'approve') {
            $repo->decide($id, 'verified', Auth::id());
            (new UserRepository())->setVerified((int) $item['user_id'], true);
            Audit::log('verification_approved', 'verification', $id);
            flash('success', 'Student verified.');
        } elseif ($decision === 'reject') {
            if (mb_strlen($reason) < 5) {
                flash('error', 'Please provide a rejection reason (min 5 characters).');
                redirect('/admin/verifications');
            }
            $repo->decide($id, 'rejected', Auth::id(), mb_substr($reason, 0, 500));
            Audit::log('verification_rejected', 'verification', $id, $reason);
            flash('success', 'Verification rejected.');
        } else abort(400, 'Invalid decision');
        redirect('/admin/verifications');
    }

    public function viewCard(int $id): void
    {
        Auth::requireAdmin();
        $repo = new VerificationRepository();
        $item = $repo->findById($id);
        if (!$item) abort(404, 'Not found');
        $path = Upload::privateFullPath((string) $item['card_image_path']);
        if ($path === null) abort(404, 'File not found');
        Audit::log('verification_card_viewed', 'verification', $id);
        $mime = mime_content_type($path) ?: 'image/jpeg';
        header('Content-Type: ' . $mime);
        header('Content-Disposition: inline; filename="student-card"');
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: private, no-store');
        readfile($path);
        exit;
    }

    public function tickets(): void
    {
        Auth::requireAdmin();
        $repo = new SupportRepository();
        $page = max(1, (int) $this->input('page', '1'));
        $status = $this->input('status');
        $this->view('admin/tickets', ['title' => 'Support Tickets', 'tickets' => $repo->ticketQueue($status, $page, 20), 'page' => $page, 'status' => $status]);
    }

    public function ticketDetail(int $id): void
    {
        Auth::requireAdmin();
        $repo = new SupportRepository();
        $ticket = $repo->findTicket($id);
        if (!$ticket) abort(404, 'Ticket not found');

        $this->view('admin/ticket_detail', [
            'title' => 'Ticket #' . $id,
            'ticket' => $ticket,
            'messages' => $repo->ticketMessages($id),
            'activity' => $repo->ticketActivity($id),
        ]);
    }

    public function replyTicket(int $id): void
    {
        Auth::requireAdmin();
        $this->requireCsrf();
        $repo = new SupportRepository();
        $ticket = $repo->findTicket($id);
        if (!$ticket) abort(404, 'Ticket not found');
        $body = $this->input('body');
        if (mb_strlen($body) < 1 || mb_strlen($body) > 5000) {
            flash('error', 'Reply must be between 1 and 5000 characters.');
            redirect('/admin/tickets/' . $id);
        }
        $repo->reply($id, Auth::id(), $body);
        $repo->addTicketActivity($id, (int) Auth::id(), 'ticket_replied', null, 'Admin reply added');
        Audit::log('ticket_replied', 'ticket', $id);
        flash('success', 'Reply sent.');
        redirect('/admin/tickets/' . $id);
    }

    public function updateTicketStatus(int $id): void
    {
        Auth::requireAdmin();
        $this->requireCsrf();

        $repo = new SupportRepository();
        $ticket = $repo->findTicket($id);
        if (!$ticket) abort(404, 'Ticket not found');

        $status = $this->input('status');
        $allowed = ['open', 'in_review', 'waiting_for_user', 'resolved', 'closed'];

        if (!in_array($status, $allowed, true)) {
            abort(400, 'Invalid ticket status');
        }

        $oldStatus = (string) $ticket['status'];

        if ($oldStatus !== $status) {
            $repo->setTicketStatus($id, $status);
            $repo->addTicketActivity($id, (int) Auth::id(), 'status_changed', $oldStatus, $status);
            Audit::log('ticket_status_changed', 'ticket', $id, $oldStatus . ' -> ' . $status);
        }

        flash('success', 'Ticket status updated.');
        redirect('/admin/tickets/' . $id);
    }

    public function updateTicketPriority(int $id): void
    {
        Auth::requireAdmin();
        $this->requireCsrf();

        $repo = new SupportRepository();
        $ticket = $repo->findTicket($id);
        if (!$ticket) abort(404, 'Ticket not found');

        $priority = $this->input('priority');
        $allowed = ['low', 'normal', 'high', 'urgent'];

        if (!in_array($priority, $allowed, true)) {
            abort(400, 'Invalid ticket priority');
        }

        $oldPriority = (string) $ticket['priority'];

        if ($oldPriority !== $priority) {
            $repo->setTicketPriority($id, $priority);
            $repo->addTicketActivity($id, (int) Auth::id(), 'priority_changed', $oldPriority, $priority);
            Audit::log('ticket_priority_changed', 'ticket', $id, $oldPriority . ' -> ' . $priority);
        }

        flash('success', 'Ticket priority updated.');
        redirect('/admin/tickets/' . $id);
    }

    public function reports(): void
    {
        Auth::requireAdmin();
        $repo = new SupportRepository();
        $page = max(1, (int) $this->input('page', '1'));
        $status = $this->input('status');
        $this->view('admin/reports', ['title' => 'Reports Queue', 'reports' => $repo->reportQueue($status, $page, 20), 'page' => $page, 'status' => $status]);
    }

    public function reportDetail(int $id): void
    {
        Auth::requireAdmin();
        $repo = new SupportRepository();
        $report = $repo->findReport($id);
        if (!$report) abort(404, 'Report not found');
        // Purpose-limited access to private chats: staff can read a conversation only through a report
        // about it, only if the reporter really took part in it, and every view is written to the audit log.
        $transcript = null;
        $chat = new ChatRepository();
        $convId = null;
        $highlight = null;
        if ($report['target_type'] === 'conversation') {
            $convId = (int) $report['target_id'];
        } elseif ($report['target_type'] === 'message') {
            $msg = $chat->findMessage((int) $report['target_id']);
            if ($msg !== null) {
                $convId = (int) $msg['conversation_id'];
                $highlight = (int) $msg['id'];
            }
        }
        if ($convId !== null && $chat->isParticipant($convId, (int) $report['reporter_id'])) {
            $transcript = ['conversation_id' => $convId, 'messages' => $chat->messages($convId, null, 200), 'highlight' => $highlight];
            Audit::log('reported_conversation_viewed', 'report', $id, "conversation {$convId}");
        }
        $this->view('admin/report_detail', ['title' => 'Report #' . $id, 'report' => $report, 'evidence' => $repo->reportEvidence($id), 'transcript' => $transcript]);
    }

    public function evidence(int $reportId, int $evidenceId): void
    {
        Auth::requireAdmin();
        $ev = (new SupportRepository())->findEvidence($evidenceId);
        if ($ev === null || (int) $ev['report_id'] !== $reportId) abort(404, 'File not found');
        $path = Upload::privateFullPath((string) $ev['file_path']);
        if ($path === null) abort(404, 'File not found (it may have been deleted under the retention policy)');
        Audit::log('report_evidence_viewed', 'report', $reportId);
        $this->sendPrivateImage($path);
    }

    public function decideReport(int $id): void
    {
        Auth::requireAdmin();
        $this->requireCsrf();
        $status = $this->input('status');
        $notes = $this->input('notes');
        if (!in_array($status, ['open', 'under_review', 'resolved', 'dismissed'], true)) abort(400, 'Invalid status');
        (new SupportRepository())->setReportStatus($id, $status, $notes ?: null);
        Audit::log('report_status_' . $status, 'report', $id, $notes);
        flash('success', 'Report updated.');
        redirect('/admin/reports');
    }

    public function audit(): void
    {
        Auth::requireAdmin();
        $repo = new AuditRepository();
        $page = max(1, (int) $this->input('page', '1'));
        $this->view('admin/audit', ['title' => 'Audit Log', 'logs' => $repo->auditLog($page, 30), 'page' => $page]);
    }

    public function securityEvents(): void
    {
        Auth::requireAdmin();
        $repo = new AuditRepository();
        $page = max(1, (int) $this->input('page', '1'));
        $severity = $this->input('severity');
        $this->view('admin/security', ['title' => 'Security Events', 'events' => $repo->securityEvents($page, 30, $severity), 'page' => $page, 'severity' => $severity]);
    }
}
