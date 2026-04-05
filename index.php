<?php
ob_start();
require_once __DIR__ . '/inc/auth.php';
imagekpr_require_login_html();
$ikName = isset($_SESSION['name']) ? (string) $_SESSION['name'] : '';
$ikEmail = isset($_SESSION['email']) ? (string) $_SESSION['email'] : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>ImageKpr</title>
  <link rel="stylesheet" href="styles.css">
</head>
<body>
  <header class="hero">
    <div class="hero-row">
      <div class="logo" aria-hidden="true">
        <img src="assets/imagekpr-logo.png" alt="ImageKpr" onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
        <span class="logo-fallback">ImageKpr</span>
      </div>
      <div class="upload-zone" id="upload-zone">
        <input type="file" id="upload-input" accept="image/jpeg,image/png,image/gif,image/webp" multiple hidden>
        <div class="upload-zone-text">
          <span id="upload-text">Drag & drop or click to upload</span>
          <span class="upload-zone-hint">Files larger than 3MB will be resized to fit or smaller.</span>
        </div>
        <div id="upload-progress" class="upload-progress" hidden></div>
      </div>
      <div class="user-session-bar">
        <span class="user-session-email" title="<?php echo htmlspecialchars($ikEmail, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($ikName !== '' ? $ikName : $ikEmail, ENT_QUOTES, 'UTF-8'); ?></span>
        <a href="auth/logout.php" class="user-session-logout">Log out</a>
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
    <div id="folder-icons" class="folder-icons"></div>
    <button type="button" id="manage-folders-btn" class="ikpr-btn-folders">Manage folders</button>
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
      <div class="banner-row" id="hint-banner-row">
        <div class="user-hint-banner">
          <span class="user-hint-text" id="user-hint-text">Loading…</span>
          <input type="search" id="search" placeholder="Search..." aria-label="Search images">
          <button type="button" id="bulk-select-all" class="select-all-visible-btn" title="Select every image currently shown below (folder, tag, and search filters apply; scroll to load more rows, then click again to include them)">Select all</button>
          <button type="button" id="scroll-to-top" class="scroll-to-top-btn" aria-label="Scroll to top" title="Scroll to top">↑ Top</button>
        </div>
      </div>
      <div class="selection-banner-below" id="selection-banner" hidden>
      <div class="bulk-bar" id="bulk-bar">
        <span id="bulk-count">0 selected</span>
        <button type="button" id="bulk-select-all-bar" title="Add every image currently shown in the grid to this selection (same filters; scroll to load more first if needed)">Select all</button>
        <button type="button" id="bulk-delete" class="ikpr-btn-delete">Delete</button>
        <button type="button" id="bulk-download">Download ZIP</button>
        <button type="button" id="bulk-slideshow">Slideshow</button>
        <button type="button" id="bulk-tags" class="ikpr-btn-tags">Apply/Manage Tags</button>
        <button type="button" id="bulk-folders" class="ikpr-btn-folders">Apply/Manage Folders</button>
        <button type="button" id="bulk-rename">Rename</button>
        <button type="button" id="bulk-clear">Clear</button>
      </div>
      <div class="selection-selected-header">
        <span class="dashboard-label dashboard-label-selected">Selected <span class="dashboard-label-hint">— drag thumbnails to set slideshow order</span></span>
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
        <button type="button" id="modal-next" class="modal-nav-btn modal-nav-next" aria-label="Next image" hidden>
          <svg width="20" height="20" viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 18l6-6-6-6"/></svg>
        </button>
      </div>
      <div class="modal-side">
        <div class="modal-actions">
          <button type="button" id="modal-copy">Copy URL</button>
          <button type="button" id="modal-copy-image">Copy Image</button>
          <button type="button" id="modal-download">Download</button>
          <button type="button" id="modal-fullscreen-btn">Full screen</button>
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
        <label class="slideshow-radio"><input type="radio" name="slideshow-advance" id="slideshow-advance-manual" value="manual" checked> Manual — Space for next, arrow keys to move</label>
        <label class="slideshow-radio"><input type="radio" name="slideshow-advance" id="slideshow-advance-auto" value="auto"> Auto</label>
        <div class="slideshow-duration-row" id="slideshow-duration-row">
          <label for="slideshow-duration">Seconds per slide</label>
          <input type="number" id="slideshow-duration" min="1" max="600" value="5" step="1" class="slideshow-duration-input">
        </div>
      </fieldset>
      <label class="slideshow-check"><input type="checkbox" id="slideshow-autoloop" checked> Loop (return to first after last)</label>
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
      <p class="slideshow-settings-hint">Exit: click outside the picture, the × button, or press Esc.</p>
      <div class="slideshow-settings-actions">
        <button type="button" id="slideshow-start" class="slideshow-btn-primary">Start slideshow</button>
        <button type="button" id="slideshow-settings-cancel">Cancel</button>
      </div>
    </aside>
  </div>

  <div id="slideshow-player" class="slideshow-player" hidden aria-hidden="true">
    <button type="button" class="slideshow-exit" id="slideshow-exit-btn" aria-label="Exit slideshow">×</button>
    <div class="slideshow-counter" id="slideshow-counter" aria-live="polite"></div>
    <div class="slideshow-stage">
      <div class="slideshow-player-bg" id="slideshow-player-bg" aria-hidden="true"></div>
      <img id="slideshow-img" class="slideshow-img" src="" alt="">
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
      <p id="manage-import-success" class="manage-import-success" hidden></p>
      <div class="dialog-actions">
        <button type="button" id="manage-import" class="ikpr-btn-folders">Import</button>
        <button type="button" id="manage-export" class="ikpr-btn-folders">Export</button>
        <input type="file" id="manage-import-file" accept=".json" hidden>
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
      <p class="upload-confirm-hint">Review the images below. Use Rename, Apply/Manage Tags, Apply/Manage Folders per image. Remove any you don't want before confirming.</p>
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

  <footer class="credits-footer">
    © 2026 <a href="https://mar.sg" target="_blank" rel="noopener noreferrer">Mar.sg</a>
  </footer>

  <script src="folders.js"></script>
  <script src="app.js"></script>
</body>
</html>
