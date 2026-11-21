<div class="container">
  <div class="page-head">
    <nav class="breadcrumb">
      <a href="<?= url('/admin/tickets') ?>">Tickets</a> / #<?= (int) $ticket['id'] ?>
    </nav>
    <h1><?= e($ticket['subject']) ?></h1>
    <p class="muted">
      <?= e($ticket['user_name']) ?> · <?= e($ticket['user_email']) ?>
    </p>
  </div>

  <div class="panel">
    <h3>Ticket management</h3>

    <p>
      <strong>Current status:</strong>
      <span class="badge badge-<?= e($ticket['status']) ?>">
        <?= e(str_replace('_', ' ', ucfirst($ticket['status']))) ?>
      </span>
    </p>

    <p>
      <strong>Current priority:</strong>
      <?= e(ucfirst($ticket['priority'] ?? 'normal')) ?>
    </p>

    <div class="grid grid-2">
      <form method="post" action="<?= url('/admin/tickets/' . (int) $ticket['id'] . '/status') ?>">
        <?= csrfField() ?>
        <div class="field">
          <label for="ticket-status">Change status</label>
          <select id="ticket-status" name="status" required>
            <?php foreach (['open', 'in_review', 'waiting_for_user', 'resolved', 'closed'] as $s): ?>
              <option value="<?= e($s) ?>" <?= $ticket['status'] === $s ? 'selected' : '' ?>>
                <?= e(str_replace('_', ' ', ucfirst($s))) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <button class="btn btn-primary" type="submit">Update status</button>
      </form>

      <form method="post" action="<?= url('/admin/tickets/' . (int) $ticket['id'] . '/priority') ?>">
        <?= csrfField() ?>
        <div class="field">
          <label for="ticket-priority">Change priority</label>
          <select id="ticket-priority" name="priority" required>
            <?php foreach (['low', 'normal', 'high', 'urgent'] as $p): ?>
              <option value="<?= e($p) ?>" <?= ($ticket['priority'] ?? 'normal') === $p ? 'selected' : '' ?>>
                <?= e(ucfirst($p)) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <button class="btn btn-primary" type="submit">Update priority</button>
      </form>
    </div>
  </div>

  <h2 class="mt">Conversation</h2>

  <div class="messages" style="max-height:none">
    <?php foreach (($messages ?? []) as $m): ?>
      <div class="msg <?= ($m['author_role'] ?? '') === 'admin' ? 'mine' : 'theirs' ?>">
        <div><?= nl2br(e($m['body'])) ?></div>

        <?php foreach (($m['attachments'] ?? []) as $attId): ?>
          <a href="<?= url('/support/attachments/' . (int) $attId) ?>" target="_blank" rel="noopener">
            <img src="<?= url('/support/attachments/' . (int) $attId) ?>"
                 alt="Attachment"
                 loading="lazy"
                 style="max-width:160px;max-height:120px;border-radius:8px;margin-top:6px;object-fit:cover">
          </a>
        <?php endforeach; ?>

        <div class="meta">
          <?= e($m['author_name']) ?>
          <?= ($m['author_role'] ?? '') === 'admin' ? ' (Support)' : '' ?>
          · <?= e(displayDate($m['created_at'])) ?>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <div class="panel mt">
    <h3>Reply to ticket</h3>
    <form method="post" action="<?= url('/admin/tickets/' . (int) $ticket['id'] . '/reply') ?>">
      <?= csrfField() ?>
      <div class="field">
        <label for="body">Your reply</label>
        <textarea id="body" name="body" required maxlength="5000"></textarea>
      </div>
      <button class="btn btn-primary" type="submit">Send reply</button>
    </form>
  </div>

  <div class="panel mt">
    <h3>Activity history</h3>

    <?php if (!empty($activity)): ?>
      <div class="activity-list">
        <?php foreach ($activity as $item): ?>
          <div class="activity-item" style="padding:12px 0;border-bottom:1px solid var(--border, #ddd)">
            <strong>
              <?= e(ucfirst(str_replace('_', ' ', $item['action']))) ?>
            </strong>

            <?php if ($item['old_value'] !== null || $item['new_value'] !== null): ?>
              <p class="muted">
                <?= e((string) ($item['old_value'] ?? '—')) ?>
                →
                <?= e((string) ($item['new_value'] ?? '—')) ?>
              </p>
            <?php endif; ?>

            <div class="meta">
              <?= e($item['actor_name']) ?>
              · <?= e(displayDate($item['created_at'])) ?>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <p class="muted">No activity recorded yet.</p>
    <?php endif; ?>
  </div>
</div>
