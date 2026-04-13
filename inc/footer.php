<?php
declare(strict_types=1);

function imagekpr_footer_config(): array
{
  return [
    'company_links' => [
      ['label' => 'Login', 'url' => '/'],
      ['label' => 'About', 'url' => '/about.php'],
      ['label' => 'Features', 'url' => '/features.php'],
      ['label' => 'Pricing', 'url' => '/pricing.php'],
      ['label' => 'Contact', 'url' => '/contact.php'],
      ['label' => 'Updates', 'url' => '/updates.php'],
    ],
    'resources_links' => [
      ['label' => 'Knowledge base', 'url' => '/knowledge-base.php'],
      ['label' => 'Use cases', 'url' => '/case-studies.php'],
      ['label' => 'Support', 'url' => '/support.php'],
    ],
    'legal_links' => [
      ['label' => 'Privacy', 'url' => '/privacy.php'],
      ['label' => 'Terms', 'url' => '/terms.php'],
      ['label' => 'Disclaimer', 'url' => '/disclaimer.php'],
    ],
    'contact' => [
      'address' => '60 Paya Lebar Road, #06-28, Singapore 409051',
      'email' => 'mar@mar.sg',
      'contact_page_url' => '/contact.php',
    ],
    'made_by' => [
      'text' => 'Mar.SG',
      'url' => 'https://mar.sg',
    ],
  ];
}

function imagekpr_should_show_footer(array $options = []): bool
{
  return true;
}

function imagekpr_render_footer(array $options = []): void
{
  if (!imagekpr_should_show_footer($options)) {
    return;
  }

  $cfg = imagekpr_footer_config();
  $year = (int) date('Y');
  ?>
  <footer class="ikpr-site-footer" aria-labelledby="ikpr-footer-title">
    <h2 id="ikpr-footer-title" class="ikpr-footer-visually-hidden">Footer</h2>
    <div class="ikpr-site-footer-grid">
      <section class="ikpr-site-footer-col" aria-label="ImageKpr">
        <h3>ImageKpr</h3>
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

    </div>

    <div class="ikpr-site-footer-bottom">
      <span>&copy; <?php echo $year; ?> ImageKpr</span>
      <a href="<?php echo htmlspecialchars((string) $cfg['made_by']['url'], ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer"><?php echo htmlspecialchars((string) $cfg['made_by']['text'], ENT_QUOTES, 'UTF-8'); ?></a>
    </div>
  </footer>
  <?php
}
