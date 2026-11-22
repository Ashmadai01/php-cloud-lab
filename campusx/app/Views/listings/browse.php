
<div class="container marketplace-page">
  <div class="marketplace-hero">
    <div class="marketplace-hero-overlay"></div>
    <div class="marketplace-hero-content">
      <span class="marketplace-eyebrow">SDUT STUDENT COMMUNITY</span>
      <h1>Campus Marketplace</h1>
      <p>Discover, buy and sell within your campus community.</p>
      <div class="marketplace-hero-stats">
        <strong><?= (int) ($total ?? 0) ?></strong>
        <span>Active listings</span>
      </div>
    </div>
  </div>

  <div class="marketplace-header">
    <nav class="breadcrumb" aria-label="Breadcrumb">
      <a href="<?= url('/') ?>">Home</a> / Marketplace
    </nav>

    <div class="marketplace-heading">
      <div>
        <span class="marketplace-eyebrow">SDUT STUDENT COMMUNITY</span>
        <h1>Campus Marketplace</h1>
        <p class="muted">Discover, buy and sell within your campus community.</p >
      </div>
      <div class="marketplace-count">
        <strong><?= (int) ($total ?? 0) ?></strong>
        <span>Active listings</span>
      </div>
    </div>
  </div>

  <section class="marketplace-filters">
    <div class="filters-heading">
      <div>
        <h2>Find what you need</h2>
        <p class="muted">Search and refine your results.</p >
      </div>
    </div>

    <form method="get" action="<?= url('/marketplace') ?>" class="marketplace-filter-form">
      <div class="filter-search">
        <label for="q">Search products</label>
        <input id="q" type="search" name="q"
          value="<?= e($filters['q'] ?? '') ?>"
          placeholder="What are you looking for?">
      </div>

      <div>
        <label for="category_id">Category</label>
        <select id="category_id" name="category_id">
          <option value="">All categories</option>
          <?php foreach (($categories ?? []) as $c): ?>
            <option value="<?= (int) $c['id'] ?>" <?= (string) ($filters['category_id'] ?? '') === (string) $c['id'] ? 'selected' : '' ?>>
              <?= e($c['name']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div>
        <label for="condition">Condition</label>
        <select id="condition" name="condition">
          <option value="">Any condition</option>
          <?php foreach (['new' => 'New', 'like_new' => 'Like new', 'good' => 'Good', 'fair' => 'Fair', 'poor' => 'Poor'] as $k => $label): ?>
            <option value="<?= e($k) ?>" <?= ($filters['condition'] ?? '') === $k ? 'selected' : '' ?>>
              <?= e($label) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div>
        <label for="min_price">Minimum price (¥)</label>
        <input id="min_price" type="number" name="min_price" min="0" step="0.01"
          value="<?= e($filters['min_price'] ?? '') ?>" placeholder="0">
      </div>

      <div>
        <label for="max_price">Maximum price (¥)</label>
        <input id="max_price" type="number" name="max_price" min="0" step="0.01"
          value="<?= e($filters['max_price'] ?? '') ?>" placeholder="Any">
      </div>

      <div>
        <label for="sort">Sort by</label>
        <select id="sort" name="sort">
          <?php foreach (['newest' => 'Newest', 'oldest' => 'Oldest', 'price_asc' => 'Price: low to high', 'price_desc' => 'Price: high to low', 'popular' => 'Most viewed'] as $k => $label): ?>
            <option value="<?= e($k) ?>" <?= ($filters['sort'] ?? 'newest') === $k ? 'selected' : '' ?>>
              <?= e($label) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="filter-actions">
        <button class="btn btn-primary" type="submit">Apply filters</button>
        <a class="marketplace-clear" href="<?= url('/marketplace') ?>">Clear</a >
      </div>
    </form>
  </section>

  <div class="marketplace-results-heading">
    <h2>Explore listings</h2>
    <span><?= (int) ($total ?? 0) ?> results</span>
  </div>

  <?php if (!empty($listings)): ?>
    <div class="grid marketplace-grid">
      <?php foreach ($listings as $l): ?>
        <article class="card marketplace-card">
          <a class="card-img marketplace-card-image"
             href="<?= url('/listings/' . (int) $l['id']) ?>">
            <img
              src="<?= e(listingCoverUrl($l['cover_path'] ?? null)) ?>"
              alt="<?= e($l['title']) ?>"
              loading="lazy">
          </a >

          <div class="card-body">
            <div class="marketplace-card-top">
              <span class="marketplace-condition">
                <?= e(ucfirst(str_replace('_', ' ', $l['item_condition']))) ?>
              </span>
              <?php if (!empty($l['seller_verified'])): ?>
                <span class="verified-badge">✓ Verified</span>
              <?php endif; ?>
            </div>

            <a class="card-title marketplace-product-title"
               href="<?= url('/listings/' . (int) $l['id']) ?>">
              <?= e($l['title']) ?>
            </a >

            <div class="card-price marketplace-product-price">
              <?= e(formatMoney($l['price'], $l['currency'])) ?>
            </div>

            <div class="card-meta marketplace-product-meta">
              <a href="<?= url('/users/' . (int) $l['seller_id']) ?>" style="display:flex;align-items:center;gap:8px;text-decoration:none">
                <?php if (!empty($l['seller_avatar'])): ?>
                  <img src="<?= e(\SdutSwap\Core\Upload::publicPath($l['seller_avatar'])) ?>" alt="<?= e($l['seller_name']) ?>" style="width:30px;height:30px;border-radius:50%;object-fit:cover">
                <?php else: ?>
                  <span style="width:30px;height:30px;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;background:var(--surface)">👤</span>
                <?php endif; ?>
                <span><?= e($l['seller_name']) ?></span>
              </a>
              <span><?= e(timeAgo($l['created_at'])) ?></span>
            </div>
          </div>
        </article>
      <?php endforeach; ?>
    </div>

    <?php if (($totalPages ?? 1) > 1): ?>
      <nav class="pagination marketplace-pagination" aria-label="Pagination">
        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
          <?php if ($i === $page): ?>
            <span class="current"><?= $i ?></span>
          <?php else: ?>
            <a href="<?= url('/marketplace?' . http_build_query(array_merge($filters, ['page' => $i]))) ?>">
              <?= $i ?>
            </a >
          <?php endif; ?>
        <?php endfor; ?>
      </nav>
    <?php endif; ?>

  <?php else: ?>
    <div class="empty marketplace-empty">
      <div class="big">⌕</div>
      <h3>No listings found</h3>
      <p>Try changing your search or adjusting the filters.</p >
      <a class="btn btn-outline" href="<?= url('/marketplace') ?>">Clear all filters</a >
    </div>
  <?php endif; ?>
</div>
