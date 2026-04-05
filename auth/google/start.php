<?php
// Buffer so stray whitespace/BOM from any include cannot block the redirect.
ob_start();
require_once __DIR__ . '/../../inc/auth.php';
require_once __DIR__ . '/../../inc/rate_limit.php';
imagekpr_start_session();
imagekpr_ensure_config();
if (!defined('GOOGLE_CLIENT_ID') || GOOGLE_CLIENT_ID === '' || !defined('GOOGLE_REDIRECT_URI') || GOOGLE_REDIRECT_URI === '') {
  ob_end_clean();
  http_response_code(500);
  header('Content-Type: text/plain; charset=utf-8');
  echo 'Google OAuth is not configured. Set GOOGLE_CLIENT_ID and GOOGLE_REDIRECT_URI in config.php.';
  exit;
}
if (!imagekpr_rate_limit('oauth_start', 40, 900)) {
  ob_end_clean();
  http_response_code(429);
  header('Content-Type: text/plain; charset=utf-8');
  echo 'Too many sign-in attempts. Try again later.';
  exit;
}
$state = bin2hex(random_bytes(16));
$_SESSION['oauth_state'] = $state;
$params = [
  'client_id' => GOOGLE_CLIENT_ID,
  'redirect_uri' => GOOGLE_REDIRECT_URI,
  'response_type' => 'code',
  'scope' => 'openid email profile',
  'state' => $state,
  'prompt' => 'select_account',
];
$url = 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params);
ob_end_clean();
if (!headers_sent()) {
  header('Location: ' . $url, true, 302);
  exit;
}
header('Content-Type: text/html; charset=utf-8');
$href = htmlspecialchars($url, ENT_QUOTES, 'UTF-8');
echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><title>Sign in</title>';
echo '<meta http-equiv="refresh" content="0;url=' . $href . '"></head><body>';
echo '<p>Redirecting to Google… If you are not redirected, <a href="' . $href . '">continue here</a>.</p></body></html>';
exit;