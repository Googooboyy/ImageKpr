<?php
declare(strict_types=1);
require_once __DIR__ . '/auth.php';

/**
 * Single source of truth for pricing tiers, limits, and public card copy.
 *
 * @return array<string, array<string, mixed>>
 */
function imagekpr_plan_catalog(): array
{
  static $catalog = null;
  if (is_array($catalog)) {
    return $catalog;
  }

  $catalog = [
    'free' => [
      'key' => 'free',
      'label' => 'Free',
      'display_label' => 'Free',
      'currency' => 'SGD',
      'monthly_price' => '0',
      'annual_price' => null,
      'price_period' => '/ month',
      'tier_image_url' => null,
      'show_tier_image' => true,
      'show_recommended' => true,
      'show_included' => true,
      'upload_mb' => 3,
      'storage_bytes' => 52428800,
      'max_images' => 100,
      'shared_dashboard_cap' => 20,
      'supports_mp4' => false,
      'video_upload_mb' => null,
      'primary_title' => 'Limits',
      'primary_bullets' => [
        'Up to <strong>3 MB</strong> per image file',
        '<strong>50 MB</strong> total library storage',
        'Up to <strong>100</strong> images in your library',
        'Shared dashboard: up to <strong>20</strong> images per dashboard',
      ],
      'recommended_for_title' => 'Recommended for',
      'recommended_for_bullets' => [
        'Best for students',
        'Best for one-off projects',
        'Data may be purged after <strong>30 days</strong> of inactivity',
      ],
      'included_title' => 'Included',
      'included_bullets' => [
        'Sign in with Google; private gallery',
        'Folders, tags, search, copyable links',
        'Slideshows, bulk zip download, and sharing controls',
      ],
      'cta_label' => 'Get started',
      'cta_href' => '/',
      'self_serve' => true,
      'dedicated' => false,
    ],
    'silver' => [
      'key' => 'silver',
      'label' => 'Silver',
      'display_label' => 'Silver',
      'currency' => 'SGD',
      'monthly_price' => '2.99',
      'annual_price' => '29.90',
      'price_period' => '/ month',
      'tier_image_url' => null,
      'show_tier_image' => true,
      'show_recommended' => true,
      'show_included' => true,
      'upload_mb' => 10,
      'storage_bytes' => 209715200,
      'max_images' => 200,
      'shared_dashboard_cap' => 40,
      'supports_mp4' => true,
      'video_upload_mb' => 10,
      'primary_title' => 'Upgrades',
      'primary_bullets' => [
        'Up to <strong>10 MB</strong> per image file',
        '<strong>200 MB</strong> total library storage',
        'Up to <strong>200</strong> images in your library',
        'Shared dashboard: up to <strong>40</strong> images per dashboard',
        'MP4 video uploads (up to <strong>10 MB</strong> per clip)',
      ],
      'recommended_for_title' => 'Recommended for',
      'recommended_for_bullets' => [
        'Hobbyists who want to share images with friends, family, or forum communities',
      ],
      'included_title' => 'Included',
      'included_bullets' => [
        'Everything in Free, with higher limits above',
        'Billed monthly or yearly via Stripe (when checkout is enabled)',
      ],
      'cta_label' => 'Contact us',
      'cta_href' => 'contact.php',
      'self_serve' => true,
      'dedicated' => false,
    ],
    'gold' => [
      'key' => 'gold',
      'label' => 'Gold',
      'display_label' => 'Gold',
      'currency' => 'SGD',
      'monthly_price' => '9.99',
      'annual_price' => '99.90',
      'price_period' => '/ month',
      'tier_image_url' => null,
      'show_tier_image' => true,
      'show_recommended' => true,
      'show_included' => true,
      'upload_mb' => 50,
      'storage_bytes' => 1048576000,
      'max_images' => 1000,
      'shared_dashboard_cap' => 200,
      'supports_mp4' => true,
      'video_upload_mb' => 50,
      'primary_title' => 'Upgrades',
      'primary_bullets' => [
        'Up to <strong>50 MB</strong> per image file',
        '<strong>1 GB</strong> total library storage',
        'Up to <strong>1,000</strong> images in your library',
        'Shared dashboard: up to <strong>200</strong> images per dashboard',
        'MP4 video uploads (up to <strong>50 MB</strong> per clip)',
      ],
      'recommended_for_title' => 'Recommended for',
      'recommended_for_bullets' => [
        'Prosumers, graphic designers, photographers, or anyone who needs a robust personal stock library',
      ],
      'included_title' => 'Included',
      'included_bullets' => [
        'Everything in Silver, with higher limits above',
        'Same core app; strong fit before Platinum for very large libraries and short MP4 clips',
      ],
      'cta_label' => 'Contact us',
      'cta_href' => 'contact.php',
      'self_serve' => true,
      'dedicated' => false,
    ],
    'platinum' => [
      'key' => 'platinum',
      'label' => 'Platinum',
      'display_label' => 'Platinum',
      'currency' => 'SGD',
      'monthly_price' => '49.90',
      'annual_price' => '499',
      'price_period' => '/ month',
      'tier_image_url' => null,
      'show_tier_image' => true,
      'show_recommended' => true,
      'show_included' => true,
      'upload_mb' => 500,
      'storage_bytes' => 10737418240,
      'max_images' => 10000,
      'shared_dashboard_cap' => 2000,
      'supports_mp4' => true,
      'video_upload_mb' => 500,
      'primary_title' => 'Upgrades',
      'primary_bullets' => [
        'Up to <strong>500 MB</strong> per image file',
        '<strong>10 GB</strong> total library storage',
        'Up to <strong>10,000</strong> images in your library',
        'Shared dashboard: up to <strong>2,000</strong> images per dashboard',
        'MP4 video uploads (up to <strong>500 MB</strong> per clip)',
      ],
      'recommended_for_title' => 'Recommended for',
      'recommended_for_bullets' => [
        'Professionals and businesses with clients who look to them for reliability and accessibility',
      ],
      'included_title' => 'Included',
      'included_bullets' => [
        'Everything in Gold, with the highest self-serve limits on this service',
        'Billed monthly or yearly via Stripe (when checkout is enabled)',
      ],
      'cta_label' => 'Contact us',
      'cta_href' => 'contact.php',
      'self_serve' => true,
      'dedicated' => false,
    ],
    'pro' => [
      'key' => 'pro',
      'label' => 'Pro',
      'display_label' => 'Ultra',
      'currency' => 'SGD',
      'monthly_price' => null,
      'annual_price' => null,
      'price_period' => null,
      'tier_image_url' => null,
      'show_tier_image' => true,
      'show_recommended' => false,
      'show_included' => false,
      'upload_mb' => null,
      'storage_bytes' => 20971520000,
      'max_images' => null,
      'shared_dashboard_cap' => null,
      'supports_mp4' => true,
      'video_upload_mb' => null,
      'commercial_note' => 'S$999 list per 3-year license; renewal at end of term',
      'summary_title' => 'Summary',
      'summary_bullets' => [
        'For teams that need <strong>their own</strong> infrastructure—not just an upgrade.',
        'White label with <strong>your own branding</strong> and technical setup.',
        '<strong>~20&nbsp;GiB</strong> storage and your own curated limits.',
        '<strong>Unlimited limits</strong> or as you see fit for your organisation.',
      ],
      'cta_label' => 'Contact us',
      'cta_href' => 'contact.php',
      'self_serve' => false,
      'dedicated' => true,
    ],
  ];

  return imagekpr_plan_catalog_apply_overrides($catalog);
}

