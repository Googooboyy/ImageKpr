<?php
header('Access-Control-Allow-Origin: *');

if ($_SERVER['REQUEST_METHOD'] !== 'GET' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  exit;
}

if (!file_exists(__DIR__ . '/../config.php')) {
  http_response_code(500);
  exit;
}
require_once __DIR__ . '/../config.php';

$ids = [];
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
  $ids = isset($_GET['ids']) ? explode(',', $_GET['ids']) : [];
} else {
  $input = json_decode(file_get_contents('php://input'), true) ?: [];
  $ids = isset($input['ids']) ? $input['ids'] : [];
}
$ids = array_map('intval', array_filter(array_map('trim', $ids)));

if (empty($ids)) {
  http_response_code(400);
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
  http_response_code(500);
  exit;
}

$placeholders = implode(',', array_fill(0, count($ids), '?'));
$stmt = $pdo->prepare("SELECT id, filename FROM images WHERE id IN ($placeholders)");
$stmt->execute($ids);

$dir = rtrim(IMAGES_DIR, '/\\') . DIRECTORY_SEPARATOR;
$zip = new ZipArchive();
$tmp = tempnam(sys_get_temp_dir(), 'img');
$zip->open($tmp, ZipArchive::CREATE | ZipArchive::OVERWRITE);

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
  $path = $dir . $row['filename'];
  if (file_exists($path)) {
    $zip->addFile($path, $row['filename']);
  }
}
$zip->close();

$name = 'imagekpr-export-' . date('Ymd') . '.zip';
header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename="' . $name . '"');
header('Content-Length: ' . filesize($tmp));
readfile($tmp);
@unlink($tmp);
