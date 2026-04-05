<?php
ob_start();
require_once __DIR__ . '/inc/auth.php';
imagekpr_redirect_if_logged_in();
$err = isset($_GET['error']) ? (string) $_GET['error'] : '';
$msgs = [
  'state' => 'Sign-in session expired. Please try again.',
  'oauth' => 'Google sign-in was cancelled or failed.',
  'code' => 'Missing authorization code. Please try again.',
  'config' => 'Server OAuth configuration is incomplete.',
  'token' => 'Could not complete sign-in with Google. Try again.',
  'userinfo' => 'Could not read your Google profile. Try again.',
  'forbidden' => 'Your account is not authorized. Ask an admin to add your email to the allowlist.',
  'database' => 'Sign-in failed: database is not ready. Run migrations/phase7_auth.sql on the server, then try again.',
];
$msg = $msgs[$err] ?? '';
?><!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sign in — ImageKpr</title>
  <link rel="stylesheet" href="styles.css">
  <style>
    .login-wrap { max-width: 420px; margin: 4rem auto; padding: 2rem; text-align: center; }
    .login-wrap h1 { margin-bottom: 0.5rem; }
    .login-error { color: #c0392b; margin: 1rem 0; font-size: 0.95rem; }
    .btn-google { display: inline-block; margin-top: 1.5rem; padding: 0.75rem 1.5rem; background: #4285f4; color: #fff !important; text-decoration: none; border-radius: 6px; font-weight: 600; }
    .btn-google:hover { background: #3367d6; }
  </style>
</head>
<body>
  <div class="login-wrap">
    <h1>ImageKpr</h1>
    <p>Sign in to manage your images.</p>
    <?php if ($msg !== '') { ?><p class="login-error"><?php echo htmlspecialchars($msg); ?></p><?php } ?>
    <a class="btn-google" href="auth/google/start.php">Continue with Google</a>
    <p style="margin-top:2rem;font-size:0.85rem;opacity:0.8;">You will be redirected to Google to sign in.</p>
  </div>
</body>
</html>