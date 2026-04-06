-- ImageKpr database schema
-- Import into your existing database (e.g. marsg_imagekpr)

CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  google_sub VARCHAR(255) NOT NULL,
  email VARCHAR(255) NOT NULL,
  name VARCHAR(255) DEFAULT NULL,
  avatar_url VARCHAR(512) DEFAULT NULL,
  is_admin TINYINT(1) NOT NULL DEFAULT 0,
  storage_quota_bytes BIGINT UNSIGNED NULL DEFAULT NULL,
  created_at DATETIME NOT NULL,
  last_login_at DATETIME DEFAULT NULL,
  UNIQUE KEY uq_google_sub (google_sub),
  INDEX idx_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS email_allowlist (
  id INT AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(255) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_allowlist_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS app_settings (
  `key` VARCHAR(128) NOT NULL,
  value TEXT NULL,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS admin_audit_log (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  actor_user_id INT NOT NULL,
  action VARCHAR(128) NOT NULL,
  meta_json JSON NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_actor (actor_user_id),
  INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS images (
  id INT AUTO_INCREMENT PRIMARY KEY,
  filename VARCHAR(255) NOT NULL,
  url VARCHAR(512) NOT NULL,
  date_uploaded DATETIME NOT NULL,
  size_bytes INT UNSIGNED NOT NULL,
  width INT UNSIGNED,
  height INT UNSIGNED,
  tags JSON,
  user_id INT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_date (date_uploaded),
  INDEX idx_size (size_bytes),
  INDEX idx_filename (filename),
  INDEX idx_user (user_id),
  UNIQUE KEY uq_user_filename (user_id, filename)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;