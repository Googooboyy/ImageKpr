<?php
declare(strict_types=1);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Privacy - ImageKpr</title>
  <link rel="stylesheet" href="styles.css">
</head>
<body class="ikpr-doc-page">
  <main class="ikpr-doc-wrap">
    <h1>Privacy Notice</h1>
    <p class="ikpr-doc-lead">This page describes how ImageKpr handles account and usage data at a high level.</p>

    <h2>Data We Process</h2>
    <ul>
      <li>Account identity details required for sign-in and authorization.</li>
      <li>Image metadata needed to provide library functionality (names, tags, folder assignments).</li>
      <li>Operational logs required for security and system administration.</li>
    </ul>

    <h2>Why We Process Data</h2>
    <ul>
      <li>To authenticate users and enforce access policies.</li>
      <li>To provide image storage, retrieval, and sharing capabilities.</li>
      <li>To maintain service reliability and investigate misuse or incidents.</li>
    </ul>

    <h2>Data Retention</h2>
    <p>Data retention is controlled by your organization’s operating policy. Admin users can remove content and manage access records as needed.</p>

    <h2>Your Controls</h2>
    <p>If you need data updates or deletions, contact your administrator or support contact listed on the Contact page.</p>

    <p class="ikpr-doc-note">Not legal advice. Replace this draft with counsel-approved privacy terms for production use.</p>
  </main>
  <?php
  require_once __DIR__ . '/inc/footer.php';
  imagekpr_render_footer();
  ?>
</body>
</html>
