-- Phase 17: updates/blog posts for public updates.php + admin/updates.php

CREATE TABLE IF NOT EXISTS updates_posts (
  id INT AUTO_INCREMENT PRIMARY KEY,
  slug VARCHAR(191) NOT NULL,
  title VARCHAR(255) NOT NULL,
  published_at DATE NOT NULL,
  summary TEXT NOT NULL,
  body MEDIUMTEXT NOT NULL,
  tags_json JSON NULL,
  status ENUM('draft','published') NOT NULL DEFAULT 'published',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_updates_slug (slug),
  INDEX idx_updates_status_date (status, published_at, id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