/**
 * @param array<string, array<string, mixed>> $catalog
 * @return array<string, array<string, mixed>>
 */
function imagekpr_plan_catalog_apply_overrides(array $catalog): array
{
  $raw = '';
  try {
    imagekpr_ensure_config();
    $raw = (string) (ImageKprAppSettings::get('plan_catalog_overrides_json') ?? '');
  } catch (Throwable $e) {
    return $catalog;
  }
  if (trim($raw) === '') {
    return $catalog;
  }

  $decoded = json_decode($raw, true);
  if (!is_array($decoded)) {
    return $catalog;
  }

  foreach ($decoded as $tierKey => $row) {
    if (!is_string($tierKey) || !isset($catalog[$tierKey]) || !is_array($row)) {
      continue;
    }
    foreach ($row as $field => $value) {
      if (!is_string($field) || !array_key_exists($field, $catalog[$tierKey])) {
        continue;
      }
      $base = $catalog[$tierKey][$field];
      if (is_array($base)) {
        if (!is_array($value)) {
          continue;
        }
        $out = [];
        foreach ($value as $item) {
          if (is_string($item) && trim($item) !== '') {
            $out[] = $item;
          }
        }
        $catalog[$tierKey][$field] = $out;
        continue;
      }
      if ($base === null) {
        if (is_scalar($value) || $value === null) {
          $catalog[$tierKey][$field] = $value === null ? null : (string) $value;
        }
        continue;
      }
      if (is_bool($base)) {
        $catalog[$tierKey][$field] = (bool) $value;
        continue;
      }
      if (is_int($base)) {
        if (is_int($value) || (is_string($value) && preg_match('/^-?\d+$/', $value) === 1)) {
          $catalog[$tierKey][$field] = (int) $value;
        }
        continue;
      }
      if (is_string($base) && is_scalar($value)) {
        $catalog[$tierKey][$field] = (string) $value;
      }
    }
  }

  return $catalog;
}

