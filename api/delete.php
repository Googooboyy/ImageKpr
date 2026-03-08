<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

if (!in_array($_SERVER['REQUEST_METHOD'], ['POST', 'DELETE'])) {
  http_response_code(405);
  echo json_encode(['success' => false, 'error' => 'Method not allowed']);
  exit;
}

if (!file_exists(__DIR__ . '/../config.php')) {
  http_response_code(500);
  echo json_encode(['success' => false, 'error' => 'Configuration missing.']);
  exit;
}
require_once __DIR__ . '/../config.php';

$input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
$id = isset($input['id']) ? (int) $input['id'] : null;
$filename = isset($input['filename']) ? trim($input['filename']) : null;

if (!$id && !$filename) {
  echo json_encode(['success' => false, 'error' => 'Missing id or filename']);
  exit;
}

try {
  $pdo = new PDO(
    'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
    DB_USER,
    DB_PASS,
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
  );
} catch (PDOException $e) {
  echo json_encode(['success' => false, 'error' => 'Database connection failed']);
  exit;
}

if ($id) {
  $stmt = $pdo->prepare('SELECT filename FROM images WHERE id = ?');
  $stmt->execute([$id]);
  $row = $stmt->fetch(PDO::FETCH_ASSOC);
  $filename = $row['filename'] ?? null;
} else {
  $stmt = $pdo->prepare('SELECT id FROM images WHERE filename = ?');
  $stmt->execute([$filename]);
  $row = $stmt->fetch(PDO::FETCH_ASSOC);
  $id = $row['id'] ?? null;
}

if (!$filename) {
  echo json_encode(['success' => false, 'error' => 'Image not found']);
  exit;
}

$path = rtrim(IMAGES_DIR, '/\\') . DIRECTORY_SEPARATOR . $filename;
if (file_exists($path) && !@unlink($path)) {
  echo json_encode(['success' => false, 'error' => 'Failed to delete file']);
  exit;
}

$stmt = $pdo->prepare('DELETE FROM images WHERE id = ?');
$stmt->execute([$id]);

echo json_encode(['success' => true]);
