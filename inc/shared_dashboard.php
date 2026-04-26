<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/tiers.php';
require_once __DIR__ . '/rate_limit.php';
require_once __DIR__ . '/footer.php';

imagekpr_start_session();
$token = isset($_GET['share']) ? trim((string) $_GET['share']) : '';
$isEmbed = !empty($_GET['embed']);
if ($token === '') {
  http_response_code(404);
  echo 'Dashboard not found.';
  exit;
}
try {
  $pdo = imagekpr_pdo();
} catch (Throwable $e) {
  http_response_code(500);
  echo 'Database error.';
  exit;
}

try {
  $st = $pdo->prepare('SELECT * FROM shared_dashboards WHERE token = ? LIMIT 1');
  $st->execute([$token]);
  $dashboard = $st->fetch(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
  error_log('ImageKpr shared dashboard lookup failed: ' . $e->getMessage());
  http_response_code(500);
  echo 'Could not load this shared dashboard right now.';
  exit;
}
if (!$dashboard) {
  http_response_code(404);
  echo 'Dashboard not found.';
  exit;
}
if (!empty($dashboard['expires_at']) && strtotime((string) $dashboard['expires_at']) < time()) {
  http_response_code(410);
}
$isExpired = !empty($dashboard['expires_at']) && strtotime((string) $dashboard['expires_at']) < time();
$isProtected = !empty($dashboard['password_hash']);
$unlockKey = 'dash_unlocked_' . (int) $dashboard['id'];
$unlocked = !$isProtected || !empty($_SESSION[$unlockKey]);
$unlockError = '';
if ($isProtected && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['dashboard_password'])) {
  if (!imagekpr_rate_limit('dash_pw_' . (int) $dashboard['id'], 10, 300)) {
    $unlockError = 'Too many password attempts. Please wait and try again.';
  } else {
    $candidate = (string) $_POST['dashboard_password'];
    if (password_verify($candidate, (string) $dashboard['password_hash'])) {
      $_SESSION[$unlockKey] = 1;
      $unlocked = true;
    } else {
      $unlockError = 'Incorrect password. Please try again.';
    }
  }
}

try {
  $pdo->prepare('UPDATE shared_dashboards SET view_count = view_count + 1, last_viewed_at = NOW() WHERE id = ?')->execute([(int) $dashboard['id']]);
} catch (Throwable $e) {
  error_log('ImageKpr shared dashboard view counter update failed: ' . $e->getMessage());
}

try {
  $imgSt = $pdo->prepare('SELECT i.id, i.filename, i.url, i.width, i.height, i.media_type
                          FROM shared_dashboard_images sdi
                          INNER JOIN images i ON i.id = sdi.image_id
                          WHERE sdi.dashboard_id = ?
                          ORDER BY sdi.sort_order ASC, sdi.id ASC');
  $imgSt->execute([(int) $dashboard['id']]);
  $images = $imgSt->fetchAll(PDO::FETCH_ASSOC) ?: [];
} catch (Throwable $e) {
  error_log('ImageKpr shared dashboard images lookup failed: ' . $e->getMessage());
  http_response_code(500);
  echo 'Could not load dashboard images right now.';
  exit;
}

if ($unlocked && !$isExpired && isset($_GET['download'])) {
  $downloadType = (string) $_GET['download'];
  if ($downloadType === 'one') {
    $imageId = (int) ($_GET['image_id'] ?? 0);
    $target = null;
    foreach ($images as $img) {
      if ((int) $img['id'] === $imageId) {
        $target = $img;
        break;
      }
    }
    if ($target) {
      $src = (string) $target['url'];
      if (preg_match('#^https?://#i', $src)) {
        header('Location: ' . $src, true, 302);
        exit;
      }
      $full = dirname(__DIR__) . '/' . ltrim($src, '/');
      if (is_file($full)) {
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename((string) $target['filename']) . '"');
        header('Content-Length: ' . filesize($full));
        readfile($full);
        exit;
      }
    }
  } elseif ($downloadType === 'all') {
    $zip = new ZipArchive();
    $tmp = tempnam(sys_get_temp_dir(), 'dash');
    $zip->open($tmp, ZipArchive::CREATE | ZipArchive::OVERWRITE);
    foreach ($images as $img) {
      $src = (string) $img['url'];
      if (preg_match('#^https?://#i', $src)) {
        continue;
      }
      $full = dirname(__DIR__) . '/' . ltrim($src, '/');
      if (is_file($full)) {
        $zip->addFile($full, basename((string) $img['filename']));
      }
    }
    $zip->close();
    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="dashboard-' . (int) $dashboard['id'] . '.zip"');
    header('Content-Length: ' . filesize($tmp));
    readfile($tmp);
    @unlink($tmp);
    exit;
  }
}

