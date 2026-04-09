<?php
declare(strict_types=1);

function imagekpr_footer_config(): array
{
  return [
    'brand' => [
      'name' => 'ImageKpr',
      'tagline' => 'Organize and deliver images with speed and clarity.',
      'home_url' => '/',
    ],
    'company_links' => [
      ['label' => 'About', 'url' => '/about.php'],
      ['label' => 'Case studies', 'url' => '/case-studies.php'],
      ['label' => 'Contact', 'url' => '/contact.php'],
    ],
    'resources_links' => [
      ['label' => 'Knowledge base', 'url' => '/knowledge-base.php'],
      ['label' => 'Login', 'url' => '/'],
      ['label' => 'Support', 'url' => '/support.php'],
    ],
    'legal_links' => [
      ['label' => 'Privacy', 'url' => '/privacy.php'],
      ['label' => 'Terms', 'url' => '/terms.php'],
      ['label' => 'Disclaimer', 'url' => '/disclaimer.php'],
    ],
    'contact' => [
      'address' => 'Add your business address here',
      'email' => 'hello@example.com',
      'contact_page_url' => '/contact.php',
    ],
    'newsletter' => [
      'provider_name' => 'Mailchimp',
      'form_action' => '',
      'method' => 'post',
      'button_text' => 'Subscribe',
    ],
    'made_by' => [
      'text' => 'Made by Digital Iron Giants',
      'url' => 'https://example.com',
    ],
  ];
}

function imagekpr_should_show_footer(array $options = []): bool
{
  $route = strtolower((string) parse_url((string) ($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH));
  $context = strtolower(trim((string) ($options['context'] ?? '')));

  if ($context === 'auth' || $context === 'dashboard') {
    return false;
  }

  $authPatterns = ['/auth/', '/login', '/signup', '/signin'];
  foreach ($authPatterns as $pattern) {
    if (strpos($route, $pattern) !== false) {
      return false;
    }
  }

  $dashboardPatterns = ['/admin', '/dashboard'];
  foreach ($dashboardPatterns as $pattern) {
    if (strpos($route, $pattern) !== false) {
      return false;
    }
  }

  return true;
}

function imagekpr_render_footer(array $options = []): void
{
  if (!imagekpr_should_show_footer($options)) {
    return;
  }

  $cfg = imagekpr_footer_config();
  $year = (int) date('Y');
  $newsletterAction = trim((string) ($cfg['newsletter']['form_action'] ?? ''));
  $newsletterEnabled = $newsletterAction !== '';
  ?>
  <footer class="ikpr-site-footer" aria-labelledby="ikpr-footer-title">
    <h2 id="ikpr-footer-title" class="ikpr-footer-visually-hidden">Footer</h2>
    <div class="ikpr-site-footer-grid">
      <section class="ikpr-site-footer-col">
        <a class="ikpr-site-footer-brand" href="<?php echo htmlspecialchars((string) $cfg['brand']['home_url'], ENT_QUOTES, 'UTF-8'); ?>">
          <?php echo htmlspecialchars((string) $cfg['brand']['name'], ENT_QUOTES, 'UTF-8'); ?>
        </a>
        <p class="ikpr-site-footer-text"><?php echo htmlspecialchars((string) $cfg['brand']['tagline'], ENT_QUOTES, 'UTF-8'); ?></p>
      </section>

      <section class="ikpr-site-footer-col" aria-label="Company">
        <h3>Company</h3>
        <ul>
          <?php foreach ($cfg['company_links'] as $link) { ?>
            <li><a href="<?php echo htmlspecialchars((string) $link['url'], ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars((string) $link['label'], ENT_QUOTES, 'UTF-8'); ?></a></li>
          <?php } ?>
        </ul>
      </section>

      <section class="ikpr-site-footer-col" aria-label="Resources">
        <h3>Resources</h3>
        <ul>
          <?php foreach ($cfg['resources_links'] as $link) { ?>
            <li><a href="<?php echo htmlspecialchars((string) $link['url'], ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars((string) $link['label'], ENT_QUOTES, 'UTF-8'); ?></a></li>
          <?php } ?>
        </ul>
      </section>

      <section class="ikpr-site-footer-col" aria-label="Legal">
        <h3>Legal</h3>
        <ul>
          <?php foreach ($cfg['legal_links'] as $link) { ?>
            <li><a href="<?php echo htmlspecialchars((string) $link['url'], ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars((string) $link['label'], ENT_QUOTES, 'UTF-8'); ?></a></li>
          <?php } ?>
        </ul>
      </section>

      <section class="ikpr-site-footer-col" aria-label="Contact">
        <h3>Contact</h3>
        <address class="ikpr-site-footer-text">
          <?php echo htmlspecialchars((string) $cfg['contact']['address'], ENT_QUOTES, 'UTF-8'); ?><br>
          <a href="mailto:<?php echo htmlspecialchars((string) $cfg['contact']['email'], ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars((string) $cfg['contact']['email'], ENT_QUOTES, 'UTF-8'); ?></a><br>
          <a href="<?php echo htmlspecialchars((string) $cfg['contact']['contact_page_url'], ENT_QUOTES, 'UTF-8'); ?>">Contact page</a>
        </address>
      </section>

      <section class="ikpr-site-footer-col" aria-label="Newsletter">
        <h3>Newsletter</h3>
        <form class="ikpr-site-footer-newsletter" action="<?php echo htmlspecialchars($newsletterAction, ENT_QUOTES, 'UTF-8'); ?>" method="<?php echo htmlspecialchars((string) ($cfg['newsletter']['method'] ?? 'post'), ENT_QUOTES, 'UTF-8'); ?>">
          <label for="ikpr-footer-newsletter-email" class="ikpr-footer-visually-hidden">Email</label>
          <input id="ikpr-footer-newsletter-email" type="email" name="email" placeholder="you@example.com" <?php echo !$newsletterEnabled ? 'disabled' : ''; ?> required>
          <button type="submit" <?php echo !$newsletterEnabled ? 'disabled' : ''; ?>><?php echo htmlspecialchars((string) $cfg['newsletter']['button_text'], ENT_QUOTES, 'UTF-8'); ?></button>
        </form>
        <?php if (!$newsletterEnabled) { ?>
          <p class="ikpr-site-footer-note">Set your <?php echo htmlspecialchars((string) $cfg['newsletter']['provider_name'], ENT_QUOTES, 'UTF-8'); ?> form action in `inc/footer.php`.</p>
        <?php } ?>
      </section>
    </div>

    <div class="ikpr-site-footer-bottom">
      <span>&copy; <?php echo $year; ?> <?php echo htmlspecialchars((string) $cfg['brand']['name'], ENT_QUOTES, 'UTF-8'); ?></span>
      <a href="<?php echo htmlspecialchars((string) $cfg['made_by']['url'], ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer"><?php echo htmlspecialchars((string) $cfg['made_by']['text'], ENT_QUOTES, 'UTF-8'); ?></a>
    </div>
  </footer>
  <?php
}
