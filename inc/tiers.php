<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/admin.php';

function imagekpr_user_is_paid(PDO $pdo, int $userId): bool
{
  $tier = imagekpr_user_upload_tier($pdo, $userId);
  if ($tier === null) {
    return false;
  }
  return (int) ($tier['upload_size_mb'] ?? 0) >= 10;
}

function imagekpr_dashboard_free_limit(): int
{
  return 20;
}

function imagekpr_dashboard_limit_for_tier(int $uploadSizeMb): int
{
  $mb = imagekpr_normalize_upload_size_mb($uploadSizeMb);
  $cap = imagekpr_plan_dashboard_cap_for_upload_mb($mb);
  return $cap !== null ? (int) $cap : imagekpr_dashboard_free_limit();
}

function imagekpr_dashboard_image_limit(PDO $pdo, int $userId): int
{
  $tier = imagekpr_user_upload_tier($pdo, $userId);
  if ($tier === null) return imagekpr_dashboard_free_limit();
  $mb = (int) ($tier['upload_size_mb'] ?? 3);
  $downgradedAt = $tier['upload_tier_downgraded_at'] ?? null;
  if ($mb >= 10 && imagekpr_upload_tier_grace_expired($downgradedAt)) {
    return imagekpr_dashboard_free_limit();
  }
  return imagekpr_dashboard_limit_for_tier($mb);
}
