<?php
require_once __DIR__ . '/../inc/auth.php';
imagekpr_require_api_user();
$uid = imagekpr_user_id();
header('Content-Type: application/json; charset=utf-8');

if (!in_array($_SERVER['REQUEST_METHOD'], ['POST', 'DELETE'])) {
  http_response_code(405);
  echo json_encode(['success' => false, 'error' => 'Method not allowed']);
  exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
$id = isset($input['id']) ? (int) $input['id'] : null;
$filename = isset($input['filename']) ? trim((string) $input['filename']) : null;
if ($filename !== null && $filename !== '') {
  $filename = basename($filename);
}

if (!$id && !$filename) {
  echo json_encode(['success' => false, 'error' => 'Missing id or filename']);
  exit;
}

try {
  $pdo = imagekpr_pdo();
} catch (PDOException $e) {
  echo json_encode(['success' => false, 'error' => 'Database connection failed']);
  exit;
}

if ($id) {
  $stmt = $pdo->prepare('SELECT filename FROM images WHERE id = ? AND user_id = ?');
  $stmt->execute([$id, $uid]);
  $row = $stmt->fetch(PDO::FETCH_ASSOC);
  $filename = $row['filename'] ?? null;
} else {
  $stmt = $pdo->prepare('SELECT id FROM images WHERE filename = ? AND user_id = ?');
  $stmt->execute([$filename, $uid]);
  $row = $stmt->fetch(PDO::FETCH_ASSOC);
  $id = $row['id'] ?? null;
}

if (!$filename || !$id) {
  echo json_encode(['success' => false, 'error' => 'Image not found']);
  exit;
}

$path = rtrim(IMAGES_DIR, '/\\') . DIRECTORY_SEPARATOR . $filename;
if (file_exists($path) && !@unlink($path)) {
  echo json_encode(['success' => false, 'error' => 'Failed to delete file']);
  exit;
}

$stmt = $pdo->prepare('DELETE FROM images WHERE id = ? AND user_id = ?');
$stmt->execute([$id, $uid]);

echo json_encode(['success' => true]);