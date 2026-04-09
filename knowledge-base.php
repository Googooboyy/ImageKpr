<?php
declare(strict_types=1);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Knowledge Base - ImageKpr</title>
  <link rel="stylesheet" href="styles.css">
</head>
<body class="ikpr-doc-page">
  <?php
  require_once __DIR__ . '/inc/page_hero.php';
  imagekpr_render_page_hero();
  ?>
  <main class="ikpr-doc-wrap">
    <h1>Knowledge Base</h1>
    <p class="ikpr-doc-lead">Operational guidance for getting the most from ImageKpr.</p>

    <h2>Getting Started</h2>
    <ul>
      <li>Sign in from the main entry route using your approved account.</li>
      <li>Upload files through drag-and-drop or click-to-select in the upload zone.</li>
      <li>Use folders and tags immediately to keep the library searchable.</li>
    </ul>

    <h2>Working with Images</h2>
    <h3>Upload and review</h3>
    <p>Upload confirmation lets you rename files and apply folders/tags before final import. Use this to enforce naming quality from the start.</p>
    <h3>Search and filters</h3>
    <p>Combine search text, folder selection, and tag chips to narrow to the exact set of assets you need.</p>
    <h3>Bulk operations</h3>
    <p>Selection mode supports batch delete, ZIP download, tags, folders, and rename workflows for high-volume curation tasks.</p>

    <h2>Sharing and Presentation</h2>
    <ul>
      <li>Use copy-link actions to place assets directly into docs, chat, or production tickets.</li>
      <li>Use slideshow mode for review meetings and visual walkthroughs.</li>
      <li>Use folder conventions to make recurring share bundles predictable.</li>
    </ul>

  </main>
  <?php
  require_once __DIR__ . '/inc/footer.php';
  imagekpr_render_footer();
  ?>
</body>
</html>
