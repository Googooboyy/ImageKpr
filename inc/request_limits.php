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
  $max = function_exists('imagekpr_max_bulk_image_ids') ? imagekpr_max_bulk_image_ids() : MAX_BULK_IMAGE_IDS;
  if (count($ids) > $max) {
    return [];
  }
  return $ids;
}
function imagekpr_bulk_ids_too_many(array $ids): bool
{
  $max = function_exists('imagekpr_max_bulk_image_ids') ? imagekpr_max_bulk_image_ids() : MAX_BULK_IMAGE_IDS;
  return count(array_unique(array_filter(array_map('intval', $ids)))) > $max;
}
