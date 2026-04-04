<?php
function imagekpr_ensure_config(): void
{
  static $done = false;
  if ($done) {
    return;
  }
  $cfg = dirname(__DIR__) . '/config.php';
  if (!is_file($cfg)) {
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Configuration missing. Copy config.example.php to config.php.';
    exit;
  }
  require_once $cfg;
  require_once __DIR__ . '/request_limits.php';
  $done = true;
}
function imagekpr_json_security_headers(): void
{
  header('X-Content-Type-Options: nosniff');
}
function imagekpr_start_session(): void
{
  if (session_status() !== PHP_SESSION_NONE) {
    return;
  }
  imagekpr_ensure_config();
  ini_set('session.cookie_httponly', '1');
  ini_set('session.use_strict_mode', '1');
  ini_set('session.cookie_samesite', 'Lax');
  $https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
  if (!$https && !empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') {
    $https = true;
  }
  if ($https) {
    ini_set('session.cookie_secure', '1');
  }
  session_name('ImageKprSESS');
  session_start();
}
function imagekpr_pdo(): PDO
{
  imagekpr_ensure_config();
  return new PDO(
    'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
    DB_USER,
    DB_PASS,
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
  );
}
function imagekpr_user_id(): int
{
  return (int) ($_SESSION['user_id'] ?? 0);
}
function imagekpr_email_allowed(PDO $pdo, string $email, string $googleSub): bool
{
  if (defined('ADMIN_GOOGLE_SUB') && ADMIN_GOOGLE_SUB !== '' && $googleSub === ADMIN_GOOGLE_SUB) {
    return true;
  }
  $n = (int) $pdo->query('SELECT COUNT(*) FROM email_allowlist')->fetchColumn();
  if ($n === 0) {
    return true;
  }
  $st = $pdo->prepare('SELECT 1 FROM email_allowlist WHERE email = ? LIMIT 1');
  $st->execute([strtolower(trim($email))]);
  return (bool) $st->fetchColumn();
}
function imagekpr_require_api_user(): void
{
  imagekpr_start_session();
  imagekpr_json_security_headers();
  if (imagekpr_user_id() < 1) {
    http_response_code(401);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 'Unauthorized', 'login' => 'login.php']);
    exit;
  }
}
function imagekpr_require_login_html(): void
{
  imagekpr_start_session();
  if (imagekpr_user_id() < 1) {
    header('Location: login.php');
    exit;
  }
}
function imagekpr_redirect_if_logged_in(): void
{
  imagekpr_start_session();
  if (imagekpr_user_id() >= 1) {
    header('Location: index.php');
    exit;
  }
}