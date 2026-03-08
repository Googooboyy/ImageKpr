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
        <input type="search" id="search" placeholder="Search..." aria-label="Search images">
      </div>
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
      <div class="banner-row">
        <button type="button" id="select-mode" class="select-mode-btn" aria-pressed="false">Enter Selection Mode</button>
        <div class="user-hint-banner">
          <span class="user-hint-text">Click card to copy URL • Click <span class="hint-expand-icon" aria-hidden="true"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 3h6v6M9 21H3v-6M21 3l-7 7M3 21l7-7"/></svg></span> to view full size</span>
          <button type="button" id="scroll-to-top" class="scroll-to-top-btn" aria-label="Scroll to top" title="Scroll to top">↑ Top</button>
        </div>
      </div>
      <div class="selection-banner-below" id="selection-banner" hidden>
      <div class="bulk-bar" id="bulk-bar">
        <span id="bulk-count">0 selected</span>
        <button type="button" id="bulk-delete">Delete</button>
        <button type="button" id="bulk-download">Download ZIP</button>
        <button type="button" id="bulk-tags">Edit tags</button>
        <button type="button" id="bulk-add-folder">Add to folder</button>
        <button type="button" id="bulk-remove-folder">Remove from folder</button>
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
    <div class="modal-content">
      <img id="modal-img" src="" alt="">
      <div class="modal-rename">
        <label for="modal-filename">Filename:</label>
        <input type="text" id="modal-filename" placeholder="Filename">
        <button type="button" id="modal-rename-btn">Rename</button>
      </div>
      <div class="modal-tags">
        <label for="modal-tag-input">Tags:</label>
        <div class="modal-tag-input-row">
          <input type="text" id="modal-tag-input" placeholder="Add tag">
          <button type="button" id="modal-add-tag-btn">Add Tag</button>
        </div>
        <div id="modal-tag-pills" class="tag-pills"></div>
      </div>
      <div class="modal-folders" id="modal-folders-section" hidden>
        <label>In folders:</label>
        <div id="modal-folder-pills" class="folder-pills"></div>
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

  <div id="manage-folders-dialog" class="dialog" hidden>
    <div class="dialog-content">
      <h3>Manage folders</h3>
      <div id="manage-folders-list"></div>
      <label>New folder: <input type="text" id="new-folder-name" placeholder="Folder name"></label>
      <button type="button" id="manage-create-folder">Create</button>
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
      <div class="upload-add-to-folder">
        <label for="upload-add-to-folder-select" class="upload-add-to-folder-label">Add to folder (optional):</label>
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