$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = (string) ($_SERVER['HTTP_HOST'] ?? 'localhost');
$selfUrl = $scheme . '://' . $host . (string) ($_SERVER['REQUEST_URI'] ?? '');
$isPaidOwner = false;
try {
  $isPaidOwner = imagekpr_user_is_paid($pdo, (int) $dashboard['user_id']);
} catch (Throwable $e) {
  error_log('ImageKpr shared dashboard paid-tier check failed: ' . $e->getMessage());
}
$heroImageUrl = '';
if (!empty($dashboard['hero_image_id'])) {
  foreach ($images as $img) {
    if ((int) ($img['id'] ?? 0) === (int) $dashboard['hero_image_id'] && (string) ($img['media_type'] ?? 'image') !== 'video') {
      $heroImageUrl = (string) ($img['url'] ?? '');
      break;
    }
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo htmlspecialchars((string) ($dashboard['title'] ?: 'Shared Dashboard'), ENT_QUOTES, 'UTF-8'); ?> - ImageKpr</title>
  <link rel="stylesheet" href="styles.css">
</head>
<body class="shared-dash-body">
  <header class="shared-dash-top">
    <div class="shared-dash-top-inner">
      <div class="shared-dash-top-media" aria-hidden="true">
        <?php if ($heroImageUrl !== '') { ?>
          <img src="<?php echo htmlspecialchars($heroImageUrl, ENT_QUOTES, 'UTF-8'); ?>" alt="">
        <?php } else { ?>
          <img src="assets/img/logo-imagekpr.jpg" alt="">
        <?php } ?>
      </div>
      <h1 class="shared-dash-top-title"><?php echo htmlspecialchars((string) ($dashboard['title'] ?: 'Shared Dashboard'), ENT_QUOTES, 'UTF-8'); ?></h1>
      <?php if (!empty($dashboard['subtitle'])) { ?><p class="shared-dash-top-subtitle"><?php echo htmlspecialchars((string) $dashboard['subtitle'], ENT_QUOTES, 'UTF-8'); ?></p><?php } ?>
      <div class="shared-dash-meta">
        <span class="shared-dash-badge"><?php echo $isExpired ? 'Expired' : (!empty($dashboard['expires_at']) ? 'Expires ' . htmlspecialchars((string) $dashboard['expires_at'], ENT_QUOTES, 'UTF-8') : 'No expiry'); ?></span>
        <span class="shared-dash-badge">Views <?php echo (int) ($dashboard['view_count'] ?? 0) + 1; ?></span>
      </div>
      <?php if ($isProtected && !$unlocked) { ?>
      <form method="post" class="shared-dash-password-form">
        <label for="dashboard_password">Enter password to view images</label>
        <input type="password" id="dashboard_password" name="dashboard_password" required>
        <button type="submit">Unlock</button>
        <?php if ($unlockError !== '') { ?><p class="shared-dash-error"><?php echo htmlspecialchars($unlockError, ENT_QUOTES, 'UTF-8'); ?></p><?php } ?>
      </form>
      <?php } ?>
    </div>
  </header>
  <main class="shared-dash-main">
    <?php if ($isExpired): ?>
      <section class="shared-dash-empty">This shared link has expired. Ask the owner for a fresh link.</section>
    <?php elseif ($isProtected && !$unlocked): ?>
      <section class="shared-dash-empty">Images will appear here once unlocked.</section>
    <?php elseif (empty($images)): ?>
      <section class="shared-dash-empty">No images have been added to this dashboard yet.</section>
    <?php else: ?>
      <div class="shared-dash-actions">
        <button type="button" id="shared-start-slideshow">Start Slideshow</button>
        <a href="<?php echo htmlspecialchars('index.php?share=' . rawurlencode($token) . '&download=all', ENT_QUOTES, 'UTF-8'); ?>">Download all ZIP</a>
      </div>
      <section class="collapsible-panel is-collapsed shared-dash-collapsible" data-collapsible-id="public-shared-tiles">
        <button type="button" class="collapsible-panel-toggle" id="shared-tiles-toggle" aria-expanded="false" aria-controls="shared-tiles-body" title="Expand shared tiles for more details">
          <span class="collapsible-panel-title">Shared dashboard tiles</span>
          <span class="collapsible-panel-hint">Collapsed. Click to expand for more details.</span>
          <span class="collapsible-panel-chevron" aria-hidden="true">▾</span>
        </button>
        <div id="shared-tiles-body" class="collapsible-panel-body" hidden>
          <input type="search" id="shared-tiles-search" class="section-search-input" placeholder="Search shared tiles..." aria-label="Search shared tiles" autocomplete="off">
          <section class="shared-dash-grid" id="shared-dash-grid">
        <?php foreach ($images as $idx => $img): ?>
          <?php $isVideo = isset($img['media_type']) && (string) $img['media_type'] === 'video'; ?>
          <figure class="shared-dash-item" data-index="<?php echo (int) $idx; ?>" data-image-id="<?php echo (int) $img['id']; ?>" data-url="<?php echo htmlspecialchars((string) $img['url'], ENT_QUOTES, 'UTF-8'); ?>" data-filename="<?php echo htmlspecialchars((string) $img['filename'], ENT_QUOTES, 'UTF-8'); ?>" data-media-type="<?php echo $isVideo ? 'video' : 'image'; ?>">
            <?php if ($isVideo): ?>
              <video src="<?php echo htmlspecialchars((string) $img['url'], ENT_QUOTES, 'UTF-8'); ?>" preload="metadata" controls muted playsinline></video>
            <?php else: ?>
              <img src="<?php echo htmlspecialchars((string) $img['url'], ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars((string) $img['filename'], ENT_QUOTES, 'UTF-8'); ?>" loading="lazy">
            <?php endif; ?>
          </figure>
        <?php endforeach; ?>
          </section>
          <div id="shared-tiles-empty" class="shared-dash-empty" hidden>No shared tiles match your search.</div>
        </div>
      </section>
    <?php endif; ?>
  </main>
  <?php if (!$isEmbed): ?>
  <section class="shared-dash-logo-band" aria-label="ImageKpr">
    <img src="assets/imagekpr-logo.png" alt="ImageKpr">
  </section>
  <?php imagekpr_render_footer(['context' => 'shared_dashboard']); ?>
  <?php endif; ?>
  <?php if (!$isPaidOwner && !$isEmbed) { ?><div class="shared-dash-watermark">Powered by ImageKpr</div><?php } ?>
  <div id="shared-lightbox" class="shared-lightbox" hidden>
    <button type="button" id="shared-lightbox-prev" class="shared-lightbox-nav">‹</button>
    <img id="shared-lightbox-img" src="" alt="">
    <video id="shared-lightbox-video" controls hidden></video>
    <button type="button" id="shared-lightbox-next" class="shared-lightbox-nav">›</button>
    <div class="shared-lightbox-top">
      <a id="shared-lightbox-download" href="#">Download</a>
      <button type="button" id="shared-copy-image-url">Copy URL</button>
      <button type="button" id="shared-lightbox-slideshow">Slideshow</button>
      <button type="button" id="shared-lightbox-close" class="shared-lightbox-close" aria-label="Close lightbox">×</button>
    </div>
    <span id="shared-lightbox-counter" class="shared-lightbox-counter-corner">1/1</span>
  </div>
  <div class="shared-mobile-actions">
    <button type="button" id="shared-start-slideshow-mobile">Slideshow</button>
    <a href="<?php echo htmlspecialchars('index.php?share=' . rawurlencode($token) . '&download=all', ENT_QUOTES, 'UTF-8'); ?>">Download all</a>
  </div>
  <div id="shared-footer-modal" class="shared-footer-modal" hidden aria-hidden="true">
    <div class="shared-footer-modal-backdrop" data-close="1"></div>
    <div class="shared-footer-modal-dialog" role="dialog" aria-modal="true" aria-label="Page preview">
      <button type="button" id="shared-footer-modal-close" class="shared-footer-modal-close" aria-label="Close">×</button>
      <iframe id="shared-footer-modal-frame" class="shared-footer-modal-frame" src="about:blank" title="Footer link preview"></iframe>
    </div>
  </div>
  <div id="slideshow-settings-wrap" class="slideshow-settings-wrap" hidden>
    <div class="slideshow-settings-backdrop" id="slideshow-settings-backdrop" aria-hidden="true"></div>
    <aside class="slideshow-settings-panel" id="slideshow-settings-panel" role="dialog" aria-modal="true" aria-labelledby="slideshow-settings-title">
      <h3 id="slideshow-settings-title">Slideshow</h3>
      <p class="slideshow-settings-lead">Configure slideshow behavior for this shared dashboard.</p>
      <fieldset class="slideshow-fieldset">
        <legend>Advance</legend>
        <label class="slideshow-radio"><input type="radio" name="slideshow-advance" id="slideshow-advance-manual" value="manual" checked> Manual — Space for next, arrow keys to move</label>
        <label class="slideshow-radio"><input type="radio" name="slideshow-advance" id="slideshow-advance-auto" value="auto"> Auto</label>
        <div class="slideshow-duration-row" id="slideshow-duration-row">
          <label for="slideshow-duration">Seconds per slide</label>
          <input type="number" id="slideshow-duration" min="1" max="600" value="5" step="1" class="slideshow-duration-input">
        </div>
      </fieldset>
      <label class="slideshow-check"><input type="checkbox" id="slideshow-show-filename"> Show filename at bottom (subtle)</label>
      <label class="slideshow-check"><input type="checkbox" id="slideshow-fill-image"> Fill image (cover screen, crop overflow)</label>
      <label class="slideshow-check"><input type="checkbox" id="slideshow-show-controller"> Show mini controller tray on start</label>
      <label class="slideshow-check"><input type="checkbox" id="slideshow-autoloop" checked> Loop (return to first after last)</label>
      <label class="slideshow-check" id="slideshow-randomize-loop-label"><input type="checkbox" id="slideshow-randomize-loop"> Randomize slide order (turn off to restore grid order)</label>
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
          <span class="ss-hint-item"><kbd>&larr;</kbd> <kbd>&rarr;</kbd> previous / next slide</span>
        </div>
        <div class="ss-hint-group ss-hint-play">
          <span class="ss-hint-label">Playback</span>
          <span class="ss-hint-item"><kbd>M</kbd> / <kbd>A</kbd> switch Manual / Auto</span>
          <span class="ss-hint-item"><kbd>P</kbd> pause / resume (Auto mode)</span>
          <span class="ss-hint-item"><kbd>&uarr;</kbd> <kbd>&darr;</kbd> seconds per slide (Auto mode)</span>
          <span class="ss-hint-item"><kbd>[</kbd> <kbd>]</kbd> scrub video 4s back / forward (video slides only)</span>
        </div>
        <div class="ss-hint-group ss-hint-display">
          <span class="ss-hint-label">Display</span>
          <span class="ss-hint-item"><kbd>F</kbd> toggle Fill image</span>
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
  <script>
    (function () {
      const items = Array.from(document.querySelectorAll('.shared-dash-item'));
      const tilesRoot = document.querySelector('[data-collapsible-id="public-shared-tiles"]');
      const tilesToggle = document.getElementById('shared-tiles-toggle');
      const tilesBody = document.getElementById('shared-tiles-body');
      const tilesSearch = document.getElementById('shared-tiles-search');
      const tilesEmpty = document.getElementById('shared-tiles-empty');
      function setTilesCollapsed(collapsed) {
        if (!tilesRoot || !tilesToggle || !tilesBody) return;
        tilesRoot.classList.toggle('is-collapsed', !!collapsed);
        tilesToggle.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
        tilesToggle.title = collapsed ? 'Expand shared tiles for more details' : 'Collapse shared tiles';
        tilesBody.hidden = !!collapsed;
      }
      if (tilesToggle) {
        setTilesCollapsed(true);
        tilesToggle.addEventListener('click', () => {
          setTilesCollapsed(tilesToggle.getAttribute('aria-expanded') === 'true');
        });
      }
      function applySharedTilesFilter() {
        if (!tilesSearch || !tilesEmpty) return;
        const q = tilesSearch.value.trim().toLowerCase();
        let visibleCount = 0;
        items.forEach((el) => {
          const filename = String(el.dataset.filename || '').toLowerCase();
          const mediaType = String(el.dataset.mediaType || '').toLowerCase();
          const match = !q || filename.indexOf(q) !== -1 || mediaType.indexOf(q) !== -1;
          el.hidden = !match;
          if (match) visibleCount++;
        });
        tilesEmpty.hidden = visibleCount > 0;
      }
      if (tilesSearch) tilesSearch.addEventListener('input', applySharedTilesFilter);
      const lb = document.getElementById('shared-lightbox');
      if (!lb || items.length === 0) return;
      const imgEl = document.getElementById('shared-lightbox-img');
      const videoEl = document.getElementById('shared-lightbox-video');
      const counterEl = document.getElementById('shared-lightbox-counter');
      const copyBtn = document.getElementById('shared-copy-image-url');
      const downloadEl = document.getElementById('shared-lightbox-download');
      const lightboxSlideshowBtn = document.getElementById('shared-lightbox-slideshow');
      let currentDownloadUrl = '';
      let currentDownloadFilename = 'image';
      let idx = 0;
      let touchStartX = 0;
      function closeLightbox() {
        lb.hidden = true;
        if (videoEl) {
          videoEl.pause();
          videoEl.removeAttribute('src');
          videoEl.hidden = true;
        }
      }
      const itemData = items.map((el) => ({
        el,
        id: Number(el.dataset.imageId || 0),
        url: el.dataset.url || '',
        filename: el.dataset.filename || '',
        media_type: el.dataset.mediaType || 'image',
      }));
      function openAt(i) {
        idx = (i + itemData.length) % itemData.length;
        const item = itemData[idx];
        const src = item.url;
        const isVideo = item.media_type === 'video';
        if (isVideo) {
          if (imgEl) {
            imgEl.hidden = true;
            imgEl.removeAttribute('src');
          }
          if (videoEl) {
            videoEl.hidden = false;
            videoEl.src = src;
            videoEl.controls = true;
          }
        } else {
          if (videoEl) {
            videoEl.pause();
            videoEl.hidden = true;
            videoEl.removeAttribute('src');
          }
          imgEl.hidden = false;
          imgEl.src = src;
        }
        counterEl.textContent = (idx + 1) + '/' + itemData.length;
        currentDownloadUrl = src;
        currentDownloadFilename = item.filename || ('image-' + (idx + 1));
        if (downloadEl) {
          const q = new URLSearchParams();
          q.set('share', <?php echo json_encode($token); ?>);
          q.set('download', 'one');
          q.set('image_id', String(item.id || ''));
          downloadEl.href = 'index.php?' + q.toString();
        }
        lb.hidden = false;
      }
      items.forEach((itemEl, i) => itemEl.addEventListener('click', () => openAt(i)));
      document.getElementById('shared-lightbox-close').addEventListener('click', closeLightbox);
      document.getElementById('shared-lightbox-prev').addEventListener('click', () => openAt(idx - 1));
      document.getElementById('shared-lightbox-next').addEventListener('click', () => openAt(idx + 1));
      lb.addEventListener('click', (e) => { if (e.target === lb) closeLightbox(); });
      document.addEventListener('keydown', (e) => {
        if (lb.hidden) return;
        if (e.key === 'Escape') closeLightbox();
        if (e.key === 'ArrowRight') openAt(idx + 1);
        if (e.key === 'ArrowLeft') openAt(idx - 1);
      });
      lb.addEventListener('touchstart', (e) => { touchStartX = e.changedTouches[0].clientX; }, { passive: true });
      lb.addEventListener('touchend', (e) => {
        const dx = e.changedTouches[0].clientX - touchStartX;
        if (Math.abs(dx) < 30) return;
        if (dx < 0) openAt(idx + 1); else openAt(idx - 1);
      }, { passive: true });
      copyBtn.addEventListener('click', async () => {
        try {
          await navigator.clipboard.writeText(currentDownloadUrl);
          copyBtn.textContent = 'Copied';
          setTimeout(() => { copyBtn.textContent = 'Copy URL'; }, 1200);
        } catch (_) {}
      });
      if (downloadEl) {
        downloadEl.addEventListener('click', async (e) => {
          e.preventDefault();
          const fallbackUrl = downloadEl.getAttribute('href') || '';
          try {
            const res = await fetch(currentDownloadUrl, { credentials: 'same-origin' });
            if (!res.ok) throw new Error('download failed');
            const blob = await res.blob();
            const objectUrl = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = objectUrl;
            a.download = currentDownloadFilename;
            document.body.appendChild(a);
            a.click();
            a.remove();
            URL.revokeObjectURL(objectUrl);
          } catch (_) {
            if (fallbackUrl) {
              window.location.href = fallbackUrl;
            }
          }
        });
      }
      const slides = itemData.map((item) => ({
        id: item.id,
        url: item.url,
        filename: item.filename,
        media_type: item.media_type,
      }));
      const LETTERBOX = { black: '#000', white: '#fff', red: '#b71c1c', green: '#1b5e20', blue: '#0d47a1' };
      let ss = null;
      function ssSetControllerVisible(visible) {
        const tray = document.getElementById('slideshow-mini-controller');
        const launcher = document.getElementById('slideshow-mini-launcher');
        if (!tray || !launcher || !ss) return;
        ss.controllerVisible = !!visible;
        tray.hidden = !ss.controllerVisible;
        tray.setAttribute('aria-hidden', ss.controllerVisible ? 'false' : 'true');
        launcher.hidden = ss.controllerVisible;
        launcher.setAttribute('aria-hidden', ss.controllerVisible ? 'true' : 'false');
      }
      function ssSyncController() {
        if (!ss) return;
        const setActive = (id, on) => { const el = document.getElementById(id); if (el) el.dataset.active = on ? 'true' : 'false'; };
        setActive('ss-ctrl-manual', !ss.auto);
        setActive('ss-ctrl-auto', !!ss.auto);
        setActive('ss-ctrl-loop', !!ss.autoloop);
        setActive('ss-ctrl-rand', !!ss.randomizeOrder);
        setActive('ss-ctrl-pause', !!ss.auto && !!ss.paused);
        setActive('ss-ctrl-fill', !!ss.fillImage);
        setActive('ss-ctrl-transition', ss.transition === 'fly');
        ['black', 'white', 'red', 'green', 'blue'].forEach((key) => setActive('ss-ctrl-lb-' + key, ss.letterboxKey === key));
        const readout = document.getElementById('ss-ctrl-interval-value');
        if (readout) readout.textContent = String(Math.round(ss.intervalMs / 1000)) + 's';
      }
      function ssShuffleInPlace(a) {
        for (let i = a.length - 1; i > 0; i--) {
          const j = Math.floor(Math.random() * (i + 1));
          const t = a[i];
          a[i] = a[j];
          a[j] = t;
        }
      }
      function ssToggleRandomizeOrder() {
        if (!ss) return;
        const curKey = ss.slides[ss.index] ? ss.slides[ss.index].url : '';
        ss.randomizeOrder = !ss.randomizeOrder;
        if (ss.randomizeOrder) {
          ssShuffleInPlace(ss.slides);
          let i = ss.slides.findIndex((s) => s.url === curKey);
          if (i < 0) i = 0;
          ss.index = i;
        } else {
          ss.slides = ss.baselineSlides.slice();
          let i = ss.slides.findIndex((s) => s.url === curKey);
          if (i < 0) i = 0;
          ss.index = i;
        }
        const randIn = document.getElementById('slideshow-randomize-loop');
        if (randIn) randIn.checked = ss.randomizeOrder;
        ssApplyUi();
        ssSyncController();
      }
      function ssClearTimer() {
        if (ss && ss.timerId) { clearInterval(ss.timerId); ss.timerId = null; }
        if (ss && ss.videoEndedHandler) {
          const sv = document.getElementById('slideshow-video');
          if (sv) sv.removeEventListener('ended', ss.videoEndedHandler);
          ss.videoEndedHandler = null;
        }
      }
      function ssArmTimer() {
        ssClearTimer();
        if (!ss || !ss.auto || ss.paused) return;
        const slide = ss.slides[ss.index];
        if (slide && slide.media_type === 'video') {
          const sv = document.getElementById('slideshow-video');
          if (!sv) return;
          ss.videoEndedHandler = () => ssMove(1);
          sv.addEventListener('ended', ss.videoEndedHandler, { once: true });
          sv.muted = true;
          sv.play().catch(() => {});
          return;
        }
        ss.timerId = setInterval(() => ssMove(1), ss.intervalMs);
      }
      function ssApplyUi() {
        if (!ss) return;
        const player = document.getElementById('slideshow-player');
        const bg = document.getElementById('slideshow-player-bg');
        const img = document.getElementById('slideshow-img');
        const video = document.getElementById('slideshow-video');
        const cap = document.getElementById('slideshow-filename-caption');
        const counter = document.getElementById('slideshow-counter');
        if (!player || !img || !counter || !bg || !cap || !video) return;
        const slide = ss.slides[ss.index];
        const isVideo = slide && slide.media_type === 'video';
        if (isVideo) {
          img.hidden = true;
          img.removeAttribute('src');
          img.removeAttribute('alt');
          video.hidden = false;
          video.src = slide.url;
          video.controls = true;
          video.preload = 'metadata';
          video.style.objectFit = ss.fillImage ? 'cover' : 'contain';
        } else {
          video.pause();
          video.hidden = true;
          video.removeAttribute('src');
          img.hidden = false;
          img.src = slide.url;
          img.alt = slide.filename || '';
          img.style.objectFit = ss.fillImage ? 'cover' : 'contain';
        }
        bg.style.background = LETTERBOX[ss.letterboxKey] || LETTERBOX.black;
        counter.textContent = (ss.index + 1) + ' / ' + ss.slides.length;
        if (ss.showFilename && slide.filename) {
          cap.hidden = false;
          cap.setAttribute('aria-hidden', 'false');
          cap.textContent = slide.filename;
        } else {
          cap.hidden = true;
          cap.setAttribute('aria-hidden', 'true');
          cap.textContent = '';
        }
        if (ss.auto && !ss.paused) ssArmTimer();
      }
      function ssMove(delta) {
        if (!ss || !ss.slides.length) return;
        const next = ss.index + delta;
        if (next < 0) {
          if (!ss.autoloop) return;
          if (ss.randomizeOrder) ssShuffleInPlace(ss.slides);
          ss.index = ss.slides.length - 1;
        } else if (next >= ss.slides.length) {
          if (!ss.autoloop) { ssClearTimer(); return; }
          if (ss.randomizeOrder) ssShuffleInPlace(ss.slides);
          ss.index = 0;
        } else {
          ss.index = next;
        }
        ssApplyUi();
      }
      function ssClosePlayer() {
        ssClearTimer();
        const sv = document.getElementById('slideshow-video');
        if (sv) {
          sv.pause();
          sv.hidden = true;
          sv.removeAttribute('src');
        }
        const player = document.getElementById('slideshow-player');
        if (player) {
          player.hidden = true;
          player.setAttribute('aria-hidden', 'true');
        }
        ss = null;
      }
      function ssStartFromForm() {
        if (!slides.length) return;
        const auto = !!document.getElementById('slideshow-advance-auto')?.checked;
        const duration = Math.max(1, Math.min(600, Number(document.getElementById('slideshow-duration')?.value || 5)));
        const baselineSlides = slides.slice();
        const randomizeOrder = !!document.getElementById('slideshow-randomize-loop')?.checked;
        const deck = slides.slice();
        if (randomizeOrder) ssShuffleInPlace(deck);
        ss = {
          slides: deck,
          baselineSlides,
          index: 0,
          auto: auto,
          paused: false,
          autoloop: !!document.getElementById('slideshow-autoloop')?.checked,
          randomizeOrder,
          showFilename: !!document.getElementById('slideshow-show-filename')?.checked,
          fillImage: !!document.getElementById('slideshow-fill-image')?.checked,
          transition: document.getElementById('slideshow-trans-fly')?.checked ? 'fly' : 'diffuse',
          letterboxKey: document.querySelector('input[name="slideshow-letterbox"]:checked')?.value || 'black',
          intervalMs: Math.round(duration * 1000),
          timerId: null,
          videoEndedHandler: null,
          controllerVisible: !!document.getElementById('slideshow-show-controller')?.checked,
        };
        const wrap = document.getElementById('slideshow-settings-wrap');
        const player = document.getElementById('slideshow-player');
        if (wrap) {
          wrap.classList.remove('slideshow-settings-open');
          wrap.hidden = true;
        }
        if (player) {
          player.hidden = false;
          player.setAttribute('aria-hidden', 'false');
        }
        ssApplyUi();
        ssSetControllerVisible(ss.controllerVisible);
        ssSyncController();
        ssArmTimer();
      }
      function ssOpenSettings() {
        const wrap = document.getElementById('slideshow-settings-wrap');
        if (!wrap || !slides.length) return;
        lb.hidden = true;
        wrap.hidden = false;
        wrap.classList.add('slideshow-settings-open');
      }
      function ssCloseSettings() {
        const wrap = document.getElementById('slideshow-settings-wrap');
        if (!wrap) return;
        wrap.classList.remove('slideshow-settings-open');
        wrap.hidden = true;
      }
      const slideBtn = document.getElementById('shared-start-slideshow');
      const slideBtnMobile = document.getElementById('shared-start-slideshow-mobile');
      if (slideBtn) slideBtn.addEventListener('click', ssOpenSettings);
      if (slideBtnMobile) slideBtnMobile.addEventListener('click', ssOpenSettings);
      if (lightboxSlideshowBtn) lightboxSlideshowBtn.addEventListener('click', ssOpenSettings);
      document.getElementById('slideshow-settings-backdrop')?.addEventListener('click', ssCloseSettings);
      document.getElementById('slideshow-settings-cancel')?.addEventListener('click', ssCloseSettings);
      document.getElementById('slideshow-start')?.addEventListener('click', ssStartFromForm);
      document.getElementById('slideshow-exit-btn')?.addEventListener('click', ssClosePlayer);
      document.getElementById('ss-ctrl-close')?.addEventListener('click', () => ssSetControllerVisible(false));
      document.getElementById('slideshow-mini-launcher')?.addEventListener('click', () => ssSetControllerVisible(true));
      document.getElementById('ss-ctrl-manual')?.addEventListener('click', () => { if (!ss) return; ss.auto = false; ss.paused = false; ssClearTimer(); ssSyncController(); });
      document.getElementById('ss-ctrl-auto')?.addEventListener('click', () => { if (!ss) return; ss.auto = true; ss.paused = false; ssSyncController(); ssArmTimer(); });
      document.getElementById('ss-ctrl-loop')?.addEventListener('click', () => { if (!ss) return; ss.autoloop = !ss.autoloop; ssSyncController(); });
      document.getElementById('ss-ctrl-rand')?.addEventListener('click', () => ssToggleRandomizeOrder());
      document.getElementById('ss-ctrl-pause')?.addEventListener('click', () => { if (!ss || !ss.auto) return; ss.paused = !ss.paused; ssSyncController(); ssArmTimer(); });
      document.getElementById('ss-ctrl-fill')?.addEventListener('click', () => { if (!ss) return; ss.fillImage = !ss.fillImage; ssApplyUi(); ssSyncController(); });
      document.getElementById('ss-ctrl-transition')?.addEventListener('click', () => { if (!ss) return; ss.transition = ss.transition === 'fly' ? 'diffuse' : 'fly'; ssSyncController(); });
      document.getElementById('ss-ctrl-interval-dec')?.addEventListener('click', () => { if (!ss) return; ss.intervalMs = Math.max(1000, ss.intervalMs - 1000); ssSyncController(); ssArmTimer(); });
      document.getElementById('ss-ctrl-interval-inc')?.addEventListener('click', () => { if (!ss) return; ss.intervalMs = Math.min(600000, ss.intervalMs + 1000); ssSyncController(); ssArmTimer(); });
      ['black', 'white', 'red', 'green', 'blue'].forEach((key) => {
        document.getElementById('ss-ctrl-lb-' + key)?.addEventListener('click', () => {
          if (!ss) return;
          ss.letterboxKey = key;
          ssApplyUi();
          ssSyncController();
        });
      });
      document.addEventListener('keydown', (e) => {
        const player = document.getElementById('slideshow-player');
        const settings = document.getElementById('slideshow-settings-wrap');
        if (settings && !settings.hidden && e.key === 'Escape') {
          e.preventDefault();
          ssCloseSettings();
          return;
        }
        if (!player || player.hidden || !ss) return;
        if (e.key === 'Escape') { e.preventDefault(); ssClosePlayer(); return; }
        if (e.key === 'ArrowRight' || e.key === ' ') { e.preventDefault(); ssMove(1); }
        if (e.key === 'ArrowLeft') { e.preventDefault(); ssMove(-1); }
        if (e.key.toLowerCase() === 'm') { ss.auto = false; ss.paused = false; ssClearTimer(); ssSyncController(); }
        if (e.key.toLowerCase() === 'a') { ss.auto = true; ss.paused = false; ssSyncController(); ssArmTimer(); }
        if (e.key.toLowerCase() === 'p' && ss.auto) { ss.paused = !ss.paused; ssSyncController(); ssArmTimer(); }
        if (e.key.toLowerCase() === 'f') { ss.fillImage = !ss.fillImage; ssApplyUi(); ssSyncController(); }
        if (e.key.toLowerCase() === 'l') { ss.autoloop = !ss.autoloop; ssSyncController(); }
        if (e.key.toLowerCase() === 'r') { e.preventDefault(); ssToggleRandomizeOrder(); }
        if (e.key.toLowerCase() === 'c') { ssSetControllerVisible(!ss.controllerVisible); }
        if (e.key === 'ArrowUp') { ss.intervalMs = Math.min(600000, ss.intervalMs + 1000); ssSyncController(); ssArmTimer(); }
        if (e.key === 'ArrowDown') { ss.intervalMs = Math.max(1000, ss.intervalMs - 1000); ssSyncController(); ssArmTimer(); }
      });

      const ssStage = document.querySelector('#slideshow-player .slideshow-stage');
      if (ssStage) {
        let ssTouchX = 0, ssTouchY = 0, ssTouchTime = 0, ssTouchMoved = false;
        let ssDoubleTapTime = 0, ssSingleTapTimer = null;
        ssStage.addEventListener('touchstart', (e) => {
          if (!ss) return;
          const t = e.touches[0];
          ssTouchX = t.clientX;
          ssTouchY = t.clientY;
          ssTouchTime = Date.now();
          ssTouchMoved = false;
        }, { passive: true });
        ssStage.addEventListener('touchmove', (e) => {
          if (!ss) return;
          const t = e.touches[0];
          const dx = Math.abs(t.clientX - ssTouchX);
          const dy = Math.abs(t.clientY - ssTouchY);
          if (dx > 10 || dy > 10) ssTouchMoved = true;
          if (dx > dy && dx > 10) e.preventDefault();
        }, { passive: false });
        ssStage.addEventListener('touchend', (e) => {
          if (!ss) return;
          const t = e.changedTouches[0];
          const dx = t.clientX - ssTouchX;
          const absDx = Math.abs(dx);
          const absDy = Math.abs(t.clientY - ssTouchY);
          const elapsed = Date.now() - ssTouchTime;
          if (absDx > 40 && absDx > absDy * 1.5 && elapsed < 500) {
            if (dx < 0) ssMove(1); else ssMove(-1);
            return;
          }
          if (!ssTouchMoved && elapsed < 300) {
            const now = Date.now();
            if (now - ssDoubleTapTime < 300) {
              clearTimeout(ssSingleTapTimer);
              ssDoubleTapTime = 0;
              ss.fillImage = !ss.fillImage;
              ssApplyUi();
              ssSyncController();
            } else {
              ssDoubleTapTime = now;
              ssSingleTapTimer = setTimeout(() => {
                if (!ss) return;
                if (ss.auto) {
                  ss.paused = !ss.paused;
                  ssSyncController();
                  ssArmTimer();
                } else {
                  ssMove(1);
                }
              }, 300);
            }
          }
        }, { passive: true });
      }

      const footerModal = document.getElementById('shared-footer-modal');
      const footerFrame = document.getElementById('shared-footer-modal-frame');
      const footerCloseBtn = document.getElementById('shared-footer-modal-close');
      function closeFooterModal() {
        if (!footerModal) return;
        footerModal.hidden = true;
        footerModal.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
        if (footerFrame) footerFrame.src = 'about:blank';
      }
      function openFooterModal(url) {
        if (!footerModal || !footerFrame) return;
        footerFrame.src = url;
        footerModal.hidden = false;
        footerModal.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
      }
      document.querySelectorAll('.ikpr-site-footer a').forEach((a) => {
        a.addEventListener('click', (e) => {
          const href = a.getAttribute('href') || '';
          if (!href || href.startsWith('mailto:') || href.startsWith('tel:')) return;
          e.preventDefault();
          const absoluteHref = new URL(href, window.location.href).href;
          openFooterModal(absoluteHref);
        });
      });
      if (footerCloseBtn) footerCloseBtn.addEventListener('click', closeFooterModal);
      if (footerModal) {
        footerModal.addEventListener('click', (e) => {
          if (e.target && e.target.dataset && e.target.dataset.close === '1') closeFooterModal();
        });
      }
      document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && footerModal && !footerModal.hidden) closeFooterModal();
      });
    })();
  </script>
</body>
</html>
