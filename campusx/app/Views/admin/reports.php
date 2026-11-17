<div class="container">
  <div class="page-head"><h1>Reports Queue</h1></div>
  <nav class="admin-nav"><a href="<?= url('/admin') ?>">Overview</a><a href="<?= url('/admin/users') ?>">Users</a><a href="<?= url('/admin/listings') ?>">Listings</a><a href="<?= url('/admin/verifications') ?>">Verifications</a><a href="<?= url('/admin/tickets') ?>">Tickets</a><a href="<?= url('/admin/reports') ?>" class="active">Reports</a><a href="<?= url('/admin/audit') ?>">Audit Log</a><a href="<?= url('/admin/security') ?>">Security Events</a></nav>
  <form method="get" action="<?= url('/admin/reports') ?>" class="flex mb">
    <select name="status" style="border:1px solid var(--border);border-radius:10px;padding:10px 12px">
      <option value="">All statuses</option>
      <?php foreach (['open', 'under_review', 'resolved', 'dismissed'] as $s): ?><option value="<?= $s ?>" <?= ($status ?? '') === $s ? 'selected' : '' ?>><?= str_replace('_', ' ', ucfirst($s)) ?></option><?php endforeach; ?>
    </select>
    <button class="btn btn-outline" type="submit">Filter</button>
  </form>
  <div class="table-wrap"><table>
    <thead><tr><th>#</th><th>Target</th><th>Type</th><th>Reporter</th><th>Status</th><th>Submitted</th></tr></thead>
    <tbody>
    <?php foreach (($reports ?? []) as $r): ?>
      <tr>
        <td><a href="<?= url('/admin/reports/' . (int) $r['id']) ?>">#<?= (int) $r['id'] ?></a></td>
        <td><?= e(ucfirst($r['target_type'])) ?> #<?= (int) $r['target_id'] ?></td>
        <td><?= e($r['report_type']) ?></td>
        <td><?= e($r['reporter_name']) ?></td>
        <td><span class="badge badge-<?= e($r['status']) ?>"><?= e(str_replace('_', ' ', ucfirst($r['status']))) ?></span></td>
        <td><?= e(timeAgo($r['created_at'])) ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
</div>
