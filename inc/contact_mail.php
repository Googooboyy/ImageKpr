<?php
declare(strict_types=1);

require_once __DIR__ . '/rate_limit.php';

/**
 * Send contact form email via PHP mail(). No third-party providers.
 * Configure CONTACT_TO_EMAIL (and optionally CONTACT_FROM_EMAIL) in config.php.
 */
function imagekpr_contact_recipient_email(): string
{
  if (defined('CONTACT_TO_EMAIL') && is_string(CONTACT_TO_EMAIL) && CONTACT_TO_EMAIL !== '') {
    return CONTACT_TO_EMAIL;
  }
  return '';
}

function imagekpr_contact_from_email(): string
{
  if (defined('CONTACT_FROM_EMAIL') && is_string(CONTACT_FROM_EMAIL) && CONTACT_FROM_EMAIL !== '') {
    return CONTACT_FROM_EMAIL;
  }
  $to = imagekpr_contact_recipient_email();
  return $to !== '' ? $to : 'noreply@localhost';
}

function imagekpr_contact_send_message(string $name, string $email, string $message): bool
{
  $to = imagekpr_contact_recipient_email();
  if ($to === '' || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
    return false;
  }
  if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    return false;
  }

  $safeName = preg_replace('/[\r\n]+/', ' ', $name);
  $safeName = $safeName !== null ? trim($safeName) : '';
  if ($safeName === '') {
    $safeName = '(no name)';
  }
  $subject = '[ImageKpr] Contact form';

  $ip = function_exists('imagekpr_client_ip') ? imagekpr_client_ip() : ($_SERVER['REMOTE_ADDR'] ?? '');
  $ua = isset($_SERVER['HTTP_USER_AGENT']) ? (string) $_SERVER['HTTP_USER_AGENT'] : '';
  $body = "Name: {$safeName}\r\n";
  $body .= "Email: {$email}\r\n";
  $body .= "IP: {$ip}\r\n";
  $body .= "User-Agent: {$ua}\r\n\r\n";
  $body .= "Message:\r\n\r\n";
  $body .= str_replace(["\r\n", "\r"], "\n", $message);
  $body .= "\r\n";

  $from = imagekpr_contact_from_email();
  $headers = [
    'MIME-Version: 1.0',
    'Content-Type: text/plain; charset=UTF-8',
    'From: ImageKpr <' . $from . '>',
    'Reply-To: ' . $email,
    'X-Mailer: PHP/' . PHP_VERSION,
  ];

  return @mail($to, $subject, $body, implode("\r\n", $headers));
}
