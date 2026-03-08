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
  echo json_encode(['success' => false, 'error' => 'Configuration missing. Copy config.example.php to config.php.']);
  exit;
}
require_once __DIR__ . '/../config.php';

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

$duplicates = [];
foreach ($names as $original) {
  $baseName = sanitizeFilename($original);
  $ext = pathinfo($baseName, PATHINFO_EXTENSION);
  $ext = $ext ? preg_replace('/[^a-z0-9]/', '', strtolower($ext)) : 'jpg';
  $stem = pathinfo($baseName, PATHINFO_FILENAME) ?: 'image';
  $sanitized = $stem . '.' . $ext;

  $stmt = $pdo->prepare('SELECT 1 FROM images WHERE filename = ? LIMIT 1');
  $stmt->execute([$sanitized]);
  if ($stmt->fetch()) {
    $duplicates[] = ['original' => $original, 'sanitized' => $sanitized];
  }
}

echo json_encode(['success' => true, 'duplicates' => $duplicates]);
