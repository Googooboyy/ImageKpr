<?php
require_once __DIR__ . '/../inc/auth.php';
imagekpr_require_api_user();
header('X-Content-Type-Options: nosniff');


$exts = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
$mimes = ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'gif' => 'image/gif', 'webp' => 'image/webp'];
$inboxDir = rtrim(INBOX_DIR, '/\\') . DIRECTORY_SEPARATOR;

if ($_SERVER['REQUEST_METHOD'] !== 'GET' || empty($_GET['file'])) {
  http_response_code(400);
  exit;
}

$filename = basename($_GET['file']);
if ($filename === '' || strpos($filename, '.') === false) {
  http_response_code(400);
  exit;
}

$ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
if (!in_array($ext, $exts)) {
  http_response_code(403);
  exit;
}

$path = $inboxDir . $filename;
if (!is_file($path) || realpath(dirname($path)) !== realpath($inboxDir)) {
  http_response_code(404);
  exit;
}

header('Content-Type: ' . ($mimes[$ext] ?? 'application/octet-stream'));
header('Cache-Control: private, max-age=60');
header('Content-Length: ' . filesize($path));
readfile($path);