<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

if (!file_exists(__DIR__ . '/../config.php')) {
    http_response_code(500);
    echo json_encode(['error' => 'Configuration missing. Copy config.example.php to config.php.']);
    exit;
}
require_once __DIR__ . '/../config.php';

try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

$stmt = $pdo->query('SELECT COUNT(*) FROM images');
$totalImages = (int) $stmt->fetchColumn();

$stmt = $pdo->query('SELECT COALESCE(SUM(size_bytes), 0) FROM images');
$totalStorageBytes = (int) $stmt->fetchColumn();
$totalStorageGb = number_format($totalStorageBytes / (1024 * 1024 * 1024), 2);

$stmt = $pdo->query(
    'SELECT id, filename, url, date_uploaded, size_bytes, width, height, tags
     FROM images
     ORDER BY date_uploaded DESC
     LIMIT 10'
);
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
