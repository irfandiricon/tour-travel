-- ============================================================
-- Tour Travel - DDL (MySQL 8 / MariaDB 10.4+)
-- Setara dengan app/Database/Migrations/*.
-- Jalankan: mysql -u root -p < database/schema.sql
-- ============================================================

CREATE DATABASE IF NOT EXISTS `tour_travel`
  DEFAULT CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `tour_travel`;

SET NAMES utf8mb4;

-- ------------------------------------------------------------
-- c_information : konten informasi (en wajib, id/ms opsional)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `c_information` (
  `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `title_en`   TEXT NOT NULL,
  `title_id`   TEXT NULL,
  `title_ms`   TEXT NULL,
  `body_en`    TEXT NOT NULL,
  `body_id`    TEXT NULL,
  `body_ms`    TEXT NULL,
  `status`     ENUM('draft','published','deleted') NOT NULL DEFAULT 'draft',
  `created_at` DATETIME NULL,
  `updated_at` DATETIME NULL,
  `deleted_at` DATETIME NULL,
  PRIMARY KEY (`id`),
  KEY `idx_c_information_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- c_news : berita
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `c_news` (
  `id`           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `title_en`     VARCHAR(200) NOT NULL,
  `title_id`     VARCHAR(200) NULL,
  `title_ms`     VARCHAR(200) NULL,
  `body_en`      MEDIUMTEXT NOT NULL,
  `body_id`      MEDIUMTEXT NULL,
  `body_ms`      MEDIUMTEXT NULL,
  `image_path`   VARCHAR(500) NULL,
  `publish_date` DATETIME NULL,
  `status`       ENUM('draft','published','deleted') NOT NULL DEFAULT 'draft',
  `created_at`   DATETIME NULL,
  `updated_at`   DATETIME NULL,
  `deleted_at`   DATETIME NULL,
  PRIMARY KEY (`id`),
  KEY `idx_c_news_status` (`status`),
  KEY `idx_c_news_publish_date` (`publish_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- c_fleet : armada kendaraan
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `c_fleet` (
  `id`             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name_en`        VARCHAR(200) NOT NULL,
  `name_id`        VARCHAR(200) NULL,
  `name_ms`        VARCHAR(200) NULL,
  `description_en` TEXT NULL,
  `description_id` TEXT NULL,
  `description_ms` TEXT NULL,
  `image_path`     VARCHAR(500) NULL,
  `status`         ENUM('draft','published','deleted') NOT NULL DEFAULT 'draft',
  `created_at`     DATETIME NULL,
  `updated_at`     DATETIME NULL,
  `deleted_at`     DATETIME NULL,
  PRIMARY KEY (`id`),
  KEY `idx_c_fleet_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- c_faqs : pertanyaan umum
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `c_faqs` (
  `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `question_en` VARCHAR(500) NOT NULL,
  `question_id` VARCHAR(500) NULL,
  `question_ms` VARCHAR(500) NULL,
  `answer_en`   TEXT NOT NULL,
  `answer_id`   TEXT NULL,
  `answer_ms`   TEXT NULL,
  `status`      ENUM('draft','published','deleted') NOT NULL DEFAULT 'draft',
  `created_at`  DATETIME NULL,
  `updated_at`  DATETIME NULL,
  `deleted_at`  DATETIME NULL,
  PRIMARY KEY (`id`),
  KEY `idx_c_faqs_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- c_gallery : galeri gambar
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `c_gallery` (
  `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `caption_en` VARCHAR(255) NULL,
  `caption_id` VARCHAR(255) NULL,
  `caption_ms` VARCHAR(255) NULL,
  `image_path` VARCHAR(500) NOT NULL,
  `sort_order` INT NOT NULL DEFAULT 0,
  `status`     ENUM('draft','published','deleted') NOT NULL DEFAULT 'draft',
  `created_at` DATETIME NULL,
  `updated_at` DATETIME NULL,
  `deleted_at` DATETIME NULL,
  PRIMARY KEY (`id`),
  KEY `idx_c_gallery_status` (`status`),
  KEY `idx_c_gallery_sort_order` (`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- site_config : konfigurasi landing page + nomor WhatsApp (1 baris)
-- whatsapp_number format E.164, contoh +628123456789; NULL = booking disembunyikan
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `site_config` (
  `id`               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `hero_title_en`    VARCHAR(255) NULL,
  `hero_title_id`    VARCHAR(255) NULL,
  `hero_title_ms`    VARCHAR(255) NULL,
  `hero_subtitle_en` VARCHAR(500) NULL,
  `hero_subtitle_id` VARCHAR(500) NULL,
  `hero_subtitle_ms` VARCHAR(500) NULL,
  `about_body_en`    TEXT NULL,
  `about_body_id`    TEXT NULL,
  `about_body_ms`    TEXT NULL,
  `gallery_title_en` VARCHAR(255) NULL,
  `gallery_title_id` VARCHAR(255) NULL,
  `gallery_title_ms` VARCHAR(255) NULL,
  `contact_body_en`  TEXT NULL,
  `contact_body_id`  TEXT NULL,
  `contact_body_ms`  TEXT NULL,
  `contact_email`    VARCHAR(255) NULL,
  `contact_address`  VARCHAR(500) NULL,
  `whatsapp_number`  VARCHAR(16) NULL,
  `site_name_en`        VARCHAR(150) NULL,
  `site_name_id`        VARCHAR(150) NULL,
  `site_name_ms`        VARCHAR(150) NULL,
  `meta_description_en` VARCHAR(320) NULL,
  `meta_description_id` VARCHAR(320) NULL,
  `meta_description_ms` VARCHAR(320) NULL,
  `meta_keywords_en`    VARCHAR(255) NULL,
  `meta_keywords_id`    VARCHAR(255) NULL,
  `meta_keywords_ms`    VARCHAR(255) NULL,
  `og_image`            VARCHAR(500) NULL,
  `about_title_en`       VARCHAR(150) NULL,
  `about_title_id`       VARCHAR(150) NULL,
  `about_title_ms`       VARCHAR(150) NULL,
  `about_image`          VARCHAR(500) NULL,
  `created_at`       DATETIME NULL,
  `updated_at`       DATETIME NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- admin_users : akun CMS (password disimpan sebagai hash)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `admin_users` (
  `id`              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `username`        VARCHAR(100) NOT NULL,
  `password_hash`   VARCHAR(255) NOT NULL,
  `status`          ENUM('active','inactive') NOT NULL DEFAULT 'active',
  `failed_attempts` INT NOT NULL DEFAULT 0,
  `locked_until`    DATETIME NULL,
  `last_login_at`   DATETIME NULL,
  `created_at`      DATETIME NULL,
  `updated_at`      DATETIME NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_admin_users_username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- visitor_logs : kunjungan landing page (tanpa IP mentah; hash SHA-256)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `visitor_logs` (
  `id`            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `visit_date`    DATE NOT NULL,
  `visitor_hash`  CHAR(64) NOT NULL,
  `page`          VARCHAR(255) NOT NULL,
  `lang`          CHAR(2) NOT NULL,
  `device_type`   ENUM('desktop','mobile','tablet','bot') NOT NULL,
  `referrer_host` VARCHAR(255) NULL,
  `is_bot`        TINYINT(1) NOT NULL DEFAULT 0,
  `created_at`    DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_visitor_logs_visit_date` (`visit_date`),
  KEY `idx_visitor_logs_visitor_hash` (`visitor_hash`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- site_events : event situs (mis. booking_click via WhatsApp)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `site_events` (
  `id`           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `event_type`   VARCHAR(50) NOT NULL,
  `ref_id`       INT UNSIGNED NULL,
  `ref_label`    VARCHAR(200) NULL,
  `lang`         CHAR(2) NOT NULL,
  `visitor_hash` CHAR(64) NOT NULL,
  `created_at`   DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_site_events_type_created` (`event_type`, `created_at`),
  KEY `idx_site_events_ref` (`ref_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
