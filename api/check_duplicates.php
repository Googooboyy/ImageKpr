<?php
require_once __DIR__ . '/../inc/auth.php';
imagekpr_require_api_user();
$uid = imagekpr_user_id();
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  echo json_encode(['success' => false, 'error' => 'Method not allowed']);
  exit;
}

function sanitizeFilename($name) {
  $name = basename($name);
  $name = preg_replace('/[^a-zA-Z0-9._-]/', '_', $name);
  $name = trim($name, '._');
  return $name ?: 'image';
}

$input = json_decode(file_get_contents('php://input'), true);
$names = $input['filenames'] ?? [];
if (!is_array($names)) {
  echo json_encode(['success' => false, 'error' => 'Invalid input']);
  exit;
}
if (count($names) > MAX_DUPLICATE_CHECK_FILENAMES) {
  http_response_code(400);
  echo json_encode(['success' => false, 'error' => 'Too many filenames (max ' . MAX_DUPLICATE_CHECK_FILENAMES . ')']);
  exit;
}

try {
  $pdo = imagekpr_pdo();
} catch (PDOException $e) {
  echo json_encode(['success' => false, 'error' => 'Database connection failed']);
  exit;
}

$duplicates = [];
foreach ($names as $original) {
  $baseName = sanitizeFilename($original);
  $ext = pathinfo($baseName, PATHINFO_EXTENSION);
  $ext = $ext ? preg_replace('/[^a-z0-9]/', '', strtolower($ext)) : 'jpg';
  $stem = pathinfo($baseName, PATHINFO_FILENAME) ?: 'image';
  $sanitized = $stem . '.' . $ext;

  $stmt = $pdo->prepare('SELECT 1 FROM images WHERE filename = ? AND user_id = ? LIMIT 1');
  $stmt->execute([$sanitized, $uid]);
  if ($stmt->fetch()) {
    $duplicates[] = ['original' => $original, 'sanitized' => $sanitized];
  }
}

echo json_encode(['success' => true, 'duplicates' => $duplicates]);