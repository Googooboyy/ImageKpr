-- Phase 15: per-user upload size tiers and downgrade grace tracking
-- Safe to re-run: skips ADD COLUMN if columns already exist.

SET @db = DATABASE();

-- upload_size_mb
SET @sql = (
  SELECT IF(
    EXISTS(
      SELECT 1 FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'users' AND COLUMN_NAME = 'upload_size_mb'
    ),
    'SELECT 1',
    'ALTER TABLE users ADD COLUMN upload_size_mb TINYINT UNSIGNED NOT NULL DEFAULT 3 AFTER storage_quota_bytes'
  )
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- upload_tier_downgraded_at (after upload_size_mb)
SET @sql = (
  SELECT IF(
    EXISTS(
      SELECT 1 FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'users' AND COLUMN_NAME = 'upload_tier_downgraded_at'
    ),
    'SELECT 1',
    'ALTER TABLE users ADD COLUMN upload_tier_downgraded_at DATETIME NULL DEFAULT NULL AFTER upload_size_mb'
  )
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Reset only invalid values; keep 3,10,30,100 — run phase16_upload_tier_100mb.sql after to map 30→100
UPDATE users
SET upload_size_mb = 3
WHERE upload_size_mb NOT IN (3, 10, 30, 100);
