<?php
require_once __DIR__ . '/../inc/auth.php';
require_once __DIR__ . '/../inc/maintenance.php';

imagekpr_require_api_user();
header('Content-Type: application/json; charset=utf-8');

$uid = (int) imagekpr_user_id();
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

function imagekpr_folders_read_json_body(): array
{
  $raw = file_get_contents('php://input');
  if ($raw === false || $raw === '') {
    return [];
  }
  $j = json_decode($raw, true);
  return is_array($j) ? $j : [];
}

function imagekpr_folder_name_valid(string $name): bool
{
  $name = trim($name);
  if ($name === '' || strlen($name) > 255) {
    return false;
  }
  return strpos($name, "\0") === false;
}

function imagekpr_folder_row_id(PDO $pdo, int $userId, string $name): ?int
{
  $st = $pdo->prepare('SELECT id FROM folders WHERE user_id = ? AND name = ? LIMIT 1');
  $st->execute([$userId, $name]);
  $row = $st->fetch(PDO::FETCH_ASSOC);
  return $row ? (int) $row['id'] : null;
}

/** Create folder row if missing; returns folder id. */
function imagekpr_folder_get_or_create(PDO $pdo, int $userId, string $name): int
{
  $id = imagekpr_folder_row_id($pdo, $userId, $name);
  if ($id !== null) {
    return $id;
  }
  $ins = $pdo->prepare('INSERT INTO folders (user_id, name) VALUES (?, ?)');
  $ins->execute([$userId, $name]);
  return (int) $pdo->lastInsertId();
}

try {
  $pdo = imagekpr_pdo();
} catch (PDOException $e) {
  http_response_code(500);
  echo json_encode(['error' => 'Database connection failed']);
  exit;
}

if ($method === 'GET') {
  $st = $pdo->prepare(
    'SELECT f.name, fi.image_id FROM folders f
     LEFT JOIN folder_images fi ON fi.folder_id = f.id
     WHERE f.user_id = ?
     ORDER BY f.name ASC, fi.image_id ASC'
  );
  $st->execute([$uid]);
  $folders = [];
  while ($row = $st->fetch(PDO::FETCH_ASSOC)) {
    $n = $row['name'];
    if (!isset($folders[$n])) {
      $folders[$n] = [];
    }
    if ($row['image_id'] !== null) {
      $folders[$n][] = (int) $row['image_id'];
    }
  }
  echo json_encode(['folders' => $folders], JSON_UNESCAPED_UNICODE);
  exit;
}

imagekpr_block_if_maintenance_json();

$body = in_array($method, ['POST', 'PATCH'], true) ? imagekpr_folders_read_json_body() : [];

if ($method === 'POST') {
  $name = trim((string) ($body['name'] ?? ''));
  if (!imagekpr_folder_name_valid($name)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid folder name']);
    exit;
  }
  try {
    $st = $pdo->prepare('INSERT INTO folders (user_id, name) VALUES (?, ?)');
    $st->execute([$uid, $name]);
    echo json_encode(['success' => true, 'name' => $name], JSON_UNESCAPED_UNICODE);
  } catch (PDOException $e) {
    if ((int) $e->errorInfo[1] === 1062) {
      http_response_code(409);
      echo json_encode(['error' => 'Folder already exists', 'code' => 'duplicate'], JSON_UNESCAPED_UNICODE);
      exit;
    }
    throw $e;
  }
  exit;
}

