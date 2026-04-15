<?php
ob_start();
require_once __DIR__ . '/../inc/admin.php';
imagekpr_require_admin_html(1, 1);

$pdo = imagekpr_pdo();
$actorId = imagekpr_user_id();

function imagekpr_admin_updates_redirect(): void
{
  header('Location: updates.php', true, 303);
  exit;
}

function imagekpr_admin_updates_slugify(string $raw): string
{
  $raw = strtolower(trim($raw));
  $raw = preg_replace('/[^a-z0-9]+/', '-', $raw) ?? '';
  $raw = trim($raw, '-');
  return substr($raw, 0, 191);
}

try {
  $pdo->query('SELECT 1 FROM updates_posts LIMIT 1');
  $updatesTableReady = true;
} catch (Throwable $e) {
  $updatesTableReady = false;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $updatesTableReady) {
  if (!imagekpr_csrf_verify()) {
    $_SESSION['admin_flash'] = ['type' => 'error', 'msg' => 'Security token invalid. Try again.'];
    imagekpr_admin_updates_redirect();
  }
  $action = (string) ($_POST['form_action'] ?? '');

  if ($action === 'delete_post') {
    $id = (int) ($_POST['id'] ?? 0);
    if ($id < 1) {
      $_SESSION['admin_flash'] = ['type' => 'error', 'msg' => 'Invalid post id.'];
      imagekpr_admin_updates_redirect();
    }
    $st = $pdo->prepare('SELECT id, title, slug FROM updates_posts WHERE id = ? LIMIT 1');
    $st->execute([$id]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    if ($row === false) {
      $_SESSION['admin_flash'] = ['type' => 'error', 'msg' => 'Post not found.'];
      imagekpr_admin_updates_redirect();
    }
    $pdo->prepare('DELETE FROM updates_posts WHERE id = ?')->execute([$id]);
    imagekpr_admin_audit_log($pdo, $actorId, 'updates_post_deleted', ['id' => $id, 'slug' => $row['slug'], 'title' => $row['title']]);
    $_SESSION['admin_flash'] = ['type' => 'ok', 'msg' => 'Update deleted.'];
    imagekpr_admin_updates_redirect();
  }

  if ($action === 'publish_post') {
    $id = (int) ($_POST['id'] ?? 0);
    if ($id < 1) {
      $_SESSION['admin_flash'] = ['type' => 'error', 'msg' => 'Invalid post id.'];
      imagekpr_admin_updates_redirect();
    }
    $st = $pdo->prepare('SELECT id, title, slug, status FROM updates_posts WHERE id = ? LIMIT 1');
    $st->execute([$id]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    if ($row === false) {
      $_SESSION['admin_flash'] = ['type' => 'error', 'msg' => 'Post not found.'];
      imagekpr_admin_updates_redirect();
    }
    if ((string) $row['status'] !== 'draft') {
      $_SESSION['admin_flash'] = ['type' => 'error', 'msg' => 'Only drafts can be published from this button.'];
      imagekpr_admin_updates_redirect();
    }
    $pdo->prepare('UPDATE updates_posts SET status = "published", updated_at = NOW() WHERE id = ?')->execute([$id]);
    imagekpr_admin_audit_log($pdo, $actorId, 'updates_post_published', ['id' => $id, 'slug' => $row['slug'], 'title' => $row['title']]);
    $_SESSION['admin_flash'] = ['type' => 'ok', 'msg' => 'Update published.'];
    imagekpr_admin_updates_redirect();
  }

  if ($action === 'save_post') {
    $id = (int) ($_POST['id'] ?? 0);
    $title = trim((string) ($_POST['title'] ?? ''));
    $slug = trim((string) ($_POST['slug'] ?? ''));
    $publishedAt = trim((string) ($_POST['published_at'] ?? ''));
    $summary = trim((string) ($_POST['summary'] ?? ''));
    $body = trim((string) ($_POST['body'] ?? ''));
    $tagsRaw = trim((string) ($_POST['tags'] ?? ''));
    $status = trim((string) ($_POST['status'] ?? 'draft'));
    if (!in_array($status, ['draft', 'published'], true)) {
      $status = 'draft';
    }
    if ($title === '' || strlen($title) > 255) {
      $_SESSION['admin_flash'] = ['type' => 'error', 'msg' => 'Title is required (max 255 chars).'];
      imagekpr_admin_updates_redirect();
    }
    if ($slug === '') {
      $slug = imagekpr_admin_updates_slugify($title);
    } else {
      $slug = imagekpr_admin_updates_slugify($slug);
    }
    if ($slug === '' || strlen($slug) > 191) {
      $_SESSION['admin_flash'] = ['type' => 'error', 'msg' => 'Slug is required and must be URL-safe.'];
      imagekpr_admin_updates_redirect();
    }
    $dt = DateTime::createFromFormat('Y-m-d', $publishedAt);
    if (!$dt || $dt->format('Y-m-d') !== $publishedAt) {
      $_SESSION['admin_flash'] = ['type' => 'error', 'msg' => 'Published date must be YYYY-MM-DD.'];
      imagekpr_admin_updates_redirect();
    }
    if ($summary === '' || strlen($summary) > 1000) {
      $_SESSION['admin_flash'] = ['type' => 'error', 'msg' => 'Summary is required (max 1000 chars).'];
      imagekpr_admin_updates_redirect();
    }
    if ($body === '') {
      $_SESSION['admin_flash'] = ['type' => 'error', 'msg' => 'Body is required.'];
      imagekpr_admin_updates_redirect();
    }

    $tags = [];
    if ($tagsRaw !== '') {
      foreach (explode(',', $tagsRaw) as $piece) {
        $t = trim($piece);
        if ($t !== '') {
          $tags[] = $t;
        }
      }
    }
    $tags = array_values(array_unique($tags));
    $tagsJson = json_encode($tags, JSON_UNESCAPED_UNICODE);
    if ($tagsJson === false) {
      $tagsJson = '[]';
    }

    try {
      if ($id > 0) {
        $st = $pdo->prepare('UPDATE updates_posts
          SET slug = ?, title = ?, published_at = ?, summary = ?, body = ?, tags_json = ?, status = ?, updated_at = NOW()
          WHERE id = ?');
        $st->execute([$slug, $title, $publishedAt, $summary, $body, $tagsJson, $status, $id]);
        imagekpr_admin_audit_log($pdo, $actorId, 'updates_post_updated', ['id' => $id, 'slug' => $slug, 'status' => $status]);
        $_SESSION['admin_flash'] = ['type' => 'ok', 'msg' => 'Update saved.'];
      } else {
        $st = $pdo->prepare('INSERT INTO updates_posts (slug, title, published_at, summary, body, tags_json, status)
          VALUES (?, ?, ?, ?, ?, ?, ?)');
        $st->execute([$slug, $title, $publishedAt, $summary, $body, $tagsJson, $status]);
        $newId = (int) $pdo->lastInsertId();
        imagekpr_admin_audit_log($pdo, $actorId, 'updates_post_created', ['id' => $newId, 'slug' => $slug, 'status' => $status]);
        $_SESSION['admin_flash'] = ['type' => 'ok', 'msg' => 'Update created.'];
      }
    } catch (PDOException $e) {
      $msg = 'Could not save update.';
      if ((int) ($e->errorInfo[1] ?? 0) === 1062) {
        $msg = 'Slug already exists. Choose another slug.';
      }
      $_SESSION['admin_flash'] = ['type' => 'error', 'msg' => $msg];
    }
    imagekpr_admin_updates_redirect();
  }
}

$flash = $_SESSION['admin_flash'] ?? null;
unset($_SESSION['admin_flash']);

$editId = isset($_GET['edit']) ? (int) $_GET['edit'] : 0;
$editing = null;
$rows = [];
if ($updatesTableReady) {
  $rows = $pdo->query('SELECT id, slug, title, published_at, summary, body, tags_json, status, updated_at
    FROM updates_posts
    ORDER BY published_at DESC, id DESC')->fetchAll(PDO::FETCH_ASSOC);
  if ($editId > 0) {
    foreach ($rows as $r) {
      if ((int) $r['id'] === $editId) {
        $editing = $r;
        break;
      }
    }
  }
}

$form = [
  'id' => $editing ? (int) $editing['id'] : 0,
  'title' => $editing ? (string) $editing['title'] : '',
  'slug' => $editing ? (string) $editing['slug'] : '',
  'published_at' => $editing ? (string) $editing['published_at'] : date('Y-m-d'),
  'summary' => $editing ? (string) $editing['summary'] : '',
  'body' => $editing ? (string) $editing['body'] : '',
  'status' => $editing ? (string) $editing['status'] : 'published',
  'tags' => '',
];
if ($editing && isset($editing['tags_json'])) {
  $decodedTags = json_decode((string) $editing['tags_json'], true);
  if (is_array($decodedTags)) {
    $form['tags'] = implode(', ', array_map('strval', $decodedTags));
  }
}

$pageTitle = 'Admin — Updates';
$adminNavCurrent = 'updates';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8'); ?></title>
  <link rel="stylesheet" href="../styles.css">
  <style>
    .admin-wrap { max-width: 980px; margin: 0 auto; padding: 1rem 1.5rem 2rem; }
    .admin-toast { padding: 0.65rem 1rem; border-radius: 6px; margin-bottom: 1rem; font-size: 0.9rem; }
    .admin-toast.ok { background: #e8f5e9; border: 1px solid #a5d6a7; color: #1b5e20; }
    .admin-toast.err { background: #ffebee; border: 1px solid #ef9a9a; color: #b71c1c; }
    .admin-muted { color: #666; font-size: 0.9rem; }
    .admin-panel { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 1rem; margin: 0 0 1rem; }
    .admin-field { margin-top: 0.65rem; }
    .admin-field label { display: block; font-size: 0.9rem; font-weight: 600; margin-bottom: 0.25rem; }
    .admin-field input[type="text"], .admin-field input[type="date"], .admin-field textarea, .admin-field select {
      width: 100%; box-sizing: border-box; padding: 0.45rem 0.55rem; font: inherit; border: 1px solid #bbb; border-radius: 5px;
    }
    .admin-field textarea { min-height: 12rem; resize: vertical; }
    .admin-actions { display: flex; flex-wrap: wrap; gap: 0.6rem; margin-top: 1rem; }
    .admin-actions button, .admin-actions a { padding: 0.45rem 0.8rem; border-radius: 5px; border: 1px solid #999; background: #fff; text-decoration: none; color: #222; cursor: pointer; }
    .admin-actions button[type="submit"] { background: #1565c0; border-color: #0d47a1; color: #fff; }
    table.admin-updates { width: 100%; border-collapse: collapse; font-size: 0.88rem; }
    table.admin-updates th, table.admin-updates td { border-bottom: 1px solid #eee; padding: 0.45rem 0.35rem; text-align: left; vertical-align: top; }
    table.admin-updates th { background: #f5f5f5; }
    .admin-inline { display: inline; margin-left: 0.35rem; }
    .admin-inline button { border: 1px solid #c62828; background: #fff; color: #c62828; border-radius: 4px; padding: 0.2rem 0.45rem; cursor: pointer; }
    .admin-inline-publish button { border-color: #2e7d32; color: #2e7d32; }
    .admin-tagline { color: #444; font-size: 0.8rem; }
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

    <h1>Updates</h1>

    <?php if (!$updatesTableReady) { ?>
      <div class="admin-toast err" role="alert">
        Updates table is not ready. Run <code>migrations/phase17_updates_posts.sql</code> on the server database.
      </div>
    <?php } ?>

    <?php if (is_array($flash) && !empty($flash['msg'])) { ?>
      <div class="admin-toast <?php echo ($flash['type'] ?? '') === 'error' ? 'err' : 'ok'; ?>" role="alert">
        <?php echo htmlspecialchars((string) $flash['msg'], ENT_QUOTES, 'UTF-8'); ?>
      </div>
    <?php } ?>

    <?php if ($updatesTableReady) { ?>
      <section class="admin-panel admin-collapsible" aria-labelledby="updates-form-title" data-collapsible-key="updates_editor">
        <div class="admin-collapsible-head">
          <h2 id="updates-form-title"><?php echo $form['id'] > 0 ? 'Edit update' : 'New update'; ?></h2>
          <button type="button" class="admin-collapsible-toggle" aria-expanded="false">Show</button>
        </div>
        <div class="admin-collapsible-body">
          <form method="post" action="updates.php">
            <?php echo imagekpr_csrf_field(); ?>
            <input type="hidden" name="form_action" value="save_post">
            <input type="hidden" name="id" value="<?php echo (int) $form['id']; ?>">

            <div class="admin-field">
              <label for="upd-title">Title</label>
              <input id="upd-title" type="text" name="title" maxlength="255" required value="<?php echo htmlspecialchars($form['title'], ENT_QUOTES, 'UTF-8'); ?>">
            </div>
            <div class="admin-field">
              <label for="upd-slug">Slug (auto-generated from title if blank)</label>
              <input id="upd-slug" type="text" name="slug" maxlength="191" value="<?php echo htmlspecialchars($form['slug'], ENT_QUOTES, 'UTF-8'); ?>">
            </div>
            <div class="admin-field">
              <label for="upd-date">Published date</label>
              <input id="upd-date" type="date" name="published_at" required value="<?php echo htmlspecialchars($form['published_at'], ENT_QUOTES, 'UTF-8'); ?>">
            </div>
            <div class="admin-field">
              <label for="upd-status">Status</label>
              <select id="upd-status" name="status">
                <option value="published"<?php echo $form['status'] === 'published' ? ' selected' : ''; ?>>Published</option>
                <option value="draft"<?php echo $form['status'] === 'draft' ? ' selected' : ''; ?>>Draft</option>
              </select>
            </div>
            <div class="admin-field">
              <label for="upd-summary">Summary (for updates listing)</label>
              <textarea id="upd-summary" name="summary" rows="3" maxlength="1000" required><?php echo htmlspecialchars($form['summary'], ENT_QUOTES, 'UTF-8'); ?></textarea>
            </div>
            <div class="admin-field">
              <label for="upd-tags">Tags (comma separated)</label>
              <input id="upd-tags" type="text" name="tags" value="<?php echo htmlspecialchars($form['tags'], ENT_QUOTES, 'UTF-8'); ?>" placeholder="release, feature, maintenance">
            </div>
            <div class="admin-field">
              <label for="upd-body">Body (separate paragraphs with blank lines)</label>
              <textarea id="upd-body" name="body" required><?php echo htmlspecialchars($form['body'], ENT_QUOTES, 'UTF-8'); ?></textarea>
            </div>
            <div class="admin-actions">
              <button type="submit">Save update</button>
              <?php if ($form['id'] > 0) { ?>
                <a href="updates.php">Cancel edit</a>
              <?php } ?>
            </div>
          </form>
        </div>
      </section>

      <section class="admin-panel admin-collapsible" aria-labelledby="updates-list-title" data-collapsible-key="updates_list">
        <div class="admin-collapsible-head">
          <h2 id="updates-list-title">All updates</h2>
          <button type="button" class="admin-collapsible-toggle" aria-expanded="false">Show</button>
        </div>
        <div class="admin-collapsible-body">
          <?php if (empty($rows)) { ?>
            <p class="admin-muted">No updates yet.</p>
          <?php } else { ?>
            <table class="admin-updates">
              <thead>
                <tr>
                  <th>Date</th>
                  <th>Status</th>
                  <th>Title</th>
                  <th>Slug</th>
                  <th>Updated</th>
                  <th></th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($rows as $r) { ?>
                  <tr>
                    <td><?php echo htmlspecialchars((string) $r['published_at'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo htmlspecialchars((string) $r['status'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td>
                      <?php echo htmlspecialchars((string) $r['title'], ENT_QUOTES, 'UTF-8'); ?>
                      <?php if (!empty($r['summary'])) { ?>
                        <div class="admin-tagline"><?php echo htmlspecialchars((string) $r['summary'], ENT_QUOTES, 'UTF-8'); ?></div>
                      <?php } ?>
                    </td>
                    <td class="admin-muted"><?php echo htmlspecialchars((string) $r['slug'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td class="admin-muted"><?php echo htmlspecialchars((string) $r['updated_at'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td>
                      <a href="updates.php?edit=<?php echo (int) $r['id']; ?>">Edit</a>
                      <?php if (($r['status'] ?? '') === 'draft') { ?>
                        <form class="admin-inline admin-inline-publish" method="post" action="updates.php">
                          <?php echo imagekpr_csrf_field(); ?>
                          <input type="hidden" name="form_action" value="publish_post">
                          <input type="hidden" name="id" value="<?php echo (int) $r['id']; ?>">
                          <button type="submit">Publish</button>
                        </form>
                      <?php } ?>
                      <form class="admin-inline" method="post" action="updates.php" onsubmit="return confirm('Delete this update?');">
                        <?php echo imagekpr_csrf_field(); ?>
                        <input type="hidden" name="form_action" value="delete_post">
                        <input type="hidden" name="id" value="<?php echo (int) $r['id']; ?>">
                        <button type="submit">Delete</button>
                      </form>
                    </td>
                  </tr>
                <?php } ?>
              </tbody>
            </table>
          <?php } ?>
        </div>
      </section>
    <?php } ?>
  </div>
  <script>
    (function () {
      var nodes = document.querySelectorAll('.admin-collapsible[data-collapsible-key]');
      if (!nodes.length) return;
      nodes.forEach(function (wrap) {
        var keyRaw = wrap.getAttribute('data-collapsible-key');
        var btn = wrap.querySelector('.admin-collapsible-toggle');
        if (!keyRaw || !btn) return;
        var storageKey = 'imagekpr_admin_updates_' + keyRaw + '_hidden';
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
</body>
</html>
