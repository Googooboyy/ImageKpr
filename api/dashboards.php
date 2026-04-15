<?php
require_once __DIR__ . '/../inc/auth.php';
require_once __DIR__ . '/../inc/tiers.php';

header('Content-Type: application/json; charset=utf-8');
imagekpr_start_session();
imagekpr_json_security_headers();

function imagekpr_dash_abort(int $code, string $error, array $extra = []): void
{
  http_response_code($code);
  echo json_encode(array_merge(['success' => false, 'error' => $error], $extra), JSON_UNESCAPED_UNICODE);
  exit;
}

function imagekpr_dash_token(): string
{
  return rtrim(strtr(base64_encode(random_bytes(16)), '+/', '-_'), '=');
}

function imagekpr_dash_parse_expiry(?string $preset, ?string $custom): ?string
{
  $preset = (string) ($preset ?? 'never');
  if ($preset === '' || $preset === 'never') {
    return null;
  }
  $now = new DateTimeImmutable('now');
  $map = [
    '1h' => '+1 hour',
    '24h' => '+24 hours',
    '7d' => '+7 days',
    '30d' => '+30 days',
    '90d' => '+90 days',
  ];
  if (isset($map[$preset])) {
    return $now->modify($map[$preset])->format('Y-m-d H:i:s');
  }
  if ($preset === 'custom') {
    $v = trim((string) ($custom ?? ''));
    if ($v === '') {
      return null;
    }
    $dt = DateTimeImmutable::createFromFormat('Y-m-d\TH:i', $v) ?: new DateTimeImmutable($v);
    return $dt->format('Y-m-d H:i:s');
  }
  return null;
}

function imagekpr_dash_image_rows(PDO $pdo, int $dashboardId, int $userId): array
{
  $sql = 'SELECT i.id, i.filename, i.url, i.date_uploaded, i.width, i.height, sdi.sort_order
          FROM shared_dashboard_images sdi
          INNER JOIN images i ON i.id = sdi.image_id
          WHERE sdi.dashboard_id = ? AND i.user_id = ?
          ORDER BY sdi.sort_order ASC, sdi.id ASC';
  $st = $pdo->prepare($sql);
  $st->execute([$dashboardId, $userId]);
  return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

function imagekpr_dash_base_url(): string
{
  $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
  if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
    $scheme = strtolower((string) $_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https' ? 'https' : $scheme;
  }
  $host = (string) ($_SERVER['HTTP_HOST'] ?? 'localhost');
  $script = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));
  $base = rtrim(dirname(dirname($script)), '/');
  return $scheme . '://' . $host . ($base !== '' ? $base : '');
}

function imagekpr_dash_payload(PDO $pdo, array $dash, bool $forPublic = false): array
{
  $images = imagekpr_dash_image_rows($pdo, (int) $dash['id'], (int) $dash['user_id']);
  $ownerPaid = imagekpr_user_is_paid($pdo, (int) $dash['user_id']);
  $base = imagekpr_dash_base_url();
  foreach ($images as &$img) {
    $url = (string) ($img['url'] ?? '');
    if ($url !== '' && !preg_match('#^https?://#i', $url)) {
      $url = rtrim($base, '/') . '/' . ltrim($url, '/');
    }
    $img['url'] = $url;
  }
  unset($img);
  $sharePath = 'index.php?share=' . rawurlencode((string) $dash['token']);
  $shareUrl = rtrim($base, '/') . '/' . $sharePath;
  $expiresAt = $dash['expires_at'] ? (string) $dash['expires_at'] : null;
  $payload = [
    'id' => (int) $dash['id'],
    'token' => (string) $dash['token'],
    'title' => (string) ($dash['title'] ?? ''),
    'subtitle' => (string) ($dash['subtitle'] ?? ''),
    'hero_image_id' => $dash['hero_image_id'] ? (int) $dash['hero_image_id'] : null,
    'allow_slideshow' => (bool) ((int) ($dash['allow_slideshow'] ?? 1)),
    'allow_download' => (bool) ((int) ($dash['allow_download'] ?? 1)),
    'expires_at' => $expiresAt,
    'never_expires' => $expiresAt === null,
    'view_count' => (int) ($dash['view_count'] ?? 0),
    'updated_at' => (string) ($dash['updated_at'] ?? ''),
    'created_at' => (string) ($dash['created_at'] ?? ''),
    'is_password_protected' => !empty($dash['password_hash']),
    'watermark_required' => !$ownerPaid,
    'image_count' => count($images),
    'images' => $images,
    'url' => $sharePath,
    'url_absolute' => $shareUrl,
  ];
  if ($forPublic) {
    return [
      'success' => true,
      'dashboard' => $payload,
      'is_password_protected' => $payload['is_password_protected'],
      'can_slideshow' => $payload['allow_slideshow'],
      'can_download' => $payload['allow_download'],
      'expires_at' => $payload['expires_at'],
      'never_expires' => $payload['never_expires'],
      'watermark_required' => $payload['watermark_required'],
      'images' => $images,
    ];
  }
  return $payload;
}

