<div class="container">
  <div class="page-head"><h1>Report</h1><p class="muted">Report a <?= e($targetType) ?> (#<?= (int) $targetId ?>). Reports are reviewed by our moderation team.</p></div>
  <div class="form-card">
    <form method="post" action="<?= url('/report') ?>" enctype="multipart/form-data">
      <?= csrfField() ?>
      <input type="hidden" name="target_type" value="<?= e($targetType) ?>">
      <input type="hidden" name="target_id" value="<?= (int) $targetId ?>">
      <?php if (!empty($errors)): foreach ($errors as $field => $msgs): foreach ($msgs as $m): ?><div class="alert alert-error"><?= e($m) ?></div><?php endforeach; endforeach; endif; ?>
      <div class="field"><label for="report_type">Report type *</label>
        <select id="report_type" name="report_type" required>
          <option value="">— Select —</option>
          <option value="scam">Scam / fraud</option>
          <option value="spam">Spam</option>
          <option value="harassment">Harassment</option>
          <option value="prohibited_item">Prohibited item</option>
          <option value="fake_listing">Fake listing</option>
          <option value="other">Other</option>
        </select>
      </div>
      <div class="field"><label for="description">Description *</label><textarea id="description" name="description" required maxlength="5000" placeholder="What happened? Include relevant details."></textarea></div>
      <div class="field"><label for="evidence">Evidence (optional, up to 3 images, max 5 MB each)</label><input id="evidence" name="evidence[]" type="file" multiple accept=".jpg,.jpeg,.png,.webp"></div>
      <button class="btn btn-primary btn-block" type="submit">Submit report</button>
    </form>
  </div>
</div>
