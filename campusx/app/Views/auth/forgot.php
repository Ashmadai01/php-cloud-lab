
<div class="container login-page">
  <div class="login-wrapper">

    <div class="login-brand">
      <span class="login-brand-name">CampusX<span>.</span></span>
      <span class="login-brand-label">SDUT STUDENT COMMUNITY</span>
    </div>

    <div class="login-card">

      <div class="login-heading">
        <span class="login-eyebrow">ACCOUNT RECOVERY</span>
        <h1>Forgot password?</h1>
        <p>Don't worry. We'll help you get back into your account.</p >
      </div>

      <form method="post" action="<?= url('/forgot-password') ?>">
        <?= csrfField() ?>

        <div class="field login-field">
          <label for="email">Email address</label>
          <input
            id="email"
            name="email"
            type="email"
            placeholder="you@example.com"
            required
            autocomplete="email">
        </div>

        <button class="btn btn-primary btn-block login-submit" type="submit">
          Send reset link
        </button>

        <p class="login-register">
          Remember your password?
          <a href="<?= url('/login') ?>">Back to sign in</a>
        </p >
      </form>

    </div>

    <p class="login-footer">
      Campus Together. <span>© CampusX</span>
    </p >

  </div>
</div>
