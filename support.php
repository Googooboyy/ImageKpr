<?php
declare(strict_types=1);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Support - ImageKpr</title>
  <link rel="stylesheet" href="styles.css">
</head>
<body class="ikpr-doc-page">
  <?php
  require_once __DIR__ . '/inc/page_hero.php';
  imagekpr_render_page_hero();
  ?>
  <main class="ikpr-doc-wrap ikpr-kb">
    <header class="ikpr-kb-intro">
      <h1>Support</h1>
      <p class="ikpr-doc-lead">Help for account access, upload problems, and operational troubleshooting.</p>
    </header>

    <section class="ikpr-doc-card" aria-labelledby="sup-common">
      <h2 id="sup-common">Common Issues</h2>
      <div class="ikpr-doc-subsection">
        <h3>I cannot sign in</h3>
        <p>Confirm you are using the approved email account. If allowlist restrictions are active, request access through the public flow or contact your administrator.</p>
      </div>
      <div class="ikpr-doc-subsection">
        <h3>Uploads fail or are blocked</h3>
        <p>Check file type and size constraints first. If maintenance mode is enabled, write actions may be temporarily disabled.</p>
      </div>
      <div class="ikpr-doc-subsection">
        <h3>I cannot find an image</h3>
        <p>Use search with folder + tag filters. Also review sort mode and whether you are viewing all folders or a specific one.</p>
      </div>
    </section>

    <section class="ikpr-doc-card" aria-labelledby="sup-triage">
      <h2 id="sup-triage">Recommended Triage Sequence</h2>
      <ul>
        <li>Reproduce the issue in one clear flow.</li>
        <li>Capture route, timestamp, and the affected user account.</li>
        <li>Note whether it affects one action or multiple actions.</li>
        <li>For admin support, include screenshots and exact error text.</li>
      </ul>
    </section>

    <section class="ikpr-doc-card" aria-labelledby="sup-contact">
      <h2 id="sup-contact">Contact Support</h2>
      <p>Use the Contact page for operational requests and include: user email, expected behavior, actual behavior, and urgency.</p>
      <p class="ikpr-doc-card-cta"><a href="/contact.php">Open contact page</a></p>
    </section>
  </main>
  <?php
  require_once __DIR__ . '/inc/footer.php';
  imagekpr_render_footer();
  ?>
</body>
</html>
