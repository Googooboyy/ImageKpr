<?php
/**
 * Public marketing / sign-in shell for guests (included from index.php only).
 * Expects: $ikMaintenance (bool), $ikMaintenanceMsg (string), $ikLoginErr (string),
 *   $ikRequestStatus (string), $ikAcceptRequests (bool), $ikSubmitted (bool).
 */
$ikLoginMsgs = [
  'state' => 'Sign-in session expired. Please try again.',
  'oauth' => 'Google sign-in was cancelled or failed.',
  'code' => 'Missing authorization code. Please try again.',
  'config' => 'Server OAuth configuration is incomplete.',
  'token' => 'Could not complete sign-in with Google. Try again.',
  'userinfo' => 'Could not read your Google profile. Try again.',
  'forbidden' => 'Sign in with Google to join the review queue. If you are already waiting, sign in again to see your status.',
  'database' => 'Sign-in failed: database is not ready. Run migrations/phase7_auth.sql on the server, then try again.',
];
$ikLoginMsg = $ikLoginMsgs[$ikLoginErr] ?? '';

$ikShowQueueBanner = !empty($ikSubmitted) || $ikRequestStatus === 'ok';

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
<body class="ikpr-landing<?php echo $ikMaintenance ? ' ikpr-maintenance' : ''; ?>">
  <?php if ($ikMaintenance) { ?>
  <div class="ikpr-maintenance-banner" role="alert"><?php echo htmlspecialchars($ikMaintenanceMsg, ENT_QUOTES, 'UTF-8'); ?></div>
  <?php } ?>
  <?php if ($ikLoginMsg !== '') { ?>
  <div class="ikpr-landing-alert ikpr-landing-alert--error" role="alert"><?php echo htmlspecialchars($ikLoginMsg, ENT_QUOTES, 'UTF-8'); ?></div>
  <?php } ?>
  <?php if ($ikShowQueueBanner) { ?>
  <div class="ikpr-landing-alert ikpr-landing-alert--success" role="status">You are in the queue! There is nothing else you need to do right now. Check back tomorrow — approvals are usually within a day or a few.</div>
  <?php } ?>
  <header class="ikpr-landing-hero" role="banner">
    <h1 id="ikpr-hero-heading" class="ikpr-landing-hero-title">
      <img src="assets/imagekpr-logo.png" alt="ImageKpr" width="605" height="330" onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
      <span class="ikpr-landing-hero-fallback">ImageKpr</span>
    </h1>
  </header>
  <main class="ikpr-landing-stack">
<?php require __DIR__ . '/landing_about_section.php'; ?>

    <section class="ikpr-landing-block ikpr-landing-login" id="login" aria-labelledby="ikpr-login-heading">
      <h2 id="ikpr-login-heading">Sign in</h2>
      <p>Already approved? Sign in with Google to open your library. New here? When access is restricted, signing in with Google adds your Google email to the review queue.</p>
      <p><a class="ikpr-btn-google" href="auth/google/start.php">Continue with Google</a></p>
      <p class="ikpr-landing-note">You will be redirected to Google to sign in.</p>
    </section>

    <section class="ikpr-landing-block ikpr-landing-request" aria-labelledby="ikpr-request-heading">
      <h2 id="ikpr-request-heading">Request early access</h2>
      <p>Prefer not to use Google yet? Submit your work email and a short note. An administrator can approve you so you can sign in later.</p>
      <?php if ($ikRequestMsg !== '' && $ikRequestStatus !== 'ok') { ?>
      <p class="ikpr-landing-request-feedback<?php echo ($ikRequestStatus === 'duplicate' || $ikRequestStatus === 'already_allowed') ? ' ikpr-landing-request-feedback--ok' : ' ikpr-landing-request-feedback--err'; ?>" role="status"><?php echo htmlspecialchars($ikRequestMsg, ENT_QUOTES, 'UTF-8'); ?></p>
      <?php } ?>
      <?php if (!$ikAcceptRequests) { ?>
      <p class="ikpr-landing-muted">The administrator is not accepting new requests at the moment.</p>
      <?php } ?>
      <form class="ikpr-landing-request-form" action="request_access.php" method="post" aria-label="Request early access">
        <?php echo imagekpr_guest_csrf_field(); ?>
        <label class="ikpr-landing-label" for="ikpr-request-email">Email</label>
        <input type="email" id="ikpr-request-email" name="email" required autocomplete="email" placeholder="you@example.com" <?php echo !$ikAcceptRequests ? 'disabled' : ''; ?>>
        <label class="ikpr-landing-label" for="ikpr-request-note">Message (optional)</label>
        <textarea id="ikpr-request-note" name="note" rows="3" maxlength="2000" placeholder="Tell us how you plan to use ImageKpr" <?php echo !$ikAcceptRequests ? 'disabled' : ''; ?>></textarea>
        <button type="submit" class="ikpr-landing-submit" <?php echo !$ikAcceptRequests ? 'disabled' : ''; ?>>Submit</button>
      </form>
    </section>
  </main>

  <footer class="credits-footer">
    © 2026 <a href="https://mar.sg" target="_blank" rel="noopener noreferrer">Mar.sg</a>
  </footer>
</body>
</html>
