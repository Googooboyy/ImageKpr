-- Phase 15: per-user upload size tiers and downgrade grace tracking

ALTER TABLE users
  ADD COLUMN upload_size_mb TINYINT UNSIGNED NOT NULL DEFAULT 3 AFTER storage_quota_bytes,
  ADD COLUMN upload_tier_downgraded_at DATETIME NULL DEFAULT NULL AFTER upload_size_mb;

UPDATE users
SET upload_size_mb = 3
WHERE upload_size_mb NOT IN (3, 10, 30);
