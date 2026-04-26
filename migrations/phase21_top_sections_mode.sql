-- Phase 21: add per-user top sections layout preference
-- Stores whether dashboard top sections use collapsible or classic layout.

SET @db := DATABASE();

SET @sql := (
  SELECT IF(
    EXISTS(
      SELECT 1
      FROM INFORMATION_SCHEMA.COLUMNS
      WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'users' AND COLUMN_NAME = 'top_sections_mode'
    ),
    'SELECT ''users.top_sections_mode already exists'' AS info',
    "ALTER TABLE users ADD COLUMN top_sections_mode ENUM('collapsible','classic') NOT NULL DEFAULT 'collapsible' AFTER display_name"
  )
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
