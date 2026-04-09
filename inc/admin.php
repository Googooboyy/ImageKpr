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
    imagekpr_redirect_html('index.php', $loginLevels);
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
  $fromDb = imagekpr_site_default_quota_from_settings();
  if (ImageKprAppSettings::has('default_storage_quota_bytes')) {
    return $fromDb;
  }
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

/**
 * Whole days elapsed since a MySQL DATETIME until now (floor). Null if missing/invalid.
 * Used for admin dashboard (e.g. days since users.created_at or last_login_at). Clamped at 0 if clock skew.
 */
function imagekpr_days_since_mysql_datetime(?string $mysqlDt): ?int
{
  if ($mysqlDt === null || trim($mysqlDt) === '') {
    return null;
  }
  $ts = strtotime($mysqlDt);
  if ($ts === false) {
    return null;
  }
  return max(0, (int) floor((time() - $ts) / 86400));
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

/** Allowed per-image upload size tiers (MB). */
function imagekpr_allowed_upload_size_tiers_mb(): array
{
  return [3, 10, 100];
}

/** Normalize requested tier to a safe supported value (defaults to 3MB). */
function imagekpr_normalize_upload_size_mb($raw): int
{
  $v = (int) $raw;
  if ($v === 30) {
    $v = 100;
  }
  return in_array($v, imagekpr_allowed_upload_size_tiers_mb(), true) ? $v : 3;
}

function imagekpr_upload_limit_bytes_from_mb(int $mb): int
{
  return $mb * 1024 * 1024;
}

/** Upload downgrade grace period in days. */
function imagekpr_upload_tier_grace_days(): int
{
  return 30;
}

/** True when the downgrade grace window has elapsed. */
function imagekpr_upload_tier_grace_expired(?string $downgradedAt): bool
{
  if ($downgradedAt === null || trim($downgradedAt) === '') {
    return false;
  }
  $ts = strtotime($downgradedAt);
  if ($ts === false) {
    return false;
  }
  return (time() - $ts) >= (imagekpr_upload_tier_grace_days() * 86400);
}

/**
 * Return upload tier + downgrade marker for a user.
 * @return array{upload_size_mb:int,upload_tier_downgraded_at:?string}|null
 */
function imagekpr_user_upload_tier(PDO $pdo, int $userId): ?array
{
  if ($userId < 1) {
    return null;
  }
  $st = $pdo->prepare('SELECT upload_size_mb, upload_tier_downgraded_at FROM users WHERE id = ? LIMIT 1');
  $st->execute([$userId]);
  $row = $st->fetch(PDO::FETCH_ASSOC);
  if ($row === false) {
    return null;
  }
  $mb = imagekpr_normalize_upload_size_mb($row['upload_size_mb'] ?? 3);
  $downgradedAt = isset($row['upload_tier_downgraded_at']) ? (string) $row['upload_tier_downgraded_at'] : null;
  if ($downgradedAt !== null && trim($downgradedAt) === '') {
    $downgradedAt = null;
  }
  return [
    'upload_size_mb' => $mb,
    'upload_tier_downgraded_at' => $downgradedAt,
  ];
}

/** Effective max upload bytes for a single image for this user. */
function imagekpr_user_max_upload_bytes(PDO $pdo, int $userId): int
{
  $tier = imagekpr_user_upload_tier($pdo, $userId);
  $mb = $tier['upload_size_mb'] ?? 3;
  return imagekpr_upload_limit_bytes_from_mb($mb);
}

function imagekpr_user_storage_used(PDO $pdo, int $userId): int
{
  if ($userId < 1) {
    return 0;
  }
  $st = $pdo->prepare('SELECT COALESCE(SUM(size_bytes), 0) FROM images WHERE user_id = ?');
  $st->execute([$userId]);
  return (int) $st->fetchColumn();
}

/** Remaining bytes under effective quota, or null if unlimited / unknown user. */
function imagekpr_storage_quota_remaining(PDO $pdo, int $userId): ?int
{
  if ($userId < 1) {
    return null;
  }
  $st = $pdo->prepare('SELECT storage_quota_bytes FROM users WHERE id = ? LIMIT 1');
  $st->execute([$userId]);
  $row = $st->fetch(PDO::FETCH_ASSOC);
  if ($row === false) {
    return null;
  }
  $dbq = $row['storage_quota_bytes'];
  $dbq = $dbq === null ? null : (int) $dbq;
  $eff = imagekpr_effective_quota_bytes($dbq);
  if ($eff === null) {
    return null;
  }
  $used = imagekpr_user_storage_used($pdo, $userId);
  return max(0, $eff - $used);
}

/** Phrase admin must type exactly (after trim) to confirm bulk gallery purge; comparison is case-insensitive. */
function imagekpr_admin_purge_confirm_phrase(): string
{
  return 'DELETE GALLERY IMAGES';
}

/**
 * Remove all gallery images for the given users: unlink files from per-user image dirs, then DELETE rows.
 * Does not modify users, allowlist, or inbox.
 *
 * @param int[] $userIds
 * @return array{rows_deleted:int, files_removed:int}
 */
function imagekpr_admin_purge_gallery_for_users(PDO $pdo, array $userIds): array
{
  imagekpr_ensure_config();
  $userIds = array_values(array_unique(array_filter(array_map('intval', $userIds), static fn ($x) => $x > 0)));
  if ($userIds === []) {
    return ['rows_deleted' => 0, 'files_removed' => 0];
  }
  $placeholders = implode(',', array_fill(0, count($userIds), '?'));
  $st = $pdo->prepare("SELECT id, filename, user_id FROM images WHERE user_id IN ($placeholders)");
  $st->execute($userIds);
  $filesRemoved = 0;
  while ($row = $st->fetch(PDO::FETCH_ASSOC)) {
    $fn = basename((string) ($row['filename'] ?? ''));
    if ($fn === '' || $fn === '.' || $fn === '..') {
      continue;
    }
    $path = imagekpr_resolve_user_image_path((int) $row['user_id'], $fn);
    if (is_file($path) && @unlink($path)) {
      $filesRemoved++;
    }
  }
  $del = $pdo->prepare("DELETE FROM images WHERE user_id IN ($placeholders)");
  $del->execute($userIds);
  $rowsDeleted = (int) $del->rowCount();
  return ['rows_deleted' => $rowsDeleted, 'files_removed' => $filesRemoved];
}

/**
 * Whether adding net bytes would exceed quota.
 * @return string|null Error message if denied, null if allowed (including unlimited quota).
 */
function imagekpr_storage_quota_denies_add(PDO $pdo, int $userId, int $netAddBytes): ?string
{
  if ($netAddBytes < 1) {
    return null;
  }
  $rem = imagekpr_storage_quota_remaining($pdo, $userId);
  if ($rem === null) {
    return null;
  }
  if ($netAddBytes <= $rem) {
    return null;
  }
  return 'Storage quota exceeded. Remaining: ' . imagekpr_format_bytes($rem) . '.';
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
