<div class="container">
  <div class="page-head"><h1>Edit listing</h1></div>
  <div class="form-card wide">
    <form method="post" action="<?= url('/listings/' . (int) $listing['id'] . '/edit') ?>" enctype="multipart/form-data">
      <?= csrfField() ?>
      <?php if (!empty($errors)): foreach ($errors as $field => $msgs): foreach ($msgs as $m): ?><div class="alert alert-error"><?= e($m) ?></div><?php endforeach; endforeach; endif; ?>
      <div class="field"><label for="title">Title *</label><input id="title" name="title" required maxlength="150" value="<?= e($listing['title']) ?>"></div>
      <div class="field"><label for="description">Description *</label><textarea id="description" name="description" required maxlength="5000"><?= e($listing['description']) ?></textarea></div>
      <div class="field-row">
        <div class="field"><label for="price">Price (¥) *</label><input id="price" name="price" type="number" min="0" max="1000000" step="0.01" required value="<?= e($listing['price']) ?>"></div>
        <div class="field"><label for="currency_display">Currency</label>
          <input id="currency_display" value="¥ CNY (Chinese yuan)" disabled aria-readonly="true">
        </div>
      </div>
      <div class="field-row">
        <div class="field"><label for="category_id">Category</label>
          <select id="category_id" name="category_id"><option value="">— Select —</option><?php foreach (($categories ?? []) as $c): ?><option value="<?= (int) $c['id'] ?>" <?= (int) ($listing['category_id'] ?? 0) === (int) $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option><?php endforeach; ?></select>
        </div>
        <div class="field"><label for="condition">Condition</label>
          <select id="condition" name="condition"><?php foreach (['new' => 'New', 'like_new' => 'Like new', 'good' => 'Good', 'fair' => 'Fair', 'poor' => 'Poor'] as $k => $label): ?><option value="<?= $k ?>" <?= $listing['item_condition'] === $k ? 'selected' : '' ?>><?= $label ?></option><?php endforeach; ?></select>
        </div>
      </div>
      <div class="field-row">
        <div class="field"><label for="campus">Campus / pickup location</label><input id="campus" name="campus" maxlength="150" value="<?= e($listing['campus'] ?? '') ?>"></div>
        <div class="field"><label for="status">Status</label>
          <select id="status" name="status"><?php foreach (['draft' => 'Draft', 'active' => 'Active', 'sold' => 'Sold', 'hidden' => 'Hidden'] as $k => $label): ?><option value="<?= $k ?>" <?= $listing['status'] === $k ? 'selected' : '' ?>><?= $label ?></option><?php endforeach; ?></select>
        </div>
      </div>
      <div class="field">
        <label>Add photos (up to 6 total)</label>
        <input type="file" name="images[]" accept=".jpg,.jpeg,.png,.webp" multiple>
      </div>
      <button class="btn btn-primary btn-block" type="submit">Save changes</button>
    </form>
    <?php if (!empty($images)): ?>
      <h3 class="mt">Current photos</h3>
      <div class="thumbs">
        <?php foreach ($images as $img): ?>
          <div style="position:relative">
            <img src="<?= e(\SdutSwap\Core\Upload::publicPath($img['file_path'])) ?>" alt="" style="width:72px;height:56px;object-fit:cover;border-radius:8px">
            <form method="post" action="<?= url('/listings/' . (int) $listing['id'] . '/images/' . (int) $img['id'] . '/delete') ?>" class="inline-form">
              <?= csrfField() ?>
              <button class="btn btn-sm btn-danger" type="submit" data-confirm="Delete this photo?" aria-label="Delete photo">✕</button>
            </form>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</div>
