<div class="container">
  <div class="page-head"><h1>Contact Support</h1><p class="muted">Tell us how we can help. We reply within 2 business days.</p></div>
  <div class="form-card">
    <form method="post" action="<?= url('/support/new') ?>" enctype="multipart/form-data">
      <?= csrfField() ?>
      <?php if (!empty($errors)): foreach ($errors as $field => $msgs): foreach ($msgs as $m): ?><div class="alert alert-error"><?= e($m) ?></div><?php endforeach; endforeach; endif; ?>
      <div class="field-row">
        <div class="field"><label for="ticket_type">Type</label>
          <select id="ticket_type" name="ticket_type"><option value="help">Help &amp; Assistance</option><option value="report">Report a problem</option></select>
        </div>
        <div class="field"><label for="priority">Priority</label>
          <select id="priority" name="priority"><option value="low">Low</option><option value="normal" selected>Normal</option><option value="high">High</option></select>
        </div>
      </div>
      <div class="field"><label for="category">Category *</label><input id="category" name="category" required maxlength="80" placeholder="e.g. Account, Listing, Payment concern"></div>
      <div class="field"><label for="subject">Subject *</label><input id="subject" name="subject" required maxlength="200" placeholder="Brief summary"></div>
      <div class="field"><label for="body">Description *</label><textarea id="body" name="body" required maxlength="5000" placeholder="Describe the issue in detail…"></textarea></div>
      <div class="field"><label for="attachments">Attachments (optional, up to 3 images, max 5 MB each)</label><input id="attachments" name="attachments[]" type="file" multiple accept=".jpg,.jpeg,.png,.webp"></div>
      <button class="btn btn-primary btn-block" type="submit">Submit ticket</button>
    </form>
  </div>
</div>
