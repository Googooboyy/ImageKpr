<?php
require_once __DIR__ . '/../../inc/auth.php';
require_once __DIR__ . '/../../inc/rate_limit.php';
imagekpr_start_session();
imagekpr_ensure_config();
if (!defined('GOOGLE_CLIENT_ID') || GOOGLE_CLIENT_ID === '' || !defined('GOOGLE_REDIRECT_URI') || GOOGLE_REDIRECT_URI === '') {
  http_response_code(500);
  header('Content-Type: text/plain; charset=utf-8');
  echo 'Google OAuth is not configured. Set GOOGLE_CLIENT_ID and GOOGLE_REDIRECT_URI in config.php.';
  exit;
}
if (!imagekpr_rate_limit('oauth_start', 40, 900)) {
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
header('Location: https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params));
exit;