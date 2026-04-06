<?php
require_once __DIR__ . '/../inc/auth.php';
imagekpr_require_api_user();
$uid = imagekpr_user_id();
header('Content-Type: application/json; charset=utf-8');
header('X-ImageKpr-User-Id: ' . (int) $uid);

try {
  $pdo = imagekpr_pdo();
} catch (PDOException $e) {
  http_response_code(500);
  echo json_encode(['error' => 'Database connection failed']);
  exit;
}

imagekpr_ensure_config();
$uidInt = (int) $uid;
$userWhere = 'user_id = ' . $uidInt;
if (imagekpr_share_null_user_rows_enabled()) {
  $userWhere = '(user_id = ' . $uidInt . ' OR user_id IS NULL)';
}

$totalImages = (int) $pdo->query('SELECT COUNT(*) FROM images WHERE ' . $userWhere)->fetchColumn();

$totalStorageBytes = (int) $pdo->query('SELECT COALESCE(SUM(size_bytes), 0) FROM images WHERE ' . $userWhere)->fetchColumn();
$totalStorageGb = number_format($totalStorageBytes / (1024 * 1024 * 1024), 2);

$stmt = $pdo->prepare(
  'SELECT id, filename, url, date_uploaded, size_bytes, width, height, tags
   FROM images
   WHERE ' . $userWhere . '
   ORDER BY date_uploaded DESC
   LIMIT 10'
);
$stmt->execute();
$last10 = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($last10 as &$row) {
  if (isset($row['tags']) && is_string($row['tags'])) {
    $row['tags'] = json_decode($row['tags'], true) ?: [];
  }
}

echo json_encode([
  'total_images' => $totalImages,
  'total_storage_bytes' => $totalStorageBytes,
  'total_storage_gb' => $totalStorageGb,
  'last_10' => $last10,
]);