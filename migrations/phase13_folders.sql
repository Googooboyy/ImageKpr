-- Phase 13: per-user folders and image membership (replaces localStorage)

CREATE TABLE IF NOT EXISTS folders (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  name VARCHAR(255) NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_user_folder_name (user_id, name),
  INDEX idx_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS folder_images (
  folder_id INT NOT NULL,
  image_id INT NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (folder_id, image_id),
  INDEX idx_image (image_id),
  CONSTRAINT fk_folder_images_folder FOREIGN KEY (folder_id) REFERENCES folders (id) ON DELETE CASCADE,
  CONSTRAINT fk_folder_images_image FOREIGN KEY (image_id) REFERENCES images (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
