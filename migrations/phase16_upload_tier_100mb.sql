-- Phase 16: replace 30MB upload tier with 100MB (existing rows)

UPDATE users
SET upload_size_mb = 100
WHERE upload_size_mb = 30;
