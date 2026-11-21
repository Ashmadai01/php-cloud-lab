
<div class="container messages-page">
  <div class="messages-heading">
    <div>
      <span class="messages-eyebrow">CAMPUSX CONNECT</span>
      <h1>Messages</h1>
      <p class="muted">Your conversations, all in one place.</p >
    </div>
    <a class="btn btn-primary" href="<?= url('/marketplace') ?>">
      Explore marketplace
    </a >
  </div>

  <?php if (!empty($conversations)): ?>
    <div class="messages-inbox">
      <div class="inbox-top">
        <div>
          <h2>Your conversations</h2>
          <p class="muted small">Stay connected with the campus community.</p >
        </div>
        <span class="inbox-total">
          <?= count($conversations) ?> conversations
        </span>
      </div>

      <div class="conversation-list">
        <?php foreach ($conversations as $c): ?>
          <a class="conv-item"
             href="<?= url('/messages/' . (int) $c['id']) ?>">
            <div class="conversation-avatar">
              <?= e(strtoupper(substr($c['listing_title'] ?? 'C', 0, 1))) ?>
            </div>

            <div class="conversation-content">
              <div class="conversation-title-row">
                <span class="t">
                  <?= e($c['listing_title'] ?? 'Conversation') ?>
                </span>

                <span class="conversation-time">
                  <?= e(timeAgo($c['last_at'] ?? $c['updated_at'])) ?>
                </span>
              </div>

              <div class="conversation-bottom">
                <span class="p">
                  <?= e($c['last_message'] ?? 'No messages yet') ?>
                </span>

                <?php if ((int) ($c['unread'] ?? 0) > 0): ?>
                  <span class="badge-count">
                    <?= (int) $c['unread'] ?>
                  </span>
                <?php endif; ?>
              </div>
            </div>

            <span class="conversation-arrow">›</span>
          </a >
        <?php endforeach; ?>
      </div>
    </div>
  <?php else: ?>
    <div class="messages-empty">
      <div class="empty-icon">✉</div>
      <h2>No conversations yet</h2>
      <p>When you contact a seller or receive a message, your conversations will appear here.</p >
      <a class="btn btn-primary" href="<?= url('/marketplace') ?>">
        Browse marketplace
      </a >
    </div>
  <?php endif; ?>
</div>
