<?php
/**
 * Main app shell for signed-in users not yet on the email allowlist (restricted mode).
 * Included from index.php only.
 * Expects: $ikMaintenance (bool), $ikMaintenanceMsg (string), $ikEmail (string), $ikName (string),
 *   $ikSubmitted (bool).
 */
$ikDispEmail = $ikEmail !== '' ? $ikEmail : 'your account';
?><!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>ImageKpr</title>
  <link rel="apple-touch-icon" sizes="180x180" href="favicons/apple-touch-icon.png">
  <link rel="icon" type="image/png" sizes="32x32" href="favicons/favicon-32x32.png">
  <link rel="icon" type="image/png" sizes="16x16" href="favicons/favicon-16x16.png">
  <link rel="icon" type="image/png" sizes="192x192" href="favicons/android-chrome-192x192.png">
  <link rel="icon" type="image/png" sizes="512x512" href="favicons/android-chrome-512x512.png">
  <link rel="shortcut icon" href="favicons/favicon.ico">
  <link rel="icon" type="image/x-icon" sizes="192x192" href="favicons/favicon-192x192.ico">
  <link rel="manifest" href="favicons/site.webmanifest">
  <link rel="stylesheet" href="styles.css">
</head>
<body class="ikpr-landing ikpr-landing--pending<?php echo $ikMaintenance ? ' ikpr-maintenance' : ''; ?>">
  <?php if ($ikMaintenance) { ?>
  <div class="ikpr-maintenance-banner" role="alert"><?php echo htmlspecialchars($ikMaintenanceMsg, ENT_QUOTES, 'UTF-8'); ?></div>
  <?php } ?>
  <?php if ($ikSubmitted) { ?>
  <div class="ikpr-landing-alert ikpr-landing-alert--success" role="status">You are in the queue! There is nothing else you need to do right now. Check back tomorrow — approvals are usually within a day or a few.</div>
  <?php } ?>
  <header class="ikpr-landing-hero ikpr-landing-hero--with-session" role="banner">
    <h1 class="ikpr-landing-hero-title">
      <img src="assets/imagekpr-logo.png" alt="ImageKpr" width="605" height="330" onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
      <span class="ikpr-landing-hero-fallback">ImageKpr</span>
    </h1>
    <div class="ikpr-landing-session" aria-label="Signed in">
      <span class="ikpr-landing-session-id" title="<?php echo htmlspecialchars($ikEmail, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($ikName !== '' ? $ikName : $ikEmail, ENT_QUOTES, 'UTF-8'); ?></span>
      <a href="auth/logout.php" class="ikpr-landing-session-logout">Log out</a>
    </div>
  </header>
  <main class="ikpr-landing-stack">
<?php require __DIR__ . '/landing_about_section.php'; ?>

    <section class="ikpr-landing-block ikpr-landing-pending" aria-labelledby="ikpr-pending-heading">
      <h2 id="ikpr-pending-heading" class="ikpr-landing-pending-title">Thank you — your account <span class="ikpr-landing-pending-email"><?php echo htmlspecialchars($ikDispEmail, ENT_QUOTES, 'UTF-8'); ?></span> is pending approval.</h2>
      <p class="ikpr-landing-pending-lead">Approvals are usually quick, but can take <strong>24 hours up to a few days</strong>. You do not need to do anything else — you are already in line. Feel free to check back tomorrow.</p>
      <h3 class="ikpr-landing-faq-heading" id="ikpr-faq-heading">While you wait</h3>
      <p class="ikpr-landing-faq-intro">Here is what you can look forward to in ImageKpr:</p>
      <div class="ikpr-landing-faq" role="region" aria-labelledby="ikpr-faq-heading">
        <div class="ikpr-landing-faq-panel">
        <details class="ikpr-landing-faq-item">
          <summary class="ikpr-landing-faq-summary">Privacy</summary>
          <div class="ikpr-landing-faq-body">
            <p>You sign in with Google; your library is tied to your account and is not shared with other users. Share links only work for people you send them to.</p>
          </div>
        </details>
        <details class="ikpr-landing-faq-item">
          <summary class="ikpr-landing-faq-summary">Batch upload and limits</summary>
          <div class="ikpr-landing-faq-body">
            <p>Upload many images at once by dragging them in or picking multiple files. Per-file size limits apply so the app stays fast and reliable; large files may be resized on upload when you confirm.</p>
          </div>
        </details>
        <details class="ikpr-landing-faq-item">
          <summary class="ikpr-landing-faq-summary">Folders</summary>
          <div class="ikpr-landing-faq-body">
            <p>Organize images into folders for projects, clients, or themes. Filter the grid by folder and move images between folders anytime.</p>
          </div>
        </details>
        <details class="ikpr-landing-faq-item">
          <summary class="ikpr-landing-faq-summary">Tags</summary>
          <div class="ikpr-landing-faq-body">
            <p>Add tags to make images easy to find later. Combine tags with search and folders to narrow down exactly what you need.</p>
          </div>
        </details>
        <details class="ikpr-landing-faq-item">
          <summary class="ikpr-landing-faq-summary">Shareable private links</summary>
          <div class="ikpr-landing-faq-body">
            <p>Copy a direct link to an image when you want to drop it into a doc, chat, or email. Links point to your file on the image host; treat them like a private URL.</p>
          </div>
        </details>
        <details class="ikpr-landing-faq-item">
          <summary class="ikpr-landing-faq-summary">Slideshows</summary>
          <div class="ikpr-landing-faq-body">
            <p>Select images, set order, then run a full-screen slideshow — manual or timed advance, with transitions. Handy for reviews and presentations.</p>
          </div>
        </details>
        </div>
      </div>
    </section>
  </main>

  <footer class="credits-footer">
    © 2026 <a href="https://mar.sg" target="_blank" rel="noopener noreferrer">Mar.sg</a>
  </footer>
</body>
</html>
