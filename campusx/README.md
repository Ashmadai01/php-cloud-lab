# SDUT Swap — Your Campus. Your Marketplace.

A complete university student marketplace: verified students buy and sell items on campus, chat on-platform, and get support through tickets and reports. Admins manage verification, listings, users, tickets, and reports.

**Stack:** PHP 8.2+ (clean layered MVC, no framework), MySQL 8+/MariaDB (InnoDB, utf8mb4), vanilla HTML/CSS/JS. Chat uses secure AJAX polling (not WebSockets).

## Quick start (XAMPP)

1. **Requirements:** PHP 8.2+ with `pdo_mysql`, `mbstring`, `fileinfo`, `gd`; MySQL/MariaDB; Apache (XAMPP) or PHP built-in server.
2. **Clone/copy** this project into your web root, e.g. `C:\xampp\htdocs\campusx`.
3. **Create the database:**
   ```sql
   CREATE DATABASE campusx CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   CREATE USER 'campusx_app'@'localhost' IDENTIFIED BY 'change-me-strong-password';
   GRANT SELECT, INSERT, UPDATE, DELETE ON campusx.* TO 'campusx_app'@'localhost';
   ```
4. **Apply the schema:** `mysql -u root -p < database/schema.sql` (or `php tools/migrate.php`).
   **Already have a database from an earlier version?** Don't re-create it — run `php tools/upgrade.php` instead (see *Upgrading* below).
5. **Configure:** `copy .env.example .env` and set `DB_*`, `APP_URL` (e.g. `http://localhost/campusx/public`), and mail settings. Never commit `.env`.
6. **Point Apache at `public/`** (recommended) — or set the document root to `campusx/public`. The root `.htaccess` blocks direct access to `app/`, `database/`, `storage/`, etc. if the docroot is the project root.
7. **Create the first admin (CLI only — never via public registration):**
   ```
   php tools/create_admin.php "Admin Name" admin@example.com
   ```
   You'll be prompted for a password (min 12 chars). There is no default admin password.
8. **Register** a student account at `/register`, verify your email (check `storage/logs/mail.log` when `MAIL_ENABLED=false`), submit Student Card verification at `/verify`, and approve it as admin at `/admin/verifications`.

### Local dev without Apache
```
php -S 0.0.0.0:8080 -t public
```
Then open `http://localhost:8080` (set `APP_URL=http://localhost:8080` in `.env`).

## Currency
All prices are in Chinese yuan (¥ / CNY). Sellers cannot pick another currency; the server forces `CNY`. Times are stored in UTC and shown in `DISPLAY_TIMEZONE` (default `Asia/Shanghai`).

## Upgrading an existing database
`php tools/upgrade.php` is idempotent (safe to run twice). It adds the new tables/columns (blocking, sessions device labels, unique student numbers, `verification_status`, multi-attachments) and relabels existing listings to CNY **without converting amounts**. It needs a DB account with `ALTER`/`CREATE`/`INDEX` rights: set `DB_MIGRATE_USER` / `DB_MIGRATE_PASS` in `.env` (the app's own least-privilege account is not enough). After upgrading, everyone is signed out once, because sessions are now tracked server-side.

## Retention (Student Cards, attachments, evidence)
Private files are deleted by a scheduled job — nothing else deletes them:
```
php tools/purge_private_files.php --dry-run   # see what would be deleted
php tools/purge_private_files.php             # delete
```
Student Card images go `CARD_RETENTION_DAYS` (default 30) after the review decision; ticket attachments and report evidence go `EVIDENCE_RETENTION_DAYS` (default 180) after the ticket/report is closed. Pending submissions are never purged. Schedule it daily (cron on Linux; Task Scheduler on Windows/XAMPP, e.g. `C:\xampp\php\php.exe C:\xampp\htdocs\campusx\tools\purge_private_files.php`).

## Mail setup
By default `MAIL_ENABLED=false` and emails are written to `storage/logs/mail.log` (local dev only). For production, set `MAIL_ENABLED=true` and configure your SMTP/sendmail provider via environment variables. Credentials come only from the environment — never hardcode them.

## Production deployment
- Serve only `public/` as the document root; deny `.env`, `.git`, `storage/`, `database/`, `tests/`, `tools/`.
- Terminate HTTPS at the web server/reverse proxy and redirect HTTP → HTTPS. HSTS is sent only when `APP_ENV=production`.
- Set `APP_ENV=production`, `APP_DEBUG=false`, strong `DB_PASS`, and a least-privilege DB user (not root).
- Rotate secrets via your secret manager; see `SECURITY_REVIEW.md`.

## Tests
```
php tests/run.php
```
Runs unit/integration tests against an in-memory SQLite database: validators, auth hashing, tokens, listing CRUD/cover photos, favorites, verification (status sync, unique student numbers), blocking, session tracking, admin re-authentication, retention, attachments/evidence, middleware order and a **route-policy test** (every `/admin` route has the admin middleware; every POST route has CSRF), chat IDOR, tickets/reports, CSRF, XSS, SQLi, upload MIME. Exit code 0 = all passed. Run it after every change and after upgrading PHP.

## Project structure
```
app/
  Core/         Config, Database, Session, Csrf, Auth, Validator, Mailer, Upload, Audit, RateLimiter, Router, helpers
  Middleware/   RequireAuth, RequireAdmin, RequireCsrf, RequireRecentAuth (admin step-up), RateLimit - attached per route in bootstrap.php
  Services/     Block, Session (devices), Reauth, Verification, Retention, Alert - business rules shared by controllers/tools
  Controllers/  Home, Auth, Listing, Profile, Verification, Chat, Block, Support, Admin, Upload
  Repositories/ User, Token, Listing, Verification, Chat, Block, Session, Support, Audit, RateLimit (PDO prepared statements)
  Views/        layout + page templates (home, listings, auth, profile, verification, chat, support, admin, pages, errors)
public/
  index.php     front controller
  assets/       CSS design system, JS (toasts, gallery, chat polling), img/placeholder.svg
database/       schema.sql, seed.sql (safe fake data)
tools/          create_admin.php, migrate.php (fresh install), upgrade.php (existing DB), purge_private_files.php (retention)
tests/          run.php test suite
storage/        uploads/ (public images), private/ (cards/evidence), logs/
```

## Troubleshooting
- **DB connection failed:** check `.env` DB settings and that MySQL is running.
- **Verification email not received:** with `MAIL_ENABLED=false`, read `storage/logs/mail.log`.
- **Images not showing:** ensure `storage/uploads` is writable and `APP_URL` matches your local URL.
- **403 on admin pages:** only `role=admin` accounts can access `/admin/*`; create one via `tools/create_admin.php`.
