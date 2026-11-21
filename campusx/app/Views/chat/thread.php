<div class="container">
  <div class="page-head">
    <nav class="breadcrumb"><a href="<?= url('/messages') ?>">Messages</a> / Conversation</nav>
    <h1><?= e($conversation['listing_title'] ?? 'Conversation') ?></h1>
    <?php foreach ($participants as $participant): ?>
  <?php if ((int) $participant['id'] === (int) $otherUserId): ?>
    <p class="muted small">
  <strong><?= e($participant['name']) ?></strong>
  ·
  <span id="presence-status">
    <?php if (!empty($otherPresence['online'])): ?>
      <span style="color:#16a34a">● Online</span>
    <?php elseif (!empty($otherPresence['last_seen'])): ?>
      Last seen <?= e(timeAgo($otherPresence['last_seen'])) ?>
    <?php else: ?>
      Offline
    <?php endif; ?>
  </span>
</p >
  <?php endif; ?>
<?php endforeach; ?>
    <?php if (!empty($conversation['listing_id'])): ?>
      <p class="muted small">About: <a href="<?= url('/listings/' . (int) $conversation['listing_id']) ?>"><?= e($conversation['listing_title']) ?></a> · <?= e(formatMoney($conversation['listing_price'] ?? 0)) ?></p>
    <?php endif; ?>
  </div>
  <div class="messages" data-chat-thread="<?= (int) $conversation['id'] ?>" data-me="<?= (int) ($user['id'] ?? 0) ?>">
    <?php if (!empty($messages)): ?>
      <?php foreach ($messages as $m): ?>
        <div class="msg <?= (int) $m['sender_id'] === (int) ($user['id'] ?? 0) ? 'mine' : 'theirs' ?>" data-msg-id="<?= (int) $m['id'] ?>">
          <div><?= nl2br(e($m['body'])) ?></div>
          <div class="meta">
  <?= e($m['sender_name']) ?> · <?= e(displayDate($m['created_at'])) ?>

  <?php if ((int) $m['sender_id'] === (int) ($user['id'] ?? 0)): ?>
    · <span class="read-status" data-read-status="<?= (int) $m['id'] ?>">
      <?= !empty($m['is_read']) ? 'Read' : 'Sent' ?>
    </span>
  <?php else: ?>
    · <a href=" '/report/message/' . (int) $m['id']) ?>">Report</a >
  <?php endif; ?>
</div>
        </div>
      <?php endforeach; ?>
    <?php else: ?>
      <p class="muted" style="text-align:center">No messages yet. Say hello!</p>
    <?php endif; ?>
  </div>
  <?php if (!empty($messagingBlocked)): ?>
    <div class="alert alert-info">Messaging is turned off in this conversation.<?php if (!empty($iBlocked)): ?> You have blocked this user.<?php endif; ?></div>
  <?php else: ?>
  <form class="chat-form" method="post" action="<?= url('/messages/' . (int) $conversation['id'] . '/send') ?>">
    <?= csrfField() ?>
    <label class="skip-link" for="body">Message</label>
    <input id="body" name="body" required maxlength="2000" placeholder="Type your message…" autocomplete="off">
    <button class="btn btn-primary" type="submit">Send</button>
  </form>
  <?php endif; ?>
  <p class="muted small mt">Keep chat on SDUT Swap. Meet on campus. <a href="<?= url('/report/conversation/' . (int) $conversation['id']) ?>">Report this conversation</a></p>
  <?php if (!empty($otherUserId)): ?>
    <form method="post" action="<?= url('/users/' . (int) $otherUserId . (!empty($iBlocked) ? '/unblock' : '/block')) ?>" class="inline-form mt">
      <?= csrfField() ?>
      <input type="hidden" name="return" value="<?= e('/messages/' . (int) $conversation['id']) ?>">
      <?php if (!empty($iBlocked)): ?>
        <button class="btn btn-sm btn-outline" type="submit">Unblock this user</button>
      <?php else: ?>
        <button class="btn btn-sm btn-danger" type="submit" data-confirm="Block this user? Neither of you will be able to send messages.">Block this user</button>
      <?php endif; ?>
    </form>
  <?php endif; ?>
</div>
