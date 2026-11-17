<div class="container">
  <div class="page-head"><h1>Audit Log</h1><p class="muted">Append-only record of administrative actions.</p></div>
  <nav class="admin-nav"><a href="<?= url('/admin') ?>">Overview</a><a href="<?= url('/admin/users') ?>">Users</a><a href="<?= url('/admin/listings') ?>">Listings</a><a href="<?= url('/admin/verifications') ?>">Verifications</a><a href="<?= url('/admin/tickets') ?>">Tickets</a><a href="<?= url('/admin/reports') ?>">Reports</a><a href="<?= url('/admin/audit') ?>" class="active">Audit Log</a><a href="<?= url('/admin/security') ?>">Security Events</a></nav>
  <div class="table-wrap"><table>
    <thead><tr><th>Time (UTC)</th><th>Admin</th><th>Action</th><th>Target</th><th>Details</th></tr></thead>
    <tbody>
    <?php foreach (($logs ?? []) as $l): ?>
      <tr>
        <td><?= e(displayDate($l['created_at'])) ?></td>
        <td><?= e($l['admin_name']) ?></td>
        <td><?= e($l['action']) ?></td>
        <td><?= e($l['target_type'] ?? '') ?> <?= e((string) ($l['target_id'] ?? '')) ?></td>
        <td><?= e($l['details'] ?? '') ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
</div>
