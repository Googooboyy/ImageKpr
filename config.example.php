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
