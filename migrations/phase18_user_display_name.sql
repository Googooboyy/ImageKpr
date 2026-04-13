-- Phase 18: optional display_name (editable in app); Google name stays in users.name
-- Safe to re-run: skips ADD COLUMN if column already exists.

SET @db = DATABASE();

SET @sql = (
  SELECT IF(
    EXISTS(
      SELECT 1 FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'users' AND COLUMN_NAME = 'display_name'
    ),
    'SELECT 1',
    'ALTER TABLE users ADD COLUMN display_name VARCHAR(255) NULL DEFAULT NULL AFTER name'
  )
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
