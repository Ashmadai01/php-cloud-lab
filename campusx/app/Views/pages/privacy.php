<div class="container privacy-page">
    <section class="privacy-hero">
        <div class="privacy-hero-copy">
            <span class="privacy-eyebrow">SDUT SWAP · YOUR PRIVACY</span>
            <h1>Your information.<br><span>Handled with care.</span></h1>
            <p>This page explains what information SDUT Swap collects, why it is used, and how sensitive student verification materials are handled.</p>
            <div class="privacy-hero-meta"><span>Clear information</span><span>•</span><span>Practical privacy details</span></div>
        </div>
        <div class="privacy-hero-art" aria-hidden="true"><div class="privacy-orbit"></div><div class="privacy-shield">⌑</div><span class="privacy-lock">✓</span></div>
    </section>

    <div class="privacy-notice"><span aria-hidden="true">ⓘ</span><p><strong>Our commitment:</strong> Personal data is used to operate the marketplace and its safety features. We do not sell personal data.</p></div>

    <div class="privacy-layout">
        <aside class="privacy-toc">
            <strong>IN THIS PAGE</strong>
            <a href="#information">01 · Information collected</a>
            <a href="#use">02 · How information is used</a>
            <a href="#verification">03 · Student verification</a>
            <a href="#retention">04 · Storage and retention</a>
            <a href="#security">05 · Security</a>
            <a href="#rights">06 · Your privacy requests</a>
        </aside>

        <main class="privacy-content">
            <section class="privacy-intro">
                <span class="privacy-kicker">PRIVACY AT A GLANCE</span>
                <h2>Know what happens to your information.</h2>
                <p>Using a student marketplace can involve account details, conversations, listings, and verification materials. The sections below describe the information identified by this policy and the purposes for which SDUT Swap uses it.</p>
            </section>

            <section class="privacy-clause" id="information"><span>01</span><div><h2>What information we collect</h2><p>Depending on how you use the platform, SDUT Swap collects and processes:</p><ul><li><strong>Account details:</strong> your name and email address.</li><li><strong>Marketplace content:</strong> listing information, uploaded photos, and messages.</li><li><strong>Support information:</strong> support tickets and related report evidence.</li><li><strong>Student verification details:</strong> your Student Card image and student number for verification. The student number is stored as a secure hash.</li></ul></div></section>

            <section class="privacy-clause" id="use"><span>02</span><div><h2>How we use your information</h2><p>Information is used to operate and maintain the SDUT Swap marketplace, including:</p><ul><li>Account authentication and access.</li><li>Student identity verification.</li><li>Creating and managing listings.</li><li>Enabling communication between users.</li><li>Handling support requests, reports, and moderation.</li></ul><p>We do not sell personal data.</p></div></section>

            <section class="privacy-clause" id="verification"><span>03</span><div><h2>Student Card images and verification</h2><p>Student Card images are stored in private storage outside the public web root. They are accessible only to verification staff through authenticated endpoints, and access is logged.</p><p>Each Student Card image is automatically deleted <strong><?= (int) \\SdutSwap\\Core\\Config::int('CARD_RETENTION_DAYS', 30) ?> days</strong> after your submission has been reviewed.</p></div></section>

            <section class="privacy-clause" id="retention"><span>04</span><div><h2>Storage and retention</h2><p>Different materials have different retention periods:</p><ul><li><strong>Student Card images:</strong> automatically deleted <?= (int) \\SdutSwap\\Core\\Config::int('CARD_RETENTION_DAYS', 30) ?> days after the submission has been reviewed.</li><li><strong>Support attachments and report evidence:</strong> deleted <?= (int) \\SdutSwap\\Core\\Config::int('EVIDENCE_RETENTION_DAYS', 180) ?> days after the related ticket or report is closed.</li></ul><p>These periods are based on the platform's configured retention settings.</p></div></section>

            <section class="privacy-clause" id="security"><span>05</span><div><h2>How we protect information</h2><ul><li>Passwords are hashed using Argon2id.</li><li>Tokens are stored as hashes.</li><li>In production, data is transmitted over HTTPS.</li><li>Student Card images are kept in private storage outside the public web root, with authenticated access for verification staff and access logging.</li></ul><p>These measures are intended to protect account and verification information; no online service can promise that every risk is eliminated.</p></div></section>

            <section class="privacy-clause" id="rights"><span>06</span><div><h2>Your privacy requests</h2><p>You can contact support to request access to, correction of, or deletion of your personal data.</p><a class="privacy-contact-link" href="/support">Contact the Support Center <span aria-hidden="true">→</span></a></div></section>
        </main>
    </div>

    <section class="privacy-bottom">
        <div><span class="privacy-kicker">QUESTIONS ABOUT YOUR DATA?</span><h2>We’re here to help.</h2><p>Contact the support team if you want to request access, correction, or deletion of your personal data.</p></div>
        <a class="btn btn-primary" href="/support">Go to Support <span aria-hidden="true">→</span></a>
    </section>
    <p class="privacy-footnote">This page describes the data practices currently specified for SDUT Swap. For a request about your information, contact the Support Center.</p>
</div>