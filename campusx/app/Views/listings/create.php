<div class="container sell-page">
  <section class="sell-hero">
    <div class="sell-hero-content">
      <span class="sell-eyebrow">CAMPUSX MARKETPLACE</span>
      <h1>Give your items a <span>second life.</span></h1>
      <p>Turn things you no longer need into something valuable for another student.</p>
      <div class="sell-benefits">
        <span>✦ Easy to list</span>
        <span>✦ Student community</span>
        <span>✦ Campus pickup</span>
      </div>
    </div>
    <div class="sell-hero-art" aria-hidden="true">
      <div class="sell-art-circle"></div>
      <div class="sell-art-card sell-art-card-one">📚</div>
      <div class="sell-art-card sell-art-card-two">🎧</div>
      <div class="sell-art-card sell-art-card-three">🎒</div>
      <div class="sell-art-caption">Your next sale starts here.</div>
    </div>
  </section>

  <div class="sell-layout">
    <main class="sell-main">
      <div class="sell-form-heading">
        <div>
          <span class="sell-step">CREATE A LISTING</span>
          <h2>Item details</h2>
          <p>Tell other students what you're selling.</p>
        </div>
        <div class="sell-step-number">01 <span>/ 01</span></div>
      </div>

      <div class="sell-form-card">
        <form method="post" action="<?= url('/sell') ?>" enctype="multipart/form-data">
          <?= csrfField() ?>

          <?php if (!empty($errors)): ?>
            <?php foreach ($errors as $field => $msgs): ?>
              <?php foreach ($msgs as $m): ?>
                <div class="alert alert-error"><?= e($m) ?></div>
              <?php endforeach; ?>
            <?php endforeach; ?>
          <?php endif; ?>

          <div class="sell-section-title">
            <span class="sell-section-icon">✎</span>
            <div><h3>Basic information</h3><p>Give your item a clear identity.</p></div>
          </div>

          <div class="field">
            <label for="title">Item title <span class="sell-required">*</span></label>
            <input id="title" name="title" required maxlength="150" value="<?= e(old('title')) ?>" placeholder="e.g. Calculus Textbook — 8th Edition">
          </div>

          <div class="field">
            <label for="description">Description <span class="sell-required">*</span></label>
            <textarea id="description" name="description" required maxlength="5000" rows="5" placeholder="Describe your item, its condition, and anything buyers should know..."><?= e(old('description')) ?></textarea>
            <p class="hint">Be honest and specific. A good description builds trust.</p>
          </div>

          <div class="sell-divider"></div>

          <div class="sell-section-title">
            <span class="sell-section-icon">¥</span>
            <div><h3>Price and details</h3><p>Set your price and item condition.</p></div>
          </div>

          <div class="field-row">
            <div class="field">
              <label for="price">Price (¥) <span class="sell-required">*</span></label>
              <input id="price" name="price" type="number" min="0" max="1000000" step="0.01" required value="<?= e(old('price')) ?>" placeholder="0.00">
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
                <option value="">— Select category —</option>
                <?php foreach (($categories ?? []) as $c): ?>
                  <option value="<?= (int) $c['id'] ?>" <?= old('category_id') === (string) $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="field">
              <label for="condition">Condition</label>
              <select id="condition" name="condition">
                <?php foreach (['new' => 'New', 'like_new' => 'Like new', 'good' => 'Good', 'fair' => 'Fair', 'poor' => 'Poor'] as $k => $label): ?>
                  <option value="<?= $k ?>" <?= old('condition', 'good') === $k ? 'selected' : '' ?>><?= $label ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>

          <div class="field">
            <label for="campus">Campus / pickup location</label>
            <input id="campus" name="campus" maxlength="150" value="<?= e(old('campus')) ?>" placeholder="e.g. North Campus Library">
          </div>

          <div class="field">
            <label for="status">Listing status</label>
            <select id="status" name="status">
              <option value="active" <?= old('status', 'active') === 'active' ? 'selected' : '' ?>>Active — publish now</option>
              <option value="draft" <?= old('status') === 'draft' ? 'selected' : '' ?>>Draft — save for later</option>
            </select>
          </div>

          <div class="sell-divider"></div>

          <div class="sell-section-title">
            <span class="sell-section-icon">▧</span>
            <div><h3>Product photos</h3><p>Show buyers exactly what they're getting.</p></div>
          </div>

          <div class="sell-upload-box">
            <div class="sell-upload-icon">↑</div>
            <label for="images">Choose your photos</label>
            <p>Click to browse files from your device</p>
            <input id="images" name="images[]" type="file" accept=".jpg,.jpeg,.png,.webp" multiple>
            <span class="sell-upload-note">Up to 6 photos · JPEG, PNG or WebP · Max 5 MB each</span>
          </div>
          <p class="hint">The first photo becomes the main image. Do not include personal documents or contact details.</p>

          <button class="btn btn-primary btn-block sell-submit" type="submit">
            Publish your listing <span>→</span>
          </button>
          <p class="sell-terms">By publishing, you agree to provide accurate information about your item.</p>
        </form>
      </div>
    </main>

    <aside class="sell-sidebar">
      <div class="sell-tip-card">
        <span class="sell-tip-icon">✦</span>
        <h3>Tips for a great listing</h3>
        <ul>
          <li><span>01</span> Use a clear and descriptive title.</li>
          <li><span>02</span> Upload bright, real photos.</li>
          <li><span>03</span> Mention any wear or defects.</li>
          <li><span>04</span> Choose a fair price.</li>
        </ul>
      </div>
      <div class="sell-trust-card">
        <div class="sell-trust-icon">🎓</div>
        <h3>Made for students</h3>
        <p>CampusX helps students buy, sell and exchange items within their campus community.</p>
      </div>
    </aside>
  </div>
</div>
