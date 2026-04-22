<?php
declare(strict_types=1);
require_once __DIR__ . '/inc/plan_catalog.php';

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

function ikpr_render_pricing_tier_media(array $plan): void
{
  $imageUrl = trim((string) ($plan['tier_image_url'] ?? ''));
  if ($imageUrl !== '') {
    $alt = trim((string) (($plan['display_label'] ?? $plan['label'] ?? 'Plan') . ' tier image'));
    echo '<img class="ikpr-pricing-tier-image" src="' . htmlspecialchars($imageUrl, ENT_QUOTES, 'UTF-8') . '" alt="' . htmlspecialchars($alt, ENT_QUOTES, 'UTF-8') . '">';
    return;
  }
  ikpr_render_pricing_laptop_mockup();
}

/**
 * @param string[] $items
 */
function ikpr_render_pricing_list(array $items, string $itemClass = 'ikpr-pricing-feat'): void
{
  foreach ($items as $item) {
    if (!is_string($item) || trim($item) === '') {
      continue;
    }
    echo '<li class="' . htmlspecialchars($itemClass, ENT_QUOTES, 'UTF-8') . '">' . imagekpr_plan_format_text($item) . '</li>';
  }
}

$planCatalog = imagekpr_plan_catalog();
$pageContent = imagekpr_plan_page_content();
$freePlan = $planCatalog['free'];
$silverPlan = $planCatalog['silver'];
$goldPlan = $planCatalog['gold'];
$platinumPlan = $planCatalog['platinum'];
$ultraPlan = $planCatalog['pro'];
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
      <h1><?php echo imagekpr_plan_format_text((string) ($pageContent['page_title'] ?? 'Pricing')); ?></h1>
      <div class="ikpr-pricing-intro-copy ikpr-pricing-intro-copy--lead"><?php echo imagekpr_plan_format_rich_text((string) ($pageContent['page_sub_title'] ?? '')); ?></div>
      <div class="ikpr-pricing-intro-copy"><?php echo imagekpr_plan_format_rich_text((string) ($pageContent['page_super_sub_title'] ?? '')); ?></div>
    </header>

    <div class="ikpr-pricing-tier-band">
    <div class="ikpr-pricing-grid">
      <article class="ikpr-pricing-card ikpr-pricing-card--free">
        <div class="ikpr-pricing-sec ikpr-pricing-sec--icon">
          <div class="ikpr-pricing-icon" aria-hidden="true">
            <svg viewBox="0 0 64 56" width="56" height="49" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <rect x="6" y="10" width="52" height="36" rx="3"/>
              <path d="M6 18h52"/>
              <circle cx="12" cy="14" r="1.5" fill="currentColor" stroke="none"/>
              <circle cx="17" cy="14" r="1.5" fill="currentColor" stroke="none"/>
            </svg>
          </div>
        </div>
        <div class="ikpr-pricing-sec ikpr-pricing-sec--title">
          <h2 class="ikpr-pricing-tier"><?php echo htmlspecialchars((string) $freePlan['display_label'], ENT_QUOTES, 'UTF-8'); ?></h2>
        </div>
        <div class="ikpr-pricing-sec ikpr-pricing-sec--price">
          <div class="ikpr-pricing-price">
            <sup class="ikpr-pricing-dollar">S$</sup><span class="ikpr-pricing-amount"><?php echo htmlspecialchars((string) $freePlan['monthly_price'], ENT_QUOTES, 'UTF-8'); ?></span>
            <span class="ikpr-pricing-period"><?php echo htmlspecialchars((string) $freePlan['price_period'], ENT_QUOTES, 'UTF-8'); ?></span>
          </div>
        </div>
        <?php if (!empty($freePlan['show_tier_image'])) { ?>
        <div class="ikpr-pricing-sec ikpr-pricing-sec--mockup">
          <?php ikpr_render_pricing_tier_media($freePlan); ?>
        </div>
        <?php } ?>
        <div class="ikpr-pricing-sec ikpr-pricing-sec--primary">
          <h3 class="ikpr-pricing-features-title"><?php echo htmlspecialchars((string) $freePlan['primary_title'], ENT_QUOTES, 'UTF-8'); ?></h3>
          <ul class="ikpr-pricing-features">
            <?php ikpr_render_pricing_list(imagekpr_plan_primary_bullets($freePlan)); ?>
          </ul>
        </div>
        <?php if (!empty($freePlan['show_recommended'])) { ?>
        <div class="ikpr-pricing-sec ikpr-pricing-sec--recommended">
          <h3 class="ikpr-pricing-features-title ikpr-pricing-features-title--sub"><?php echo htmlspecialchars((string) $freePlan['recommended_for_title'], ENT_QUOTES, 'UTF-8'); ?></h3>
          <ul class="ikpr-pricing-features ikpr-pricing-features--compact">
            <?php ikpr_render_pricing_list((array) ($freePlan['recommended_for_bullets'] ?? [])); ?>
          </ul>
        </div>
        <?php } ?>
        <?php if (!empty($freePlan['show_included'])) { ?>
        <div class="ikpr-pricing-sec ikpr-pricing-sec--included">
          <h3 class="ikpr-pricing-features-title ikpr-pricing-features-title--sub"><?php echo htmlspecialchars((string) $freePlan['included_title'], ENT_QUOTES, 'UTF-8'); ?></h3>
          <ul class="ikpr-pricing-features ikpr-pricing-features--compact">
            <?php ikpr_render_pricing_list((array) ($freePlan['included_bullets'] ?? [])); ?>
          </ul>
        </div>
        <?php } ?>
        <div class="ikpr-pricing-sec ikpr-pricing-sec--cta">
          <a class="ikpr-pricing-cta" href="<?php echo htmlspecialchars((string) $freePlan['cta_href'], ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars((string) $freePlan['cta_label'], ENT_QUOTES, 'UTF-8'); ?></a>
        </div>
      </article>

      <article class="ikpr-pricing-card ikpr-pricing-card--silver">
        <div class="ikpr-pricing-sec ikpr-pricing-sec--icon">
          <div class="ikpr-pricing-icon" aria-hidden="true">
            <svg viewBox="0 0 64 56" width="56" height="49" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <rect x="6" y="10" width="52" height="36" rx="3"/>
              <path d="M6 18h52"/>
              <circle cx="12" cy="14" r="1.5" fill="currentColor" stroke="none"/>
              <circle cx="17" cy="14" r="1.5" fill="currentColor" stroke="none"/>
              <path d="M40 28l5 5 10-12" stroke-width="2.5"/>
            </svg>
          </div>
        </div>
        <div class="ikpr-pricing-sec ikpr-pricing-sec--title">
          <h2 class="ikpr-pricing-tier"><?php echo htmlspecialchars((string) $silverPlan['display_label'], ENT_QUOTES, 'UTF-8'); ?></h2>
        </div>
        <div class="ikpr-pricing-sec ikpr-pricing-sec--price">
          <div class="ikpr-pricing-price">
            <sup class="ikpr-pricing-dollar">S$</sup><span class="ikpr-pricing-amount"><?php echo htmlspecialchars((string) $silverPlan['monthly_price'], ENT_QUOTES, 'UTF-8'); ?></span>
            <span class="ikpr-pricing-period"><?php echo htmlspecialchars((string) $silverPlan['price_period'], ENT_QUOTES, 'UTF-8'); ?></span>
            <span class="ikpr-pricing-annual"><?php echo htmlspecialchars('(or S$' . (string) $silverPlan['annual_price'] . '/yr)', ENT_QUOTES, 'UTF-8'); ?></span>
          </div>
        </div>
        <?php if (!empty($silverPlan['show_tier_image'])) { ?>
        <div class="ikpr-pricing-sec ikpr-pricing-sec--mockup">
          <?php ikpr_render_pricing_tier_media($silverPlan); ?>
        </div>
        <?php } ?>
        <div class="ikpr-pricing-sec ikpr-pricing-sec--primary">
          <h3 class="ikpr-pricing-features-title"><?php echo htmlspecialchars((string) $silverPlan['primary_title'], ENT_QUOTES, 'UTF-8'); ?></h3>
          <ul class="ikpr-pricing-features">
            <?php ikpr_render_pricing_list(imagekpr_plan_primary_bullets($silverPlan), 'ikpr-pricing-feat ikpr-pricing-feat--pos'); ?>
          </ul>
        </div>
        <?php if (!empty($silverPlan['show_recommended'])) { ?>
        <div class="ikpr-pricing-sec ikpr-pricing-sec--recommended">
          <h3 class="ikpr-pricing-features-title ikpr-pricing-features-title--sub"><?php echo htmlspecialchars((string) $silverPlan['recommended_for_title'], ENT_QUOTES, 'UTF-8'); ?></h3>
          <ul class="ikpr-pricing-features ikpr-pricing-features--compact">
            <?php ikpr_render_pricing_list((array) ($silverPlan['recommended_for_bullets'] ?? [])); ?>
          </ul>
        </div>
        <?php } ?>
        <?php if (!empty($silverPlan['show_included'])) { ?>
        <div class="ikpr-pricing-sec ikpr-pricing-sec--included">
          <h3 class="ikpr-pricing-features-title ikpr-pricing-features-title--sub"><?php echo htmlspecialchars((string) $silverPlan['included_title'], ENT_QUOTES, 'UTF-8'); ?></h3>
          <ul class="ikpr-pricing-features ikpr-pricing-features--compact">
            <?php ikpr_render_pricing_list((array) ($silverPlan['included_bullets'] ?? [])); ?>
          </ul>
        </div>
        <?php } ?>
        <div class="ikpr-pricing-sec ikpr-pricing-sec--cta">
          <a class="ikpr-pricing-cta" href="<?php echo htmlspecialchars((string) $silverPlan['cta_href'], ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars((string) $silverPlan['cta_label'], ENT_QUOTES, 'UTF-8'); ?></a>
        </div>
      </article>

      <article class="ikpr-pricing-card ikpr-pricing-card--featured">
        <div class="ikpr-pricing-sec ikpr-pricing-sec--icon">
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
        </div>
        <div class="ikpr-pricing-sec ikpr-pricing-sec--title">
          <h2 class="ikpr-pricing-tier"><?php echo htmlspecialchars((string) $goldPlan['display_label'], ENT_QUOTES, 'UTF-8'); ?></h2>
        </div>
        <div class="ikpr-pricing-sec ikpr-pricing-sec--price">
          <div class="ikpr-pricing-price">
            <sup class="ikpr-pricing-dollar">S$</sup><span class="ikpr-pricing-amount"><?php echo htmlspecialchars((string) $goldPlan['monthly_price'], ENT_QUOTES, 'UTF-8'); ?></span>
            <span class="ikpr-pricing-period"><?php echo htmlspecialchars((string) $goldPlan['price_period'], ENT_QUOTES, 'UTF-8'); ?></span>
            <span class="ikpr-pricing-annual"><?php echo htmlspecialchars('(or S$' . (string) $goldPlan['annual_price'] . '/yr)', ENT_QUOTES, 'UTF-8'); ?></span>
          </div>
        </div>
        <?php if (!empty($goldPlan['show_tier_image'])) { ?>
        <div class="ikpr-pricing-sec ikpr-pricing-sec--mockup">
          <?php ikpr_render_pricing_tier_media($goldPlan); ?>
        </div>
        <?php } ?>
        <div class="ikpr-pricing-sec ikpr-pricing-sec--primary">
          <h3 class="ikpr-pricing-features-title"><?php echo htmlspecialchars((string) $goldPlan['primary_title'], ENT_QUOTES, 'UTF-8'); ?></h3>
          <ul class="ikpr-pricing-features">
            <?php ikpr_render_pricing_list(imagekpr_plan_primary_bullets($goldPlan), 'ikpr-pricing-feat ikpr-pricing-feat--pos ikpr-pricing-feat--bright'); ?>
          </ul>
        </div>
        <?php if (!empty($goldPlan['show_recommended'])) { ?>
        <div class="ikpr-pricing-sec ikpr-pricing-sec--recommended">
          <h3 class="ikpr-pricing-features-title ikpr-pricing-features-title--sub"><?php echo htmlspecialchars((string) $goldPlan['recommended_for_title'], ENT_QUOTES, 'UTF-8'); ?></h3>
          <ul class="ikpr-pricing-features ikpr-pricing-features--compact">
            <?php ikpr_render_pricing_list((array) ($goldPlan['recommended_for_bullets'] ?? [])); ?>
          </ul>
        </div>
        <?php } ?>
        <?php if (!empty($goldPlan['show_included'])) { ?>
        <div class="ikpr-pricing-sec ikpr-pricing-sec--included">
          <h3 class="ikpr-pricing-features-title ikpr-pricing-features-title--sub"><?php echo htmlspecialchars((string) $goldPlan['included_title'], ENT_QUOTES, 'UTF-8'); ?></h3>
          <ul class="ikpr-pricing-features ikpr-pricing-features--compact">
            <?php ikpr_render_pricing_list((array) ($goldPlan['included_bullets'] ?? [])); ?>
          </ul>
        </div>
        <?php } ?>
        <div class="ikpr-pricing-sec ikpr-pricing-sec--cta">
          <a class="ikpr-pricing-cta" href="<?php echo htmlspecialchars((string) $goldPlan['cta_href'], ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars((string) $goldPlan['cta_label'], ENT_QUOTES, 'UTF-8'); ?></a>
        </div>
      </article>

      <article class="ikpr-pricing-card ikpr-pricing-card--platinum">
        <div class="ikpr-pricing-sec ikpr-pricing-sec--icon">
          <div class="ikpr-pricing-icon" aria-hidden="true">
            <svg viewBox="0 0 64 56" width="56" height="49" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <rect x="6" y="10" width="52" height="36" rx="3"/>
              <path d="M6 18h52"/>
              <path d="M14 26h36M14 32h28M14 38h32"/>
            </svg>
          </div>
        </div>
        <div class="ikpr-pricing-sec ikpr-pricing-sec--title">
          <h2 class="ikpr-pricing-tier"><?php echo htmlspecialchars((string) $platinumPlan['display_label'], ENT_QUOTES, 'UTF-8'); ?></h2>
        </div>
        <div class="ikpr-pricing-sec ikpr-pricing-sec--price">
          <div class="ikpr-pricing-price">
            <sup class="ikpr-pricing-dollar">S$</sup><span class="ikpr-pricing-amount"><?php echo htmlspecialchars((string) $platinumPlan['monthly_price'], ENT_QUOTES, 'UTF-8'); ?></span>
            <span class="ikpr-pricing-period"><?php echo htmlspecialchars((string) $platinumPlan['price_period'], ENT_QUOTES, 'UTF-8'); ?></span>
            <span class="ikpr-pricing-annual"><?php echo htmlspecialchars('(or S$' . (string) $platinumPlan['annual_price'] . '/yr)', ENT_QUOTES, 'UTF-8'); ?></span>
          </div>
        </div>
        <?php if (!empty($platinumPlan['show_tier_image'])) { ?>
        <div class="ikpr-pricing-sec ikpr-pricing-sec--mockup">
          <?php ikpr_render_pricing_tier_media($platinumPlan); ?>
        </div>
        <?php } ?>
        <div class="ikpr-pricing-sec ikpr-pricing-sec--primary">
          <h3 class="ikpr-pricing-features-title"><?php echo htmlspecialchars((string) $platinumPlan['primary_title'], ENT_QUOTES, 'UTF-8'); ?></h3>
          <ul class="ikpr-pricing-features">
            <?php ikpr_render_pricing_list(imagekpr_plan_primary_bullets($platinumPlan), 'ikpr-pricing-feat ikpr-pricing-feat--pos ikpr-pricing-feat--platinum'); ?>
          </ul>
        </div>
        <?php if (!empty($platinumPlan['show_recommended'])) { ?>
        <div class="ikpr-pricing-sec ikpr-pricing-sec--recommended">
          <h3 class="ikpr-pricing-features-title ikpr-pricing-features-title--sub"><?php echo htmlspecialchars((string) $platinumPlan['recommended_for_title'], ENT_QUOTES, 'UTF-8'); ?></h3>
          <ul class="ikpr-pricing-features ikpr-pricing-features--compact">
            <?php ikpr_render_pricing_list((array) ($platinumPlan['recommended_for_bullets'] ?? [])); ?>
          </ul>
        </div>
        <?php } ?>
        <?php if (!empty($platinumPlan['show_included'])) { ?>
        <div class="ikpr-pricing-sec ikpr-pricing-sec--included">
          <h3 class="ikpr-pricing-features-title ikpr-pricing-features-title--sub"><?php echo htmlspecialchars((string) $platinumPlan['included_title'], ENT_QUOTES, 'UTF-8'); ?></h3>
          <ul class="ikpr-pricing-features ikpr-pricing-features--compact">
            <?php ikpr_render_pricing_list((array) ($platinumPlan['included_bullets'] ?? [])); ?>
          </ul>
        </div>
        <?php } ?>
        <div class="ikpr-pricing-sec ikpr-pricing-sec--cta">
          <a class="ikpr-pricing-cta" href="<?php echo htmlspecialchars((string) $platinumPlan['cta_href'], ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars((string) $platinumPlan['cta_label'], ENT_QUOTES, 'UTF-8'); ?></a>
        </div>
      </article>
    </div>

      <article class="ikpr-pricing-card ikpr-pricing-card--pro ikpr-pricing-card--ultra-wide">
        <div class="ikpr-pricing-ultra-grid">
          <div class="ikpr-pricing-ultra-col ikpr-pricing-ultra-col--lead">
            <div class="ikpr-pricing-sec ikpr-pricing-sec--icon">
              <div class="ikpr-pricing-icon" aria-hidden="true">
                <svg viewBox="0 0 64 56" width="56" height="49" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <rect x="8" y="12" width="48" height="34" rx="2"/>
                  <path d="M8 20h48M20 44h24"/>
                  <rect x="22" y="24" width="20" height="14" rx="1"/>
                  <path d="M26 31h12M26 35h8"/>
                </svg>
              </div>
            </div>
            <div class="ikpr-pricing-sec ikpr-pricing-sec--title">
              <h2 class="ikpr-pricing-tier"><?php echo htmlspecialchars((string) $ultraPlan['display_label'], ENT_QUOTES, 'UTF-8'); ?></h2>
            </div>
            <div class="ikpr-pricing-sec ikpr-pricing-sec--price">
              <p class="ikpr-pricing-pro-tagline">Dedicated white-label deployment</p>
              <div class="ikpr-pricing-price ikpr-pricing-price--once ikpr-pricing-price--pro-ask">
                <span class="ikpr-pricing-amount ikpr-pricing-amount--ask" aria-label="Pricing on request">$ASK</span>
                <span class="ikpr-pricing-period ikpr-pricing-period--once">Per-organization deployment. <strong>Contact us</strong> for pricing and terms.</span>
              </div>
            </div>
          </div>
          <div class="ikpr-pricing-ultra-col ikpr-pricing-ultra-col--visual">
            <?php if (!empty($ultraPlan['show_tier_image'])) { ?>
            <div class="ikpr-pricing-sec ikpr-pricing-sec--mockup">
              <?php ikpr_render_pricing_tier_media($ultraPlan); ?>
            </div>
            <?php } ?>
          </div>
          <div class="ikpr-pricing-ultra-col ikpr-pricing-ultra-col--summary">
            <div class="ikpr-pricing-sec ikpr-pricing-sec--primary">
              <h3 class="ikpr-pricing-features-title"><?php echo htmlspecialchars((string) $ultraPlan['summary_title'], ENT_QUOTES, 'UTF-8'); ?></h3>
              <ul class="ikpr-pricing-features ikpr-pricing-features--ultra-summary">
                <?php ikpr_render_pricing_list((array) ($ultraPlan['summary_bullets'] ?? [])); ?>
              </ul>
            </div>
          </div>
          <div class="ikpr-pricing-ultra-col ikpr-pricing-ultra-col--cta">
            <div class="ikpr-pricing-sec ikpr-pricing-sec--cta">
              <a class="ikpr-pricing-cta ikpr-pricing-cta--pro" href="<?php echo htmlspecialchars((string) $ultraPlan['cta_href'], ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars((string) $ultraPlan['cta_label'], ENT_QUOTES, 'UTF-8'); ?></a>
            </div>
          </div>
        </div>
      </article>
    </div>
  </main>
  <?php
  require_once __DIR__ . '/inc/footer.php';
  imagekpr_render_footer();
  ?>
</body>
</html>