function imagekpr_plan_catalog_row(string $key): ?array
{
  $catalog = imagekpr_plan_catalog();
  return $catalog[$key] ?? null;
}

/**
 * Pricing-page header copy editable from admin.
 *
 * @return array{page_title:string,page_sub_title:string,page_super_sub_title:string}
 */
function imagekpr_plan_page_content(): array
{
  $defaults = [
    'page_title' => 'Pricing',
    'page_sub_title' => 'Free is always available-full core experience at S$0. Silver, Gold, and Platinum add headroom as your library grows.',
    'page_super_sub_title' => 'All prices are in SGD.',
  ];

  $raw = '';
  try {
    imagekpr_ensure_config();
    $raw = (string) (ImageKprAppSettings::get('plan_catalog_page_json') ?? '');
  } catch (Throwable $e) {
    return $defaults;
  }
  if (trim($raw) === '') {
    return $defaults;
  }

  $decoded = json_decode($raw, true);
  if (!is_array($decoded)) {
    return $defaults;
  }

  foreach (array_keys($defaults) as $field) {
    if (isset($decoded[$field]) && is_scalar($decoded[$field])) {
      $defaults[$field] = trim((string) $decoded[$field]);
    }
  }

  return $defaults;
}

function imagekpr_plan_storage_display_bytes(int $bytes): string
{
  $mb = 1024 * 1024;
  $gb = 1024 * 1024 * 1024;
  if ($bytes >= $gb && $bytes % $gb === 0) {
    return (string) ((int) round($bytes / $gb)) . ' GB';
  }
  if ($bytes >= $mb && $bytes % $mb === 0) {
    return (string) ((int) round($bytes / $mb)) . ' MB';
  }
  return imagekpr_format_bytes($bytes);
}

/**
 * @return string[]
 */
function imagekpr_plan_primary_bullets(array $plan): array
{
  $uploadMb = isset($plan['upload_mb']) && $plan['upload_mb'] !== null ? (int) $plan['upload_mb'] : null;
  $storageBytes = isset($plan['storage_bytes']) && $plan['storage_bytes'] !== null ? (int) $plan['storage_bytes'] : null;
  $maxImages = isset($plan['max_images']) && $plan['max_images'] !== null ? (int) $plan['max_images'] : null;
  $sharedDashboardCap = isset($plan['shared_dashboard_cap']) && $plan['shared_dashboard_cap'] !== null ? (int) $plan['shared_dashboard_cap'] : null;
  $supportsMp4 = !empty($plan['supports_mp4']);
  $videoUploadMb = isset($plan['video_upload_mb']) && $plan['video_upload_mb'] !== null ? (int) $plan['video_upload_mb'] : null;

  $items = [];
  if ($uploadMb !== null && $uploadMb > 0) {
    $items[] = 'Up to <strong>' . $uploadMb . ' MB</strong> per image file';
  }
  if ($storageBytes !== null && $storageBytes > 0) {
    $items[] = '<strong>' . imagekpr_plan_storage_display_bytes($storageBytes) . '</strong> total library storage';
  }
  if ($maxImages !== null && $maxImages > 0) {
    $items[] = 'Up to <strong>' . number_format($maxImages) . '</strong> images in your library';
  }
  if ($sharedDashboardCap !== null && $sharedDashboardCap > 0) {
    $items[] = 'Shared dashboard: up to <strong>' . number_format($sharedDashboardCap) . '</strong> images per dashboard';
  }
  if ($supportsMp4 && $videoUploadMb !== null && $videoUploadMb > 0) {
    $items[] = 'MP4 video uploads (up to <strong>' . $videoUploadMb . ' MB</strong> per clip)';
  }

  if ($items !== []) {
    return $items;
  }

  $fallback = $plan['primary_bullets'] ?? [];
  if (!is_array($fallback)) {
    return [];
  }
  return array_values(array_filter($fallback, static fn ($item) => is_string($item) && trim($item) !== ''));
}

