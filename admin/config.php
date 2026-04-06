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

    imagekpr_admin_audit_log($pdo, $actorId, 'app_settings_updated', ['keys' => $changes]);
    $_SESSION['admin_flash'] = ['type' => 'ok', 'msg' => 'Settings saved.'];
    imagekpr_admin_config_redirect();
  }

  if ($act === 'allowlist_add') {
    $em = strtolower(trim((string) ($_POST['allow_email'] ?? '')));
    if ($em === '' || !filter_var($em, FILTER_VALIDATE_EMAIL)) {
      $_SESSION['admin_flash'] = ['type' => 'error', 'msg' => 'Enter a valid email address.'];
      imagekpr_admin_config_redirect();
    }
    try {
      $ins = $pdo->prepare('INSERT INTO email_allowlist (email) VALUES (?)');
      $ins->execute([$em]);
      imagekpr_admin_audit_log($pdo, $actorId, 'allowlist_add', ['email' => $em]);
      $_SESSION['admin_flash'] = ['type' => 'ok', 'msg' => 'Added ' . $em . ' to the allowlist.'];
    } catch (PDOException $e) {
      if ((int) $e->errorInfo[1] === 1062) {
        $_SESSION['admin_flash'] = ['type' => 'error', 'msg' => 'That email is already on the allowlist.'];
      } else {
        $_SESSION['admin_flash'] = ['type' => 'error', 'msg' => 'Could not add email.'];
      }
    }
    imagekpr_admin_config_redirect();
  }

  if ($act === 'allowlist_delete') {
    $aid = (int) ($_POST['allowlist_id'] ?? 0);
    if ($aid < 1) {
      $_SESSION['admin_flash'] = ['type' => 'error', 'msg' => 'Invalid allowlist entry.'];
      imagekpr_admin_config_redirect();
    }
    $st = $pdo->prepare('SELECT email FROM email_allowlist WHERE id = ? LIMIT 1');
    $st->execute([$aid]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    if ($row === false) {
      $_SESSION['admin_flash'] = ['type' => 'error', 'msg' => 'Allowlist entry not found.'];
      imagekpr_admin_config_redirect();
    }
    $pdo->prepare('DELETE FROM email_allowlist WHERE id = ?')->execute([$aid]);
    imagekpr_admin_audit_log($pdo, $actorId, 'allowlist_delete', ['id' => $aid, 'email' => $row['email']]);
    $_SESSION['admin_flash'] = ['type' => 'ok', 'msg' => 'Removed ' . $row['email'] . ' from the allowlist.'];
    imagekpr_admin_config_redirect();
  }

  imagekpr_admin_config_redirect();
}

$flash = $_SESSION['admin_flash'] ?? null;
unset($_SESSION['admin_flash']);

ImageKprAppSettings::bust();
$allowRows = $pdo->query('SELECT id, email, created_at FROM email_allowlist ORDER BY email ASC')->fetchAll(PDO::FETCH_ASSOC);
$allowCount = (int) $pdo->query('SELECT COUNT(*) FROM email_allowlist')->fetchColumn();
$allowOpen = $allowCount === 0;

$defQ = ImageKprAppSettings::get('default_storage_quota_bytes');
$shareChecked = ImageKprAppSettings::get('share_null_user_rows') === '1';
$maintChecked = ImageKprAppSettings::get('maintenance_mode') === '1';
$maintMsg = (string) (ImageKprAppSettings::get('maintenance_message') ?? '');

