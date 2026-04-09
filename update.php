<?php
declare(strict_types=1);
require_once __DIR__ . '/inc/updates_data.php';

$slug = isset($_GET['slug']) ? trim((string) $_GET['slug']) : '';
$post = $slug !== '' ? imagekpr_updates_find_by_slug($slug) : null;
$notFound = $post === null;
$adjacent = $notFound ? ['previous' => null, 'next' => null] : imagekpr_updates_prev_next($slug);
if ($notFound) {
  http_response_code(404);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo $notFound ? 'Update not found' : htmlspecialchars((string) $post['title'], ENT_QUOTES, 'UTF-8'); ?> - ImageKpr</title>
  <link rel="stylesheet" href="styles.css">
</head>
<body class="ikpr-doc-page">
  <?php
  require_once __DIR__ . '/inc/page_hero.php';
  imagekpr_render_page_hero();
  ?>
  <main class="ikpr-doc-wrap">
    <?php if ($notFound) { ?>
      <h1>Update not found</h1>
      <p>The requested update could not be found.</p>
      <p><a href="/updates.php">Back to all updates</a></p>
    <?php } else { ?>
      <p><a href="/updates.php">← All updates</a></p>
      <h1><?php echo htmlspecialchars((string) $post['title'], ENT_QUOTES, 'UTF-8'); ?></h1>
      <p class="ikpr-doc-lead">Published <?php echo htmlspecialchars(imagekpr_updates_format_date((string) $post['published_at']), ENT_QUOTES, 'UTF-8'); ?></p>
      <?php if (!empty($post['tags']) && is_array($post['tags'])) { ?>
        <p style="color:#666;">Tags: <?php echo htmlspecialchars(implode(', ', array_map('strval', $post['tags'])), ENT_QUOTES, 'UTF-8'); ?></p>
      <?php } ?>
      <?php foreach (($post['body'] ?? []) as $paragraph) { ?>
        <p><?php echo htmlspecialchars((string) $paragraph, ENT_QUOTES, 'UTF-8'); ?></p>
      <?php } ?>
      <nav style="margin-top:1.25rem;display:flex;gap:1rem;flex-wrap:wrap;">
        <?php if (is_array($adjacent['previous'])) { ?>
          <a href="/update.php?slug=<?php echo urlencode((string) $adjacent['previous']['slug']); ?>">← Previous: <?php echo htmlspecialchars((string) $adjacent['previous']['title'], ENT_QUOTES, 'UTF-8'); ?></a>
        <?php } ?>
        <?php if (is_array($adjacent['next'])) { ?>
          <a href="/update.php?slug=<?php echo urlencode((string) $adjacent['next']['slug']); ?>">Next: <?php echo htmlspecialchars((string) $adjacent['next']['title'], ENT_QUOTES, 'UTF-8'); ?> →</a>
        <?php } ?>
      </nav>
    <?php } ?>
  </main>
  <?php
  require_once __DIR__ . '/inc/footer.php';
  imagekpr_render_footer();
  ?>
</body>
</html>
