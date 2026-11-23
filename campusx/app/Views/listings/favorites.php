<div class="container favorites-page">
  <header class="favorites-header">
    <div>
      <span class="favorites-eyebrow">YOUR COLLECTION</span>
      <h1>My Favorites</h1>
      <p>Keep the listings you love close at hand.</p>
    </div>
    <a class="btn btn-primary" href="<?= url('/marketplace') ?>">Browse marketplace <span aria-hidden="true">→</span></a>
  </header>

  <section class="favorites-panel" aria-label="Saved listings">
    <div class="favorites-toolbar">
      <div>
        <h2>Saved listings <span class="favorites-count" data-favorites-count><?= count($listings) ?></span></h2>
        <p>Your shortlist, all in one place.</p>
      </div>
      <?php if (!empty($listings)): ?>
        <label class="favorites-search"><span aria-hidden="true">⌕</span><input type="search" id="favoritesSearch" placeholder="Search your favorites…" aria-label="Search favorites"></label>
      <?php endif; ?>
    </div>

    <?php if (!empty($listings)): ?>
      <div class="favorites-grid" data-favorites-grid>
        <?php foreach ($listings as $l): ?>
          <article class="favorite-card" data-favorite-card data-title="<?= e(mb_strtolower($l['title'])) ?>">
            <a class="favorite-image" href="<?= url('/listings/' . (int) $l['id']) ?>">
              <img src="<?= e(listingCoverUrl($l['cover_path'] ?? null)) ?>" alt="<?= e($l['title']) ?>" loading="lazy">
              <span class="favorite-image-label">Saved item</span>
            </a>
            <div class="favorite-card-body">
              <div class="favorite-card-top">
                <span class="favorite-seller"><?= e($l['seller_name']) ?></span>
                <span class="badge badge-<?= e($l['status']) ?>"><?= e(ucfirst($l['status'])) ?></span>
              </div>
              <a class="favorite-title" href="<?= url('/listings/' . (int) $l['id']) ?>"><?= e($l['title']) ?></a>
              <div class="favorite-card-bottom">
                <strong class="favorite-price"><?= e(formatMoney($l['price'], $l['currency'])) ?></strong>
                <form method="post" action="<?= url('/listings/' . (int) $l['id'] . '/favorite') ?>" data-favorite-form>
                  <?= csrfField() ?>
                  <button class="favorite-remove" type="submit" aria-label="Remove <?= e($l['title']) ?> from favorites" aria-pressed="true" title="Remove from favorites">♥ <span>Remove</span></button>
                </form>
              </div>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
      <div class="favorites-no-results" data-favorites-no-results hidden>
        <strong>No matching favorites</strong>
        <p>Try another search term.</p>
      </div>
    <?php endif; ?>

    <div class="favorites-empty" data-favorites-empty <?= !empty($listings) ? 'hidden' : '' ?>>
      <div class="favorites-empty-icon" aria-hidden="true">♡</div>
      <h2>Your favorites list is empty</h2>
      <p>When you find something you like, save it here and come back anytime.</p>
      <a class="btn btn-primary" href="<?= url('/marketplace') ?>">Explore the marketplace</a>
    </div>
  </section>
</div>
