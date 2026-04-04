<?php
require_once __DIR__ . '/../../inc/auth.php';
require_once __DIR__ . '/../../inc/google_http.php';
imagekpr_start_session();

if (empty($_GET['state']) || empty($_SESSION['oauth_state']) || !hash_equals($_SESSION['oauth_state'], $_GET['state'])) {
  unset($_SESSION['oauth_state']);
  header('Location: ../../login.php?error=state');
  exit;
}
unset($_SESSION['oauth_state']);

if (!empty($_GET['error'])) {
  header('Location: ../../login.php?error=oauth');
  exit;
}
$code = isset($_GET['code']) ? (string) $_GET['code'] : '';
if ($code === '') {
  header('Location: ../../login.php?error=code');
  exit;
}

imagekpr_ensure_config();
if (!defined('GOOGLE_CLIENT_ID') || GOOGLE_CLIENT_ID === '' || !defined('GOOGLE_CLIENT_SECRET') || GOOGLE_CLIENT_SECRET === '' || !defined('GOOGLE_REDIRECT_URI') || GOOGLE_REDIRECT_URI === '') {
  header('Location: ../../login.php?error=config');
  exit;
}

$token = imagekpr_http_post_form('https://oauth2.googleapis.com/token', [
  'code' => $code,
  'client_id' => GOOGLE_CLIENT_ID,
  'client_secret' => GOOGLE_CLIENT_SECRET,
  'redirect_uri' => GOOGLE_REDIRECT_URI,
  'grant_type' => 'authorization_code',
]);
if (!$token || empty($token['access_token'])) {
  header('Location: ../../login.php?error=token');
  exit;
}

$info = imagekpr_http_get_json('https://openidconnect.googleapis.com/v1/userinfo', $token['access_token']);
if (!$info || empty($info['sub']) || empty($info['email'])) {
  header('Location: ../../login.php?error=userinfo');
  exit;
}

$sub = $info['sub'];
$email = $info['email'];
$name = isset($info['name']) ? (string) $info['name'] : '';
$picture = isset($info['picture']) ? (string) $info['picture'] : '';

$pdo = imagekpr_pdo();
if (!imagekpr_email_allowed($pdo, $email, $sub)) {
  header('Location: ../../login.php?error=forbidden');
  exit;
}

$adminCfg = defined('ADMIN_GOOGLE_SUB') && ADMIN_GOOGLE_SUB !== '' && $sub === ADMIN_GOOGLE_SUB;
$st = $pdo->prepare('SELECT id, is_admin FROM users WHERE google_sub = ?');
$st->execute([$sub]);
$row = $st->fetch(PDO::FETCH_ASSOC);

if ($row) {
  $isAdmin = $adminCfg ? 1 : (int) $row['is_admin'];
  $uid = (int) $row['id'];
  $up = $pdo->prepare('UPDATE users SET email = ?, name = ?, avatar_url = ?, last_login_at = NOW(), is_admin = ? WHERE id = ?');
  $up->execute([$email, $name, $picture, $isAdmin, $uid]);
} else {
  $isAdmin = $adminCfg ? 1 : 0;
  $ins = $pdo->prepare('INSERT INTO users (google_sub, email, name, avatar_url, is_admin, created_at, last_login_at) VALUES (?, ?, ?, ?, ?, NOW(), NOW())');
  $ins->execute([$sub, $email, $name, $picture, $isAdmin]);
  $uid = (int) $pdo->lastInsertId();
}

session_regenerate_id(true);
$_SESSION['user_id'] = $uid;
$_SESSION['email'] = $email;
$_SESSION['name'] = $name;
$_SESSION['google_sub'] = $sub;

header('Location: ../../index.php');
exit;