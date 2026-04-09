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
  <main class="ikpr-doc-wrap">
    <h1>Support</h1>
    <p class="ikpr-doc-lead">Help for account access, upload problems, and operational troubleshooting.</p>

    <h2>Common Issues</h2>
    <h3>I cannot sign in</h3>
    <p>Confirm you are using the approved email account. If allowlist restrictions are active, request access through the public flow or contact your administrator.</p>

    <h3>Uploads fail or are blocked</h3>
    <p>Check file type and size constraints first. If maintenance mode is enabled, write actions may be temporarily disabled.</p>

    <h3>I cannot find an image</h3>
    <p>Use search with folder + tag filters. Also review sort mode and whether you are viewing all folders or a specific one.</p>

    <h2>Recommended Triage Sequence</h2>
    <ul>
      <li>Reproduce the issue in one clear flow.</li>
      <li>Capture route, timestamp, and the affected user account.</li>
      <li>Note whether it affects one action or multiple actions.</li>
      <li>For admin support, include screenshots and exact error text.</li>
    </ul>

    <h2>Contact Support</h2>
    <p>Use the Contact page for operational requests and include: user email, expected behavior, actual behavior, and urgency.</p>
    <p><a href="/contact.php">Open contact page</a></p>
  </main>
  <?php
  require_once __DIR__ . '/inc/footer.php';
  imagekpr_render_footer();
  ?>
</body>
</html>
