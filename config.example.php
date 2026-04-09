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

define('IMAGES_DIR', __DIR__ . '/images'); // User uploads are stored in per-user subfolders under this root, e.g. /images/{user_id}/filename.jpg
define('IMAGES_URL', 'http://imagekpr.mar.sg/images');  // Base URL for uploads. App appends /{user_id}/filename.jpg. Relative (e.g. /images) also works.
define('INBOX_DIR', __DIR__ . '/inbox');
// Google OAuth (Phase 7). In Google Cloud Console, set redirect URI to your site callback, e.g.
// https://your-domain.example/auth/google/callback.php
define('GOOGLE_CLIENT_ID', '');
define('GOOGLE_CLIENT_SECRET', '');
define('GOOGLE_REDIRECT_URI', 'https://your-domain.example/auth/google/callback.php');
// Optional: Google "sub" for a user allowed even when not on the email allowlist (if used).
define('ADMIN_GOOGLE_SUB', '');

// Optional (Phase 9+): default per-user storage cap in bytes when users.storage_quota_bytes IS NULL.
// Omit or use 0 for no default limit (unlimited until a per-user quota is set).
// define('DEFAULT_STORAGE_QUOTA_BYTES', 10737418240); // 10 GiB

// Optional: login session persistence in seconds (code defaults to 14 days).
// Set this if your host expires sessions too quickly.
// define('IMAGEKPR_SESSION_TTL_SECONDS', 1209600);

// Emergency only: list images with user_id NULL to every logged-in user (unsafe if multiple accounts share the app).
// define('IMAGEKPR_SHARE_NULL_USER_ROWS', true);

// Phase 12+: default quota cap, share-null, maintenance/read-only, and API bulk limits are in Admin → Config.
// Email allowlist, pending access requests, and “accept requests” toggle are in Admin → Allowlist (app_settings,
// email_allowlist, email_access_requests — run migrations/phase14_access_requests.sql for the requests table).

// Public contact form (contact.php): recipient for PHP mail(). Required for the form to send.
// define('CONTACT_TO_EMAIL', 'you@example.com');
// Optional: From address for outgoing mail (must be allowed by your host; otherwise CONTACT_TO_EMAIL is used).
// define('CONTACT_FROM_EMAIL', 'noreply@imagekpr.mar.sg');
