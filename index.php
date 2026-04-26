<?php
ob_start();
require_once __DIR__ . '/inc/auth.php';
imagekpr_ensure_config();
imagekpr_start_session();
if (!empty($_GET['share'])) {
  try {
    require __DIR__ . '/inc/shared_dashboard.php';
  } catch (Throwable $e) {
    $debugCode = substr(hash('sha256', (string) microtime(true) . '|' . (string) mt_rand()), 0, 10);
    error_log('ImageKpr share route fatal [' . $debugCode . ']: ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
    $showDebug = isset($_GET['ik_debug']) && (string) $_GET['ik_debug'] === '1';
    $debugText = '';
    if ($showDebug) {
      $debugText = htmlspecialchars(get_class($e) . ': ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine(), ENT_QUOTES, 'UTF-8');
    }
    if (!headers_sent()) {
      http_response_code(200);
      header('Content-Type: text/html; charset=utf-8');
    }
    echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Shared Dashboard Unavailable</title><style>body{font-family:Arial,sans-serif;background:#f8fafc;color:#111827;margin:0;padding:2rem}main{max-width:42rem;margin:0 auto;background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:1.25rem}h1{margin:0 0 .5rem;font-size:1.2rem}p{margin:.45rem 0;color:#374151}.code{margin-top:.85rem;font-family:Consolas,monospace;font-size:.85rem;background:#f3f4f6;border:1px solid #e5e7eb;border-radius:8px;padding:.65rem;overflow:auto}</style></head><body><main><h1>Shared dashboard temporarily unavailable</h1><p>The link is valid but the server hit an internal error while rendering this page.</p><p>Reference: <strong>' . htmlspecialchars($debugCode, ENT_QUOTES, 'UTF-8') . '</strong></p>' . ($debugText !== '' ? '<div class="code">' . $debugText . '</div>' : '') . '<p>Please try again in a minute or ask the owner to refresh/recreate the link.</p></main></body></html>';
  }
  exit;
}
$ikLoggedIn = imagekpr_user_id() >= 1;

if ($ikLoggedIn) {
  try {
    $pdo = imagekpr_pdo();
    if (!imagekpr_user_has_app_access($pdo)) {
      $ikMaintenance = imagekpr_maintenance_enabled();
      $ikMaintenanceMsg = $ikMaintenance ? imagekpr_maintenance_banner_text() : '';
      $ikName = isset($_SESSION['name']) ? (string) $_SESSION['name'] : '';
      $ikEmail = isset($_SESSION['email']) ? (string) $_SESSION['email'] : '';
      $ikSubmitted = isset($_GET['submitted']) && (string) $_GET['submitted'] === '1';
      require __DIR__ . '/inc/pending_landing.php';
      exit;
    }
    require_once __DIR__ . '/inc/admin.php';
    $ikIsAdmin = imagekpr_user_is_admin($pdo, imagekpr_user_id());
  } catch (Throwable $e) {
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Could not verify your account. Please try again later.';
    exit;
  }
}

if (!$ikLoggedIn) {
  $ikMaintenance = imagekpr_maintenance_enabled();
  $ikMaintenanceMsg = $ikMaintenance ? imagekpr_maintenance_banner_text() : '';
  $ikLoginErrAllowed = imagekpr_guest_login_error_codes();
  $rawErr = isset($_GET['error']) ? (string) $_GET['error'] : '';
  $ikLoginErr = in_array($rawErr, $ikLoginErrAllowed, true) ? $rawErr : '';
  if ($ikLoginErr === '' && !empty($_SESSION['ik_guest_login_error'])) {
    $flashCode = (string) $_SESSION['ik_guest_login_error'];
    unset($_SESSION['ik_guest_login_error']);
    $ikLoginErr = in_array($flashCode, $ikLoginErrAllowed, true) ? $flashCode : '';
  }
  $ikReqAllowed = ['ok', 'duplicate', 'closed', 'invalid', 'ratelimit', 'already_allowed', 'database', 'csrf'];
  $rawReq = isset($_GET['request']) ? (string) $_GET['request'] : '';
  $ikRequestStatus = in_array($rawReq, $ikReqAllowed, true) ? $rawReq : '';
  $ikAcceptRequests = imagekpr_accept_access_requests_enabled();
  $ikSubmitted = isset($_GET['submitted']) && (string) $_GET['submitted'] === '1';
  require __DIR__ . '/inc/public_landing.php';
  exit;
}

$ikMaintenance = imagekpr_maintenance_enabled();
$ikMaintenanceMsg = $ikMaintenance ? imagekpr_maintenance_banner_text() : '';
$ikName = isset($_SESSION['name']) ? (string) $_SESSION['name'] : '';
$ikEmail = isset($_SESSION['email']) ? (string) $_SESSION['email'] : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>ImageKpr</title>
  <link rel="apple-touch-icon" sizes="180x180" href="favicons/apple-touch-icon.png">
  <link rel="icon" type="image/png" sizes="32x32" href="favicons/favicon-32x32.png">
  <link rel="icon" type="image/png" sizes="16x16" href="favicons/favicon-16x16.png">
  <link rel="icon" type="image/png" sizes="192x192" href="favicons/android-chrome-192x192.png">
  <link rel="icon" type="image/png" sizes="512x512" href="favicons/android-chrome-512x512.png">
  <link rel="shortcut icon" href="favicons/favicon.ico">
  <link rel="icon" type="image/x-icon" sizes="192x192" href="favicons/favicon-192x192.ico">
  <link rel="manifest" href="favicons/site.webmanifest">
  <link rel="stylesheet" href="styles.css">
</head>
<body<?php echo $ikMaintenance ? ' class="ikpr-maintenance"' : ''; ?>>
  <?php if ($ikMaintenance) { ?>
  <div class="ikpr-maintenance-banner" role="alert"><?php echo htmlspecialchars($ikMaintenanceMsg, ENT_QUOTES, 'UTF-8'); ?></div>
  <?php } ?>
  <header class="hero">
    <div class="hero-row">
      <div class="logo" aria-hidden="true">
        <video autoplay muted playsinline preload="auto" onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
          <source src="assets/video-folder-logo-animations_6s.mp4" type="video/mp4">
        </video>
        <span class="logo-fallback">ImageKpr</span>
      </div>
      <div class="upload-zone" id="upload-zone">
        <input type="file" id="upload-input" accept="image/jpeg,image/png,image/gif,image/webp" multiple hidden>
        <div class="upload-zone-text">
          <span id="upload-text">Drag & drop or click to upload</span>
          <span class="upload-zone-hint">Files above your limit can be auto-resized if you choose Continue.</span>
        </div>
        <div id="upload-progress" class="upload-progress" hidden></div>
        <div class="upload-url-row">
          <input
            type="url"
            id="upload-url-input"
            class="upload-url-input"
            placeholder="Paste image URL (https://...)"
            aria-label="Image URL"
          >
          <button type="button" id="upload-url-btn" class="upload-url-btn">Upload</button>
        </div>
      </div>
      <div class="user-session-bar">
        <a href="account.php" class="user-session-email user-session-profile-link" title="Account &amp; profile — <?php echo htmlspecialchars($ikEmail, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($ikName !== '' ? $ikName : $ikEmail, ENT_QUOTES, 'UTF-8'); ?></a>
        <span class="user-session-profile-hint" aria-hidden="true">Profile</span>
        <div class="user-session-actions">
          <?php if (!empty($ikIsAdmin)) { ?>
          <a href="admin/index.php" class="user-session-logout">Admin</a>
          <?php } ?>
          <a href="auth/logout.php" class="user-session-logout">Log out</a>
        </div>
      </div>
    </div>
  </header>

  <section class="dashboard">
    <div class="dashboard-inbox" id="dashboard-inbox" hidden>
      <span id="inbox-count">0</span> images in inbox.
      <button type="button" id="inbox-import-btn">Review & import</button>
    </div>
  </section>

  <section class="folders-filter">
    <input type="hidden" id="folder-filter" value="" aria-label="Filter by folder">
    <section class="collapsible-panel is-collapsed" data-collapsible-id="folders">
      <button type="button" class="collapsible-panel-toggle" id="folders-toggle" aria-expanded="false" aria-controls="folders-panel-body" title="Expand folders for more details">
        <span class="collapsible-panel-title">Folders</span>
        <span class="collapsible-panel-hint">Collapsed. Click to expand for more details.</span>
        <span class="collapsible-panel-chevron" aria-hidden="true">▾</span>
      </button>
      <div id="folders-panel-body" class="collapsible-panel-body" hidden>
        <input type="search" id="folder-icons-search" class="section-search-input" placeholder="Search folders..." aria-label="Search folders" autocomplete="off">
        <div id="folder-icons" class="folder-icons"></div>
      </div>
    </section>
    <section id="my-dashboards" class="my-dashboards collapsible-panel is-collapsed" data-collapsible-id="owner-dashboards">
      <button type="button" class="collapsible-panel-toggle" id="my-dashboards-toggle" aria-expanded="false" aria-controls="my-dashboards-body" title="Expand shared dashboard tiles for more details">
        <span class="collapsible-panel-title">Shared dashboard tiles</span>
        <span class="collapsible-panel-hint">Collapsed. Click to expand for more details.</span>
        <span class="collapsible-panel-chevron" aria-hidden="true">▾</span>
      </button>
      <div id="my-dashboards-body" class="collapsible-panel-body" hidden>
        <input type="search" id="my-dashboards-search" class="section-search-input" placeholder="Search shared dashboards..." aria-label="Search shared dashboards" autocomplete="off">
        <div id="my-dashboards-cards" class="my-dashboards-list" aria-label="Dashboards"></div>
      </div>
    </section>
  </section>

  <section class="sort-row" aria-label="Sort order">
    <span class="sort-label">Sort:</span>
    <div id="sort-pills" class="sort-pills"></div>
  </section>

  <section class="tags-row">
    <label>Tags:</label>
    <div id="tag-filters" class="tag-filters"></div>
    <button type="button" id="manage-tags-btn" class="ikpr-btn-tags">Manage tags</button>
  </section>

  <main class="main-content">
    <div class="banner-sticky">
      <div id="upload-global-status" class="upload-global-status" hidden aria-live="polite" aria-atomic="true">
        <div class="upload-global-status-inner">
          <span class="upload-global-status-text" id="upload-global-status-text"></span>
          <div class="upload-global-status-track" id="upload-global-status-track">
            <div class="upload-global-status-fill" id="upload-global-status-fill"></div>
          </div>
          <span class="upload-global-status-pct" id="upload-global-status-pct" aria-hidden="true"></span>
        </div>
      </div>
      <div class="banner-row" id="hint-banner-row">
        <div class="user-hint-banner">
          <span class="user-hint-text" id="user-hint-text">Loading…</span>
          <input type="search" id="search" placeholder="Search..." aria-label="Search images" autocomplete="off">
          <button type="button" id="bulk-select-all" class="select-all-visible-btn" title="Select every image currently shown below (folder, tag, and search filters apply; scroll to load more rows, then click again to include them)">Select all</button>
          <button type="button" id="scroll-to-top" class="scroll-to-top-btn" aria-label="Scroll to top" title="Scroll to top">↑ Top</button>
        </div>
      </div>
      <div class="grid-size-row" aria-label="Grid image size">
        <span class="grid-size-label">Grid size</span>
        <div class="grid-size-controls" role="radiogroup" aria-label="Main grid size scale">
          <input type="radio" id="grid-scale-05" name="grid-scale" value="0.5">
          <label for="grid-scale-05">0.5x</label>
          <input type="radio" id="grid-scale-075" name="grid-scale" value="0.75">
          <label for="grid-scale-075">0.75x</label>
          <input type="radio" id="grid-scale-10" name="grid-scale" value="1">
          <label for="grid-scale-10">1x</label>
          <input type="radio" id="grid-scale-15" name="grid-scale" value="1.5">
          <label for="grid-scale-15">1.5x</label>
          <input type="radio" id="grid-scale-20" name="grid-scale" value="2">
          <label for="grid-scale-20">2x</label>
        </div>
        <label class="grid-tight-toggle" title="Compact view: square thumbnails, no card padding; thin white lines between cells">
          <input type="checkbox" id="grid-layout-tight" aria-label="Compact view: flush squares with thin white separators">
          compact-view
        </label>
      </div>
      <div id="dashboard-editor-wrap" class="dashboard-editor-wrap" hidden>
        <div class="dashboard-editor-head">
          <h3 id="dashboard-editor-title"></h3>
          <div class="dashboard-editor-head-actions">
            <button type="button" id="dashboard-editor-copy-link" class="ikpr-btn-dark" hidden>Copy link</button>
            <button type="button" id="dashboard-editor-view-link" class="ikpr-btn-dark" hidden>View link</button>
            <button type="button" id="dashboard-editor-qr" class="ikpr-btn-dark" hidden>Show QR</button>
            <button type="button" id="dashboard-editor-delete" class="ikpr-btn-delete" hidden>Delete</button>
            <button type="button" id="dashboard-editor-close">Close</button>
          </div>
        </div>
        <div class="dashboard-editor-grid">
          <label>Title
            <input type="text" id="dashboard-title" maxlength="255" placeholder="e.g. Spring campaign selects">
          </label>
          <label class="dashboard-field-subtitle">Subtitle
            <input type="text" id="dashboard-subtitle" maxlength="500" placeholder="Optional short description">
          </label>
          <label id="dashboard-expiry-custom-wrap">Custom expiry
            <input type="datetime-local" id="dashboard-expiry-custom">
          </label>
          <label id="dashboard-password-wrap" class="dashboard-field-password" hidden>Password (paid)
            <input type="password" id="dashboard-password" maxlength="100" placeholder="Leave blank to remove password">
          </label>
        </div>
        <div class="dashboard-editor-hero">
          <span>Select Hero Image for shared dashboard <span id="dashboard-image-count" class="dashboard-image-count"></span></span>
          <div id="dashboard-hero-strip" class="dashboard-hero-strip"></div>
        </div>
        <div class="dashboard-editor-actions">
          <button type="button" id="dashboard-create-btn" class="ikpr-btn-dark">Save and Copy link</button>
          <span id="dashboard-editor-status" aria-live="polite"></span>
        </div>
      </div>
      <div class="selection-banner-below" id="selection-banner" hidden>
      <div class="bulk-bar" id="bulk-bar">
        <span id="bulk-count">0 selected</span>
        <button type="button" id="bulk-select-all-bar" title="Add every image currently shown in the grid to this selection (same filters; scroll to load more first if needed)">Select all</button>
        <button type="button" id="bulk-delete" class="ikpr-btn-delete">Delete</button>
        <button type="button" id="bulk-download">Download ZIP</button>
        <button type="button" id="bulk-slideshow" class="ikpr-btn-dark">Slideshow</button>
        <button type="button" id="bulk-create-dashboard" class="ikpr-btn-dark">Create Dashboard</button>
        <button type="button" id="bulk-tags" class="ikpr-btn-tags">Apply/Manage Tags</button>
        <button type="button" id="bulk-folders" class="ikpr-btn-folders">Apply/Manage Folders</button>
        <button type="button" id="bulk-rename">Rename</button>
        <button type="button" id="bulk-clear">Clear</button>
      </div>
      <div class="selection-selected-header">
        <span class="dashboard-label dashboard-label-selected">Selected <span class="dashboard-label-hint">— drag thumbnails to set slideshow order: Left (start) &gt; Right (end)</span></span>
        <label class="selection-thumbs-large-label"><input type="checkbox" id="selection-thumbs-large" aria-label="Use 100 pixel thumbnails for selection strip"> Bigger thumbnails (100px)</label>
      </div>
      <div class="selection-row" id="selection-row"></div>
      </div>
    </div>
    <div id="grid" class="grid grid-stock"></div>
    <div id="load-more" class="load-more"></div>
  </main>

  <div id="modal" class="modal" hidden aria-modal="true" aria-labelledby="modal-title">
    <div class="modal-content" id="modal-content">
      <div class="modal-img-wrap">
        <button type="button" id="modal-prev" class="modal-nav-btn modal-nav-prev" aria-label="Previous image" hidden>
          <svg width="20" height="20" viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 18l-6-6 6-6"/></svg>
        </button>
        <img id="modal-img" src="" alt="">
        <video id="modal-video" controls hidden></video>
        <button type="button" id="modal-next" class="modal-nav-btn modal-nav-next" aria-label="Next image" hidden>
          <svg width="20" height="20" viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 18l6-6-6-6"/></svg>
        </button>
      </div>
      <div class="modal-side">
        <div class="modal-actions">
          <button type="button" id="modal-copy">Copy URL</button>
          <button type="button" id="modal-copy-image">Copy Image</button>
          <button type="button" id="modal-download">Download</button>
          <button type="button" id="modal-fullscreen-btn" class="ikpr-btn-dark">Full screen</button>
          <button type="button" id="modal-delete" class="ikpr-btn-delete">Delete</button>
          <button type="button" id="modal-manage-tags" class="ikpr-btn-tags">Apply/Manage Tags</button>
          <button type="button" id="modal-manage-folders" class="ikpr-btn-folders">Apply/Manage Folders</button>
          <button type="button" id="modal-close">Close</button>
        </div>
      </div>
      <div class="modal-rename">
        <label for="modal-filename">Filename:</label>
        <input type="text" id="modal-filename" placeholder="Filename">
        <button type="button" id="modal-rename-btn">Rename</button>
      </div>
    </div>
  </div>

  <div id="modal-fullscreen" class="modal-fullscreen" hidden aria-hidden="true">
    <div class="modal-fullscreen-stage">
      <img id="modal-fullscreen-img" class="modal-fullscreen-img" src="" alt="">
      <button type="button" id="modal-fullscreen-exit" class="slideshow-exit" aria-label="Exit full screen">×</button>
    </div>
  </div>

  <div id="manage-tags-image-dialog" class="dialog" hidden aria-modal="true" aria-labelledby="manage-tags-image-title">
    <div class="dialog-content">
      <h3 id="manage-tags-image-title">Manage tags</h3>
      <div id="manage-tags-image-pills" class="tag-pills" style="margin-bottom:1rem;"></div>
      <label class="dialog-label">Add tag:</label>
      <div class="add-tag-row">
        <select id="manage-tags-image-select" aria-label="Select existing tag">
          <option value="">— Select or type new —</option>
        </select>
        <input type="text" id="manage-tags-image-new" placeholder="Or type new tag" autocomplete="off" aria-label="New tag name">
      </div>
      <div class="dialog-actions" style="margin-top:1rem;">
        <button type="button" id="manage-tags-image-add" class="ikpr-btn-tags">Add</button>
        <button type="button" id="manage-tags-image-close" class="ikpr-btn-tags">Done</button>
      </div>
    </div>
  </div>

  <div id="manage-folders-image-dialog" class="dialog" hidden aria-modal="true" aria-labelledby="manage-folders-image-title">
    <div class="dialog-content">
      <h3 id="manage-folders-image-title">Manage folders</h3>
      <div id="manage-folders-image-pills" class="folder-pills" style="margin-bottom:1rem;"></div>
      <label class="dialog-label">Add to folder:</label>
      <div class="add-tag-row">
        <select id="manage-folders-image-select" aria-label="Select folder">
          <option value="">— Select or type new —</option>
        </select>
        <input type="text" id="manage-folders-image-new" placeholder="Or type new folder name" autocomplete="off" aria-label="New folder name">
      </div>
      <div class="dialog-actions" style="margin-top:1rem;">
        <button type="button" id="manage-folders-image-add" class="ikpr-btn-folders">Add</button>
        <button type="button" id="manage-folders-image-close" class="ikpr-btn-folders">Done</button>
      </div>
    </div>
  </div>

  <div id="slideshow-settings-wrap" class="slideshow-settings-wrap" hidden>
    <div class="slideshow-settings-backdrop" id="slideshow-settings-backdrop" aria-hidden="true"></div>
    <aside class="slideshow-settings-panel" id="slideshow-settings-panel" role="dialog" aria-modal="true" aria-labelledby="slideshow-settings-title">
      <h3 id="slideshow-settings-title">Slideshow</h3>
      <p class="slideshow-settings-lead">Plays selected images in the order shown under Selected (drag thumbnails there to reorder). Missing from the grid still works if they stay selected.</p>
      <fieldset class="slideshow-fieldset">
        <legend>Advance</legend>
        <label class="slideshow-radio"><input type="radio" name="slideshow-advance" id="slideshow-advance-manual" value="manual" checked> Manual — Space (image: next, video: play/pause), arrow keys to move</label>
        <label class="slideshow-radio"><input type="radio" name="slideshow-advance" id="slideshow-advance-auto" value="auto"> Auto</label>
        <div class="slideshow-duration-row" id="slideshow-duration-row">
          <label for="slideshow-duration">Seconds per slide</label>
          <input type="number" id="slideshow-duration" min="1" max="600" value="5" step="1" class="slideshow-duration-input">
        </div>
      </fieldset>
      <label class="slideshow-check"><input type="checkbox" id="slideshow-show-filename"> Show filename at bottom (subtle)</label>
      <label class="slideshow-check"><input type="checkbox" id="slideshow-fill-image"> Fill image (cover screen, crop overflow)</label>
      <label class="slideshow-check"><input type="checkbox" id="slideshow-show-controller" checked> Show mini controller tray on start</label>
      <label class="slideshow-check"><input type="checkbox" id="slideshow-autoloop" checked> Loop (return to first after last)</label>
      <label class="slideshow-check" id="slideshow-randomize-loop-label"><input type="checkbox" id="slideshow-randomize-loop"> Randomize slide order (turn off to restore your Selected order)</label>
      <fieldset class="slideshow-fieldset">
        <legend>Transition</legend>
        <label class="slideshow-radio"><input type="radio" name="slideshow-transition" id="slideshow-trans-diffuse" value="diffuse" checked> Diffuse (crossfade)</label>
        <label class="slideshow-radio"><input type="radio" name="slideshow-transition" id="slideshow-trans-fly" value="fly"> Fly in</label>
      </fieldset>
      <fieldset class="slideshow-fieldset">
        <legend>Letterbox (around image)</legend>
        <p class="slideshow-fieldset-note">Images keep their proportions; empty areas use this colour.</p>
        <label class="slideshow-radio"><input type="radio" name="slideshow-letterbox" id="slideshow-lb-black" value="black" checked> Black</label>
        <label class="slideshow-radio"><input type="radio" name="slideshow-letterbox" id="slideshow-lb-white" value="white"> White</label>
        <label class="slideshow-radio"><input type="radio" name="slideshow-letterbox" id="slideshow-lb-red" value="red"> Red</label>
        <label class="slideshow-radio"><input type="radio" name="slideshow-letterbox" id="slideshow-lb-green" value="green"> Green</label>
        <label class="slideshow-radio"><input type="radio" name="slideshow-letterbox" id="slideshow-lb-blue" value="blue"> Blue</label>
      </fieldset>
      <div class="slideshow-settings-hint" aria-label="Slideshow keyboard help">
        <div class="ss-hint-group ss-hint-nav">
          <span class="ss-hint-label">Navigate</span>
          <span class="ss-hint-item"><kbd>Space</kbd> next slide on images &middot; play / pause on videos</span>
          <span class="ss-hint-item"><kbd>&larr;</kbd> <kbd>&rarr;</kbd> previous / next slide</span>
          <span class="ss-hint-item"><kbd>Home</kbd> <kbd>End</kbd> first / last slide in the current order</span>
        </div>
        <div class="ss-hint-group ss-hint-play">
          <span class="ss-hint-label">Playback</span>
          <span class="ss-hint-item"><kbd>M</kbd> / <kbd>A</kbd> switch Manual / Auto</span>
          <span class="ss-hint-item"><kbd>P</kbd> pause / resume (Auto mode)</span>
          <span class="ss-hint-item"><kbd>&uarr;</kbd> <kbd>&darr;</kbd> seconds per slide (Auto mode)</span>
          <span class="ss-hint-item"><kbd>O</kbd> toggle "replay video on end" (MP4 loops instead of advancing)</span>
          <span class="ss-hint-item"><kbd>[</kbd> <kbd>]</kbd> scrub video 4s back / forward (video slides only)</span>
          <span class="ss-hint-item"><span class="ss-hint-pill">REPLAY</span> tray button restarts the current video (videos only)</span>
        </div>
        <div class="ss-hint-group ss-hint-display">
          <span class="ss-hint-label">Display</span>
          <span class="ss-hint-item"><kbd>F</kbd> toggle Fill image &middot; mouse wheel pans when on</span>
          <span class="ss-hint-item"><kbd>C</kbd> show / hide the mini controller tray</span>
        </div>
        <div class="ss-hint-group ss-hint-deck">
          <span class="ss-hint-label">Deck</span>
          <span class="ss-hint-item"><kbd>L</kbd> toggle Loop &middot; <kbd>R</kbd> toggle Randomize order</span>
        </div>
        <div class="ss-hint-group ss-hint-exit">
          <span class="ss-hint-label">Exit</span>
          <span class="ss-hint-item"><kbd>Esc</kbd> &middot; the &times; button &middot; click outside the picture</span>
        </div>
        <div class="ss-hint-mobile">On mobile: swipe left/right to navigate &middot; single tap to pause / resume (or advance in Manual) &middot; double tap to toggle Fill.</div>
      </div>
      <div class="slideshow-settings-actions">
        <button type="button" id="slideshow-start" class="slideshow-btn-primary">Start slideshow</button>
        <button type="button" id="slideshow-settings-cancel">Cancel</button>
      </div>
    </aside>
  </div>

  <div id="slideshow-player" class="slideshow-player" hidden aria-hidden="true">
    <button type="button" class="slideshow-exit" id="slideshow-exit-btn" aria-label="Exit slideshow">×</button>
    <div class="slideshow-counter" id="slideshow-counter" aria-live="polite"></div>
    <div class="slideshow-filename-caption" id="slideshow-filename-caption" hidden aria-hidden="true"></div>
    <div id="slideshow-mini-controller" class="slideshow-mini-controller" hidden aria-hidden="true">
      <button type="button" class="ss-ctrl-pill ss-ctrl-close" id="ss-ctrl-close" data-active="false" title="Hide controller">×</button>
      <button type="button" class="ss-ctrl-pill" id="ss-ctrl-manual" data-active="false" title="Manual mode (M)">MAN</button>
      <button type="button" class="ss-ctrl-pill" id="ss-ctrl-auto" data-active="false" title="Auto mode (A)">AUTO</button>
      <button type="button" class="ss-ctrl-pill" id="ss-ctrl-loop" data-active="false" title="Loop (L)">LOOP</button>
      <button type="button" class="ss-ctrl-pill" id="ss-ctrl-rand" data-active="false" title="Randomize order (R)">RAND</button>
      <button type="button" class="ss-ctrl-pill" id="ss-ctrl-pause" data-active="false" title="Pause / resume (P)">PAUSE</button>
      <button type="button" class="ss-ctrl-pill" id="ss-ctrl-replay" disabled data-active="false" title="Replay video from start">REPLAY</button>
      <button type="button" class="ss-ctrl-pill" id="ss-ctrl-fill" data-active="false" title="Fill image (F)">FILL</button>
      <button type="button" class="ss-ctrl-pill" id="ss-ctrl-interval-dec" data-active="false" title="Decrease interval">-1s</button>
      <span class="ss-ctrl-pill ss-ctrl-readout" id="ss-ctrl-interval-value" aria-live="polite">5s</span>
      <button type="button" class="ss-ctrl-pill" id="ss-ctrl-interval-inc" data-active="false" title="Increase interval">+1s</button>
      <button type="button" class="ss-ctrl-pill" id="ss-ctrl-transition" data-active="false" title="Transition">TRANS</button>
      <button type="button" class="ss-ctrl-pill ss-ctrl-color" id="ss-ctrl-lb-black" data-letterbox="black" data-active="false" title="Letterbox black" aria-label="Letterbox black"></button>
      <button type="button" class="ss-ctrl-pill ss-ctrl-color" id="ss-ctrl-lb-white" data-letterbox="white" data-active="false" title="Letterbox white" aria-label="Letterbox white"></button>
      <button type="button" class="ss-ctrl-pill ss-ctrl-color" id="ss-ctrl-lb-red" data-letterbox="red" data-active="false" title="Letterbox red" aria-label="Letterbox red"></button>
      <button type="button" class="ss-ctrl-pill ss-ctrl-color" id="ss-ctrl-lb-green" data-letterbox="green" data-active="false" title="Letterbox green" aria-label="Letterbox green"></button>
      <button type="button" class="ss-ctrl-pill ss-ctrl-color" id="ss-ctrl-lb-blue" data-letterbox="blue" data-active="false" title="Letterbox blue" aria-label="Letterbox blue"></button>
    </div>
    <button type="button" id="slideshow-mini-launcher" class="slideshow-mini-launcher" hidden aria-hidden="true" title="Show controller tray" aria-label="Show controller tray">
      <svg viewBox="0 0 24 24" aria-hidden="true">
        <rect x="7.2" y="2.5" width="9.6" height="19" rx="3.1" ry="3.1"></rect>
        <circle cx="12" cy="7.3" r="1.5"></circle>
        <circle cx="9.7" cy="11.4" r="1"></circle>
        <circle cx="12" cy="11.4" r="1"></circle>
        <circle cx="14.3" cy="11.4" r="1"></circle>
        <circle cx="9.7" cy="14.5" r="1"></circle>
        <circle cx="12" cy="14.5" r="1"></circle>
        <circle cx="14.3" cy="14.5" r="1"></circle>
      </svg>
    </button>
    <div class="slideshow-stage">
      <div class="slideshow-player-bg" id="slideshow-player-bg" aria-hidden="true"></div>
      <img id="slideshow-img" class="slideshow-img" src="" alt="">
      <video id="slideshow-video" class="slideshow-video" controls preload="metadata" hidden></video>
    </div>
  </div>

  <div id="toast" class="toast" hidden aria-live="polite"></div>

  <div id="bulk-manage-tags-dialog" class="dialog" hidden aria-modal="true">
    <div class="dialog-content">
      <h3>Manage tags for selected images</h3>
      <p class="dialog-hint">Add or remove a tag from all selected images.</p>
      <div class="bulk-tags-add-row">
        <label class="dialog-label">Add tag:</label>
        <div class="add-tag-row">
          <select id="bulk-tags-add-select" aria-label="Select tag"><option value="">— Select or type new —</option></select>
          <input type="text" id="bulk-tags-add-new" placeholder="Or type new tag" autocomplete="off">
          <button type="button" id="bulk-tags-add-btn" class="ikpr-btn-tags">Add</button>
        </div>
      </div>
      <div class="bulk-tags-remove-row">
        <label class="dialog-label">Remove tag:</label>
        <div class="add-tag-row">
          <select id="bulk-tags-remove-select" aria-label="Select tag to remove"><option value="">— Select tag —</option></select>
          <button type="button" id="bulk-tags-remove-btn" class="ikpr-btn-tags">Remove</button>
        </div>
      </div>
      <div class="dialog-actions">
        <button type="button" id="bulk-tags-dialog-close" class="ikpr-btn-tags">Done</button>
      </div>
    </div>
  </div>

  <div id="bulk-manage-folders-dialog" class="dialog" hidden aria-modal="true">
    <div class="dialog-content">
      <h3>Manage folders for selected images</h3>
      <p class="dialog-hint">Add or remove selected images from folders.</p>
      <div class="bulk-folders-add-row">
        <label class="dialog-label">Add to folder:</label>
        <div class="add-tag-row">
          <select id="bulk-folders-add-select" aria-label="Select folder"><option value="">— Select or type new —</option></select>
          <input type="text" id="bulk-folders-add-new" placeholder="Or type new folder name" autocomplete="off">
          <button type="button" id="bulk-folders-add-btn" class="ikpr-btn-folders">Add</button>
        </div>
      </div>
      <div class="bulk-folders-remove-row">
        <label class="dialog-label">Remove from folder:</label>
        <div class="add-tag-row">
          <select id="bulk-folders-remove-select" aria-label="Select folder"><option value="">— Select folder —</option></select>
          <button type="button" id="bulk-folders-remove-btn" class="ikpr-btn-folders">Remove</button>
        </div>
      </div>
      <div class="dialog-actions">
        <button type="button" id="bulk-folders-dialog-close" class="ikpr-btn-folders">Done</button>
      </div>
    </div>
  </div>

  <div id="manage-folders-dialog" class="dialog" hidden>
    <div class="dialog-content">
      <h3>Manage folders</h3>
      <div id="manage-folders-list"></div>
      <label>New folder: <input type="text" id="new-folder-name" placeholder="Folder name"></label>
      <button type="button" id="manage-create-folder" class="ikpr-btn-folders">Create</button>
      <div class="dialog-actions">
        <button type="button" id="manage-close" class="ikpr-btn-folders">Close</button>
      </div>
    </div>
  </div>

  <div id="rename-dialog" class="dialog" hidden>
    <div class="dialog-content">
      <h3>Bulk rename</h3>
      <label>Base name: <input type="text" id="rename-base" placeholder="e.g. project-alpha"></label>
      <div class="dialog-actions">
        <button type="button" id="rename-confirm">Rename</button>
        <button type="button" id="rename-cancel">Cancel</button>
      </div>
    </div>
  </div>

  <div id="upload-confirm-dialog" class="dialog upload-confirm-dialog" hidden aria-modal="true" aria-labelledby="upload-confirm-title">
    <div class="dialog-content upload-confirm-content">
      <h3 id="upload-confirm-title">Confirm upload</h3>
      <p class="upload-confirm-hint">Review the files below. Use Rename, Apply/Manage Tags, Apply/Manage Folders per item. Remove any you don't want before confirming.</p>
      <div id="upload-confirm-grid" class="upload-confirm-grid upload-confirm-list"></div>
      <div class="upload-add-to-folder">
        <label for="upload-add-to-folder-select" class="upload-add-to-folder-label">Add all to folder (optional):</label>
        <select id="upload-add-to-folder-select" aria-label="Add uploads to folder">
          <option value="">— None —</option>
        </select>
        <input type="text" id="upload-add-to-folder-new" class="upload-add-to-folder-new" placeholder="Or type new folder name" autocomplete="off">
      </div>
      <div class="dialog-actions upload-confirm-actions">
        <span id="upload-confirm-count" class="upload-confirm-count"></span>
        <button type="button" id="upload-confirm-upload">Upload</button>
        <button type="button" id="upload-confirm-cancel">Cancel</button>
      </div>
    </div>
  </div>

  <div id="upload-warn-dialog" class="dialog" hidden aria-modal="true" aria-labelledby="upload-warn-title">
    <div class="dialog-content upload-warn-content">
      <div class="upload-warn-header">
        <span class="upload-warn-icon" aria-hidden="true">⚠</span>
        <h3 id="upload-warn-title">Some files can't be uploaded</h3>
      </div>
      <p id="upload-warn-body" class="upload-warn-body"></p>
      <ul id="upload-warn-list" class="upload-warn-list"></ul>
      <p class="upload-warn-footnote">ImageKpr accepts JPEG, PNG, GIF, and WebP only. <a href="knowledge-base.php#kb-working" target="_blank">Learn more</a></p>
      <div class="dialog-actions">
        <button type="button" id="upload-warn-continue">Continue</button>
        <button type="button" id="upload-warn-cancel">Cancel upload</button>
      </div>
    </div>
  </div>

  <div id="upload-rename-dialog" class="dialog" hidden aria-modal="true">
    <div class="dialog-content">
      <h3>Rename file</h3>
      <label class="dialog-label">New filename:</label>
      <input type="text" id="upload-rename-input" placeholder="filename.jpg">
      <div class="dialog-actions">
        <button type="button" id="upload-rename-ok">OK</button>
        <button type="button" id="upload-rename-cancel">Cancel</button>
      </div>
    </div>
  </div>

  <div id="inbox-rename-dialog" class="dialog" hidden aria-modal="true">
    <div class="dialog-content">
      <h3>Rename file</h3>
      <label class="dialog-label">Current: <span id="inbox-rename-current"></span></label>
      <label class="dialog-label">New filename:</label>
      <input type="text" id="inbox-rename-input" placeholder="filename.jpg">
      <div class="dialog-actions">
        <button type="button" id="inbox-rename-ok">OK</button>
        <button type="button" id="inbox-rename-cancel">Cancel</button>
      </div>
    </div>
  </div>

  <div id="inbox-import-dialog" class="dialog inbox-import-dialog" hidden aria-modal="true" aria-labelledby="inbox-import-title">
    <div class="dialog-content inbox-import-content">
      <h3 id="inbox-import-title">Review inbox import</h3>
      <p class="inbox-import-hint">Review files. Use Rename, Apply/Manage Tags, Apply/Manage Folders per file. Skip any for later – they stay in inbox.</p>
      <div class="inbox-import-bulk">
        <span class="inbox-import-bulk-label">Apply to all:</span>
        <input type="text" id="inbox-import-bulk-tags" placeholder="Tags (comma-separated)" class="inbox-import-bulk-tags">
        <select id="inbox-import-bulk-folder" class="inbox-import-bulk-folder" aria-label="Folder for all">
          <option value="">— None —</option>
        </select>
        <button type="button" id="inbox-import-bulk-apply">Apply</button>
      </div>
      <div id="inbox-import-list" class="inbox-import-list"></div>
      <div class="dialog-actions inbox-import-actions">
        <span id="inbox-import-count" class="inbox-import-count"></span>
        <button type="button" id="inbox-import-confirm">Import</button>
        <button type="button" id="inbox-import-cancel">Cancel</button>
      </div>
    </div>
  </div>

  <div id="confirm-dialog" class="dialog" hidden aria-modal="true">
    <div class="dialog-content">
      <p id="confirm-message"></p>
      <div class="dialog-actions">
        <button type="button" id="confirm-ok">OK</button>
        <button type="button" id="confirm-cancel">Cancel</button>
      </div>
    </div>
  </div>

  <div id="unsaved-dialog" class="dialog" hidden aria-modal="true">
    <div class="dialog-content">
      <p id="unsaved-message">You have unsaved changes. What would you like to do?</p>
      <div class="dialog-actions">
        <button type="button" id="unsaved-save" class="ikpr-btn-dark">Save first</button>
        <button type="button" id="unsaved-discard" class="ikpr-btn-delete">Discard</button>
        <button type="button" id="unsaved-cancel">Cancel</button>
      </div>
    </div>
  </div>

  <div id="add-to-folder-dialog" class="dialog" hidden aria-modal="true" aria-labelledby="add-to-folder-title">
    <div class="dialog-content">
      <h3 id="add-to-folder-title">Add to folder</h3>
      <label for="add-to-folder-input" class="dialog-label">Folder name:</label>
      <input type="text" id="add-to-folder-input" placeholder="e.g. Project Alpha" autocomplete="off">
      <div class="dialog-actions">
        <button type="button" id="add-to-folder-ok" class="ikpr-btn-folders">OK</button>
        <button type="button" id="add-to-folder-cancel">Cancel</button>
      </div>
    </div>
  </div>

  <div id="rename-folder-dialog" class="dialog" hidden aria-modal="true" aria-labelledby="rename-folder-title">
    <div class="dialog-content">
      <h3 id="rename-folder-title">Rename folder</h3>
      <label for="rename-folder-input" class="dialog-label">New folder name:</label>
      <input type="text" id="rename-folder-input" placeholder="e.g. Project Alpha" autocomplete="off">
      <div class="dialog-actions">
        <button type="button" id="rename-folder-ok" class="ikpr-btn-folders">Rename</button>
        <button type="button" id="rename-folder-cancel">Cancel</button>
      </div>
    </div>
  </div>

  <div id="add-to-folder-select-dialog" class="dialog" hidden aria-modal="true" aria-labelledby="add-to-folder-select-title">
    <div class="dialog-content">
      <h3 id="add-to-folder-select-title">Add to folder</h3>
      <label for="add-to-folder-select" class="dialog-label">Select or add folder:</label>
      <div class="add-tag-row">
        <select id="add-to-folder-select" aria-label="Select folder">
          <option value="">— Select or type new —</option>
        </select>
        <input type="text" id="add-to-folder-new" placeholder="Or type new folder name" autocomplete="off" aria-label="New folder name">
      </div>
      <div class="dialog-actions">
        <button type="button" id="add-to-folder-select-ok" class="ikpr-btn-folders">Add</button>
        <button type="button" id="add-to-folder-select-cancel">Cancel</button>
      </div>
    </div>
  </div>

  <div id="add-tag-dialog" class="dialog" hidden aria-modal="true" aria-labelledby="add-tag-title">
    <div class="dialog-content">
      <h3 id="add-tag-title">Add tag</h3>
      <label for="add-tag-select" class="dialog-label">Select or add tag:</label>
      <div class="add-tag-row">
        <select id="add-tag-select" aria-label="Select existing tag">
          <option value="">— Select or type new —</option>
        </select>
        <input type="text" id="add-tag-new" placeholder="Or type new tag" autocomplete="off" aria-label="New tag name">
      </div>
      <div class="dialog-actions">
        <button type="button" id="add-tag-ok" class="ikpr-btn-tags">Add</button>
        <button type="button" id="add-tag-cancel">Cancel</button>
      </div>
    </div>
  </div>

  <div id="manage-tags-dialog" class="dialog" hidden aria-modal="true" aria-labelledby="manage-tags-title">
    <div class="dialog-content">
      <h3 id="manage-tags-title">Manage tags</h3>
      <p class="dialog-hint">Click a tag to filter images. Use the buttons to rename or remove a tag from all images.</p>
      <div id="manage-tags-list" class="manage-tags-list"></div>
      <div class="dialog-actions">
        <button type="button" id="manage-tags-close" class="ikpr-btn-tags">Close</button>
      </div>
    </div>
  </div>

  <div id="dashboard-preview-modal" class="dialog dashboard-preview-modal" hidden aria-modal="true">
    <div class="dashboard-preview-modal-content">
      <button type="button" id="dashboard-preview-close" class="dashboard-preview-modal-close" aria-label="Close preview">&times;</button>
      <iframe id="dashboard-preview-iframe" class="dashboard-preview-iframe" sandbox="allow-scripts allow-same-origin" title="Dashboard preview"></iframe>
    </div>
  </div>

  <div id="dashboard-qr-modal" class="dialog dashboard-qr-modal" hidden aria-modal="true">
    <div class="dialog-content dashboard-qr-modal-content">
      <h3>QR Code</h3>
      <img id="dashboard-qr-img" class="dashboard-qr-img" src="" alt="QR code">
      <div class="dialog-actions dashboard-qr-actions">
        <button type="button" id="dashboard-qr-download" class="ikpr-btn-dark">Download</button>
        <button type="button" id="dashboard-qr-copy" class="ikpr-btn-dark">Copy image</button>
        <button type="button" id="dashboard-qr-close">Close</button>
      </div>
    </div>
  </div>

  <?php
  require_once __DIR__ . '/inc/footer.php';
  imagekpr_render_footer(['context' => 'dashboard']);
  ?>

  <script src="folders.js"></script>
  <script src="app.js"></script>
</body>
</html>
