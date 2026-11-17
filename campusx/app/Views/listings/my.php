<div class="container">
  <div class="page-head"><h1>My Listings</h1><p class="muted">Manage your items for sale.</p></div>
  <?php if (!empty($listings)): ?>
    <div class="table-wrap"><table>
      <thead><tr><th>Title</th><th>Price</th><th>Status</th><th>Views</th><th>Posted</th><th>Actions</th></tr></thead>
      <tbody>
      <?php foreach ($listings as $l): ?>
        <tr>
          <td><a href="<?= url('/listings/' . (int) $l['id']) ?>"><?= e($l['title']) ?></a></td>
          <td><?= e(formatMoney($l['price'], $l['currency'])) ?></td>
          <td><span class="badge badge-<?= e($l['status']) ?>"><?= e(ucfirst($l['status'])) ?></span></td>
          <td><?= (int) $l['views'] ?></td>
          <td><?= e(timeAgo($l['created_at'])) ?></td>
          <td>
            <a class="btn btn-sm btn-outline" href="<?= url('/listings/' . (int) $l['id'] . '/edit') ?>">Edit</a>
            <form method="post" action="<?= url('/listings/' . (int) $l['id'] . '/delete') ?>" class="inline-form"><?= csrfField() ?><button class="btn btn-sm btn-danger" type="submit" data-confirm="Remove this listing?">Remove</button></form>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table></div>
  <?php else: ?>
    <div class="empty"><div class="big">📦</div><p>You haven't listed anything yet.</p><a class="btn btn-primary" href="<?= url('/sell') ?>">Sell your first item</a></div>
  <?php endif; ?>
</div>
