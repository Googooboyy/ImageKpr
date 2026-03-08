<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
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
$ids = isset($input['ids']) ? $input['ids'] : [];
if (!is_array($ids)) $ids = [];
$ids = array_map('intval', array_filter($ids));

if (empty($ids)) {
  echo json_encode(['success' => false, 'error' => 'No ids provided']);
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

$dir = rtrim(IMAGES_DIR, '/\\') . DIRECTORY_SEPARATOR;
$deleted = 0;
$placeholders = implode(',', array_fill(0, count($ids), '?'));
$stmt = $pdo->prepare("SELECT id, filename FROM images WHERE id IN ($placeholders)");
$stmt->execute($ids);
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
  $path = $dir . $row['filename'];
  if (file_exists($path)) @unlink($path);
  $del = $pdo->prepare('DELETE FROM images WHERE id = ?');
  $del->execute([$row['id']]);
  $deleted++;
}

echo json_encode(['success' => true, 'deleted' => $deleted]);
