<div class="container">
  <div class="page-head">
    <nav class="breadcrumb" aria-label="Breadcrumb"><a href="<?= url('/') ?>">Home</a> / Marketplace</nav>
    <h1>Marketplace</h1>
    <p class="muted"><?= (int) ($total ?? 0) ?> active listings</p>
  </div>

  <div class="filters">
    <form method="get" action="<?= url('/marketplace') ?>">
      <div>
        <label for="q">Search</label>
        <input id="q" type="search" name="q" value="<?= e($filters['q'] ?? '') ?>" placeholder="Keywords…">
      </div>
      <div>
        <label for="category_id">Category</label>
        <select id="category_id" name="category_id">
          <option value="">All categories</option>
          <?php foreach (($categories ?? []) as $c): ?>
            <option value="<?= (int) $c['id'] ?>" <?= (string) ($filters['category_id'] ?? '') === (string) $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label for="condition">Condition</label>
        <select id="condition" name="condition">
          <option value="">Any</option>
          <?php foreach (['new' => 'New', 'like_new' => 'Like new', 'good' => 'Good', 'fair' => 'Fair', 'poor' => 'Poor'] as $k => $label): ?>
            <option value="<?= e($k) ?>" <?= ($filters['condition'] ?? '') === $k ? 'selected' : '' ?>><?= e($label) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label for="min_price">Min price (¥)</label>
        <input id="min_price" type="number" name="min_price" min="0" step="0.01" value="<?= e($filters['min_price'] ?? '') ?>">
      </div>
      <div>
        <label for="max_price">Max price (¥)</label>
        <input id="max_price" type="number" name="max_price" min="0" step="0.01" value="<?= e($filters['max_price'] ?? '') ?>">
      </div>
      <div>
        <label for="sort">Sort</label>
        <select id="sort" name="sort">
          <?php foreach (['newest' => 'Newest', 'oldest' => 'Oldest', 'price_asc' => 'Price: low to high', 'price_desc' => 'Price: high to low', 'popular' => 'Most viewed'] as $k => $label): ?>
            <option value="<?= e($k) ?>" <?= ($filters['sort'] ?? 'newest') === $k ? 'selected' : '' ?>><?= e($label) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <button class="btn btn-primary" type="submit">Apply</button>
    </form>
  </div>

  <?php if (!empty($listings)): ?>
    <div class="grid">
      <?php foreach ($listings as $l): ?>
        <article class="card">
          <a class="card-img" href="<?= url('/listings/' . (int) $l['id']) ?>"><img src="<?= e(listingCoverUrl($l['cover_path'] ?? null)) ?>" alt="<?= e($l['title']) ?>" loading="lazy"></a>
          <div class="card-body">
            <a class="card-title" href="<?= url('/listings/' . (int) $l['id']) ?>"><?= e($l['title']) ?></a>
            <div class="card-price"><?= e(formatMoney($l['price'], $l['currency'])) ?></div>
            <div class="card-meta">
              <span><?= e($l['seller_name']) ?></span>
              <?php if (!empty($l['seller_verified'])): ?><span class="verified-badge">✓ Verified</span><?php endif; ?>
              <span><?= e(ucfirst(str_replace('_', ' ', $l['item_condition']))) ?></span>
              <span><?= e(timeAgo($l['created_at'])) ?></span>
            </div>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
    <?php if (($totalPages ?? 1) > 1): ?>
      <nav class="pagination" aria-label="Pagination">
        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
          <?php if ($i === $page): ?><span class="current"><?= $i ?></span>
          <?php else: ?>
            <a href="<?= url('/marketplace?' . http_build_query(array_merge($filters, ['page' => $i]))) ?>"><?= $i ?></a>
          <?php endif; ?>
        <?php endfor; ?>
      </nav>
    <?php endif; ?>
  <?php else: ?>
    <div class="empty"><div class="big">🔍</div><p>No listings match your filters.</p><a class="btn btn-outline" href="<?= url('/marketplace') ?>">Clear filters</a></div>
  <?php endif; ?>
</div>
