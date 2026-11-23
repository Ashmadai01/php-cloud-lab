<div class="container contact-support-page">
  <section class="contact-support-hero">
    <div class="contact-support-hero-copy">
      <span class="contact-support-eyebrow">SDUT SWAP · SUPPORT DESK</span>
      <h1>Let's get this<br><span>sorted together.</span></h1>
      <p>Tell us what's happening. Our support team will review your request and help you find the next step.</p>
      <div class="contact-support-trust"><span>✓ Private support request</span><span>✓ Human review</span><span>✓ Reply within 2 business days</span></div>
    </div>
    <div class="contact-support-hero-art" aria-hidden="true"><div class="contact-support-orbit"></div><div class="contact-support-mail">✉</div><span class="contact-support-spark">✦</span><span class="contact-support-check">✓</span></div>
  </section>

  <div class="contact-support-layout">
    <main class="contact-support-main">
      <div class="contact-support-form-heading">
        <span class="contact-support-kicker">SEND A REQUEST</span>
        <h2>How can we help you today?</h2>
        <p>Complete the details below. Fields marked with * are required.</p>
      </div>
      <form class="contact-support-form" method="post" action="<?= url('/support/new') ?>" enctype="multipart/form-data" id="contactSupportForm">
        <?= csrfField() ?>
        <?php if (!empty($errors)): ?>
          <div class="contact-support-errors" role="alert">
            <strong>Please review the following:</strong>
            <?php foreach ($errors as $field => $msgs): foreach ($msgs as $m): ?><p><?= e($m) ?></p><?php endforeach; endforeach; ?>
          </div>
        <?php endif; ?>

        <div class="contact-support-field">
          <label for="ticket_type">What do you need help with? <span>*</span></label>
          <div class="contact-support-select-wrap"><select id="ticket_type" name="ticket_type">
            <option value="help">Help &amp; assistance</option>
            <option value="report">Report a problem</option>
          </select><span aria-hidden="true">⌄</span></div>
          <small>Choose the option that best describes your request.</small>
        </div>

        <div class="contact-support-field-grid">
          <div class="contact-support-field">
            <label for="category">Category <span>*</span></label>
            <input id="category" name="category" required minlength="2" maxlength="80" placeholder="e.g. Account, listing, verification">
            <small>What part of SDUT Swap is this about?</small>
          </div>
          <div class="contact-support-field">
            <label for="priority">Priority</label>
            <div class="contact-support-select-wrap"><select id="priority" name="priority">
              <option value="low">Low</option><option value="normal" selected>Normal</option><option value="high">High</option>
            </select><span aria-hidden="true">⌄</span></div>
            <small>Choose high only if the issue needs urgent attention.</small>
          </div>
        </div>

        <div class="contact-support-field">
          <label for="subject">Subject <span>*</span></label>
          <input id="subject" name="subject" required minlength="5" maxlength="200" placeholder="A short summary of your request">
        </div>

        <div class="contact-support-field">
          <div class="contact-support-label-row"><label for="body">Tell us more <span>*</span></label><small><span id="supportMessageCount">0</span> / 5000</small></div>
          <textarea id="body" name="body" required minlength="10" maxlength="5000" rows="7" placeholder="Describe what happened, what you expected, and what would help resolve it…"></textarea>
          <small>Include useful details, but never share your password or verification codes.</small>
        </div>

        <div class="contact-support-upload">
          <label for="attachments">Add screenshots or images <span class="optional-tag">OPTIONAL</span></label>
          <div class="contact-support-upload-box"><span class="contact-support-upload-icon">↥</span><div><strong>Choose files to attach</strong><small>Up to 3 images · JPG, PNG or WEBP · 5 MB each</small></div><input id="attachments" name="attachments[]" type="file" multiple accept=".jpg,.jpeg,.png,.webp"></div>
          <p id="supportAttachmentHint" class="contact-support-upload-hint">Attachments help us understand the issue faster. Please hide sensitive information before uploading.</p>
        </div>

        <div class="contact-support-submit-row"><p><span>🔒</span> Your request is visible only to you and authorized support staff.</p><button class="btn btn-primary" type="submit">Send support request <span aria-hidden="true">→</span></button></div>
      </form>
    </main>

    <aside class="contact-support-sidebar">
      <section class="contact-support-side-card contact-support-response-card">
        <span class="contact-support-side-icon">◷</span><span class="contact-support-kicker">RESPONSE TIME</span>
        <h3>You're not left waiting.</h3><p>We aim to respond to support tickets within <strong>2 business days</strong>.</p>
        <div class="contact-support-response-line"><span></span><small>Request received → Team review → Reply</small></div>
      </section>
      <section class="contact-support-side-card">
        <span class="contact-support-kicker">BEFORE YOU SEND</span><h3>A few helpful reminders</h3>
        <ul class="contact-support-reminders"><li><span>✓</span> Give your request a clear subject.</li><li><span>✓</span> Include steps to reproduce the issue, if relevant.</li><li><span>✓</span> Attach a screenshot if it helps explain the problem.</li><li><span>✓</span> Keep passwords and verification codes private.</li></ul>
      </section>
      <section class="contact-support-help-card"><span class="contact-support-kicker">LOOKING FOR A QUICK ANSWER?</span><h3>You may find it here.</h3><p>Browse common questions before submitting a ticket.</p><a href="<?= url('/support') ?>">Visit Help Center <span aria-hidden="true">→</span></a></section>
    </aside>
  </div>
  <p class="contact-support-footer">Need to follow up on an existing request? <a href="<?= url('/support/my') ?>">View your support requests</a>.</p>
</div>