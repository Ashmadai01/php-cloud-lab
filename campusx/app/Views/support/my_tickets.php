<div class="container support-center support-requests-page">
  <header class="support-page-heading">
    <div><span class="support-eyebrow">YOUR SUPPORT SPACE</span><h1>My Support</h1><p>Keep track of your conversations with the SDUT Swap team.</p></div>
    <a class="btn btn-primary" href="<?= url('/support/new') ?>">＋ New ticket</a>
  </header>

  <section class="support-request-section">
    <div class="support-section-heading"><div><h2>Support tickets</h2><p>Questions and issues you've sent to our team.</p></div><span class="support-total"><?= count($tickets) ?> total</span></div>
    <?php if (!empty($tickets)): ?>
      <div class="support-ticket-list">
        <?php foreach ($tickets as $t): ?>
          <a class="support-ticket-card" href="<?= url('/support/tickets/' . (int) $t['id']) ?>">
            <span class="support-ticket-symbol">✉</span>
            <span class="support-ticket-main"><strong><?= e($t['subject']) ?></strong><small>Ticket #<?= (int) $t['id'] ?> · <?= e(ucfirst($t['ticket_type'])) ?> · <?= e($t['category']) ?></small></span>
            <span class="support-ticket-meta"><span class="badge badge-<?= e($t['status']) ?>"><?= e(str_replace('_', ' ', ucfirst($t['status']))) ?></span><small><?= e(timeAgo($t['updated_at'])) ?></small></span>
            <span class="support-ticket-arrow" aria-hidden="true">→</span>
          </a>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <div class="support-empty"><span aria-hidden="true">✉</span><h3>No support tickets yet</h3><p>If you need help, create a ticket and our team will follow up.</p><a class="btn btn-primary" href="<?= url('/support/new') ?>">Create your first ticket</a></div>
    <?php endif; ?>
  </section>

  <section class="support-request-section support-reports-section">
    <div class="support-section-heading"><div><h2>Reports you've submitted</h2><p>Updates on items, users, or conversations you've reported.</p></div><span class="support-total"><?= count($reports) ?> total</span></div>
    <?php if (!empty($reports)): ?>
      <div class="support-report-list">
        <?php foreach ($reports as $r): ?>
          <div class="support-report-card"><span class="support-report-symbol">⚑</span><span class="support-ticket-main"><strong><?= e(ucfirst($r['target_type'])) ?> #<?= (int) $r['target_id'] ?></strong><small><?= e($r['report_type']) ?> · Submitted <?= e(timeAgo($r['created_at'])) ?></small></span><span class="badge badge-<?= e($r['status']) ?>"><?= e(str_replace('_', ' ', ucfirst($r['status']))) ?></span></div>
        <?php endforeach; ?>
      </div>
    <?php else: ?><p class="support-muted-empty">You haven't submitted any reports.</p><?php endif; ?>
  </section>
</div>
