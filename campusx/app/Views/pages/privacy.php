<div class="container"><div class="page-head"><h1>Privacy Policy</h1></div>
<div class="panel" style="max-width:820px">
<h3>What we collect</h3><p>Account details (name, email), listing content and photos, messages, support tickets, and — for verification — your Student Card image and student number (stored as a secure hash).</p>
<h3>How we use it</h3><p>To operate the marketplace: authentication, verification, listings, messaging, support, and moderation. We never sell personal data.</p>
<h3>Student Card images</h3><p>Stored in private storage outside the public web root, accessible only to verification staff through authenticated endpoints. Access is logged. Each image is automatically deleted <?= (int) \SdutSwap\Core\Config::int('CARD_RETENTION_DAYS', 30) ?> days after your submission has been reviewed. Support attachments and report evidence are deleted <?= (int) \SdutSwap\Core\Config::int('EVIDENCE_RETENTION_DAYS', 180) ?> days after the ticket or report is closed.</p>
<h3>Security</h3><p>Passwords are hashed (Argon2id), tokens are stored hashed, and all data is transmitted over HTTPS in production.</p>
<h3>Your rights</h3><p>Contact support to request access, correction, or deletion of your personal data.</p>
</div></div>
