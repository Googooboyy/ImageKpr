<?php
declare(strict_types=1);
require_once __DIR__ . '/auth.php';

function imagekpr_updates_static_seed(): array
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

function imagekpr_updates_db_post_to_public(array $row): array
{
  $bodyText = (string) ($row['body'] ?? '');
  $parts = preg_split('/\R{2,}/', trim($bodyText));
  $body = [];
  if (is_array($parts)) {
    foreach ($parts as $p) {
      $p = trim((string) $p);
      if ($p !== '') {
        $body[] = $p;
      }
    }
  }
  $tags = [];
  $tagsRaw = isset($row['tags_json']) ? (string) $row['tags_json'] : '';
  if ($tagsRaw !== '') {
    $decoded = json_decode($tagsRaw, true);
    if (is_array($decoded)) {
      foreach ($decoded as $t) {
        $tv = trim((string) $t);
        if ($tv !== '') {
          $tags[] = $tv;
        }
      }
    }
  }

  return [
    'slug' => (string) ($row['slug'] ?? ''),
    'title' => (string) ($row['title'] ?? ''),
    'published_at' => (string) ($row['published_at'] ?? ''),
    'summary' => (string) ($row['summary'] ?? ''),
    'tags' => $tags,
    'body' => $body,
  ];
}

function imagekpr_updates_all(): array
{
  try {
    $pdo = imagekpr_pdo();
    $sql = 'SELECT slug, title, published_at, summary, body, tags_json
      FROM updates_posts
      WHERE status = "published"
      ORDER BY published_at DESC, id DESC';
    $rows = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    $out = [];
    foreach ($rows as $row) {
      $out[] = imagekpr_updates_db_post_to_public($row);
    }
    return $out;
  } catch (Throwable $e) {
    return imagekpr_updates_static_seed();
  }
}

function imagekpr_updates_find_by_slug(string $slug): ?array
{
  $slug = trim($slug);
  if ($slug === '') {
    return null;
  }
  try {
    $pdo = imagekpr_pdo();
    $st = $pdo->prepare('SELECT slug, title, published_at, summary, body, tags_json
      FROM updates_posts
      WHERE status = "published" AND slug = ?
      LIMIT 1');
    $st->execute([$slug]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    if ($row !== false) {
      return imagekpr_updates_db_post_to_public($row);
    }
    return null;
  } catch (Throwable $e) {
    foreach (imagekpr_updates_static_seed() as $post) {
      if (($post['slug'] ?? '') === $slug) {
        return $post;
      }
    }
    return null;
  }
}

function imagekpr_updates_sorted_desc(): array
{
  return imagekpr_updates_all();
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

function imagekpr_updates_count_published(): int
{
  try {
    $pdo = imagekpr_pdo();
    return (int) $pdo->query('SELECT COUNT(*) FROM updates_posts WHERE status = "published"')->fetchColumn();
  } catch (Throwable $e) {
    return count(imagekpr_updates_static_seed());
  }
}

function imagekpr_updates_list_published(int $offset, int $limit): array
{
  $offset = max(0, $offset);
  $limit = max(1, min(100, $limit));
  try {
    $pdo = imagekpr_pdo();
    $st = $pdo->prepare('SELECT slug, title, published_at, summary, body, tags_json
      FROM updates_posts
      WHERE status = "published"
      ORDER BY published_at DESC, id DESC
      LIMIT ? OFFSET ?');
    $st->bindValue(1, $limit, PDO::PARAM_INT);
    $st->bindValue(2, $offset, PDO::PARAM_INT);
    $st->execute();
    $rows = $st->fetchAll(PDO::FETCH_ASSOC);
    $out = [];
    foreach ($rows as $row) {
      $out[] = imagekpr_updates_db_post_to_public($row);
    }
    return $out;
  } catch (Throwable $e) {
    return array_slice(imagekpr_updates_static_seed(), $offset, $limit);
  }
}
