-- Phase 8: admin foundation — run once on existing DB after Phase 7

ALTER TABLE users
  ADD COLUMN storage_quota_bytes BIGINT UNSIGNED NULL DEFAULT NULL AFTER is_admin;

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
