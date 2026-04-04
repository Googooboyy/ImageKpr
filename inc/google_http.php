<?php
function imagekpr_http_post_form(string $url, array $fields): ?array
{
  $body = http_build_query($fields);
  if (function_exists('curl_init')) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
      CURLOPT_POST => true,
      CURLOPT_POSTFIELDS => $body,
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_TIMEOUT => 25,
      CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
    ]);
    $raw = curl_exec($ch);
    curl_close($ch);
    if ($raw === false) {
      return null;
    }
    $j = json_decode($raw, true);
    return is_array($j) ? $j : null;
  }
  $ctx = stream_context_create([
    'http' => [
      'method' => 'POST',
      'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
      'content' => $body,
      'timeout' => 25,
    ],
  ]);
  $raw = @file_get_contents($url, false, $ctx);
  if ($raw === false) {
    return null;
  }
  $j = json_decode($raw, true);
  return is_array($j) ? $j : null;
}
function imagekpr_http_get_json(string $url, string $bearer): ?array
{
  if (function_exists('curl_init')) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_TIMEOUT => 25,
      CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $bearer],
    ]);
    $raw = curl_exec($ch);
    curl_close($ch);
    if ($raw === false) {
      return null;
    }
    $j = json_decode($raw, true);
    return is_array($j) ? $j : null;
  }
  $ctx = stream_context_create([
    'http' => [
      'method' => 'GET',
      'header' => "Authorization: Bearer {$bearer}\r\n",
      'timeout' => 25,
    ],
  ]);
  $raw = @file_get_contents($url, false, $ctx);
  if ($raw === false) {
    return null;
  }
  $j = json_decode($raw, true);
  return is_array($j) ? $j : null;
}