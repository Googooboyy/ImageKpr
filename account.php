<?php
ob_start();
require_once __DIR__ . '/inc/auth.php';
imagekpr_ensure_config();
imagekpr_start_session();

if (imagekpr_user_id() < 1) {
  imagekpr_redirect_html('index.php', 0);
}

$ikLoggedIn = true;
try {
  $pdo = imagekpr_pdo();
  if (!imagekpr_user_has_app_access($pdo)) {
    $ikMaintenance = imagekpr_maintenance_enabled();
    $ikMaintenanceMsg = $ikMaintenance ? imagekpr_maintenance_banner_text() : '';
    $ikName = isset($_SESSION['name']) ? (string) $_SESSION['name'] : '';
    $ikEmail = isset($_SESSION['email']) ? (string) $_SESSION['email'] : '';
    $ikSubmitted = isset($_GET['submitted']) && (string) $_GET['submitted'] === '1';
    require __DIR__ . '/inc/pending_landing.php';
    exit;
  }
  require_once __DIR__ . '/inc/admin.php';
} catch (Throwable $e) {
  http_response_code(500);
  header('Content-Type: text/plain; charset=utf-8');
  echo 'Could not verify your account. Please try again later.';
  exit;
}

$uid = (int) imagekpr_user_id();
$ikMaintenance = imagekpr_maintenance_enabled();
$ikMaintenanceMsg = $ikMaintenance ? imagekpr_maintenance_banner_text() : '';
$schemaError = '';
$formError = '';
$saved = false;
if (!empty($_SESSION['ikpr_account_saved_flash'])) {
  $saved = true;
  unset($_SESSION['ikpr_account_saved_flash']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['form_action']) && (string) $_POST['form_action'] === 'save_profile') {
  if (!imagekpr_app_csrf_verify()) {
    $formError = 'Security check failed. Please reload the page and try again.';
  } else {
    $rawDn = isset($_POST['display_name']) ? (string) $_POST['display_name'] : '';
    $trimDn = trim($rawDn);
    if (strlen($trimDn) > 255) {
      $formError = 'Display name must be 255 characters or fewer.';
    } else {
      $storeDn = $trimDn === '' ? null : $trimDn;
      try {
        $up = $pdo->prepare('UPDATE users SET display_name = ? WHERE id = ?');
        $up->execute([$storeDn, $uid]);
        $st = $pdo->prepare('SELECT display_name, name, email FROM users WHERE id = ? LIMIT 1');
        $st->execute([$uid]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        if ($row !== false) {
          $em = (string) ($row['email'] ?? $_SESSION['email'] ?? '');
          $_SESSION['name'] = imagekpr_user_header_display_label(
            isset($row['display_name']) ? (string) $row['display_name'] : null,
            isset($row['name']) ? (string) $row['name'] : null,
            $em
          );
        }
        $_SESSION['ikpr_account_saved_flash'] = 1;
        while (ob_get_level() > 0) {
          ob_end_clean();
        }
        header('Location: ' . imagekpr_public_path('account.php', 0), true, 302);
        exit;
      } catch (Throwable $e) {
        $schemaError = 'Could not save your profile. If this is a new install, run the database migration migrations/phase18_user_display_name.sql.';
      }
    }
  }
}

$userRow = null;
$totalImages = 0;
$totalFolders = 0;
$totalStorageBytes = 0;
$quotaStatus = ['effective_bytes' => null, 'unlimited' => true, 'remaining_bytes' => null, 'used_bytes' => 0];
$planLabelDisplay = '—';
$maxFileSizeDisplay = '—';
$googleNameLine = '';
$uploadMb = 3;
$downgradedAt = null;
$preset = null;
$planCapMaxImages = null;
$planCapSharedDashboard = null;
$dateJoinedDisplay = '—';

try {
  $st = $pdo->prepare(
    'SELECT email, name, display_name, storage_quota_bytes, upload_size_mb, upload_tier_downgraded_at, created_at
     FROM users WHERE id = ? LIMIT 1'
  );
  $st->execute([$uid]);
  $userRow = $st->fetch(PDO::FETCH_ASSOC);
  if ($userRow === false) {
    throw new RuntimeException('User row missing');
  }

  $userWhere = 'user_id = ' . $uid;
  if (imagekpr_share_null_user_rows_enabled()) {
    $userWhere = '(user_id = ' . $uid . ' OR user_id IS NULL)';
  }
  $totalImages = (int) $pdo->query('SELECT COUNT(*) FROM images WHERE ' . $userWhere)->fetchColumn();
  $totalFolders = (int) $pdo->query('SELECT COUNT(*) FROM folders WHERE user_id = ' . $uid)->fetchColumn();
  $totalStorageBytes = (int) $pdo->query('SELECT COALESCE(SUM(size_bytes), 0) FROM images WHERE ' . $userWhere)->fetchColumn();

  $quotaStatus = imagekpr_user_storage_quota_status($pdo, $uid);
  $uploadMb = imagekpr_normalize_upload_size_mb($userRow['upload_size_mb'] ?? 3);
  $downgradedAt = isset($userRow['upload_tier_downgraded_at']) ? (string) $userRow['upload_tier_downgraded_at'] : null;
  if ($downgradedAt !== null && trim($downgradedAt) === '') {
    $downgradedAt = null;
  }

  $dbq = $userRow['storage_quota_bytes'];
  $dbq = $dbq === null ? null : (int) $dbq;
  $preset = imagekpr_infer_saas_tier_preset_match($dbq, $uploadMb);
  if ($preset === null) {
    $planLabelDisplay = 'Pro';
  } elseif ($preset === 'custom') {
    $planLabelDisplay = 'Custom';
  } else {
    $refLabel = imagekpr_plan_tier_matrix_reference()[$preset];
    $planLabelDisplay = (string) $refLabel['label'];
  }
  $maxFileSizeDisplay = 'Up to ' . (int) $uploadMb . ' MB per file';

  if (in_array($preset, ['free', 'silver', 'gold'], true)) {
    $refCaps = imagekpr_plan_tier_matrix_reference()[$preset];
    $planCapMaxImages = (int) $refCaps['max_images'];
    $planCapSharedDashboard = (int) $refCaps['shared_dashboard_cap'];
  }

  $createdRaw = $userRow['created_at'] ?? null;
  if ($createdRaw !== null && trim((string) $createdRaw) !== '') {
    try {
      $dateJoinedDisplay = (new DateTimeImmutable((string) $createdRaw))->format('F j, Y');
    } catch (Throwable $e) {
      $dateJoinedDisplay = (string) $createdRaw;
    }
  }

  $gName = trim((string) ($userRow['name'] ?? ''));
  $googleNameLine = $gName !== '' ? $gName : '—';
} catch (Throwable $e) {
  $schemaError = $schemaError !== '' ? $schemaError : 'Could not load account data. Run migrations/phase18_user_display_name.sql if you have not yet.';
}

$emailShown = $userRow ? (string) ($userRow['email'] ?? '') : (string) ($_SESSION['email'] ?? '');
$displayInput = '';
if ($userRow !== null) {
  $displayInput = isset($userRow['display_name']) && $userRow['display_name'] !== null
    ? (string) $userRow['display_name']
    : '';
}
if ($formError !== '' && isset($_POST['display_name'])) {
  $displayInput = (string) $_POST['display_name'];
}

$storageHint = $userRow !== null ? imagekpr_stats_storage_hint_line($totalStorageBytes, $quotaStatus) : '';
$graceNote = '';
if ($downgradedAt !== null && !imagekpr_upload_tier_grace_expired($downgradedAt)) {
  $graceNote = 'Your upload limit was reduced recently; larger files may still upload until the '
    . (int) imagekpr_upload_tier_grace_days()
    . '-day grace period ends.';
}

$ikIsAdmin = imagekpr_user_is_admin($pdo, $uid);
$headerLabel = imagekpr_user_header_display_label(
  $userRow && isset($userRow['display_name']) ? (string) $userRow['display_name'] : null,
  $userRow && isset($userRow['name']) ? (string) $userRow['name'] : null,
  $emailShown
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Account — ImageKpr</title>
  <link rel="stylesheet" href="styles.css">
</head>
<body class="ikpr-account-body<?php echo $ikMaintenance ? ' ikpr-maintenance' : ''; ?>">
  <?php if ($ikMaintenance) { ?>
  <div class="ikpr-maintenance-banner" role="alert"><?php echo htmlspecialchars($ikMaintenanceMsg, ENT_QUOTES, 'UTF-8'); ?></div>
  <?php } ?>
  <header class="ikpr-account-header">
    <a href="index.php" class="ikpr-account-back">← Back to library</a>
    <h1 class="ikpr-account-title">Account</h1>
    <p class="ikpr-account-lead">Signed in as <strong><?php echo htmlspecialchars($headerLabel, ENT_QUOTES, 'UTF-8'); ?></strong></p>
  </header>

  <main class="ikpr-account-main">
    <?php if ($schemaError !== '') { ?>
    <p class="ikpr-account-error" role="alert"><?php echo htmlspecialchars($schemaError, ENT_QUOTES, 'UTF-8'); ?></p>
    <?php } ?>
    <?php if ($saved && $formError === '') { ?>
    <p class="ikpr-account-saved" role="status">Profile saved.</p>
    <?php } ?>
    <?php if ($formError !== '') { ?>
    <p class="ikpr-account-error" role="alert"><?php echo htmlspecialchars($formError, ENT_QUOTES, 'UTF-8'); ?></p>
    <?php } ?>

    <?php if ($userRow !== null) { ?>
    <section class="ikpr-account-card" aria-labelledby="account-achievements-heading">
      <h2 id="account-achievements-heading" class="ikpr-account-card-title">Achievements</h2>
      <dl class="ikpr-account-dl ikpr-account-dl-single">
        <div><dt>Date joined</dt><dd><?php echo htmlspecialchars($dateJoinedDisplay, ENT_QUOTES, 'UTF-8'); ?></dd></div>
        <div><dt>Achievements unlocked</dt><dd class="ikpr-account-dl-note"><?php echo htmlspecialchars('Coming soon — badges and milestones will appear here.', ENT_QUOTES, 'UTF-8'); ?></dd></div>
      </dl>
    </section>

    <section class="ikpr-account-card" aria-labelledby="account-stats-heading">
      <h2 id="account-stats-heading" class="ikpr-account-card-title">Library &amp; limits</h2>
      <dl class="ikpr-account-dl">
        <div><dt>Plan</dt><dd><?php echo htmlspecialchars($planLabelDisplay, ENT_QUOTES, 'UTF-8'); ?></dd></div>
        <div><dt>Total images</dt><dd><?php echo (int) $totalImages; ?></dd></div>
        <div><dt>Folders</dt><dd><?php echo (int) $totalFolders; ?></dd></div>
        <div><dt>Storage</dt><dd><?php echo htmlspecialchars($storageHint, ENT_QUOTES, 'UTF-8'); ?></dd></div>
        <div><dt>Max file size</dt><dd><?php echo htmlspecialchars($maxFileSizeDisplay, ENT_QUOTES, 'UTF-8'); ?></dd></div>
        <?php if ($planCapMaxImages !== null && $planCapSharedDashboard !== null) { ?>
        <div><dt>Max library images</dt><dd><?php echo (int) $planCapMaxImages; ?> <span class="ikpr-account-dl-sub">(plan cap)</span></dd></div>
        <div><dt>Shared dashboards</dt><dd><?php echo htmlspecialchars('Up to ' . (int) $planCapSharedDashboard . ' images in each shared dashboard', ENT_QUOTES, 'UTF-8'); ?></dd></div>
        <?php } else { ?>
        <div><dt>Max library images</dt><dd class="ikpr-account-dl-note"><?php echo htmlspecialchars('Not listed — your quota does not match a standard Free, Silver, or Gold preset.', ENT_QUOTES, 'UTF-8'); ?></dd></div>
        <div><dt>Shared dashboards</dt><dd class="ikpr-account-dl-note"><?php echo htmlspecialchars('Not listed for the same reason.', ENT_QUOTES, 'UTF-8'); ?></dd></div>
        <?php } ?>
        <div><dt>Total shared dashboards</dt><dd class="ikpr-account-dl-note"><?php echo htmlspecialchars('Coming soon..', ENT_QUOTES, 'UTF-8'); ?></dd></div>
        <?php if ($graceNote !== '') { ?>
        <div class="ikpr-account-grace"><dt></dt><dd><?php echo htmlspecialchars($graceNote, ENT_QUOTES, 'UTF-8'); ?></dd></div>
        <?php } ?>
      </dl>
      <p class="ikpr-account-features-cta"><a href="features.php">See the Features page</a><?php echo htmlspecialchars(' for how library limits, shared dashboards, and plans work.', ENT_QUOTES, 'UTF-8'); ?></p>
      <p class="ikpr-account-features-cta ikpr-account-pricing-cta"><a href="pricing.php">Compare pricing</a><?php echo htmlspecialchars(' and see what fits your needs.', ENT_QUOTES, 'UTF-8'); ?></p>
      <div class="ikpr-account-upgrade">
        <p class="ikpr-account-upgrade-text">Need a higher tier or different limits? Online upgrades are coming soon — for now, get in touch and we can adjust your plan.</p>
        <a href="contact.php" class="ikpr-account-upgrade-cta">Contact us</a>
      </div>
    </section>

    <section class="ikpr-account-card" aria-labelledby="account-profile-heading">
      <h2 id="account-profile-heading" class="ikpr-account-card-title">Profile</h2>
      <p class="ikpr-account-muted">Your email comes from Google sign-in and cannot be changed here.</p>
      <dl class="ikpr-account-dl ikpr-account-dl-single">
        <div><dt>Email</dt><dd><?php echo htmlspecialchars($emailShown, ENT_QUOTES, 'UTF-8'); ?></dd></div>
        <div><dt>Name from Google</dt><dd><?php echo htmlspecialchars($googleNameLine, ENT_QUOTES, 'UTF-8'); ?></dd></div>
      </dl>
      <form method="post" action="account.php" class="ikpr-account-form">
        <input type="hidden" name="form_action" value="save_profile">
        <?php echo imagekpr_app_csrf_field(); ?>
        <label class="ikpr-account-label" for="display_name">Display name</label>
        <input type="text" id="display_name" name="display_name" class="ikpr-account-input" maxlength="255"
          value="<?php echo htmlspecialchars($displayInput, ENT_QUOTES, 'UTF-8'); ?>"
          autocomplete="name" placeholder="How your name appears in the app">
        <p class="ikpr-account-hint">Shown in the top bar. Leave blank to use your Google name.</p>
        <button type="submit" class="ikpr-account-submit">Save display name</button>
      </form>
    </section>
    <?php } ?>

    <nav class="ikpr-account-nav">
      <?php if (!empty($ikIsAdmin)) { ?>
      <a href="admin/index.php">Admin</a>
      <?php } ?>
      <a href="auth/logout.php">Log out</a>
    </nav>
  </main>
</body>
</html>
