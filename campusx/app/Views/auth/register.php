
<div class="container login-page">
  <div class="login-wrapper">

    <div class="login-brand">
      <span class="login-brand-name">CampusX<span>.</span></span>
      <span class="login-brand-label">SDUT STUDENT COMMUNITY</span>
    </div>

    <div class="login-card register-card">

      <div class="login-heading">
        <span class="login-eyebrow">JOIN OUR COMMUNITY</span>
        <h1>Create your account.</h1>
        <p>Be part of your campus community.</p >
      </div>

      <form method="post" action="<?= url('/register') ?>">
        <?= csrfField() ?>

        <?php if (!empty($errors)): ?>
          <?php foreach ($errors as $field => $msgs): ?>
            <?php foreach ($msgs as $m): ?>
              <div class="alert alert-error"><?= e($m) ?></div>
            <?php endforeach; ?>
          <?php endforeach; ?>
        <?php endif; ?>

        <div class="field login-field">
          <label for="name">Full name *</label>
          <input
            id="name"
            name="name"
            type="text"
            placeholder="Your full name"
            required
            maxlength="100"
            value="<?= e(old('name')) ?>"
            autocomplete="name">
        </div>

        <div class="field login-field">
          <label for="email">University email *</label>
          <input
            id="email"
            name="email"
            type="email"
            placeholder="you@university.edu"
            required
            value="<?= e(old('email')) ?>"
            autocomplete="email">
          <p class="hint">Use your university email address.</p >
        </div>

        <div class="field login-field">
          <label for="password">Password *</label>
          <input
            id="password"
            name="password"
            type="password"
            placeholder="Create a strong password"
            required
            autocomplete="new-password">
          <p class="hint">
            At least 10 characters with uppercase, lowercase, and a number.
          </p >
        </div>

        <div class="field login-field">
          <label for="password_confirm">Confirm password *</label>
          <input
            id="password_confirm"
            name="password_confirm"
            type="password"
            placeholder="Enter your password again"
            required
            autocomplete="new-password">
        </div>

        <div class="field checkbox register-terms">
          <input
            id="terms"
            name="terms"
            type="checkbox"
            value="1"
            required>
          <label for="terms" style="font-weight:400">
            I agree to the
            <a href="<?= url('/terms') ?>">Terms of Service</a>
            and
            <a href="<?= url('/privacy') ?>">Privacy Policy</a >.
          </label>
        </div>

        <button class="btn btn-primary btn-block login-submit" type="submit">
          Create account
        </button>

        <p class="login-register">
          Already have an account?
          <a href="<?= url('/login') ?>">Sign in</a >
        </p >
      </form>
    </div>

    <p class="login-footer">
      Campus Together. <span>© CampusX</span>
    </p >

  </div>
</div>