try {
  $pdo = imagekpr_pdo();
} catch (Throwable $e) {
  imagekpr_dash_abort(500, 'Database connection failed');
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['token'])) {
  $token = trim((string) $_GET['token']);
  if ($token === '') {
    imagekpr_dash_abort(400, 'Missing token');
  }
  $st = $pdo->prepare('SELECT * FROM shared_dashboards WHERE token = ? LIMIT 1');
  $st->execute([$token]);
  $dash = $st->fetch(PDO::FETCH_ASSOC);
  if (!$dash) {
    imagekpr_dash_abort(404, 'Dashboard not found');
  }
  if (!empty($dash['expires_at']) && strtotime((string) $dash['expires_at']) < time()) {
    imagekpr_dash_abort(410, 'Dashboard expired');
  }
  $upd = $pdo->prepare('UPDATE shared_dashboards SET view_count = view_count + 1, last_viewed_at = NOW() WHERE id = ?');
  $upd->execute([(int) $dash['id']]);
  $dash['view_count'] = (int) $dash['view_count'] + 1;
  echo json_encode(imagekpr_dash_payload($pdo, $dash, true), JSON_UNESCAPED_UNICODE);
  exit;
}

imagekpr_require_api_user();
$uid = imagekpr_user_id();
$uidInt = (int) $uid;

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
  if (isset($_GET['id'])) {
    $id = (int) $_GET['id'];
    $st = $pdo->prepare('SELECT * FROM shared_dashboards WHERE id = ? AND user_id = ? LIMIT 1');
    $st->execute([$id, $uidInt]);
    $dash = $st->fetch(PDO::FETCH_ASSOC);
    if (!$dash) {
      imagekpr_dash_abort(404, 'Dashboard not found');
    }
    echo json_encode(['success' => true, 'dashboard' => imagekpr_dash_payload($pdo, $dash)], JSON_UNESCAPED_UNICODE);
    exit;
  }
  $st = $pdo->prepare('SELECT * FROM shared_dashboards WHERE user_id = ? ORDER BY updated_at DESC, id DESC');
  $st->execute([$uidInt]);
  $rows = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
  $items = [];
  foreach ($rows as $row) {
    $items[] = imagekpr_dash_payload($pdo, $row);
  }
  echo json_encode(['success' => true, 'dashboards' => $items], JSON_UNESCAPED_UNICODE);
  exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  imagekpr_dash_abort(405, 'Method not allowed');
}

$input = json_decode(file_get_contents('php://input'), true) ?: [];
$action = (string) ($input['action'] ?? '');

if ($action === 'delete') {
  $id = (int) ($input['id'] ?? 0);
  if ($id < 1) {
    imagekpr_dash_abort(400, 'Missing id');
  }
  $st = $pdo->prepare('DELETE FROM shared_dashboards WHERE id = ? AND user_id = ?');
  $st->execute([$id, $uidInt]);
  echo json_encode(['success' => true], JSON_UNESCAPED_UNICODE);
  exit;
}

