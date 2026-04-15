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

/**
 * Site default quota (bytes) when user row has NULL storage_quota_bytes; null = no default cap (unlimited).
 * If app_settings has the key: use that value only (positive digits). Empty / 0 / invalid there means unlimited,
 * not config.php — so Admin Config is authoritative once saved.
 */
function imagekpr_default_storage_quota_bytes(): ?int
{
  imagekpr_ensure_config();
  if (ImageKprAppSettings::has('default_storage_quota_bytes')) {
    return imagekpr_site_default_quota_from_settings();
  }
  if (!defined('DEFAULT_STORAGE_QUOTA_BYTES')) {
    return null;
  }
  $v = (int) DEFAULT_STORAGE_QUOTA_BYTES;
  return $v > 0 ? $v : null;
}

/**
 * Storage line for main app status hint; uses same byte formatter as admin so caps match Config/Users.
 *
 * @param array{effective_bytes: ?int, unlimited: bool, remaining_bytes: ?int, used_bytes: int} $quotaStatus
 */
function imagekpr_stats_storage_hint_line(int $totalLibraryBytes, array $quotaStatus): string
{
  if (!empty($quotaStatus['unlimited'])) {
    return imagekpr_format_bytes($totalLibraryBytes) . ' in library — no storage cap';
  }
  $eff = $quotaStatus['effective_bytes'] ?? null;
  if ($eff === null || $eff < 1) {
    return imagekpr_format_bytes($totalLibraryBytes) . ' in library — no storage cap';
  }
  $used = max(0, (int) ($quotaStatus['used_bytes'] ?? 0));
  $rem = $quotaStatus['remaining_bytes'];
  $remInt = $rem === null ? max(0, (int) $eff - $used) : max(0, (int) $rem);

  return imagekpr_format_bytes($used)
    . ' / '
    . imagekpr_format_bytes((int) $eff)
    . ' storage ('
    . imagekpr_format_bytes($remInt)
    . ' left)';
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

/** Encode byte counts for JSON so clients never coerce BIGINT via float (always decimal string). */
function imagekpr_json_byte_string(?int $bytes): ?string
{
  if ($bytes === null) {
    return null;
  }

  return (string) $bytes;
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

/** Allowed per-image upload size tiers (MB). Align with Stripe tier matrix (Free 3, Silver 10, Gold 50). */
function imagekpr_allowed_upload_size_tiers_mb(): array
{
  return [3, 10, 50];
}

/**
 * Full product tier matrix for admin/marketing alignment (Stripe plan doc). Not applied automatically.
 *
 * @return array<string, array<string, mixed>>
 */
function imagekpr_plan_tier_matrix_reference(): array
{
  return [
    'free' => [
      'label' => 'Free',
      'upload_mb' => 3,
      'storage_bytes' => 52428800,
      'max_images' => 100,
      'shared_dashboard_cap' => 20,
    ],
    'silver' => [
      'label' => 'Silver',
      'upload_mb' => 10,
      'storage_bytes' => 209715200,
      'max_images' => 200,
      'shared_dashboard_cap' => 40,
    ],
    'gold' => [
      'label' => 'Gold',
      'upload_mb' => 50,
      'storage_bytes' => 1048576000,
      'max_images' => 1000,
      'shared_dashboard_cap' => 200,
    ],
    'pro' => [
      'label' => 'Pro',
      'storage_bytes' => 20971520000,
      'commercial_note' => 'S$999 list per 3-year license; renewal at end of term',
    ],
  ];
}

/**
 * SaaS tiers only — storage + upload for quick lookups.
 *
 * @return array<string, array{label:string, bytes:int, upload_mb:int}>
 */
function imagekpr_plan_tier_storage_reference(): array
{
  $m = imagekpr_plan_tier_matrix_reference();
  $out = [];
  foreach (['free', 'silver', 'gold'] as $k) {
    $r = $m[$k];
    $out[$k] = ['label' => $r['label'], 'bytes' => $r['storage_bytes'], 'upload_mb' => $r['upload_mb']];
  }
  return $out;
}

/**
 * Match effective storage cap + upload tier to a SaaS preset (exact bytes and MB).
 *
 * @return string|null Preset key free|silver|gold, 'custom' if capped but no match, null if unlimited.
 */
function imagekpr_infer_saas_tier_preset_match(?int $storageQuotaColumn, int $uploadMb): ?string
{
  $uploadMb = imagekpr_normalize_upload_size_mb($uploadMb);
  $dbq = $storageQuotaColumn;
  $eff = imagekpr_effective_quota_bytes($dbq === null ? null : (int) $dbq);
  if ($eff === null) {
    return null;
  }
  foreach (imagekpr_plan_tier_storage_reference() as $key => $t) {
    if ((int) $t['bytes'] === $eff && (int) $t['upload_mb'] === $uploadMb) {
      return (string) $key;
    }
  }
  return 'custom';
}

/**
 * Storage quota status for the signed-in API user (same used-bytes basis as upload/inbox enforcement).
 *
 * @return array{effective_bytes: ?int, unlimited: bool, remaining_bytes: ?int, used_bytes: int}
 */
function imagekpr_user_storage_quota_status(PDO $pdo, int $userId): array
{
  if ($userId < 1) {
    return ['effective_bytes' => null, 'unlimited' => true, 'remaining_bytes' => null, 'used_bytes' => 0];
  }
  $st = $pdo->prepare('SELECT storage_quota_bytes FROM users WHERE id = ? LIMIT 1');
  $st->execute([$userId]);
  $row = $st->fetch(PDO::FETCH_ASSOC);
  if ($row === false) {
    return ['effective_bytes' => null, 'unlimited' => true, 'remaining_bytes' => null, 'used_bytes' => 0];
  }
  $dbq = $row['storage_quota_bytes'];
  $dbq = $dbq === null ? null : (int) $dbq;
  $eff = imagekpr_effective_quota_bytes($dbq);
  $unlimited = $eff === null;
  $used = imagekpr_user_storage_used($pdo, $userId);
  $remaining = $unlimited ? null : max(0, $eff - $used);

  return [
    'effective_bytes' => $eff,
    'unlimited' => $unlimited,
    'remaining_bytes' => $remaining,
    'used_bytes' => $used,
  ];
}

/** HTML fragment: Free/Silver/Gold line for admin help text. */
function imagekpr_admin_html_plan_matrix_saas_blurb(): string
{
  $m = imagekpr_plan_tier_matrix_reference();
  $chunks = [];
  foreach (['free', 'silver', 'gold'] as $k) {
    $r = $m[$k];
    $chunks[] =
      '<strong>' . htmlspecialchars((string) $r['label'], ENT_QUOTES, 'UTF-8') . '</strong>: '
      . (int) $r['upload_mb'] . 'MB/file · '
      . htmlspecialchars(imagekpr_format_bytes((int) $r['storage_bytes']), ENT_QUOTES, 'UTF-8')
      . ' (<span class="admin-mono">' . (int) $r['storage_bytes'] . '</span> B) · max '
      . (int) $r['max_images'] . ' images · shared dashboard max '
      . (int) $r['shared_dashboard_cap'] . ' images/dashboard';
  }
  return implode('; ', $chunks);
}

/** HTML fragment: Pro (dedicated) line for admin help text. */
function imagekpr_admin_html_plan_matrix_pro_blurb(): string
{
  $p = imagekpr_plan_tier_matrix_reference()['pro'];
  $b = (int) $p['storage_bytes'];
  $note = htmlspecialchars((string) $p['commercial_note'], ENT_QUOTES, 'UTF-8');
  return '<strong>' . htmlspecialchars((string) $p['label'], ENT_QUOTES, 'UTF-8') . '</strong> (dedicated white-label, maximum features): '
    . 'not a seat on this shared multi-tenant app. Reference storage '
    . htmlspecialchars(imagekpr_format_bytes($b), ENT_QUOTES, 'UTF-8')
    . ' (<span class="admin-mono">' . $b . '</span> B); unlimited library images &amp; shared-dashboard selection on the dedicated instance. '
    . $note . ' (Stripe: one-time or invoice per contract).';
}

/** Normalize requested tier to a safe supported value (defaults to 3MB). */
function imagekpr_normalize_upload_size_mb($raw): int
{
  $v = (int) $raw;
  if ($v === 100) {
    $v = 50;
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

/** Phrase admin must type exactly (after trim) to confirm bulk user deletion; comparison is case-insensitive. */
function imagekpr_admin_delete_users_confirm_phrase(): string
{
  return 'DELETE USER ACCOUNTS';
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
 * Delete selected user accounts and their data.
 * - Skips protected user ids (e.g. current actor)
 * - Skips admin accounts
 * - Purges gallery image rows/files and folder rows before deleting users
 * - Removes matching emails from allowlist and access requests
 *
 * @param int[] $userIds
 * @param int[] $protectedUserIds
 * @return array{
 *   users_deleted:int,
 *   deleted_user_ids:int[],
 *   skipped_admin_ids:int[],
 *   skipped_protected_ids:int[],
 *   images_deleted:int,
 *   image_files_removed:int,
 *   folders_deleted:int,
 *   folder_links_deleted:int,
 *   allowlist_deleted:int,
 *   access_requests_deleted:int
 * }
 */
function imagekpr_admin_delete_users(PDO $pdo, array $userIds, array $protectedUserIds = []): array
{
  $userIds = array_values(array_unique(array_filter(array_map('intval', $userIds), static fn ($x) => $x > 0)));
  $protected = array_values(array_unique(array_filter(array_map('intval', $protectedUserIds), static fn ($x) => $x > 0)));
  $result = [
    'users_deleted' => 0,
    'deleted_user_ids' => [],
    'skipped_admin_ids' => [],
    'skipped_protected_ids' => [],
    'images_deleted' => 0,
    'image_files_removed' => 0,
    'folders_deleted' => 0,
    'folder_links_deleted' => 0,
    'allowlist_deleted' => 0,
    'access_requests_deleted' => 0,
  ];
  if ($userIds === []) {
    return $result;
  }

  $placeholders = implode(',', array_fill(0, count($userIds), '?'));
  $st = $pdo->prepare("SELECT id, email, is_admin FROM users WHERE id IN ($placeholders)");
  $st->execute($userIds);
  $rows = $st->fetchAll(PDO::FETCH_ASSOC);
  if ($rows === []) {
    return $result;
  }

  $protectedMap = array_fill_keys($protected, true);
  $targetIds = [];
  $targetEmails = [];
  foreach ($rows as $row) {
    $uid = (int) ($row['id'] ?? 0);
    if ($uid < 1) {
      continue;
    }
    if (isset($protectedMap[$uid])) {
      $result['skipped_protected_ids'][] = $uid;
      continue;
    }
    if ((int) ($row['is_admin'] ?? 0) === 1) {
      $result['skipped_admin_ids'][] = $uid;
      continue;
    }
    $targetIds[] = $uid;
    $targetEmails[] = strtolower(trim((string) ($row['email'] ?? '')));
  }
  $targetIds = array_values(array_unique($targetIds));
  $targetEmails = array_values(array_unique(array_filter($targetEmails, static fn ($x) => $x !== '')));
  $result['skipped_admin_ids'] = array_values(array_unique($result['skipped_admin_ids']));
  $result['skipped_protected_ids'] = array_values(array_unique($result['skipped_protected_ids']));

  if ($targetIds === []) {
    return $result;
  }

  $pdo->beginTransaction();
  try {
    $purge = imagekpr_admin_purge_gallery_for_users($pdo, $targetIds);
    $result['images_deleted'] = (int) ($purge['rows_deleted'] ?? 0);
    $result['image_files_removed'] = (int) ($purge['files_removed'] ?? 0);

    $phUsers = implode(',', array_fill(0, count($targetIds), '?'));
    $delFolderLinks = $pdo->prepare("DELETE fi FROM folder_images fi INNER JOIN folders f ON f.id = fi.folder_id WHERE f.user_id IN ($phUsers)");
    $delFolderLinks->execute($targetIds);
    $result['folder_links_deleted'] = (int) $delFolderLinks->rowCount();

    $delFolders = $pdo->prepare("DELETE FROM folders WHERE user_id IN ($phUsers)");
    $delFolders->execute($targetIds);
    $result['folders_deleted'] = (int) $delFolders->rowCount();

    if ($targetEmails !== []) {
      $phEmails = implode(',', array_fill(0, count($targetEmails), '?'));
      $delAllow = $pdo->prepare("DELETE FROM email_allowlist WHERE LOWER(email) IN ($phEmails)");
      $delAllow->execute($targetEmails);
      $result['allowlist_deleted'] = (int) $delAllow->rowCount();

      $delReq = $pdo->prepare("DELETE FROM email_access_requests WHERE LOWER(email) IN ($phEmails)");
      $delReq->execute($targetEmails);
      $result['access_requests_deleted'] = (int) $delReq->rowCount();
    }

    $delUsers = $pdo->prepare("DELETE FROM users WHERE id IN ($phUsers)");
    $delUsers->execute($targetIds);
    $result['users_deleted'] = (int) $delUsers->rowCount();
    $result['deleted_user_ids'] = $targetIds;

    $pdo->commit();
  } catch (Throwable $e) {
    if ($pdo->inTransaction()) {
      $pdo->rollBack();
    }
    throw $e;
  }

  return $result;
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
