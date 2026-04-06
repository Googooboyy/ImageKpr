<?php
/**
 * Public marketing / sign-in shell for guests (included from index.php only).
 * Expects: $ikMaintenance (bool), $ikMaintenanceMsg (string), $ikLoginErr (string),
 *   $ikRequestStatus (string), $ikAcceptRequests (bool).
 */
$ikLoginMsgs = [
  'state' => 'Sign-in session expired. Please try again.',
  'oauth' => 'Google sign-in was cancelled or failed.',
  'code' => 'Missing authorization code. Please try again.',
  'config' => 'Server OAuth configuration is incomplete.',
  'token' => 'Could not complete sign-in with Google. Try again.',
  'userinfo' => 'Could not read your Google profile. Try again.',
  'forbidden' => 'Sorry, your email is not yet approved for app access. Please request access below.',
  'database' => 'Sign-in failed: database is not ready. Run migrations/phase7_auth.sql on the server, then try again.',
];
$ikLoginMsg = $ikLoginMsgs[$ikLoginErr] ?? '';

$ikRequestMsgs = [
  'ok' => 'Thanks — we received your request. An administrator will review it.',
  'duplicate' => 'That email already has a pending request.',
  'closed' => 'Access requests are not being accepted right now.',
  'invalid' => 'Please enter a valid email address.',
  'ratelimit' => 'Too many requests from your network. Please try again later.',
  'already_allowed' => 'That email is already authorized to sign in.',
  'database' => 'Could not save your request. The server may need a database update (run migrations/phase14_access_requests.sql).',
  'csrf' => 'Your session expired. Please try submitting again.',
];
$ikRequestMsg = $ikRequestMsgs[$ikRequestStatus] ?? '';
?><!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>ImageKpr</title>
  <link rel="stylesheet" href="styles.css">
</head>
<body class="ikpr-landing<?php echo $ikMaintenance ? ' ikpr-maintenance' : ''; ?>">
  <?php if ($ikMaintenance) { ?>
  <div class="ikpr-maintenance-banner" role="alert"><?php echo htmlspecialchars($ikMaintenanceMsg, ENT_QUOTES, 'UTF-8'); ?></div>
  <?php } ?>
  <?php if ($ikLoginMsg !== '') { ?>
  <div class="ikpr-landing-alert ikpr-landing-alert--error" role="alert"><?php echo htmlspecialchars($ikLoginMsg, ENT_QUOTES, 'UTF-8'); ?></div>
  <?php } ?>
  <header class="ikpr-landing-hero" role="banner">
    <h1 id="ikpr-hero-heading" class="ikpr-landing-hero-title">
      <img src="assets/imagekpr-logo.png" alt="ImageKpr" width="605" height="330" onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
      <span class="ikpr-landing-hero-fallback">ImageKpr</span>
    </h1>
  </header>
  <main class="ikpr-landing-stack">
    <section class="ikpr-landing-block ikpr-landing-about" aria-labelledby="ikpr-about-heading">
      <h2 id="ikpr-about-heading">About ImageKpr</h2>
      <p class="ikpr-landing-lead ikpr-landing-about-tagline">Your personal stock image library.</p>
      <ul class="ikpr-landing-about-list">
        <li>Privacy-first — sign in with Google; your gallery stays private to your account.</li>
        <li>Upload and organize images in one place.</li>
        <li>Folders and tags to keep your collection tidy.</li>
        <li>Search your library; run slideshows and presentations.</li>
        <li>Share private image links.</li>
        <li>Zip batches of images and download for distribution.</li>
      </ul>
    </section>

    <section class="ikpr-landing-block ikpr-landing-login" id="login" aria-labelledby="ikpr-login-heading">
      <h2 id="ikpr-login-heading">Sign in</h2>
      <p>Already have access? Sign in with Google to open your library.</p>
      <p><a class="ikpr-btn-google" href="auth/google/start.php">Continue with Google</a></p>
      <p class="ikpr-landing-note">You will be redirected to Google to sign in.</p>
    </section>

    <section class="ikpr-landing-block ikpr-landing-request" aria-labelledby="ikpr-request-heading">
      <h2 id="ikpr-request-heading">Request access</h2>
      <p>Need an account? Submit your work email. An admin can approve it so you can sign in with Google.</p>
      <?php if ($ikRequestMsg !== '') { ?>
      <p class="ikpr-landing-request-feedback<?php echo ($ikRequestStatus === 'ok' || $ikRequestStatus === 'duplicate' || $ikRequestStatus === 'already_allowed') ? ' ikpr-landing-request-feedback--ok' : ' ikpr-landing-request-feedback--err'; ?>" role="status"><?php echo htmlspecialchars($ikRequestMsg, ENT_QUOTES, 'UTF-8'); ?></p>
      <?php } ?>
      <?php if (!$ikAcceptRequests) { ?>
      <p class="ikpr-landing-muted">The administrator is not accepting new requests at the moment.</p>
      <?php } ?>
      <form class="ikpr-landing-request-form" action="request_access.php" method="post" aria-label="Request access">
        <?php echo imagekpr_guest_csrf_field(); ?>
        <label class="ikpr-landing-label" for="ikpr-request-email">Email</label>
        <input type="email" id="ikpr-request-email" name="email" required autocomplete="email" placeholder="you@example.com" <?php echo !$ikAcceptRequests ? 'disabled' : ''; ?>>
        <label class="ikpr-landing-label" for="ikpr-request-note">Message (optional)</label>
        <textarea id="ikpr-request-note" name="note" rows="3" maxlength="2000" placeholder="Tell us how you plan to use ImageKpr" <?php echo !$ikAcceptRequests ? 'disabled' : ''; ?>></textarea>
        <button type="submit" class="ikpr-landing-submit" <?php echo !$ikAcceptRequests ? 'disabled' : ''; ?>>Submit request</button>
      </form>
    </section>
  </main>

  <footer class="credits-footer">
    © 2026 <a href="https://mar.sg" target="_blank" rel="noopener noreferrer">Mar.sg</a>
  </footer>
</body>
</html>
