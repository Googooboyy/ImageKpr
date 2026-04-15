<?php
ob_start();
require_once __DIR__ . '/../inc/admin.php';
imagekpr_require_admin_html(1, 1);

$pdo = imagekpr_pdo();
$actorId = imagekpr_user_id();

function imagekpr_admin_allowlist_redirect(): void
{
  header('Location: allowlist.php', true, 303);
  exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!imagekpr_csrf_verify()) {
    $_SESSION['admin_flash'] = ['type' => 'error', 'msg' => 'Security token invalid. Try again.'];
    imagekpr_admin_allowlist_redirect();
  }
  $act = (string) ($_POST['form_action'] ?? '');

  if ($act === 'save_accept_requests') {
    $open = !empty($_POST['accept_access_requests']) ? '1' : '0';
    ImageKprAppSettings::upsert($pdo, 'accept_access_requests', $open);
    imagekpr_admin_audit_log($pdo, $actorId, 'access_requests_toggle', ['accept' => $open]);
    $_SESSION['admin_flash'] = ['type' => 'ok', 'msg' => $open === '1' ? 'The site is now accepting access requests from the public form.' : 'The site is no longer accepting new access requests.'];
    imagekpr_admin_allowlist_redirect();
  }

  if ($act === 'allowlist_add') {
    $em = strtolower(trim((string) ($_POST['allow_email'] ?? '')));
    if ($em === '' || !filter_var($em, FILTER_VALIDATE_EMAIL)) {
      $_SESSION['admin_flash'] = ['type' => 'error', 'msg' => 'Enter a valid email address.'];
      imagekpr_admin_allowlist_redirect();
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
    imagekpr_admin_allowlist_redirect();
  }

  if ($act === 'allowlist_delete') {
    $aid = (int) ($_POST['allowlist_id'] ?? 0);
    if ($aid < 1) {
      $_SESSION['admin_flash'] = ['type' => 'error', 'msg' => 'Invalid allowlist entry.'];
      imagekpr_admin_allowlist_redirect();
    }
    $st = $pdo->prepare('SELECT email FROM email_allowlist WHERE id = ? LIMIT 1');
    $st->execute([$aid]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    if ($row === false) {
      $_SESSION['admin_flash'] = ['type' => 'error', 'msg' => 'Allowlist entry not found.'];
      imagekpr_admin_allowlist_redirect();
    }
    $pdo->prepare('DELETE FROM email_allowlist WHERE id = ?')->execute([$aid]);
    imagekpr_admin_audit_log($pdo, $actorId, 'allowlist_delete', ['id' => $aid, 'email' => $row['email']]);
    $_SESSION['admin_flash'] = ['type' => 'ok', 'msg' => 'Removed ' . $row['email'] . ' from the allowlist.'];
    imagekpr_admin_allowlist_redirect();
  }

  if ($act === 'access_request_approve') {
    $rid = (int) ($_POST['request_id'] ?? 0);
    if ($rid < 1) {
      $_SESSION['admin_flash'] = ['type' => 'error', 'msg' => 'Invalid request.'];
      imagekpr_admin_allowlist_redirect();
    }
    $st = $pdo->prepare('SELECT email FROM email_access_requests WHERE id = ? LIMIT 1');
    $st->execute([$rid]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    if ($row === false) {
      $_SESSION['admin_flash'] = ['type' => 'error', 'msg' => 'That request was already handled or removed.'];
      imagekpr_admin_allowlist_redirect();
    }
    $em = (string) $row['email'];
    try {
      $pdo->prepare('INSERT IGNORE INTO email_allowlist (email) VALUES (?)')->execute([$em]);
      $pdo->prepare('DELETE FROM email_access_requests WHERE id = ?')->execute([$rid]);
    } catch (Throwable $e) {
      $_SESSION['admin_flash'] = ['type' => 'error', 'msg' => 'Could not approve that request.'];
      imagekpr_admin_allowlist_redirect();
    }
    imagekpr_admin_audit_log($pdo, $actorId, 'access_request_approved', ['email' => $em, 'request_id' => $rid]);
    $_SESSION['admin_flash'] = ['type' => 'ok', 'msg' => 'Approved ' . $em . '. They can sign in with Google; they will appear on the Dashboard after their first login.'];
    imagekpr_admin_allowlist_redirect();
  }

  if ($act === 'access_request_dismiss') {
    $rid = (int) ($_POST['request_id'] ?? 0);
    if ($rid < 1) {
      $_SESSION['admin_flash'] = ['type' => 'error', 'msg' => 'Invalid request.'];
      imagekpr_admin_allowlist_redirect();
    }
    $st = $pdo->prepare('SELECT email FROM email_access_requests WHERE id = ? LIMIT 1');
    $st->execute([$rid]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    if ($row === false) {
      $_SESSION['admin_flash'] = ['type' => 'error', 'msg' => 'That request was already removed.'];
      imagekpr_admin_allowlist_redirect();
    }
    $pdo->prepare('DELETE FROM email_access_requests WHERE id = ?')->execute([$rid]);
    imagekpr_admin_audit_log($pdo, $actorId, 'access_request_dismissed', ['email' => $row['email'], 'request_id' => $rid]);
    $_SESSION['admin_flash'] = ['type' => 'ok', 'msg' => 'Dismissed the request for ' . $row['email'] . ' (not added to the allowlist).'];
    imagekpr_admin_allowlist_redirect();
  }

  imagekpr_admin_allowlist_redirect();
}

$flash = $_SESSION['admin_flash'] ?? null;
unset($_SESSION['admin_flash']);

ImageKprAppSettings::bust();
$acceptRequestsChecked = imagekpr_accept_access_requests_enabled();

$allowRows = $pdo->query('SELECT id, email, created_at FROM email_allowlist ORDER BY email ASC')->fetchAll(PDO::FETCH_ASSOC);
$allowCount = (int) $pdo->query('SELECT COUNT(*) FROM email_allowlist')->fetchColumn();
$allowOpen = $allowCount === 0;

try {
  $pendingRows = $pdo->query('SELECT id, email, note, created_at FROM email_access_requests ORDER BY created_at ASC')->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
  $pendingRows = [];
}

$pageTitle = 'Admin — Email allowlist';
$adminNavCurrent = 'allowlist';
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
    .admin-config-panel {
      background: #f8fafc;
      border: 1px solid #e2e8f0;
      border-radius: 10px;
      padding: 1.1rem 1.25rem 1.15rem;
      margin: 0 0 1rem;
    }
    .admin-config-section label.block { display: block; margin: 0.5rem 0; font-size: 0.9rem; }
    .admin-config-section input[type="email"], .admin-config-section textarea { width: 100%; max-width: 28rem; padding: 0.35rem 0.5rem; box-sizing: border-box; }
    .admin-config-section button[type="submit"] { margin-top: 0.75rem; margin-right: 0.5rem; padding: 0.45rem 1.1rem; cursor: pointer; font-weight: 600; border-radius: 6px; }
    table.allowlist { width: 100%; border-collapse: collapse; font-size: 0.85rem; margin-top: 0.5rem; }
    table.allowlist th, table.allowlist td { padding: 0.4rem 0.5rem; border-bottom: 1px solid #eee; text-align: left; vertical-align: top; }
    .admin-mono { font-family: ui-monospace, monospace; font-size: 0.8rem; }
    .admin-btn-danger { background: #c62828; color: #fff; border: 1px solid #8e0000; cursor: pointer; border-radius: 4px; padding: 0.25rem 0.6rem; font-size: 0.8rem; }
    .admin-btn-danger:hover { background: #b71c1c; }
    .admin-btn-secondary { background: #f5f5f5; color: #333; border: 1px solid #ccc; cursor: pointer; border-radius: 4px; padding: 0.25rem 0.6rem; font-size: 0.8rem; }
    .admin-btn-approve { background: #2e7d32; color: #fff; border: 1px solid #1b5e20; cursor: pointer; border-radius: 4px; padding: 0.25rem 0.6rem; font-size: 0.8rem; }
    .admin-note-cell { max-width: 18rem; word-break: break-word; }
    .admin-toggle-form button[type="submit"] { margin-top: 0.5rem; }
    .admin-collapsible-head {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 0.75rem;
      margin-bottom: 0.6rem;
    }
    .admin-collapsible-head h2 { margin: 0; }
    .admin-collapsible-toggle { padding: 0.3rem 0.65rem; cursor: pointer; font-size: 0.78rem; }
    .admin-collapsible.is-hidden .admin-collapsible-body { display: none; }
  </style>
</head>
<body>
  <div class="admin-wrap">
    <?php require __DIR__ . '/_nav.php'; ?>

    <h1>Email allowlist</h1>

    <?php if (is_array($flash) && !empty($flash['msg'])) { ?>
      <div class="admin-toast <?php echo ($flash['type'] ?? '') === 'error' ? 'err' : 'ok'; ?>" role="alert">
        <?php echo htmlspecialchars((string) $flash['msg'], ENT_QUOTES, 'UTF-8'); ?>
      </div>
    <?php } ?>

    <section class="admin-config-section admin-config-panel admin-collapsible" aria-labelledby="req-toggle-heading" data-collapsible-key="allowlist_access_requests">
      <div class="admin-collapsible-head">
        <h2 id="req-toggle-heading">Access requests</h2>
        <button type="button" class="admin-collapsible-toggle" aria-expanded="false">Show</button>
      </div>
      <div class="admin-collapsible-body">
        <p class="admin-muted">When enabled, visitors can submit their email from the main sign-in page. If the allowlist is not empty, anyone who signs in with Google while not yet allowed is also added here automatically (note: &quot;Requested via Google sign-in&quot;). Pending rows appear below; approving adds the address to the allowlist and removes the request.</p>
        <form method="post" action="allowlist.php" class="admin-toggle-form">
          <?php echo imagekpr_csrf_field(); ?>
          <input type="hidden" name="form_action" value="save_accept_requests">
          <label class="block"><input type="checkbox" name="accept_access_requests" value="1" <?php echo $acceptRequestsChecked ? 'checked' : ''; ?>> Accept new access requests from the public form</label>
          <button type="submit">Save</button>
        </form>
      </div>
    </section>

    <section class="admin-config-section admin-config-panel admin-collapsible" aria-labelledby="pending-heading" data-collapsible-key="allowlist_pending_requests">
      <div class="admin-collapsible-head">
        <h2 id="pending-heading">Pending requests</h2>
        <button type="button" class="admin-collapsible-toggle" aria-expanded="false">Show</button>
      </div>
      <div class="admin-collapsible-body">
        <?php if (empty($pendingRows)) { ?>
          <p class="admin-muted">No pending requests.</p>
        <?php } else { ?>
          <table class="allowlist">
            <thead><tr><th>Email</th><th>Note</th><th>Requested</th><th></th></tr></thead>
            <tbody>
              <?php foreach ($pendingRows as $pr) {
                $nid = (int) $pr['id'];
                $pnote = (string) ($pr['note'] ?? '');
                ?>
              <tr>
                <td class="admin-mono"><?php echo htmlspecialchars((string) $pr['email'], ENT_QUOTES, 'UTF-8'); ?></td>
                <td class="admin-note-cell admin-muted"><?php echo $pnote !== '' ? htmlspecialchars($pnote, ENT_QUOTES, 'UTF-8') : '—'; ?></td>
                <td class="admin-muted"><?php echo htmlspecialchars((string) $pr['created_at'], ENT_QUOTES, 'UTF-8'); ?></td>
                <td>
                  <form method="post" action="allowlist.php" style="display:inline">
                    <?php echo imagekpr_csrf_field(); ?>
                    <input type="hidden" name="form_action" value="access_request_approve">
                    <input type="hidden" name="request_id" value="<?php echo $nid; ?>">
                    <button type="submit" class="admin-btn-approve">Approve</button>
                  </form>
                  <form method="post" action="allowlist.php" style="display:inline" onsubmit="return confirm('Dismiss this request without adding to the allowlist?');">
                    <?php echo imagekpr_csrf_field(); ?>
                    <input type="hidden" name="form_action" value="access_request_dismiss">
                    <input type="hidden" name="request_id" value="<?php echo $nid; ?>">
                    <button type="submit" class="admin-btn-secondary">Dismiss</button>
                  </form>
                </td>
              </tr>
              <?php } ?>
            </tbody>
          </table>
        <?php } ?>
      </div>
    </section>

    <section class="admin-config-section admin-config-panel admin-collapsible" aria-labelledby="allowlist-heading" data-collapsible-key="allowlist_allowed_emails">
      <div class="admin-collapsible-head">
        <h2 id="allowlist-heading">Allowed emails</h2>
        <button type="button" class="admin-collapsible-toggle" aria-expanded="false">Show</button>
      </div>
      <div class="admin-collapsible-body">
        <?php if ($allowOpen) { ?>
          <p class="admin-muted"><strong>Open signup:</strong> the allowlist is empty, so any Google account can sign in (subject to OAuth). Add emails below to restrict access.</p>
        <?php } else { ?>
          <p class="admin-muted"><strong>Restricted:</strong> only listed emails may sign in (<?php echo (int) $allowCount; ?> entr<?php echo $allowCount === 1 ? 'y' : 'ies'; ?>).</p>
        <?php } ?>

        <form method="post" action="allowlist.php" style="margin:0.75rem 0">
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
                  <form method="post" action="allowlist.php" style="display:inline" onsubmit="return confirm('Remove this email from the allowlist?');">
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
      </div>
    </section>
  </div>
  <script>
    (function () {
      var nodes = document.querySelectorAll('.admin-collapsible[data-collapsible-key]');
      if (!nodes.length) return;
      nodes.forEach(function (wrap) {
        var keyRaw = wrap.getAttribute('data-collapsible-key');
        var btn = wrap.querySelector('.admin-collapsible-toggle');
        if (!keyRaw || !btn) return;
        var storageKey = 'imagekpr_admin_allowlist_' + keyRaw + '_hidden';
        var setState = function (hidden) {
          wrap.classList.toggle('is-hidden', hidden);
          btn.setAttribute('aria-expanded', hidden ? 'false' : 'true');
          btn.textContent = hidden ? 'Show' : 'Hide';
        };

        try {
          var saved = window.localStorage.getItem(storageKey);
          setState(saved === null ? true : saved === '1');
        } catch (e) {
          setState(true);
        }

        btn.addEventListener('click', function () {
          var hidden = !wrap.classList.contains('is-hidden');
          setState(hidden);
          try {
            window.localStorage.setItem(storageKey, hidden ? '1' : '0');
          } catch (e) {
            // Ignore storage errors and keep current UI state.
          }
        });
      });
    })();
  </script>
  <?php
  require_once __DIR__ . '/../inc/footer.php';
  imagekpr_render_footer(['context' => 'dashboard']);
  ?>
</body>
</html>
