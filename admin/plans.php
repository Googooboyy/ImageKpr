<?php
ob_start();
require_once __DIR__ . '/../inc/admin.php';
require_once __DIR__ . '/../inc/plan_catalog.php';
imagekpr_require_admin_html(1, 1);

$pdo = imagekpr_pdo();
$actorId = imagekpr_user_id();

function imagekpr_admin_plans_redirect(): void
{
  header('Location: plans.php', true, 303);
  exit;
}

/**
 * @param string $raw
 * @return string[]
 */
function imagekpr_admin_plans_split_lines(string $raw): array
{
  $lines = preg_split('/\r\n|\r|\n/', $raw);
  if (!is_array($lines)) {
    return [];
  }
  $out = [];
  foreach ($lines as $line) {
    $line = trim((string) $line);
    if ($line !== '') {
      $out[] = $line;
    }
  }
  return $out;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!imagekpr_csrf_verify()) {
    $_SESSION['admin_flash'] = ['type' => 'error', 'msg' => 'Security token invalid. Try again.'];
    imagekpr_admin_plans_redirect();
  }

  $action = (string) ($_POST['form_action'] ?? '');
  if ($action === 'reset_plan_overrides') {
    ImageKprAppSettings::upsert($pdo, 'plan_catalog_overrides_json', null);
    ImageKprAppSettings::upsert($pdo, 'plan_catalog_page_json', null);
    imagekpr_admin_audit_log($pdo, $actorId, 'plan_catalog_overrides_reset');
    $_SESSION['admin_flash'] = ['type' => 'ok', 'msg' => 'Plan overrides reset to code defaults.'];
    imagekpr_admin_plans_redirect();
  }

  if ($action === 'save_plan_overrides') {
    $posted = $_POST['plan'] ?? [];
    if (!is_array($posted)) {
      $posted = [];
    }
    $postedPage = $_POST['page'] ?? [];
    if (!is_array($postedPage)) {
      $postedPage = [];
    }

    $base = imagekpr_plan_catalog();
    $keys = ['free', 'silver', 'gold', 'platinum', 'pro'];
    $overrides = [];
    $pageContent = imagekpr_plan_page_content();

    foreach ($keys as $tierKey) {
      if (!isset($base[$tierKey])) {
        continue;
      }
      $row = is_array($posted[$tierKey] ?? null) ? $posted[$tierKey] : [];
      $out = [];
      $stringFields = [
        'display_label', 'monthly_price', 'annual_price', 'price_period',
        'primary_title', 'recommended_for_title', 'included_title',
        'summary_title', 'cta_label', 'cta_href', 'commercial_note', 'tier_image_url',
      ];
      foreach ($stringFields as $field) {
        if (!array_key_exists($field, $base[$tierKey])) {
          continue;
        }
        $raw = trim((string) ($row[$field] ?? ''));
        if ($raw === '' && $base[$tierKey][$field] === null) {
          $out[$field] = null;
        } elseif ($raw === '') {
          $out[$field] = '';
        } else {
          $out[$field] = $raw;
        }
      }

      $intFields = ['upload_mb', 'storage_bytes', 'max_images', 'shared_dashboard_cap', 'video_upload_mb'];
      foreach ($intFields as $field) {
        if (!array_key_exists($field, $base[$tierKey])) {
          continue;
        }
        $raw = trim((string) ($row[$field] ?? ''));
        if ($raw === '') {
          $out[$field] = null;
          continue;
        }
        if (preg_match('/^\d+$/', $raw) !== 1) {
          $_SESSION['admin_flash'] = ['type' => 'error', 'msg' => 'Numeric field "' . $field . '" for ' . ucfirst($tierKey) . ' must be a whole number or empty.'];
          imagekpr_admin_plans_redirect();
        }
        $out[$field] = (int) $raw;
      }

      $boolFields = ['supports_mp4', 'self_serve', 'dedicated', 'show_tier_image', 'show_recommended', 'show_included'];
      foreach ($boolFields as $field) {
        if (!array_key_exists($field, $base[$tierKey])) {
          continue;
        }
        $out[$field] = !empty($row[$field]);
      }

      $listFields = ['primary_bullets', 'recommended_for_bullets', 'included_bullets', 'summary_bullets'];
      foreach ($listFields as $field) {
        if (!array_key_exists($field, $base[$tierKey])) {
          continue;
        }
        $out[$field] = imagekpr_admin_plans_split_lines((string) ($row[$field] ?? ''));
      }

      $overrides[$tierKey] = $out;
    }

    $json = json_encode($overrides, JSON_UNESCAPED_UNICODE);
    if (!is_string($json)) {
      $_SESSION['admin_flash'] = ['type' => 'error', 'msg' => 'Could not encode plan settings.'];
      imagekpr_admin_plans_redirect();
    }

    $pageOverrides = [];
    foreach (['page_title', 'page_sub_title', 'page_super_sub_title'] as $field) {
      $pageOverrides[$field] = trim((string) ($postedPage[$field] ?? ($pageContent[$field] ?? '')));
    }
    $pageJson = json_encode($pageOverrides, JSON_UNESCAPED_UNICODE);
    if (!is_string($pageJson)) {
      $_SESSION['admin_flash'] = ['type' => 'error', 'msg' => 'Could not encode pricing page header settings.'];
      imagekpr_admin_plans_redirect();
    }

    ImageKprAppSettings::upsert($pdo, 'plan_catalog_overrides_json', $json);
    ImageKprAppSettings::upsert($pdo, 'plan_catalog_page_json', $pageJson);
    imagekpr_admin_audit_log($pdo, $actorId, 'plan_catalog_overrides_saved', [
      'tier_keys' => $keys,
      'page_keys' => ['page_title', 'page_sub_title', 'page_super_sub_title'],
    ]);
    $_SESSION['admin_flash'] = ['type' => 'ok', 'msg' => 'Plan catalog settings saved.'];
    imagekpr_admin_plans_redirect();
  }
}

