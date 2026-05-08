<?php
ob_start();
require_once __DIR__ . '/../inc/admin.php';
require_once __DIR__ . '/../inc/tiers.php';
imagekpr_require_admin_html(1, 1);

$pdo = imagekpr_pdo();
$actorId = imagekpr_user_id();
$ikServerTheme = imagekpr_user_theme_preference($pdo, (int) $actorId);

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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'apply_saas_tier') {
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
  $tier = (string) ($_POST['saas_tier'] ?? '');
  $ref = imagekpr_plan_tier_storage_reference();
  if ($targetId < 1 || !isset($ref[$tier])) {
    $_SESSION['admin_flash'] = ['type' => 'error', 'msg' => 'Invalid user or SaaS tier.'];
    header('Location: index.php', true, 303);
    exit;
  }
  $stOld = $pdo->prepare('SELECT id, email, storage_quota_bytes, upload_size_mb, upload_tier_downgraded_at FROM users WHERE id = ? LIMIT 1');
  $stOld->execute([$targetId]);
  $oldRow = $stOld->fetch(PDO::FETCH_ASSOC);
  if ($oldRow === false) {
    $_SESSION['admin_flash'] = ['type' => 'error', 'msg' => 'User not found.'];
    header('Location: index.php', true, 303);
    exit;
  }
  $newBytes = (int) $ref[$tier]['bytes'];
  $newMb = (int) $ref[$tier]['upload_mb'];
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
  $up = $pdo->prepare('UPDATE users SET storage_quota_bytes = ?, upload_size_mb = ?, upload_tier_downgraded_at = ? WHERE id = ?');
  $stCheck = $pdo->prepare('SELECT storage_quota_bytes, upload_size_mb FROM users WHERE id = ? LIMIT 1');
  $up->execute([$newBytes, $newMb, $newDown, $targetId]);
  $stCheck->execute([$targetId]);
  $checkRow = $stCheck->fetch(PDO::FETCH_ASSOC);
  $appliedBytes = $checkRow === false || $checkRow['storage_quota_bytes'] === null ? null : (int) $checkRow['storage_quota_bytes'];
  $appliedMb = $checkRow === false ? null : (int) ($checkRow['upload_size_mb'] ?? 0);
  if ($checkRow === false || $appliedBytes !== $newBytes || $appliedMb !== $newMb) {
    $_SESSION['admin_flash'] = [
      'type' => 'error',
      'msg' => 'Could not fully apply ' . $ref[$tier]['label'] . ' preset for ' . $oldRow['email'] . '. Run migrations/phase20_upload_size_mb_smallint.sql and try again.'
    ];
    $redir = 'index.php';
    $q = $_GET;
    if (!empty($q)) {
      $redir .= '?' . http_build_query($q);
    }
    header('Location: ' . $redir, true, 303);
    exit;
  }
  imagekpr_admin_audit_log($pdo, $actorId, 'user_saas_tier_applied', [
    'target_user_id' => $targetId,
    'target_email' => $oldRow['email'],
    'saas_tier' => $tier,
    'new_storage_quota_bytes' => $newBytes,
    'new_upload_size_mb' => $newMb,
    'old_storage_quota_bytes' => $oldRow['storage_quota_bytes'],
    'old_upload_size_mb' => $oldMb,
  ]);
  $suffix = ($newMb < $oldMb) ? ' Upload grace starts now (30 days).' : '';
  $_SESSION['admin_flash'] = ['type' => 'ok', 'msg' => 'Applied ' . $ref[$tier]['label'] . ' preset for ' . $oldRow['email'] . '.' . $suffix];
  $redir = 'index.php';
  $q = $_GET;
  if (!empty($q)) {
    $redir .= '?' . http_build_query($q);
  }
  header('Location: ' . $redir, true, 303);
  exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'bulk_apply_saas_tier') {
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
  $tier = (string) ($_POST['bulk_saas_tier'] ?? '');
  $ref = imagekpr_plan_tier_storage_reference();
  if (!isset($ref[$tier])) {
    $_SESSION['admin_flash'] = ['type' => 'error', 'msg' => 'Invalid SaaS tier.'];
    $redir = 'index.php';
    $q = $_GET;
    if (!empty($q)) {
      $redir .= '?' . http_build_query($q);
    }
    header('Location: ' . $redir, true, 303);
    exit;
  }
  $newBytes = (int) $ref[$tier]['bytes'];
  $newMb = (int) $ref[$tier]['upload_mb'];
  $stOld = $pdo->prepare('SELECT id, email, upload_size_mb, upload_tier_downgraded_at FROM users WHERE id = ? LIMIT 1');
  $stCheck = $pdo->prepare('SELECT email, storage_quota_bytes, upload_size_mb FROM users WHERE id = ? LIMIT 1');
  $up = $pdo->prepare('UPDATE users SET storage_quota_bytes = ?, upload_size_mb = ?, upload_tier_downgraded_at = ? WHERE id = ?');
  $okIds = [];
  $failedEmails = [];
  foreach ($ids as $tid) {
    $stOld->execute([$tid]);
    $oldRow = $stOld->fetch(PDO::FETCH_ASSOC);
    if ($oldRow === false) {
      continue;
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
    $up->execute([$newBytes, $newMb, $newDown, $tid]);
    $stCheck->execute([$tid]);
    $checkRow = $stCheck->fetch(PDO::FETCH_ASSOC);
    $appliedBytes = $checkRow === false || $checkRow['storage_quota_bytes'] === null ? null : (int) $checkRow['storage_quota_bytes'];
    $appliedMb = $checkRow === false ? null : (int) ($checkRow['upload_size_mb'] ?? 0);
    if ($checkRow !== false && $appliedBytes === $newBytes && $appliedMb === $newMb) {
      $okIds[] = $tid;
    } else {
      $failedEmails[] = (string) ($oldRow['email'] ?? ('user #' . $tid));
    }
  }
  imagekpr_admin_audit_log($pdo, $actorId, 'bulk_saas_tier_applied', [
    'target_user_ids' => $ids,
    'applied_user_ids' => $okIds,
    'failed_emails' => $failedEmails,
    'saas_tier' => $tier,
    'new_storage_quota_bytes' => $newBytes,
    'new_upload_size_mb' => $newMb,
  ]);
  if ($failedEmails !== []) {
    $_SESSION['admin_flash'] = [
      'type' => 'error',
      'msg' => 'Applied ' . $ref[$tier]['label'] . ' preset to ' . count($okIds) . ' user(s), but ' . count($failedEmails) . ' row(s) did not persist correctly. Run migrations/phase20_upload_size_mb_smallint.sql and try again. Failed: ' . implode(', ', $failedEmails) . '.'
    ];
  } else {
    $_SESSION['admin_flash'] = ['type' => 'ok', 'msg' => 'Applied ' . $ref[$tier]['label'] . ' preset to ' . count($okIds) . ' user(s).'];
  }
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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'bulk_delete_users') {
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
  $phrase = trim((string) ($_POST['delete_users_confirm'] ?? ''));
  if (strcasecmp($phrase, imagekpr_admin_delete_users_confirm_phrase()) !== 0) {
    $_SESSION['admin_flash'] = ['type' => 'error', 'msg' => 'Confirmation phrase does not match. No user accounts were deleted.'];
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
    $_SESSION['admin_flash'] = ['type' => 'error', 'msg' => 'Select at least one user account to delete.'];
    $redir = 'index.php';
    $q = $_GET;
    if (!empty($q)) {
      $redir .= '?' . http_build_query($q);
    }
    header('Location: ' . $redir, true, 303);
    exit;
  }

  $result = imagekpr_admin_delete_users($pdo, $ids, [$actorId]);
  imagekpr_admin_audit_log($pdo, $actorId, 'bulk_user_delete', [
    'target_user_ids_requested' => $ids,
    'deleted_user_ids' => $result['deleted_user_ids'],
    'users_deleted' => $result['users_deleted'],
    'skipped_admin_ids' => $result['skipped_admin_ids'],
    'skipped_protected_ids' => $result['skipped_protected_ids'],
    'images_deleted' => $result['images_deleted'],
    'image_files_removed' => $result['image_files_removed'],
    'folders_deleted' => $result['folders_deleted'],
    'folder_links_deleted' => $result['folder_links_deleted'],
    'allowlist_deleted' => $result['allowlist_deleted'],
    'access_requests_deleted' => $result['access_requests_deleted'],
  ]);
  $msg = 'Deleted ' . (int) $result['users_deleted'] . ' user account(s)';
  if (!empty($result['skipped_admin_ids'])) {
    $msg .= '; skipped ' . count($result['skipped_admin_ids']) . ' admin account(s)';
  }
  if (!empty($result['skipped_protected_ids'])) {
    $msg .= '; skipped ' . count($result['skipped_protected_ids']) . ' protected account(s)';
  }
  $msg .= '.';
  $_SESSION['admin_flash'] = ['type' => 'ok', 'msg' => $msg];
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
$tierFilter = strtolower(trim((string) ($_GET['tier'] ?? 'all')));
$tierMap = [
  'all' => null,
  'free' => 'u.upload_size_mb <= 3',
  'silver' => 'u.upload_size_mb = 10',
  'gold' => 'u.upload_size_mb = 50',
  'platinum' => 'u.upload_size_mb >= 500',
];
if (!array_key_exists($tierFilter, $tierMap)) {
  $tierFilter = 'all';
}
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
  'files' => 'image_count',
  'dashes' => 'dashboard_count',
];
if (!isset($sortMap[$sort])) {
  $sort = 'email';
}
$orderCol = $sortMap[$sort];

$params = [];
$whereParts = [];
if ($qSearch !== '') {
  $whereParts[] = '(u.email LIKE ? OR u.name LIKE ?)';
  $like = '%' . $qSearch . '%';
  $params[] = $like;
  $params[] = $like;
}
if ($tierMap[$tierFilter] !== null) {
  $whereParts[] = (string) $tierMap[$tierFilter];
}
$whereSql = '';
if (!empty($whereParts)) {
  $whereSql = ' WHERE ' . implode(' AND ', $whereParts) . ' ';
}

$sql = 'SELECT u.id, u.email, u.name, u.is_admin, u.created_at, u.last_login_at, u.storage_quota_bytes, u.upload_size_mb, u.upload_tier_downgraded_at,
  COALESCE(SUM(i.size_bytes), 0) AS used_bytes,
  COUNT(i.id) AS image_count,
  COALESCE(dc.dashboard_count, 0) AS dashboard_count,
  COALESCE(dm.dashboard_max_images_one_board, 0) AS dashboard_max_images_one_board
  FROM users u
  LEFT JOIN (
    SELECT user_id, COUNT(*) AS dashboard_count FROM shared_dashboards GROUP BY user_id
  ) dc ON dc.user_id = u.id
  LEFT JOIN (
    SELECT t.user_id, MAX(t.slot_cnt) AS dashboard_max_images_one_board
    FROM (
      SELECT sd.user_id, sd.id, COUNT(sdi.image_id) AS slot_cnt
      FROM shared_dashboards sd
      LEFT JOIN shared_dashboard_images sdi ON sdi.dashboard_id = sd.id
      GROUP BY sd.user_id, sd.id
    ) t
    GROUP BY t.user_id
  ) dm ON dm.user_id = u.id
  LEFT JOIN images i ON i.user_id = u.id'
  . $whereSql .
  ' GROUP BY u.id, u.email, u.name, u.is_admin, u.created_at, u.last_login_at, u.storage_quota_bytes, u.upload_size_mb, u.upload_tier_downgraded_at,
    dc.dashboard_count, dm.dashboard_max_images_one_board
  ORDER BY ' . $orderCol . ' ' . $dir . ', u.id ASC';

$st = $pdo->prepare($sql);
$st->execute($params);
$rows = $st->fetchAll(PDO::FETCH_ASSOC);

$totalStorage = (int) $pdo->query('SELECT COALESCE(SUM(size_bytes), 0) FROM images')->fetchColumn();
$totalFiles = (int) $pdo->query('SELECT COUNT(*) FROM images')->fetchColumn();
$hasMediaTypeColumn = false;
try {
  $colSt = $pdo->query("SHOW COLUMNS FROM images LIKE 'media_type'");
  $hasMediaTypeColumn = $colSt && (bool) $colSt->fetch(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
  $hasMediaTypeColumn = false;
}
$totalImageFiles = $totalFiles;
$totalVideoFiles = 0;
if ($hasMediaTypeColumn) {
  $mediaTotals = $pdo->query(
    "SELECT
      SUM(media_type = 'image') AS image_count,
      SUM(media_type = 'video') AS video_count
    FROM images"
  )->fetch(PDO::FETCH_ASSOC);
  $totalImageFiles = (int) ($mediaTotals['image_count'] ?? 0);
  $totalVideoFiles = (int) ($mediaTotals['video_count'] ?? 0);
}
$totalFolders = (int) $pdo->query('SELECT COUNT(*) FROM folders')->fetchColumn();
$totalDashboards = (int) $pdo->query('SELECT COUNT(*) FROM shared_dashboards')->fetchColumn();
$totalTags = (int) $pdo->query('SELECT COALESCE(SUM(JSON_LENGTH(tags)), 0) FROM images WHERE tags IS NOT NULL')->fetchColumn();
$userCount = (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
$freeUsers = 0;
$silverUsers = 0;
$goldUsers = 0;
$platinumUsers = 0;

$sqlAll = 'SELECT u.id, u.email, u.name, u.is_admin, u.created_at, u.last_login_at, u.storage_quota_bytes, u.upload_size_mb, u.upload_tier_downgraded_at,
  COALESCE(SUM(i.size_bytes), 0) AS used_bytes,
  COUNT(i.id) AS image_count,
  COALESCE(dc.dashboard_count, 0) AS dashboard_count,
  COALESCE(dm.dashboard_max_images_one_board, 0) AS dashboard_max_images_one_board
  FROM users u
  LEFT JOIN (
    SELECT user_id, COUNT(*) AS dashboard_count FROM shared_dashboards GROUP BY user_id
  ) dc ON dc.user_id = u.id
  LEFT JOIN (
    SELECT t.user_id, MAX(t.slot_cnt) AS dashboard_max_images_one_board
    FROM (
      SELECT sd.user_id, sd.id, COUNT(sdi.image_id) AS slot_cnt
      FROM shared_dashboards sd
      LEFT JOIN shared_dashboard_images sdi ON sdi.dashboard_id = sd.id
      GROUP BY sd.user_id, sd.id
    ) t
    GROUP BY t.user_id
  ) dm ON dm.user_id = u.id
  LEFT JOIN images i ON i.user_id = u.id
  GROUP BY u.id, u.email, u.name, u.is_admin, u.created_at, u.last_login_at, u.storage_quota_bytes, u.upload_size_mb, u.upload_tier_downgraded_at,
    dc.dashboard_count, dm.dashboard_max_images_one_board';
$allRows = $pdo->query($sqlAll)->fetchAll(PDO::FETCH_ASSOC);

$overQuota = 0;
$expiredGraceUsers = 0;
foreach ($allRows as $r) {
  $used = (int) $r['used_bytes'];
  $dbq = $r['storage_quota_bytes'];
  $dbq = $dbq === null ? null : (int) $dbq;
  $tierEnt = imagekpr_plan_admin_tier_entitlements($dbq, (int) ($r['upload_size_mb'] ?? 3));
  if ($tierEnt['matrix_key'] === 'free') {
    $freeUsers++;
  } elseif ($tierEnt['matrix_key'] === 'silver') {
    $silverUsers++;
  } elseif ($tierEnt['matrix_key'] === 'gold') {
    $goldUsers++;
  } elseif ($tierEnt['matrix_key'] === 'platinum') {
    $platinumUsers++;
  }
  $eff = imagekpr_effective_quota_bytes_for_user_row($dbq, (int) ($r['upload_size_mb'] ?? 3));
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

function admin_sort_link(string $col, string $label, string $currentSort, string $currentDir, string $q, string $tier): string
{
  $nextDir = 'asc';
  if ($currentSort === $col && $currentDir === 'ASC') {
    $nextDir = 'desc';
  }
  $qs = ['sort' => $col, 'dir' => $nextDir];
  if ($q !== '') {
    $qs['q'] = $q;
  }
  if ($tier !== 'all') {
    $qs['tier'] = $tier;
  }
  $arrow = '';
  if ($currentSort === $col) {
    $arrow = $currentDir === 'ASC' ? ' ▲' : ' ▼';
  }
  return '<a href="index.php?' . htmlspecialchars(http_build_query($qs), ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . $arrow . '</a>';
}

/** Circled “i” — hover says click for more; click opens the shared info modal. */
function admin_th_hint(string $tip): string
{
  $e = htmlspecialchars($tip, ENT_QUOTES, 'UTF-8');

  return '<button type="button" class="admin-th-hint" title="Click for more" data-admin-info="' . $e . '" aria-label="More info"><span aria-hidden="true">i</span></button>';
}

?>
<!DOCTYPE html>
<html lang="en" data-theme="<?php echo htmlspecialchars($ikServerTheme, ENT_QUOTES, 'UTF-8'); ?>">
<head>
  <meta charset="UTF-8">
  <script>
    (function () {
      try {
        var t = localStorage.getItem('ikpr-theme-override');
        if (t === 'light' || t === 'dark') {
          document.documentElement.setAttribute('data-theme', t);
        }
      } catch (e) {}
    })();
  </script>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8'); ?></title>
  <link rel="apple-touch-icon" sizes="180x180" href="../favicons/apple-touch-icon.png">
  <link rel="icon" type="image/png" sizes="32x32" href="../favicons/favicon-32x32.png">
  <link rel="icon" type="image/png" sizes="16x16" href="../favicons/favicon-16x16.png">
  <link rel="icon" type="image/png" sizes="192x192" href="../favicons/android-chrome-192x192.png">
  <link rel="icon" type="image/png" sizes="512x512" href="../favicons/android-chrome-512x512.png">
  <link rel="shortcut icon" href="../favicons/favicon.ico">
  <link rel="icon" type="image/x-icon" sizes="192x192" href="../favicons/favicon-192x192.ico">
  <link rel="manifest" href="../favicons/site.webmanifest">
  <link rel="stylesheet" href="../styles.css">
  <style>
    .admin-wrap { max-width: min(1240px, calc(100vw - 1.5rem)); margin: 0 auto; padding: 1rem 0.75rem 2rem; box-sizing: border-box; }
    .admin-nav { display: flex; gap: 1rem; align-items: center; flex-wrap: wrap; padding: 0.75rem 0; border-bottom: 1px solid #ddd; margin-bottom: 1.25rem; }
    .admin-nav a { color: #1565c0; text-decoration: none; font-weight: 600; }
    .admin-nav a:hover { text-decoration: underline; }
    .admin-nav .admin-nav-spacer { flex: 1; min-width: 0; }
    .admin-muted { color: #666; font-size: 0.9rem; }
    .admin-badge { display: inline-block; padding: 0.2rem 0.5rem; background: #e3f2fd; border-radius: 4px; font-size: 0.75rem; color: #1565c0; }
    .admin-stats { display: grid; grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); gap: 0.75rem; margin: 1rem 0 1.5rem; }
    .admin-stat { background: #fafafa; border: 1px solid #eee; border-radius: 6px; padding: 0.75rem 1rem; min-width: 0; }
    .admin-stat--wide { grid-column: span 2; }
    .admin-stat dt { font-size: 0.75rem; color: #666; margin: 0; text-transform: uppercase; letter-spacing: 0.03em; }
    .admin-stat dd { margin: 0.35rem 0 0; font-size: 1.15rem; font-weight: 600; color: #333; }
    .admin-top-list { margin: 0; padding-left: 1.1rem; font-size: 0.85rem; color: #444; }
    .admin-top-list li { word-break: break-all; line-height: 1.4; }
    .admin-stats-wrap.is-hidden .admin-stats { display: none; }
    .admin-info-wrap.is-hidden .admin-info-panel { display: none; }
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
    table.admin-users th .admin-th-head { display: inline-flex; align-items: center; gap: 0.28rem; flex-wrap: wrap; max-width: 100%; box-sizing: border-box; }
    table.admin-users th.admin-col-storage .admin-th-head,
    table.admin-users th.admin-col-files .admin-th-head,
    table.admin-users th.admin-col-dashboards .admin-th-head,
    table.admin-users th.admin-col-days .admin-th-head { justify-content: flex-end; width: 100%; }
    table.admin-users th .admin-th-head--plan { flex-direction: column; align-items: stretch; gap: 0.15rem; }
    table.admin-users th .admin-th-plan-line { display: flex; align-items: center; justify-content: space-between; gap: 0.25rem; }
    .admin-th-hint {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      width: 0.95rem;
      height: 0.95rem;
      border-radius: 50%;
      border: 1px solid #90a4ae;
      background: #eceff1;
      color: #37474f;
      font-size: 0.58rem;
      font-weight: 700;
      font-style: italic;
      font-family: Georgia, 'Times New Roman', serif;
      line-height: 1;
      cursor: pointer;
      flex-shrink: 0;
      user-select: none;
      padding: 0;
      appearance: none;
      -webkit-appearance: none;
    }
    .admin-th-hint:hover {
      border-color: #546e7a;
      background: #cfd8dc;
      color: #102027;
    }
    .admin-th-hint:focus-visible {
      outline: 2px solid rgba(21, 101, 192, 0.45);
      outline-offset: 2px;
    }
    .admin-info-modal[hidden] { display: none; }
    .admin-info-modal {
      position: fixed;
      inset: 0;
      z-index: 1100;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 1rem;
      box-sizing: border-box;
    }
    .admin-info-modal-backdrop {
      position: absolute;
      inset: 0;
      background: rgba(17, 24, 39, 0.48);
    }
    .admin-info-modal-card {
      position: relative;
      width: min(32rem, calc(100vw - 2rem));
      max-height: min(80vh, 36rem);
      overflow: auto;
      background: #fff;
      border: 1px solid #d8e1e8;
      border-radius: 14px;
      box-shadow: 0 20px 48px rgba(15, 23, 42, 0.22);
      padding: 1rem 1rem 1.1rem;
    }
    .admin-info-modal-top {
      display: flex;
      align-items: flex-start;
      justify-content: space-between;
      gap: 1rem;
      margin-bottom: 0.65rem;
    }
    .admin-info-modal-title {
      margin: 0;
      font-size: 1rem;
      line-height: 1.3;
      color: #111827;
    }
    .admin-info-modal-close {
      border: 1px solid #cbd5e1;
      background: #f8fafc;
      color: #334155;
      border-radius: 8px;
      padding: 0.28rem 0.55rem;
      font-size: 0.85rem;
      cursor: pointer;
      flex-shrink: 0;
    }
    .admin-info-modal-close:hover {
      background: #eef2f7;
    }
    .admin-info-modal-body {
      margin: 0;
      color: #334155;
      font-size: 0.94rem;
      line-height: 1.6;
      white-space: pre-line;
    }
    table.admin-users tr:last-child td { border-bottom: none; }
    table.admin-users .admin-col-cb { width: 2.25rem; box-sizing: border-box; }
    table.admin-users .admin-col-email { width: 20%; max-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    table.admin-users .admin-col-name { width: 11%; max-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    table.admin-users .admin-col-storage { width: 11%; white-space: nowrap; font-variant-numeric: tabular-nums; font-size: 0.8rem; }
    table.admin-users .admin-col-files { width: 8%; white-space: nowrap; text-align: right; font-variant-numeric: tabular-nums; font-size: 0.8rem; }
    table.admin-users .admin-col-dashboards { width: 8.5%; white-space: nowrap; text-align: right; font-variant-numeric: tabular-nums; font-size: 0.8rem; }
    table.admin-users .admin-col-login { width: 7%; white-space: nowrap; font-variant-numeric: tabular-nums; font-size: 0.78rem; }
    table.admin-users .admin-col-days { width: 4rem; white-space: nowrap; text-align: right; font-variant-numeric: tabular-nums; }
    table.admin-users .admin-col-admin { width: 2.5rem; white-space: nowrap; text-align: center; }
    table.admin-users .admin-col-plan { width: 6.75rem; min-width: 6rem; font-size: 0.75rem; line-height: 1.25; }
    table.admin-users .admin-tier-match { font-weight: 600; margin-bottom: 0.3rem; word-break: break-word; }
    table.admin-users .admin-preset-form { display: flex; flex-direction: column; gap: 0.2rem; align-items: stretch; }
    table.admin-users .admin-preset-form button { font-size: 0.68rem; padding: 0.12rem 0.25rem; cursor: pointer; }
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
    .admin-toggle-row { display: flex; justify-content: flex-end; margin-bottom: 0.25rem; }
    .admin-toggle-row button { padding: 0.3rem 0.65rem; cursor: pointer; font-size: 0.78rem; }
    .admin-bulk-quick.is-hidden { display: none; }
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

    <div id="admin-stats-wrap" class="admin-stats-wrap">
      <div class="admin-toggle-row">
        <button type="button" id="admin-stats-toggle" aria-expanded="true" aria-controls="admin-stats-panel">Hide stats</button>
      </div>
      <dl id="admin-stats-panel" class="admin-stats">
        <div class="admin-stat">
          <dt>Total storage (images)</dt>
          <dd><?php echo htmlspecialchars(imagekpr_format_bytes($totalStorage), ENT_QUOTES, 'UTF-8'); ?></dd>
        </div>
        <?php if ($hasMediaTypeColumn) { ?>
        <div class="admin-stat">
          <dt>Image files</dt>
          <dd><?php echo number_format($totalImageFiles); ?></dd>
        </div>
        <div class="admin-stat">
          <dt>Video files</dt>
          <dd><?php echo number_format($totalVideoFiles); ?></dd>
        </div>
        <?php } else { ?>
        <div class="admin-stat">
          <dt>Files</dt>
          <dd><?php echo number_format($totalFiles); ?></dd>
        </div>
        <?php } ?>
        <div class="admin-stat">
          <dt>Folders</dt>
          <dd><?php echo number_format($totalFolders); ?></dd>
        </div>
        <div class="admin-stat">
          <dt>Shared dashboards</dt>
          <dd><?php echo number_format($totalDashboards); ?></dd>
        </div>
        <div class="admin-stat">
          <dt>Tag usages</dt>
          <dd><?php echo number_format($totalTags); ?></dd>
        </div>
        <div class="admin-stat">
          <dt>Users</dt>
          <dd><?php echo (int) $userCount; ?></dd>
        </div>
        <div class="admin-stat">
          <dt>Free users</dt>
          <dd><?php echo $freeUsers; ?></dd>
        </div>
        <div class="admin-stat">
          <dt>Silver users</dt>
          <dd><?php echo $silverUsers; ?></dd>
        </div>
        <div class="admin-stat">
          <dt>Gold users</dt>
          <dd><?php echo $goldUsers; ?></dd>
        </div>
        <div class="admin-stat">
          <dt>Platinum users</dt>
          <dd><?php echo $platinumUsers; ?></dd>
        </div>
        <div class="admin-stat">
          <dt>Over quota</dt>
          <dd><?php echo $overQuota > 0 ? '<span class="admin-over">' . (int) $overQuota . '</span>' : (int) $overQuota; ?></dd>
        </div>
        <div class="admin-stat">
          <dt>Upload grace expired</dt>
          <dd><?php echo $expiredGraceUsers > 0 ? '<span class="admin-over">' . (int) $expiredGraceUsers . '</span>' : (int) $expiredGraceUsers; ?></dd>
        </div>
        <div class="admin-stat admin-stat--wide">
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
    </div>

    <?php
    $d = imagekpr_default_storage_quota_bytes();
    $uploadTiers = imagekpr_allowed_upload_size_tiers_mb();
    $saasRef = imagekpr_plan_tier_storage_reference();
    ?>
    <div id="admin-info-wrap" class="admin-info-wrap">
      <div class="admin-toggle-row">
        <button type="button" id="admin-info-toggle" aria-expanded="true" aria-controls="admin-info-panel">Hide quota/tier notes</button>
      </div>
      <div id="admin-info-panel" class="admin-info-panel">
        <p class="admin-muted">Default quota for users with no per-user cap: <?php
          echo $d === null ? 'none (unlimited)' : htmlspecialchars(imagekpr_format_bytes($d), ENT_QUOTES, 'UTF-8');
        ?> — set <span class="admin-mono">DEFAULT_STORAGE_QUOTA_BYTES</span> in <span class="admin-mono">config.php</span> if needed.</p>
        <p class="admin-muted"><strong>SaaS tier matrix</strong> (reference — upload MB, total storage, image caps, shared-dashboard caps). Use <strong>Plan preset</strong> below to apply Free/Silver/Gold/Platinum storage + upload limits together: <?php echo imagekpr_admin_html_plan_matrix_saas_blurb(); ?></p>
        <p class="admin-muted"><?php echo imagekpr_admin_html_plan_matrix_pro_blurb(); ?></p>
      </div>
    </div>

    <form class="admin-search" method="get" action="index.php">
      <?php if ($sort !== 'email') { ?><input type="hidden" name="sort" value="<?php echo htmlspecialchars($sort, ENT_QUOTES, 'UTF-8'); ?>"><?php } ?>
      <?php if ($dir !== 'ASC') { ?><input type="hidden" name="dir" value="<?php echo htmlspecialchars(strtolower($dir), ENT_QUOTES, 'UTF-8'); ?>"><?php } ?>
      <label>Search <input type="search" name="q" value="<?php echo htmlspecialchars($qSearch, ENT_QUOTES, 'UTF-8'); ?>" placeholder="Email or name"></label>
      <label>Tier
        <select name="tier">
          <option value="all"<?php echo $tierFilter === 'all' ? ' selected' : ''; ?>>All tiers</option>
          <option value="free"<?php echo $tierFilter === 'free' ? ' selected' : ''; ?>>Free</option>
          <option value="silver"<?php echo $tierFilter === 'silver' ? ' selected' : ''; ?>>Silver</option>
          <option value="gold"<?php echo $tierFilter === 'gold' ? ' selected' : ''; ?>>Gold</option>
          <option value="platinum"<?php echo $tierFilter === 'platinum' ? ' selected' : ''; ?>>Platinum</option>
        </select>
      </label>
      <button type="submit">Filter</button>
      <?php
      if ($qSearch !== '') {
        $clearQs = array_filter([
          'sort' => $sort !== 'email' ? $sort : null,
          'dir' => $dir !== 'ASC' ? strtolower($dir) : null,
          'tier' => $tierFilter !== 'all' ? $tierFilter : null,
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
      'tier' => $tierFilter !== 'all' ? $tierFilter : null,
    ]);
    $bulkAction = 'index.php' . (!empty($bulkActionQs) ? '?' . http_build_query($bulkActionQs) : '');
    ?>
    <?php $purgePhrase = imagekpr_admin_purge_confirm_phrase(); ?>
    <?php $deleteUsersPhrase = imagekpr_admin_delete_users_confirm_phrase(); ?>
    <form id="bulkUserForm" class="admin-bulk admin-bulk-actions" method="post" action="<?php echo htmlspecialchars($bulkAction, ENT_QUOTES, 'UTF-8'); ?>">
      <?php echo imagekpr_csrf_field(); ?>
      <?php /* HIDDEN: bulk quota / upload tier / SaaS preset tools — superseded by per-row preset buttons; re-enable if bulk batch operations become needed */ if (false): ?>
      <div class="admin-toggle-row">
        <button type="button" id="admin-bulk-quick-toggle" aria-expanded="true" aria-controls="admin-bulk-quick">Hide quick bulk tools</button>
      </div>
      <div id="admin-bulk-quick" class="admin-bulk-quick">
        <div class="admin-bulk-row">
          <fieldset>
            <legend>Bulk quota (selected rows)</legend>
            <label><input type="radio" name="bulk_quota_mode" value="default" checked> Site default</label>
            <label><input type="radio" name="bulk_quota_mode" value="unlimited"> Unlimited</label>
            <label title="Binary GiB; 50 = 50 GiB not 50 MB"><input type="radio" name="bulk_quota_mode" value="custom"> GiB <input type="number" name="bulk_quota_gb" min="0.001" step="any" value="10"></label>
          </fieldset>
          <button type="submit" name="action" value="bulk_set_quota">Apply quota to selected</button>
        </div>
        <div class="admin-bulk-row">
          <fieldset>
            <legend>Bulk upload tier (selected rows)</legend>
            <?php foreach ($uploadTiers as $idx => $mb) { ?>
            <label><input type="radio" name="bulk_upload_size_mb" value="<?php echo (int) $mb; ?>"<?php echo $idx === 0 ? ' checked' : ''; ?>><?php echo (int) $mb; ?>MB</label>
            <?php } ?>
          </fieldset>
          <button type="submit" name="action" value="bulk_set_upload_tier">Apply upload tier to selected</button>
        </div>
        <div class="admin-bulk-row">
          <fieldset>
            <legend>Bulk SaaS preset (selected rows)</legend>
            <?php
            $firstSaas = true;
            foreach ($saasRef as $sk => $stier) {
              ?>
            <label><input type="radio" name="bulk_saas_tier" value="<?php echo htmlspecialchars((string) $sk, ENT_QUOTES, 'UTF-8'); ?>"<?php echo $firstSaas ? ' checked' : ''; ?>><?php echo htmlspecialchars((string) $stier['label'], ENT_QUOTES, 'UTF-8'); ?></label>
              <?php
              $firstSaas = false;
            }
            ?>
          </fieldset>
          <button type="submit" name="action" value="bulk_apply_saas_tier">Apply preset to selected</button>
        </div>
      </div>
      <?php endif; ?>
      <fieldset class="admin-bulk-purge">
        <legend>Purge gallery only (destructive)</legend>
        <p class="admin-muted">Deletes <strong>published gallery</strong> database rows and image files for the selected users. Does <strong>not</strong> remove user accounts, the email allowlist, or files in the shared inbox folder.</p>
        <label>Type <kbd><?php echo htmlspecialchars($purgePhrase, ENT_QUOTES, 'UTF-8'); ?></kbd> to confirm:
          <input type="text" name="purge_confirm" autocomplete="off" placeholder="<?php echo htmlspecialchars($purgePhrase, ENT_QUOTES, 'UTF-8'); ?>" aria-label="Type confirmation phrase to purge galleries"></label>
        <button type="submit" name="action" value="bulk_purge_gallery" class="admin-btn-danger">Purge gallery for selected</button>
      </fieldset>
      <fieldset class="admin-bulk-purge">
        <legend>Delete user accounts (destructive)</legend>
        <p class="admin-muted">Permanently deletes selected <strong>non-admin user accounts</strong> and their gallery/folder data, and removes matching emails from allowlist + access requests. Your own account is protected and cannot be deleted here.</p>
        <label>Type <kbd><?php echo htmlspecialchars($deleteUsersPhrase, ENT_QUOTES, 'UTF-8'); ?></kbd> to confirm:
          <input type="text" name="delete_users_confirm" autocomplete="off" placeholder="<?php echo htmlspecialchars($deleteUsersPhrase, ENT_QUOTES, 'UTF-8'); ?>" aria-label="Type confirmation phrase to delete users"></label>
        <button type="submit" name="action" value="bulk_delete_users" class="admin-btn-danger">Delete selected user accounts</button>
      </fieldset>
    </form>

    <div class="admin-table-wrap">
      <table class="admin-users">
        <thead>
          <tr>
            <th class="admin-col-cb"><input type="checkbox" id="admin-select-all" title="Select all on this page" aria-label="Select all users on this page"></th>
            <th class="admin-col-email"><?php echo admin_sort_link('email', 'Email', $sort, $dir, $qSearch, $tierFilter); ?></th>
            <th class="admin-col-name"><?php echo admin_sort_link('name', 'Name', $sort, $dir, $qSearch, $tierFilter); ?></th>
            <th class="admin-col-plan">
              <span class="admin-th-head admin-th-head--plan">
                <span class="admin-th-plan-line"><span>Plan preset</span><?php echo admin_th_hint('Matrix label from storage + upload when they match; otherwise storage-only or upload-only. Preset buttons apply both quota and upload tier.'); ?></span>
                <span class="admin-th-sub">matrix match</span>
              </span>
            </th>
            <th class="admin-col-storage">
              <span class="admin-th-head"><?php echo admin_sort_link('used', 'Storage', $sort, $dir, $qSearch, $tierFilter); ?><?php echo admin_th_hint('Used library bytes / effective quota cap (MiB). Red if over cap.'); ?></span>
            </th>
            <th class="admin-col-files">
              <span class="admin-th-head"><?php echo admin_sort_link('files', 'Files', $sort, $dir, $qSearch, $tierFilter); ?><?php echo admin_th_hint('Gallery file count / max library images for the resolved SaaS tier (storage + upload, or storage-only match).'); ?></span>
            </th>
            <th class="admin-col-dashboards">
              <span class="admin-th-head"><?php echo admin_sort_link('dashes', 'Dashboards', $sort, $dir, $qSearch, $tierFilter); ?><?php echo admin_th_hint('Boards owned · largest image count on any one board / per-board image cap from the tier matrix. Red if any board exceeds its cap.'); ?></span>
            </th>
            <th class="admin-col-days">
              <span class="admin-th-head">
                <span>Last access<br><span class="admin-th-sub">days ago</span></span> <?php echo admin_th_hint('Whole days since last sign-in (0 = today). A dash in a cell means never logged in.'); ?>
              </span>
            </th>
            <th class="admin-col-admin"><?php echo admin_sort_link('admin', 'Admin', $sort, $dir, $qSearch, $tierFilter); ?></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($rows as $r) {
            $used = (int) $r['used_bytes'];
            $dbq = $r['storage_quota_bytes'];
            $dbq = $dbq === null ? null : (int) $dbq;
            $eff = imagekpr_effective_quota_bytes_for_user_row($dbq, (int) ($r['upload_size_mb'] ?? 3));
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
            $tierEnt = imagekpr_plan_admin_tier_entitlements($dbq, (int) ($r['upload_size_mb'] ?? 3));
            if ($tierEnt['matrix_key'] !== null) {
              $mk = (string) $tierEnt['matrix_key'];
              $tierMatchLabel = imagekpr_plan_tier_display_label(imagekpr_plan_tier_matrix_reference()[$mk]);
            } else {
              $tierKey = imagekpr_infer_saas_tier_preset_match($dbq, $uploadMb);
              if ($tierKey === null) {
                $tierMatchLabel = 'Unlimited';
              } elseif ($tierKey === 'custom') {
                $tierMatchLabel = 'Custom';
              } else {
                $tierMatchLabel = (string) ($saasRef[$tierKey]['label'] ?? $tierKey);
              }
            }
            ?>
            <tr>
              <td class="admin-col-cb"><input type="checkbox" class="admin-user-cb" form="bulkUserForm" name="bulk_user_ids[]" value="<?php echo (int) $r['id']; ?>" aria-label="Select <?php echo htmlspecialchars((string) $r['email'], ENT_QUOTES, 'UTF-8'); ?>"></td>
              <?php
              $emailDisp = (string) $r['email'];
              $nameDisp = (string) ($r['name'] ?? '');
              ?>
              <td class="admin-mono admin-col-email" title="<?php echo htmlspecialchars($emailDisp, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($emailDisp, ENT_QUOTES, 'UTF-8'); ?></td>
              <td class="admin-col-name" title="<?php echo htmlspecialchars($nameDisp !== '' ? $nameDisp : '(no name)', ENT_QUOTES, 'UTF-8'); ?>"><?php echo $nameDisp !== '' ? htmlspecialchars($nameDisp, ENT_QUOTES, 'UTF-8') : '—'; ?></td>
              <td class="admin-col-plan">
                <div class="admin-tier-match"><?php echo htmlspecialchars($tierMatchLabel, ENT_QUOTES, 'UTF-8'); ?></div>
                <form class="admin-preset-form" method="post" action="index.php<?php
            $hiddenQ = array_filter(['q' => $qSearch !== '' ? $qSearch : null, 'sort' => $sort !== 'email' ? $sort : null, 'dir' => $dir !== 'ASC' ? strtolower($dir) : null, 'tier' => $tierFilter !== 'all' ? $tierFilter : null]);
            if (!empty($hiddenQ)) {
              echo '?' . htmlspecialchars(http_build_query($hiddenQ), ENT_QUOTES, 'UTF-8');
            }
            ?>">
                  <?php echo imagekpr_csrf_field(); ?>
                  <input type="hidden" name="action" value="apply_saas_tier">
                  <input type="hidden" name="user_id" value="<?php echo (int) $r['id']; ?>">
                  <?php foreach ($saasRef as $psk => $pt) { ?>
                  <button type="submit" name="saas_tier" value="<?php echo htmlspecialchars((string) $psk, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars((string) $pt['label'], ENT_QUOTES, 'UTF-8'); ?></button>
                  <?php } ?>
                </form>
              </td>
              <?php
              $ll = $r['last_login_at'];
              $imageCount = (int) ($r['image_count'] ?? 0);
              $maxImages = $tierEnt['max_images'];
              $maxImagesDisp = $maxImages !== null ? (string) (int) $maxImages : '—';
              $dashCount = (int) ($r['dashboard_count'] ?? 0);
              $dashSlotMax = (int) ($r['dashboard_max_images_one_board'] ?? 0);
              $perDashCap = $tierEnt['shared_dashboard_cap'];
              $usedMib = round($used / (1024 * 1024), 1);
              $quotaMibStr = $eff === null ? '∞' : (string) (int) round($eff / (1024 * 1024));
              $storageDisp = $usedMib . ' / ' . $quotaMibStr . ' MiB';
              $imagesOver = $maxImages !== null && $imageCount >= $maxImages;
              $dashOver = $perDashCap !== null && $dashSlotMax > $perDashCap;
              $dashDisp = $perDashCap !== null
                ? $dashCount . ' · ' . $dashSlotMax . '/' . (int) $perDashCap
                : $dashCount . ' · —';
              ?>
              <td class="admin-col-storage <?php echo $over ? 'admin-over' : ''; ?>"><?php echo htmlspecialchars($storageDisp, ENT_QUOTES, 'UTF-8'); ?></td>
              <td class="admin-col-files <?php echo $imagesOver ? 'admin-over' : ''; ?>"><?php echo htmlspecialchars($imageCount . ' / ' . $maxImagesDisp, ENT_QUOTES, 'UTF-8'); ?></td>
              <td class="admin-col-dashboards <?php echo $dashOver ? 'admin-over' : ''; ?>"><?php echo htmlspecialchars($dashDisp, ENT_QUOTES, 'UTF-8'); ?></td>
              <td class="admin-muted admin-col-days"><?php
                $dl = imagekpr_days_since_mysql_datetime($ll !== null && $ll !== '' ? (string) $ll : null);
            echo $dl !== null ? (string) (int) $dl : '—';
            ?></td>
              <td class="admin-col-admin"><?php echo (int) $r['is_admin'] ? 'Yes' : ''; ?></td>
            </tr>
          <?php } ?>
        </tbody>
      </table>
    </div>
  </div>
  <div class="admin-info-modal" id="admin-info-modal" hidden aria-hidden="true">
    <div class="admin-info-modal-backdrop" id="admin-info-modal-backdrop"></div>
    <div class="admin-info-modal-card" role="dialog" aria-modal="true" aria-labelledby="admin-info-modal-title">
      <div class="admin-info-modal-top">
        <h2 class="admin-info-modal-title" id="admin-info-modal-title">Column info</h2>
        <button type="button" class="admin-info-modal-close" id="admin-info-modal-close" aria-label="Close info">Close</button>
      </div>
      <p class="admin-info-modal-body" id="admin-info-modal-body"></p>
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

    (function () {
      var wrap = document.getElementById('admin-stats-wrap');
      var toggle = document.getElementById('admin-stats-toggle');
      if (!wrap || !toggle) return;

      var key = 'imagekpr_admin_stats_hidden';
      var setState = function (hidden) {
        wrap.classList.toggle('is-hidden', hidden);
        toggle.setAttribute('aria-expanded', hidden ? 'false' : 'true');
        toggle.textContent = hidden ? 'Show stats' : 'Hide stats';
      };

      try {
        var saved = window.localStorage.getItem(key);
        setState(saved === null ? true : saved === '1');
      } catch (e) {
        setState(true);
      }

      toggle.addEventListener('click', function () {
        var hidden = !wrap.classList.contains('is-hidden');
        setState(hidden);
        try {
          window.localStorage.setItem(key, hidden ? '1' : '0');
        } catch (e) {
          // Ignore storage errors and keep the current UI state.
        }
      });
    })();

    (function () {
      var wrap = document.getElementById('admin-info-wrap');
      var toggle = document.getElementById('admin-info-toggle');
      if (!wrap || !toggle) return;

      var key = 'imagekpr_admin_info_hidden';
      var setState = function (hidden) {
        wrap.classList.toggle('is-hidden', hidden);
        toggle.setAttribute('aria-expanded', hidden ? 'false' : 'true');
        toggle.textContent = hidden ? 'Show quota/tier notes' : 'Hide quota/tier notes';
      };

      try {
        var saved = window.localStorage.getItem(key);
        setState(saved === null ? true : saved === '1');
      } catch (e) {
        setState(true);
      }

      toggle.addEventListener('click', function () {
        var hidden = !wrap.classList.contains('is-hidden');
        setState(hidden);
        try {
          window.localStorage.setItem(key, hidden ? '1' : '0');
        } catch (e) {
          // Ignore storage errors and keep the current UI state.
        }
      });
    })();

    (function () {
      var quick = document.getElementById('admin-bulk-quick');
      var toggle = document.getElementById('admin-bulk-quick-toggle');
      if (!quick || !toggle) return;

      var key = 'imagekpr_admin_bulk_quick_hidden';
      var setState = function (hidden) {
        quick.classList.toggle('is-hidden', hidden);
        toggle.setAttribute('aria-expanded', hidden ? 'false' : 'true');
        toggle.textContent = hidden ? 'Show quick bulk tools' : 'Hide quick bulk tools';
      };

      try {
        var saved = window.localStorage.getItem(key);
        setState(saved === null ? true : saved === '1');
      } catch (e) {
        setState(true);
      }

      toggle.addEventListener('click', function () {
        var hidden = !quick.classList.contains('is-hidden');
        setState(hidden);
        try {
          window.localStorage.setItem(key, hidden ? '1' : '0');
        } catch (e) {
          // Ignore storage errors and keep the current UI state.
        }
      });
    })();

    (function () {
      var modal = document.getElementById('admin-info-modal');
      var body = document.getElementById('admin-info-modal-body');
      var closeBtn = document.getElementById('admin-info-modal-close');
      var backdrop = document.getElementById('admin-info-modal-backdrop');
      if (!modal || !body || !closeBtn || !backdrop) return;

      var lastTrigger = null;
      var closeModal = function () {
        modal.hidden = true;
        modal.setAttribute('aria-hidden', 'true');
        body.textContent = '';
        if (lastTrigger && typeof lastTrigger.focus === 'function') {
          lastTrigger.focus();
        }
        lastTrigger = null;
      };
      var openModal = function (text, trigger) {
        body.textContent = text || '';
        lastTrigger = trigger || null;
        modal.hidden = false;
        modal.setAttribute('aria-hidden', 'false');
        closeBtn.focus();
      };

      document.querySelectorAll('.admin-th-hint[data-admin-info]').forEach(function (btn) {
        btn.addEventListener('click', function () {
          openModal(btn.getAttribute('data-admin-info') || '', btn);
        });
      });

      closeBtn.addEventListener('click', closeModal);
      backdrop.addEventListener('click', closeModal);
      document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && !modal.hidden) {
          closeModal();
        }
      });
    })();
  </script>
  <?php
  require_once __DIR__ . '/../inc/footer.php';
  imagekpr_render_footer(['context' => 'dashboard']);
  ?>
</body>
</html>
