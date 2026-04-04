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

const MAX_SIZE = 3 * 1024 * 1024;
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
    $n = count($_FILES['file']['name']);
    if ($n > MAX_FILES_PER_UPLOAD_POST) {
      http_response_code(400);
      echo json_encode(['success' => false, 'error' => 'Too many files (max ' . MAX_FILES_PER_UPLOAD_POST . ')']);
      exit;
    }
    for ($i = 0; $i < $n; $i++) {
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

$replaceNames = [];
if (!empty($_POST['replace']) && is_array($_POST['replace'])) {
  $replaceNames = array_map('trim', $_POST['replace']);
  $replaceNames = array_filter($replaceNames);
}

try {
  $pdo = imagekpr_pdo();
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
  $doReplace = in_array($baseName, $replaceNames);
  if ($doReplace && file_exists($path)) {
    @unlink($path);
    $pdo->prepare('DELETE FROM images WHERE filename = ? AND user_id = ?')->execute([$baseName, $uid]);
  }
  if (!$doReplace) {
    $suffix = 0;
    while (file_exists($path)) {
      $suffix++;
      $baseName = $stem . '-' . $suffix . '.' . $ext;
      $path = $dir . $baseName;
    }
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
  $stmt = $pdo->prepare('INSERT INTO images (filename, url, date_uploaded, size_bytes, width, height, tags, user_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
  $stmt->execute([$baseName, $url, $date, $size, $width, $height, $tagsJson, $uid]);
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