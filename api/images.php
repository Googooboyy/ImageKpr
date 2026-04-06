<?php
require_once __DIR__ . '/../inc/auth.php';
imagekpr_require_api_user();
$uid = imagekpr_user_id();
header('Content-Type: application/json; charset=utf-8');
header('X-ImageKpr-User-Id: ' . (int) $uid);

$allowedSort = ['date_desc', 'date_asc', 'size_desc', 'size_asc', 'name_asc', 'name_desc', 'random'];
$sort = isset($_GET['sort']) && in_array($_GET['sort'], $allowedSort) ? $_GET['sort'] : 'date_desc';
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = min(imagekpr_max_images_per_page(), max(1, (int)($_GET['per_page'] ?? 50)));
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$tag = isset($_GET['tag']) ? trim($_GET['tag']) : '';
$idsParam = isset($_GET['ids']) ? trim($_GET['ids']) : '';
$ids = [];
if ($idsParam !== '') {
  $raw = array_map('intval', array_filter(explode(',', $idsParam)));
  $ids = array_unique(array_filter($raw));
}
if (imagekpr_bulk_ids_too_many($ids)) {
  http_response_code(400);
  echo json_encode(['error' => 'Too many ids (max ' . imagekpr_max_bulk_image_ids() . ')']);
  exit;
}

try {
  $pdo = imagekpr_pdo();
} catch (PDOException $e) {
  http_response_code(500);
  echo json_encode(['error' => 'Database connection failed']);
  exit;
}

imagekpr_ensure_config();
$uidInt = (int) $uid;
$userScope = '(user_id = ' . $uidInt . ')';
if (imagekpr_share_null_user_rows_enabled()) {
  $userScope = '(user_id = ' . $uidInt . ' OR user_id IS NULL)';
}
$where = [$userScope];
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

switch ($sort) {
  case 'date_asc':
    $orderBy = 'id ASC';
    break;
  case 'size_desc':
    $orderBy = 'size_bytes DESC';
    break;
  case 'size_asc':
    $orderBy = 'size_bytes ASC';
    break;
  case 'name_asc':
    $orderBy = 'filename ASC';
    break;
  case 'name_desc':
    $orderBy = 'filename DESC';
    break;
  case 'random':
    $orderBy = 'RAND()';
    break;
  case 'date_desc':
  default:
    $orderBy = 'id DESC';
    break;
}

$countSql = "SELECT COUNT(*) FROM images WHERE $whereClause";
$stmt = $pdo->prepare($countSql);
foreach ($params as $k => $v) {
  if (preg_match('/^:ids\d+$/', $k)) {
    $stmt->bindValue($k, $v, PDO::PARAM_INT);
  } else {
    $stmt->bindValue($k, $v, PDO::PARAM_STR);
  }
}
$stmt->execute();
$total = (int) $stmt->fetchColumn();

$offset = ($page - 1) * $perPage;
// Integers only (already validated); avoid bound LIMIT/OFFSET — breaks some MySQL PDO native prepares.
$lim = (int) $perPage;
$off = (int) $offset;
$sql = "SELECT id, filename, url, date_uploaded, size_bytes, width, height, tags
        FROM images
        WHERE $whereClause
        ORDER BY $orderBy
        LIMIT $lim OFFSET $off";
$stmt = $pdo->prepare($sql);
foreach ($params as $k => $v) {
  if (preg_match('/^:ids\d+$/', $k)) {
    $stmt->bindValue($k, $v, PDO::PARAM_INT);
  } else {
    $stmt->bindValue($k, $v, PDO::PARAM_STR);
  }
}
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