<?php
ob_start();
require_once __DIR__ . '/inc/auth.php';
require_once __DIR__ . '/inc/rate_limit.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header('Location: index.php', true, 303);
  exit;
}

imagekpr_start_session();
if (!imagekpr_guest_csrf_verify()) {
  header('Location: index.php?request=csrf', true, 303);
  exit;
}

imagekpr_ensure_config();

if (!imagekpr_accept_access_requests_enabled()) {
  header('Location: index.php?request=closed', true, 303);
  exit;
}

if (!imagekpr_rate_limit('access_request', 10, 3600)) {
  header('Location: index.php?request=ratelimit', true, 303);
  exit;
}

$em = strtolower(trim((string) ($_POST['email'] ?? '')));
if ($em === '' || !filter_var($em, FILTER_VALIDATE_EMAIL)) {
  header('Location: index.php?request=invalid', true, 303);
  exit;
}

$note = trim((string) ($_POST['note'] ?? ''));
if (strlen($note) > 2000) {
  header('Location: index.php?request=invalid', true, 303);
  exit;
}
$noteDb = $note === '' ? null : $note;

try {
  $pdo = imagekpr_pdo();
  $st = $pdo->prepare('SELECT 1 FROM email_allowlist WHERE email = ? LIMIT 1');
  $st->execute([$em]);
  if ($st->fetchColumn()) {
    header('Location: index.php?request=already_allowed', true, 303);
    exit;
  }
  $st2 = $pdo->prepare('SELECT id FROM email_access_requests WHERE email = ? LIMIT 1');
  $st2->execute([$em]);
  if ($st2->fetchColumn()) {
    header('Location: index.php?request=duplicate', true, 303);
    exit;
  }
  $ins = $pdo->prepare('INSERT INTO email_access_requests (email, note) VALUES (?, ?)');
  $ins->execute([$em, $noteDb]);
} catch (Throwable $e) {
  header('Location: index.php?request=database', true, 303);
  exit;
}

header('Location: index.php?request=ok', true, 303);
exit;
