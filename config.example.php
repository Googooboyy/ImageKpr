<?php
/**
 * ImageKpr Configuration (example)
 * Copy this file to config.php and fill in your values.
 * config.php is gitignored and must not be committed.
 */

define('DB_HOST', 'localhost');
define('DB_NAME', 'db hostname here');
define('DB_USER', 'db username here');
define('DB_PASS', 'your password here');

define('IMAGES_DIR', __DIR__ . '/images');
define('IMAGES_URL', 'http://imagekpr.mar.sg/images');  // Use absolute URL for shareable links (e.g. http://imagekpr.mar.sg/images). Relative (e.g. /images) also works; copy will resolve to current origin.
define('INBOX_DIR', __DIR__ . '/inbox');
// Google OAuth (Phase 7). In Google Cloud Console, set redirect URI to your site callback, e.g.
// https://your-domain.example/auth/google/callback.php
define('GOOGLE_CLIENT_ID', '');
define('GOOGLE_CLIENT_SECRET', '');
define('GOOGLE_REDIRECT_URI', 'https://your-domain.example/auth/google/callback.php');
// Optional: Google "sub" for a user allowed even when not on the email allowlist (if used).
define('ADMIN_GOOGLE_SUB', '');