$flash = $_SESSION['admin_flash'] ?? null;
unset($_SESSION['admin_flash']);

$catalog = imagekpr_plan_catalog();
$pageContent = imagekpr_plan_page_content();
$pageTitle = 'Admin - Plans';
$adminNavCurrent = 'plans';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8'); ?></title>
  <link rel="stylesheet" href="../styles.css">
  <style>
    .admin-wrap { max-width: 980px; margin: 0 auto; padding: 1rem 1rem 2rem; }
    .admin-toast { padding: 0.65rem 1rem; border-radius: 6px; margin-bottom: 1rem; font-size: 0.9rem; }
    .admin-toast.ok { background: #e8f5e9; border: 1px solid #a5d6a7; color: #1b5e20; }
    .admin-toast.err { background: #ffebee; border: 1px solid #ef9a9a; color: #b71c1c; }
    .admin-muted { color: #666; font-size: 0.9rem; }
    .admin-form { display: grid; gap: 1rem; }
    .admin-tier { border: 1px solid #e2e8f0; border-radius: 10px; padding: 0.85rem 1rem 1rem; background: #f8fafc; }
    .admin-tier h2 { margin: 0 0 0.65rem; font-size: 1.1rem; }
    .admin-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(190px, 1fr)); gap: 0.6rem 0.8rem; }
    .admin-field { display: flex; flex-direction: column; gap: 0.2rem; font-size: 0.86rem; }
    .admin-field input[type="text"], .admin-field input[type="number"], .admin-field textarea {
      width: 100%;
      box-sizing: border-box;
      padding: 0.35rem 0.45rem;
      border: 1px solid #cbd5e1;
      border-radius: 6px;
      font: inherit;
      background: #fff;
    }
    .admin-field textarea { min-height: 4.8rem; resize: vertical; }
    .admin-field-help { margin: 0.2rem 0 0; font-size: 0.78rem; color: #64748b; line-height: 1.45; }
    .admin-checkboxes { display: flex; gap: 1rem; flex-wrap: wrap; margin-top: 0.3rem; font-size: 0.86rem; }
    .admin-actions { display: flex; gap: 0.65rem; flex-wrap: wrap; margin-top: 0.5rem; }
    .admin-actions button { padding: 0.45rem 0.9rem; font-weight: 600; cursor: pointer; }
  </style>
</head>
<body>
  <div class="admin-wrap">
    <?php require __DIR__ . '/_nav.php'; ?>

    <h1>Plan Catalog Editor</h1>
    <p class="admin-muted">Edit pricing content and tier limits here. Changes are saved in <code>app_settings</code> and override defaults in <code>inc/plan_catalog.php</code>. Simple formatting is supported with <code>**bold**</code>, <code>*italics*</code>, <code>~~strikethrough~~</code>, and <code>__underline__</code>.</p>

    <?php if (is_array($flash) && !empty($flash['msg'])) { ?>
      <div class="admin-toast <?php echo ($flash['type'] ?? '') === 'error' ? 'err' : 'ok'; ?>" role="alert">
        <?php echo htmlspecialchars((string) $flash['msg'], ENT_QUOTES, 'UTF-8'); ?>
      </div>
    <?php } ?>

    <form method="post" action="plans.php" class="admin-form">
      <?php echo imagekpr_csrf_field(); ?>
      <input type="hidden" name="form_action" value="save_plan_overrides">

      <section class="admin-tier">
        <h2>Pricing Page Header</h2>
        <div class="admin-grid">
          <label class="admin-field">
            <span>Page Title</span>
            <input type="text" name="page[page_title]" value="<?php echo htmlspecialchars((string) ($pageContent['page_title'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
          </label>
          <label class="admin-field" style="grid-column: 1 / -1;">
            <span>Page Sub-Title</span>
            <textarea name="page[page_sub_title]"><?php echo htmlspecialchars((string) ($pageContent['page_sub_title'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></textarea>
            <span class="admin-field-help">Supports <code>**bold**</code>, <code>*italics*</code>, <code>~~strikethrough~~</code>, <code>__underline__</code>. Press Enter for a line break; leave a blank line for a new paragraph.</span>
          </label>
          <label class="admin-field" style="grid-column: 1 / -1;">
            <span>Page Super Sub-Title</span>
            <textarea name="page[page_super_sub_title]"><?php echo htmlspecialchars((string) ($pageContent['page_super_sub_title'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></textarea>
            <span class="admin-field-help">Supports the same formatting and paragraph behavior as the Page Sub-Title field.</span>
          </label>
        </div>
      </section>

      <?php foreach (['free', 'silver', 'gold', 'platinum', 'pro'] as $tierKey) {
        $row = $catalog[$tierKey];
        ?>
      <section class="admin-tier">
        <h2><?php echo htmlspecialchars((string) ($row['display_label'] ?? $row['label']), ENT_QUOTES, 'UTF-8'); ?> (<?php echo htmlspecialchars($tierKey, ENT_QUOTES, 'UTF-8'); ?>)</h2>
        <div class="admin-grid">
          <?php
          $textFields = ['display_label', 'monthly_price', 'annual_price', 'price_period', 'primary_title', 'recommended_for_title', 'included_title', 'summary_title', 'cta_label', 'cta_href', 'commercial_note', 'tier_image_url'];
          foreach ($textFields as $field) {
            if (!array_key_exists($field, $row)) {
              continue;
            }
            ?>
          <label class="admin-field">
            <span><?php echo htmlspecialchars($field, ENT_QUOTES, 'UTF-8'); ?></span>
            <input type="text" name="plan[<?php echo htmlspecialchars($tierKey, ENT_QUOTES, 'UTF-8'); ?>][<?php echo htmlspecialchars($field, ENT_QUOTES, 'UTF-8'); ?>]" value="<?php echo htmlspecialchars((string) ($row[$field] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
            <?php if ($field === 'tier_image_url') { ?>
            <span class="admin-field-help">Optional image URL. Recommended: 640x400 px or another 16:10 image. If left empty, the pricing page uses the placeholder graphic.</span>
            <?php } ?>
          </label>
          <?php } ?>

          <?php
          $numFields = ['upload_mb', 'storage_bytes', 'max_images', 'shared_dashboard_cap', 'video_upload_mb'];
          foreach ($numFields as $field) {
            if (!array_key_exists($field, $row)) {
              continue;
            }
            ?>
          <label class="admin-field">
            <span><?php echo htmlspecialchars($field, ENT_QUOTES, 'UTF-8'); ?></span>
            <input type="number" min="0" step="1" name="plan[<?php echo htmlspecialchars($tierKey, ENT_QUOTES, 'UTF-8'); ?>][<?php echo htmlspecialchars($field, ENT_QUOTES, 'UTF-8'); ?>]" value="<?php echo $row[$field] === null ? '' : htmlspecialchars((string) $row[$field], ENT_QUOTES, 'UTF-8'); ?>">
          </label>
          <?php } ?>

          <?php
          $listFields = ['primary_bullets', 'recommended_for_bullets', 'included_bullets', 'summary_bullets'];
          foreach ($listFields as $field) {
            if (!array_key_exists($field, $row)) {
              continue;
            }
            if ($field === 'primary_bullets' && !empty($row['self_serve'])) {
              ?>
          <div class="admin-field" style="grid-column: 1 / -1;">
            <span><?php echo htmlspecialchars($field, ENT_QUOTES, 'UTF-8'); ?></span>
            <span class="admin-field-help">Auto-generated from the numeric plan fields above: <code>upload_mb</code>, <code>storage_bytes</code>, <code>max_images</code>, <code>shared_dashboard_cap</code>, and <code>video_upload_mb</code>. Change those values to update both the pricing page and the admin dashboard consistently.</span>
          </div>
              <?php
              continue;
            }
            $value = is_array($row[$field]) ? implode("\n", array_map('strval', $row[$field])) : '';
            ?>
          <label class="admin-field" style="grid-column: 1 / -1;">
            <span><?php echo htmlspecialchars($field, ENT_QUOTES, 'UTF-8'); ?> (one line per bullet)</span>
            <textarea name="plan[<?php echo htmlspecialchars($tierKey, ENT_QUOTES, 'UTF-8'); ?>][<?php echo htmlspecialchars($field, ENT_QUOTES, 'UTF-8'); ?>]"><?php echo htmlspecialchars($value, ENT_QUOTES, 'UTF-8'); ?></textarea>
          </label>
          <?php } ?>
        </div>

        <div class="admin-checkboxes">
          <?php foreach (['supports_mp4', 'self_serve', 'dedicated', 'show_tier_image', 'show_recommended', 'show_included'] as $field) {
            if (!array_key_exists($field, $row)) {
              continue;
            }
            ?>
          <label><input type="checkbox" name="plan[<?php echo htmlspecialchars($tierKey, ENT_QUOTES, 'UTF-8'); ?>][<?php echo htmlspecialchars($field, ENT_QUOTES, 'UTF-8'); ?>]" value="1" <?php echo !empty($row[$field]) ? 'checked' : ''; ?>> <?php echo htmlspecialchars($field, ENT_QUOTES, 'UTF-8'); ?></label>
          <?php } ?>
        </div>
      </section>
      <?php } ?>

      <div class="admin-actions">
        <button type="submit">Save plan catalog</button>
      </div>
    </form>

    <form method="post" action="plans.php" style="margin-top:0.75rem;">
      <?php echo imagekpr_csrf_field(); ?>
      <input type="hidden" name="form_action" value="reset_plan_overrides">
      <button type="submit">Reset to code defaults</button>
    </form>
  </div>
  <?php
  require_once __DIR__ . '/../inc/footer.php';
  imagekpr_render_footer(['context' => 'dashboard']);
  ?>
</body>
</html>
