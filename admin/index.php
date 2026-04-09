<?php
ob_start();
require_once __DIR__ . '/../inc/admin.php';
imagekpr_require_admin_html(1, 1);

$pdo = imagekpr_pdo();
$actorId = imagekpr_user_id();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'set_quota') {
  if (!imagekpr_csrf_verify()) {
    $_SESSION['admin_flash'] = ['type' => 'error', 'msg' => 'Security token invalid. Try again.'];
    $redir = 'index.php';
    $q = $_GET;
    if (!empty($q)) {
      $redir .= '?' . http_build_query($q);
    }
    header('Location: ' . $redir, true, 303);
    exit;
  }
  $targetId = (int) ($_POST['user_id'] ?? 0);
  $mode = (string) ($_POST['quota_mode'] ?? '');
  if ($targetId < 1) {
    $_SESSION['admin_flash'] = ['type' => 'error', 'msg' => 'Invalid user.'];
    header('Location: index.php', true, 303);
    exit;
  }
  $stOld = $pdo->prepare('SELECT id, email, storage_quota_bytes FROM users WHERE id = ? LIMIT 1');
  $stOld->execute([$targetId]);
  $oldRow = $stOld->fetch(PDO::FETCH_ASSOC);
  if ($oldRow === false) {
    $_SESSION['admin_flash'] = ['type' => 'error', 'msg' => 'User not found.'];
    header('Location: index.php', true, 303);
    exit;
  }
  $oldQuota = $oldRow['storage_quota_bytes'];
  $oldQuota = $oldQuota === null ? null : (int) $oldQuota;

  $newQuota = null;
  if ($mode === 'default') {
    $newQuota = null;
  } elseif ($mode === 'unlimited') {
    $newQuota = 0;
  } elseif ($mode === 'custom') {
    $gb = isset($_POST['quota_gb']) ? (float) str_replace(',', '.', (string) $_POST['quota_gb']) : 0;
    if ($gb <= 0 || !is_finite($gb)) {
      $_SESSION['admin_flash'] = ['type' => 'error', 'msg' => 'Custom quota must be a positive number (GB).'];
      header('Location: index.php', true, 303);
      exit;
    }
    $bytes = (int) round($gb * 1024 * 1024 * 1024);
    if ($bytes < 1) {
      $_SESSION['admin_flash'] = ['type' => 'error', 'msg' => 'Quota too small after conversion.'];
      header('Location: index.php', true, 303);
      exit;
    }
    $newQuota = $bytes;
  } else {
    $_SESSION['admin_flash'] = ['type' => 'error', 'msg' => 'Invalid quota mode.'];
    header('Location: index.php', true, 303);
    exit;
  }

  $up = $pdo->prepare('UPDATE users SET storage_quota_bytes = ? WHERE id = ?');
  $up->execute([$newQuota, $targetId]);
  imagekpr_admin_audit_log($pdo, $actorId, 'user_quota_set', [
    'target_user_id' => $targetId,
    'target_email' => $oldRow['email'],
    'old_storage_quota_bytes' => $oldQuota,
    'new_storage_quota_bytes' => $newQuota,
  ]);
  $_SESSION['admin_flash'] = ['type' => 'ok', 'msg' => 'Quota updated for ' . $oldRow['email'] . '.'];
  $redir = 'index.php';
  $q = $_GET;
  if (!empty($q)) {
    $redir .= '?' . http_build_query($q);
  }
  header('Location: ' . $redir, true, 303);
  exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'bulk_set_quota') {
  if (!imagekpr_csrf_verify()) {
    $_SESSION['admin_flash'] = ['type' => 'error', 'msg' => 'Security token invalid. Try again.'];
    $redir = 'index.php';
    $q = $_GET;
    if (!empty($q)) {
      $redir .= '?' . http_build_query($q);
    }
    header('Location: ' . $redir, true, 303);
    exit;
  }
  $rawIds = $_POST['bulk_user_ids'] ?? [];
  if (!is_array($rawIds)) {
    $rawIds = [];
  }
  $ids = [];
  foreach ($rawIds as $v) {
    $ids[] = (int) $v;
  }
  $ids = array_values(array_unique(array_filter($ids, static fn ($x) => $x > 0)));
  if ($ids === []) {
    $_SESSION['admin_flash'] = ['type' => 'error', 'msg' => 'Select at least one user.'];
    $redir = 'index.php';
    $q = $_GET;
    if (!empty($q)) {
      $redir .= '?' . http_build_query($q);
    }
    header('Location: ' . $redir, true, 303);
    exit;
  }

  $mode = (string) ($_POST['bulk_quota_mode'] ?? '');
  $newQuota = null;
  if ($mode === 'default') {
    $newQuota = null;
  } elseif ($mode === 'unlimited') {
    $newQuota = 0;
  } elseif ($mode === 'custom') {
    $gb = isset($_POST['bulk_quota_gb']) ? (float) str_replace(',', '.', (string) $_POST['bulk_quota_gb']) : 0;
    if ($gb <= 0 || !is_finite($gb)) {
      $_SESSION['admin_flash'] = ['type' => 'error', 'msg' => 'Bulk custom quota must be a positive number (GB).'];
      $redir = 'index.php';
      $q = $_GET;
      if (!empty($q)) {
        $redir .= '?' . http_build_query($q);
      }
      header('Location: ' . $redir, true, 303);
      exit;
    }
    $bytes = (int) round($gb * 1024 * 1024 * 1024);
    if ($bytes < 1) {
      $_SESSION['admin_flash'] = ['type' => 'error', 'msg' => 'Bulk quota too small after conversion.'];
      $redir = 'index.php';
      $q = $_GET;
      if (!empty($q)) {
        $redir .= '?' . http_build_query($q);
      }
      header('Location: ' . $redir, true, 303);
      exit;
    }
    $newQuota = $bytes;
  } else {
    $_SESSION['admin_flash'] = ['type' => 'error', 'msg' => 'Invalid bulk quota mode.'];
    $redir = 'index.php';
    $q = $_GET;
    if (!empty($q)) {
      $redir .= '?' . http_build_query($q);
    }
    header('Location: ' . $redir, true, 303);
    exit;
  }

  $up = $pdo->prepare('UPDATE users SET storage_quota_bytes = ? WHERE id = ?');
  foreach ($ids as $tid) {
    $up->execute([$newQuota, $tid]);
  }
  imagekpr_admin_audit_log($pdo, $actorId, 'bulk_user_quota_set', [
    'target_user_ids' => $ids,
    'new_storage_quota_bytes' => $newQuota,
  ]);
  $_SESSION['admin_flash'] = ['type' => 'ok', 'msg' => 'Quota updated for ' . count($ids) . ' user(s).'];
  $redir = 'index.php';
  $q = $_GET;
  if (!empty($q)) {
    $redir .= '?' . http_build_query($q);
  }
  header('Location: ' . $redir, true, 303);
  exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'set_upload_tier') {
  if (!imagekpr_csrf_verify()) {
    $_SESSION['admin_flash'] = ['type' => 'error', 'msg' => 'Security token invalid. Try again.'];
    $redir = 'index.php';
    $q = $_GET;
    if (!empty($q)) {
      $redir .= '?' . http_build_query($q);
    }
    header('Location: ' . $redir, true, 303);
    exit;
  }
  $targetId = (int) ($_POST['user_id'] ?? 0);
  if ($targetId < 1) {
    $_SESSION['admin_flash'] = ['type' => 'error', 'msg' => 'Invalid user.'];
    header('Location: index.php', true, 303);
    exit;
  }
  $newMb = imagekpr_normalize_upload_size_mb($_POST['upload_size_mb'] ?? 3);
  $stOld = $pdo->prepare('SELECT id, email, upload_size_mb, upload_tier_downgraded_at FROM users WHERE id = ? LIMIT 1');
  $stOld->execute([$targetId]);
  $oldRow = $stOld->fetch(PDO::FETCH_ASSOC);
  if ($oldRow === false) {
    $_SESSION['admin_flash'] = ['type' => 'error', 'msg' => 'User not found.'];
    header('Location: index.php', true, 303);
    exit;
  }
  $oldMb = imagekpr_normalize_upload_size_mb($oldRow['upload_size_mb'] ?? 3);
  $oldDown = isset($oldRow['upload_tier_downgraded_at']) ? (string) $oldRow['upload_tier_downgraded_at'] : null;
  if ($oldDown !== null && trim($oldDown) === '') {
    $oldDown = null;
  }
  $newDown = $oldDown;
  if ($newMb < $oldMb) {
    $newDown = date('Y-m-d H:i:s');
  } elseif ($newMb > $oldMb) {
    $newDown = null;
  }
  $up = $pdo->prepare('UPDATE users SET upload_size_mb = ?, upload_tier_downgraded_at = ? WHERE id = ?');
  $up->execute([$newMb, $newDown, $targetId]);
  imagekpr_admin_audit_log($pdo, $actorId, 'user_upload_tier_set', [
    'target_user_id' => $targetId,
    'target_email' => $oldRow['email'],
    'old_upload_size_mb' => $oldMb,
    'new_upload_size_mb' => $newMb,
    'old_upload_tier_downgraded_at' => $oldDown,
    'new_upload_tier_downgraded_at' => $newDown,
  ]);
  $suffix = ($newMb < $oldMb) ? ' Grace starts now (30 days).' : '';
  $_SESSION['admin_flash'] = ['type' => 'ok', 'msg' => 'Upload tier updated for ' . $oldRow['email'] . ' to ' . $newMb . 'MB.' . $suffix];
  $redir = 'index.php';
  $q = $_GET;
  if (!empty($q)) {
    $redir .= '?' . http_build_query($q);
  }
  header('Location: ' . $redir, true, 303);
  exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'bulk_set_upload_tier') {
  if (!imagekpr_csrf_verify()) {
    $_SESSION['admin_flash'] = ['type' => 'error', 'msg' => 'Security token invalid. Try again.'];
    $redir = 'index.php';
    $q = $_GET;
    if (!empty($q)) {
      $redir .= '?' . http_build_query($q);
    }
    header('Location: ' . $redir, true, 303);
    exit;
  }
  $rawIds = $_POST['bulk_user_ids'] ?? [];
  if (!is_array($rawIds)) {
    $rawIds = [];
  }
  $ids = [];
  foreach ($rawIds as $v) {
    $ids[] = (int) $v;
  }
  $ids = array_values(array_unique(array_filter($ids, static fn ($x) => $x > 0)));
  if ($ids === []) {
    $_SESSION['admin_flash'] = ['type' => 'error', 'msg' => 'Select at least one user.'];
    $redir = 'index.php';
    $q = $_GET;
    if (!empty($q)) {
      $redir .= '?' . http_build_query($q);
    }
    header('Location: ' . $redir, true, 303);
    exit;
  }
  $newMb = imagekpr_normalize_upload_size_mb($_POST['bulk_upload_size_mb'] ?? 3);
  $st = $pdo->prepare('UPDATE users SET upload_size_mb = ?, upload_tier_downgraded_at = CASE WHEN upload_size_mb > ? THEN NOW() WHEN upload_size_mb < ? THEN NULL ELSE upload_tier_downgraded_at END WHERE id = ?');
  foreach ($ids as $tid) {
    $st->execute([$newMb, $newMb, $newMb, $tid]);
  }
  imagekpr_admin_audit_log($pdo, $actorId, 'bulk_user_upload_tier_set', [
    'target_user_ids' => $ids,
    'new_upload_size_mb' => $newMb,
  ]);
  $_SESSION['admin_flash'] = ['type' => 'ok', 'msg' => 'Upload tier set to ' . $newMb . 'MB for ' . count($ids) . ' user(s).'];
  $redir = 'index.php';
  $q = $_GET;
  if (!empty($q)) {
    $redir .= '?' . http_build_query($q);
  }
  header('Location: ' . $redir, true, 303);
  exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'bulk_purge_gallery') {
  if (!imagekpr_csrf_verify()) {
    $_SESSION['admin_flash'] = ['type' => 'error', 'msg' => 'Security token invalid. Try again.'];
    $redir = 'index.php';
    $q = $_GET;
    if (!empty($q)) {
      $redir .= '?' . http_build_query($q);
    }
    header('Location: ' . $redir, true, 303);
    exit;
  }
  $phrase = trim((string) ($_POST['purge_confirm'] ?? ''));
  if (strcasecmp($phrase, imagekpr_admin_purge_confirm_phrase()) !== 0) {
    $_SESSION['admin_flash'] = ['type' => 'error', 'msg' => 'Confirmation phrase does not match. No images were deleted.'];
    $redir = 'index.php';
    $q = $_GET;
    if (!empty($q)) {
      $redir .= '?' . http_build_query($q);
    }
    header('Location: ' . $redir, true, 303);
    exit;
  }
  $rawIds = $_POST['bulk_user_ids'] ?? [];
  if (!is_array($rawIds)) {
    $rawIds = [];
  }
  $ids = [];
  foreach ($rawIds as $v) {
    $ids[] = (int) $v;
  }
  $ids = array_values(array_unique(array_filter($ids, static fn ($x) => $x > 0)));
  if ($ids === []) {
    $_SESSION['admin_flash'] = ['type' => 'error', 'msg' => 'Select at least one user to purge.'];
    $redir = 'index.php';
    $q = $_GET;
    if (!empty($q)) {
      $redir .= '?' . http_build_query($q);
    }
    header('Location: ' . $redir, true, 303);
    exit;
  }

  $result = imagekpr_admin_purge_gallery_for_users($pdo, $ids);
  imagekpr_admin_audit_log($pdo, $actorId, 'bulk_gallery_purge', [
    'target_user_ids' => $ids,
    'rows_deleted' => $result['rows_deleted'],
    'files_removed' => $result['files_removed'],
  ]);
  $_SESSION['admin_flash'] = [
    'type' => 'ok',
    'msg' => 'Gallery purged for ' . count($ids) . ' user(s): ' . $result['rows_deleted'] . ' image row(s) removed, ' . $result['files_removed'] . ' file(s) deleted from disk.',
  ];
  $redir = 'index.php';
  $q = $_GET;
  if (!empty($q)) {
    $redir .= '?' . http_build_query($q);
  }
  header('Location: ' . $redir, true, 303);
  exit;
}

