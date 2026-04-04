<?php
require_once __DIR__ . '/../inc/auth.php';
imagekpr_require_api_user();
$uid = imagekpr_user_id();

if ($_SERVER['REQUEST_METHOD'] !== 'GET' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  exit;
}

$ids = [];
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
  $ids = isset($_GET['ids']) ? explode(',', $_GET['ids']) : [];
} else {
  $input = json_decode(file_get_contents('php://input'), true) ?: [];
  $ids = isset($input['ids']) ? $input['ids'] : [];
}
$ids = array_map('intval', array_filter(array_map('trim', $ids)));

if (empty($ids)) {
  http_response_code(400);
  exit;
}
if (imagekpr_bulk_ids_too_many($ids)) {
  http_response_code(400);
  exit;
}
$ids = imagekpr_cap_bulk_ids($ids);

try {
  $pdo = imagekpr_pdo();
} catch (PDOException $e) {
  http_response_code(500);
  exit;
}

$placeholders = implode(',', array_fill(0, count($ids), '?'));
$params = array_merge($ids, [$uid]);
$stmt = $pdo->prepare("SELECT id, filename FROM images WHERE id IN ($placeholders) AND user_id = ?");
$stmt->execute($params);

$dir = rtrim(IMAGES_DIR, '/\\') . DIRECTORY_SEPARATOR;
$zip = new ZipArchive();
$tmp = tempnam(sys_get_temp_dir(), 'img');
$zip->open($tmp, ZipArchive::CREATE | ZipArchive::OVERWRITE);

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
  $path = $dir . $row['filename'];
  if (file_exists($path)) {
    $zip->addFile($path, $row['filename']);
  }
}
$zip->close();

$name = 'imagekpr-export-' . date('Ymd') . '.zip';
header('X-Content-Type-Options: nosniff');
header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename="' . $name . '"');
header('Content-Length: ' . filesize($tmp));
readfile($tmp);
@unlink($tmp);