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
    <nav class="main-nav" id="main-nav" aria-label="Main navigation">
      <a href="<?= url('/') ?>" class="nav-icon-link" aria-label="Home" title="Home"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m3 10 9-7 9 7v10a1 1 0 0 1-1 1h-5v-7H9v7H4a1 1 0 0 1-1-1z"/></svg><span>Home</span></a >
      <a href="<?= url('/marketplace') ?>" class="nav-icon-link" aria-label="Marketplace" title="Marketplace"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 9h18l-1.5-6h-15zM5 9v12h14V9M9 21v-7h6v7M3 9a3 3 0 0 0 6 0 3 3 0 0 0 6 0 3 3 0 0 0 6 0"/></svg><span>Marketplace</span></a >
      <?php if (!empty($user)): ?>
        <a href="<?= url('/sell') ?>" class="nav-icon-link" aria-label="Sell an item" title="Sell an item"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg><span>Sell</span></a >
        <a href="<?= url('/messages') ?>" class="nav-icon-link" aria-label="Messages" title="Messages"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M21 11.5a8.4 8.4 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.4 8.4 0 0 1-3.8-.9L3 21l1.9-5.7a8.4 8.4 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.4 8.4 0 0 1 3.8-.9h.5a8.5 8.5 0 0 1 8 8z"/></svg><span>Messages</span><span class="badge-count" data-unread-badge hidden>0</span></a >
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
        <a href="<?= url('/login') ?>" class="nav-icon-link" aria-label="Sign in" title="Sign in"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4M10 17l5-5-5-5M15 12H3"/></svg><span>Sign in</span></a >
        <a href="<?= url('/register') ?>" class="btn btn-sm btn-primary nav-icon-link" aria-label="Join SDUT Swap" title="Join SDUT Swap" style="color:#fff"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8M19 8v6M22 11h-6"/></svg><span>Join SDUT Swap</span></a >
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
