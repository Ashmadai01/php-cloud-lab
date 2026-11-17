<?php
declare(strict_types=1);
// SDUT Swap — enforce the retention policy for private files. Usage:
//   php tools/purge_private_files.php            (delete what is due)
//   php tools/purge_private_files.php --dry-run  (only report what would be deleted)
// Schedule it daily (cron on Linux, Task Scheduler on Windows/XAMPP).
// Windows example: C:\xampp\php\php.exe C:\xampp\htdocs\campusx\tools\purge_private_files.php

define('SDUTSWAP_NO_DISPATCH', true);
require dirname(__DIR__) . '/app/bootstrap.php';

use SdutSwap\Core\Config;
use SdutSwap\Repositories\SessionRepository;
use SdutSwap\Repositories\SupportRepository;
use SdutSwap\Repositories\VerificationRepository;
use SdutSwap\Services\RetentionService;

if (PHP_SAPI !== 'cli') { fwrite(STDERR, "CLI only.\n"); exit(1); }

$dry = in_array('--dry-run', $argv ?? [], true);
$cardDays = Config::int('CARD_RETENTION_DAYS', 30);
$evidenceDays = Config::int('EVIDENCE_RETENTION_DAYS', 180);

try {
    $service = new RetentionService(new VerificationRepository(), new SupportRepository(), new SessionRepository());
    $r = $service->run(time(), $cardDays, $evidenceDays, $dry);
} catch (Throwable $e) {
    fwrite(STDERR, 'Retention run failed: ' . $e->getMessage() . "\n");
    exit(1);
}

$verb = $dry ? 'would delete' : 'deleted';
echo "Retention run" . ($dry ? ' (dry run)' : '') . ":\n";
echo "  Student Card images {$verb}: {$r['cards']} (older than {$cardDays} days after review)\n";
echo "  Ticket attachments {$verb}: {$r['attachments']} (closed tickets older than {$evidenceDays} days)\n";
echo "  Report evidence {$verb}: {$r['evidence']} (closed reports older than {$evidenceDays} days)\n";
if (!$dry) echo "  Old session records removed: {$r['sessions']}\n";
