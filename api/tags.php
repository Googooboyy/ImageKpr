<?php
require_once __DIR__ . '/../inc/auth.php';
imagekpr_require_api_user();
$uid = imagekpr_user_id();
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
  try {
    $pdo = imagekpr_pdo();
  } catch (PDOException $e) {
    echo json_encode(['tags' => []]);
    exit;
  }
  $stmt = $pdo->prepare('SELECT tags FROM images WHERE user_id = ? AND tags IS NOT NULL AND tags IS NOT NULL AND JSON_LENGTH(tags) > 0');
  $stmt->execute([$uid]);
  $allTags = [];
  while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $tags = json_decode($row['tags'], true);
    if (is_array($tags)) {
      foreach ($tags as $t) {
        if (is_string($t) && trim($t) !== '') {
          $allTags[trim($t)] = true;
        }
      }
    }
  }
  $tags = array_values(array_keys($allTags));
  sort($tags);
  echo json_encode(['tags' => $tags]);
  exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'PATCH') {
  http_response_code(405);
  echo json_encode(['success' => false, 'error' => 'Method not allowed']);
  exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?: [];
$id = isset($input['id']) ? (int) $input['id'] : null;
$ids = isset($input['ids']) ? $input['ids'] : null;
$tags = isset($input['tags']) ? $input['tags'] : null;
$action = isset($input['action']) ? $input['action'] : null;
$tag = isset($input['tag']) ? trim($input['tag']) : null;

if ($ids && !is_array($ids)) $ids = null;
if ($tags && !is_array($tags)) $tags = null;

if (!$id && (!$ids || empty($ids))) {
  echo json_encode(['success' => false, 'error' => 'Missing id or ids']);
  exit;
}

$rawTarget = $ids ? array_map('intval', $ids) : [$id];
if ($ids && imagekpr_bulk_ids_too_many($rawTarget)) {
  http_response_code(400);
  echo json_encode(['success' => false, 'error' => 'Too many ids (max ' . MAX_BULK_IMAGE_IDS . ')']);
  exit;
}
$targetIds = imagekpr_cap_bulk_ids($rawTarget);
if (empty($targetIds)) {
  echo json_encode(['success' => false, 'error' => 'Invalid ids']);
  exit;
}

try {
  $pdo = imagekpr_pdo();
} catch (PDOException $e) {
  echo json_encode(['success' => false, 'error' => 'Database connection failed']);
  exit;
}

if ($tags !== null) {
  $tags = array_values(array_unique(array_map('trim', array_filter($tags, 'is_string'))));
  $json = json_encode($tags);
  $stmt = $pdo->prepare('UPDATE images SET tags = ? WHERE id = ? AND user_id = ?');
  foreach ($targetIds as $tid) {
    $stmt->execute([$json, $tid, $uid]);
  }
} elseif ($action && $tag) {
  foreach ($targetIds as $tid) {
    $st = $pdo->prepare('SELECT tags FROM images WHERE id = ? AND user_id = ?');
    $st->execute([$tid, $uid]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    if (!$row) continue;
    $current = json_decode($row['tags'], true) ?: [];
    if ($action === 'add' && !in_array($tag, $current)) $current[] = $tag;
    if ($action === 'remove') $current = array_values(array_filter($current, fn($t) => $t !== $tag));
    $stmt = $pdo->prepare('UPDATE images SET tags = ? WHERE id = ? AND user_id = ?');
    $stmt->execute([json_encode($current), $tid, $uid]);
  }
} else {
  echo json_encode(['success' => false, 'error' => 'Provide tags array or action+tag']);
  exit;
}

echo json_encode(['success' => true]);