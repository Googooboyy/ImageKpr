-- Run after phase7_auth.sql on an existing DB that had UNIQUE(filename) on images alone.
-- Per-user filenames: two users may both have "photo.jpg".

ALTER TABLE images DROP INDEX filename;
ALTER TABLE images ADD UNIQUE KEY uq_user_filename (user_id, filename);