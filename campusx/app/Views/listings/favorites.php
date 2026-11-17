<div class="container">
  <div class="page-head"><h1>My Favorites</h1></div>
  <?php if (!empty($listings)): ?>
    <div class="grid">
      <?php foreach ($listings as $l): ?>
        <article class="card">
          <a class="card-img" href="<?= url('/listings/' . (int) $l['id']) ?>"><img src="<?= e(listingCoverUrl($l['cover_path'] ?? null)) ?>" alt="<?= e($l['title']) ?>" loading="lazy"></a>
          <div class="card-body">
            <a class="card-title" href="<?= url('/listings/' . (int) $l['id']) ?>"><?= e($l['title']) ?></a>
            <div class="card-price"><?= e(formatMoney($l['price'], $l['currency'])) ?></div>
            <div class="card-meta"><span><?= e($l['seller_name']) ?></span><span class="badge badge-<?= e($l['status']) ?>"><?= e(ucfirst($l['status'])) ?></span></div>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  <?php else: ?>
    <div class="empty"><div class="big">☆</div><p>No favorites yet. Tap “Save to favorites” on any listing.</p><a class="btn btn-primary" href="<?= url('/marketplace') ?>">Browse marketplace</a></div>
  <?php endif; ?>
</div>
