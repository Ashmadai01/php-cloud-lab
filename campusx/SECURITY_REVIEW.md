# SDUT Swap — Security Review

> This document describes controls implemented in SDUT Swap, how they were tested, and residual risks. It is **not** a security guarantee. SDUT Swap should undergo an independent review before handling real student data at scale.

## 1. Threat model (summary)

| Threat | Example | Primary controls |
|---|---|---|
| Account takeover | Credential stuffing, token theft | Argon2id hashing, hashed single-use tokens, rate limits, session rotation |
| Enumeration | "Does this email exist?" | Generic auth/reset responses |
| SQL injection | `' OR 1=1 --` in search | PDO prepared statements everywhere; allowlisted sort/order |
| XSS (stored/reflected) | `<script>` in listing title | `htmlspecialchars` output encoding; plain-text messages; safe DOM APIs in JS |
| CSRF | Forged POST from another site | Synchronizer tokens on every state-changing request |
| IDOR/BOLA | Reading another user's chat/ticket/card | Server-side participant/owner checks; 403 + security event on violation |
| Privilege escalation | Non-admin hitting `/admin/*` | `Auth::requireAdmin()` server-side on every admin route |
| Malicious uploads | PHP shell as "image" | `finfo` MIME check, GD re-encode, random filenames, outside web root |
| Session fixation | Pre-login session reuse | `session_regenerate_id(true)` on login/password change |
| Spam/flooding | Chat/report floods | Rate limits (login, register, reset, chat, tickets, reports) |
| Info disclosure | `.env`, card images, SQL errors | Docroot = `public/` only; private storage; generic error pages; `display_errors` off in production |

## 2. Controls implemented

- **Passwords:** `password_hash()` with Argon2id (memory 64 MiB, time 4, threads 2), bcrypt fallback; `password_verify()`; rehash on login; 10+ char policy with common-password blocklist.
- **Tokens:** email verification (24 h) and password reset (1 h) stored as SHA-256 hashes, single-use, invalidated on re-issue.
- **Sessions:** HttpOnly, SameSite=Lax, Secure in production, strict mode, idle (30 min) + absolute (2 h) timeouts, ID rotation on login/privilege change, invalidation on logout.
- **CSRF:** 256-bit synchronizer tokens validated server-side on all POSTs; failures logged as security events.
- **Authorization:** route-level middleware (`RequireAuth`, `RequireAdmin`, `RequireCsrf`, `RequireRecentAuth`) attached in `bootstrap.php`, with the controllers' own checks kept as defense in depth; owner checks on listing edit/delete; participant checks on chat; owner-or-admin on tickets and attachments; admin-only card/evidence viewing with audit logging.
- **Admin step-up:** suspend/restore, listing moderation, verification decisions and Student Card viewing require the admin's password re-entered within `ADMIN_REAUTH_TTL` (default 10 min). The post-confirmation redirect accepts only `/admin...` paths. Confirmation attempts are rate-limited and failures logged.
- **Sessions & devices:** each sign-in is tracked in `user_sessions` (hashed session ID, readable device label, no raw User-Agent). A session is valid only while its row is un-revoked and unexpired. Logout, idle/absolute timeout, password change (other devices), password reset and suspension revoke sessions. The tracking check fails open on database errors (logged) so an outage does not lock everyone out.
- **Blocking:** a block in either direction stops new conversations and new messages; staff cannot be blocked.
- **Private files:** ticket attachments are served only to the ticket owner or an admin; report evidence only to admins; every admin view is audited; conversations are shown to staff only through a report filed by a participant.
- **Retention:** `tools/purge_private_files.php` deletes Student Card images, ticket attachments and report evidence after the configured periods (it must be scheduled).
- **Alerts:** optional e-mail for high-severity events and rate-limit lockouts, throttled to one per type per 15 minutes.
- **Uploads:** JPEG/PNG/WebP only via `finfo`, GD re-encode (strips metadata), dimension limits, 5 MB cap, random 128-bit filenames, public images under `storage/uploads`, Student Cards/evidence under `storage/private` (outside web root, served only via authenticated admin endpoints with `no-store`).
- **Student numbers:** stored only as SHA-256 hash + last 4 digits for display; never logged in full. Normalised (letters/digits, upper-case) and unique among pending/verified submissions via a database unique index.
- **Headers:** CSP (`default-src 'self'`, `frame-ancestors 'none'`), `X-Content-Type-Options: nosniff`, `Referrer-Policy`, `Permissions-Policy`, HSTS in production only.
- **Rate limiting:** DB-backed buckets for login (10/15 min), register (5/h), forgot/reset, verification submit (3/day), chat send (30/min + 50/5 min), tickets/reports (10/h).
- **Audit & monitoring:** `admin_audit_logs` (append-only from app perspective) for admin actions; `security_events` for failed logins, CSRF failures, IDOR attempts, rate-limit blocks, rejected uploads. IPs stored as keyed hashes.
- **Environment:** `.env` never committed (`.gitignore`); `.env.example` placeholders only; production fails closed if `DB_PASS` is empty.

