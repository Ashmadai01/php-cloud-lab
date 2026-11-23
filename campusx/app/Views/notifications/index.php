<div class="container notifications-page">
  <div class="notifications-header">
    <div>
      <span class="notifications-eyebrow">STAY UPDATED</span>
      <h1>Notifications</h1>
      <p class="muted">Your latest updates and support activity.</p>
    </div>
    <div class="notifications-summary">
      <span class="notifications-summary-number"><?= (int) $unreadCount ?></span>
      <span>Unread</span>
    </div>
  </div>

  <div class="notifications-toolbar">
    <span class="notifications-total">
      <?= count($notifications) ?> recent notifications
    </span>
    <?php if ($unreadCount > 0): ?>
      <form method="post" action="<?= url('/notifications/read-all') ?>">
        <?= csrfField() ?>
        <button class="btn btn-outline btn-sm" type="submit">
          ✓ Mark all as read
        </button>
      </form>
    <?php endif; ?>
  </div>

  <?php if (!empty($notifications)): ?>
    <div class="notifications-list">
      <?php foreach ($notifications as $n): ?>
        <?php
          $isUnread = $n['read_at'] === null;
          $isPurchase = !empty($n['conversation_id']);
          $iconClass = $n['ticket_id'] !== null
              ? 'is-support'
              : ($isPurchase ? 'is-purchase' : 'is-general');
          $icon = $n['ticket_id'] !== null
              ? '🎧'
              : ($isPurchase ? '🛒' : '🔔');
        ?>
        <article class="notification-item <?= $isUnread ? 'is-unread' : 'is-read' ?>">
          <div class="notification-icon <?= $iconClass ?>">
            <?= $icon ?>
          </div>

          <div class="notification-content">
            <div class="notification-message">
              <?= e($n['message']) ?>
              <?php if ($isUnread): ?>
                <span class="notification-new">NEW</span>
              <?php endif; ?>
            </div>
            <div class="notification-time">
              <span>◷</span> <?= e(timeAgo($n['created_at'])) ?>
            </div>
          </div>

          <div class="notification-actions">
            <?php if ($n['ticket_id'] !== null || $isPurchase): ?>
              <a class="btn btn-primary btn-sm"
                 href="<?= url('/notifications/' . (int) $n['id'] . '/open') ?>">
                <?= $isPurchase ? 'Open conversation →' : 'View ticket →' ?>
              </a>
            <?php endif; ?>

            <?php if ($isUnread): ?>
              <form method="post"
                    action="<?= url('/notifications/' . (int) $n['id'] . '/read') ?>">
                <?= csrfField() ?>
                <button class="notification-read-btn" type="submit">
                  Mark as read
                </button>
              </form>
            <?php endif; ?>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  <?php else: ?>
    <div class="notifications-empty">
      <div class="notifications-empty-icon">🔔</div>
      <h2>You're all caught up!</h2>
      <p class="muted">There are no notifications to show right now.</p>
      <a href="<?= url('/') ?>" class="btn btn-primary">Back to home</a>
    </div>
  <?php endif; ?>
</div>
