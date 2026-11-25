<div class="container">
  <div class="page-head">
    <h1>Confirm your email</h1>
    <p class="muted">Click the button below to verify your email address and activate your CampusX account.</p>
  </div>
  <div class="form-card">
    <?php if ($token === ''): ?>
      <div class="alert alert-error">This verification link is missing its token. Please request a new verification email.</div>
      <a class="btn btn-primary" href="<?= url('/login') ?>">Back to sign in</a>
    <?php else: ?>
      <form method="post" action="<?= url('/verify-email') ?>">
        <?= csrfField() ?>
        <input type="hidden" name="token" value="<?= e($token) ?>">
        <button class="btn btn-primary btn-block" type="submit">Verify email address</button>
      </form>
    <?php endif; ?>
    <p class="muted small mt">For your security, opening this page does not verify your email. Confirmation happens only after you submit this form.</p>
  </div>
</div>
