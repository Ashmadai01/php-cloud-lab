<div class="container">
  <div class="page-head">
    <nav class="breadcrumb" aria-label="Breadcrumb"><a href="<?= url('/') ?>">Home</a> / <a href="<?= url('/marketplace') ?>">Marketplace</a> / <?= e($listing['title']) ?></nav>
  </div>
  <div class="split">
    <div>
      <div class="gallery-main">
        <?php if (!empty($images)): ?>
          <img src="<?= e(\SdutSwap\Core\Upload::publicPath($images[0]['file_path'])) ?>" alt="<?= e($listing['title']) ?>">
        <?php else: ?>
          <img src="<?= e(asset('img/placeholder.svg')) ?>" alt="No photos yet">
        <?php endif; ?>
      </div>
      <?php if (!empty($images)): ?>
        <div class="thumbs">
          <?php foreach ($images as $idx => $img): ?>
            <button type="button" class="<?= $idx === 0 ? 'active' : '' ?>" data-full="<?= e(\SdutSwap\Core\Upload::publicPath($img['file_path'])) ?>" aria-label="View photo <?= $idx + 1 ?>">
              <img src="<?= e(\SdutSwap\Core\Upload::publicPath($img['file_path'])) ?>" alt="">
            </button>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
      <div class="panel mt">
        <h3>Description</h3>
        <p><?= nl2br(e($listing['description'])) ?></p>
        <p class="muted small">Posted <?= e(displayDate($listing['created_at'])) ?> · <?= (int) $listing['views'] ?> views</p>
      </div>
    </div>
    <div>
      <div class="panel">
        <div class="flex" style="justify-content:space-between">
          <span class="badge badge-<?= e($listing['status']) ?>"><?= e(ucfirst($listing['status'])) ?></span>
          <span class="muted small"><?= e($listing['category_name'] ?? '') ?></span>
        </div>
        <h1 style="margin:10px 0 4px;font-size:1.5rem"><?= e($listing['title']) ?></h1>
        <div style="font-size:1.8rem;font-weight:800;color:var(--navy)"><?= e(formatMoney($listing['price'], $listing['currency'])) ?></div>
        <dl class="small mt">
          <dt><strong>Condition:</strong> <?= e(ucfirst(str_replace('_', ' ', $listing['item_condition']))) ?></dt>
          <?php if (!empty($listing['campus'])): ?><dt><strong>Location:</strong> <?= e($listing['campus']) ?></dt><?php endif; ?>
        </dl>
        <hr>
        <h3>Seller</h3>
        <div class="flex mb" style="align-items:center;gap:12px">
          <a href="<?= url('/users/' . (int) $listing['seller_id']) ?>" style="display:flex;align-items:center;gap:12px;text-decoration:none">
            <?php if (!empty($listing['seller_avatar'])): ?>
              <img src="<?= e(\SdutSwap\Core\Upload::publicPath($listing['seller_avatar'])) ?>" alt="<?= e($listing['seller_name']) ?>" style="width:52px;height:52px;border-radius:50%;object-fit:cover">
            <?php else: ?>
              <span style="width:52px;height:52px;border-radius:50%;background:var(--surface);display:flex;align-items:center;justify-content:center;font-size:1.3rem">👤</span>
            <?php endif; ?>
            <strong><?= e($listing['seller_name']) ?></strong>
          </a>
          <?php if (!empty($listing['seller_verified'])): ?><span class="verified-badge">✓ Verified student</span><?php endif; ?>
        </div>
        <?php if (!empty($listing['seller_university'])): ?>
          <p class="muted small"><?= e($listing['seller_university']) ?></p>
        <?php endif; ?>
        <?php if (!empty($user) && !$isOwner && $listing['status'] === 'active'): ?>
          <form method="post" action="<?= url('/listings/' . (int) $listing['id'] . '/message') ?>">
            <?= csrfField() ?>
            <button class="btn btn-primary btn-block" type="submit">💬 Message Seller</button>
          </form>
          <form method="post" action="<?= url('/listings/' . (int) $listing['id'] . '/favorite') ?>" class="mt" data-favorite-form>
            <?= csrfField() ?>
            <button class="btn btn-outline btn-block" type="submit" aria-pressed="<?= $isFavorite ? 'true' : 'false' ?>"><?= $isFavorite ? '★ Saved to favorites' : '☆ Save to favorites' ?></button>
          </form>
          <p class="mt"><a href="<?= url('/report/listing/' . (int) $listing['id']) ?>">🚩 Report this listing</a></p>
        <?php elseif (empty($user)): ?>
          <a class="btn btn-primary btn-block" href="<?= url('/login') ?>">Sign in to message seller</a>
        <?php endif; ?>
        <?php if ($isOwner): ?>
          <div class="flex mt">
            <a class="btn btn-outline" href="<?= url('/listings/' . (int) $listing['id'] . '/edit') ?>">Edit listing</a>
            <form method="post" action="<?= url('/listings/' . (int) $listing['id'] . '/delete') ?>" class="inline-form">
              <?= csrfField() ?>
              <button class="btn btn-danger" type="submit" data-confirm="Remove this listing? This cannot be undone.">Remove</button>
            </form>
          </div>
        <?php endif; ?>
        <p class="muted small mt">⚠️ Meet on campus in a public place. Inspect the item before paying. Never share bank details in chat.</p>
      </div>
    </div>
  </div>
</div>
