<div class="container support-center">
  <header class="support-hero">
    <div class="support-hero-copy">
      <span class="support-eyebrow">SDUT SWAP • HELP CENTER</span>
      <h1>How can we help?</h1>
      <p>Find quick answers, get help with your account, or contact our support team.</p>
      <div class="support-hero-actions">
        <a class="btn btn-primary" href="<?= url('/support/new') ?>">Contact support <span aria-hidden="true">→</span></a>
        <a class="support-hero-link" href="<?= url('/support/my') ?>">View my support requests</a>
      </div>
    </div>
    <div class="support-hero-art" aria-hidden="true"><span class="support-art-bubble bubble-one">?</span><span class="support-art-bubble bubble-two">✉</span><div class="support-art-icon">✦</div></div>
  </header>

  <section class="support-quick-grid" aria-label="Support options">
    <a class="support-quick-card" href="<?= url('/verify') ?>"><span class="support-quick-icon">✓</span><span><strong>Student verification</strong><small>Verify your student identity</small></span><b aria-hidden="true">→</b></a>
    <a class="support-quick-card" href="<?= url('/safety') ?>"><span class="support-quick-icon">⌑</span><span><strong>Safety center</strong><small>Trade safely on campus</small></span><b aria-hidden="true">→</b></a>
    <a class="support-quick-card" href="<?= url('/support/my') ?>"><span class="support-quick-icon">▤</span><span><strong>My requests</strong><small>Track tickets and reports</small></span><b aria-hidden="true">→</b></a>
  </section>

  <div class="support-content-grid">
    <section class="support-faq-section">
      <div class="support-section-heading"><div><span class="support-eyebrow">POPULAR QUESTIONS</span><h2>Frequently asked questions</h2></div></div>
      <div class="support-faq-list">
        <details class="support-faq-item"><summary>How do I verify my student identity?</summary><p>Go to <a href="<?= url('/verify') ?>">Verify</a> and upload a clear photo of your Student Card. Our team reviews submissions within 2 business days. Verified students get a badge and can publish listings.</p></details>
        <details class="support-faq-item"><summary>How do I buy or sell safely?</summary><p>Meet in a public campus location, inspect the item before paying, and keep all communication inside SDUT Swap chat. Never pay in advance or share bank details. Read our <a href="<?= url('/safety') ?>">Safety Tips</a>.</p></details>
        <details class="support-faq-item"><summary>How do I message a seller?</summary><p>Open any listing and click “Message Seller”. Conversations are linked to the listing so both sides know what they're discussing.</p></details>
        <details class="support-faq-item"><summary>What if something goes wrong?</summary><p>Report the listing, user, or conversation from the relevant page, or open a support ticket. Our team reviews every report.</p></details>
        <details class="support-faq-item"><summary>How do I mark an item as sold?</summary><p>Go to <a href="<?= url('/my-listings') ?>">My Listings</a>, edit the listing, and set its status to “Sold”.</p></details>
      </div>
    </section>
    <aside class="support-contact-card">
      <span class="support-contact-icon">✉</span>
      <span class="support-eyebrow">WE'RE HERE TO HELP</span>
      <h2>Still need a hand?</h2>
      <p>Send our team the details and we'll get back to you within 2 business days.</p>
      <a class="btn btn-primary btn-block" href="<?= url('/support/new') ?>">Open a support ticket</a>
      <div class="support-response-note"><span aria-hidden="true">◷</span><span><strong>Response time</strong><small>Within 2 business days</small></span></div>
    </aside>
  </div>
</div>
