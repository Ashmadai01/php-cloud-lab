<?php
$stats = $stats ?? [];
$totalListings = (int) ($stats['total_listings'] ?? 0);
$activeListings = (int) ($stats['active_listings'] ?? 0);
$soldListings = (int) ($stats['sold_listings'] ?? 0);
$draftListings = (int) ($stats['draft_listings'] ?? 0);
$hiddenListings = (int) ($stats['hidden_listings'] ?? 0);
$totalViews = (int) ($stats['total_views'] ?? 0);
?>
<div class="container my-listings-page">
  <header class="my-listings-header">
    <div>
      <span class="my-listings-eyebrow">SELLER CENTER</span>
      <h1>My Listings</h1>
      <p class="muted">Track your items, monitor activity and manage your sales.</p>
    </div>
    <a class="btn btn-primary" href="<?= url('/sell') ?>">＋ Add new listing</a>
  </header>

  <section class="listing-stats" aria-label="Listing overview">
    <article class="listing-stat-card"><span class="listing-stat-icon">▦</span><div><span class="listing-stat-label">Total listings</span><strong><?= $totalListings ?></strong></div></article>
    <article class="listing-stat-card"><span class="listing-stat-icon is-active">●</span><div><span class="listing-stat-label">Active</span><strong><?= $activeListings ?></strong></div></article>
    <article class="listing-stat-card"><span class="listing-stat-icon is-sold">✓</span><div><span class="listing-stat-label">Sold</span><strong><?= $soldListings ?></strong></div></article>
    <article class="listing-stat-card"><span class="listing-stat-icon is-draft">✎</span><div><span class="listing-stat-label">Drafts</span><strong><?= $draftListings ?></strong></div></article>
    <article class="listing-stat-card"><span class="listing-stat-icon is-views">◉</span><div><span class="listing-stat-label">Total views</span><strong><?= $totalViews ?></strong></div></article>
  </section>

  <section class="my-listings-panel">
    <div class="my-listings-toolbar">
      <div><h2>Your products</h2><p class="muted">A quick overview of your marketplace listings.</p></div>
      <label class="listing-search"><span aria-hidden="true">⌕</span><input id="listingSearch" type="search" placeholder="Search your listings..." aria-label="Search your listings"></label>
    </div>
    <nav class="listing-filter-tabs" aria-label="Filter listings">
      <button class="listing-filter is-selected" type="button" data-filter="all">All <span><?= $totalListings ?></span></button>
      <button class="listing-filter" type="button" data-filter="active">Active <span><?= $activeListings ?></span></button>
      <button class="listing-filter" type="button" data-filter="sold">Sold <span><?= $soldListings ?></span></button>
      <button class="listing-filter" type="button" data-filter="draft">Draft <span><?= $draftListings ?></span></button>
      <button class="listing-filter" type="button" data-filter="hidden">Hidden <span><?= $hiddenListings ?></span></button>
    </nav>

    <?php if (!empty($listings)): ?>
      <div class="listing-table-wrap">
        <table class="my-listings-table" id="myListingsTable">
          <thead><tr><th>Product</th><th>Price</th><th>Status</th><th>Views</th><th>Date listed</th><th>Actions</th></tr></thead>
          <tbody>
          <?php foreach ($listings as $l): ?>
            <tr data-status="<?= e($l['status']) ?>" data-title="<?= e(mb_strtolower($l['title'])) ?>">
              <td>
                <div class="listing-product-cell">
                  <img src="<?= e(listingCoverUrl($l['cover_path'] ?? null)) ?>" alt="<?= e($l['title']) ?>" loading="lazy">
                  <div><a class="listing-product-title" href="<?= url('/listings/' . (int) $l['id']) ?>"><?= e($l['title']) ?></a>
                    <?php if (!empty($l['category_name'])): ?><span><?= e($l['category_name']) ?></span><?php endif; ?>
                  </div>
                </div>
              </td>
              <td class="listing-price"><?= e(formatMoney($l['price'], $l['currency'])) ?></td>
              <td><span class="badge badge-<?= e($l['status']) ?>"><?= e(ucfirst($l['status'])) ?></span></td>
              <td><span class="listing-views">◉ <?= (int) $l['views'] ?></span></td>
              <td class="listing-date"><?= e(timeAgo($l['created_at'])) ?></td>
              <td><div class="listing-row-actions">
                <a class="btn btn-sm btn-outline" href="<?= url('/listings/' . (int) $l['id']) ?>">View</a>
                <a class="btn btn-sm btn-outline" href="<?= url('/listings/' . (int) $l['id'] . '/edit') ?>">Edit</a>
                <form method="post" action="<?= url('/listings/' . (int) $l['id'] . '/delete') ?>" class="inline-form"><?= csrfField() ?><button class="btn btn-sm btn-danger" type="submit" data-confirm="Remove this listing?">Remove</button></form>
              </div></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
        <div class="listing-no-results" id="listingNoResults" hidden>No listings match your search.</div>
      </div>
    <?php else: ?>
      <div class="empty listing-empty"><div class="big">📦</div><h3>No listings yet</h3><p>You haven't listed anything for sale. Add your first product to get started.</p><a class="btn btn-primary" href="<?= url('/sell') ?>">Sell your first item</a></div>
    <?php endif; ?>
  </section>
</div>

