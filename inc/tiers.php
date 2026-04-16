<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/admin.php';

function imagekpr_user_is_paid(PDO $pdo, int $userId): bool
{
  $st = $pdo->prepare('SELECT upload_size_mb FROM users WHERE id = ? LIMIT 1');
  $st->execute([$userId]);
  $row = $st->fetch(PDO::FETCH_ASSOC);
  if (!$row) {
    return false;
  }
  return (int) ($row['upload_size_mb'] ?? 0) >= 10;
}

function imagekpr_dashboard_free_limit(): int
{
  return 20;
}

function imagekpr_dashboard_limit_for_tier(int $uploadSizeMb): int
{
  if ($uploadSizeMb >= 500) {
    return 2000;
  }
  if ($uploadSizeMb >= 50) {
    return 200;
  }
  if ($uploadSizeMb >= 10) {
    return 40;
  }
  return 20;
}

function imagekpr_dashboard_image_limit(PDO $pdo, int $userId): int
{
  $st = $pdo->prepare('SELECT upload_size_mb, upload_tier_downgraded_at FROM users WHERE id = ? LIMIT 1');
  $st->execute([$userId]);
  $row = $st->fetch(PDO::FETCH_ASSOC);
  if (!$row) return imagekpr_dashboard_free_limit();
  $mb = (int) ($row['upload_size_mb'] ?? 3);
  $downgradedAt = $row['upload_tier_downgraded_at'] ?? null;
  if ($mb >= 10 && imagekpr_upload_tier_grace_expired($downgradedAt)) {
    return imagekpr_dashboard_free_limit();
  }
  return imagekpr_dashboard_limit_for_tier($mb);
}