$pageTitle = 'Admin — Config';
$adminNavCurrent = 'config';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8'); ?></title>
  <link rel="stylesheet" href="../styles.css">
  <style>
    .admin-wrap { max-width: 720px; margin: 0 auto; padding: 1rem 1.5rem 2rem; }
    .admin-nav { display: flex; gap: 1rem; align-items: center; flex-wrap: wrap; padding: 0.75rem 0; border-bottom: 1px solid #ddd; margin-bottom: 1.25rem; }
    .admin-nav a { color: #1565c0; text-decoration: none; font-weight: 600; }
    .admin-nav a:hover { text-decoration: underline; }
    .admin-nav .admin-nav-spacer { flex: 1; min-width: 0; }
    .admin-muted { color: #666; font-size: 0.9rem; }
    .admin-badge { display: inline-block; padding: 0.2rem 0.5rem; background: #e3f2fd; border-radius: 4px; font-size: 0.75rem; color: #1565c0; }
    .admin-toast { padding: 0.65rem 1rem; border-radius: 6px; margin-bottom: 1rem; font-size: 0.9rem; }
    .admin-toast.ok { background: #e8f5e9; border: 1px solid #a5d6a7; color: #1b5e20; }
    .admin-toast.err { background: #ffebee; border: 1px solid #ef9a9a; color: #b71c1c; }
    .admin-config-section { margin: 1.5rem 0; padding-bottom: 1.25rem; border-bottom: 1px solid #eee; }
    .admin-config-section h2 { font-size: 1.05rem; margin: 0 0 0.5rem; }
    .admin-config-section label.block { display: block; margin: 0.5rem 0; font-size: 0.9rem; }
    .admin-config-section input[type="text"], .admin-config-section input[type="number"], .admin-config-section textarea { width: 100%; max-width: 28rem; padding: 0.35rem 0.5rem; box-sizing: border-box; }
    .admin-config-section textarea { min-height: 4rem; }
    .admin-config-section button[type="submit"] { margin-top: 0.75rem; padding: 0.4rem 1rem; cursor: pointer; font-weight: 600; }
    table.allowlist { width: 100%; border-collapse: collapse; font-size: 0.85rem; margin-top: 0.5rem; }
    table.allowlist th, table.allowlist td { padding: 0.4rem 0.5rem; border-bottom: 1px solid #eee; text-align: left; }
    .admin-mono { font-family: ui-monospace, monospace; font-size: 0.8rem; }
    .admin-btn-danger { background: #c62828; color: #fff; border: 1px solid #8e0000; cursor: pointer; border-radius: 4px; }
    .admin-btn-danger:hover { background: #b71c1c; }
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

    <p class="admin-muted">Values here are stored in <span class="admin-mono">app_settings</span> and override PHP defaults where noted. Changes are audited.</p>

    <form method="post" action="config.php" class="admin-config-section">
      <?php echo imagekpr_csrf_field(); ?>
      <input type="hidden" name="form_action" value="save_app_settings">
      <h2>App settings</h2>

      <label class="block">Default storage quota (bytes, per user when their quota is “site default”)<br>
        <input type="text" name="default_storage_quota_bytes" value="<?php echo $defQ !== null ? htmlspecialchars((string) $defQ, ENT_QUOTES, 'UTF-8') : ''; ?>" placeholder="empty = use DEFAULT_STORAGE_QUOTA_BYTES in config.php if set">
      </label>
      <p class="admin-muted">Use <span class="admin-mono">0</span> or clear and save a separate time to mean unlimited site default. Empty field removes the DB override.</p>

      <label class="block"><input type="checkbox" name="share_null_user_rows" value="1" <?php echo $shareChecked ? 'checked' : ''; ?>> Share legacy images with <span class="admin-mono">user_id</span> NULL (lists them for every signed-in user)</label>
      <p class="admin-muted">If this has never been saved, the optional <span class="admin-mono">IMAGEKPR_SHARE_NULL_USER_ROWS</span> line in <span class="admin-mono">config.php</span> still applies. After you save this form, the checkbox here controls that behavior.</p>

      <label class="block"><input type="checkbox" name="maintenance_mode" value="1" <?php echo $maintChecked ? 'checked' : ''; ?>> Maintenance / read-only mode (main app)</label>
      <label class="block">Maintenance banner message<br>
        <textarea name="maintenance_message" placeholder="Shown to all signed-in users on the main app"><?php echo htmlspecialchars($maintMsg, ENT_QUOTES, 'UTF-8'); ?></textarea>
      </label>
      <p class="admin-muted">While enabled, uploads, inbox import/delete, deletes, renames, and tag edits are blocked via API (downloads and viewing still work). You can still use this admin area.</p>

      <h2 style="margin-top:1.25rem">Request limits</h2>
      <p class="admin-muted">Empty = use built-in defaults. All must be positive integers within the allowed range.</p>
      <?php
      $lims = [
        'max_bulk_image_ids' => [1, 100000, 'Max image IDs per bulk request'],
        'max_duplicate_check_filenames' => [1, 10000, 'Max filenames in duplicate check'],
        'max_files_per_upload_post' => [1, 500, 'Max files per upload POST'],
        'max_images_per_page' => [1, 5000, 'Max images per page (API cap)'],
      ];
      foreach ($lims as $k => $meta) {
        $v = ImageKprAppSettings::get($k);
        ?>
      <label class="block"><?php echo htmlspecialchars($meta[2], ENT_QUOTES, 'UTF-8'); ?> (<?php echo (int) $meta[0]; ?>–<?php echo (int) $meta[1]; ?>)<br>
        <input type="number" name="<?php echo htmlspecialchars($k, ENT_QUOTES, 'UTF-8'); ?>" min="<?php echo (int) $meta[0]; ?>" max="<?php echo (int) $meta[1]; ?>" value="<?php echo $v !== null && $v !== '' ? htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8') : ''; ?>" placeholder="default">
      </label>
      <?php } ?>

      <button type="submit">Save settings</button>
    </form>

    <section class="admin-config-section">
      <h2>Email allowlist</h2>
      <?php if ($allowOpen) { ?>
        <p class="admin-muted"><strong>Open signup:</strong> the allowlist is empty, so any Google account can sign in (subject to OAuth). Add emails below to restrict access.</p>
      <?php } else { ?>
        <p class="admin-muted"><strong>Restricted:</strong> only listed emails may sign in (<?php echo (int) $allowCount; ?> entr<?php echo $allowCount === 1 ? 'y' : 'ies'; ?>).</p>
      <?php } ?>

      <form method="post" action="config.php" style="margin:0.75rem 0">
        <?php echo imagekpr_csrf_field(); ?>
        <input type="hidden" name="form_action" value="allowlist_add">
        <label>Add email <input type="email" name="allow_email" required placeholder="user@example.com" style="min-width:16rem;padding:0.35rem"></label>
        <button type="submit">Add</button>
      </form>

      <?php if (empty($allowRows)) { ?>
        <p class="admin-muted">No addresses in the list.</p>
      <?php } else { ?>
        <table class="allowlist">
          <thead><tr><th>Email</th><th>Added</th><th></th></tr></thead>
          <tbody>
            <?php foreach ($allowRows as $ar) { ?>
            <tr>
              <td class="admin-mono"><?php echo htmlspecialchars((string) $ar['email'], ENT_QUOTES, 'UTF-8'); ?></td>
              <td class="admin-muted"><?php echo htmlspecialchars((string) $ar['created_at'], ENT_QUOTES, 'UTF-8'); ?></td>
              <td>
                <form method="post" action="config.php" style="display:inline" onsubmit="return confirm('Remove this email from the allowlist?');">
                  <?php echo imagekpr_csrf_field(); ?>
                  <input type="hidden" name="form_action" value="allowlist_delete">
                  <input type="hidden" name="allowlist_id" value="<?php echo (int) $ar['id']; ?>">
                  <button type="submit" class="admin-btn-danger" style="padding:0.2rem 0.5rem;font-size:0.8rem;cursor:pointer">Remove</button>
                </form>
              </td>
            </tr>
            <?php } ?>
          </tbody>
        </table>
      <?php } ?>
    </section>
  </div>
</body>
</html>
