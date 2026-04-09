<?php
ob_start();
require_once __DIR__ . '/../inc/admin.php';
imagekpr_require_admin_html(1, 1);

$pdo = imagekpr_pdo();
$actorId = imagekpr_user_id();

function imagekpr_admin_config_redirect(): void
{
  header('Location: config.php', true, 303);
  exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!imagekpr_csrf_verify()) {
    $_SESSION['admin_flash'] = ['type' => 'error', 'msg' => 'Security token invalid. Try again.'];
    imagekpr_admin_config_redirect();
  }
  $act = (string) ($_POST['form_action'] ?? '');

  if ($act === 'save_app_settings') {
    $changes = [];
    $dq = trim((string) ($_POST['default_storage_quota_bytes'] ?? ''));
    if ($dq === '') {
      ImageKprAppSettings::upsert($pdo, 'default_storage_quota_bytes', null);
      $changes[] = 'default_storage_quota_bytes';
    } else {
      if (!ctype_digit($dq)) {
        $_SESSION['admin_flash'] = ['type' => 'error', 'msg' => 'Default quota must be empty (use config.php) or a whole number of bytes.'];
        imagekpr_admin_config_redirect();
      }
      ImageKprAppSettings::upsert($pdo, 'default_storage_quota_bytes', $dq);
      $changes[] = 'default_storage_quota_bytes';
    }

    $share = !empty($_POST['share_null_user_rows']) ? '1' : '0';
    ImageKprAppSettings::upsert($pdo, 'share_null_user_rows', $share);
    $changes[] = 'share_null_user_rows';

    $maint = !empty($_POST['maintenance_mode']) ? '1' : '0';
    ImageKprAppSettings::upsert($pdo, 'maintenance_mode', $maint);
    $changes[] = 'maintenance_mode';

    $mm = trim((string) ($_POST['maintenance_message'] ?? ''));
    if ($mm === '') {
      ImageKprAppSettings::upsert($pdo, 'maintenance_message', null);
    } else {
      ImageKprAppSettings::upsert($pdo, 'maintenance_message', $mm);
    }
    $changes[] = 'maintenance_message';

    $limitKeys = [
      'max_bulk_image_ids' => ['post' => 'max_bulk_image_ids', 'min' => 1, 'max' => 100000],
      'max_duplicate_check_filenames' => ['post' => 'max_duplicate_check_filenames', 'min' => 1, 'max' => 10000],
      'max_files_per_upload_post' => ['post' => 'max_files_per_upload_post', 'min' => 1, 'max' => 500],
      'max_images_per_page' => ['post' => 'max_images_per_page', 'min' => 1, 'max' => 5000],
    ];
    foreach ($limitKeys as $dbKey => $meta) {
      $raw = trim((string) ($_POST[$meta['post']] ?? ''));
      if ($raw === '') {
        ImageKprAppSettings::upsert($pdo, $dbKey, null);
      } else {
        if (!ctype_digit($raw)) {
          $_SESSION['admin_flash'] = ['type' => 'error', 'msg' => 'Invalid number for ' . htmlspecialchars($dbKey, ENT_QUOTES, 'UTF-8') . '.'];
          imagekpr_admin_config_redirect();
        }
        $n = (int) $raw;
        if ($n < $meta['min'] || $n > $meta['max']) {
          $_SESSION['admin_flash'] = ['type' => 'error', 'msg' => $dbKey . ' must be between ' . $meta['min'] . ' and ' . $meta['max'] . '.'];
          imagekpr_admin_config_redirect();
        }
        ImageKprAppSettings::upsert($pdo, $dbKey, (string) $n);
      }
      $changes[] = $dbKey;
    }

    $rlMsg = (string) ($_POST['request_limit_user_message'] ?? '');
    if (strlen($rlMsg) > 2000) {
      $_SESSION['admin_flash'] = ['type' => 'error', 'msg' => 'Request limit message is too long (max 2000 characters).'];
      imagekpr_admin_config_redirect();
    }
    $rlMsg = trim($rlMsg);
    if ($rlMsg === '') {
      ImageKprAppSettings::upsert($pdo, 'request_limit_user_message', null);
    } else {
      ImageKprAppSettings::upsert($pdo, 'request_limit_user_message', $rlMsg);
    }
    $changes[] = 'request_limit_user_message';

    imagekpr_admin_audit_log($pdo, $actorId, 'app_settings_updated', ['keys' => $changes]);
    $_SESSION['admin_flash'] = ['type' => 'ok', 'msg' => 'Settings saved.'];
    imagekpr_admin_config_redirect();
  }

  imagekpr_admin_config_redirect();
}

