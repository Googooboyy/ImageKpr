-- Phase 22: per-user light/dark theme preference for the signed-in app UI.

SET @db := DATABASE();

SET @sql := (
  SELECT IF(
    EXISTS(
      SELECT 1
      FROM INFORMATION_SCHEMA.COLUMNS
      WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'users' AND COLUMN_NAME = 'theme_preference'
    ),
    'SELECT ''users.theme_preference already exists'' AS info',
    "ALTER TABLE users ADD COLUMN theme_preference ENUM('light','dark') NULL DEFAULT NULL AFTER top_sections_mode"
  )
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
