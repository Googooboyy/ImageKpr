<?php
/**
 * Signed-in user (JSON). Use when the grid is empty but the DB has rows:
 * compare user_id here to images.user_id in phpMyAdmin.
 */
require_once __DIR__ . '/../inc/auth.php';
imagekpr_require_api_user();
header('Content-Type: application/json; charset=utf-8');
echo json_encode([
  'user_id' => imagekpr_user_id(),
  'email' => isset($_SESSION['email']) ? (string) $_SESSION['email'] : null,
  'name' => isset($_SESSION['name']) ? (string) $_SESSION['name'] : null,
], JSON_UNESCAPED_UNICODE);
