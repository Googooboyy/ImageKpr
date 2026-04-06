<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/admin.php';
require_once __DIR__ . '/settings.php';

/**
 * Block state-changing API requests during maintenance for non-admin users.
 * Call after imagekpr_require_api_user().
 * Allows GET/HEAD/OPTIONS; allows read-like POST on download_bulk and check_duplicates.
 */
function imagekpr_block_if_maintenance_json(): void
{
  $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
  if (in_array($method, ['GET', 'HEAD', 'OPTIONS'], true)) {
    return;
  }
  $script = basename($_SERVER['SCRIPT_FILENAME'] ?? '');
  if ($script === 'download_bulk.php' || $script === 'check_duplicates.php') {
    return;
  }
  if (!imagekpr_maintenance_enabled()) {
    return;
  }
  try {
    $pdo = imagekpr_pdo();
    $uid = imagekpr_user_id();
    if ($uid >= 1 && imagekpr_user_is_admin($pdo, $uid)) {
      return;
    }
  } catch (Throwable $e) {
    return;
  }
  http_response_code(503);
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode([
    'error' => 'Maintenance',
    'message' => imagekpr_maintenance_banner_text(),
    'maintenance' => true,
  ], JSON_UNESCAPED_UNICODE);
  exit;
}