$flash = $_SESSION['admin_flash'] ?? null;
unset($_SESSION['admin_flash']);

ImageKprAppSettings::bust();

$defQ = ImageKprAppSettings::get('default_storage_quota_bytes');
$shareChecked = ImageKprAppSettings::get('share_null_user_rows') === '1';
$maintChecked = ImageKprAppSettings::get('maintenance_mode') === '1';
$maintMsg = (string) (ImageKprAppSettings::get('maintenance_message') ?? '');
$reqLimitUserMsg = (string) (ImageKprAppSettings::get('request_limit_user_message') ?? '');

$pageTitle = 'Admin — Config';
$adminNavCurrent = 'config';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
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
    .admin-wrap { max-width: 800px; margin: 0 auto; padding: 1rem 1.5rem 2rem; }
    .admin-nav { display: flex; gap: 1rem; align-items: center; flex-wrap: wrap; padding: 0.75rem 0; border-bottom: 1px solid #ddd; margin-bottom: 1.25rem; }
    .admin-nav a { color: #1565c0; text-decoration: none; font-weight: 600; }
    .admin-nav a:hover { text-decoration: underline; }
    .admin-nav .admin-nav-spacer { flex: 1; min-width: 0; }
    .admin-muted { color: #666; font-size: 0.9rem; }
    .admin-badge { display: inline-block; padding: 0.2rem 0.5rem; background: #e3f2fd; border-radius: 4px; font-size: 0.75rem; color: #1565c0; }
    .admin-toast { padding: 0.65rem 1rem; border-radius: 6px; margin-bottom: 1rem; font-size: 0.9rem; }
    .admin-toast.ok { background: #e8f5e9; border: 1px solid #a5d6a7; color: #1b5e20; }
    .admin-toast.err { background: #ffebee; border: 1px solid #ef9a9a; color: #b71c1c; }
    .admin-config-section { margin: 1.75rem 0 0; }
    .admin-config-section h2 { font-size: 1.15rem; margin: 0 0 0.75rem; color: #1a1a1a; }
    .admin-config-lead { margin: 0 0 1.25rem; max-width: 40rem; }
    .admin-config-form > h2 { margin-bottom: 1rem; }
    .admin-config-panel > h2:first-child { margin-top: 0; }
    .admin-config-panel {
      background: #f8fafc;
      border: 1px solid #e2e8f0;
      border-radius: 10px;
      padding: 1.1rem 1.25rem 1.15rem;
      margin: 0 0 1rem;
    }
    .admin-config-subtitle {
      font-size: 0.72rem;
      font-weight: 700;
      letter-spacing: 0.06em;
      text-transform: uppercase;
      color: #64748b;
      margin: 0 0 0.65rem;
      padding-bottom: 0.5rem;
      border-bottom: 1px solid #e2e8f0;
    }
    .admin-config-form-actions {
      margin-top: 1.25rem;
      padding-top: 1.1rem;
      border-top: 1px solid #e2e8f0;
    }
    .admin-config-form-actions button[type="submit"] { margin-top: 0; }
    .admin-limit-block {
      padding: 0.65rem 0;
      border-bottom: 1px solid #e8eef5;
    }
    .admin-limit-block:first-of-type { padding-top: 0; }
    .admin-limit-block:last-of-type { border-bottom: none; padding-bottom: 0; }
    .admin-limit-block label.block { margin-top: 0; }
    .admin-limit-block .admin-field-help { margin-bottom: 0; }
    .admin-config-form label.block, .admin-config-section label.block { display: block; margin: 0.5rem 0; font-size: 0.9rem; }
    .admin-config-form input[type="text"], .admin-config-form input[type="number"], .admin-config-form textarea { width: 100%; max-width: 28rem; padding: 0.35rem 0.5rem; box-sizing: border-box; }
    .admin-config-form textarea { min-height: 4rem; }
    .admin-config-form button[type="submit"] { margin-top: 0.75rem; padding: 0.45rem 1.1rem; cursor: pointer; font-weight: 600; border-radius: 6px; }
    .admin-mono { font-family: ui-monospace, monospace; font-size: 0.8rem; }
    .admin-field-help { margin: 0.35rem 0 0.85rem; font-size: 0.85rem; color: #555; line-height: 1.5; max-width: 42rem; }
    .admin-field-help p { margin: 0.4rem 0 0; }
    .admin-field-help p:first-child { margin-top: 0; }
  </style>
</head>
<body>
  <div class="admin-wrap">
    <?php require __DIR__ . '/_nav.php'; ?>

    <h1>Configuration</h1>

    <?php if (is_array($flash) && !empty($flash['msg'])) { ?>
      <div class="admin-toast <?php echo ($flash['type'] ?? '') === 'error' ? 'err' : 'ok'; ?>" role="alert">
        <?php echo htmlspecialchars((string) $flash['msg'], ENT_QUOTES, 'UTF-8'); ?>
      </div>
    <?php } ?>

    <p class="admin-muted admin-config-lead">Values here are stored in <span class="admin-mono">app_settings</span> and override PHP defaults where noted. Changes are audited. Use <strong>Save settings</strong> at the bottom to apply everything in the App settings form.</p>

    <form method="post" action="config.php" class="admin-config-form">
      <?php echo imagekpr_csrf_field(); ?>
      <input type="hidden" name="form_action" value="save_app_settings">
      <h2>App settings</h2>

      <div class="admin-config-panel">
        <h3 class="admin-config-subtitle">Storage &amp; quotas</h3>
        <label class="block">Default storage quota (bytes, per user when their quota is “site default”)<br>
          <input type="text" name="default_storage_quota_bytes" value="<?php echo $defQ !== null ? htmlspecialchars((string) $defQ, ENT_QUOTES, 'UTF-8') : ''; ?>" placeholder="empty = use DEFAULT_STORAGE_QUOTA_BYTES in config.php if set">
        </label>
        <p class="admin-muted">Use <span class="admin-mono">0</span> or clear and save a separate time to mean unlimited site default. Empty field removes the DB override.</p>
      </div>

      <div class="admin-config-panel">
        <h3 class="admin-config-subtitle">Legacy library (NULL user rows)</h3>
        <label class="block"><input type="checkbox" name="share_null_user_rows" value="1" <?php echo $shareChecked ? 'checked' : ''; ?>> Share legacy images with <span class="admin-mono">user_id</span> NULL (lists them for every signed-in user)</label>
        <p class="admin-muted">If this has never been saved, the optional <span class="admin-mono">IMAGEKPR_SHARE_NULL_USER_ROWS</span> line in <span class="admin-mono">config.php</span> still applies. After you save this form, the checkbox here controls that behavior.</p>
      </div>

      <div class="admin-config-panel">
        <h3 class="admin-config-subtitle">Maintenance</h3>
        <label class="block"><input type="checkbox" name="maintenance_mode" value="1" <?php echo $maintChecked ? 'checked' : ''; ?>> Maintenance / read-only mode (main app)</label>
        <label class="block">Maintenance banner message<br>
          <textarea name="maintenance_message" placeholder="Shown to all signed-in users on the main app"><?php echo htmlspecialchars($maintMsg, ENT_QUOTES, 'UTF-8'); ?></textarea>
        </label>
        <p class="admin-muted">While enabled, uploads, inbox import/delete, deletes, renames, and tag edits are blocked via API (downloads and viewing still work). You can still use this admin area.</p>
      </div>

      <div class="admin-config-panel">
        <h3 class="admin-config-subtitle">Request limits</h3>
        <p class="admin-muted" style="margin-top:0">These caps protect the server from oversized requests. <strong>Leave a field empty</strong> to use ImageKpr’s built-in default for that setting. If you enter a value, it must be a whole number within the min–max range shown.</p>

        <label class="block" style="margin-top:1rem">Message when users hit a request limit<br>
          <textarea name="request_limit_user_message" rows="3" maxlength="2000" placeholder="Example: Free accounts are limited to {max} items per request. Contact the administrator to upgrade."><?php echo htmlspecialchars($reqLimitUserMsg, ENT_QUOTES, 'UTF-8'); ?></textarea>
        </label>
        <p class="admin-muted">Optional. If set, this text is shown (in the main app toast / API <span class="admin-mono">error</span> field) instead of short technical messages when someone exceeds bulk image-ID limits, multi-file upload size, duplicate-check filename count, or ZIP download ID count. Put the placeholder <span class="admin-mono">{max}</span> where the numeric cap for that request should appear. Clear the box and save to use the default technical messages only.</p>

      <?php
      $lims = [
        'max_bulk_image_ids' => [
          'min' => 1,
          'max' => 100000,
          'label' => 'Max image IDs per bulk request',
          'help' => [
            'This limits how many image IDs the main app may send in <strong>one API request</strong> when users work on many images at once—for example: bulk delete, download as ZIP, bulk rename, bulk tag edit, or adding/removing many images from folders.',
            'A <strong>higher</strong> number lets people act on larger selections in one step but each request uses more memory and database work. A <strong>lower</strong> number is safer on small hosting plans; users may need to repeat an action on smaller batches if they exceed the limit.',
          ],
        ],
        'max_duplicate_check_filenames' => [
          'min' => 1,
          'max' => 10000,
          'label' => 'Max filenames in duplicate check',
          'help' => [
            'The duplicate-check API compares incoming filenames against the library in one shot. This value is the <strong>maximum number of filenames</strong> allowed in that single check.',
            'Use it to prevent accidentally (or deliberately) submitting huge lists that could slow the server. Typical day-to-day use stays well below the default unless you have automated tools calling this endpoint.',
          ],
        ],
        'max_files_per_upload_post' => [
          'min' => 1,
          'max' => 500,
          'label' => 'Max files per upload POST',
          'help' => [
            'When someone uploads from the main gallery screen, they can select multiple files at once. This is the <strong>maximum number of files</strong> accepted in one upload submission (one form post).',
            'It should align with your PHP limits (<span class="admin-mono">max_file_uploads</span>, <span class="admin-mono">post_max_size</span>, <span class="admin-mono">upload_max_filesize</span>). If this number is high but PHP allows only a few files, the real limit will still be PHP’s.',
          ],
        ],
        'max_images_per_page' => [
          'min' => 1,
          'max' => 5000,
          'label' => 'Max images per page (API cap)',
          'help' => [
            'The image list API loads the gallery in pages (used for scrolling / pagination). This value is the <strong>largest “page size”</strong> a client is allowed to request in one call—for example, how many rows can be fetched at once when loading or filtering the grid.',
            'Folder and filter views that load many IDs may also rely on large page sizes internally. Raising this speeds full-library fetches but increases memory and query cost per request; lowering it encourages smaller chunks.',
          ],
        ],
      ];
      foreach ($lims as $k => $meta) {
        $v = ImageKprAppSettings::get($k);
        ?>
      <div class="admin-limit-block">
        <label class="block"><?php echo htmlspecialchars($meta['label'], ENT_QUOTES, 'UTF-8'); ?> (<?php echo (int) $meta['min']; ?>–<?php echo (int) $meta['max']; ?>)<br>
          <input type="number" name="<?php echo htmlspecialchars($k, ENT_QUOTES, 'UTF-8'); ?>" min="<?php echo (int) $meta['min']; ?>" max="<?php echo (int) $meta['max']; ?>" value="<?php echo $v !== null && $v !== '' ? htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8') : ''; ?>" placeholder="default" aria-describedby="help-<?php echo htmlspecialchars($k, ENT_QUOTES, 'UTF-8'); ?>">
        </label>
        <div class="admin-field-help" id="help-<?php echo htmlspecialchars($k, ENT_QUOTES, 'UTF-8'); ?>">
          <?php foreach ($meta['help'] as $para) { ?>
          <p><?php echo $para; ?></p>
          <?php } ?>
        </div>
      </div>
      <?php } ?>
      </div>

      <div class="admin-config-form-actions">
        <button type="submit">Save settings</button>
      </div>
    </form>

    <p class="admin-muted admin-config-section" style="margin-top:1.5rem">Email allowlist and access requests are managed on <a href="allowlist.php">Allowlist</a>.</p>
  </div>
  <?php
  require_once __DIR__ . '/../inc/footer.php';
  imagekpr_render_footer(['context' => 'dashboard']);
  ?>
</body>
</html>