function imagekpr_plan_format_text(string $text): string
{
  if ($text === '') {
    return '';
  }

  $normalized = strtr($text, [
    '<strong>' => '**',
    '</strong>' => '**',
    '<b>' => '**',
    '</b>' => '**',
    '<em>' => '*',
    '</em>' => '*',
    '<i>' => '*',
    '</i>' => '*',
    '<u>' => '__',
    '</u>' => '__',
    '<s>' => '~~',
    '</s>' => '~~',
    '<strike>' => '~~',
    '</strike>' => '~~',
    '&nbsp;' => ' ',
  ]);

  $escaped = htmlspecialchars($normalized, ENT_QUOTES, 'UTF-8');
  $patterns = [
    '/\*\*(.+?)\*\*/s' => '<strong>$1</strong>',
    '/__(.+?)__/s' => '<u>$1</u>',
    '/~~(.+?)~~/s' => '<s>$1</s>',
    '/(?<!\*)\*(?!\*)(.+?)(?<!\*)\*(?!\*)/s' => '<em>$1</em>',
  ];

  return preg_replace(array_keys($patterns), array_values($patterns), $escaped) ?? $escaped;
}

function imagekpr_plan_format_rich_text(string $text): string
{
  $text = trim($text);
  if ($text === '') {
    return '';
  }

  $paragraphs = preg_split('/\r\n\r\n|\r\r|\n\n+/', $text);
  if (!is_array($paragraphs)) {
    return '<p>' . imagekpr_plan_format_text($text) . '</p>';
  }

  $out = [];
  foreach ($paragraphs as $paragraph) {
    $paragraph = trim((string) $paragraph);
    if ($paragraph === '') {
      continue;
    }
    $formatted = imagekpr_plan_format_text($paragraph);
    $formatted = preg_replace('/\r\n|\r|\n/', "<br>\n", $formatted) ?? $formatted;
    $out[] = '<p>' . $formatted . '</p>';
  }

  return implode("\n", $out);
}

/**
 * @return string[]
 */
function imagekpr_plan_saas_keys(): array
{
  return ['free', 'silver', 'gold', 'platinum'];
}

/**
 * @return array<string, array{label:string, bytes:int, upload_mb:int}>
 */
function imagekpr_plan_saas_storage_reference(): array
{
  $catalog = imagekpr_plan_catalog();
  $out = [];
  foreach (imagekpr_plan_saas_keys() as $key) {
    $row = $catalog[$key];
    $out[$key] = [
      'label' => (string) $row['label'],
      'bytes' => (int) $row['storage_bytes'],
      'upload_mb' => (int) $row['upload_mb'],
    ];
  }
  return $out;
}

function imagekpr_plan_max_images_for_upload_mb_from_catalog(int $uploadMb): ?int
{
  $catalog = imagekpr_plan_catalog();
  foreach (imagekpr_plan_saas_keys() as $key) {
    $row = $catalog[$key];
    if ((int) ($row['upload_mb'] ?? 0) === $uploadMb) {
      return (int) ($row['max_images'] ?? 0);
    }
  }
  return null;
}

function imagekpr_plan_dashboard_cap_for_upload_mb(int $uploadMb): ?int
{
  $catalog = imagekpr_plan_catalog();
  foreach (imagekpr_plan_saas_keys() as $key) {
    $row = $catalog[$key];
    if ((int) ($row['upload_mb'] ?? 0) === $uploadMb) {
      return (int) ($row['shared_dashboard_cap'] ?? 0);
    }
  }
  return null;
}
