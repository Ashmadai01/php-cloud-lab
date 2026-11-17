<div class="container">
  <div class="page-head"><h1>Sell an item</h1><p class="muted">Create a listing. Photos help your item sell faster.</p></div>
  <div class="form-card wide">
    <form method="post" action="<?= url('/sell') ?>" enctype="multipart/form-data">
      <?= csrfField() ?>
      <?php if (!empty($errors)): foreach ($errors as $field => $msgs): foreach ($msgs as $m): ?><div class="alert alert-error"><?= e($m) ?></div><?php endforeach; endforeach; endif; ?>
      <div class="field">
        <label for="title">Title *</label>
        <input id="title" name="title" required maxlength="150" value="<?= e(old('title')) ?>" placeholder="e.g. Calculus Textbook — 8th Edition">
      </div>
      <div class="field">
        <label for="description">Description *</label>
        <textarea id="description" name="description" required maxlength="5000" placeholder="Describe the item, condition, and any flaws honestly."><?= e(old('description')) ?></textarea>
      </div>
      <div class="field-row">
        <div class="field">
          <label for="price">Price (¥) *</label>
          <input id="price" name="price" type="number" min="0" max="1000000" step="0.01" required value="<?= e(old('price')) ?>">
        </div>
        <div class="field">
          <label for="currency_display">Currency</label>
          <input id="currency_display" value="¥ CNY (Chinese yuan)" disabled aria-readonly="true">
        </div>
      </div>
      <div class="field-row">
        <div class="field">
          <label for="category_id">Category</label>
          <select id="category_id" name="category_id">
            <option value="">— Select —</option>
            <?php foreach (($categories ?? []) as $c): ?><option value="<?= (int) $c['id'] ?>" <?= old('category_id') === (string) $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option><?php endforeach; ?>
          </select>
        </div>
        <div class="field">
          <label for="condition">Condition</label>
          <select id="condition" name="condition">
            <?php foreach (['new' => 'New', 'like_new' => 'Like new', 'good' => 'Good', 'fair' => 'Fair', 'poor' => 'Poor'] as $k => $label): ?><option value="<?= $k ?>" <?= old('condition', 'good') === $k ? 'selected' : '' ?>><?= $label ?></option><?php endforeach; ?>
          </select>
        </div>
      </div>
      <div class="field-row">
        <div class="field">
          <label for="campus">Campus / pickup location</label>
          <input id="campus" name="campus" maxlength="150" value="<?= e(old('campus')) ?>" placeholder="e.g. North Campus Library">
        </div>
        <div class="field">
          <label for="status">Status</label>
          <select id="status" name="status">
            <option value="active" <?= old('status', 'active') === 'active' ? 'selected' : '' ?>>Active (publish now)</option>
            <option value="draft" <?= old('status') === 'draft' ? 'selected' : '' ?>>Draft (save for later)</option>
          </select>
        </div>
      </div>
      <div class="field">
        <label for="images">Photos (up to 6, JPEG/PNG/WebP, max 5 MB each)</label>
        <input id="images" name="images[]" type="file" accept=".jpg,.jpeg,.png,.webp" multiple>
        <p class="hint">First photo becomes the main image. Do not include personal documents or contact details in photos.</p>
      </div>
      <button class="btn btn-primary btn-block" type="submit">Create listing</button>
    </form>
  </div>
</div>
