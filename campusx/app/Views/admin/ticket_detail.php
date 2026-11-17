<div class="container">
  <div class="page-head"><nav class="breadcrumb"><a href="<?= url('/admin/tickets') ?>">Tickets</a> / #<?= (int) $ticket['id'] ?></nav><h1><?= e($ticket['subject']) ?></h1>
  <p class="muted"><?= e($ticket['user_name']) ?> · <?= e($ticket['user_email']) ?> · <span class="badge badge-<?= e($ticket['status']) ?>"><?= e(str_replace('_', ' ', ucfirst($ticket['status']))) ?></span></p></div>
  <div class="messages" style="max-height:none">
    <?php foreach (($messages ?? []) as $m): ?>
      <div class="msg <?= ($m['author_role'] ?? '') === 'admin' ? 'mine' : 'theirs' ?>">
        <div><?= nl2br(e($m['body'])) ?></div>
        <?php foreach (($m['attachments'] ?? []) as $attId): ?>
          <a href="<?= url('/support/attachments/' . (int) $attId) ?>" target="_blank" rel="noopener"><img src="<?= url('/support/attachments/' . (int) $attId) ?>" alt="Attachment" loading="lazy" style="max-width:160px;max-height:120px;border-radius:8px;margin-top:6px;object-fit:cover"></a>
        <?php endforeach; ?>
        <div class="meta"><?= e($m['author_name']) ?><?= ($m['author_role'] ?? '') === 'admin' ? ' (Support)' : '' ?> · <?= e(displayDate($m['created_at'])) ?></div>
      </div>
    <?php endforeach; ?>
  </div>
  <div class="panel mt">
    <form method="post" action="<?= url('/admin/tickets/' . (int) $ticket['id'] . '/reply') ?>">
      <?= csrfField() ?>
      <div class="field"><label for="body">Reply</label><textarea id="body" name="body" required maxlength="5000"></textarea></div>
      <div class="field"><label for="status">Set status</label>
        <select id="status" name="status"><?php foreach (['open', 'in_review', 'waiting_for_user', 'resolved', 'closed'] as $s): ?><option value="<?= $s ?>" <?= $ticket['status'] === $s ? 'selected' : '' ?>><?= str_replace('_', ' ', ucfirst($s)) ?></option><?php endforeach; ?></select>
      </div>
      <button class="btn btn-primary" type="submit">Send reply</button>
    </form>
  </div>
</div>
