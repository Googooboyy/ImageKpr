<?php
require_once __DIR__ . '/../inc/auth.php';
require_once __DIR__ . '/../inc/admin.php';
require_once __DIR__ . '/../inc/maintenance.php';
imagekpr_require_api_user();
imagekpr_block_if_maintenance_json();
$uid = imagekpr_user_id();
header('Content-Type: application/json; charset=utf-8');

$exts = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
$inboxDir = rtrim(INBOX_DIR, '/\\') . DIRECTORY_SEPARATOR;
$inboxReal = is_dir($inboxDir) ? realpath($inboxDir) : false;

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
  $pending = [];
  if (!$inboxReal) {
    echo json_encode(['pending' => [], 'count' => 0]);
    exit;
  }
  try {
    $pdo = imagekpr_pdo();
  } catch (PDOException $e) {
    echo json_encode(['pending' => [], 'count' => 0]);
    exit;
  }
  $stmt = $pdo->prepare('SELECT filename FROM images WHERE user_id = ?');
  $stmt->execute([$uid]);
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
  $action = isset($input['action']) ? $input['action'] : 'import';

  if ($action === 'delete') {
    $toDelete = isset($input['files']) && is_array($input['files']) ? $input['files'] : [];
    $deleted = 0;
    foreach ($toDelete as $f) {
      if (!is_string($f)) continue;
      $base = basename($f);
      $path = $inboxDir . $base;
      if (is_file($path) && realpath(dirname($path)) === $inboxReal) {
        if (@unlink($path)) $deleted++;
      }
    }
    echo json_encode(['success' => true, 'deleted' => $deleted]);
    exit;
  }

  $importAll = empty($input['files']) && empty($input['items']) ? ($input['import_all'] ?? false) : false;
  $files = isset($input['files']) ? $input['files'] : [];
  $items = isset($input['items']) && is_array($input['items']) ? $input['items'] : [];

  try {
    $pdo = imagekpr_pdo();
  } catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit;
  }

  $imagesDir = imagekpr_ensure_user_images_dir($uid) . DIRECTORY_SEPARATOR;
  $maxSize = 3 * 1024 * 1024;
  $allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

  if (!$inboxReal) {
    echo json_encode(['success' => true, 'imported' => 0, 'imported_ids' => []]);
    exit;
  }

  $stmt = $pdo->prepare('SELECT filename FROM images WHERE user_id = ?');
  $stmt->execute([$uid]);
  $inDb = [];
  while ($r = $stmt->fetch(PDO::FETCH_COLUMN)) $inDb[$r] = true;

  $toImport = [];
  if (!empty($items)) {
    foreach ($items as $it) {
      $raw = isset($it['filename']) ? $it['filename'] : (is_string($it) ? $it : null);
      $f = is_string($raw) ? basename($raw) : null;
      if ($f && !isset($inDb[$f])) $toImport[] = $it;
    }
  } elseif ($importAll) {
    foreach (scandir($inboxDir) as $f) {
      if ($f === '.' || $f === '..') continue;
      $ext = strtolower(pathinfo($f, PATHINFO_EXTENSION));
      if (in_array($ext, $exts) && !isset($inDb[$f])) $toImport[] = ['filename' => $f];
    }
  } else {
    foreach ($files as $f) {
      if (!is_string($f)) continue;
      $fb = basename($f);
      if ($fb && !isset($inDb[$fb])) $toImport[] = ['filename' => $fb];
    }
  }

  $importBatchBytes = 0;
  foreach ($toImport as $it) {
    $raw = is_array($it) ? ($it['filename'] ?? '') : $it;
    if (!is_string($raw) || $raw === '') {
      continue;
    }
    $f = basename($raw);
    $path = $inboxDir . $f;
    if (!is_file($path) || realpath(dirname($path)) !== $inboxReal) {
      continue;
    }
    $fi = finfo_open(FILEINFO_MIME_TYPE);
    $mime = $fi ? finfo_file($fi, $path) : '';
    if ($fi) {
      finfo_close($fi);
    }
    if (!in_array($mime, $allowedMimes)) {
      continue;
    }
    $sz = filesize($path);
    if ($sz === false || $sz > $maxSize) {
      continue;
    }
    $importBatchBytes += (int) $sz;
  }
  $quotaErr = imagekpr_storage_quota_denies_add($pdo, $uid, $importBatchBytes);
  if ($quotaErr !== null) {
    http_response_code(507);
    echo json_encode(['success' => false, 'error' => $quotaErr, 'quota' => true, 'imported' => 0, 'imported_ids' => []]);
    exit;
  }

  $imported = 0;
  $importedIds = [];
  foreach ($toImport as $it) {
    $raw = is_array($it) ? ($it['filename'] ?? '') : $it;
    if (!is_string($raw) || $raw === '') continue;
    $f = basename($raw);
    $path = $inboxDir . $f;
    if (!is_file($path) || realpath(dirname($path)) !== $inboxReal) continue;
    $fi = finfo_open(FILEINFO_MIME_TYPE);
    $mime = $fi ? finfo_file($fi, $path) : '';
    if ($fi) finfo_close($fi);
    if (!in_array($mime, $allowedMimes)) continue;
    if (filesize($path) > $maxSize) continue;

    $customName = (is_array($it) && !empty($it['newName'])) ? trim($it['newName']) : null;
    $baseName = $customName ? preg_replace('/[^a-zA-Z0-9._-]/', '_', basename($customName)) : preg_replace('/[^a-zA-Z0-9._-]/', '_', $f);
    if ($baseName === '' || pathinfo($baseName, PATHINFO_EXTENSION) === '') {
      $baseName = pathinfo($baseName, PATHINFO_FILENAME) ?: 'image';
      $baseName .= '.' . strtolower(pathinfo($f, PATHINFO_EXTENSION));
    }
    $dest = $imagesDir . $baseName;
    $suffix = 0;
    while (file_exists($dest)) {
      $suffix++;
      $dest = $imagesDir . pathinfo($baseName, PATHINFO_FILENAME) . '-' . $suffix . '.' . pathinfo($baseName, PATHINFO_EXTENSION);
    }
    $baseName = basename($dest);

    if (!@copy($path, $dest)) continue;
    @unlink($path);

    $tags = (is_array($it) && !empty($it['tags']) && is_array($it['tags'])) ? $it['tags'] : [];
    $tags = array_values(array_unique(array_map('trim', array_filter($tags, 'is_string'))));
    $tagsJson = json_encode($tags);

    $info = @getimagesize($dest);
    $url = imagekpr_user_images_url($uid) . '/' . $baseName;
    $stmt = $pdo->prepare('INSERT INTO images (filename, url, date_uploaded, size_bytes, width, height, tags, user_id) VALUES (?, ?, NOW(), ?, ?, ?, ?, ?)');
    $stmt->execute([$baseName, $url, filesize($dest), $info[0] ?? null, $info[1] ?? null, $tagsJson, $uid]);
    $importedIds[] = (int) $pdo->lastInsertId();
    $imported++;
  }
  echo json_encode(['success' => true, 'imported' => $imported, 'imported_ids' => $importedIds]);
  exit;
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);