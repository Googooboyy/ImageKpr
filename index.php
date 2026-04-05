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
    <button type="button" id="manage-folders-btn">Manage folders</button>
  </section>

  <section class="sort-row" aria-label="Sort order">
    <span class="sort-label">Sort:</span>
    <div id="sort-pills" class="sort-pills"></div>
  </section>

  <section class="tags-row">
    <label>Tags:</label>
    <div id="tag-filters" class="tag-filters"></div>
    <button type="button" id="manage-tags-btn">Manage tags</button>
  </section>

  <main class="main-content">
    <div class="banner-sticky">
      <div class="banner-row" id="hint-banner-row">
        <div class="user-hint-banner">
          <span class="user-hint-text" id="user-hint-text">Loading…</span>
          <input type="search" id="search" placeholder="Search..." aria-label="Search images">
          <button type="button" id="scroll-to-top" class="scroll-to-top-btn" aria-label="Scroll to top" title="Scroll to top">↑ Top</button>
        </div>
      </div>
      <div class="selection-banner-below" id="selection-banner" hidden>
      <div class="bulk-bar" id="bulk-bar">
        <span id="bulk-count">0 selected</span>
        <button type="button" id="bulk-delete">Delete</button>
        <button type="button" id="bulk-download">Download ZIP</button>
        <button type="button" id="bulk-tags">Manage Tags</button>
        <button type="button" id="bulk-folders">Manage Folders</button>
        <button type="button" id="bulk-rename">Rename</button>
        <button type="button" id="bulk-clear">Clear</button>
      </div>
      <span class="dashboard-label">Selected</span>
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
          <button type="button" id="modal-download">Download</button>
          <button type="button" id="modal-delete">Delete</button>
          <button type="button" id="modal-manage-tags">Manage Tags</button>
          <button type="button" id="modal-manage-folders">Manage Folders</button>
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
        <button type="button" id="manage-tags-image-add">Add</button>
        <button type="button" id="manage-tags-image-close">Done</button>
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
        <button type="button" id="manage-folders-image-add">Add</button>
        <button type="button" id="manage-folders-image-close">Done</button>
      </div>
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
          <button type="button" id="bulk-tags-add-btn">Add</button>
        </div>
      </div>
      <div class="bulk-tags-remove-row">
        <label class="dialog-label">Remove tag:</label>
        <div class="add-tag-row">
          <select id="bulk-tags-remove-select" aria-label="Select tag to remove"><option value="">— Select tag —</option></select>
          <button type="button" id="bulk-tags-remove-btn">Remove</button>
        </div>
      </div>
      <div class="dialog-actions">
        <button type="button" id="bulk-tags-dialog-close">Done</button>
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
          <button type="button" id="bulk-folders-add-btn">Add</button>
        </div>
      </div>
      <div class="bulk-folders-remove-row">
        <label class="dialog-label">Remove from folder:</label>
        <div class="add-tag-row">
          <select id="bulk-folders-remove-select" aria-label="Select folder"><option value="">— Select folder —</option></select>
          <button type="button" id="bulk-folders-remove-btn">Remove</button>
        </div>
      </div>
      <div class="dialog-actions">
        <button type="button" id="bulk-folders-dialog-close">Done</button>
      </div>
    </div>
  </div>

  <div id="manage-folders-dialog" class="dialog" hidden>
    <div class="dialog-content">
      <h3>Manage folders</h3>
      <div id="manage-folders-list"></div>
      <label>New folder: <input type="text" id="new-folder-name" placeholder="Folder name"></label>
      <button type="button" id="manage-create-folder">Create</button>
      <p id="manage-import-success" class="manage-import-success" hidden></p>
      <div class="dialog-actions">
        <button type="button" id="manage-import">Import</button>
        <button type="button" id="manage-export">Export</button>
        <input type="file" id="manage-import-file" accept=".json" hidden>
        <button type="button" id="manage-close">Close</button>
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
      <p class="upload-confirm-hint">Review the images below. Use Rename, Manage Tags, Manage Folders per image. Remove any you don't want before confirming.</p>
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
      <p class="inbox-import-hint">Review files. Use Rename, Manage Tags, Manage Folders per file. Skip any for later – they stay in inbox.</p>
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
        <button type="button" id="add-to-folder-ok">OK</button>
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
        <button type="button" id="add-to-folder-select-ok">Add</button>
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
        <button type="button" id="add-tag-ok">Add</button>
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
        <button type="button" id="manage-tags-close">Close</button>
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
