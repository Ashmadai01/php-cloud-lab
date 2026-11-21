<div class="container">
  <div class="page-head">
    <h1>Public Profile</h1>
  </div>

  <div class="panel" style="max-width:700px;margin:0 auto;text-align:center;padding:30px">
    <?php if (!empty($profile['avatar_path'])): ?>
      <img
        src="<?= e(\SdutSwap\Core\Upload::publicPath((string) $profile['avatar_path'])) ?>"
        alt="<?= e($profile['name']) ?>'s profile picture"
        style="width:140px;height:140px;object-fit:cover;border-radius:50%;margin-bottom:18px"
      >
    <?php else: ?>
      <div style="width:140px;height:140px;border-radius:50%;background:var(--surface-2,#eee);display:flex;align-items:center;justify-content:center;margin:0 auto 18px">
        No photo
      </div>
    <?php endif; ?>

    <h2><?= e($profile['name']) ?></h2>

    <?php if (!empty($profile['is_verified'])): ?>
      <span class="badge badge-active">Verified Student</span>
    <?php endif; ?>

    <?php if (!empty($profile['university'])): ?>
      <p class="muted"><?= e($profile['university']) ?></p>
    <?php endif; ?>

    <?php if (!empty($profile['bio'])): ?>
      <div style="margin-top:22px;text-align:left">
        <h3>About</h3>
        <p><?= nl2br(e($profile['bio'])) ?></p>
      </div>
    <?php endif; ?>

    <p class="muted small" style="margin-top:25px">
      Member since <?= e(displayDate($profile['created_at'] ?? null)) ?>
    </p>
  </div>
</div>
