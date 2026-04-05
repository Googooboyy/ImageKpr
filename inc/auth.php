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
  $st = session_status();
  if ($st === PHP_SESSION_ACTIVE || $st === PHP_SESSION_DISABLED) {
    return;
  }
  if (ob_get_level() === 0) {
    // Buffer accidental output so headers/cookies can still be set.
    ob_start();
  }
  imagekpr_ensure_config();
  $https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
  if (!$https && !empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') {
    $https = true;
  }
  $headersFile = '';
  $headersLine = 0;
  if (headers_sent($headersFile, $headersLine)) {
    return;
  }
  ini_set('session.use_strict_mode', '1');
  ini_set('session.cookie_path', '/');
  ini_set('session.cookie_httponly', '1');
  ini_set('session.cookie_secure', $https ? '1' : '0');
  if (version_compare(PHP_VERSION, '7.3.0', '>=')) {
    ini_set('session.cookie_samesite', 'Lax');
  }

  // First attempt: custom cookie name.
  session_name('ImageKprSESS');
  if (@session_start()) {
    return;
  }

  // Fallback: host may reject renamed session cookie under custom rules.
  if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    session_name('PHPSESSID');
    @session_start();
  }
}
function imagekpr_pdo(): PDO
{
  imagekpr_ensure_config();
  return new PDO(
    'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
    DB_USER,
    DB_PASS,
    [
      PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
      PDO::ATTR_EMULATE_PREPARES => true,
    ]
  );
}
function imagekpr_user_id(): int
{
  return (int) ($_SESSION['user_id'] ?? 0);
}
/** Site-root-relative URL. $parentLevels = extra dirname() steps from this script's directory (use 2 for auth/google/). */
function imagekpr_public_path(string $relativePath, int $parentLevels = 0): string
{
  $b = dirname($_SERVER['SCRIPT_NAME'] ?? '/');
  for ($i = 0; $i < $parentLevels; $i++) {
    $b = dirname($b);
  }
  $b = str_replace('\\', '/', rtrim($b, '/'));
  return ($b === '' ? '' : $b) . '/' . ltrim($relativePath, '/');
}
function imagekpr_redirect_html(string $relativePath, int $parentLevels = 0): void
{
  while (ob_get_level() > 0) {
    ob_end_clean();
  }
  header('Location: ' . imagekpr_public_path($relativePath, $parentLevels), true, 302);
  exit;
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
  if (session_status() !== PHP_SESSION_ACTIVE) {
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 'Session unavailable', 'hint' => 'PHP session_start failed']);
    exit;
  }
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
    imagekpr_redirect_html('login.php', 0);
  }
}
function imagekpr_redirect_if_logged_in(): void
{
  imagekpr_start_session();
  if (imagekpr_user_id() >= 1) {
    imagekpr_redirect_html('index.php', 0);
  }
}