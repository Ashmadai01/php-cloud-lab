<?php
declare(strict_types=1);
namespace SdutSwap\Services;

use SdutSwap\Core\Upload;
use SdutSwap\Repositories\SessionRepository;
use SdutSwap\Repositories\SupportRepository;
use SdutSwap\Repositories\VerificationRepository;

/**
 * Enforces the data-retention policy for sensitive private files:
 *  - Student Card images: deleted CARD_RETENTION_DAYS after the review decision
 *  - Support attachments / report evidence: deleted EVIDENCE_RETENTION_DAYS after the ticket/report was closed
 * Database rows are kept (they hold the audit trail); only the files are removed. Safe to run repeatedly.
 */
final class RetentionService
{
    public function __construct(
        private VerificationRepository $verifications,
        private SupportRepository $support,
        private SessionRepository $sessions,
    ) {
    }

    /** @return array{cards:int, attachments:int, evidence:int, sessions:int} */
    public function run(int $nowTs, int $cardDays, int $evidenceDays, bool $dryRun = false): array
    {
        $cardDays = max(1, $cardDays);
        $evidenceDays = max(1, $evidenceDays);
        $cardCutoff = gmdate('Y-m-d H:i:s', $nowTs - $cardDays * 86400);
        $evidenceCutoff = gmdate('Y-m-d H:i:s', $nowTs - $evidenceDays * 86400);
        $out = ['cards' => 0, 'attachments' => 0, 'evidence' => 0, 'sessions' => 0];

        foreach ($this->verifications->purgeCandidates($cardCutoff) as $row) {
            if (!$dryRun) {
                Upload::deletePrivate((string) $row['card_image_path']);
                $this->verifications->markCardPurged((int) $row['id']);
            }
            $out['cards']++;
        }
        foreach ($this->support->purgeableAttachments($evidenceCutoff) as $row) {
            if (!$dryRun) {
                Upload::deletePrivate((string) $row['file_path']);
                $this->support->deleteAttachmentRow((int) $row['id']);
            }
            $out['attachments']++;
        }
        foreach ($this->support->purgeableEvidence($evidenceCutoff) as $row) {
            if (!$dryRun) {
                Upload::deletePrivate((string) $row['file_path']);
                $this->support->deleteEvidenceRow((int) $row['id']);
            }
            $out['evidence']++;
        }
        if (!$dryRun) {
            $out['sessions'] = $this->sessions->purgeOld(gmdate('Y-m-d H:i:s', $nowTs - 7 * 86400));
        }
        return $out;
    }
}
