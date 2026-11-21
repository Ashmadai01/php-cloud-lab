
<div class="container login-page">
  <div class="login-wrapper">

    <div class="login-brand">
      <span class="login-brand-name">CampusX<span>.</span></span>
      <span class="login-brand-label">SDUT STUDENT COMMUNITY</span>
    </div>

    <div class="login-card">
      <div class="login-heading">
        <span class="login-eyebrow">WELCOME BACK</span>
        <h1>Sign in to CampusX.</h1>
        <p>Connect with your campus community.</p >
      </div>

      <form method="post" action="<?= url('/login') ?>">
        <?= csrfField() ?>

        <?php if (!empty($errors)): ?>
          <?php foreach ($errors as $field => $msgs): ?>
            <?php foreach ($msgs as $m): ?>
              <div class="alert alert-error"><?= e($m) ?></div>
            <?php endforeach; ?>
          <?php endforeach; ?>
        <?php endif; ?>

        <div class="field login-field">
          <label for="email">Email address</label>
          <input
            id="email"
            name="email"
            type="email"
            placeholder="you@example.com"
            required
            value="<?= e(old('email')) ?>"
            autocomplete="email">
        </div>

        <div class="field login-field">
          <label for="password">Password</label>
          <input
            id="password"
            name="password"
            type="password"
            placeholder="Enter your password"
            required
            autocomplete="current-password">
        </div>

        <div class="login-forgot">
          <a href="<?= url('/forgot-password') ?>">Forgot password?</a>
        </div>

        <button class="btn btn-primary btn-block login-submit" type="submit">
          Sign in
        </button>

        <p class="login-register">
          Don't have an account?
          <a href="<?= url('/register') ?>">Create account</a >
        </p >
      </form>

      <div class="login-divider"><span>ACCOUNT VERIFICATION</span></div>

      <form method="post" action="<?= url('/resend-verification') ?>">
        <?= csrfField() ?>

        <p class="login-verification-text">
          Haven't received your verification email?
        </p >

        <div class="login-resend">
          <input
            type="email"
            name="email"
            placeholder="Your email address"
            required>
          <button class="btn btn-outline" type="submit">Resend</button>
        </div>
      </form>
    </div>

    <p class="login-footer">
      Campus Together. <span>© CampusX</span>
    </p >

  </div>
</div>
