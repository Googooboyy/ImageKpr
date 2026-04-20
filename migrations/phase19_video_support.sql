-- Add media type support (image/video) to the images table.
ALTER TABLE images
  ADD COLUMN media_type ENUM('image', 'video') NOT NULL DEFAULT 'image' AFTER tags;
