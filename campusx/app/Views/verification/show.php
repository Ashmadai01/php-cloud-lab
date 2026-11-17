<div class="container">
  <div class="page-head"><h1>Verify student identity</h1><p class="muted">Upload your Student Card so you can list items and message sellers. Our team reviews each submission manually.</p></div>
  <div class="form-card">
    <p>Status: <span class="badge badge-<?= e($user['verification_status'] ?? 'not_submitted') ?>"><?= e(verificationLabel($user['verification_status'] ?? 'not_submitted')) ?></span></p>
    <?php if (!empty($verification) && ($verification['status'] ?? '') === 'pending'): ?>
      <div class="alert alert-info">⏳ Your verification is pending review. We'll notify you by email once it's done.</div>
    <?php elseif (!empty($verification) && ($verification['status'] ?? '') === 'verified'): ?>
      <div class="alert alert-success">✓ You're verified! You can now publish listings and message sellers.</div>
    <?php else: ?>
      <?php if (!empty($verification) && ($verification['status'] ?? '') === 'rejected'): ?>
        <div class="alert alert-error">Your previous submission was rejected: <?= e($verification['rejection_reason'] ?? 'Please check your details and try again.') ?></div>
      <?php endif; ?>
      <?php if (!empty($errors)): foreach ($errors as $field => $msgs): foreach ($msgs as $m): ?><div class="alert alert-error"><?= e($m) ?></div><?php endforeach; endforeach; endif; ?>
      <form method="post" action="<?= url('/verify') ?>" enctype="multipart/form-data">
        <?= csrfField() ?>
        <div class="field"><label for="full_name">Full name (as on Student Card) *</label><input id="full_name" name="full_name" required maxlength="100"></div>
        <div class="field"><label for="student_number">Student number *</label><input id="student_number" name="student_number" required maxlength="30" placeholder="e.g. 2024001234"><p class="hint">We store only a secure hash of your student number — never the full number in plain text.</p></div>
        <div class="field"><label for="university">University *</label><input id="university" name="university" required maxlength="150"></div>
        <div class="field"><label for="card">Student Card photo/scan * (JPEG/PNG/WebP, max 5 MB)</label><input id="card" name="card" type="file" accept=".jpg,.jpeg,.png,.webp" required><p class="hint">Your card image is stored privately, visible only to verification staff, and automatically deleted <?= (int) \SdutSwap\Core\Config::int('CARD_RETENTION_DAYS', 30) ?> days after your submission has been reviewed.</p></div>
        <button class="btn btn-primary btn-block" type="submit">Submit for verification</button>
      </form>
    <?php endif; ?>
    <p class="muted small mt">Note: verification is a manual review process. It confirms your Student Card appears genuine, but cannot guarantee identity with absolute certainty.</p>
  </div>
</div>
