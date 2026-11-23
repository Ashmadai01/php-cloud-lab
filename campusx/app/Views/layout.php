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
    <a class="brand" href="<?= url('/') ?>">SDUT<span>Swap</span><span class="tagline">Your Campus. Your Marketplace.</span></a >
    <button class="mobile-toggle" type="button" aria-label="Open navigation menu" aria-expanded="false" aria-controls="main-nav">
      <span class="mobile-toggle-icon" aria-hidden="true"><span></span><span></span><span></span></span>
      <span class="mobile-toggle-label">Menu</span>
    </button>
    <nav class="main-nav" id="main-nav" aria-label="Main navigation">
      <a href="<?= url('/') ?>">Home</a >
      <a href="<?= url('/marketplace') ?>">Marketplace</a >
      <?php if (!empty($user)): ?>
        <a href="<?= url('/sell') ?>">Sell</a >
        <a href="<?= url('/messages') ?>">Messages <span class="badge-count" data-unread-badge hidden>0</span></a >
        <a href="<?= url('/notifications') ?>" class="notification-bell" title="Notifications" aria-label="Notifications">
          <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4"/></svg>
          <?php if (($notificationUnreadCount ?? 0) > 0): ?>
            <span class="badge-count"><?= (int) $notificationUnreadCount ?></span>
          <?php endif; ?>
        </a >
        <details class="nav-account">
          <summary class="nav-profile" aria-label="Account menu" title="Account menu">
            <?php if (!empty($user['avatar_path'])): ?>
              <img src="<?= e(\SdutSwap\Core\Upload::publicPath((string) $user['avatar_path'])) ?>" alt="Profile picture">
            <?php else: ?>
              <span class="nav-profile-fallback"><?= e(strtoupper(substr((string) ($user['name'] ?? 'U'), 0, 1))) ?></span>
            <?php endif; ?>
          </summary>
          <div class="account-dropdown">
            <a href="<?= url('/profile') ?>">My Profile</a >
            <a href="<?= url('/my-listings') ?>">My Listings</a >
            <a href="<?= url('/favorites') ?>">Favorites</a >
            <a href="<?= url('/support/my') ?>">Support</a >
            <?php if (($user['role'] ?? '') === 'admin'): ?>
              <a href="<?= url('/admin') ?>">Admin</a >
            <?php endif; ?>
            <div class="account-dropdown-divider"></div>
            <form class="inline-form" method="post" action="<?= url('/logout') ?>">
              <?= csrfField() ?>
              <button type="submit">Sign out</button>
            </form>
          </div>
        </details>
      <?php else: ?>
        <a href="<?= url('/login') ?>">Sign in</a >
        <a href="<?= url('/register') ?>" class="btn btn-sm btn-primary" style="color:#fff">Join SDUT Swap</a >
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