if ($action === 'add_images') {
  $id = (int) ($input['id'] ?? 0);
  if ($id < 1) {
    imagekpr_dash_abort(400, 'Missing id');
  }
  $newIds = isset($input['image_ids']) && is_array($input['image_ids']) ? array_values(array_unique(array_map('intval', $input['image_ids']))) : [];
  if (empty($newIds)) {
    imagekpr_dash_abort(400, 'No image_ids provided');
  }
  $st = $pdo->prepare('SELECT * FROM shared_dashboards WHERE id = ? AND user_id = ? LIMIT 1');
  $st->execute([$id, $uidInt]);
  $dash = $st->fetch(PDO::FETCH_ASSOC);
  if (!$dash) {
    imagekpr_dash_abort(404, 'Dashboard not found');
  }
  $ph = implode(',', array_fill(0, count($newIds), '?'));
  $st = $pdo->prepare('SELECT id FROM images WHERE user_id = ? AND id IN (' . $ph . ')');
  $st->execute(array_merge([$uidInt], $newIds));
  $validNew = array_map('intval', $st->fetchAll(PDO::FETCH_COLUMN));
  if (empty($validNew)) {
    imagekpr_dash_abort(400, 'No valid images');
  }
  $st = $pdo->prepare('SELECT COALESCE(MAX(sort_order), -1) FROM shared_dashboard_images WHERE dashboard_id = ?');
  $st->execute([$id]);
  $maxSort = (int) $st->fetchColumn();
  $st = $pdo->prepare('SELECT image_id FROM shared_dashboard_images WHERE dashboard_id = ?');
  $st->execute([$id]);
  $existing = array_map('intval', $st->fetchAll(PDO::FETCH_COLUMN));
  $existingSet = array_flip($existing);
  $limit = imagekpr_dashboard_image_limit($pdo, $uidInt);
  $ins = $pdo->prepare('INSERT INTO shared_dashboard_images (dashboard_id, image_id, sort_order) VALUES (?, ?, ?)');
  $added = 0;
  foreach ($validNew as $imgId) {
    if (isset($existingSet[$imgId])) continue;
    if ((count($existing) + $added + 1) > $limit) break;
    $maxSort++;
    $ins->execute([$id, $imgId, $maxSort]);
    $added++;
  }
  $rowSt = $pdo->prepare('SELECT * FROM shared_dashboards WHERE id = ? LIMIT 1');
  $rowSt->execute([$id]);
  $dash = $rowSt->fetch(PDO::FETCH_ASSOC);
  echo json_encode(['success' => true, 'added' => $added, 'dashboard' => imagekpr_dash_payload($pdo, $dash)], JSON_UNESCAPED_UNICODE);
  exit;
}

if ($action !== 'create' && $action !== 'update') {
  imagekpr_dash_abort(400, 'Unknown action');
}

$title = trim((string) ($input['title'] ?? ''));
$subtitle = trim((string) ($input['subtitle'] ?? ''));
$allowSlideshow = !isset($input['allow_slideshow']) || (bool) $input['allow_slideshow'];
$allowDownload = !isset($input['allow_download']) || (bool) $input['allow_download'];
$imageIds = isset($input['image_ids']) && is_array($input['image_ids']) ? array_values(array_unique(array_map('intval', $input['image_ids']))) : [];
$heroImageId = isset($input['hero_image_id']) ? (int) $input['hero_image_id'] : null;
$expiresAt = imagekpr_dash_parse_expiry((string) ($input['expiry_preset'] ?? 'never'), (string) ($input['expires_custom'] ?? ''));
$password = trim((string) ($input['password'] ?? ''));
$passwordHash = null;
if ($password !== '') {
  if (!imagekpr_user_is_paid($pdo, $uidInt)) {
    imagekpr_dash_abort(403, 'Password protection requires a paid tier');
  }
  if (strlen($password) < 4) {
    imagekpr_dash_abort(422, 'Password must be at least 4 characters');
  }
  $passwordHash = password_hash($password, PASSWORD_BCRYPT);
}

$limit = imagekpr_dashboard_image_limit($pdo, $uidInt);
if (count($imageIds) > $limit) {
  imagekpr_dash_abort(422, 'Your plan allows up to ' . $limit . ' images per dashboard');
}

