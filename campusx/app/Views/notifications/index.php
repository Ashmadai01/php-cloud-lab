<div class="container">
  <div class="page-head">
    <h1>Notifications</h1>
    <p class="muted">
      Your latest updates and support activity.
      <?php if ($unreadCount > 0): ?>
        <strong><?= (int) $unreadCount ?> unread</strong>
      <?php endif; ?>
    </p>
  </div>

  <?php if ($unreadCount > 0): ?>
    <form method="post" action="<?= url('/notifications/read-all') ?>" class="mb">
      <?= csrfField() ?>
      <button class="btn btn-outline" type="submit">Mark all as read</button>
    </form>
  <?php endif; ?>

  <?php if (!empty($notifications)): ?>
    <div class="card-list">
      <?php foreach ($notifications as $n): ?>
        <div class="card mb" style="<?= $n['read_at'] === null ? 'border-left:4px solid var(--primary);' : '' ?>">
          <div class="flex" style="justify-content:space-between;align-items:flex-start;gap:12px;">
            <div>
              <p>
                <strong><?= e($n['message']) ?></strong>
                <?php if ($n['read_at'] === null): ?>
                  <span class="badge">New</span>
                <?php endif; ?>
              </p>
              <p class="muted">
                <?= e(timeAgo($n['created_at'])) ?>
              </p>
            </div>

            <?php if ($n['ticket_id'] !== null): ?>
              <a class="btn btn-sm btn-primary"
                 href="<?= url('/notifications/' . (int) $n['id'] . '/open') ?>">
                View ticket
              </a>
            <?php endif; ?>
          </div>

          <?php if ($n['read_at'] === null): ?>
            <form method="post"
                  action="<?= url('/notifications/' . (int) $n['id'] . '/read') ?>"
                  class="mt">
              <?= csrfField() ?>
              <button class="btn btn-sm btn-outline" type="submit">
                Mark as read
              </button>
            </form>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>
  <?php else: ?>
    <p class="muted">You don't have any notifications yet.</p>
  <?php endif; ?>
</div>
