<div class="container">
  <div class="page-head" style="text-align:center"><h1>Create your account</h1><p class="muted">Join SDUT Swap — Your Campus. Your Marketplace.</p></div>
  <div class="form-card">
    <form method="post" action="<?= url('/register') ?>">
      <?= csrfField() ?>
      <?php if (!empty($errors)): foreach ($errors as $field => $msgs): foreach ($msgs as $m): ?><div class="alert alert-error"><?= e($m) ?></div><?php endforeach; endforeach; endif; ?>
      <div class="field"><label for="name">Full name *</label><input id="name" name="name" required maxlength="100" value="<?= e(old('name')) ?>" autocomplete="name"></div>
      <div class="field"><label for="email">University email *</label><input id="email" name="email" type="email" required value="<?= e(old('email')) ?>" autocomplete="email"><p class="hint">Use your university email address.</p></div>
      <div class="field"><label for="password">Password *</label><input id="password" name="password" type="password" required autocomplete="new-password"><p class="hint">At least 10 characters with uppercase, lowercase, and a number.</p></div>
      <div class="field"><label for="password_confirm">Confirm password *</label><input id="password_confirm" name="password_confirm" type="password" required autocomplete="new-password"></div>
      <div class="field checkbox"><input id="terms" name="terms" type="checkbox" value="1" required><label for="terms" style="font-weight:400">I agree to the <a href="<?= url('/terms') ?>">Terms of Service</a> and <a href="<?= url('/privacy') ?>">Privacy Policy</a>.</label></div>
      <button class="btn btn-primary btn-block" type="submit">Create account</button>
      <p class="muted small" style="text-align:center">Already have an account? <a href="<?= url('/login') ?>">Sign in</a></p>
    </form>
  </div>
</div>