if (!empty($imageIds)) {
  $ph = implode(',', array_fill(0, count($imageIds), '?'));
  $st = $pdo->prepare('SELECT id FROM images WHERE user_id = ? AND id IN (' . $ph . ')');
  $st->execute(array_merge([$uidInt], $imageIds));
  $valid = array_map('intval', $st->fetchAll(PDO::FETCH_COLUMN));
  $validLookup = array_flip($valid);
  $imageIds = array_values(array_filter($imageIds, static function ($id) use ($validLookup) {
    return isset($validLookup[(int) $id]);
  }));
}

if ($heroImageId && !in_array($heroImageId, $imageIds, true)) {
  $heroImageId = null;
}

if ($action === 'create') {
  $token = imagekpr_dash_token();
  $st = $pdo->prepare('INSERT INTO shared_dashboards (user_id, token, title, subtitle, hero_image_id, allow_slideshow, allow_download, password_hash, expires_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
  $st->execute([
    $uidInt,
    $token,
    $title !== '' ? $title : null,
    $subtitle !== '' ? $subtitle : null,
    $heroImageId ?: null,
    $allowSlideshow ? 1 : 0,
    $allowDownload ? 1 : 0,
    $passwordHash,
    $expiresAt,
  ]);
  $id = (int) $pdo->lastInsertId();
  if (!empty($imageIds)) {
    $ins = $pdo->prepare('INSERT INTO shared_dashboard_images (dashboard_id, image_id, sort_order) VALUES (?, ?, ?)');
    foreach ($imageIds as $idx => $imgId) {
      $ins->execute([$id, $imgId, $idx]);
    }
  }
  $rowSt = $pdo->prepare('SELECT * FROM shared_dashboards WHERE id = ? LIMIT 1');
  $rowSt->execute([$id]);
  $dash = $rowSt->fetch(PDO::FETCH_ASSOC);
  echo json_encode(['success' => true, 'dashboard' => imagekpr_dash_payload($pdo, $dash)], JSON_UNESCAPED_UNICODE);
  exit;
}

$id = (int) ($input['id'] ?? 0);
if ($id < 1) {
  imagekpr_dash_abort(400, 'Missing id');
}
$st = $pdo->prepare('SELECT * FROM shared_dashboards WHERE id = ? AND user_id = ? LIMIT 1');
$st->execute([$id, $uidInt]);
$dash = $st->fetch(PDO::FETCH_ASSOC);
if (!$dash) {
  imagekpr_dash_abort(404, 'Dashboard not found');
}
$nextHash = $dash['password_hash'];
if (array_key_exists('password', $input)) {
  if ($password === '') {
    $nextHash = null;
  } else {
    $nextHash = $passwordHash;
  }
}
$upd = $pdo->prepare('UPDATE shared_dashboards SET title = ?, subtitle = ?, hero_image_id = ?, allow_slideshow = ?, allow_download = ?, password_hash = ?, expires_at = ? WHERE id = ? AND user_id = ?');
$upd->execute([
  $title !== '' ? $title : null,
  $subtitle !== '' ? $subtitle : null,
  $heroImageId ?: null,
  $allowSlideshow ? 1 : 0,
  $allowDownload ? 1 : 0,
  $nextHash,
  $expiresAt,
  $id,
  $uidInt,
]);
$pdo->prepare('DELETE FROM shared_dashboard_images WHERE dashboard_id = ?')->execute([$id]);
if (!empty($imageIds)) {
  $ins = $pdo->prepare('INSERT INTO shared_dashboard_images (dashboard_id, image_id, sort_order) VALUES (?, ?, ?)');
  foreach ($imageIds as $idx => $imgId) {
    $ins->execute([$id, $imgId, $idx]);
  }
}
$rowSt = $pdo->prepare('SELECT * FROM shared_dashboards WHERE id = ? LIMIT 1');
$rowSt->execute([$id]);
$dash = $rowSt->fetch(PDO::FETCH_ASSOC);
echo json_encode(['success' => true, 'dashboard' => imagekpr_dash_payload($pdo, $dash)], JSON_UNESCAPED_UNICODE);
