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
        <span id="upload-text">Drag & drop or click to upload</span>
        <div id="upload-progress" class="upload-progress" hidden></div>
      </div>
    </div>
  </header>

  <section class="dashboard">
    <div class="dashboard-inbox" id="dashboard-inbox" hidden>
      <span id="inbox-count">0</span> images in inbox.
      <button type="button" id="inbox-import-btn">Import all</button>
    </div>
    <div class="dashboard-top-row">
      <div class="dashboard-stats">
        <div class="stat">
          <span class="stat-value" id="stat-total-images">—</span>
          <span class="stat-label">Images</span>
        </div>
        <div class="stat">
          <span class="stat-value" id="stat-total-storage">—</span>
          <span class="stat-label">Storage</span>
        </div>
      </div>
      <div class="toolbar">
        <button type="button" id="select-mode" class="pill-btn" aria-pressed="false">Enter Selection Mode</button>
        <input type="search" id="search" placeholder="Search..." aria-label="Search images">
      </div>
    </div>
    <div class="dashboard-selection" id="selection-banner" hidden>
      <div class="bulk-bar" id="bulk-bar">
        <span id="bulk-count">0 selected</span>
        <button type="button" id="bulk-delete">Delete</button>
        <button type="button" id="bulk-download">Download ZIP</button>
        <button type="button" id="bulk-tags">Edit tags</button>
        <button type="button" id="bulk-add-list">Add to list</button>
        <button type="button" id="bulk-rename">Rename</button>
        <button type="button" id="bulk-clear">Clear</button>
      </div>
      <span class="dashboard-label">Selected</span>
      <div class="selection-row" id="selection-row"></div>
    </div>
  </section>

  <section class="lists-filter">
    <label>Show:</label>
    <input type="hidden" id="list-filter" value="" aria-label="Filter by list">
    <div id="list-pills" class="list-pills"></div>
    <button type="button" id="manage-lists-btn">Manage lists</button>
  </section>

  <section class="sort-row" aria-label="Sort order">
    <span class="sort-label">Sort:</span>
    <div id="sort-pills" class="sort-pills"></div>
  </section>

  <main class="main-content">
    <div class="user-hint-banner">
      <span class="user-hint-text">Click card to copy URL • Click icon to view full size</span>
      <button type="button" id="scroll-to-top" class="scroll-to-top-btn" aria-label="Scroll to top" title="Scroll to top">↑ Top</button>
    </div>
    <div id="grid" class="grid grid-stock"></div>
    <div id="load-more" class="load-more"></div>
  </main>

  <div id="modal" class="modal" hidden aria-modal="true" aria-labelledby="modal-title">
    <div class="modal-content">
      <img id="modal-img" src="" alt="">
      <div class="modal-tags">
        <label for="modal-tag-input">Tags:</label>
        <input type="text" id="modal-tag-input" placeholder="Add tag, press Enter">
        <div id="modal-tag-pills" class="tag-pills"></div>
      </div>
      <div class="modal-actions">
        <button type="button" id="modal-copy">Copy URL</button>
        <button type="button" id="modal-download">Download</button>
        <button type="button" id="modal-delete">Delete</button>
        <button type="button" id="modal-close">Close</button>
      </div>
    </div>
  </div>

  <div id="toast" class="toast" hidden aria-live="polite"></div>

  <div id="manage-lists-dialog" class="dialog" hidden>
    <div class="dialog-content">
      <h3>Manage lists</h3>
      <div id="manage-lists-list"></div>
      <label>New list: <input type="text" id="new-list-name" placeholder="List name"></label>
      <button type="button" id="manage-create-list">Create</button>
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
      <p class="upload-confirm-hint">Review the images below. Remove any you don't want before confirming.</p>
      <div id="upload-confirm-grid" class="upload-confirm-grid"></div>
      <div class="upload-add-to-list">
        <label for="upload-add-to-list-select" class="upload-add-to-list-label">Add to list (optional):</label>
        <select id="upload-add-to-list-select" aria-label="Add uploads to list">
          <option value="">— None —</option>
        </select>
        <input type="text" id="upload-add-to-list-new" class="upload-add-to-list-new" placeholder="Or type new list name" autocomplete="off">
      </div>
      <div class="dialog-actions upload-confirm-actions">
        <span id="upload-confirm-count" class="upload-confirm-count"></span>
        <button type="button" id="upload-confirm-upload">Upload</button>
        <button type="button" id="upload-confirm-cancel">Cancel</button>
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

  <div id="add-to-list-dialog" class="dialog" hidden aria-modal="true" aria-labelledby="add-to-list-title">
    <div class="dialog-content">
      <h3 id="add-to-list-title">Add to list</h3>
      <label for="add-to-list-input" class="dialog-label">List name:</label>
      <input type="text" id="add-to-list-input" placeholder="e.g. Favourites" autocomplete="off">
      <div class="dialog-actions">
        <button type="button" id="add-to-list-ok">OK</button>
        <button type="button" id="add-to-list-cancel">Cancel</button>
      </div>
    </div>
  </div>

  <footer class="credits-footer">
    © 2026 <a href="https://mar.sg" target="_blank" rel="noopener noreferrer">Mar.sg</a>
  </footer>

  <script src="lists.js"></script>
  <script src="app.js"></script>
</body>
</html>
