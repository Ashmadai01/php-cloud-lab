<?php
declare(strict_types=1);
namespace SdutSwap\Controllers;

use SdutSwap\Core\Auth;
use SdutSwap\Core\Config;
use SdutSwap\Core\Upload;
use SdutSwap\Core\Validator;
use SdutSwap\Repositories\UserRepository;
use SdutSwap\Repositories\VerificationRepository;
use SdutSwap\Services\VerificationService;

final class VerificationController extends Controller
{
    public function show(): void
    {
        Auth::requireLogin();
        $repo = new VerificationRepository();
        $this->view('verification/show', ['title' => 'Verify student identity', 'verification' => $repo->latestForUser(Auth::id())]);
    }

    public function submit(): void
    {
        Auth::requireLogin();
        $this->requireCsrf();
        $this->rateLimit('verification_submit', 3, 86400, 86400);
        $repo = new VerificationRepository();
        $existing = $repo->latestForUser(Auth::id());
        if ($existing && ($existing['status'] ?? '') === 'pending') {
            flash('error', 'You already have a pending verification. Please wait for review.');
            redirect('/verify');
        }
        $v = new Validator();
        $fullName = $this->input('full_name');
        $studentNumber = $this->input('student_number');
        $university = $this->input('university');
        $v->required('full_name', $fullName, 2, 100)->required('university', $university, 2, 150);
        $v->regex('student_number', $studentNumber, '/^[A-Za-z0-9\-]{4,30}$/', 'Student number must be 4–30 letters, numbers, or dashes.');
        // Dashes are ignored when comparing numbers, so at least 4 letters/digits must remain.
        if (strlen(VerificationRepository::normalizeStudentNumber($studentNumber)) < 4) {
            $v->add('student_number', 'Student number must contain at least 4 letters or digits.');
        }
        if ($v->fails()) {
            $this->view('verification/show', ['title' => 'Verify student identity', 'verification' => $existing, 'errors' => $v->errors()], 422);
        }
        $service = new VerificationService($repo, new UserRepository());
        $pre = $service->precheck((int) Auth::id(), $studentNumber);
        if ($pre === VerificationService::PENDING) {
            flash('error', 'You already have a pending verification. Please wait for review.');
            redirect('/verify');
        }
        if ($pre === VerificationService::ALREADY_VERIFIED) {
            flash('info', 'Your student identity is already verified.');
            redirect('/verify');
        }
        if ($pre === VerificationService::DUPLICATE_NUMBER) {
            \SdutSwap\Core\Audit::security('duplicate_student_number', 'medium', 'Student number already in use by another account');
            $this->view('verification/show', ['title' => 'Verify student identity', 'verification' => $existing, 'errors' => ['student_number' => [VerificationService::duplicateMessage()]]], 422);
        }
        if (empty($_FILES['card']) || ($_FILES['card']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            $this->view('verification/show', ['title' => 'Verify student identity', 'verification' => $existing, 'errors' => ['card' => ['Please upload a photo/scan of your Student Card.']]], 422);
        }
        $max = Config::int('UPLOAD_MAX_SIZE', 5242880);
        $res = Upload::storePrivateImage($_FILES['card'], 'cards', $max);
        if (!$res['ok']) {
            $this->view('verification/show', ['title' => 'Verify student identity', 'verification' => $existing, 'errors' => ['card' => [(string) $res['error']]]], 422);
        }
        if ($service->create((int) Auth::id(), $fullName, $studentNumber, $university, (string) $res['path']) === VerificationService::DUPLICATE_NUMBER) {
            // Lost a race with another account using the same number: do not keep the card we just stored.
            Upload::deletePrivate((string) $res['path']);
            \SdutSwap\Core\Audit::security('duplicate_student_number', 'medium', 'Student number already in use by another account (race)');
            $this->view('verification/show', ['title' => 'Verify student identity', 'verification' => $existing, 'errors' => ['student_number' => [VerificationService::duplicateMessage()]]], 422);
        }
        \SdutSwap\Core\Audit::security('verification_submitted', 'low', 'Student verification submitted');
        flash('success', 'Verification submitted! Our team will review your Student Card within 2 business days.');
        redirect('/verify');
    }
}
