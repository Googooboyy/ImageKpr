#!/usr/bin/env php
<?php
/**
 * Sync inbox/ to images/ and DB.
 * Run: php scripts/sync_images.php
 */
$root = dirname(__DIR__);
if (!file_exists($root . '/config.php')) {
  fwrite(STDERR, "config.php not found. Copy config.example.php to config.php.\n");
  exit(1);
}
require_once $root . '/config.php';

$exts = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
$inboxDir = rtrim(INBOX_DIR, '/\\') . DIRECTORY_SEPARATOR;
$imagesDir = rtrim(IMAGES_DIR, '/\\') . DIRECTORY_SEPARATOR;
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

$stmt = $pdo->query('SELECT filename FROM images');
$inDb = [];
while ($r = $stmt->fetch(PDO::FETCH_COLUMN)) $inDb[$r] = true;

$imported = 0;
foreach (scandir($inboxDir) as $f) {
  if ($f === '.' || $f === '..') continue;
  $path = $inboxDir . $f;
  if (!is_file($path)) continue;
  $ext = strtolower(pathinfo($f, PATHINFO_EXTENSION));
  if (!in_array($ext, $exts)) continue;
  if (isset($inDb[$f])) continue;
  $mime = @finfo_file(finfo_open(FILEINFO_MIME_TYPE), $path);
  if (!in_array($mime, $allowedMimes)) continue;
  if (filesize($path) > $maxSize) continue;
  $baseName = preg_replace('/[^a-zA-Z0-9._-]/', '_', $f);
  $dest = $imagesDir . $baseName;
  $suffix = 0;
  while (file_exists($dest)) {
    $suffix++;
    $dest = $imagesDir . pathinfo($baseName, PATHINFO_FILENAME) . '-' . $suffix . '.' . pathinfo($baseName, PATHINFO_EXTENSION);
  }
  $baseName = basename($dest);
  if (rename($path, $dest)) {
    $info = @getimagesize($dest);
    $url = rtrim(IMAGES_URL, '/') . '/' . $baseName;
    $st = $pdo->prepare('INSERT INTO images (filename, url, date_uploaded, size_bytes, width, height, tags) VALUES (?, ?, NOW(), ?, ?, ?, ?)');
    $st->execute([$baseName, $url, filesize($dest), $info[0] ?? null, $info[1] ?? null, '[]']);
    $imported++;
    echo "Imported: $baseName\n";
  }
}
echo "Done. Imported $imported file(s).\n";
