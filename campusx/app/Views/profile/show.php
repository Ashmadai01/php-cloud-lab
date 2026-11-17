<div class="container">
  <div class="page-head"><h1>My Profile</h1></div>
  <div class="split">
    <div class="form-card" style="margin:0">
      <h3>Profile details</h3>
      <?php if (!empty($errors)): foreach ($errors as $field => $msgs): foreach ($msgs as $m): ?><div class="alert alert-error"><?= e($m) ?></div><?php endforeach; endforeach; endif; ?>
      <form method="post" action="<?= url('/profile') ?>" enctype="multipart/form-data">
        <?= csrfField() ?>
        <div class="field"><label for="name">Display name *</label><input id="name" name="name" required maxlength="100" value="<?= e($profile['name'] ?? '') ?>"></div>
        <div class="field"><label for="university">University</label><input id="university" name="university" maxlength="150" value="<?= e($profile['university'] ?? '') ?>"></div>
        <div class="field"><label for="bio">Bio</label><textarea id="bio" name="bio" maxlength="1000"><?= e($profile['bio'] ?? '') ?></textarea></div>
        <div class="field"><label for="avatar">Avatar (JPEG/PNG/WebP, max 5 MB)</label><input id="avatar" name="avatar" type="file" accept=".jpg,.jpeg,.png,.webp"></div>
        <button class="btn btn-primary" type="submit">Save profile</button>
      </form>
      <hr>
      <h3>Change password</h3>
      <form method="post" action="<?= url('/profile/password') ?>">
        <?= csrfField() ?>
        <div class="field"><label for="current_password">Current password</label><input id="current_password" name="current_password" type="password" required autocomplete="current-password"></div>
        <div class="field"><label for="new_password">New password</label><input id="new_password" name="new_password" type="password" required autocomplete="new-password"></div>
        <div class="field"><label for="new_password_confirm">Confirm new password</label><input id="new_password_confirm" name="new_password_confirm" type="password" required autocomplete="new-password"></div>
        <button class="btn btn-outline" type="submit">Change password</button>
      </form>
    </div>
    <div>
      <div class="panel">
        <h3>Student verification</h3>
        <p>Status: <span class="badge badge-<?= e($profile['verification_status'] ?? 'not_submitted') ?>"><?= e(verificationLabel($profile['verification_status'] ?? 'not_submitted')) ?></span></p>
        <?php if (empty($verification)): ?>
          <p class="muted">You haven't submitted your Student Card yet.</p>
          <a class="btn btn-primary" href="<?= url('/verify') ?>">Verify now</a>
        <?php else: ?>
          <?php if (($verification['status'] ?? '') === 'rejected'): ?>
            <p class="muted">Reason: <?= e($verification['rejection_reason'] ?? '') ?></p>
            <a class="btn btn-outline" href="<?= url('/verify') ?>">Submit again</a>
          <?php elseif (($verification['status'] ?? '') === 'pending'): ?>
            <p class="muted">Your Student Card is under review. This usually takes up to 2 business days.</p>
          <?php else: ?>
            <p class="muted">✓ You're a verified student. Your badge appears on your listings.</p>
          <?php endif; ?>
        <?php endif; ?>
      </div>
      <div class="panel mt">
        <h3>Signed-in devices</h3>
        <?php if (empty($sessions)): ?>
          <p class="muted small">No active sessions found.</p>
        <?php else: ?>
          <ul class="plain-list">
          <?php foreach ($sessions as $sess): ?>
            <li style="display:flex;justify-content:space-between;align-items:center;gap:10px;padding:6px 0">
              <span><strong><?= e($sess['device_label']) ?></strong><?= !empty($sess['is_current']) ? ' <span class="badge badge-active">This device</span>' : '' ?><br><span class="muted small">Signed in <?= e(displayDate($sess['created_at'])) ?> · active <?= e(timeAgo($sess['last_seen_at'])) ?></span></span>
              <?php if (empty($sess['is_current'])): ?>
                <form method="post" action="<?= url('/profile/sessions/' . (int) $sess['id'] . '/revoke') ?>" class="inline-form"><?= csrfField() ?><button class="btn btn-sm btn-outline" type="submit">Sign out</button></form>
              <?php endif; ?>
            </li>
          <?php endforeach; ?>
          </ul>
          <?php if (count($sessions) > 1): ?>
            <form method="post" action="<?= url('/profile/sessions/revoke-others') ?>" class="mt"><?= csrfField() ?><button class="btn btn-sm btn-danger" type="submit" data-confirm="Sign out of all other devices?">Sign out all other devices</button></form>
          <?php endif; ?>
        <?php endif; ?>
      </div>
      <div class="panel mt">
        <h3>Blocked users</h3>
        <?php if (empty($blockedUsers)): ?>
          <p class="muted small">You haven't blocked anyone. You can block someone from a conversation.</p>
        <?php else: ?>
          <ul class="plain-list">
          <?php foreach ($blockedUsers as $b): ?>
            <li style="display:flex;justify-content:space-between;align-items:center;gap:10px;padding:6px 0">
              <span><?= e($b['name']) ?></span>
              <form method="post" action="<?= url('/users/' . (int) $b['id'] . '/unblock') ?>" class="inline-form"><?= csrfField() ?><input type="hidden" name="return" value="/profile"><button class="btn btn-sm btn-outline" type="submit">Unblock</button></form>
            </li>
          <?php endforeach; ?>
          </ul>
        <?php endif; ?>
      </div>
      <div class="panel mt">
        <h3>Account</h3>
        <p class="small muted">Email: <?= e($profile['email'] ?? '') ?><br>Role: <?= e($profile['role'] ?? 'student') ?><br>Member since: <?= e(displayDate($profile['created_at'] ?? null)) ?></p>
      </div>
    </div>
  </div>
</div>
