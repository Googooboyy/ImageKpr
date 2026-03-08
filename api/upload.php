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

const MAX_SIZE = 3 * 1024 * 1024; // 3MB
const ALLOWED_MIMES = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

function sanitizeFilename($name) {
  $name = basename($name);
  $name = preg_replace('/[^a-zA-Z0-9._-]/', '_', $name);
  $name = trim($name, '._');
  return $name ?: 'image';
}

$files = [];
if (isset($_FILES['file'])) {
  if (is_array($_FILES['file']['name'])) {
    for ($i = 0; $i < count($_FILES['file']['name']); $i++) {
      $files[] = [
        'name' => $_FILES['file']['name'][$i],
        'type' => $_FILES['file']['type'][$i],
        'tmp_name' => $_FILES['file']['tmp_name'][$i],
        'error' => $_FILES['file']['error'][$i],
        'size' => $_FILES['file']['size'][$i],
      ];
    }
  } else {
    $files[] = $_FILES['file'];
  }
}

if (empty($files)) {
  echo json_encode(['success' => false, 'error' => 'No file uploaded']);
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

$uploaded = [];
foreach ($files as $file) {
  if ($file['error'] !== UPLOAD_ERR_OK) {
    $uploaded[] = ['success' => false, 'error' => 'Upload error ' . $file['error']];
    continue;
  }
  if ($file['size'] > MAX_SIZE) {
    $uploaded[] = ['success' => false, 'error' => 'File too large (max 3MB)'];
    continue;
  }
  $finfo = finfo_open(FILEINFO_MIME_TYPE);
  $mime = finfo_file($finfo, $file['tmp_name']);
  finfo_close($finfo);
  if (!in_array($mime, ALLOWED_MIMES)) {
    $uploaded[] = ['success' => false, 'error' => 'Invalid image type'];
    continue;
  }
  $baseName = sanitizeFilename($file['name']);
  $ext = pathinfo($baseName, PATHINFO_EXTENSION);
  $ext = $ext ? preg_replace('/[^a-z0-9]/', '', strtolower($ext)) : 'jpg';
  $stem = pathinfo($baseName, PATHINFO_FILENAME) ?: 'image';
  $baseName = $stem . '.' . $ext;
  $dir = rtrim(IMAGES_DIR, '/\\') . DIRECTORY_SEPARATOR;
  $path = $dir . $baseName;
  $suffix = 0;
  while (file_exists($path)) {
    $suffix++;
    $baseName = $stem . '-' . $suffix . '.' . $ext;
    $path = $dir . $baseName;
  }
  if (!move_uploaded_file($file['tmp_name'], $path)) {
    $uploaded[] = ['success' => false, 'error' => 'Failed to save file'];
    continue;
  }
  $size = filesize($path);
  $info = @getimagesize($path);
  $width = $info[0] ?? null;
  $height = $info[1] ?? null;
  $url = rtrim(IMAGES_URL, '/') . '/' . $baseName;
  $date = date('Y-m-d H:i:s');
  $tagsJson = json_encode([]);
  $stmt = $pdo->prepare('INSERT INTO images (filename, url, date_uploaded, size_bytes, width, height, tags) VALUES (?, ?, ?, ?, ?, ?, ?)');
  $stmt->execute([$baseName, $url, $date, $size, $width, $height, $tagsJson]);
  $id = (int) $pdo->lastInsertId();
  $uploaded[] = [
    'success' => true,
    'image' => [
      'id' => $id,
      'filename' => $baseName,
      'url' => $url,
      'date_uploaded' => $date,
      'size_bytes' => $size,
      'width' => $width,
      'height' => $height,
      'tags' => [],
    ]
  ];
}

$allOk = count(array_filter($uploaded, fn($u) => $u['success'] ?? false)) === count($uploaded);
if (count($uploaded) === 1) {
  echo json_encode($uploaded[0]);
} else {
  echo json_encode(['success' => $allOk, 'results' => $uploaded]);
}
