<?php
declare(strict_types=1);
require_once __DIR__ . '/inc/updates_data.php';

$perPage = 10;
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
if ($page < 1) {
  $page = 1;
}
$totalPosts = imagekpr_updates_count_published();
$totalPages = max(1, (int) ceil($totalPosts / $perPage));
if ($page > $totalPages) {
  $page = $totalPages;
}
$offset = ($page - 1) * $perPage;
$posts = imagekpr_updates_list_published($offset, $perPage);

function imagekpr_updates_page_link(int $page): string
{
  return '/updates.php?page=' . max(1, $page);
}

function imagekpr_updates_render_pagination(int $page, int $totalPages): void
{
  if ($totalPages <= 1) {
    return;
  }
  ?>
  <nav class="ikpr-updates-pagination" aria-label="Updates pagination">
    <a href="<?php echo htmlspecialchars(imagekpr_updates_page_link(1), ENT_QUOTES, 'UTF-8'); ?>"<?php echo $page === 1 ? ' aria-disabled="true"' : ''; ?>>First</a>
    <a href="<?php echo htmlspecialchars(imagekpr_updates_page_link($page - 1), ENT_QUOTES, 'UTF-8'); ?>"<?php echo $page === 1 ? ' aria-disabled="true"' : ''; ?>>Previous</a>
    <span>Page <?php echo (int) $page; ?> of <?php echo (int) $totalPages; ?></span>
    <a href="<?php echo htmlspecialchars(imagekpr_updates_page_link($page + 1), ENT_QUOTES, 'UTF-8'); ?>"<?php echo $page === $totalPages ? ' aria-disabled="true"' : ''; ?>>Next</a>
    <a href="<?php echo htmlspecialchars(imagekpr_updates_page_link($totalPages), ENT_QUOTES, 'UTF-8'); ?>"<?php echo $page === $totalPages ? ' aria-disabled="true"' : ''; ?>>Last</a>
  </nav>
  <?php
}
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
    <?php imagekpr_updates_render_pagination($page, $totalPages); ?>

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
    <?php imagekpr_updates_render_pagination($page, $totalPages); ?>
  </main>
  <?php
  require_once __DIR__ . '/inc/footer.php';
  imagekpr_render_footer();
  ?>
</body>
</html>
