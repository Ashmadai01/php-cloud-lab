<?php
declare(strict_types=1);
namespace SdutSwap\Services;

use SdutSwap\Repositories\UserRepository;
use SdutSwap\Repositories\VerificationRepository;

/** Business rules for Student Card verification submissions. */
final class VerificationService
{
    public const OK = 'ok';
    public const PENDING = 'pending';
    public const ALREADY_VERIFIED = 'already_verified';
    public const DUPLICATE_NUMBER = 'duplicate_number';

    public function __construct(private VerificationRepository $repo, private UserRepository $users)
    {
    }

    /** Cheap checks to run BEFORE accepting an upload, so we never store a card we will refuse. */
    public function precheck(int $userId, string $studentNumber): string
    {
        $latest = $this->repo->latestForUser($userId);
        if ($latest !== null && ($latest['status'] ?? '') === 'pending') {
            return self::PENDING;
        }
        $user = $this->users->findById($userId);
        if ($user !== null && (($user['verification_status'] ?? '') === 'verified' || !empty($user['is_verified']))) {
            return self::ALREADY_VERIFIED;
        }
        if ($this->repo->studentNumberInUse($studentNumber, $userId)) {
            return self::DUPLICATE_NUMBER;
        }
        return self::OK;
    }

    /** Creates the submission. The database's unique index settles any race between two users. */
    public function create(int $userId, string $fullName, string $studentNumber, string $university, string $cardPath): string
    {
        try {
            $this->repo->create($userId, $fullName, $studentNumber, $university, $cardPath);
            return self::OK;
        } catch (\PDOException $e) {
            if ((string) $e->getCode() === '23000') {
                return self::DUPLICATE_NUMBER;
            }
            throw $e;
        }
    }

    public static function duplicateMessage(): string
    {
        return 'This student number has already been submitted for another account. If you think this is a mistake, please contact support.';
    }
}
