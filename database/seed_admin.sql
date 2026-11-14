-- Admin awal CMS. Username: admin
-- Password ada di .env (ADMIN_SEED_PASSWORD). Ganti setelah login pertama.
USE `tour_travel`;

INSERT INTO `admin_users`
  (`username`, `password_hash`, `status`, `failed_attempts`, `locked_until`, `last_login_at`, `created_at`, `updated_at`)
VALUES
  ('admin', '$2y$12$hmgdWbdFyZz.xKzj6Wsit.odTg/eGQNIHhS511KwUS655bMzipKo6', 'active', 0, NULL, NULL, NOW(), NOW())
ON DUPLICATE KEY UPDATE
  `password_hash` = VALUES(`password_hash`),
  `status` = 'active',
  `failed_attempts` = 0,
  `locked_until` = NULL,
  `updated_at` = NOW();