<div class="container">
  <div class="page-head" style="text-align:center"><h1>Confirm your password</h1><p class="muted">This action is sensitive. Please re-enter your password to continue. The confirmation stays valid for a few minutes.</p></div>
  <div class="form-card">
    <form method="post" action="<?= url('/admin/confirm') ?>">
      <?= csrfField() ?>
      <input type="hidden" name="next" value="<?= e($next ?? '/admin') ?>">
      <div class="field"><label for="password">Password</label><input id="password" name="password" type="password" required autocomplete="current-password" autofocus></div>
      <button class="btn btn-primary btn-block" type="submit">Confirm</button>
    </form>
    <p class="muted small mt"><a href="<?= url('/admin') ?>">Cancel</a></p>
  </div>
</div>
