-- Phase 20: widen upload_size_mb so Platinum (500MB) persists correctly
-- Safe to re-run: only modifies the column if it is not already SMALLINT UNSIGNED.
-- Also repairs rows that already have Platinum storage but a non-Platinum upload tier.

SET @db = DATABASE();

SET @sql = (
  SELECT IF(
    EXISTS(
      SELECT 1
      FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = @db
        AND TABLE_NAME = 'users'
        AND COLUMN_NAME = 'upload_size_mb'
        AND DATA_TYPE = 'smallint'
        AND COLUMN_TYPE LIKE '%unsigned%'
    ),
    'SELECT 1',
    'ALTER TABLE users MODIFY COLUMN upload_size_mb SMALLINT UNSIGNED NOT NULL DEFAULT 3'
  )
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Repair Platinum preset rows that previously could not persist 500 in a TINYINT column.
UPDATE users
SET upload_size_mb = 500,
    upload_tier_downgraded_at = NULL
WHERE storage_quota_bytes = 10737418240
  AND upload_size_mb <> 500;
