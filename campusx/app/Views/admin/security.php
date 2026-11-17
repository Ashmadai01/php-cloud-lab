<div class="container">
  <div class="page-head"><h1>Security Events</h1><p class="muted">Failed logins, CSRF failures, rate limits, and suspicious access.</p></div>
  <nav class="admin-nav"><a href="<?= url('/admin') ?>">Overview</a><a href="<?= url('/admin/users') ?>">Users</a><a href="<?= url('/admin/listings') ?>">Listings</a><a href="<?= url('/admin/verifications') ?>">Verifications</a><a href="<?= url('/admin/tickets') ?>">Tickets</a><a href="<?= url('/admin/reports') ?>">Reports</a><a href="<?= url('/admin/audit') ?>">Audit Log</a><a href="<?= url('/admin/security') ?>" class="active">Security Events</a></nav>
  <form method="get" action="<?= url('/admin/security') ?>" class="flex mb">
    <select name="severity" style="border:1px solid var(--border);border-radius:10px;padding:10px 12px">
      <option value="">All severities</option>
      <?php foreach (['low', 'medium', 'high'] as $s): ?><option value="<?= $s ?>" <?= ($severity ?? '') === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option><?php endforeach; ?>
    </select>
    <button class="btn btn-outline" type="submit">Filter</button>
  </form>
  <div class="table-wrap"><table>
    <thead><tr><th>Time (UTC)</th><th>Event</th><th>Severity</th><th>User</th><th>Details</th></tr></thead>
    <tbody>
    <?php foreach (($events ?? []) as $ev): ?>
      <tr>
        <td><?= e(displayDate($ev['created_at'])) ?></td>
        <td><?= e($ev['event_type']) ?></td>
        <td><span class="badge badge-<?= $ev['severity'] === 'high' ? 'removed' : ($ev['severity'] === 'medium' ? 'pending' : 'draft') ?>"><?= e(ucfirst($ev['severity'])) ?></span></td>
        <td><?= e((string) ($ev['user_id'] ?? '—')) ?></td>
        <td><?= e($ev['details'] ?? '') ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
</div>
