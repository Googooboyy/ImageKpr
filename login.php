<?php
/**
 * Legacy URL: sign-in UI lives on index.php for guests. Preserve query params (e.g. ?error=forbidden).
 */
ob_start();
require_once __DIR__ . '/inc/auth.php';
imagekpr_ensure_config();
imagekpr_start_session();
if (imagekpr_user_id() >= 1) {
  imagekpr_redirect_html('index.php', 0);
}
$allowed = imagekpr_guest_login_error_codes();
$err = isset($_GET['error']) ? (string) $_GET['error'] : '';
$qs = ($err !== '' && in_array($err, $allowed, true)) ? ('?error=' . rawurlencode($err)) : '';
imagekpr_redirect_html('index.php' . $qs, 0);