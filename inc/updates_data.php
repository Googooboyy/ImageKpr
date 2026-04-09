<?php
declare(strict_types=1);

function imagekpr_updates_all(): array
{
  return [
    [
      'slug' => '2026-04-platform-reliability-and-bulk-workflow-updates',
      'title' => 'Platform reliability and bulk workflow updates',
      'published_at' => '2026-04-10',
      'summary' => 'Improved stability for high-volume actions and clearer operational feedback in key workflows.',
      'tags' => ['release', 'reliability', 'bulk-actions'],
      'body' => [
        'This update focuses on reliability during larger image operations. We tightened error handling and improved user-facing feedback for batch actions where timing or request limits can otherwise feel unclear.',
        'We also made consistency improvements across delete, rename, and bulk selection flows so users can better understand action outcomes without reloading or guessing system state.',
        'From an operations standpoint, this release reduces support friction by making the most common failure paths more explicit and easier to recover from.',
      ],
    ],
    [
      'slug' => '2026-04-footer-and-information-pages-live',
      'title' => 'Footer and information pages are now live',
      'published_at' => '2026-04-10',
      'summary' => 'Added a global footer and launched structured public pages for company, resources, and legal information.',
      'tags' => ['release', 'website', 'communications'],
      'body' => [
        'We added a reusable site footer and linked foundational pages such as About, Case Studies, Knowledge Base, Support, Privacy, Terms, and Disclaimer.',
        'This gives teams a stable place to publish app context, policy notes, and operational guidance without changing core product flows.',
        'The new Updates section is part of this rollout and will be used for product notices, maintenance communication, and release summaries.',
      ],
    ],
  ];
}

function imagekpr_updates_find_by_slug(string $slug): ?array
{
  foreach (imagekpr_updates_all() as $post) {
    if (($post['slug'] ?? '') === $slug) {
      return $post;
    }
  }
  return null;
}

function imagekpr_updates_sorted_desc(): array
{
  $posts = imagekpr_updates_all();
  usort($posts, static function (array $a, array $b): int {
    return strcmp((string) ($b['published_at'] ?? ''), (string) ($a['published_at'] ?? ''));
  });
  return $posts;
}

function imagekpr_updates_format_date(string $date): string
{
  $dt = DateTime::createFromFormat('Y-m-d', $date);
  if (!$dt) {
    return $date;
  }
  return $dt->format('d M Y');
}

function imagekpr_updates_prev_next(string $slug): array
{
  $posts = imagekpr_updates_sorted_desc();
  $count = count($posts);
  for ($i = 0; $i < $count; $i++) {
    if ((string) ($posts[$i]['slug'] ?? '') === $slug) {
      return [
        'previous' => $i > 0 ? $posts[$i - 1] : null,
        'next' => $i < ($count - 1) ? $posts[$i + 1] : null,
      ];
    }
  }
  return ['previous' => null, 'next' => null];
}
