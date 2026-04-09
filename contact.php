<?php
declare(strict_types=1);
require_once __DIR__ . '/inc/auth.php';
require_once __DIR__ . '/inc/rate_limit.php';
require_once __DIR__ . '/inc/contact_mail.php';

imagekpr_ensure_config();
imagekpr_start_session();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!imagekpr_guest_csrf_verify()) {
    $_SESSION['contact_error'] = 'Your session expired. Please try again.';
    header('Location: contact.php', true, 303);
    exit;
  }
  if (!imagekpr_rate_limit('contact_form', 5, 3600)) {
    $_SESSION['contact_error'] = 'Too many submissions from this address. Please try again in an hour.';
    header('Location: contact.php', true, 303);
    exit;
  }
  $honeypot = trim((string) ($_POST['website'] ?? ''));
  if ($honeypot !== '') {
    header('Location: contact-thanks.php', true, 303);
    exit;
  }

  $name = trim((string) ($_POST['name'] ?? ''));
  $email = trim((string) ($_POST['email'] ?? ''));
  $message = trim((string) ($_POST['message'] ?? ''));

  if ($name === '' || strlen($name) > 200) {
    $_SESSION['contact_error'] = 'Please enter your name (max 200 characters).';
    header('Location: contact.php', true, 303);
    exit;
  }
  if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['contact_error'] = 'Please enter a valid email address.';
    header('Location: contact.php', true, 303);
    exit;
  }
  if ($message === '' || strlen($message) > 8000) {
    $_SESSION['contact_error'] = 'Please enter a message (max 8000 characters).';
    header('Location: contact.php', true, 303);
    exit;
  }

  if (imagekpr_contact_recipient_email() === '') {
    $_SESSION['contact_error'] = 'The contact form is not configured yet. Please email us directly.';
    header('Location: contact.php', true, 303);
    exit;
  }

  if (!imagekpr_contact_send_message($name, $email, $message)) {
    $_SESSION['contact_error'] = 'We could not send your message. Please try again or email us directly.';
    header('Location: contact.php', true, 303);
    exit;
  }

  header('Location: contact-thanks.php', true, 303);
  exit;
}

$contactError = isset($_SESSION['contact_error']) ? (string) $_SESSION['contact_error'] : '';
unset($_SESSION['contact_error']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Contact - ImageKpr</title>
  <link rel="stylesheet" href="styles.css">
</head>
<body class="ikpr-doc-page">
  <?php
  require_once __DIR__ . '/inc/page_hero.php';
  imagekpr_render_page_hero();
  ?>
  <main class="ikpr-doc-wrap">
    <h1>Contact</h1>
    <p class="ikpr-doc-lead">Reach out for onboarding, support requests, and operational questions.</p>

    <?php if ($contactError !== '') { ?>
      <p class="ikpr-contact-alert" role="alert"><?php echo htmlspecialchars($contactError, ENT_QUOTES, 'UTF-8'); ?></p>
    <?php } ?>

    <h2>Send a message</h2>
    <form class="ikpr-contact-form" method="post" action="contact.php" novalidate>
      <?php echo imagekpr_guest_csrf_field(); ?>
      <p class="ikpr-contact-honeypot" aria-hidden="true">
        <label for="ikpr-contact-website">Website</label>
        <input type="text" id="ikpr-contact-website" name="website" tabindex="-1" autocomplete="off">
      </p>
      <label class="ikpr-contact-label" for="ikpr-contact-name">Name</label>
      <input class="ikpr-contact-input" type="text" id="ikpr-contact-name" name="name" required maxlength="200" autocomplete="name">

      <label class="ikpr-contact-label" for="ikpr-contact-email">Email</label>
      <input class="ikpr-contact-input" type="email" id="ikpr-contact-email" name="email" required maxlength="254" autocomplete="email">

      <label class="ikpr-contact-label" for="ikpr-contact-message">Message</label>
      <textarea class="ikpr-contact-textarea" id="ikpr-contact-message" name="message" required maxlength="8000" rows="8"></textarea>

      <button class="ikpr-contact-submit" type="submit">Send message</button>
    </form>


    <h2>What to include</h2>
    <ul>
      <li>Your organization or team name.</li>
      <li>The account email(s) involved.</li>
      <li>A concise summary of the request or issue.</li>
      <li>Any deadline or urgency constraints.</li>
    </ul>

    <h2>Commercial and partnership inquiries</h2>
    <p>For implementation, customization, or scale requirements, include expected usage volume and your preferred deployment environment.</p>
  </main>
  <?php
  require_once __DIR__ . '/inc/footer.php';
  imagekpr_render_footer();
  ?>
</body>
</html>
