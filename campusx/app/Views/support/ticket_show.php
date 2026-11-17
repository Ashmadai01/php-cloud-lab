<div class="container">
  <div class="page-head">
    <nav class="breadcrumb"><a href="<?= url('/support/my') ?>">My Support</a> / Ticket #<?= (int) $ticket['id'] ?></nav>
    <h1><?= e($ticket['subject']) ?></h1>
    <p class="muted">Status: <span class="badge badge-<?= e($ticket['status']) ?>"><?= e(str_replace('_', ' ', ucfirst($ticket['status']))) ?></span> · Priority: <?= e(ucfirst($ticket['priority'])) ?> · Category: <?= e($ticket['category']) ?></p>
  </div>
  <div class="messages" style="max-height:none">
    <?php foreach (($messages ?? []) as $m): ?>
      <div class="msg <?= (int) $m['author_id'] === (int) ($user['id'] ?? 0) ? 'mine' : 'theirs' ?>">
        <div><?= nl2br(e($m['body'])) ?></div>
        <?php foreach (($m['attachments'] ?? []) as $attId): ?>
          <a href="<?= url('/support/attachments/' . (int) $attId) ?>" target="_blank" rel="noopener"><img src="<?= url('/support/attachments/' . (int) $attId) ?>" alt="Attachment" loading="lazy" style="max-width:160px;max-height:120px;border-radius:8px;margin-top:6px;object-fit:cover"></a>
        <?php endforeach; ?>
        <div class="meta"><?= e($m['author_name']) ?><?= ($m['author_role'] ?? '') === 'admin' ? ' (Support)' : '' ?> · <?= e(displayDate($m['created_at'])) ?></div>
      </div>
    <?php endforeach; ?>
  </div>
  <form class="chat-form" method="post" enctype="multipart/form-data" action="<?= url('/support/tickets/' . (int) $ticket['id'] . '/reply') ?>" style="flex-wrap:wrap">
    <?= csrfField() ?>
    <label class="skip-link" for="body">Reply</label>
    <input id="body" name="body" required maxlength="5000" placeholder="Write a reply…">
    <button class="btn btn-primary" type="submit">Reply</button>
    <div style="flex-basis:100%"><label class="small muted" for="reply_attachments">Attach images (optional, up to 3)</label> <input id="reply_attachments" name="attachments[]" type="file" multiple accept=".jpg,.jpeg,.png,.webp"></div>
  </form>
</div>
