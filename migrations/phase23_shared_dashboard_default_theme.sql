-- Phase 23: default theme for shared dashboard guest view (light/dark).

SET @db := DATABASE();

SET @sql := (
  SELECT IF(
    EXISTS(
      SELECT 1
      FROM INFORMATION_SCHEMA.COLUMNS
      WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'shared_dashboards' AND COLUMN_NAME = 'default_theme'
    ),
    'SELECT ''shared_dashboards.default_theme already exists'' AS info',
    "ALTER TABLE shared_dashboards ADD COLUMN default_theme ENUM('light','dark') NOT NULL DEFAULT 'light' AFTER allow_download"
  )
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