if ($method === 'PATCH') {
  $action = (string) ($body['action'] ?? '');
  if ($action === 'add') {
    $name = trim((string) ($body['name'] ?? ''));
    if (!imagekpr_folder_name_valid($name)) {
      http_response_code(400);
      echo json_encode(['error' => 'Invalid folder name']);
      exit;
    }
    $rawIds = $body['image_ids'] ?? [];
    if (!is_array($rawIds)) {
      http_response_code(400);
      echo json_encode(['error' => 'image_ids must be an array']);
      exit;
    }
    $ids = array_values(array_unique(array_filter(array_map('intval', $rawIds))));
    if (imagekpr_bulk_ids_too_many($ids)) {
      http_response_code(400);
      echo json_encode(['error' => 'Too many image_ids (max ' . imagekpr_max_bulk_image_ids() . ')']);
      exit;
    }
    if ($ids === []) {
      echo json_encode(['success' => true]);
      exit;
    }
    $pdo->beginTransaction();
    try {
      $folderId = imagekpr_folder_get_or_create($pdo, $uid, $name);
      $ph = implode(',', array_fill(0, count($ids), '?'));
      $st = $pdo->prepare("SELECT id FROM images WHERE user_id = ? AND id IN ($ph)");
      $st->execute(array_merge([$uid], $ids));
      $ok = $st->fetchAll(PDO::FETCH_COLUMN);
      $ins = $pdo->prepare('INSERT IGNORE INTO folder_images (folder_id, image_id) VALUES (?, ?)');
      foreach ($ok as $iid) {
        $ins->execute([$folderId, (int) $iid]);
      }
      $pdo->commit();
      echo json_encode(['success' => true], JSON_UNESCAPED_UNICODE);
    } catch (Throwable $e) {
      $pdo->rollBack();
      throw $e;
    }
    exit;
  }
  if ($action === 'remove') {
    $name = trim((string) ($body['name'] ?? ''));
    if (!imagekpr_folder_name_valid($name)) {
      http_response_code(400);
      echo json_encode(['error' => 'Invalid folder name']);
      exit;
    }
    $rawIds = $body['image_ids'] ?? [];
    if (!is_array($rawIds)) {
      http_response_code(400);
      echo json_encode(['error' => 'image_ids must be an array']);
      exit;
    }
    $ids = array_values(array_unique(array_filter(array_map('intval', $rawIds))));
    if (imagekpr_bulk_ids_too_many($ids)) {
      http_response_code(400);
      echo json_encode(['error' => 'Too many image_ids (max ' . imagekpr_max_bulk_image_ids() . ')']);
      exit;
    }
    $folderId = imagekpr_folder_row_id($pdo, $uid, $name);
    if ($folderId === null) {
      http_response_code(404);
      echo json_encode(['error' => 'Folder not found']);
      exit;
    }
    if ($ids === []) {
      echo json_encode(['success' => true]);
      exit;
    }
    $ph = implode(',', array_fill(0, count($ids), '?'));
    $st = $pdo->prepare("DELETE FROM folder_images WHERE folder_id = ? AND image_id IN ($ph)");
    $st->execute(array_merge([$folderId], $ids));
    echo json_encode(['success' => true], JSON_UNESCAPED_UNICODE);
    exit;
  }
  if ($action === 'rename') {
    $old = trim((string) ($body['name'] ?? ''));
    $new = trim((string) ($body['new_name'] ?? ''));
    if (!imagekpr_folder_name_valid($old) || !imagekpr_folder_name_valid($new)) {
      http_response_code(400);
      echo json_encode(['error' => 'Invalid folder name']);
      exit;
    }
    if ($old === $new) {
      echo json_encode(['success' => true]);
      exit;
    }
    $folderId = imagekpr_folder_row_id($pdo, $uid, $old);
    if ($folderId === null) {
      http_response_code(404);
      echo json_encode(['error' => 'Folder not found']);
      exit;
    }
    $dup = imagekpr_folder_row_id($pdo, $uid, $new);
    if ($dup !== null) {
      http_response_code(409);
      echo json_encode(['error' => 'A folder with that name already exists']);
      exit;
    }
    $st = $pdo->prepare('UPDATE folders SET name = ? WHERE id = ? AND user_id = ?');
    $st->execute([$new, $folderId, $uid]);
    echo json_encode(['success' => true, 'name' => $new], JSON_UNESCAPED_UNICODE);
    exit;
  }
  http_response_code(400);
  echo json_encode(['error' => 'Unknown action']);
  exit;
}

if ($method === 'DELETE') {
  $name = trim((string) ($_GET['name'] ?? ''));
  if (!imagekpr_folder_name_valid($name)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid or missing name']);
    exit;
  }
  $st = $pdo->prepare('DELETE FROM folders WHERE user_id = ? AND name = ?');
  $st->execute([$uid, $name]);
  echo json_encode(['success' => true], JSON_UNESCAPED_UNICODE);
  exit;
}

http_response_code(405);
header('Allow: GET, POST, PATCH, DELETE');
echo json_encode(['error' => 'Method not allowed']);
