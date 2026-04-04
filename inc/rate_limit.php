<?php
function imagekpr_client_ip(): string
{
  if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
    $parts = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
    return trim($parts[0]);
  }
  return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}
function imagekpr_rate_limit(string $bucket, int $maxAttempts, int $windowSeconds): bool
{
  $dir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'imagekpr_rl';
  if (!is_dir($dir)) {
    @mkdir($dir, 0700, true);
  }
  $key = hash('sha256', imagekpr_client_ip() . '|' . $bucket);
  $file = $dir . DIRECTORY_SEPARATOR . $key . '.json';
  $now = time();
  $data = ['window_start' => $now, 'count' => 0];
  if (is_file($file)) {
    $raw = @file_get_contents($file);
    if ($raw) {
      $decoded = json_decode($raw, true);
      if (is_array($decoded) && isset($decoded['window_start'], $decoded['count'])) {
        $data = $decoded;
      }
    }
  }
  if ($now - (int) $data['window_start'] > $windowSeconds) {
    $data = ['window_start' => $now, 'count' => 0];
  }
  $data['count'] = (int) $data['count'] + 1;
  @file_put_contents($file, json_encode($data), LOCK_EX);
  return $data['count'] <= $maxAttempts;
}
