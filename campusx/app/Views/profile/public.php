<div class="container">
  <div class="page-head">
    <h1>Public Profile</h1>
  </div>

  <section class="panel public-profile-header" style="padding:30px;margin-bottom:24px">
    <div style="display:flex;align-items:center;gap:24px;flex-wrap:wrap">
      <?php if (!empty($profile['avatar_path'])): ?>
        <img
          src="<?= e(\SdutSwap\Core\Upload::publicPath((string) $profile['avatar_path'])) ?>"
          alt="<?= e($profile['name']) ?>'s profile picture"
          style="width:120px;height:120px;object-fit:cover;border-radius:50%"
        >
      <?php else: ?>
        <div style="width:120px;height:120px;border-radius:50%;background:var(--surface-2,#eee);display:flex;align-items:center;justify-content:center">
          No photo
        </div>
      <?php endif; ?>

      <div style="flex:1;min-width:200px">
        <h2 style="margin:0 0 8px"><?= e($profile['name']) ?></h2>

        <?php if (!empty($profile['is_verified'])): ?>
          <span class="badge badge-active">✓ Verified Student</span>
        <?php endif; ?>

        <?php if (!empty($profile['university'])): ?>
          <p class="muted" style="margin:10px 0"><?= e($profile['university']) ?></p>
        <?php endif; ?>

        <p class="muted small">
          Member since <?= e(displayDate($profile['created_at'] ?? null)) ?>
        </p>
      </div>
    </div>
  </section>

  <section style="display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:16px;margin-bottom:28px">
    <div class="panel" style="padding:20px;text-align:center">
      <div class="muted small">Active Listings</div>
      <h2 style="margin:8px 0"><?= (int) ($stats['active_listings'] ?? 0) ?></h2>
    </div>

    <div class="panel" style="padding:20px;text-align:center">
      <div class="muted small">Sold Listings</div>
      <h2 style="margin:8px 0"><?= (int) ($stats['sold_listings'] ?? 0) ?></h2>
    </div>

    <div class="panel" style="padding:20px;text-align:center">
      <div class="muted small">Total Views</div>
      <h2 style="margin:8px 0"><?= number_format((int) ($stats['total_views'] ?? 0)) ?></h2>
    </div>
  </section>

  <?php if (!empty($profile['bio'])): ?>
    <section class="panel" style="padding:24px;margin-bottom:28px">
      <h3>About</h3>
      <p><?= nl2br(e($profile['bio'])) ?></p>
    </section>
  <?php endif; ?>

  <section style="margin-bottom:30px">
    <div class="page-head">
      <h2>Active Listings</h2>
    </div>

    <?php if (!empty($listings)): ?>
      <div class="grid marketplace-grid">
        <?php foreach ($listings as $l): ?>
          <article class="card marketplace-card">
            <a class="card-img marketplace-card-image" href="<?= url('/listings/' . (int) $l['id']) ?>">
              <img
                src="<?= e(listingCoverUrl($l['cover_path'] ?? null)) ?>"
                alt="<?= e($l['title']) ?>"
                loading="lazy"
              >
            </a>

            <div class="card-body">
              <div class="marketplace-card-top">
                <span class="marketplace-condition">
                  <?= e(ucfirst(str_replace('_', ' ', $l['item_condition'] ?? 'good'))) ?>
                </span>
              </div>

              <a class="card-title marketplace-product-title" href="<?= url('/listings/' . (int) $l['id']) ?>">
                <?= e($l['title']) ?>
              </a>

              <div class="card-price marketplace-product-price">
                <?= e(formatMoney($l['price'], $l['currency'])) ?>
              </div>

              <?php if (!empty($l['category_name'])): ?>
                <div class="card-meta marketplace-product-meta muted">
                  <?= e($l['category_name']) ?>
                </div>
              <?php endif; ?>

              <div class="card-meta muted small">
                <?= e(timeAgo($l['created_at'])) ?>
              </div>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <div class="panel" style="padding:30px;text-align:center">
        <p class="muted">This user has no active listings yet.</p>
      </div>
    <?php endif; ?>
  </section>
</div>
