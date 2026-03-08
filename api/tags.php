<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
  if (!file_exists(__DIR__ . '/../config.php')) {
    http_response_code(500);
    echo json_encode(['error' => 'Configuration missing.']);
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
    echo json_encode(['tags' => []]);
    exit;
  }
  $stmt = $pdo->query('SELECT tags FROM images WHERE tags IS NOT NULL AND tags != "[]"');
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

if (!file_exists(__DIR__ . '/../config.php')) {
  http_response_code(500);
  echo json_encode(['success' => false, 'error' => 'Configuration missing.']);
  exit;
}
require_once __DIR__ . '/../config.php';

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

try {
  $pdo = new PDO(
    'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
    DB_USER,
    DB_PASS,
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
  );
} catch (PDOException $e) {
  echo json_encode(['success' => false, 'error' => 'Database connection failed']);
  exit;
}

$targetIds = $ids ? array_map('intval', $ids) : [$id];

if ($tags !== null) {
  $tags = array_values(array_unique(array_map('trim', array_filter($tags, 'is_string'))));
  $json = json_encode($tags);
  $stmt = $pdo->prepare('UPDATE images SET tags = ? WHERE id = ?');
  foreach ($targetIds as $tid) {
    $stmt->execute([$json, $tid]);
  }
} elseif ($action && $tag) {
  foreach ($targetIds as $tid) {
    $st = $pdo->prepare('SELECT tags FROM images WHERE id = ?');
    $st->execute([$tid]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    if (!$row) continue;
    $current = json_decode($row['tags'], true) ?: [];
    if ($action === 'add' && !in_array($tag, $current)) $current[] = $tag;
    if ($action === 'remove') $current = array_values(array_filter($current, fn($t) => $t !== $tag));
    $stmt = $pdo->prepare('UPDATE images SET tags = ? WHERE id = ?');
    $stmt->execute([json_encode($current), $tid]);
  }
} else {
  echo json_encode(['success' => false, 'error' => 'Provide tags array or action+tag']);
  exit;
}

echo json_encode(['success' => true]);
