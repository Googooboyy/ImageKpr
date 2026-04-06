<?php
require_once __DIR__ . '/auth.php';

function imagekpr_user_is_admin(PDO $pdo, int $userId): bool
{
  if ($userId < 1) {
    return false;
  }
  $st = $pdo->prepare('SELECT is_admin FROM users WHERE id = ? LIMIT 1');
  $st->execute([$userId]);
  $row = $st->fetch(PDO::FETCH_ASSOC);
  return $row !== false && (int) $row['is_admin'] === 1;
}

/** JSON APIs: 401 if not logged in, 403 if not admin. Admin flag read from DB on every call. */
function imagekpr_require_admin_api(): void
{
  imagekpr_require_api_user();
  $uid = imagekpr_user_id();
  try {
    $pdo = imagekpr_pdo();
    if (!imagekpr_user_is_admin($pdo, $uid)) {
      http_response_code(403);
      header('Content-Type: application/json; charset=utf-8');
      echo json_encode(['error' => 'Forbidden', 'hint' => 'Admin access required']);
      exit;
    }
  } catch (Throwable $e) {
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 'Server error', 'hint' => 'Could not verify admin']);
    exit;
  }
}

/**
 * HTML admin pages under admin/. Redirects to login if not authenticated; to main app if not admin.
 * $loginLevels / $appLevels: dirname steps from SCRIPT_NAME to site root (usually 1 for admin/*.php).
 */
function imagekpr_require_admin_html(int $loginLevels = 1, int $appLevels = 1): void
{
  imagekpr_start_session();
  if (imagekpr_user_id() < 1) {
    imagekpr_redirect_html('login.php', $loginLevels);
  }
  try {
    $pdo = imagekpr_pdo();
    if (!imagekpr_user_is_admin($pdo, imagekpr_user_id())) {
      imagekpr_redirect_html('index.php', $appLevels);
    }
  } catch (Throwable $e) {
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Could not verify admin. Run migrations/phase8_admin_foundation.sql if the schema is not ready.';
    exit;
  }
}

function imagekpr_csrf_token(): string
{
  imagekpr_start_session();
  if (empty($_SESSION['admin_csrf']) || !is_string($_SESSION['admin_csrf'])) {
    try {
      $_SESSION['admin_csrf'] = bin2hex(random_bytes(32));
    } catch (Throwable $e) {
      $_SESSION['admin_csrf'] = bin2hex(openssl_random_pseudo_bytes(32));
    }
  }
  return $_SESSION['admin_csrf'];
}

function imagekpr_csrf_field(): string
{
  return '<input type="hidden" name="csrf" value="' . htmlspecialchars(imagekpr_csrf_token(), ENT_QUOTES, 'UTF-8') . '">';
}

/** For POST forms; optionally accept X-CSRF-Token header for JSON admin APIs. */
function imagekpr_csrf_verify(): bool
{
  imagekpr_start_session();
  $t = $_POST['csrf'] ?? '';
  if (!is_string($t) || $t === '') {
    $h = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    $t = is_string($h) ? $h : '';
  }
  $sess = $_SESSION['admin_csrf'] ?? '';
  return is_string($sess) && $sess !== '' && is_string($t) && hash_equals($sess, $t);
}

/** Site default quota (bytes) when user row has NULL storage_quota_bytes; null = no default cap. */
function imagekpr_default_storage_quota_bytes(): ?int
{
  imagekpr_ensure_config();
  if (!defined('DEFAULT_STORAGE_QUOTA_BYTES')) {
    return null;
  }
  $v = (int) DEFAULT_STORAGE_QUOTA_BYTES;
  return $v > 0 ? $v : null;
}

/**
 * Effective cap in bytes: NULL = unlimited.
 * DB NULL → site default or unlimited; DB 0 → unlimited; DB >0 → cap.
 */
function imagekpr_effective_quota_bytes(?int $storageQuotaColumn): ?int
{
  if ($storageQuotaColumn !== null && $storageQuotaColumn > 0) {
    return $storageQuotaColumn;
  }
  if ($storageQuotaColumn === 0) {
    return null;
  }
  return imagekpr_default_storage_quota_bytes();
}

function imagekpr_format_bytes(int $bytes): string
{
  if ($bytes < 1024) {
    return $bytes . ' B';
  }
  $units = ['KB', 'MB', 'GB', 'TB'];
  $v = (float) $bytes;
  $u = -1;
  do {
    $v /= 1024;
    $u++;
  } while ($v >= 1024 && $u < count($units) - 1);
  return round($v, $u >= 2 ? 2 : 1) . ' ' . $units[$u];
}

/** Append-only audit row. $meta JSON-encoded; null stored as SQL NULL. */
function imagekpr_admin_audit_log(PDO $pdo, int $actorUserId, string $action, ?array $meta = null): void
{
  if ($actorUserId < 1) {
    return;
  }
  $json = null;
  if ($meta !== null) {
    $json = json_encode($meta, JSON_UNESCAPED_UNICODE);
    if ($json === false) {
      $json = '{}';
    }
  }
  $st = $pdo->prepare('INSERT INTO admin_audit_log (actor_user_id, action, meta_json) VALUES (?, ?, ?)');
  $st->execute([$actorUserId, $action, $json]);
}
