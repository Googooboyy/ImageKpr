#!/usr/bin/env php
<?php
/**
 * Sync inbox/ to images/ and DB.
 * Run: php scripts/sync_images.php [user_id]
 * Or: php scripts/sync_images.php --user-id=5
 * Or: IMAGEKPR_SYNC_USER_ID=5 php scripts/sync_images.php
 * Omit user id to import as user_id NULL (legacy / unscoped rows).
 */
$root = dirname(__DIR__);
if (!file_exists($root . '/config.php')) {
  fwrite(STDERR, "config.php not found. Copy config.example.php to config.php.\n");
  exit(1);
}
require_once $root . '/config.php';
require_once $root . '/inc/images_path.php';

$syncUserId = null;
$envUid = getenv('IMAGEKPR_SYNC_USER_ID');
if ($envUid !== false && $envUid !== '') {
  $syncUserId = (int) $envUid;
}
foreach ($argv as $a) {
  if (preg_match('/^--user-id=(\d+)$/', $a, $m)) {
    $syncUserId = (int) $m[1];
  }
}
if ($argc >= 2 && ctype_digit((string) $argv[1])) {
  $syncUserId = (int) $argv[1];
}

$exts = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
$inboxDir = rtrim(INBOX_DIR, '/\\') . DIRECTORY_SEPARATOR;
$targetUserId = $syncUserId === null ? 0 : (int) $syncUserId;
$imagesDir = imagekpr_ensure_user_images_dir($targetUserId) . DIRECTORY_SEPARATOR;
$maxSize = 3 * 1024 * 1024;
$allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

try {
  $pdo = new PDO(
    'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
    DB_USER,
    DB_PASS,
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
  );
} catch (PDOException $e) {
  fwrite(STDERR, "Database connection failed.\n");
  exit(1);
}

if (!is_dir($inboxDir)) {
  echo "Inbox directory not found.\n";
  exit(0);
}

if ($syncUserId === null) {
  $stmt = $pdo->query('SELECT filename FROM images WHERE user_id IS NULL');
} else {
  $stmt = $pdo->prepare('SELECT filename FROM images WHERE user_id = ?');
  $stmt->execute([$syncUserId]);
}
$inDb = [];
while ($r = $stmt->fetch(PDO::FETCH_COLUMN)) {
  $inDb[$r] = true;
}

$imported = 0;
foreach (scandir($inboxDir) as $f) {
  if ($f === '.' || $f === '..') continue;
  $path = $inboxDir . $f;
  if (!is_file($path)) continue;
  $ext = strtolower(pathinfo($f, PATHINFO_EXTENSION));
  if (!in_array($ext, $exts, true)) continue;
  $mime = @finfo_file(finfo_open(FILEINFO_MIME_TYPE), $path);
  if (!in_array($mime, $allowedMimes, true)) continue;
  if (filesize($path) > $maxSize) continue;

  $baseName = preg_replace('/[^a-zA-Z0-9._-]/', '_', $f);
  $baseName = trim($baseName, '._') ?: 'image';
  $extNorm = pathinfo($baseName, PATHINFO_EXTENSION);
  $extNorm = $extNorm ? preg_replace('/[^a-z0-9]/', '', strtolower($extNorm)) : 'jpg';
  $stem = pathinfo($baseName, PATHINFO_FILENAME) ?: 'image';
  $baseName = $stem . '.' . $extNorm;

  $suffix = 0;
  $chosen = null;
  while (true) {
    $candidate = $suffix === 0 ? $baseName : $stem . '-' . $suffix . '.' . $extNorm;
    if (isset($inDb[$candidate])) {
      continue 2;
    }
    $dest = $imagesDir . $candidate;
    if (!file_exists($dest)) {
      $chosen = $candidate;
      break;
    }
    $suffix++;
  }
  if ($chosen === null) {
    continue;
  }
  $dest = $imagesDir . $chosen;

  if (copy($path, $dest)) {
    @unlink($path);
    $info = @getimagesize($dest);
    $url = imagekpr_user_images_url($targetUserId) . '/' . $chosen;
    $st = $pdo->prepare('INSERT INTO images (filename, url, date_uploaded, size_bytes, width, height, tags, user_id) VALUES (?, ?, NOW(), ?, ?, ?, ?, ?)');
    $st->execute([$chosen, $url, filesize($dest), $info[0] ?? null, $info[1] ?? null, '[]', $syncUserId]);
    $inDb[$chosen] = true;
    $imported++;
    echo "Imported: $chosen\n";
  }
}
echo "Done. Imported $imported file(s).\n";