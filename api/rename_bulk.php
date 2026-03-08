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
$base = isset($input['base']) ? trim($input['base']) : '';
$pattern = isset($input['pattern']) ? $input['pattern'] : null;

if (!is_array($ids)) $ids = [];
$ids = array_map('intval', array_filter($ids));

if (empty($ids)) {
  echo json_encode(['success' => false, 'error' => 'No ids provided']);
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
$results = [];
$placeholders = implode(',', array_fill(0, count($ids), '?'));
$stmt = $pdo->prepare("SELECT id, filename, url FROM images WHERE id IN ($placeholders)");
$stmt->execute($ids);
$byId = [];
while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) $byId[$r['id']] = $r;
$rows = [];
foreach ($ids as $i) {
  if (isset($byId[$i])) $rows[] = $byId[$i];
}
$n = 0;
foreach ($rows as $row) {
  $oldFn = $row['filename'];
  $ext = pathinfo($oldFn, PATHINFO_EXTENSION) ?: 'jpg';
  if ($pattern === 'search_replace' && isset($input['find']) && isset($input['replace'])) {
    $stem = pathinfo($oldFn, PATHINFO_FILENAME);
    $stem = str_replace($input['find'], $input['replace'], $stem);
    $newFn = sanitize($stem) . '.' . $ext;
  } else {
    $n++;
    $stem = sanitize($base) ?: 'image';
    $newFn = $stem . '-' . str_pad((string)$n, 2, '0', STR_PAD_LEFT) . '.' . $ext;
  }
  $newPath = $dir . $newFn;
  $suffix = 0;
  while (file_exists($newPath) && $newPath !== $dir . $oldFn) {
    $suffix++;
    $newFn = pathinfo($newFn, PATHINFO_FILENAME) . '-' . $suffix . '.' . $ext;
    $newPath = $dir . $newFn;
  }
  $oldPath = $dir . $oldFn;
  if (file_exists($oldPath) && @rename($oldPath, $newPath)) {
    $newUrl = $baseUrl . $newFn;
    $up = $pdo->prepare('UPDATE images SET filename = ?, url = ? WHERE id = ?');
    $up->execute([$newFn, $newUrl, $row['id']]);
    $results[] = ['id' => $row['id'], 'old_filename' => $oldFn, 'new_filename' => $newFn];
  }
}

echo json_encode(['success' => true, 'renamed' => count($results), 'results' => $results]);
