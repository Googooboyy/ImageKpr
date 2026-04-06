<?php
require_once __DIR__ . '/../inc/auth.php';
require_once __DIR__ . '/../inc/maintenance.php';
imagekpr_require_api_user();
imagekpr_block_if_maintenance_json();
$uid = imagekpr_user_id();
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  echo json_encode(['success' => false, 'error' => 'Method not allowed']);
  exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
$ids = isset($input['ids']) ? $input['ids'] : [];
if (!is_array($ids)) $ids = [];
$ids = array_map('intval', array_filter($ids));

if (empty($ids)) {
  echo json_encode(['success' => false, 'error' => 'No ids provided']);
  exit;
}
if (imagekpr_bulk_ids_too_many($ids)) {
  $cap = imagekpr_max_bulk_image_ids();
  imagekpr_json_request_limit_exceeded($cap, 'Too many ids (max ' . $cap . ')', true);
}
$ids = imagekpr_cap_bulk_ids($ids);

try {
  $pdo = imagekpr_pdo();
} catch (PDOException $e) {
  echo json_encode(['success' => false, 'error' => 'Database connection failed']);
  exit;
}

$dir = rtrim(IMAGES_DIR, '/\\') . DIRECTORY_SEPARATOR;
$deleted = 0;
$placeholders = implode(',', array_fill(0, count($ids), '?'));
$params = array_merge($ids, [$uid]);
$sql = "SELECT id, filename FROM images WHERE id IN ($placeholders) AND user_id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
  $path = $dir . $row['filename'];
  if (file_exists($path)) @unlink($path);
  $del = $pdo->prepare('DELETE FROM images WHERE id = ? AND user_id = ?');
  $del->execute([$row['id'], $uid]);
  $deleted++;
}

echo json_encode(['success' => true, 'deleted' => $deleted]);