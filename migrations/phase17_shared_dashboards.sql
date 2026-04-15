-- Phase 17: shared dashboards and dashboard images mapping

CREATE TABLE IF NOT EXISTS shared_dashboards (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  token VARCHAR(32) NOT NULL UNIQUE,
  title VARCHAR(255) DEFAULT NULL,
  subtitle VARCHAR(500) DEFAULT NULL,
  hero_image_id INT DEFAULT NULL,
  allow_slideshow TINYINT(1) NOT NULL DEFAULT 1,
  allow_download TINYINT(1) NOT NULL DEFAULT 1,
  password_hash VARCHAR(255) DEFAULT NULL,
  expires_at DATETIME DEFAULT NULL,
  view_count INT NOT NULL DEFAULT 0,
  last_viewed_at DATETIME DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_shared_dashboards_user (user_id),
  KEY idx_shared_dashboards_token (token),
  KEY idx_shared_dashboards_expires (expires_at),
  CONSTRAINT fk_shared_dashboards_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_shared_dashboards_hero FOREIGN KEY (hero_image_id) REFERENCES images(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS shared_dashboard_images (
  id INT AUTO_INCREMENT PRIMARY KEY,
  dashboard_id INT NOT NULL,
  image_id INT NOT NULL,
  sort_order INT NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_dashboard_image (dashboard_id, image_id),
  KEY idx_dashboard_sort (dashboard_id, sort_order, id),
  CONSTRAINT fk_dashboard_images_dashboard FOREIGN KEY (dashboard_id) REFERENCES shared_dashboards(id) ON DELETE CASCADE,
  CONSTRAINT fk_dashboard_images_image FOREIGN KEY (image_id) REFERENCES images(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
