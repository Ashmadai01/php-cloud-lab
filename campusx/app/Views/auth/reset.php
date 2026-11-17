<div class="container">
  <div class="page-head" style="text-align:center"><h1>Reset password</h1></div>
  <div class="form-card">
    <form method="post" action="<?= url('/reset-password') ?>">
      <?= csrfField() ?>
      <input type="hidden" name="token" value="<?= e($token ?? '') ?>">
      <?php if (!empty($errors)): foreach ($errors as $field => $msgs): foreach ($msgs as $m): ?><div class="alert alert-error"><?= e($m) ?></div><?php endforeach; endforeach; endif; ?>
      <div class="field"><label for="password">New password</label><input id="password" name="password" type="password" required autocomplete="new-password"><p class="hint">At least 10 characters with uppercase, lowercase, and a number.</p></div>
      <div class="field"><label for="password_confirm">Confirm new password</label><input id="password_confirm" name="password_confirm" type="password" required autocomplete="new-password"></div>
      <button class="btn btn-primary btn-block" type="submit">Reset password</button>
    </form>
  </div>
</div>
