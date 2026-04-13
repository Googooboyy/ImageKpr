<?php
declare(strict_types=1);

/** Public pricing page; linked from the site footer. */
function ikpr_render_pricing_laptop_mockup(): void
{
  ?>
  <div class="ikpr-pricing-laptop" role="img" aria-label="ImageKpr library on a laptop screen">
    <div class="ikpr-pricing-laptop-lid">
      <div class="ikpr-pricing-laptop-bezel">
        <div class="ikpr-pricing-laptop-screen-inner">
          <div class="ikpr-pricing-ui-bar"></div>
          <div class="ikpr-pricing-ui-body">
            <div class="ikpr-pricing-ui-slide"></div>
            <div class="ikpr-pricing-ui-thumbs">
              <span></span><span></span><span></span>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="ikpr-pricing-laptop-chassis"></div>
  </div>
  <?php
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Pricing - ImageKpr</title>
  <link rel="stylesheet" href="styles.css">
</head>
<body class="ikpr-doc-page ikpr-pricing-page">
  <?php
  require_once __DIR__ . '/inc/page_hero.php';
  imagekpr_render_page_hero();
  ?>
  <main class="ikpr-pricing-main">
    <header class="ikpr-pricing-intro">
      <h1>Pricing</h1>
      <p class="ikpr-pricing-intro-lead"><strong>Free</strong> is always available—full core experience at <strong>S$0</strong>.</p>
      <p class="ikpr-pricing-intro-lead"><strong>Silver</strong> and <strong>Gold</strong> are optional paid upgrades with higher limits if you outgrow the free tier.</p>
      <p>All prices are in <strong>SGD</strong>.</p>
    </header>

    <div class="ikpr-pricing-grid">
      <article class="ikpr-pricing-card">
        <div class="ikpr-pricing-icon" aria-hidden="true">
          <svg viewBox="0 0 64 56" width="56" height="49" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <rect x="6" y="10" width="52" height="36" rx="3"/>
            <path d="M6 18h52"/>
            <circle cx="12" cy="14" r="1.5" fill="currentColor" stroke="none"/>
            <circle cx="17" cy="14" r="1.5" fill="currentColor" stroke="none"/>
          </svg>
        </div>
        <h2 class="ikpr-pricing-tier">Free</h2>
        <div class="ikpr-pricing-price">
          <sup class="ikpr-pricing-dollar">S$</sup><span class="ikpr-pricing-amount">0</span>
          <span class="ikpr-pricing-period">/ month</span>
        </div>
        <?php ikpr_render_pricing_laptop_mockup(); ?>
        <h3 class="ikpr-pricing-features-title">Limits</h3>
        <ul class="ikpr-pricing-features">
          <li class="ikpr-pricing-feat">Up to <strong>3 MB</strong> per image file</li>
          <li class="ikpr-pricing-feat"><strong>50 MB</strong> total library storage</li>
          <li class="ikpr-pricing-feat">Up to <strong>100</strong> images in your library</li>
          <li class="ikpr-pricing-feat">Shared dashboard: up to <strong>20</strong> images per dashboard</li>
        </ul>
        <h3 class="ikpr-pricing-features-title ikpr-pricing-features-title--sub">Included</h3>
        <ul class="ikpr-pricing-features ikpr-pricing-features--compact">
          <li class="ikpr-pricing-feat">Sign in with Google; private gallery</li>
          <li class="ikpr-pricing-feat">Folders, tags, search, copyable links</li>
          <li class="ikpr-pricing-feat">Slideshows, bulk zip download, and sharing controls</li>
        </ul>
        <a class="ikpr-pricing-cta" href="/">Get started</a>
      </article>

      <article class="ikpr-pricing-card">
        <div class="ikpr-pricing-icon" aria-hidden="true">
          <svg viewBox="0 0 64 56" width="56" height="49" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <rect x="6" y="10" width="52" height="36" rx="3"/>
            <path d="M6 18h52"/>
            <circle cx="12" cy="14" r="1.5" fill="currentColor" stroke="none"/>
            <circle cx="17" cy="14" r="1.5" fill="currentColor" stroke="none"/>
            <path d="M40 28l5 5 10-12" stroke-width="2.5"/>
          </svg>
        </div>
        <h2 class="ikpr-pricing-tier">Silver</h2>
        <div class="ikpr-pricing-price">
          <sup class="ikpr-pricing-dollar">S$</sup><span class="ikpr-pricing-amount">2.99</span>
          <span class="ikpr-pricing-period">/ month</span>
          <span class="ikpr-pricing-annual">(or S$29.90/yr)</span>
        </div>
        <?php ikpr_render_pricing_laptop_mockup(); ?>
        <h3 class="ikpr-pricing-features-title">Upgrades</h3>
        <ul class="ikpr-pricing-features">
          <li class="ikpr-pricing-feat ikpr-pricing-feat--pos">Up to <strong>10 MB</strong> per image file</li>
          <li class="ikpr-pricing-feat ikpr-pricing-feat--pos"><strong>200 MB</strong> total library storage</li>
          <li class="ikpr-pricing-feat ikpr-pricing-feat--pos">Up to <strong>200</strong> images in your library</li>
          <li class="ikpr-pricing-feat ikpr-pricing-feat--pos">Shared dashboard: up to <strong>40</strong> images per dashboard</li>
        </ul>
        <h3 class="ikpr-pricing-features-title ikpr-pricing-features-title--sub">Included</h3>
        <ul class="ikpr-pricing-features ikpr-pricing-features--compact">
          <li class="ikpr-pricing-feat">Everything in Free, with higher limits above</li>
          <li class="ikpr-pricing-feat">Billed monthly or yearly via Stripe (when checkout is enabled)</li>
        </ul>
        <a class="ikpr-pricing-cta" href="contact.php">Contact us</a>
      </article>

      <article class="ikpr-pricing-card ikpr-pricing-card--featured">
        <div class="ikpr-pricing-icon" aria-hidden="true">
          <svg viewBox="0 0 64 56" width="56" height="49" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <rect x="6" y="4" width="52" height="22" rx="2"/>
            <path d="M6 11h52"/>
            <rect x="6" y="18" width="52" height="22" rx="2"/>
            <path d="M6 25h52"/>
            <rect x="6" y="32" width="52" height="20" rx="2"/>
            <path d="M6 39h52"/>
          </svg>
        </div>
        <h2 class="ikpr-pricing-tier">Gold</h2>
        <div class="ikpr-pricing-price">
          <sup class="ikpr-pricing-dollar">S$</sup><span class="ikpr-pricing-amount">9.99</span>
          <span class="ikpr-pricing-period">/ month</span>
          <span class="ikpr-pricing-annual">(or S$99.90/yr)</span>
        </div>
        <?php ikpr_render_pricing_laptop_mockup(); ?>
        <h3 class="ikpr-pricing-features-title">Upgrades</h3>
        <ul class="ikpr-pricing-features">
          <li class="ikpr-pricing-feat ikpr-pricing-feat--pos ikpr-pricing-feat--bright">Up to <strong>50 MB</strong> per image file</li>
          <li class="ikpr-pricing-feat ikpr-pricing-feat--pos ikpr-pricing-feat--bright"><strong>1 GB</strong> total library storage</li>
          <li class="ikpr-pricing-feat ikpr-pricing-feat--pos ikpr-pricing-feat--bright">Up to <strong>1,000</strong> images in your library</li>
          <li class="ikpr-pricing-feat ikpr-pricing-feat--pos ikpr-pricing-feat--bright">Shared dashboard: up to <strong>200</strong> images per dashboard</li>
        </ul>
        <h3 class="ikpr-pricing-features-title ikpr-pricing-features-title--sub">Included</h3>
        <ul class="ikpr-pricing-features ikpr-pricing-features--compact">
          <li class="ikpr-pricing-feat">Everything in Silver, with the highest self-serve limits</li>
          <li class="ikpr-pricing-feat">Same core app; best for bigger libraries and larger uploads than Silver</li>
        </ul>
        <a class="ikpr-pricing-cta" href="contact.php">Contact us</a>
      </article>

      <article class="ikpr-pricing-card ikpr-pricing-card--pro">
        <div class="ikpr-pricing-icon" aria-hidden="true">
          <svg viewBox="0 0 64 56" width="56" height="49" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <rect x="8" y="12" width="48" height="34" rx="2"/>
            <path d="M8 20h48M20 44h24"/>
            <rect x="22" y="24" width="20" height="14" rx="1"/>
            <path d="M26 31h12M26 35h8"/>
          </svg>
        </div>
        <h2 class="ikpr-pricing-tier">Pro</h2>
        <p class="ikpr-pricing-pro-tagline">Dedicated white-label deployment</p>
        <div class="ikpr-pricing-price ikpr-pricing-price--once">
          <sup class="ikpr-pricing-dollar">S$</sup><span class="ikpr-pricing-amount">999</span>
          <span class="ikpr-pricing-period ikpr-pricing-period--once">per organization, <strong>3-year</strong> license</span>
          <span class="ikpr-pricing-pro-renewal">Renews at end of term.</span>
        </div>
        <?php ikpr_render_pricing_laptop_mockup(); ?>
        <h3 class="ikpr-pricing-features-title">Summary</h3>
        <ul class="ikpr-pricing-features">
          <li class="ikpr-pricing-feat">For teams that need <strong>their own</strong> infrastructure—not just an upgrade.</li>
          <li class="ikpr-pricing-feat">White label with <strong>your own branding</strong> and technical setup.</li>
          <li class="ikpr-pricing-feat"><strong>~20&nbsp;GiB</strong> storage and your own curated limits.</li>
          <li class="ikpr-pricing-feat"><strong>Unlimited limits</strong> or as you see fit for your organisation.</li>
        </ul>
        <a class="ikpr-pricing-cta" href="contact.php">Contact us</a>
      </article>
    </div>
  </main>
  <?php
  require_once __DIR__ . '/inc/footer.php';
  imagekpr_render_footer();
  ?>
</body>
</html>
