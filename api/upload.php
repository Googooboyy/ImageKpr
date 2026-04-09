<?php
require_once __DIR__ . '/../inc/auth.php';
require_once __DIR__ . '/../inc/admin.php';
require_once __DIR__ . '/../inc/maintenance.php';
imagekpr_require_api_user();
imagekpr_block_if_maintenance_json();
$uid = imagekpr_user_id();
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  echo json_encode(['success' => false, 'error' => 'Method not allowed']);
  exit;
}

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
    if ($n > imagekpr_max_files_per_upload_post()) {
      $cap = imagekpr_max_files_per_upload_post();
      imagekpr_json_request_limit_exceeded($cap, 'Too many files (max ' . $cap . ')', true);
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
$maxUploadBytes = imagekpr_user_max_upload_bytes($pdo, $uid);
$maxUploadMb = (int) round($maxUploadBytes / (1024 * 1024));

$plannedDelta = 0;
$sizeStmt = $pdo->prepare('SELECT COALESCE(size_bytes, 0) FROM images WHERE filename = ? AND user_id = ? LIMIT 1');
foreach ($files as $file) {
  if ($file['error'] !== UPLOAD_ERR_OK) {
    continue;
  }
  if ($file['size'] > $maxUploadBytes) {
    continue;
  }
  $finfo = finfo_open(FILEINFO_MIME_TYPE);
  $mime = finfo_file($finfo, $file['tmp_name']);
  finfo_close($finfo);
  if (!in_array($mime, ALLOWED_MIMES)) {
    continue;
  }
  $baseName = sanitizeFilename($file['name']);
  $ext = pathinfo($baseName, PATHINFO_EXTENSION);
  $ext = $ext ? preg_replace('/[^a-z0-9]/', '', strtolower($ext)) : 'jpg';
  $stem = pathinfo($baseName, PATHINFO_FILENAME) ?: 'image';
  $baseName = $stem . '.' . $ext;
  $doReplace = in_array($baseName, $replaceNames);
  if ($doReplace) {
    $sizeStmt->execute([$baseName, $uid]);
    $oldSz = (int) $sizeStmt->fetchColumn();
    $plannedDelta += (int) $file['size'] - $oldSz;
  } else {
    $plannedDelta += (int) $file['size'];
  }
}
$quotaErr = imagekpr_storage_quota_denies_add($pdo, $uid, $plannedDelta);
if ($quotaErr !== null) {
  http_response_code(507);
  echo json_encode(['success' => false, 'error' => $quotaErr, 'quota' => true]);
  exit;
}

$uploaded = [];
foreach ($files as $file) {
  if ($file['error'] !== UPLOAD_ERR_OK) {
    $uploaded[] = ['success' => false, 'error' => 'Upload error ' . $file['error']];
    continue;
  }
  if ($file['size'] > $maxUploadBytes) {
    $uploaded[] = ['success' => false, 'error' => 'File too large (max ' . $maxUploadMb . 'MB)'];
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
  $dir = imagekpr_ensure_user_images_dir($uid) . DIRECTORY_SEPARATOR;
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
  $url = imagekpr_user_images_url($uid) . '/' . $baseName;
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