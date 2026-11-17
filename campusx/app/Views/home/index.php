<section class="hero">
  <div class="container">
    <h1>Your Campus. Your Marketplace.</h1>
    <p>Buy and sell textbooks, electronics, dorm essentials, and more — with verified students from your university. Meet on campus, pay in person, trade with confidence.</p>
    <form class="search-bar" method="get" action="<?= url('/marketplace') ?>" role="search">
      <label class="skip-link" for="hero-q">Search listings</label>
      <input id="hero-q" type="search" name="q" placeholder="Search textbooks, laptops, furniture…" autocomplete="off">
      <button class="btn btn-primary" type="submit">Search</button>
    </form>
    <div class="hero-stats">
      <div><strong>Verified</strong>student community</div>
      <div><strong>On-campus</strong>meetups only</div>
      <div><strong>Free</strong>to list &amp; browse</div>
    </div>
  </div>
</section>

<section class="section">
  <div class="container">
    <h2>Browse by category</h2>
    <p class="sub">Find what you need, from classmates you can trust.</p>
    <div class="flex">
      <?php foreach (($categories ?? []) as $c): ?>
        <a class="btn btn-outline" href="<?= url('/marketplace?category_id=' . (int) $c['id']) ?>"><?= e($c['name']) ?></a>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="section" style="padding-top:0">
  <div class="container">
    <h2>Featured listings</h2>
    <p class="sub">Popular items from verified sellers.</p>
    <?php if (!empty($featured)): ?>
      <div class="grid">
        <?php foreach ($featured as $l): ?>
          <article class="card">
            <a class="card-img" href="<?= url('/listings/' . (int) $l['id']) ?>">
              <?php $imgs = []; ?>
              <img src="<?= e(listingCoverUrl($l['cover_path'] ?? null)) ?>" alt="<?= e($l['title']) ?>" loading="lazy">
            </a>
            <div class="card-body">
              <a class="card-title" href="<?= url('/listings/' . (int) $l['id']) ?>"><?= e($l['title']) ?></a>
              <div class="card-price"><?= e(formatMoney($l['price'], $l['currency'])) ?></div>
              <div class="card-meta">
                <span><?= e($l['seller_name']) ?></span>
                <?php if (!empty($l['seller_verified'])): ?><span class="verified-badge">✓ Verified</span><?php endif; ?>
                <span><?= e(timeAgo($l['created_at'])) ?></span>
              </div>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <div class="empty"><div class="big">🛒</div><p>No listings yet. <a href="<?= url('/sell') ?>">Be the first to sell!</a></p></div>
    <?php endif; ?>
  </div>
</section>

<section class="section" style="padding-top:0">
  <div class="container">
    <h2>Latest listings</h2>
    <p class="sub">Fresh on the marketplace.</p>
    <?php if (!empty($latest)): ?>
      <div class="grid">
        <?php foreach ($latest as $l): ?>
          <article class="card">
            <a class="card-img" href="<?= url('/listings/' . (int) $l['id']) ?>"><img src="<?= e(listingCoverUrl($l['cover_path'] ?? null)) ?>" alt="<?= e($l['title']) ?>" loading="lazy"></a>
            <div class="card-body">
              <a class="card-title" href="<?= url('/listings/' . (int) $l['id']) ?>"><?= e($l['title']) ?></a>
              <div class="card-price"><?= e(formatMoney($l['price'], $l['currency'])) ?></div>
              <div class="card-meta"><span><?= e($l['seller_name']) ?></span><span><?= e(timeAgo($l['created_at'])) ?></span></div>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <div class="empty"><p>No listings yet.</p></div>
    <?php endif; ?>
    <div class="mt" style="text-align:center"><a class="btn btn-navy" href="<?= url('/marketplace') ?>">Browse all listings</a></div>
  </div>
</section>

<section class="section" style="padding-top:0">
  <div class="container">
    <h2>Trust &amp; safety</h2>
    <p class="sub">SDUT Swap is built for students, by students — with safety first.</p>
    <div class="trust-grid">
      <div class="trust-card"><div class="icon">🪪</div><h3>Verified students</h3><p class="muted">Sellers verify their Student Card before listing, so you know you're trading with a real classmate.</p></div>
      <div class="trust-card"><div class="icon">💬</div><h3>On-platform chat</h3><p class="muted">Keep conversations inside SDUT Swap. Never share personal contact details with strangers.</p></div>
      <div class="trust-card"><div class="icon">🤝</div><h3>Meet on campus</h3><p class="muted">Always meet in a public, well-lit campus location. Inspect items before you pay.</p></div>
      <div class="trust-card"><div class="icon">🛡️</div><h3>Report &amp; support</h3><p class="muted">Report suspicious listings or users anytime. Our support team reviews every report.</p></div>
    </div>
  </div>
</section>

<section class="section" style="padding-top:0">
  <div class="container">
    <div class="cta">
      <h2>Ready to buy or sell?</h2>
      <p>Join thousands of students trading safely on campus.</p>
      <div class="flex" style="justify-content:center">
        <a class="btn btn-primary" href="<?= url('/register') ?>">Create free account</a>
        <a class="btn btn-outline" href="<?= url('/marketplace') ?>" style="background:#fff">Browse listings</a>
      </div>
    </div>
  </div>
</section>
