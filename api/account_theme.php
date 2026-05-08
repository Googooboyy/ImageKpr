<?php
/**
 * Persist signed-in theme preference (light/dark). Used by theme toggle / account Appearance.
 */
require_once __DIR__ . '/../inc/auth.php';
imagekpr_ensure_config();
imagekpr_require_api_user();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  header('Allow: POST');
  exit;
}

if (!imagekpr_app_csrf_verify()) {
  http_response_code(403);
  header('Content-Type: text/plain; charset=utf-8');
  echo 'Forbidden';
  exit;
}

$theme = isset($_POST['theme']) ? (string) $_POST['theme'] : '';
if ($theme !== 'light' && $theme !== 'dark') {
  http_response_code(400);
  header('Content-Type: text/plain; charset=utf-8');
  echo 'Invalid theme';
  exit;
}

try {
  $pdo = imagekpr_pdo();
  $uid = (int) imagekpr_user_id();
  $up = $pdo->prepare('UPDATE users SET theme_preference = ? WHERE id = ?');
  $up->execute([$theme, $uid]);
} catch (Throwable $e) {
  http_response_code(500);
  header('Content-Type: text/plain; charset=utf-8');
  echo 'Could not save theme. Run migrations/phase22_user_theme_preference.sql if needed.';
  exit;
}

http_response_code(204);
exit;
