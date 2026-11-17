<div class="container">
  <div class="page-head" style="text-align:center"><h1>Forgot password</h1><p class="muted">Enter your email and we'll send a reset link.</p></div>
  <div class="form-card">
    <form method="post" action="<?= url('/forgot-password') ?>">
      <?= csrfField() ?>
      <div class="field"><label for="email">Email</label><input id="email" name="email" type="email" required autocomplete="email"></div>
      <button class="btn btn-primary btn-block" type="submit">Send reset link</button>
      <p class="small" style="text-align:center"><a href="<?= url('/login') ?>">Back to sign in</a></p>
    </form>
  </div>
</div>