## 3. Test evidence

Run `php tests/run.php`. The suite (SQLite in-memory) covers:

- Validators (email, password policy, allowlists)
- Argon2id/bcrypt hashing + verification
- Single-use token enforcement (verification + reset)
- Listing CRUD + search; SQLi payload (`' OR 1=1 --`) returns 0 results
- Favorites toggle
- Verification: student number hashed (64-char), last-4 only; status sync; duplicate-number rejection incl. race via unique index; rejection frees the number
- Blocking (both directions, idempotent, staff exempt); session tracking (revoke own only, rotation, sign-out-others, expiry, purge); device labels
- Admin step-up: freshness window, future/non-integer timestamps rejected, redirect-target allowlist (open-redirect payloads)
- Retention: old cards/attachments/evidence deleted, recent/pending/open ones kept, idempotent
- Middleware order; **route policy**: every `/admin` route has `RequireAdmin`, every POST has `RequireCsrf`, sensitive admin actions have `RequireRecentAuth`
- Chat IDOR: outsider `isParticipant()` = false; unread/read flow
- Tickets + reports CRUD and status transitions
- CSRF token generation/validation/rejection
- XSS: `<script>` escaped to entities
- Upload: PHP file detected as non-image MIME

**Results:** this revision's changes were written in an environment with no PHP interpreter, so **the suite has not been run on them** and no pass/fail result is claimed. Run `php tests/run.php` before use. Tests exercise repository/service logic and the route table; full HTTP-level testing (real sessions, uploads, redirects) and penetration testing were not performed.

## 4. Residual risks & deployment caveats

1. **No MFA** for admins — recommended before production (TOTP/WebAuthn). Step-up password confirmation raises the bar for a hijacked session but is not a second factor.
2. **Chat is AJAX polling**, not WebSockets — fine for campus scale; polling interval backs off (4 s → 30 s).
3. **Mail** uses PHP `mail()` transport; configure a proper SMTP provider in production.
4. **No antivirus scanning** of uploads — GD re-encode mitigates most image polyglots, but add ClamAV in production if feasible.
5. **Rate limiting is per-IP** (no distributed store) — adequate for single-server XAMPP/production; add Redis for multi-server.
6. **Student Card verification is manual review**, not infallible identity proof — stated in the UI.
7. **HTTPS must be configured** at the server/reverse proxy; the app cannot enforce it alone. HSTS is only sent in production mode.
8. **Backups, log retention, and log tamper-resistance** are deployment responsibilities — define retention (e.g. 90 days) and restrict log access.
9. **Retention depends on a scheduled job** (`tools/purge_private_files.php`); if it is not scheduled, files are kept indefinitely.
10. **Student-number hashes are unsalted SHA-256** of low-entropy values; a database leak would allow brute force. Use an HMAC with a server secret if this matters.
11. **Session tracking fails open** on database errors, by design (availability over lockout); watch the error log.
12. **Dependency audit:** the app has zero Composer runtime dependencies (pure PHP + PDO), minimizing supply-chain surface. Run `composer audit` if dependencies are added.

## 5. Pre-production checklist

- [ ] HTTPS + redirect + HSTS verified
- [ ] `APP_ENV=production`, `APP_DEBUG=false`, `display_errors=Off`
- [ ] Docroot = `public/`; `.env`, `storage/private`, `database/`, `tests/`, `tools/` unreachable
- [ ] Least-privilege DB user; strong credentials in secret manager
- [ ] Admin MFA enabled (when available); first admin created via CLI
- [ ] `tools/purge_private_files.php` scheduled daily; `tools/upgrade.php` run on existing databases
- [ ] `php tests/run.php` passes on the target PHP version
- [ ] Mail provider configured; SPF/DKIM/DMARC set
- [ ] Backups encrypted; log retention defined
- [ ] Independent security review scheduled
