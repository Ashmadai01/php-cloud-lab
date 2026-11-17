<div class="container">
  <div class="page-head"><nav class="breadcrumb"><a href="<?= url('/admin/reports') ?>">Reports</a> / #<?= (int) $report['id'] ?></nav><h1>Report #<?= (int) $report['id'] ?></h1></div>
  <div class="split">
    <div class="panel">
      <p><strong>Target:</strong> <?= e(ucfirst($report['target_type'])) ?> #<?= (int) $report['target_id'] ?></p>
      <p><strong>Type:</strong> <?= e($report['report_type']) ?></p>
      <p><strong>Reporter:</strong> <?= e($report['reporter_name']) ?> (<?= e($report['reporter_email']) ?>)</p>
      <p><strong>Description:</strong></p>
      <p><?= nl2br(e($report['description'])) ?></p>
      <?php if (!empty($evidence)): ?>
        <p><strong>Evidence files</strong> (private storage, staff-only; each view is logged):</p>
        <?php foreach ($evidence as $ev): ?>
          <a href="<?= url('/admin/reports/' . (int) $report['id'] . '/evidence/' . (int) $ev['id']) ?>" target="_blank" rel="noopener"><img src="<?= url('/admin/reports/' . (int) $report['id'] . '/evidence/' . (int) $ev['id']) ?>" alt="Evidence" loading="lazy" style="max-width:160px;max-height:120px;border-radius:8px;margin:4px;object-fit:cover"></a>
        <?php endforeach; ?>
      <?php endif; ?>
      <?php if (!empty($transcript)): ?>
        <hr>
        <p><strong>Reported conversation</strong> <span class="muted small">(shown only because it was reported by a participant; this access is audited)</span></p>
        <div class="messages" style="max-height:320px">
          <?php foreach ($transcript['messages'] as $m): ?>
            <div class="msg theirs" <?= ((int) $m['id'] === (int) ($transcript['highlight'] ?? 0)) ? 'style="outline:2px solid var(--danger)"' : '' ?>>
              <div><?= nl2br(e($m['body'])) ?></div>
              <div class="meta"><?= e($m['sender_name']) ?> · <?= e(displayDate($m['created_at'])) ?><?= ((int) $m['id'] === (int) ($transcript['highlight'] ?? 0)) ? ' · REPORTED MESSAGE' : '' ?></div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php elseif (in_array($report['target_type'], ['conversation', 'message'], true)): ?>
        <p class="muted small">The conversation is not shown: the reporter was not a participant, or it no longer exists.</p>
      <?php elseif ($report['target_type'] === 'listing'): ?>
        <p><a href="<?= url('/listings/' . (int) $report['target_id']) ?>">Open reported listing</a></p>
      <?php endif; ?>
    </div>
    <div class="panel">
      <h3>Decision</h3>
      <form method="post" action="<?= url('/admin/reports/' . (int) $report['id'] . '/decide') ?>">
        <?= csrfField() ?>
        <div class="field"><label for="status">Status</label>
          <select id="status" name="status"><?php foreach (['open', 'under_review', 'resolved', 'dismissed'] as $s): ?><option value="<?= $s ?>" <?= $report['status'] === $s ? 'selected' : '' ?>><?= str_replace('_', ' ', ucfirst($s)) ?></option><?php endforeach; ?></select>
        </div>
        <div class="field"><label for="notes">Moderator notes</label><textarea id="notes" name="notes" maxlength="5000"><?= e($report['moderator_notes'] ?? '') ?></textarea></div>
        <button class="btn btn-primary" type="submit">Save decision</button>
      </form>
    </div>
  </div>
</div>
