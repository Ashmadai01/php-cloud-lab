<section class="hero">
  <div class="container">
    <div class="hero-content">
      <span class="hero-label">SDUT STUDENT COMMUNITY</span>
      <h1>Campus Together.</h1>
      <p>
        Discover great deals, exchange essentials, and connect with
        verified students across your university.
      </p>

      <form class="search-bar" method="get"
            action="<?= url('/marketplace') ?>" role="search">
        <label class="skip-link" for="hero-q">Search listings</label>
        <input id="hero-q" type="search" name="q"
               placeholder="Search textbooks, laptops, furniture…"
               autocomplete="off">
        <button class="btn btn-primary" type="submit">Search →</button>
      </form>

      <div class="hero-stats">
        <div><strong>Verified</strong>Student community</div>
        <div><strong>On-campus</strong>Meetups</div>
        <div><strong>Free</strong>To buy and sell</div>
      </div>
    </div>
  </div>
</section>

<section class="section">
  <div class="container">
    <h2>Explore Categories</h2>
    <p class="sub">Everything students need, all in one place.</p>

    <div class="flex">
      <?php foreach (($categories ?? []) as $c): ?>
        <a class="btn btn-outline"
           href="<?= url('/marketplace?category_id=' . (int) $c['id']) ?>">
          <?= e($c['name']) ?>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="section" style="padding-top:0">
  <div class="container">
    <h2>Featured Listings</h2>
    <p class="sub">Discover popular items from your student community.</p>

    <?php if (!empty($featured)): ?>
      <div class="grid">
        <?php foreach ($featured as $l): ?>
          <article class="card">
            <a class="card-img" href="<?= url('/listings/' . (int) $l['id']) ?>">
              <img src="<?= e(listingCoverUrl($l['cover_path'] ?? null)) ?>"
                   alt="<?= e($l['title']) ?>" loading="lazy">
            </a>

            <div class="card-body">
              <a class="card-title"
                 href="<?= url('/listings/' . (int) $l['id']) ?>">
                <?= e($l['title']) ?>
              </a>

              <div class="card-price">
                <?= e(formatMoney($l['price'], $l['currency'])) ?>
              </div>

              <div class="card-meta">
                <span><?= e($l['seller_name']) ?></span>
                <?php if (!empty($l['seller_verified'])): ?>
                  <span class="verified-badge">✓ Verified</span>
                <?php endif; ?>
                <span><?= e(timeAgo($l['created_at'])) ?></span>
              </div>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <div class="empty">
        <div class="big">🛒</div>
        <p>No listings yet. <a href="<?= url('/sell') ?>">Be the first to sell!</a></p>
      </div>
    <?php endif; ?>
  </div>
</section>

<section class="section" style="padding-top:0">
  <div class="container">
    <h2>Recently Added</h2>
    <p class="sub">The latest items shared by students.</p>

    <?php if (!empty($latest)): ?>
      <div class="grid">
        <?php foreach ($latest as $l): ?>
          <article class="card">
            <a class="card-img" href="<?= url('/listings/' . (int) $l['id']) ?>">
              <img src="<?= e(listingCoverUrl($l['cover_path'] ?? null)) ?>"
                   alt="<?= e($l['title']) ?>" loading="lazy">
            </a>

            <div class="card-body">
              <a class="card-title"
                 href="<?= url('/listings/' . (int) $l['id']) ?>">
                <?= e($l['title']) ?>
              </a>

              <div class="card-price">
                <?= e(formatMoney($l['price'], $l['currency'])) ?>
              </div>

              <div class="card-meta">
                <span><?= e($l['seller_name']) ?></span>
                <span><?= e(timeAgo($l['created_at'])) ?></span>
              </div>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <div class="empty"><p>No listings yet.</p></div>
    <?php endif; ?>

    <div class="mt" style="text-align:center">
      <a class="btn btn-navy" href="<?= url('/marketplace') ?>">
        Explore All Listings →
      </a>
    </div>
  </div>
</section>

<section class="section" style="padding-top:0">
  <div class="container">
    <h2>Trade with Confidence</h2>
    <p class="sub">A student marketplace built around trust and campus safety.</p>

    <div class="trust-grid">
      <div class="trust-card">
        <div class="icon">🪪</div>
        <h3>Verified Students</h3>
        <p class="muted">Connect with students whose university identity has been verified.</p>
      </div>

      <div class="trust-card">
        <div class="icon">💬</div>
        <h3>Direct Messaging</h3>
        <p class="muted">Communicate with buyers and sellers through our platform.</p>
      </div>

      <div class="trust-card">
        <div class="icon">🤝</div>
        <h3>Campus Meetups</h3>
        <p class="muted">Meet in public campus locations and inspect items before paying.</p>
      </div>

      <div class="trust-card">
        <div class="icon">🛡️</div>
        <h3>Community Safety</h3>
        <p class="muted">Report suspicious listings or users to our support team.</p>
      </div>
    </div>
  </div>
</section>

<section class="section" style="padding-top:0">
  <div class="container">
    <div class="cta">
      <h2>Make Campus Exchanges Easier.</h2>
      <p>Buy smarter. Sell faster. Connect with your university community.</p>

      <div class="flex" style="justify-content:center">
        <a class="btn btn-primary" href="<?= url('/register') ?>">
          Join the Community
        </a>
        <a class="btn btn-outline" href="<?= url('/marketplace') ?>">
          Browse Marketplace
        </a>
      </div>
    </div>
  </div>
</section>