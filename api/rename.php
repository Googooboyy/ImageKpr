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
$baseUrl = rtrim(IMAGES_URL, '/') . '/';

$stmt = $pdo->prepare('SELECT id, filename, url FROM images WHERE id = ?');
$stmt->execute([$id]);
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
  $up = $pdo->prepare('UPDATE images SET filename = ?, url = ? WHERE id = ?');
  $up->execute([$newFn, $newUrl, $id]);
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
