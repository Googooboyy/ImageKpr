<?php

function imagekpr_user_images_dir(int $userId): string
{
  $safeUserId = max(0, $userId);
  return rtrim(IMAGES_DIR, '/\\') . DIRECTORY_SEPARATOR . $safeUserId;
}

function imagekpr_user_images_url(int $userId): string
{
  $safeUserId = max(0, $userId);
  return rtrim(IMAGES_URL, '/') . '/' . $safeUserId;
}

function imagekpr_ensure_user_images_dir(int $userId): string
{
  $dir = imagekpr_user_images_dir($userId);
  if (!is_dir($dir)) {
    @mkdir($dir, 0755, true);
  }
  return $dir;
}

function imagekpr_user_image_path(int $userId, string $filename): string
{
  return imagekpr_user_images_dir($userId) . DIRECTORY_SEPARATOR . basename($filename);
}

function imagekpr_resolve_user_image_path(int $userId, string $filename): string
{
  $name = basename($filename);
  $scoped = imagekpr_user_image_path($userId, $name);
  if (is_file($scoped)) {
    return $scoped;
  }
  // Temporary backward-compatibility path for pre-migration files.
  return rtrim(IMAGES_DIR, '/\\') . DIRECTORY_SEPARATOR . $name;
}