$flash = $_SESSION['admin_flash'] ?? null;
unset($_SESSION['admin_flash']);

$qSearch = trim((string) ($_GET['q'] ?? ''));
$sort = (string) ($_GET['sort'] ?? 'email');
$dir = strtolower((string) ($_GET['dir'] ?? 'asc')) === 'desc' ? 'DESC' : 'ASC';
$sortMap = [
  'email' => 'u.email',
  'name' => 'u.name',
  'used' => 'used_bytes',
  'last' => 'u.last_login_at',
  'admin' => 'u.is_admin',
  'quo' => 'u.storage_quota_bytes',
  'upl' => 'u.upload_size_mb',
];
if (!isset($sortMap[$sort])) {
  $sort = 'email';
}
$orderCol = $sortMap[$sort];

$params = [];
$whereSql = '';
if ($qSearch !== '') {
  $whereSql = ' WHERE (u.email LIKE ? OR u.name LIKE ?) ';
  $like = '%' . $qSearch . '%';
  $params[] = $like;
  $params[] = $like;
}

$sql = 'SELECT u.id, u.email, u.name, u.is_admin, u.created_at, u.last_login_at, u.storage_quota_bytes, u.upload_size_mb, u.upload_tier_downgraded_at,
  COALESCE(SUM(i.size_bytes), 0) AS used_bytes
  FROM users u
  LEFT JOIN images i ON i.user_id = u.id'
  . $whereSql .
  ' GROUP BY u.id, u.email, u.name, u.is_admin, u.created_at, u.last_login_at, u.storage_quota_bytes, u.upload_size_mb, u.upload_tier_downgraded_at
  ORDER BY ' . $orderCol . ' ' . $dir . ', u.id ASC';

