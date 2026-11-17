<div class="container">
  <div class="page-head"><h1>Manage Users</h1></div>
  <nav class="admin-nav"><a href="<?= url('/admin') ?>">Overview</a><a href="<?= url('/admin/users') ?>" class="active">Users</a><a href="<?= url('/admin/listings') ?>">Listings</a><a href="<?= url('/admin/verifications') ?>">Verifications</a><a href="<?= url('/admin/tickets') ?>">Tickets</a><a href="<?= url('/admin/reports') ?>">Reports</a><a href="<?= url('/admin/audit') ?>">Audit Log</a><a href="<?= url('/admin/security') ?>">Security Events</a></nav>
  <form method="get" action="<?= url('/admin/users') ?>" class="flex mb">
    <input type="search" name="q" value="<?= e($q ?? '') ?>" placeholder="Search name or email…" style="flex:1;max-width:320px;border:1px solid var(--border);border-radius:10px;padding:10px 12px">
    <button class="btn btn-outline" type="submit">Search</button>
  </form>
  <div class="table-wrap"><table>
    <thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Verified</th><th>Status</th><th>Joined</th><th>Actions</th></tr></thead>
    <tbody>
    <?php foreach (($users ?? []) as $u): ?>
      <tr>
        <td><?= e($u['name']) ?></td>
        <td><?= e($u['email']) ?></td>
        <td><?= e($u['role']) ?></td>
        <td><?= !empty($u['is_verified']) ? '✓ Yes' : 'No' ?></td>
        <td><?= !empty($u['suspended_at']) ? '<span class="badge badge-removed">Suspended</span>' : '<span class="badge badge-active">Active</span>' ?></td>
        <td><?= e(timeAgo($u['created_at'])) ?></td>
        <td>
          <?php if (!empty($u['suspended_at'])): ?>
            <form method="post" action="<?= url('/admin/users/' . (int) $u['id'] . '/restore') ?>" class="inline-form"><?= csrfField() ?><button class="btn btn-sm btn-outline" type="submit">Restore</button></form>
          <?php else: ?>
            <form method="post" action="<?= url('/admin/users/' . (int) $u['id'] . '/suspend') ?>" class="inline-form">
              <?= csrfField() ?>
              <input type="text" name="reason" placeholder="Reason" maxlength="500" style="border:1px solid var(--border);border-radius:8px;padding:5px 8px;width:130px">
              <button class="btn btn-sm btn-danger" type="submit" data-confirm="Suspend this user?">Suspend</button>
            </form>
          <?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
</div>
