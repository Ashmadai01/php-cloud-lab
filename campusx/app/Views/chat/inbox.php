<div class="container">
  <div class="page-head"><h1>Messages</h1></div>
  <?php if (!empty($conversations)): ?>
    <div class="panel" style="padding:0;overflow:hidden">
      <?php foreach ($conversations as $c): ?>
        <a class="conv-item" href="<?= url('/messages/' . (int) $c['id']) ?>">
          <div class="flex" style="justify-content:space-between">
            <span class="t"><?= e($c['listing_title'] ?? 'Conversation') ?></span>
            <?php if ((int) ($c['unread'] ?? 0) > 0): ?><span class="badge-count"><?= (int) $c['unread'] ?> new</span><?php endif; ?>
          </div>
          <div class="p"><?= e($c['last_message'] ?? 'No messages yet') ?></div>
          <div class="muted small"><?= e(timeAgo($c['last_at'] ?? $c['updated_at'])) ?></div>
        </a>
      <?php endforeach; ?>
    </div>
  <?php else: ?>
    <div class="empty"><div class="big">💬</div><p>No conversations yet. Message a seller from any listing.</p><a class="btn btn-primary" href="<?= url('/marketplace') ?>">Browse marketplace</a></div>
  <?php endif; ?>
</div>
