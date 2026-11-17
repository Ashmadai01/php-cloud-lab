<div class="container">
  <div class="page-head" style="text-align:center"><h1>Welcome back</h1><p class="muted">Sign in to SDUT Swap.</p></div>
  <div class="form-card">
    <form method="post" action="<?= url('/login') ?>">
      <?= csrfField() ?>
      <?php if (!empty($errors)): foreach ($errors as $field => $msgs): foreach ($msgs as $m): ?><div class="alert alert-error"><?= e($m) ?></div><?php endforeach; endforeach; endif; ?>
      <div class="field"><label for="email">Email</label><input id="email" name="email" type="email" required value="<?= e(old('email')) ?>" autocomplete="email"></div>
      <div class="field"><label for="password">Password</label><input id="password" name="password" type="password" required autocomplete="current-password"></div>
      <button class="btn btn-primary btn-block" type="submit">Sign in</button>
      <p class="small" style="text-align:center"><a href="<?= url('/forgot-password') ?>">Forgot password?</a> · <a href="<?= url('/register') ?>">Create account</a></p>
    </form>
    <hr>
    <form method="post" action="<?= url('/resend-verification') ?>">
      <?= csrfField() ?>
      <p class="muted small">Haven't received a verification email?</p>
      <div class="flex">
        <input type="email" name="email" placeholder="Your email" required style="flex:1;border:1px solid var(--border);border-radius:10px;padding:10px 12px">
        <button class="btn btn-outline" type="submit">Resend</button>
      </div>
    </form>
  </div>
</div>
