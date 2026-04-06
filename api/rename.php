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
$id = isset($input['id']) ? (int) $input['id'] : 0;
$newFilename = isset($input['filename']) ? trim($input['filename']) : '';

if ($id <= 0 || $newFilename === '') {
  echo json_encode(['success' => false, 'error' => 'Invalid id or filename']);
  exit;
}

function sanitize($s) {
  $s = preg_replace('/[^a-zA-Z0-9._-]/', '_', $s);
  return trim($s, '._') ?: 'image';
}

try {
  $pdo = imagekpr_pdo();
} catch (PDOException $e) {
  echo json_encode(['success' => false, 'error' => 'Database connection failed']);
  exit;
}

$dir = rtrim(IMAGES_DIR, '/\\') . DIRECTORY_SEPARATOR;
$baseUrl = rtrim(IMAGES_URL, '/') . '/';

$stmt = $pdo->prepare('SELECT id, filename, url FROM images WHERE id = ? AND user_id = ?');
$stmt->execute([$id, $uid]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$row) {
  echo json_encode(['success' => false, 'error' => 'Image not found']);
  exit;
}

$oldFn = $row['filename'];
$ext = pathinfo($oldFn, PATHINFO_EXTENSION) ?: 'jpg';
$stem = pathinfo($newFilename, PATHINFO_FILENAME);
$newExt = pathinfo($newFilename, PATHINFO_EXTENSION);
if (!$newExt) $newExt = $ext;
$newFn = sanitize($stem ?: pathinfo($oldFn, PATHINFO_FILENAME)) . '.' . $newExt;

$oldPath = $dir . $oldFn;
$newPath = $dir . $newFn;

if (!file_exists($oldPath)) {
  echo json_encode(['success' => false, 'error' => 'File not found on disk']);
  exit;
}

if (file_exists($newPath) && $newPath !== $oldPath) {
  echo json_encode(['success' => false, 'error' => 'A file with that name already exists']);
  exit;
}

if (@rename($oldPath, $newPath)) {
  $newUrl = $baseUrl . $newFn;
  $up = $pdo->prepare('UPDATE images SET filename = ?, url = ? WHERE id = ? AND user_id = ?');
  $up->execute([$newFn, $newUrl, $id, $uid]);
  echo json_encode([
    'success' => true,
    'id' => $id,
    'filename' => $newFn,
    'url' => $newUrl,
    'old_filename' => $oldFn
  ]);
} else {
  echo json_encode(['success' => false, 'error' => 'Rename failed']);
}