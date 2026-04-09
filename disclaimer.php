<?php
declare(strict_types=1);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Disclaimer - ImageKpr</title>
  <link rel="stylesheet" href="styles.css">
</head>
<body class="ikpr-doc-page">
  <?php
  require_once __DIR__ . '/inc/page_hero.php';
  imagekpr_render_page_hero();
  ?>
  <main class="ikpr-doc-wrap">
    <h1>Disclaimer</h1>
    <p class="ikpr-doc-lead">General service disclaimers for ImageKpr usage and informational content.</p>

    <h2>Informational Content</h2>
    <p>Documentation and guidance pages are provided for operational convenience and may be updated without notice.</p>

    <h2>No Professional Advice</h2>
    <p>Content on this site is not legal, financial, or compliance advice. Seek qualified professional advice for those matters.</p>

    <h2>Third-Party Services</h2>
    <p>Some workflows may connect to external services (for example, sign-in providers or newsletter platforms). Their policies and availability are outside this site’s direct control.</p>

    <h2>Operational Limits</h2>
    <p>Performance and behavior may vary based on deployment environment, server configuration, and traffic conditions.</p>

  </main>
  <?php
  require_once __DIR__ . '/inc/footer.php';
  imagekpr_render_footer();
  ?>
</body>
</html>
