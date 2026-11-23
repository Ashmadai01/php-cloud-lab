<?php
$ticketCount = count($tickets ?? []);
$reportCount = count($reports ?? []);
$openCount = count(array_filter($tickets ?? [], static fn($t) => in_array($t['status'] ?? '', ['open', 'in_progress', 'pending'], true)));
$resolvedCount = count(array_filter($tickets ?? [], static fn($t) => in_array($t['status'] ?? '', ['resolved', 'closed'], true)));
?>
<div class="container support-center support-requests-page">
  <header class="my-support-hero">
    <div class="my-support-heading-copy">
      <span class="support-eyebrow">SDUT SWAP · SUPPORT PORTAL</span>
      <h1>Your support, all in one place.</h1>
      <p>Track conversations, follow up on requests, and stay updated on reports you've submitted.</p>
      <a class="btn btn-primary" href="<?= url('/support/new') ?>">＋ Create a ticket</a>
    </div>
    <div class="my-support-hero-art" aria-hidden="true"><span>✦</span><i>?</i></div>
  </header>

  <section class="my-support-stats" aria-label="Support overview">
    <article class="my-support-stat"><span class="my-support-stat-icon">▤</span><div><small>Total tickets</small><strong><?= $ticketCount ?></strong></div></article>
    <article class="my-support-stat"><span class="my-support-stat-icon is-open">◷</span><div><small>Awaiting attention</small><strong><?= $openCount ?></strong></div></article>
    <article class="my-support-stat"><span class="my-support-stat-icon is-done">✓</span><div><small>Resolved / closed</small><strong><?= $resolvedCount ?></strong></div></article>
    <article class="my-support-stat"><span class="my-support-stat-icon is-report">⚑</span><div><small>Reports submitted</small><strong><?= $reportCount ?></strong></div></article>
  </section>

  <section class="my-support-workspace">
    <div class="my-support-toolbar">
      <div><span class="support-eyebrow">YOUR ACTIVITY</span><h2>Support requests</h2><p>Everything you've shared with our team.</p></div>
      <label class="my-support-search"><span aria-hidden="true">⌕</span><input type="search" id="supportRequestSearch" placeholder="Search your requests…" aria-label="Search support requests"></label>
    </div>

    <div class="my-support-tabs" role="tablist" aria-label="Request type">
      <button type="button" class="my-support-tab is-active" data-support-tab="tickets" role="tab" aria-selected="true">Tickets <span><?= $ticketCount ?></span></button>
      <button type="button" class="my-support-tab" data-support-tab="reports" role="tab" aria-selected="false">Reports <span><?= $reportCount ?></span></button>
    </div>

    <div class="my-support-pane is-active" data-support-pane="tickets">
      <?php if (!empty($tickets)): ?>
        <div class="my-support-list">
          <?php foreach ($tickets as $t): ?>
            <a class="my-support-row" data-support-item data-search="<?= e(strtolower(($t['subject'] ?? '') . ' ' . ($t['category'] ?? '') . ' ' . ($t['ticket_type'] ?? '') . ' ' . ($t['status'] ?? '') . ' #' . $t['id'])) ?>" href="<?= url('/support/tickets/' . (int) $t['id']) ?>">
              <span class="my-support-row-icon">✉</span>
              <span class="my-support-row-content"><strong><?= e($t['subject']) ?></strong><small>Ticket #<?= (int) $t['id'] ?> <i>·</i> <?= e(ucfirst($t['ticket_type'])) ?> <i>·</i> <?= e($t['category']) ?></small></span>
              <span class="my-support-row-status"><span class="badge badge-<?= e($t['status']) ?>"><?= e(str_replace('_', ' ', ucfirst($t['status']))) ?></span><small>Updated <?= e(timeAgo($t['updated_at'])) ?></small></span>
              <span class="my-support-row-arrow" aria-hidden="true">→</span>
            </a>
          <?php endforeach; ?>
        </div>
        <div class="my-support-no-results" data-support-no-results hidden><strong>No matching tickets</strong><span>Try another search term.</span></div>
      <?php else: ?>
        <div class="my-support-empty"><span class="my-support-empty-icon">✉</span><h3>No tickets yet</h3><p>When you contact support, your requests and replies will appear here.</p><a class="btn btn-primary" href="<?= url('/support/new') ?>">Create your first ticket</a></div>
      <?php endif; ?>
    </div>

    <div class="my-support-pane" data-support-pane="reports" hidden>
      <?php if (!empty($reports)): ?>
        <div class="my-support-list">
          <?php foreach ($reports as $r): ?>
            <div class="my-support-row my-support-report-row" data-support-item data-search="<?= e(strtolower(($r['target_type'] ?? '') . ' ' . ($r['report_type'] ?? '') . ' ' . ($r['status'] ?? '') . ' #' . $r['target_id'])) ?>">
              <span class="my-support-row-icon is-report">⚑</span>
              <span class="my-support-row-content"><strong><?= e(ucfirst($r['target_type'])) ?> #<?= (int) $r['target_id'] ?></strong><small><?= e($r['report_type']) ?> <i>·</i> Submitted <?= e(timeAgo($r['created_at'])) ?></small></span>
              <span class="my-support-row-status"><span class="badge badge-<?= e($r['status']) ?>"><?= e(str_replace('_', ' ', ucfirst($r['status']))) ?></span><small>Report #<?= (int) $r['id'] ?></small></span>
            </div>
          <?php endforeach; ?>
        </div>
        <div class="my-support-no-results" data-support-no-results hidden><strong>No matching reports</strong><span>Try another search term.</span></div>
      <?php else: ?>
        <div class="my-support-empty"><span class="my-support-empty-icon is-report">⚑</span><h3>No reports submitted</h3><p>Reports you submit to help keep SDUT Swap safe will be listed here.</p></div>
      <?php endif; ?>
    </div>
  </section>
  <p class="my-support-footnote">Need more help? <a href="<?= url('/support/new') ?>">Contact our support team</a>. We aim to respond within 2 business days.</p>
</div>
