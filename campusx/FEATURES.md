# SDUT Swap — Feature Checklist & Limitations

## Implemented

### Public
- [x] Home: hero, search, featured/latest listings, category navigation, trust & safety, CTA
- [x] Marketplace browse: keyword search, category/condition filters, price range, sorting, pagination
- [x] Product details: gallery, price, description, category, condition, seller + verification badge, posted date, Message Seller, report link
- [x] About, Community Guidelines, Privacy Policy, Terms, Safety Tips
- [x] Register, login, forgot/reset password, email verification (single-use expiring tokens)

### Accounts
- [x] Registration with university-email policy hook (`ALLOWED_EMAIL_DOMAINS`), terms acceptance
- [x] Argon2id password hashing, generic anti-enumeration responses, session rotation
- [x] Profile (name, avatar, university, bio, verification status), password change
- [x] Roles `student`/`admin` enforced server-side; no public admin creation; CLI bootstrap (`tools/create_admin.php`)

### Student Card verification
- [x] Submit name, student number (hashed), university, card image (private storage)
- [x] Statuses `not_submitted`/`pending`/`verified`/`rejected` (stored on the user, kept in sync by the decision) with rejection reason
- [x] One student number can be held by only one pending/verified account (normalised, enforced by a unique index; a rejection releases it)
- [x] Admin queue with private card preview (authenticated, re-authenticated, audited), approve/reject
- [x] Card images are deleted automatically after `CARD_RETENTION_DAYS` by `tools/purge_private_files.php` (must be scheduled)
- [x] Verified badge; configurable policy gates listing/chat (`REQUIRE_VERIFIED_TO_LIST/CHAT`)

### Marketplace
- [x] Listing CRUD with soft-delete (`removed`), statuses draft/active/sold/hidden/removed
- [x] Multiple photos with ordering + delete; main image = first photo, shown on every listing card (local placeholder when a listing has no photo)
- [x] All prices in Chinese yuan (¥ / CNY)
- [x] My Listings, favorites, report listing/user, owner-or-admin edit/delete

### Messaging
- [x] Conversation from product page, linked to listing
- [x] Inbox, thread, send, timestamps, unread counts, mark-read
- [x] AJAX polling with backoff (4 s → 30 s) — not WebSockets
- [x] Participant-only access (IDOR tested), rate limits, plain-text escaped output
- [x] Report a conversation or a single message (reporter must be a participant); staff can read a chat only through a report about it, and each view is audit-logged
- [x] Block / unblock a user (either direction stops new messages); blocked list on the profile page; staff accounts cannot be blocked

### Support Center
- [x] Separate Reports & Complaints vs Help & Assistance ticket types
- [x] Tickets: category, subject, description, status, priority, replies, history; up to 3 image attachments per message, viewable only by the ticket owner and admins
- [x] Reports: user/listing/message/conversation targets (validated), up to 3 evidence images viewable by admins (each view audited), status, moderator notes
- [x] Users see only own tickets/reports; FAQ page; submission feedback

### Admin
- [x] Dashboard metrics (users, verified, pending, active listings, open tickets/reports)
- [x] User suspend/restore with reason (suspending also signs the user out everywhere); role-safe (cannot suspend self/other admins)
- [x] Step-up authentication: suspend/restore, listing moderation, verification decisions and opening a Student Card need the admin's password re-entered within `ADMIN_REAUTH_TTL` seconds
- [x] Listing hide/remove/restore with reason + audit trail
- [x] Verification queue, ticket/report queues with filters + status changes
- [x] Append-only audit log; security-event viewer with severity filters

### Security
- [x] PDO prepared statements, allowlisted sorting, utf8mb4, FKs, indexes
- [x] CSRF tokens, secure session cookies, security headers, CSP
- [x] Upload MIME validation + GD re-encode, random filenames, private storage
- [x] Rate limiting, structured security logging, generic error pages
- [x] Sessions & devices: server-side session tracking, per-device sign-out, "sign out all other devices"; password change signs out other devices, password reset and suspension sign out all
- [x] Optional e-mail alerts for high-severity events and rate-limit lockouts (`SECURITY_ALERT_EMAIL`, throttled)
- [x] Layered architecture: route-level middleware (auth/admin/CSRF/step-up) + services; a test asserts every `/admin` route has the admin middleware and every POST route has CSRF

## Honest limitations
- **The code changes in this revision were written without a PHP interpreter available, so the test suite has NOT been run on them.** Run `php tests/run.php` and click through the flows below before relying on it.
- Chat uses AJAX polling (no WebSocket server configured).
- No admin MFA yet (documented as pre-production requirement). Step-up password confirmation is not a second factor.
- Retention only happens if `tools/purge_private_files.php` is scheduled; the app does not run it by itself.
- Student numbers are stored as an unsalted SHA-256 of a low-entropy value; if the database leaks they can be brute-forced. Consider a keyed hash (HMAC with a secret from the environment).
- Blocking is per pair of users; it does not hide a blocked user's listings.
- Mail uses PHP `mail()`; production needs SMTP configuration.
- No antivirus scanning of uploads (GD re-encode only).
- No payments — SDUT Swap is a discovery/messaging platform; transactions happen in person.
- Seed data is fake/demo only; demo password hashes in `seed.sql` are placeholders.
- Tests run against SQLite in-memory; MySQL-specific behavior (FULLTEXT) is not exercised by the suite.
