-- Phase 7: users, allowlist (run once on existing DB)

CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  google_sub VARCHAR(255) NOT NULL,
  email VARCHAR(255) NOT NULL,
  name VARCHAR(255) DEFAULT NULL,
  avatar_url VARCHAR(512) DEFAULT NULL,
  is_admin TINYINT(1) NOT NULL DEFAULT 0,
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

-- Legacy images need user_id set or they will not appear after login. See phase7_backfill_images_user_id.sql.