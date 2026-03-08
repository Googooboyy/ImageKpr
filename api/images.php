<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

if (!file_exists(__DIR__ . '/../config.php')) {
    http_response_code(500);
    echo json_encode(['error' => 'Configuration missing. Copy config.example.php to config.php.']);
    exit;
}
require_once __DIR__ . '/../config.php';

$allowedSort = ['date_desc', 'date_asc', 'size_desc', 'size_asc', 'name_asc', 'name_desc', 'random'];
$sort = isset($_GET['sort']) && in_array($_GET['sort'], $allowedSort) ? $_GET['sort'] : 'date_desc';
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = min(5000, max(1, (int)($_GET['per_page'] ?? 50)));
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$tag = isset($_GET['tag']) ? trim($_GET['tag']) : '';
$idsParam = isset($_GET['ids']) ? trim($_GET['ids']) : '';
$ids = [];
if ($idsParam !== '') {
    $raw = array_map('intval', array_filter(explode(',', $idsParam)));
    $ids = array_unique(array_filter($raw));
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
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

$where = ['1=1'];
$params = [];

if ($search !== '') {
    $where[] = '(filename LIKE :search OR tags LIKE :search_tags)';
    $params[':search'] = '%' . $search . '%';
    $params[':search_tags'] = '%' . $search . '%';
}

if ($tag !== '') {
    $where[] = 'JSON_CONTAINS(tags, :tag_json, \'$\')';
    $params[':tag_json'] = json_encode($tag);
}

if (!empty($ids)) {
    $phs = [];
    foreach ($ids as $i => $id) {
        $k = ':ids' . $i;
        $phs[] = $k;
        $params[$k] = $id;
    }
    $where[] = 'id IN (' . implode(',', $phs) . ')';
}

$whereClause = implode(' AND ', $where);

$orderBy = match ($sort) {
    'date_asc' => 'date_uploaded ASC, id ASC',
    'date_desc' => 'date_uploaded DESC, id DESC',
    'size_desc' => 'size_bytes DESC',
    'size_asc' => 'size_bytes ASC',
    'name_asc' => 'filename ASC',
    'name_desc' => 'filename DESC',
    'random' => 'RAND()',
    default => 'date_uploaded DESC, id DESC',
};

$countSql = "SELECT COUNT(*) FROM images WHERE $whereClause";
$stmt = $pdo->prepare($countSql);
$stmt->execute($params);
$total = (int) $stmt->fetchColumn();

$offset = ($page - 1) * $perPage;
$sql = "SELECT id, filename, url, date_uploaded, size_bytes, width, height, tags
        FROM images
        WHERE $whereClause
        ORDER BY $orderBy
        LIMIT :limit OFFSET :offset";
$stmt = $pdo->prepare($sql);
foreach ($params as $k => $v) {
    $stmt->bindValue($k, $v);
}
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();

$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as &$row) {
    if (isset($row['tags']) && is_string($row['tags'])) {
        $row['tags'] = json_decode($row['tags'], true) ?: [];
    }
}

echo json_encode([
    'images' => $rows,
    'total' => $total,
    'page' => $page,
    'per_page' => $perPage,
]);
