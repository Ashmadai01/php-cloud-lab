-- SDUT Swap safe fake seed data (no real personal data, no real credentials).
-- Apply after schema.sql: mysql -u root -p < database/seed.sql
USE campusx;

-- Demo student accounts (password: DemoPass123! — change immediately; demo only)
INSERT INTO users (name, email, password_hash, role, university, bio, is_verified, verification_status, email_verified_at) VALUES
('Demo Student','demo.student@example.com','$2y$12$demo.hash.placeholder','student','Example University','Demo account for local development.',1,'verified',NOW()),
('Demo Buyer','demo.buyer@example.com','$2y$12$demo.hash.placeholder','student','Example University','Demo buyer account.',0,'not_submitted',NOW())
ON DUPLICATE KEY UPDATE name=VALUES(name);

-- NOTE: demo password hashes above are placeholders. Run `php tools/create_admin.php` /
-- register via UI to create working accounts. Seed listings reference demo users by email lookup below.
SET @seller = (SELECT id FROM users WHERE email='demo.student@example.com' LIMIT 1);
SET @cat = (SELECT id FROM categories WHERE slug='textbooks' LIMIT 1);

INSERT INTO listings (seller_id, title, description, price, currency, category_id, item_condition, campus, status) VALUES
(@seller,'Introduction to Computer Science — Textbook (3rd Ed.)','Lightly used textbook, no highlighting. Pickup on campus.',25.00,'CNY',@cat,'like_new','North Campus','active'),
(@seller,'Scientific Calculator TI-84','Works perfectly, includes cover and batteries.',45.00,'CNY',@cat,'good','North Campus','active')
ON DUPLICATE KEY UPDATE title=VALUES(title);
