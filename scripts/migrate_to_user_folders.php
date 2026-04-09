#!/usr/bin/env php
<?php
/**
 * Move existing images from flat IMAGES_DIR to per-user folders and update URLs.
 * Safe to re-run; already migrated rows are skipped.
 *
 * Usage:
 *   php scripts/migrate_to_user_folders.php
 */

$root = dirname(__DIR__);
if (!file_exists($root . '/config.php')) {
  fwrite(STDERR, "config.php not found. Copy config.example.php to config.php.\n");
  exit(1);
}

require_once $root . '/config.php';
require_once $root . '/inc/images_path.php';

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

$flatRoot = rtrim(IMAGES_DIR, '/\\') . DIRECTORY_SEPARATOR;
$st = $pdo->query('SELECT id, filename, user_id FROM images ORDER BY id ASC');
$up = $pdo->prepare('UPDATE images SET url = ? WHERE id = ?');

$moved = 0;
$updated = 0;
$skipped = 0;
$errors = 0;

while ($row = $st->fetch(PDO::FETCH_ASSOC)) {
  $id = (int) ($row['id'] ?? 0);
  $filename = basename((string) ($row['filename'] ?? ''));
  $userId = isset($row['user_id']) ? (int) $row['user_id'] : 0;
  if ($id < 1 || $filename === '' || $filename === '.' || $filename === '..') {
    $skipped++;
    continue;
  }

  $targetDir = imagekpr_ensure_user_images_dir($userId);
  $targetPath = $targetDir . DIRECTORY_SEPARATOR . $filename;
  $flatPath = $flatRoot . $filename;

  if (!file_exists($targetPath) && file_exists($flatPath)) {
    if (!@rename($flatPath, $targetPath)) {
      fwrite(STDERR, "Failed move for image id {$id}: {$filename}\n");
      $errors++;
      continue;
    }
    $moved++;
  } elseif (!file_exists($targetPath) && !file_exists($flatPath)) {
    fwrite(STDERR, "Missing file for image id {$id}: {$filename}\n");
    $errors++;
    continue;
  }

  $url = imagekpr_user_images_url($userId) . '/' . $filename;
  $up->execute([$url, $id]);
  $updated++;
}

echo "Done.\n";
echo "Moved: {$moved}\n";
echo "Updated URLs: {$updated}\n";
echo "Skipped: {$skipped}\n";
echo "Errors: {$errors}\n";
