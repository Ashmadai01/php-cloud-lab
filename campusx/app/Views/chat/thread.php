
<div class="container chat-page">

  <div class="chat-header">
    <div class="chat-header-left">
      <a class="chat-back" href="<?= url('/messages') ?>">←</a >

      <div class="chat-header-info">
        <span class="chat-eyebrow">CAMPUSX CONNECT</span>
        <h1><?= e($conversation['listing_title'] ?? 'Conversation') ?></h1>

        <?php foreach ($participants as $participant): ?>
          <?php if ((int) $participant['id'] === (int) $otherUserId): ?>
            <p class="chat-user-status">
              <strong><?= e($participant['name']) ?></strong>
              <span id="presence-status">
                <?php if (!empty($otherPresence['online'])): ?>
                  <span class="status-online">● Online</span>
                <?php elseif (!empty($otherPresence['last_seen'])): ?>
                  Last seen <?= e(timeAgo($otherPresence['last_seen'])) ?>
                <?php else: ?>
                  Offline
                <?php endif; ?>
              </span>
            </p >
          <?php endif; ?>
        <?php endforeach; ?>
      </div>
    </div>

    <?php if (!empty($otherUserId)): ?>
      <form method="post" action="<?= url('/users/' . (int) $otherUserId . (!empty($iBlocked) ? '/unblock' : '/block')) ?>">
        <?= csrfField() ?>
        <input type="hidden" name="return" value="<?= e('/messages/' . (int) $conversation['id']) ?>">
        <?php if (!empty($iBlocked)): ?>
          <button class="chat-action-btn" type="submit">Unblock</button>
        <?php else: ?>
          <button class="chat-action-btn" type="submit" data-confirm="Block this user? Neither of you will be able to send messages.">Block</button>
        <?php endif; ?>
      </form>
    <?php endif; ?>
  </div>

  <?php if (!empty($conversation['listing_id'])): ?>
    <a class="chat-listing-card" href="<?= url('/listings/' . (int) $conversation['listing_id']) ?>">
      <span class="chat-listing-icon">⌂</span>
      <span class="chat-listing-info">
        <small>DISCUSSING ITEM</small>
        <strong><?= e($conversation['listing_title']) ?></strong>
      </span>
      <strong class="chat-listing-price">
        <?= e(formatMoney($conversation['listing_price'] ?? 0)) ?>
      </strong>
    </a >
  <?php endif; ?>

  <div class="chat-window">
    <div class="messages chat-messages"
         data-chat-thread="<?= (int) $conversation['id'] ?>"
         data-me="<?= (int) ($user['id'] ?? 0) ?>">

      <?php if (!empty($messages)): ?>
        <?php foreach ($messages as $m): ?>
          <div class="msg <?= (int) $m['sender_id'] === (int) ($user['id'] ?? 0) ? 'mine' : 'theirs' ?>"
               data-msg-id="<?= (int) $m['id'] ?>">

            <div class="message-body"><?= nl2br(e($m['body'])) ?></div>

            <div class="meta">
              <?= e(displayDate($m['created_at'])) ?>

              <?php if ((int) $m['sender_id'] === (int) ($user['id'] ?? 0)): ?>
                <span class="read-status" data-read-status="<?= (int) $m['id'] ?>">
                  <?= !empty($m['is_read']) ? ' · Read' : ' · Sent' ?>
                </span>
              <?php else: ?>
                <span> · <a href="<?= url('/report/message/' . (int) $m['id']) ?>">Report</a ></span>
              <?php endif; ?>
            </div>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <div class="chat-empty">
          <span>✉</span>
          <h3>Start a conversation</h3>
          <p>Say hello and make your first connection on campus.</p >
        </div>
      <?php endif; ?>

    </div>

    <div id="typing-indicator" class="typing-indicator" style="display:none;">
      <span></span><span></span><span></span>
      <small>Typing...</small>
    </div>

    <?php if (!empty($messagingBlocked)): ?>
      <div class="alert alert-info">
        Messaging is turned off in this conversation.
        <?php if (!empty($iBlocked)): ?>
          You have blocked this user.
        <?php endif; ?>
      </div>
    <?php else: ?>
      <form class="chat-form" method="post"
            action="<?= url('/messages/' . (int) $conversation['id'] . '/send') ?>">
        <?= csrfField() ?>
        <label class="skip-link" for="body">Message</label>
        <input id="body" name="body" required maxlength="2000"
               placeholder="Write your message..." autocomplete="off">
        <button class="btn btn-primary" type="submit">
          Send <span>➤</span>
        </button>
      </form>
    <?php endif; ?>
  </div>

  <div class="chat-footer">
    <p>Keep chat on SDUT Swap. Meet on campus.</p >
    <a href="<?= url('/report/conversation/' . (int) $conversation['id']) ?>">
      Report conversation
    </a >
  </div>

</div>
