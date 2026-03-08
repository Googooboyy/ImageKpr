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
    <div class="hero-inner">
      <div class="logo" aria-hidden="true">
        <img src="assets/logo.svg" alt="ImageKpr" onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
        <span class="logo-fallback">ImageKpr</span>
      </div>
    </div>
  </header>

  <section class="dashboard">
    <div class="dashboard-inbox" id="dashboard-inbox" hidden>
      <span id="inbox-count">0</span> images in inbox.
      <button type="button" id="inbox-import-btn">Import all</button>
    </div>
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
    <div class="dashboard-last10">
      <span class="dashboard-label">Last 10 uploaded</span>
      <div class="last10-row" id="last10-row"></div>
    </div>
  </section>

  <section class="lists-filter">
    <label for="list-filter">Show:</label>
    <select id="list-filter" aria-label="Filter by list">
      <option value="">All</option>
    </select>
    <button type="button" id="manage-lists-btn">Manage lists</button>
  </section>

  <main class="main-content">
    <div class="toolbar">
      <label><input type="checkbox" id="select-mode"> Select</label>
      <input type="search" id="search" placeholder="Search..." aria-label="Search images">
      <select id="sort" aria-label="Sort order">
        <option value="date_desc">Date (newest)</option>
        <option value="date_asc">Date (oldest)</option>
        <option value="size_desc">Size (largest)</option>
        <option value="size_asc">Size (smallest)</option>
        <option value="name_asc">Filename (A–Z)</option>
        <option value="name_desc">Filename (Z–A)</option>
        <option value="random">Random</option>
      </select>
      <div class="upload-zone" id="upload-zone">
        <input type="file" id="upload-input" accept="image/jpeg,image/png,image/gif,image/webp" multiple hidden>
        <span id="upload-text">Drag & drop or click to upload</span>
        <div id="upload-progress" class="upload-progress" hidden></div>
      </div>
    </div>
    <div class="bulk-bar" id="bulk-bar" hidden>
      <span id="bulk-count">0 selected</span>
      <button type="button" id="bulk-delete">Delete</button>
      <button type="button" id="bulk-download">Download ZIP</button>
      <button type="button" id="bulk-tags">Edit tags</button>
      <button type="button" id="bulk-add-list">Add to list</button>
      <button type="button" id="bulk-rename">Rename</button>
      <button type="button" id="bulk-clear">Clear</button>
    </div>
    <p class="user-hint">Click card to copy URL • Click icon to view full size</p>
    <div id="grid" class="grid"></div>
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

  <script src="app.js"></script>
</body>
</html>
