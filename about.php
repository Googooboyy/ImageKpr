<?php
declare(strict_types=1);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>About - ImageKpr</title>
  <link rel="stylesheet" href="styles.css">
</head>
<body class="ikpr-doc-page">
  <?php
  require_once __DIR__ . '/inc/page_hero.php';
  imagekpr_render_page_hero();
  ?>
  <main class="ikpr-doc-wrap">
    <h1>About ImageKpr</h1>
    <p class="ikpr-doc-lead">It's like a second brain for your favourite images.</p>
    <p class="ikpr-doc-lead">ImageKpr is a personal image repository for professionals that need to quickly upload, organize, find, and share images.</p>

    <h2>What It Is Built For</h2>
    <ul>
      <li>Keeping a shared image library easy to navigate through folders, tags, and search.</li>
      <li>Moving fast with drag-and-drop uploads, bulk actions, and copyable links.</li>
      <li>Supporting a lightweight workflow for agencies, content teams, and operations teams.</li>
    </ul>

    <h2>Core Product Principles</h2>
    <h3>1. Speed over ceremony</h3>
    <p>Common tasks should take one or two steps. Uploading, tagging, and grabbing a usable link should be immediate.</p>
    <h3>2. Clarity over complexity</h3>
    <p>The UI favors obvious controls and clear feedback, with plain language for actions and system states.</p>
    <h3>3. Controlled sharing</h3>
    <p>Sign-in and allowlist controls exist so teams can decide who gets access while still collaborating quickly.</p>

    <h2>How Teams Use It</h2>
    <ul>
      <li>Marketing operations libraries for campaign assets.</li>
      <li>Internal repositories for social, blog, and email imagery.</li>
      <li>Client handoff libraries where quick retrieval matters more than deep media governance.</li>
    </ul>

    <p class="ikpr-doc-note">ImageKpr is intentionally framework-light and hosting-friendly, making it straightforward to deploy and maintain in environments where reliability and simplicity are key requirements.</p>
  </main>
  <?php
  require_once __DIR__ . '/inc/footer.php';
  imagekpr_render_footer();
  ?>
</body>
</html>
