<?php
if (!defined('MAX_BULK_IMAGE_IDS')) {
  define('MAX_BULK_IMAGE_IDS', 300);
}
if (!defined('MAX_DUPLICATE_CHECK_FILENAMES')) {
  define('MAX_DUPLICATE_CHECK_FILENAMES', 200);
}
if (!defined('MAX_FILES_PER_UPLOAD_POST')) {
  define('MAX_FILES_PER_UPLOAD_POST', 20);
}
if (!defined('MAX_IMAGES_PER_PAGE')) {
  define('MAX_IMAGES_PER_PAGE', 500);
}
function imagekpr_cap_bulk_ids(array $ids): array
{
  $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
  if (count($ids) > MAX_BULK_IMAGE_IDS) {
    return [];
  }
  return $ids;
}
function imagekpr_bulk_ids_too_many(array $ids): bool
{
  return count(array_unique(array_filter(array_map('intval', $ids)))) > MAX_BULK_IMAGE_IDS;
}
