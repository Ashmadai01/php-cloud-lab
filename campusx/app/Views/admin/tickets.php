<div class="container">
  <div class="page-head"><h1>Support Tickets</h1></div>
  <nav class="admin-nav"><a href="<?= url('/admin') ?>">Overview</a><a href="<?= url('/admin/users') ?>">Users</a><a href="<?= url('/admin/listings') ?>">Listings</a><a href="<?= url('/admin/verifications') ?>">Verifications</a><a href="<?= url('/admin/tickets') ?>" class="active">Tickets</a><a href="<?= url('/admin/reports') ?>">Reports</a><a href="<?= url('/admin/audit') ?>">Audit Log</a><a href="<?= url('/admin/security') ?>">Security Events</a></nav>
  <form method="get" action="<?= url('/admin/tickets') ?>" class="flex mb">
    <select name="status" style="border:1px solid var(--border);border-radius:10px;padding:10px 12px">
      <option value="">All statuses</option>
      <?php foreach (['open', 'in_review', 'waiting_for_user', 'resolved', 'closed'] as $s): ?><option value="<?= $s ?>" <?= ($status ?? '') === $s ? 'selected' : '' ?>><?= str_replace('_', ' ', ucfirst($s)) ?></option><?php endforeach; ?>
    </select>
    <button class="btn btn-outline" type="submit">Filter</button>
  </form>
  <div class="table-wrap"><table>
    <thead><tr><th>#</th><th>Subject</th><th>User</th><th>Type</th><th>Status</th><th>Priority</th><th>Updated</th></tr></thead>
    <tbody>
    <?php foreach (($tickets ?? []) as $t): ?>
      <tr>
        <td><a href="<?= url('/admin/tickets/' . (int) $t['id']) ?>">#<?= (int) $t['id'] ?></a></td>
        <td><?= e($t['subject']) ?></td>
        <td><?= e($t['user_name']) ?></td>
        <td><?= e(ucfirst($t['ticket_type'])) ?></td>
        <td><span class="badge badge-<?= e($t['status']) ?>"><?= e(str_replace('_', ' ', ucfirst($t['status']))) ?></span></td>
        <td><?= e(ucfirst($t['priority'])) ?></td>
        <td><?= e(timeAgo($t['updated_at'])) ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
</div>
