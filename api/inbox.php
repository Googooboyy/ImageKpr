<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

if (!file_exists(__DIR__ . '/../config.php')) {
  http_response_code(500);
  echo json_encode(['error' => 'Configuration missing.']);
  exit;
}
require_once __DIR__ . '/../config.php';

$exts = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
$inboxDir = rtrim(INBOX_DIR, '/\\') . DIRECTORY_SEPARATOR;

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
  $pending = [];
  if (!is_dir($inboxDir)) {
    echo json_encode(['pending' => [], 'count' => 0]);
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
    echo json_encode(['pending' => [], 'count' => 0]);
    exit;
  }
  $stmt = $pdo->query('SELECT filename FROM images');
  $inDb = [];
  while ($r = $stmt->fetch(PDO::FETCH_COLUMN)) $inDb[$r] = true;
  foreach (scandir($inboxDir) as $f) {
    if ($f === '.' || $f === '..') continue;
    $path = $inboxDir . $f;
    if (!is_file($path)) continue;
    $ext = strtolower(pathinfo($f, PATHINFO_EXTENSION));
    if (!in_array($ext, $exts)) continue;
    if (isset($inDb[$f])) continue;
    $pending[] = ['filename' => $f, 'size' => filesize($path)];
  }
  echo json_encode(['pending' => $pending, 'count' => count($pending)]);
  exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
  $importAll = empty($input['files']) || $input['import_all'] ?? false;
  $files = isset($input['files']) ? $input['files'] : [];

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

  $imagesDir = rtrim(IMAGES_DIR, '/\\') . DIRECTORY_SEPARATOR;
  $maxSize = 3 * 1024 * 1024;
  $allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

  if (!is_dir($inboxDir)) {
    echo json_encode(['success' => true, 'imported' => 0]);
    exit;
  }

  $stmt = $pdo->query('SELECT filename FROM images');
  $inDb = [];
  while ($r = $stmt->fetch(PDO::FETCH_COLUMN)) $inDb[$r] = true;

  $toImport = [];
  if ($importAll) {
    foreach (scandir($inboxDir) as $f) {
      if ($f === '.' || $f === '..') continue;
      $ext = strtolower(pathinfo($f, PATHINFO_EXTENSION));
      if (in_array($ext, $exts) && !isset($inDb[$f])) $toImport[] = $f;
    }
  } else {
    foreach ($files as $f) {
      if (is_string($f) && !isset($inDb[$f])) $toImport[] = $f;
    }
  }

  $imported = 0;
  foreach ($toImport as $f) {
    $path = $inboxDir . $f;
    if (!is_file($path)) continue;
    $mime = finfo_file(finfo_open(FILEINFO_MIME_TYPE), $path);
    if (!in_array($mime, $allowedMimes)) continue;
    if (filesize($path) > $maxSize) continue;
    $baseName = preg_replace('/[^a-zA-Z0-9._-]/', '_', basename($f));
    $dest = $imagesDir . $baseName;
    $suffix = 0;
    while (file_exists($dest)) {
      $suffix++;
      $dest = $imagesDir . pathinfo($baseName, PATHINFO_FILENAME) . '-' . $suffix . '.' . pathinfo($baseName, PATHINFO_EXTENSION);
    }
    $baseName = basename($dest);
    if (!rename($path, $dest)) continue;
    $info = @getimagesize($dest);
    $url = rtrim(IMAGES_URL, '/') . '/' . $baseName;
    $stmt = $pdo->prepare('INSERT INTO images (filename, url, date_uploaded, size_bytes, width, height, tags) VALUES (?, ?, NOW(), ?, ?, ?, ?)');
    $stmt->execute([$baseName, $url, filesize($dest), $info[0] ?? null, $info[1] ?? null, '[]']);
    $imported++;
  }
  echo json_encode(['success' => true, 'imported' => $imported]);
}
