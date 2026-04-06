<?php

/**
 * Runtime app settings from app_settings (Phase 12). Loaded lazily; call ImageKprAppSettings::bust() after admin updates.
 */
final class ImageKprAppSettings
{
  /** @var array<string, string|null>|null */
  private static ?array $rows = null;

  public static function bust(): void
  {
    self::$rows = null;
  }

  /** @return array<string, string|null> */
  public static function all(): array
  {
    if (self::$rows !== null) {
      return self::$rows;
    }
    self::$rows = [];
    try {
      imagekpr_ensure_config();
      $pdo = imagekpr_pdo();
      $st = $pdo->query('SELECT `key`, value FROM app_settings');
      while ($row = $st->fetch(PDO::FETCH_ASSOC)) {
        self::$rows[(string) $row['key']] = $row['value'] === null ? null : (string) $row['value'];
      }
    } catch (Throwable $e) {
      self::$rows = [];
    }
    return self::$rows;
  }

  public static function has(string $key): bool
  {
    return array_key_exists($key, self::all());
  }

  public static function get(string $key, ?string $default = null): ?string
  {
    $a = self::all();
    return array_key_exists($key, $a) ? $a[$key] : $default;
  }

  public static function upsert(PDO $pdo, string $key, ?string $value): void
  {
    if ($value === null || $value === '') {
      $d = $pdo->prepare('DELETE FROM app_settings WHERE `key` = ?');
      $d->execute([$key]);
    } else {
      $st = $pdo->prepare('INSERT INTO app_settings (`key`, value) VALUES (?, ?) ON DUPLICATE KEY UPDATE value = VALUES(value)');
      $st->execute([$key, $value]);
    }
    self::bust();
  }
}

function imagekpr_share_null_user_rows_enabled(): bool
{
  if (ImageKprAppSettings::has('share_null_user_rows')) {
    return ImageKprAppSettings::get('share_null_user_rows') === '1';
  }
  return defined('IMAGEKPR_SHARE_NULL_USER_ROWS') && IMAGEKPR_SHARE_NULL_USER_ROWS;
}

function imagekpr_maintenance_enabled(): bool
{
  $v = ImageKprAppSettings::get('maintenance_mode');
  return $v === '1';
}

function imagekpr_maintenance_banner_text(): string
{
  $m = ImageKprAppSettings::get('maintenance_message');
  if (is_string($m) && trim($m) !== '') {
    return trim($m);
  }
  return 'This site is in read-only maintenance mode. Uploads, imports, and edits are temporarily disabled.';
}

/**
 * @return int|null Positive cap, or null = unlimited site default
 */
function imagekpr_site_default_quota_from_settings(): ?int
{
  if (!ImageKprAppSettings::has('default_storage_quota_bytes')) {
    return null;
  }
  $raw = trim((string) (ImageKprAppSettings::get('default_storage_quota_bytes') ?? ''));
  if ($raw === '' || $raw === '0') {
    return null;
  }
  $n = (int) $raw;
  return $n > 0 ? $n : null;
}

function imagekpr_setting_int_bounded(string $key, int $fallback, int $min, int $max): int
{
  if (!ImageKprAppSettings::has($key)) {
    return max($min, min($max, $fallback));
  }
  $raw = trim((string) (ImageKprAppSettings::get($key) ?? ''));
  if ($raw === '' || !ctype_digit($raw)) {
    return max($min, min($max, $fallback));
  }
  $n = (int) $raw;
  return max($min, min($max, $n));
}

function imagekpr_max_bulk_image_ids(): int
{
  imagekpr_ensure_config();
  return imagekpr_setting_int_bounded('max_bulk_image_ids', MAX_BULK_IMAGE_IDS, 1, 100000);
}

function imagekpr_max_duplicate_check_filenames(): int
{
  imagekpr_ensure_config();
  return imagekpr_setting_int_bounded('max_duplicate_check_filenames', MAX_DUPLICATE_CHECK_FILENAMES, 1, 10000);
}

function imagekpr_max_files_per_upload_post(): int
{
  imagekpr_ensure_config();
  return imagekpr_setting_int_bounded('max_files_per_upload_post', MAX_FILES_PER_UPLOAD_POST, 1, 500);
}

function imagekpr_max_images_per_page(): int
{
  imagekpr_ensure_config();
  return imagekpr_setting_int_bounded('max_images_per_page', MAX_IMAGES_PER_PAGE, 1, 5000);
}

/**
 * Message shown when a request exceeds bulk/file/filename caps.
 * Optional app_settings key request_limit_user_message: use placeholder {max} for the numeric limit.
 * If empty/unset, returns $technicalFallback (the default terse API text).
 */
function imagekpr_request_limit_public_message(int $max, string $technicalFallback): string
{
  imagekpr_ensure_config();
  $tpl = ImageKprAppSettings::get('request_limit_user_message');
  $tpl = $tpl !== null ? trim($tpl) : '';
  if ($tpl === '') {
    return $technicalFallback;
  }
  return str_replace('{max}', (string) $max, $tpl);
}

/**
 * Respond with 400 JSON when a request exceeds a configured numeric cap.
 *
 * @param bool $withSuccessFalse use { success: false, error } shape (upload, tags, bulk APIs)
 */
function imagekpr_json_request_limit_exceeded(int $max, string $technicalFallback, bool $withSuccessFalse = false): void
{
  $msg = imagekpr_request_limit_public_message($max, $technicalFallback);
  http_response_code(400);
  header('Content-Type: application/json; charset=utf-8');
  if ($withSuccessFalse) {
    echo json_encode(['success' => false, 'error' => $msg], JSON_UNESCAPED_UNICODE);
  } else {
    echo json_encode(['error' => $msg], JSON_UNESCAPED_UNICODE);
  }
  exit;
}
