<!doctype html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($title ?? 'SDUT Swap') ?></title>
<meta name="description" content="SDUT Swap — Your Campus. Your Marketplace. Buy and sell with verified students on your campus.">
<?= csrfMeta() ?>
<link rel="stylesheet" href="<?= asset('css/app.css') ?>">
</head>
<body>
<a class="skip-link" href="#main">Skip to main content</a>
<header class="site-header">
  <div class="container header-inner">
    <a class="brand" href="<?= url('/') ?>">SDUT<span>Swap</span><span class="tagline">Your Campus. Your Marketplace.</span></a>
    <button class="mobile-toggle" aria-expanded="false" aria-controls="main-nav">Menu</button>
    <nav class="main-nav" id="main-nav" aria-label="Main navigation">
      <a href="<?= url('/') ?>">Home</a>
      <a href="<?= url('/marketplace') ?>">Marketplace</a>
      <?php if (!empty($user)): ?>
        <a href="<?= url('/sell') ?>">Sell</a>
        <a href="<?= url('/messages') ?>">Messages</a>
        <a href="<?= url('/favorites') ?>">Favorites</a>
        <a href="<?= url('/my-listings') ?>">My Listings</a>
        <a href="<?= url('/profile') ?>">Profile</a>
        <a href="<?= url('/support/my') ?>">Support</a>
        <?php if (($user['role'] ?? '') === 'admin'): ?>
          <a href="<?= url('/admin') ?>">Admin</a>
        <?php endif; ?>
        <form class="inline-form" method="post" action="<?= url('/logout') ?>">
          <?= csrfField() ?>
          <button class="btn btn-sm" type="submit" style="background:rgba(255,255,255,.14);color:#fff">Sign out</button>
        </form>
      <?php else: ?>
        <a href="<?= url('/login') ?>">Sign in</a>
        <a href="<?= url('/register') ?>" class="btn btn-sm btn-primary" style="color:#fff">Join SDUT Swap</a>
      <?php endif; ?>
    </nav>
  </div>
</header>
<main id="main">
  <div class="container">
    <?php foreach (($flashes ?? []) as $f): ?>
      <div class="alert alert-<?= e($f['type'] === 'error' ? 'error' : 'success') ?>" role="alert" style="margin-top:16px"><?= e($f['message']) ?></div>
      <span data-flash="<?= e($f['message']) ?>" data-flash-type="<?= e($f['type']) ?>" hidden></span>
    <?php endforeach; ?>
  </div>
  <?= $content ?? '' ?>
</main>
<footer class="footer">
  <div class="container">
    <div class="footer-grid">
      <div>
        <h4>SDUT Swap</h4>
        <p class="small">Your Campus. Your Marketplace.<br>A trusted marketplace for university students.</p>
      </div>
      <div>
        <h4>Marketplace</h4>
        <ul>
          <li><a href="<?= url('/marketplace') ?>">Browse listings</a></li>
          <li><a href="<?= url('/sell') ?>">Sell an item</a></li>
          <li><a href="<?= url('/favorites') ?>">Favorites</a></li>
        </ul>
      </div>
      <div>
        <h4>Support</h4>
        <ul>
          <li><a href="<?= url('/support') ?>">Help &amp; FAQ</a></li>
          <li><a href="<?= url('/support/new') ?>">Contact support</a></li>
          <li><a href="<?= url('/safety') ?>">Safety tips</a></li>
        </ul>
      </div>
      <div>
        <h4>Legal</h4>
        <ul>
          <li><a href="<?= url('/about') ?>">About</a></li>
          <li><a href="<?= url('/guidelines') ?>">Community Guidelines</a></li>
          <li><a href="<?= url('/privacy') ?>">Privacy Policy</a></li>
          <li><a href="<?= url('/terms') ?>">Terms of Service</a></li>
        </ul>
      </div>
    </div>
    <div class="footer-bottom">
      <span>© <?= date('Y') ?> SDUT Swap. All rights reserved.</span>
      <span>Meet on campus. Pay in person. Stay safe.</span>
    </div>
  </div>
</footer>
<div class="toast-wrap" aria-live="polite"></div>
<script src="<?= asset('js/app.js') ?>" defer></script>
</body>
</html>
