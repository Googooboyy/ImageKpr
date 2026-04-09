<?php
declare(strict_types=1);
require_once __DIR__ . '/inc/updates_data.php';

$posts = imagekpr_updates_sorted_desc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Updates - ImageKpr</title>
  <link rel="stylesheet" href="styles.css">
</head>
<body class="ikpr-doc-page">
  <?php
  require_once __DIR__ . '/inc/page_hero.php';
  imagekpr_render_page_hero();
  ?>
  <main class="ikpr-doc-wrap">
    <h1>Updates</h1>
    <p class="ikpr-doc-lead">Product news, release notes, and operational notices for ImageKpr.</p>
    <p><a href="/updates.xml">RSS feed</a></p>

    <?php if (empty($posts)) { ?>
      <p>No updates published yet.</p>
    <?php } else { ?>
      <?php foreach ($posts as $post) { ?>
        <article class="ikpr-doc-note">
          <h2 style="margin-top:0">
            <a href="/update.php?slug=<?php echo urlencode((string) $post['slug']); ?>">
              <?php echo htmlspecialchars((string) $post['title'], ENT_QUOTES, 'UTF-8'); ?>
            </a>
          </h2>
          <p style="margin:0.2rem 0 0.6rem;color:#666;">
            Published <?php echo htmlspecialchars(imagekpr_updates_format_date((string) $post['published_at']), ENT_QUOTES, 'UTF-8'); ?>
          </p>
          <p style="margin:0;"><?php echo htmlspecialchars((string) $post['summary'], ENT_QUOTES, 'UTF-8'); ?></p>
        </article>
      <?php } ?>
    <?php } ?>
  </main>
  <?php
  require_once __DIR__ . '/inc/footer.php';
  imagekpr_render_footer();
  ?>
</body>
</html>
