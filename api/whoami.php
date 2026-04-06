<?php
/**
 * Signed-in user (JSON). Use when the grid is empty but the DB has rows:
 * compare user_id here to images.user_id in phpMyAdmin.
 */
require_once __DIR__ . '/../inc/auth.php';
require_once __DIR__ . '/../inc/admin.php';
imagekpr_require_api_user();
header('Content-Type: application/json; charset=utf-8');
imagekpr_ensure_config();
$maint = imagekpr_maintenance_enabled();
$uploadTierMb = 3;
$uploadTierDowngradedAt = null;
try {
  $pdo = imagekpr_pdo();
  $tier = imagekpr_user_upload_tier($pdo, imagekpr_user_id());
  $uploadTierMb = (int) ($tier['upload_size_mb'] ?? 3);
  $uploadTierDowngradedAt = $tier['upload_tier_downgraded_at'] ?? null;
} catch (Throwable $e) {
  // Keep safe defaults if DB read fails.
}
echo json_encode([
  'user_id' => imagekpr_user_id(),
  'email' => isset($_SESSION['email']) ? (string) $_SESSION['email'] : null,
  'name' => isset($_SESSION['name']) ? (string) $_SESSION['name'] : null,
  'maintenance' => $maint,
  'maintenance_message' => $maint ? imagekpr_maintenance_banner_text() : '',
  'upload_size_mb' => $uploadTierMb,
  'upload_max_bytes' => imagekpr_upload_limit_bytes_from_mb($uploadTierMb),
  'upload_tier_downgraded_at' => $uploadTierDowngradedAt,
  'upload_grace_days' => imagekpr_upload_tier_grace_days(),
  'upload_grace_expired' => imagekpr_upload_tier_grace_expired($uploadTierDowngradedAt),
], JSON_UNESCAPED_UNICODE);