$st = $pdo->prepare($sql);
$st->execute($params);
$rows = $st->fetchAll(PDO::FETCH_ASSOC);

$totalStorage = (int) $pdo->query('SELECT COALESCE(SUM(size_bytes), 0) FROM images')->fetchColumn();
$userCount = (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();

$sqlAll = 'SELECT u.id, u.email, u.name, u.is_admin, u.created_at, u.last_login_at, u.storage_quota_bytes, u.upload_size_mb, u.upload_tier_downgraded_at,
  COALESCE(SUM(i.size_bytes), 0) AS used_bytes
  FROM users u
  LEFT JOIN images i ON i.user_id = u.id
  GROUP BY u.id, u.email, u.name, u.is_admin, u.created_at, u.last_login_at, u.storage_quota_bytes, u.upload_size_mb, u.upload_tier_downgraded_at';
$allRows = $pdo->query($sqlAll)->fetchAll(PDO::FETCH_ASSOC);

$overQuota = 0;
$expiredGraceUsers = 0;
foreach ($allRows as $r) {
  $used = (int) $r['used_bytes'];
  $dbq = $r['storage_quota_bytes'];
  $dbq = $dbq === null ? null : (int) $dbq;
  $eff = imagekpr_effective_quota_bytes($dbq);
  if ($eff !== null && $used > $eff) {
    $overQuota++;
  }
  if (imagekpr_upload_tier_grace_expired(isset($r['upload_tier_downgraded_at']) ? (string) $r['upload_tier_downgraded_at'] : null)) {
    $expiredGraceUsers++;
  }
}
$byUsed = $allRows;
usort($byUsed, static function ($a, $b) {
  return (int) $b['used_bytes'] <=> (int) $a['used_bytes'];
});
$topUsers = array_slice($byUsed, 0, 5);

$pageTitle = 'Admin — Dashboard';
$adminNavCurrent = 'dashboard';

function admin_sort_link(string $col, string $label, string $currentSort, string $currentDir, string $q): string
{
  $nextDir = 'asc';
  if ($currentSort === $col && $currentDir === 'ASC') {
    $nextDir = 'desc';
  }
  $qs = ['sort' => $col, 'dir' => $nextDir];
  if ($q !== '') {
    $qs['q'] = $q;
  }
  $arrow = '';
  if ($currentSort === $col) {
    $arrow = $currentDir === 'ASC' ? ' ▲' : ' ▼';
  }
  return '<a href="index.php?' . htmlspecialchars(http_build_query($qs), ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . $arrow . '</a>';
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8'); ?></title>
  <link rel="stylesheet" href="../styles.css">
  <style>
    .admin-wrap { max-width: min(1240px, calc(100vw - 1.5rem)); margin: 0 auto; padding: 1rem 0.75rem 2rem; box-sizing: border-box; }
    .admin-nav { display: flex; gap: 1rem; align-items: center; flex-wrap: wrap; padding: 0.75rem 0; border-bottom: 1px solid #ddd; margin-bottom: 1.25rem; }
    .admin-nav a { color: #1565c0; text-decoration: none; font-weight: 600; }
    .admin-nav a:hover { text-decoration: underline; }
    .admin-nav .admin-nav-spacer { flex: 1; min-width: 0; }
    .admin-muted { color: #666; font-size: 0.9rem; }
    .admin-badge { display: inline-block; padding: 0.2rem 0.5rem; background: #e3f2fd; border-radius: 4px; font-size: 0.75rem; color: #1565c0; }
    .admin-stats { display: grid; grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); gap: 0.75rem; margin: 1rem 0 1.5rem; }
    .admin-stat { background: #fafafa; border: 1px solid #eee; border-radius: 6px; padding: 0.75rem 1rem; }
    .admin-stat dt { font-size: 0.75rem; color: #666; margin: 0; text-transform: uppercase; letter-spacing: 0.03em; }
    .admin-stat dd { margin: 0.35rem 0 0; font-size: 1.15rem; font-weight: 600; color: #333; }
    .admin-top-list { margin: 0; padding-left: 1.1rem; font-size: 0.85rem; color: #444; }
    .admin-toast { padding: 0.65rem 1rem; border-radius: 6px; margin-bottom: 1rem; font-size: 0.9rem; }
    .admin-toast.ok { background: #e8f5e9; border: 1px solid #a5d6a7; color: #1b5e20; }
    .admin-toast.err { background: #ffebee; border: 1px solid #ef9a9a; color: #b71c1c; }
    .admin-search { margin: 0.5rem 0 1rem; display: flex; gap: 0.5rem; flex-wrap: wrap; align-items: center; }
    .admin-search input[type="search"] { padding: 0.35rem 0.5rem; min-width: 200px; }
    .admin-search button { padding: 0.35rem 0.75rem; cursor: pointer; }
    .admin-table-wrap { overflow-x: auto; border: 1px solid #eee; border-radius: 6px; -webkit-overflow-scrolling: touch; }
    table.admin-users { width: 100%; border-collapse: collapse; font-size: 0.8rem; table-layout: fixed; }
    table.admin-users th, table.admin-users td { padding: 0.4rem 0.45rem; text-align: left; border-bottom: 1px solid #eee; vertical-align: top; }
    table.admin-users th { background: #f5f5f5; font-weight: 600; white-space: normal; line-height: 1.25; hyphens: auto; }
    table.admin-users .admin-th-sub { font-weight: 500; color: #666; font-size: 0.72rem; }
    table.admin-users th a { color: #1565c0; text-decoration: none; }
    table.admin-users th a:hover { text-decoration: underline; }
    table.admin-users tr:last-child td { border-bottom: none; }
    table.admin-users .admin-col-cb { width: 2.25rem; box-sizing: border-box; }
    table.admin-users .admin-col-email { width: 20%; max-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    table.admin-users .admin-col-name { width: 11%; max-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    table.admin-users .admin-col-used { width: 6.5%; white-space: nowrap; text-align: right; font-variant-numeric: tabular-nums; }
    table.admin-users .admin-col-qtxt { width: 12%; word-break: break-word; hyphens: auto; line-height: 1.3; font-size: 0.78rem; }
    table.admin-users .admin-col-login { width: 10%; white-space: nowrap; font-variant-numeric: tabular-nums; font-size: 0.78rem; }
    table.admin-users .admin-col-days { width: 4rem; white-space: nowrap; text-align: right; font-variant-numeric: tabular-nums; }
    table.admin-users .admin-col-admin { width: 2.5rem; white-space: nowrap; text-align: center; }
    table.admin-users .admin-col-quota { width: 11rem; min-width: 8.5rem; }
    .admin-over { color: #c62828; font-weight: 600; }
    .admin-quota-form { display: flex; flex-direction: column; gap: 0.25rem; min-width: 0; }
    .admin-quota-form label { display: flex; align-items: center; gap: 0.25rem; font-size: 0.72rem; flex-wrap: nowrap; }
    .admin-quota-form input[type="number"] { width: 3.5rem; min-width: 0; padding: 0.15rem 0.25rem; font-size: 0.75rem; }
    .admin-quota-form button { padding: 0.2rem 0.45rem; font-size: 0.72rem; cursor: pointer; margin-top: 0.15rem; align-self: flex-start; }
    .admin-mono { font-family: ui-monospace, monospace; font-size: 0.8rem; }
    .admin-bulk { background: #e3f2fd; border: 1px solid #90caf9; border-radius: 6px; padding: 0.75rem 1rem; margin: 0 0 1rem; display: flex; flex-wrap: wrap; gap: 0.75rem; align-items: flex-end; }
    .admin-bulk fieldset { border: none; margin: 0; padding: 0; }
    .admin-bulk legend { font-weight: 600; font-size: 0.85rem; margin-bottom: 0.35rem; }
    .admin-bulk label { display: inline-flex; align-items: center; gap: 0.35rem; margin-right: 0.75rem; font-size: 0.8rem; }
    .admin-bulk input[type="number"] { width: 5rem; padding: 0.2rem 0.35rem; }
    .admin-bulk button { padding: 0.35rem 0.75rem; cursor: pointer; font-weight: 600; }
    .admin-bulk-actions { flex-direction: column; align-items: stretch; }
    .admin-bulk-actions .admin-bulk-row { display: flex; flex-wrap: wrap; gap: 0.75rem; align-items: flex-end; }
    .admin-bulk-purge { margin-top: 0.5rem; padding-top: 0.75rem; border-top: 1px solid #ef9a9a; }
    .admin-bulk-purge legend { color: #b71c1c; }
    .admin-bulk-purge .admin-muted { max-width: 42rem; margin: 0 0 0.5rem; }
    .admin-bulk-purge label { display: flex; flex-wrap: wrap; align-items: center; gap: 0.5rem; font-size: 0.85rem; margin-bottom: 0.5rem; }
    .admin-bulk-purge input[type="text"] { min-width: 14rem; padding: 0.35rem 0.5rem; font-family: ui-monospace, monospace; }
    .admin-btn-danger { background: #c62828; color: #fff; border: 1px solid #8e0000; }
    .admin-btn-danger:hover { background: #b71c1c; }
  </style>
</head>
<body>
  <div class="admin-wrap">
    <?php require __DIR__ . '/_nav.php'; ?>

    <h1>Dashboard</h1>

    <?php if (is_array($flash) && !empty($flash['msg'])) { ?>
      <div class="admin-toast <?php echo ($flash['type'] ?? '') === 'error' ? 'err' : 'ok'; ?>" role="alert">
        <?php echo htmlspecialchars((string) $flash['msg'], ENT_QUOTES, 'UTF-8'); ?>
      </div>
    <?php } ?>

    <dl class="admin-stats">
      <div class="admin-stat">
        <dt>Total storage (images)</dt>
        <dd><?php echo htmlspecialchars(imagekpr_format_bytes($totalStorage), ENT_QUOTES, 'UTF-8'); ?></dd>
      </div>
      <div class="admin-stat">
        <dt>Users</dt>
        <dd><?php echo (int) $userCount; ?></dd>
      </div>
      <div class="admin-stat">
        <dt>Over quota</dt>
        <dd><?php echo $overQuota > 0 ? '<span class="admin-over">' . (int) $overQuota . '</span>' : (int) $overQuota; ?></dd>
      </div>
      <div class="admin-stat">
        <dt>Upload grace expired</dt>
        <dd><?php echo $expiredGraceUsers > 0 ? '<span class="admin-over">' . (int) $expiredGraceUsers . '</span>' : (int) $expiredGraceUsers; ?></dd>
      </div>
      <div class="admin-stat">
        <dt>Largest users</dt>
        <dd>
          <?php if (empty($topUsers)) { ?>
            <span class="admin-muted">—</span>
          <?php } else { ?>
            <ul class="admin-top-list">
              <?php foreach ($topUsers as $tu) { ?>
                <li><?php echo htmlspecialchars((string) $tu['email'], ENT_QUOTES, 'UTF-8'); ?> — <?php echo htmlspecialchars(imagekpr_format_bytes((int) $tu['used_bytes']), ENT_QUOTES, 'UTF-8'); ?></li>
              <?php } ?>
            </ul>
          <?php } ?>
        </dd>
      </div>
    </dl>

    <p class="admin-muted">Default quota for users with no per-user cap: <?php
      $d = imagekpr_default_storage_quota_bytes();
    echo $d === null ? 'none (unlimited)' : htmlspecialchars(imagekpr_format_bytes($d), ENT_QUOTES, 'UTF-8');
    ?> — set <span class="admin-mono">DEFAULT_STORAGE_QUOTA_BYTES</span> in <span class="admin-mono">config.php</span> if needed.</p>

    <form class="admin-search" method="get" action="index.php">
      <?php if ($sort !== 'email') { ?><input type="hidden" name="sort" value="<?php echo htmlspecialchars($sort, ENT_QUOTES, 'UTF-8'); ?>"><?php } ?>
      <?php if ($dir !== 'ASC') { ?><input type="hidden" name="dir" value="<?php echo htmlspecialchars(strtolower($dir), ENT_QUOTES, 'UTF-8'); ?>"><?php } ?>
      <label>Search <input type="search" name="q" value="<?php echo htmlspecialchars($qSearch, ENT_QUOTES, 'UTF-8'); ?>" placeholder="Email or name"></label>
      <button type="submit">Filter</button>
      <?php
      if ($qSearch !== '') {
        $clearQs = array_filter([
          'sort' => $sort !== 'email' ? $sort : null,
          'dir' => $dir !== 'ASC' ? strtolower($dir) : null,
        ]);
        $clearHref = 'index.php' . (!empty($clearQs) ? '?' . http_build_query($clearQs) : '');
        ?>
      <a href="<?php echo htmlspecialchars($clearHref, ENT_QUOTES, 'UTF-8'); ?>">Clear search</a>
      <?php } ?>
    </form>

    <?php
    $bulkActionQs = array_filter([
      'q' => $qSearch !== '' ? $qSearch : null,
      'sort' => $sort !== 'email' ? $sort : null,
      'dir' => $dir !== 'ASC' ? strtolower($dir) : null,
    ]);
    $bulkAction = 'index.php' . (!empty($bulkActionQs) ? '?' . http_build_query($bulkActionQs) : '');
    ?>
    <?php $purgePhrase = imagekpr_admin_purge_confirm_phrase(); ?>
    <form id="bulkUserForm" class="admin-bulk admin-bulk-actions" method="post" action="<?php echo htmlspecialchars($bulkAction, ENT_QUOTES, 'UTF-8'); ?>">
      <?php echo imagekpr_csrf_field(); ?>
      <div class="admin-bulk-row">
        <fieldset>
          <legend>Bulk quota (selected rows)</legend>
          <label><input type="radio" name="bulk_quota_mode" value="default" checked> Site default</label>
          <label><input type="radio" name="bulk_quota_mode" value="unlimited"> Unlimited</label>
          <label><input type="radio" name="bulk_quota_mode" value="custom"> GB <input type="number" name="bulk_quota_gb" min="0.001" step="any" value="10"></label>
        </fieldset>
        <button type="submit" name="action" value="bulk_set_quota">Apply quota to selected</button>
      </div>
      <div class="admin-bulk-row">
        <fieldset>
          <legend>Bulk upload tier (selected rows)</legend>
          <label><input type="radio" name="bulk_upload_size_mb" value="3" checked> 3MB</label>
          <label><input type="radio" name="bulk_upload_size_mb" value="10"> 10MB</label>
          <label><input type="radio" name="bulk_upload_size_mb" value="100"> 100MB</label>
        </fieldset>
        <button type="submit" name="action" value="bulk_set_upload_tier">Apply upload tier to selected</button>
      </div>
      <fieldset class="admin-bulk-purge">
        <legend>Purge gallery only (destructive)</legend>
        <p class="admin-muted">Deletes <strong>published gallery</strong> database rows and image files for the selected users. Does <strong>not</strong> remove user accounts, the email allowlist, or files in the shared inbox folder.</p>
        <label>Type <kbd><?php echo htmlspecialchars($purgePhrase, ENT_QUOTES, 'UTF-8'); ?></kbd> to confirm:
          <input type="text" name="purge_confirm" autocomplete="off" placeholder="<?php echo htmlspecialchars($purgePhrase, ENT_QUOTES, 'UTF-8'); ?>" aria-label="Type confirmation phrase to purge galleries"></label>
        <button type="submit" name="action" value="bulk_purge_gallery" class="admin-btn-danger">Purge gallery for selected</button>
      </fieldset>
    </form>

    <div class="admin-table-wrap">
      <table class="admin-users">
        <thead>
          <tr>
            <th class="admin-col-cb"><input type="checkbox" id="admin-select-all" title="Select all on this page" aria-label="Select all users on this page"></th>
            <th class="admin-col-email"><?php echo admin_sort_link('email', 'Email', $sort, $dir, $qSearch); ?></th>
            <th class="admin-col-name"><?php echo admin_sort_link('name', 'Name', $sort, $dir, $qSearch); ?></th>
            <th class="admin-col-used"><?php echo admin_sort_link('used', 'Used', $sort, $dir, $qSearch); ?></th>
            <th class="admin-col-qtxt"><?php echo admin_sort_link('quo', 'Quota', $sort, $dir, $qSearch); ?></th>
            <th class="admin-col-login"><?php echo admin_sort_link('last', 'Last login', $sort, $dir, $qSearch); ?></th>
            <th class="admin-col-days" title="Whole days since first successful sign-in (account created)">1st access<br><span class="admin-th-sub">days ago</span></th>
            <th class="admin-col-days" title="Whole days since last sign-in (— if never)">Last access<br><span class="admin-th-sub">days ago</span></th>
            <th class="admin-col-admin"><?php echo admin_sort_link('admin', 'Admin', $sort, $dir, $qSearch); ?></th>
            <th class="admin-col-quota">Set quota</th>
            <th class="admin-col-quota"><?php echo admin_sort_link('upl', 'Set upload tier', $sort, $dir, $qSearch); ?></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($rows as $r) {
            $used = (int) $r['used_bytes'];
            $dbq = $r['storage_quota_bytes'];
            $dbq = $dbq === null ? null : (int) $dbq;
            $eff = imagekpr_effective_quota_bytes($dbq);
            $over = $eff !== null && $used > $eff;
            $displayQuota = 'Default';
            if ($dbq === 0) {
              $displayQuota = 'Unlimited';
            } elseif ($dbq !== null && $dbq > 0) {
              $displayQuota = imagekpr_format_bytes($dbq);
            } elseif ($dbq === null && $eff !== null) {
              $displayQuota = imagekpr_format_bytes($eff) . ' (default)';
            } else {
              $displayQuota = 'Unlimited';
            }
            $customGb = '';
            if ($dbq !== null && $dbq > 0) {
              $customGb = (string) round($dbq / (1024 * 1024 * 1024), 4);
            }
            $uploadMb = imagekpr_normalize_upload_size_mb($r['upload_size_mb'] ?? 3);
            $tierDownAt = isset($r['upload_tier_downgraded_at']) ? (string) $r['upload_tier_downgraded_at'] : null;
            if ($tierDownAt !== null && trim($tierDownAt) === '') {
              $tierDownAt = null;
            }
            $tierGraceExpired = imagekpr_upload_tier_grace_expired($tierDownAt);
            ?>
            <tr>
              <td class="admin-col-cb"><input type="checkbox" class="admin-user-cb" form="bulkUserForm" name="bulk_user_ids[]" value="<?php echo (int) $r['id']; ?>" aria-label="Select <?php echo htmlspecialchars((string) $r['email'], ENT_QUOTES, 'UTF-8'); ?>"></td>
              <?php
              $emailDisp = (string) $r['email'];
              $nameDisp = (string) ($r['name'] ?? '');
              ?>
              <td class="admin-mono admin-col-email" title="<?php echo htmlspecialchars($emailDisp, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($emailDisp, ENT_QUOTES, 'UTF-8'); ?></td>
              <td class="admin-col-name" title="<?php echo htmlspecialchars($nameDisp !== '' ? $nameDisp : '(no name)', ENT_QUOTES, 'UTF-8'); ?>"><?php echo $nameDisp !== '' ? htmlspecialchars($nameDisp, ENT_QUOTES, 'UTF-8') : '—'; ?></td>
              <td class="admin-col-used <?php echo $over ? 'admin-over' : ''; ?>"><?php echo htmlspecialchars(imagekpr_format_bytes($used), ENT_QUOTES, 'UTF-8'); ?></td>
              <td class="admin-col-qtxt"><?php echo htmlspecialchars($displayQuota, ENT_QUOTES, 'UTF-8'); ?></td>
              <td class="admin-muted admin-col-login"><?php
                $ll = $r['last_login_at'];
            if ($ll) {
              $llStr = (string) $ll;
              $llTs = strtotime($llStr);
              echo $llTs !== false ? htmlspecialchars(date('Y-m-d H:i', $llTs), ENT_QUOTES, 'UTF-8') : htmlspecialchars($llStr, ENT_QUOTES, 'UTF-8');
            } else {
              echo '—';
            }
            ?></td>
              <td class="admin-muted admin-col-days"><?php
                $df = imagekpr_days_since_mysql_datetime(isset($r['created_at']) ? (string) $r['created_at'] : null);
            echo $df !== null ? (string) (int) $df : '—';
            ?></td>
              <td class="admin-muted admin-col-days"><?php
                $dl = imagekpr_days_since_mysql_datetime($ll !== null && $ll !== '' ? (string) $ll : null);
            echo $dl !== null ? (string) (int) $dl : '—';
            ?></td>
              <td class="admin-col-admin"><?php echo (int) $r['is_admin'] ? 'Yes' : ''; ?></td>
              <td class="admin-col-quota">
                <form class="admin-quota-form" method="post" action="index.php<?php
            $hiddenQ = array_filter(['q' => $qSearch !== '' ? $qSearch : null, 'sort' => $sort !== 'email' ? $sort : null, 'dir' => $dir !== 'ASC' ? strtolower($dir) : null]);
            if (!empty($hiddenQ)) {
              echo '?' . htmlspecialchars(http_build_query($hiddenQ), ENT_QUOTES, 'UTF-8');
            }
            ?>">
                  <?php echo imagekpr_csrf_field(); ?>
                  <input type="hidden" name="action" value="set_quota">
                  <input type="hidden" name="user_id" value="<?php echo (int) $r['id']; ?>">
                  <label title="Use site default quota from config / Admin Config"><input type="radio" name="quota_mode" value="default" <?php echo $dbq === null ? 'checked' : ''; ?>> Default</label>
                  <label title="No storage cap"><input type="radio" name="quota_mode" value="unlimited" <?php echo $dbq === 0 ? 'checked' : ''; ?>> Unlimited</label>
                  <label title="Custom cap in gigabytes"><input type="radio" name="quota_mode" value="custom" <?php echo $dbq !== null && $dbq > 0 ? 'checked' : ''; ?>> GB <input type="number" name="quota_gb" min="0.001" step="any" value="<?php echo htmlspecialchars($customGb, ENT_QUOTES, 'UTF-8'); ?>" aria-label="Gigabytes"></label>
                  <button type="submit">Save</button>
                </form>
              </td>
              <td class="admin-col-quota">
                <form class="admin-quota-form" method="post" action="index.php<?php
            $hiddenQ = array_filter(['q' => $qSearch !== '' ? $qSearch : null, 'sort' => $sort !== 'email' ? $sort : null, 'dir' => $dir !== 'ASC' ? strtolower($dir) : null]);
            if (!empty($hiddenQ)) {
              echo '?' . htmlspecialchars(http_build_query($hiddenQ), ENT_QUOTES, 'UTF-8');
            }
            ?>">
                  <?php echo imagekpr_csrf_field(); ?>
                  <input type="hidden" name="action" value="set_upload_tier">
                  <input type="hidden" name="user_id" value="<?php echo (int) $r['id']; ?>">
                  <label><input type="radio" name="upload_size_mb" value="3" <?php echo $uploadMb === 3 ? 'checked' : ''; ?>> 3MB</label>
                  <label><input type="radio" name="upload_size_mb" value="10" <?php echo $uploadMb === 10 ? 'checked' : ''; ?>> 10MB</label>
                  <label><input type="radio" name="upload_size_mb" value="100" <?php echo $uploadMb === 100 ? 'checked' : ''; ?>> 100MB</label>
                  <?php if ($tierDownAt) { ?>
                    <span class="admin-muted"><?php echo $tierGraceExpired ? 'Grace expired' : ('Grace until ' . htmlspecialchars(date('Y-m-d', strtotime($tierDownAt . ' +30 days')), ENT_QUOTES, 'UTF-8')); ?></span>
                  <?php } ?>
                  <button type="submit">Save</button>
                </form>
              </td>
            </tr>
          <?php } ?>
        </tbody>
      </table>
    </div>
  </div>
  <script>
    (function () {
      var all = document.getElementById('admin-select-all');
      if (!all) return;
      all.addEventListener('change', function () {
        document.querySelectorAll('.admin-user-cb').forEach(function (cb) { cb.checked = all.checked; });
      });
    })();
  </script>
  <?php
  require_once __DIR__ . '/../inc/footer.php';
  imagekpr_render_footer(['context' => 'dashboard']);
  ?>
</body>
</html>
