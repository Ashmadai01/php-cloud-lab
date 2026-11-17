<div class="container">
  <div class="page-head"><h1>Moderate Listings</h1></div>
  <nav class="admin-nav"><a href="<?= url('/admin') ?>">Overview</a><a href="<?= url('/admin/users') ?>">Users</a><a href="<?= url('/admin/listings') ?>" class="active">Listings</a><a href="<?= url('/admin/verifications') ?>">Verifications</a><a href="<?= url('/admin/tickets') ?>">Tickets</a><a href="<?= url('/admin/reports') ?>">Reports</a><a href="<?= url('/admin/audit') ?>">Audit Log</a><a href="<?= url('/admin/security') ?>">Security Events</a></nav>
  <form method="get" action="<?= url('/admin/listings') ?>" class="flex mb">
    <select name="status" style="border:1px solid var(--border);border-radius:10px;padding:10px 12px">
      <option value="">All statuses</option>
      <?php foreach (['draft', 'active', 'sold', 'hidden', 'removed'] as $s): ?><option value="<?= $s ?>" <?= ($status ?? '') === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option><?php endforeach; ?>
    </select>
    <button class="btn btn-outline" type="submit">Filter</button>
  </form>
  <div class="table-wrap"><table>
    <thead><tr><th>Title</th><th>Seller</th><th>Price</th><th>Status</th><th>Posted</th><th>Moderate</th></tr></thead>
    <tbody>
    <?php foreach (($listings ?? []) as $l): ?>
      <tr>
        <td><a href="<?= url('/listings/' . (int) $l['id']) ?>"><?= e($l['title']) ?></a></td>
        <td><?= e($l['seller_name']) ?><br><span class="muted small"><?= e($l['seller_email']) ?></span></td>
        <td><?= e(formatMoney($l['price'], $l['currency'])) ?></td>
        <td><span class="badge badge-<?= e($l['status']) ?>"><?= e(ucfirst($l['status'])) ?></span></td>
        <td><?= e(timeAgo($l['created_at'])) ?></td>
        <td>
          <form method="post" action="<?= url('/admin/listings/' . (int) $l['id'] . '/moderate') ?>" class="inline-form">
            <?= csrfField() ?>
            <input type="text" name="reason" placeholder="Reason" maxlength="500" style="border:1px solid var(--border);border-radius:8px;padding:5px 8px;width:110px">
            <button class="btn btn-sm btn-outline" name="action" value="hide" type="submit">Hide</button>
            <button class="btn btn-sm btn-danger" name="action" value="remove" type="submit" data-confirm="Remove this listing?">Remove</button>
            <button class="btn btn-sm btn-outline" name="action" value="restore" type="submit">Restore</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
</div>
