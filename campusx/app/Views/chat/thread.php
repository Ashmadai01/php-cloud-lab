
<div class="container chat-page">

  <div class="chat-header">
    <div class="chat-header-left">
      <a class="chat-back" href="<?= url('/messages') ?>">←</a >

      <div class="chat-header-info">
        <span class="chat-eyebrow">CAMPUSX CONNECT</span>
        <h1>Conversation</h1>

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
      <div class="chat-header-actions">
        <a class="chat-profile-btn" href="<?= url('/users/' . (int) $otherUserId) ?>">View Profile</a>
        <form method="post" action="<?= url('/users/' . (int) $otherUserId . (!empty($iBlocked) ? '/unblock' : '/block')) ?>">
          <?= csrfField() ?>
          <input type="hidden" name="return" value="<?= e('/messages/' . (int) $conversation['id']) ?>">
          <?php if (!empty($iBlocked)): ?>
            <button class="chat-action-btn" type="submit">Unblock</button>
          <?php else: ?>
            <button class="chat-action-btn" type="submit" data-confirm="Block this user? Neither of you will be able to send messages.">Block</button>
          <?php endif; ?>
        </form>
      </div>
    <?php endif; ?>
  </div>

  <?php if (!empty($conversation['listing_id'])): ?>
    <a class="chat-listing-card" href="<?= url('/listings/' . (int) $conversation['listing_id']) ?>">
      <img class="chat-listing-image" src="<?= e(listingCoverUrl($conversation['listing_cover_path'] ?? null)) ?>" alt="<?= e($conversation['listing_title']) ?>">
      <span class="chat-listing-info">
        <small>DISCUSSING ITEM</small>
        <strong><?= e($conversation['listing_title']) ?></strong>
      </span>
      <strong class="chat-listing-price">
        <?= e(formatMoney($conversation['listing_price'] ?? 0)) ?>
      </strong>
    </a >
  <?php endif; ?>

  <?php
    $transaction = $currentTransaction ?? null;
    $currentUserId = (int) ($user['id'] ?? 0);
    $isBuyer = $transaction && (int) $transaction['buyer_id'] === $currentUserId;
    $isSeller = $transaction && (int) $transaction['seller_id'] === $currentUserId;
  ?>

  <section class="transaction-panel">
    <h3>Purchase & Transaction</h3>

    <?php if (!$transaction): ?>
      <p>No purchase request has been made for this listing.</p>

      <?php if (!empty($conversation['listing_id']) && !$messagingBlocked): ?>
        <form method="post" action="<?= url('/messages/' . (int) $conversation['id'] . '/purchase-request') ?>">
          <?= csrfField() ?>
          <button class="btn btn-primary" type="submit">Request to Buy</button>
        </form>
      <?php endif; ?>

    <?php else: ?>
      <p>Status: <strong><?= e(ucfirst($transaction['status'])) ?></strong></p>

      <?php if ($transaction['status'] === 'pending' && $isSeller): ?>
        <div class="transaction-actions">
          <form method="post" action="<?= url('/transactions/' . (int) $transaction['id'] . '/decision') ?>">
            <?= csrfField() ?>
            <input type="hidden" name="decision" value="accept">
            <button class="btn btn-primary" type="submit">Accept Request</button>
          </form>

          <form method="post" action="<?= url('/transactions/' . (int) $transaction['id'] . '/decision') ?>">
            <?= csrfField() ?>
            <input type="hidden" name="decision" value="reject">
            <button class="btn btn-danger" type="submit">Reject Request</button>
          </form>
        </div>
      <?php endif; ?>

      <?php if ($transaction['status'] === 'accepted' && $isBuyer): ?>
        <form method="post" action="<?= url('/transactions/' . (int) $transaction['id'] . '/confirm') ?>">
          <?= csrfField() ?>
          <button class="btn btn-primary" type="submit"
                  data-confirm="Confirm that you have received this item?">
            Confirm Receipt
          </button>
        </form>
      <?php endif; ?>

      <?php if ($transaction['status'] === 'completed' && $isBuyer && empty($transaction['review_id'])): ?>
        <form class="review-form" method="post" action="<?= url('/transactions/' . (int) $transaction['id'] . '/review') ?>">
          <?= csrfField() ?>

          <div class="review-form-heading">
            <span class="review-eyebrow">SHARE YOUR EXPERIENCE</span>
            <h3>How was your purchase?</h3>
            <p>Your feedback helps build trust in the SDUTSwap community.</p>
          </div>

          <fieldset class="rating-field">
            <legend>Your rating</legend>
            <div class="rating-stars" role="radiogroup" aria-label="Choose your rating">
              <?php for ($star = 5; $star >= 1; $star--): ?>
                <input type="radio" id="rating-<?= $star ?>"
                       name="rating" value="<?= $star ?>" required>
                <label for="rating-<?= $star ?>" title="<?= $star ?> out of 5 stars">★</label>
              <?php endfor; ?>
            </div>
            <small class="rating-hint">Select from 1 to 5 stars</small>
          </fieldset>

          <div class="review-comment-field">
            <label for="transaction-comment">Your comment <span>(optional)</span></label>
            <textarea id="transaction-comment" name="comment" maxlength="1000"
                      rows="4" placeholder="Tell others about your experience with this seller..."></textarea>
            <small>Maximum 1000 characters.</small>
          </div>

          <button class="btn btn-primary review-submit" type="submit">
            Submit Review <span aria-hidden="true">→</span>
          </button>
        </form>
      <?php endif; ?>

      <?php if (!empty($transaction['review_id'])): ?>
        <p>Review submitted: <strong><?= (int) $transaction['review_rating'] ?>/5</strong></p>
        <?php if (!empty($transaction['review_comment'])): ?>
          <p><?= nl2br(e($transaction['review_comment'])) ?></p>
        <?php endif; ?>
      <?php endif; ?>
    <?php endif; ?>
  </section>

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
