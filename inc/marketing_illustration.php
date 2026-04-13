<?php
declare(strict_types=1);

/**
 * Outputs a framed decorative SVG for marketing pages (features, case studies, etc.).
 *
 * @param string $ariaLabel Short description for assistive tech.
 * @param string $variant   Visual style: default, grid, folders, link, search, slideshow.
 * @param string $palette   Colour theme: blue (default), green (muted sage for use cases).
 */
function ikpr_marketing_render_illustration(string $ariaLabel, string $variant = 'default', string $palette = 'blue'): void
{
  $palette = $palette === 'green' ? 'green' : 'blue';

  $palettes = [
    'blue' => [
      'g0' => '#e8eef5',
      'g1' => '#d4dde8',
      'chrome' => '#c5d0dc',
      'dot' => '#9aa8b8',
      'block' => '#b8c4d4',
      'stroke' => '#8fa0b5',
      'accent' => '#7a8fa3',
      'panel' => '#cfd8e6',
      'line' => '#b0bdd0',
    ],
    'green' => [
      'g0' => '#e8f1ea',
      'g1' => '#d4e6d9',
      'chrome' => '#c4d6ca',
      'dot' => '#8aa394',
      'block' => '#b5d0be',
      'stroke' => '#6d9078',
      'accent' => '#5a8f6a',
      'panel' => '#d0e0d4',
      'line' => '#a8c4b2',
    ],
  ];

  $c = $palettes[$palette];
  $gradId = 'ikpr-ill-' . preg_replace('/[^a-z0-9]+/i', '', $variant) . '-' . $palette . '-' . substr(sha1($variant . $palette), 0, 8);
  ?>
  <figure class="ikpr-features-media">
    <div class="ikpr-features-media-frame" role="img" aria-label="<?php echo htmlspecialchars($ariaLabel, ENT_QUOTES, 'UTF-8'); ?>">
      <svg class="ikpr-features-media-svg" viewBox="0 0 640 400" width="640" height="400" aria-hidden="true">
        <defs>
          <linearGradient id="<?php echo htmlspecialchars($gradId, ENT_QUOTES, 'UTF-8'); ?>" x1="0%" y1="0%" x2="100%" y2="100%">
            <stop offset="0%" stop-color="<?php echo htmlspecialchars($c['g0'], ENT_QUOTES, 'UTF-8'); ?>"/>
            <stop offset="100%" stop-color="<?php echo htmlspecialchars($c['g1'], ENT_QUOTES, 'UTF-8'); ?>"/>
          </linearGradient>
        </defs>
        <rect width="640" height="400" rx="12" fill="url(#<?php echo htmlspecialchars($gradId, ENT_QUOTES, 'UTF-8'); ?>)"/>
        <rect x="24" y="24" width="592" height="40" rx="6" fill="<?php echo htmlspecialchars($c['chrome'], ENT_QUOTES, 'UTF-8'); ?>" opacity="0.55"/>
        <circle cx="48" cy="44" r="6" fill="<?php echo htmlspecialchars($c['dot'], ENT_QUOTES, 'UTF-8'); ?>"/>
        <circle cx="72" cy="44" r="6" fill="<?php echo htmlspecialchars($c['dot'], ENT_QUOTES, 'UTF-8'); ?>"/>
        <circle cx="96" cy="44" r="6" fill="<?php echo htmlspecialchars($c['dot'], ENT_QUOTES, 'UTF-8'); ?>"/>
        <?php if ($variant === 'grid') { ?>
        <g fill="<?php echo htmlspecialchars($c['block'], ENT_QUOTES, 'UTF-8'); ?>" opacity="0.9">
          <rect x="40" y="96" width="170" height="120" rx="6"/>
          <rect x="230" y="96" width="170" height="120" rx="6"/>
          <rect x="420" y="96" width="170" height="120" rx="6"/>
          <rect x="40" y="232" width="260" height="140" rx="6"/>
          <rect x="320" y="232" width="270" height="140" rx="6"/>
        </g>
        <?php } elseif ($variant === 'folders') { ?>
        <g stroke="<?php echo htmlspecialchars($c['stroke'], ENT_QUOTES, 'UTF-8'); ?>" stroke-width="3" fill="none" stroke-linecap="round">
          <path d="M56 120h180l24 32h224"/>
          <path d="M56 168h528"/>
        </g>
        <rect x="56" y="200" width="200" height="44" rx="6" fill="<?php echo htmlspecialchars($c['block'], ENT_QUOTES, 'UTF-8'); ?>"/>
        <rect x="56" y="260" width="240" height="44" rx="6" fill="<?php echo htmlspecialchars($c['block'], ENT_QUOTES, 'UTF-8'); ?>"/>
        <rect x="56" y="320" width="180" height="44" rx="6" fill="<?php echo htmlspecialchars($c['block'], ENT_QUOTES, 'UTF-8'); ?>"/>
        <?php } elseif ($variant === 'link') { ?>
        <path d="M280 210c0-38 32-70 70-70h40c38 0 70 32 70 70s-32 70-70 70h-40" fill="none" stroke="<?php echo htmlspecialchars($c['accent'], ENT_QUOTES, 'UTF-8'); ?>" stroke-width="10" stroke-linecap="round"/>
        <path d="M360 210c0 38-32 70-70 70h-40c-38 0-70-32-70-70s32-70 70-70h40" fill="none" stroke="<?php echo htmlspecialchars($c['accent'], ENT_QUOTES, 'UTF-8'); ?>" stroke-width="10" stroke-linecap="round"/>
        <?php } elseif ($variant === 'search') { ?>
        <circle cx="280" cy="200" r="72" fill="none" stroke="<?php echo htmlspecialchars($c['accent'], ENT_QUOTES, 'UTF-8'); ?>" stroke-width="10"/>
        <path d="M332 248l88 88" stroke="<?php echo htmlspecialchars($c['accent'], ENT_QUOTES, 'UTF-8'); ?>" stroke-width="10" stroke-linecap="round"/>
        <?php } elseif ($variant === 'slideshow') { ?>
        <rect x="120" y="120" width="400" height="240" rx="8" fill="<?php echo htmlspecialchars($c['panel'], ENT_QUOTES, 'UTF-8'); ?>"/>
        <polygon points="280,200 280,280 360,240" fill="<?php echo htmlspecialchars($c['accent'], ENT_QUOTES, 'UTF-8'); ?>"/>
        <?php } else { ?>
        <rect x="80" y="120" width="480" height="240" rx="8" fill="<?php echo htmlspecialchars($c['panel'], ENT_QUOTES, 'UTF-8'); ?>"/>
        <rect x="120" y="160" width="400" height="24" rx="4" fill="<?php echo htmlspecialchars($c['line'], ENT_QUOTES, 'UTF-8'); ?>"/>
        <rect x="120" y="200" width="320" height="24" rx="4" fill="<?php echo htmlspecialchars($c['line'], ENT_QUOTES, 'UTF-8'); ?>"/>
        <rect x="120" y="240" width="360" height="24" rx="4" fill="<?php echo htmlspecialchars($c['line'], ENT_QUOTES, 'UTF-8'); ?>"/>
        <?php } ?>
      </svg>
    </div>
  </figure>
  <?php
}
