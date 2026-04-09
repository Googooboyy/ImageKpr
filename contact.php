<?php
declare(strict_types=1);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Contact - ImageKpr</title>
  <link rel="stylesheet" href="styles.css">
</head>
<body class="ikpr-doc-page">
  <main class="ikpr-doc-wrap">
    <h1>Contact</h1>
    <p class="ikpr-doc-lead">Reach out for onboarding, support requests, and operational questions.</p>

    <h2>Primary Contact</h2>
    <p>Email: <a href="mailto:hello@example.com">hello@example.com</a></p>
    <p>Address: Add your business address here</p>

    <h2>What to Include in Your Message</h2>
    <ul>
      <li>Your organization or team name.</li>
      <li>The account email(s) involved.</li>
      <li>A concise summary of the request or issue.</li>
      <li>Any deadline or urgency constraints.</li>
    </ul>

    <h2>Commercial and Partnership Inquiries</h2>
    <p>For implementation, customization, or scale requirements, include expected usage volume and your preferred deployment environment.</p>

    <p class="ikpr-doc-note">This page is content-ready and can be replaced later with a form handler or third-party contact form integration.</p>
  </main>
  <?php
  require_once __DIR__ . '/inc/footer.php';
  imagekpr_render_footer();
  ?>
</body>
</html>
