<div class="container">
  <div class="page-head"><h1>Admin Dashboard</h1></div>
  <nav class="admin-nav" aria-label="Admin">
    <a href="<?= url('/admin') ?>" class="active">Overview</a>
    <a href="<?= url('/admin/users') ?>">Users</a>
    <a href="<?= url('/admin/listings') ?>">Listings</a>
    <a href="<?= url('/admin/verifications') ?>">Verifications</a>
    <a href="<?= url('/admin/tickets') ?>">Tickets</a>
    <a href="<?= url('/admin/reports') ?>">Reports</a>
    <a href="<?= url('/admin/audit') ?>">Audit Log</a>
    <a href="<?= url('/admin/security') ?>">Security Events</a>
  </nav>
  <div class="stat-grid">
    <div class="stat"><div class="num"><?= (int) ($stats['total_users'] ?? 0) ?></div><div class="label">Total users</div></div>
    <div class="stat"><div class="num"><?= (int) ($stats['verified'] ?? 0) ?></div><div class="label">Verified students</div></div>
    <div class="stat"><div class="num"><?= (int) ($stats['pending_verification'] ?? 0) ?></div><div class="label">Pending verification</div></div>
    <div class="stat"><div class="num"><?= (int) ($stats['active_listings'] ?? 0) ?></div><div class="label">Active listings</div></div>
    <div class="stat"><div class="num"><?= (int) ($stats['open_tickets'] ?? 0) ?></div><div class="label">Open tickets</div></div>
    <div class="stat"><div class="num"><?= (int) ($stats['open_reports'] ?? 0) ?></div><div class="label">Open reports</div></div>
  </div>
  <div class="panel">
    <h3>Quick actions</h3>
    <div class="flex">
      <a class="btn btn-outline" href="<?= url('/admin/verifications') ?>">Review verifications</a>
      <a class="btn btn-outline" href="<?= url('/admin/tickets') ?>">Support tickets</a>
      <a class="btn btn-outline" href="<?= url('/admin/reports') ?>">Reports queue</a>
      <a class="btn btn-outline" href="<?= url('/admin/listings') ?>">Moderate listings</a>
    </div>
  </div>
</div>
