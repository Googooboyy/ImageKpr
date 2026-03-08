/* ImageKpr - app logic */
(function () {
  'use strict';

  const API_BASE = 'api';

  function fetchJSON(url) {
    return fetch(url).then(r => {
      if (!r.ok) throw new Error(r.statusText);
      return r.json();
    });
  }

  function showToast(msg) {
    const el = document.getElementById('toast');
    el.textContent = msg;
    el.hidden = false;
    clearTimeout(showToast._t);
    showToast._t = setTimeout(() => { el.hidden = true; }, 2000);
  }

  function confirmDialog(msg) {
    return new Promise((resolve) => {
      const d = document.getElementById('confirm-dialog');
      const msgEl = document.getElementById('confirm-message');
      const okBtn = document.getElementById('confirm-ok');
      const cancelBtn = document.getElementById('confirm-cancel');
      msgEl.textContent = msg;
      d.hidden = false;
      const cleanup = () => {
        d.hidden = true;
        okBtn.onclick = null;
        cancelBtn.onclick = null;
      };
      okBtn.onclick = () => { cleanup(); resolve(true); };
      cancelBtn.onclick = () => { cleanup(); resolve(false); };
    });
  }

  function addToListDialog(defaultValue) {
    return new Promise((resolve) => {
      const d = document.getElementById('add-to-list-dialog');
      const input = document.getElementById('add-to-list-input');
      const okBtn = document.getElementById('add-to-list-ok');
      const cancelBtn = document.getElementById('add-to-list-cancel');
      input.value = defaultValue || 'Favourites';
      input.select();
      d.hidden = false;
      input.focus();
      document.body.style.overflow = 'hidden';
      const cleanup = () => {
        d.hidden = true;
        document.body.style.overflow = '';
        input.onkeydown = null;
        okBtn.onclick = null;
        cancelBtn.onclick = null;
        d.onclick = null;
        document.removeEventListener('keydown', onEscape);
      };
      const submit = () => {
        const v = input.value.trim();
        cleanup();
        resolve(v || null);
      };
      const cancel = () => {
        cleanup();
        resolve(null);
      };
      const onEscape = (e) => { if (e.key === 'Escape') cancel(); };
      input.onkeydown = (e) => {
        if (e.key === 'Enter') { e.preventDefault(); submit(); }
      };
      okBtn.onclick = submit;
      cancelBtn.onclick = cancel;
      d.onclick = (e) => { if (e.target === d) cancel(); };
      document.addEventListener('keydown', onEscape);
    });
  }

  function copyUrl(url) {
    navigator.clipboard.writeText(url).then(() => showToast('Copied!')).catch(() => showToast('Copy failed'));
  }

  function formatBytes(b) {
    if (b < 1024) return b + ' B';
    if (b < 1024 * 1024) return (b / 1024).toFixed(1) + ' KB';
    return (b / (1024 * 1024)).toFixed(1) + ' MB';
  }

  function formatDate(s) {
    if (!s) return '';
    const d = new Date(s);
    return isNaN(d) ? s : d.toLocaleDateString();
  }

  function truncate(str, len) {
    if (!str || str.length <= len) return str || '';
    return str.slice(0, len - 2) + '…';
  }

  let selectedIds = new Set();
  let selectedImages = new Map();
  let selectMode = false;

  function updateBulkBar() {
    const count = document.getElementById('bulk-count');
    const banner = document.getElementById('selection-banner');
    if (selectedIds.size > 0) {
      count.textContent = selectedIds.size + ' selected';
      updateSelectionBanner();
      if (banner) banner.hidden = false;
      document.body.classList.add('selection-active');
    } else {
      if (banner) banner.hidden = true;
      document.body.classList.remove('selection-active');
    }
  }

  function updateSelectionBanner() {
    const banner = document.getElementById('selection-banner');
    const row = document.getElementById('selection-row');
    if (!banner || !row) return;
    row.innerHTML = '';
    selectedImages.forEach((data, id) => {
      const wrap = document.createElement('div');
      wrap.className = 'selection-thumb';
      const im = document.createElement('img');
      im.src = data.url;
      im.alt = data.filename;
      wrap.appendChild(im);
      row.appendChild(wrap);
    });
    banner.hidden = false;
  }

  function renderCard(img) {
    const article = document.createElement('article');
    article.className = 'grid-item card';
    article.dataset.id = img.id;
    article.dataset.url = img.url;
    article.dataset.filename = img.filename;
    const name = truncate(img.filename, 24);
    const size = formatBytes(img.size_bytes || 0);
    const date = formatDate(img.date_uploaded);
    const defList = window.ImageKprLists ? window.ImageKprLists.getDefaultList() : 'Favourites';
    const inFav = window.ImageKprLists ? window.ImageKprLists.isInList(defList, img.id) : false;
    const starSvg = '<svg width="16" height="16" viewBox="0 0 24 24" fill="' + (inFav ? 'currentColor' : 'none') + '" stroke="currentColor" stroke-width="2"><polygon points="12 2 15 9 22 9 17 14 19 22 12 18 5 22 7 14 2 9 9 9"/></svg>';
    const cb = '<input type="checkbox" class="card-select" data-id="' + img.id + '" style="display:' + (selectMode ? 'inline-block' : 'none') + '">';
    article.innerHTML =
      '<div class="card-inner">' + cb +
      '<button type="button" class="card-star' + (inFav ? ' in-list' : '') + '" aria-label="Add to list" data-id="' + img.id + '">' + starSvg + '</button>' +
      '<img class="card-img" data-src="' + (img.url || '') + '" alt="' + (img.filename || 'Image') + '" loading="lazy">' +
      '<div class="card-info">' +
      '<span class="card-name" title="' + (img.filename || '') + '">' + name + '</span>' +
      '<span class="card-meta">' + size + ' • ' + date + '</span>' +
      '</div>' +
      '<button type="button" class="card-expand" aria-label="View full size"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 3h6v6M9 21H3v-6M21 3l-7 7M3 21l7-7"/></svg></button>' +
      '</div>';
    const inner = article.querySelector('.card-inner');
    const expandBtn = article.querySelector('.card-expand');
    const checkEl = article.querySelector('.card-select');
    const starBtn = article.querySelector('.card-star');
    if (starBtn && window.ImageKprLists) {
      starBtn.addEventListener('click', e => {
        e.stopPropagation();
        window.ImageKprLists.toggleInList(defList, img.id);
        starBtn.classList.toggle('in-list');
        starBtn.querySelector('svg').setAttribute('fill', window.ImageKprLists.isInList(defList, img.id) ? 'currentColor' : 'none');
      });
    }
    if (checkEl) {
      checkEl.addEventListener('click', e => e.stopPropagation());
      checkEl.addEventListener('change', () => {
        if (checkEl.checked) {
          selectedIds.add(img.id);
          selectedImages.set(img.id, { url: img.url, filename: img.filename });
        } else {
          selectedIds.delete(img.id);
          selectedImages.delete(img.id);
        }
        inner.classList.toggle('selected', checkEl.checked);
        updateBulkBar();
      });
      if (selectedIds.has(img.id)) {
        checkEl.checked = true;
        inner.classList.add('selected');
        selectedImages.set(img.id, { url: img.url, filename: img.filename });
      }
    }
    inner.addEventListener('click', (e) => {
      if (e.target.closest('.card-select') || e.target.closest('.card-star') || e.target.closest('.card-expand')) return;
      if (selectMode) {
        const c = article.querySelector('.card-select');
        if (c) { c.checked = !c.checked; c.dispatchEvent(new Event('change')); }
      } else {
        copyUrl(img.url);
      }
    });
    expandBtn.addEventListener('click', e => { e.stopPropagation(); openModal(img); });
    return article;
  }

  let currentModalImg = null;

  function renderTagPills(container, tags, onRemove) {
    container.innerHTML = '';
    (tags || []).forEach(tag => {
      const span = document.createElement('span');
      span.className = 'tag-pill';
      span.innerHTML = tag + ' <button type="button" aria-label="Remove ' + tag + '">&times;</button>';
      span.querySelector('button').addEventListener('click', () => onRemove && onRemove(tag));
      container.appendChild(span);
    });
  }

  function openModal(img) {
    currentModalImg = img;
    const modal = document.getElementById('modal');
    const imgEl = document.getElementById('modal-img');
    const pills = document.getElementById('modal-tag-pills');
    const input = document.getElementById('modal-tag-input');
    imgEl.src = img.url;
    imgEl.alt = img.filename;
    const tags = Array.isArray(img.tags) ? img.tags : (img.tags ? JSON.parse(img.tags || '[]') : []);
    const refreshPills = () => renderTagPills(pills, tags, (removed) => {
      const i = tags.indexOf(removed);
      if (i >= 0) tags.splice(i, 1);
      updateImageTags(img.id, tags);
      refreshPills();
    });
    refreshPills();
    input.value = '';
    input.onkeydown = (e) => {
      if (e.key === 'Enter') {
        const t = input.value.trim();
        if (t && !tags.includes(t)) {
          tags.push(t);
          updateImageTags(img.id, tags);
          refreshPills();
          input.value = '';
        }
      }
    };
    modal.hidden = false;
    document.body.style.overflow = 'hidden';
  }

  function updateImageTags(id, tags) {
    fetch(API_BASE + '/tags.php', {
      method: 'PATCH',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ id, tags })
    }).then(() => {
      if (currentModalImg && currentModalImg.id === id) currentModalImg.tags = tags;
    }).catch(() => showToast('Failed to update tags'));
  }

  function closeModal() {
    document.getElementById('modal').hidden = true;
    document.body.style.overflow = '';
  }

  let gridState = { page: 1, perPage: 50, sort: 'name_asc', search: '', total: 0, loading: false };

  function loadGrid(params, append) {
    const q = new URLSearchParams(params || {});
    fetchJSON(API_BASE + '/images.php?' + q).then(data => {
      const grid = document.getElementById('grid');
      const loadMore = document.getElementById('load-more');
      if (!append) grid.innerHTML = '';
      (data.images || []).forEach(img => {
        grid.appendChild(renderCard(img));
      });
      gridState.total = data.total || 0;
      gridState.page = data.page || 1;
      const imgs = grid.querySelectorAll('img[data-src]');
      if (typeof IntersectionObserver !== 'undefined') {
        const io = new IntersectionObserver((entries) => {
          entries.forEach(entry => {
            if (entry.isIntersecting) {
              const el = entry.target;
              el.src = el.dataset.src || '';
              el.removeAttribute('data-src');
              io.unobserve(el);
            }
          });
        }, { rootMargin: '100px' });
        imgs.forEach(el => io.observe(el));
      } else {
        imgs.forEach(el => { el.src = el.dataset.src || ''; el.removeAttribute('data-src'); });
      }
      const loaded = grid.querySelectorAll('.grid-item').length;
      if (gridState.total === 0 && !append) {
        grid.innerHTML = '<p class="empty">No images yet. Upload some!</p>';
      }
      if (append) return;
      if (gridState.total > 1000) {
        loadMore.innerHTML = '';
        const p = document.createElement('div');
        p.className = 'pagination';
        const prev = document.createElement('button');
        prev.textContent = 'Previous';
        prev.disabled = gridState.page <= 1;
        prev.onclick = () => { gridState.page--; refreshGrid(false); };
        const next = document.createElement('button');
        next.textContent = 'Next';
        next.disabled = loaded >= gridState.total;
        next.onclick = () => { gridState.page++; refreshGrid(false); };
        p.appendChild(prev);
        p.appendChild(document.createTextNode(' Page ' + gridState.page + ' of ' + Math.ceil(gridState.total / gridState.perPage) + ' '));
        p.appendChild(next);
        loadMore.appendChild(p);
      } else if (loaded < gridState.total && loaded < 1000) {
        loadMore.innerHTML = '<div class="loading">Scroll for more...</div>';
        loadMore.style.display = '';
      } else {
        loadMore.innerHTML = '';
        loadMore.style.display = 'none';
      }
    }).catch(() => {
      if (!append) document.getElementById('grid').innerHTML = '<p class="empty">No images yet. Upload some!</p>';
    });
  }

  const LATEST_FILTER = '__latest__';
  const LAST_BATCH_KEY = 'imagekpr_last_batch';

  function getLastBatchIds() {
    try {
      const s = localStorage.getItem(LAST_BATCH_KEY);
      const ids = s ? JSON.parse(s) : [];
      return Array.isArray(ids) ? ids : [];
    } catch (_) { return []; }
  }

  function setLastBatchIds(ids) {
    try { localStorage.setItem(LAST_BATCH_KEY, JSON.stringify(ids)); } catch (_) {}
  }

  function refreshGrid(append) {
    const filterInput = document.getElementById('list-filter');
    const filterVal = filterInput ? filterInput.value : '';
    if (filterVal === LATEST_FILTER) {
      const ids = getLastBatchIds();
      if (ids.length > 0) {
        loadGridFiltered(ids, append);
      } else {
        const grid = document.getElementById('grid');
        if (!append) grid.innerHTML = '<p class="empty">No last batch — upload to see your most recent batch here.</p>';
        document.getElementById('load-more').innerHTML = '';
      }
      return;
    }
    const ids = (window.ImageKprLists && window.ImageKprLists.getFilterIds && window.ImageKprLists.getFilterIds()) || null;
    if (ids && ids.length > 0) {
      loadGridFiltered(ids, append);
      return;
    }
    const p = { page: gridState.page, per_page: gridState.perPage, sort: gridState.sort };
    if (gridState.search) p.search = gridState.search;
    loadGrid(p, append);
  }

  function loadGridFiltered(ids, append) {
    fetchJSON(API_BASE + '/images.php?per_page=1000&sort=' + gridState.sort).then(data => {
      const filtered = (data.images || []).filter(img => ids.includes(Number(img.id)));
      const grid = document.getElementById('grid');
      if (!append) grid.innerHTML = '';
      filtered.forEach(img => grid.appendChild(renderCard(img)));
      gridState.total = filtered.length;
      document.getElementById('load-more').innerHTML = '';
      const imgs = grid.querySelectorAll('img[data-src]');
      if (typeof IntersectionObserver !== 'undefined') {
        const io = new IntersectionObserver((entries) => {
          entries.forEach(entry => {
            if (entry.isIntersecting) {
              const el = entry.target;
              el.src = el.dataset.src || '';
              el.removeAttribute('data-src');
              io.unobserve(el);
            }
          });
        }, { rootMargin: '100px' });
        imgs.forEach(el => io.observe(el));
      } else {
        imgs.forEach(el => { el.src = el.dataset.src || ''; el.removeAttribute('data-src'); });
      }
    }).catch(() => { if (!append) document.getElementById('grid').innerHTML = '<p class="empty">No images in this list.</p>'; });
  }

  function debounce(fn, ms) {
    let t;
    return function (...args) { clearTimeout(t); t = setTimeout(() => fn.apply(this, args), ms); };
  }

  const MAX_UPLOAD = 3 * 1024 * 1024;
  const MAX_WIDTH = 1920;

  function resizeIfNeeded(file) {
    return new Promise((resolve) => {
      if (file.size <= MAX_UPLOAD && !file.type.match(/image/)) {
        resolve(file);
        return;
      }
      const img = new Image();
      img.onload = () => {
        if (img.width <= MAX_WIDTH) {
          resolve(file);
          return;
        }
        const canvas = document.createElement('canvas');
        const r = MAX_WIDTH / img.width;
        canvas.width = MAX_WIDTH;
        canvas.height = Math.round(img.height * r);
        const ctx = canvas.getContext('2d');
        ctx.drawImage(img, 0, 0, canvas.width, canvas.height);
        canvas.toBlob(blob => {
          resolve(blob ? new File([blob], file.name, { type: file.type }) : file);
        }, file.type, 0.9);
      };
      img.onerror = () => resolve(file);
      img.src = URL.createObjectURL(file);
    });
  }

  function extractUploadedIds(d) {
    const ids = [];
    if (d.image && d.image.id) ids.push(Number(d.image.id));
    if (d.results && Array.isArray(d.results)) {
      d.results.forEach(r => { if (r.image && r.image.id) ids.push(Number(r.image.id)); });
    }
    return ids;
  }

  function uploadFiles(files, addToListName) {
    const zone = document.getElementById('upload-zone');
    const prog = document.getElementById('upload-progress');
    const text = document.getElementById('upload-text');
    const valid = Array.from(files).filter(f => f.size <= MAX_UPLOAD);
    const tooBig = Array.from(files).filter(f => f.size > MAX_UPLOAD);
    if (tooBig.length) showToast(tooBig.length + ' file(s) skipped (max 3MB)');
    if (valid.length === 0) return;
    text.hidden = true;
    prog.hidden = false;
    prog.innerHTML = '<div class="upload-progress-bar" id="upload-bar"></div>';
    const bar = document.getElementById('upload-bar');
    const fd = new FormData();
    valid.forEach((f, i) => fd.append('file[]', f, f.name));
    const xhr = new XMLHttpRequest();
    xhr.upload.onprogress = (e) => {
      if (e.lengthComputable) bar.style.width = (e.loaded / e.total * 100) + '%';
    };
    xhr.onload = () => {
      prog.hidden = true;
      text.hidden = false;
      try {
        const d = JSON.parse(xhr.responseText);
        if (d.success !== false) {
          showToast('Uploaded');
          const ids = extractUploadedIds(d);
          if (ids.length > 0) setLastBatchIds(ids);
          if (addToListName && ids.length > 0 && window.ImageKprLists) {
            window.ImageKprLists.addToList(addToListName, ids);
            if (window.ImageKprLists.onChange) window.ImageKprLists.onChange();
          }
          gridState.page = 1;
          refreshGrid(false);
          loadStats();
          setTimeout(loadStats, 300);
        } else showToast(d.error || 'Upload failed');
      } catch (_) { showToast('Upload failed'); }
    };
    xhr.onerror = () => {
      prog.hidden = true;
      text.hidden = false;
      showToast('Upload failed');
    };
    xhr.open('POST', API_BASE + '/upload.php');
    xhr.send(fd);
  }

  function processAndUpload(files, addToListName) {
    const arr = Array.from(files);
    Promise.all(arr.map(f => resizeIfNeeded(f))).then(resized => {
      uploadFiles(resized, addToListName);
    });
  }

  function showUploadConfirmModal(files) {
    const arr = Array.from(files).filter(f => f.type.startsWith('image/'));
    const tooBig = arr.filter(f => f.size > MAX_UPLOAD);
    if (tooBig.length) showToast(tooBig.length + ' file(s) skipped (max 3MB)');
    const valid = arr.filter(f => f.size <= MAX_UPLOAD);
    if (valid.length === 0) return;

    const dialog = document.getElementById('upload-confirm-dialog');
    const grid = document.getElementById('upload-confirm-grid');
    const countEl = document.getElementById('upload-confirm-count');
    const uploadBtn = document.getElementById('upload-confirm-upload');
    const cancelBtn = document.getElementById('upload-confirm-cancel');
    const listSelect = document.getElementById('upload-add-to-list-select');
    const listNewInput = document.getElementById('upload-add-to-list-new');

    const data = window.ImageKprLists ? window.ImageKprLists.load() : { 'Favourites': [] };
    listSelect.innerHTML = '<option value="">— None —</option>';
    Object.keys(data).sort().forEach(name => {
      const opt = document.createElement('option');
      opt.value = name;
      opt.textContent = name + ' (' + (data[name]?.length || 0) + ')';
      listSelect.appendChild(opt);
    });
    listNewInput.value = '';

    let pendingFiles = [...valid];
    let objectUrls = [];

    function getAddToListName() {
      const newName = listNewInput.value.trim();
      if (newName) return newName;
      const sel = listSelect.value;
      return sel || null;
    }

    function renderGrid() {
      objectUrls.forEach(u => URL.revokeObjectURL(u));
      objectUrls = [];
      grid.innerHTML = '';
      pendingFiles.forEach((file, i) => {
        const wrap = document.createElement('div');
        wrap.className = 'upload-confirm-thumb';
        wrap.dataset.index = String(i);
        const url = URL.createObjectURL(file);
        objectUrls.push(url);
        const im = document.createElement('img');
        im.src = url;
        im.alt = file.name;
        const removeBtn = document.createElement('button');
        removeBtn.type = 'button';
        removeBtn.className = 'upload-confirm-thumb-remove';
        removeBtn.setAttribute('aria-label', 'Remove ' + file.name);
        removeBtn.textContent = '×';
        removeBtn.addEventListener('click', () => {
          const idx = pendingFiles.indexOf(file);
          if (idx >= 0) {
            pendingFiles.splice(idx, 1);
            renderGrid();
            updateCount();
          }
        });
        wrap.appendChild(im);
        wrap.appendChild(removeBtn);
        grid.appendChild(wrap);
      });
    }

    function updateCount() {
      const n = pendingFiles.length;
      countEl.textContent = n + ' image' + (n === 1 ? '' : 's') + ' to upload';
      uploadBtn.disabled = n === 0;
    }

    let teardown = null;

    function closeModal() {
      dialog.hidden = true;
      document.body.style.overflow = '';
      objectUrls.forEach(u => URL.revokeObjectURL(u));
      uploadBtn.onclick = null;
      cancelBtn.onclick = null;
      if (teardown) { teardown(); teardown = null; }
    }

    renderGrid();
    updateCount();
    dialog.hidden = false;
    document.body.style.overflow = 'hidden';

    uploadBtn.onclick = () => {
      const addToListName = getAddToListName();
      closeModal();
      if (pendingFiles.length > 0) processAndUpload(pendingFiles, addToListName);
    };
    cancelBtn.onclick = closeModal;

    const onBackdrop = (e) => {
      if (e.target.id === 'upload-confirm-dialog') closeModal();
    };
    const onEscape = (e) => {
      if (e.key === 'Escape') closeModal();
    };
    dialog.addEventListener('click', onBackdrop);
    document.addEventListener('keydown', onEscape);
    teardown = () => {
      dialog.removeEventListener('click', onBackdrop);
      document.removeEventListener('keydown', onEscape);
    };
  }

  function loadInbox() {
    fetchJSON(API_BASE + '/inbox.php').then(data => {
      const count = data.count || 0;
      const box = document.getElementById('dashboard-inbox');
      if (count > 0) {
        box.hidden = false;
        document.getElementById('inbox-count').textContent = count;
      } else {
        box.hidden = true;
      }
    }).catch(() => {});
  }

  function importInbox() {
    fetch(API_BASE + '/inbox.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ import_all: true })
    }).then(r => r.json()).then(data => {
      if (data.success) {
        showToast('Imported ' + (data.imported || 0) + ' image(s)');
        loadInbox();
        loadStats();
        refreshGrid(false);
      }
    }).catch(() => showToast('Import failed'));
  }

  function loadStats() {
    loadInbox();
    fetchJSON(API_BASE + '/stats.php?t=' + Date.now()).then(data => {
      document.getElementById('stat-total-images').textContent = data.total_images;
      document.getElementById('stat-total-storage').textContent = data.total_storage_gb + ' GB';
    }).catch(() => {
      document.getElementById('stat-total-images').textContent = '—';
      document.getElementById('stat-total-storage').textContent = '—';
    });
  }

  const SORT_OPTIONS = [
    { value: 'date_desc', label: 'Date (newest)' },
    { value: 'date_asc', label: 'Date (oldest)' },
    { value: 'size_desc', label: 'Size (largest)' },
    { value: 'size_asc', label: 'Size (smallest)' },
    { value: 'name_asc', label: 'Name (A–Z)' },
    { value: 'name_desc', label: 'Name (Z–A)' },
    { value: 'random', label: 'Random' }
  ];

  function populateSortPills() {
    const container = document.getElementById('sort-pills');
    if (!container) return;
    container.innerHTML = '';
    SORT_OPTIONS.forEach(opt => {
      const pill = document.createElement('button');
      pill.type = 'button';
      pill.className = 'sort-pill' + (gridState.sort === opt.value ? ' active' : '');
      pill.textContent = opt.label;
      pill.addEventListener('click', () => {
        gridState.sort = opt.value;
        gridState.page = 1;
        refreshGrid(false);
        populateSortPills();
      });
      container.appendChild(pill);
    });
  }

  function populateListPills(activeValue) {
    const container = document.getElementById('list-pills');
    const filterInput = document.getElementById('list-filter');
    if (!container || !filterInput) return;
    const data = window.ImageKprLists ? window.ImageKprLists.load() : { 'Favourites': [] };
    const cur = activeValue !== undefined ? activeValue : filterInput.value;
    container.innerHTML = '';

    const allPill = document.createElement('button');
    allPill.type = 'button';
    allPill.className = 'list-pill' + (!cur ? ' active' : '');
    allPill.textContent = 'All';
    allPill.addEventListener('click', () => {
      filterInput.value = '';
      refreshGrid(false);
      populateListPills('');
    });
    container.appendChild(allPill);

    const latestPill = document.createElement('button');
    latestPill.type = 'button';
    latestPill.className = 'list-pill' + (cur === LATEST_FILTER ? ' active' : '');
    latestPill.textContent = 'Last uploaded';
    latestPill.title = 'Show only the last uploaded batch of images';
    latestPill.addEventListener('click', () => {
      filterInput.value = LATEST_FILTER;
      refreshGrid(false);
      populateListPills(LATEST_FILTER);
    });
    container.appendChild(latestPill);

    Object.keys(data).forEach(name => {
      const pill = document.createElement('button');
      pill.type = 'button';
      pill.className = 'list-pill' + (cur === name ? ' active' : '');
      pill.textContent = name + ' (' + (data[name]?.length || 0) + ')';
      pill.addEventListener('click', () => {
        filterInput.value = name;
        refreshGrid(false);
        populateListPills(name);
      });
      container.appendChild(pill);
    });
  }

  document.addEventListener('DOMContentLoaded', () => {
    populateListPills();
    populateSortPills();
    if (window.ImageKprLists) window.ImageKprLists.onChange = () => { populateListPills(); refreshGrid(false); };
    document.getElementById('manage-lists-btn').addEventListener('click', () => {
      const d = document.getElementById('manage-lists-dialog');
      const list = document.getElementById('manage-lists-list');
      const data = window.ImageKprLists ? window.ImageKprLists.load() : {};
      list.innerHTML = '';
      Object.entries(data).forEach(([name, ids]) => {
        const div = document.createElement('div');
        div.innerHTML = '<span>' + name + ' (' + ids.length + ')</span> <button data-name="' + name + '" class="manage-rename">Rename</button> <button data-name="' + name + '" class="manage-delete">Delete</button>';
        list.appendChild(div);
      });
      d.hidden = false;
    });
    document.getElementById('manage-close').addEventListener('click', () => { document.getElementById('manage-lists-dialog').hidden = true; });
    document.getElementById('manage-create-list').addEventListener('click', () => {
      const n = document.getElementById('new-list-name').value.trim();
      if (!n) return;
      const data = window.ImageKprLists ? window.ImageKprLists.load() : {};
      data[n] = [];
      window.ImageKprLists.save(data);
      populateListPills();
      document.getElementById('new-list-name').value = '';
      document.getElementById('manage-lists-dialog').hidden = true;
      showToast('List "' + n + '" created');
    });
    document.getElementById('manage-lists-list').addEventListener('click', e => {
      const name = e.target.dataset.name;
      if (!name) return;
      const data = window.ImageKprLists.load();
      if (e.target.classList.contains('manage-delete')) {
        delete data[name];
        window.ImageKprLists.save(data);
        populateListPills();
        const list = document.getElementById('manage-lists-list');
        list.innerHTML = '';
        Object.entries(data).forEach(([n, ids]) => {
          const div = document.createElement('div');
          div.innerHTML = '<span>' + n + ' (' + ids.length + ')</span> <button data-name="' + n + '" class="manage-rename">Rename</button> <button data-name="' + n + '" class="manage-delete">Delete</button>';
          list.appendChild(div);
        });
      }
    });
    document.getElementById('manage-export').addEventListener('click', () => {
      const a = document.createElement('a');
      a.href = 'data:application/json,' + encodeURIComponent(JSON.stringify(window.ImageKprLists.load(), null, 2));
      a.download = 'imagekpr-lists.json';
      a.click();
    });
    document.getElementById('manage-import').addEventListener('click', () => document.getElementById('manage-import-file').click());
    document.getElementById('manage-import-file').addEventListener('change', e => {
      const f = e.target.files[0];
      if (!f) return;
      const r = new FileReader();
      r.onload = () => {
        try {
          const imported = JSON.parse(r.result);
          const data = window.ImageKprLists.load();
          Object.assign(data, imported);
          window.ImageKprLists.save(data);
          populateListPills();
          showToast('Imported');
        } catch (_) { showToast('Invalid file'); }
      };
      r.readAsText(f);
      e.target.value = '';
    });
    window.ImageKprLists.addToList = (ids) => {
      addToListDialog('Favourites').then(name => {
        if (name) {
          const data = window.ImageKprLists.load();
          if (!data[name]) data[name] = [];
          ids.forEach(id => { if (!data[name].includes(id)) data[name].push(id); });
          window.ImageKprLists.save(data);
          showToast('Added');
          populateListPills();
        }
      });
    };

    loadStats();
    refreshGrid(false);

    const uploadZone = document.getElementById('upload-zone');
    const uploadInput = document.getElementById('upload-input');
    uploadZone.addEventListener('click', () => uploadInput.click());
    uploadInput.addEventListener('change', () => {
      if (uploadInput.files.length) showUploadConfirmModal(uploadInput.files);
      uploadInput.value = '';
    });
    uploadZone.addEventListener('dragover', (e) => { e.preventDefault(); uploadZone.classList.add('drag-over'); });
    uploadZone.addEventListener('dragleave', () => uploadZone.classList.remove('drag-over'));
    uploadZone.addEventListener('drop', (e) => {
      e.preventDefault();
      uploadZone.classList.remove('drag-over');
      const files = Array.from(e.dataTransfer.files).filter(f => f.type.startsWith('image/'));
      if (files.length) showUploadConfirmModal(files);
    });

    document.getElementById('search').addEventListener('input', debounce(() => {
      gridState.search = document.getElementById('search').value.trim();
      gridState.page = 1;
      refreshGrid(false);
    }, 500));

    const io = typeof IntersectionObserver !== 'undefined' ? new IntersectionObserver((entries) => {
      entries.forEach(e => {
        if (e.isIntersecting && !gridState.loading && gridState.total <= 1000) {
          const loaded = document.querySelectorAll('#grid .grid-item').length;
          if (loaded < gridState.total) {
            gridState.loading = true;
            gridState.page++;
            const p = { page: gridState.page, per_page: gridState.perPage, sort: gridState.sort };
            if (gridState.search) p.search = gridState.search;
            loadGrid(p, true);
            gridState.loading = false;
          }
        }
      });
    }, { rootMargin: '200px' }) : null;
    if (io) {
      const lm = document.getElementById('load-more');
      if (lm) io.observe(lm);
    }

    document.getElementById('modal-close').addEventListener('click', closeModal);
    document.getElementById('modal').addEventListener('click', e => {
      if (e.target.id === 'modal') closeModal();
    });
    document.getElementById('modal-copy').addEventListener('click', () => {
      const url = document.getElementById('modal-img').src;
      if (url) copyUrl(url);
    });
    document.getElementById('modal-download').addEventListener('click', () => {
      const img = document.getElementById('modal-img');
      const a = document.createElement('a');
      a.href = img.src;
      a.download = img.alt || 'image';
      a.click();
    });
    const selectBtn = document.getElementById('select-mode');
    selectBtn.addEventListener('click', () => {
      selectMode = !selectMode;
      selectBtn.classList.toggle('active', selectMode);
      selectBtn.setAttribute('aria-pressed', String(selectMode));
      selectBtn.textContent = selectMode ? 'Disable Selection Mode' : 'Enter Selection Mode';
      document.querySelectorAll('.card-select').forEach(el => { el.style.display = selectMode ? 'inline-block' : 'none'; });
      document.querySelectorAll('.card-inner.selected').forEach(el => el.classList.remove('selected'));
      if (!selectMode) { selectedIds.clear(); selectedImages.clear(); updateBulkBar(); }
      const hintEl = document.querySelector('.user-hint-text');
      if (hintEl) hintEl.textContent = selectMode ? 'Click to select' : 'Click card to copy URL • Click icon to view full size';
    });

    document.getElementById('bulk-clear').addEventListener('click', () => {
      selectedIds.clear();
      selectedImages.clear();
      document.querySelectorAll('.card-select:checked').forEach(c => { c.checked = false; });
      document.querySelectorAll('.card-inner.selected').forEach(el => el.classList.remove('selected'));
      updateBulkBar();
    });
    document.getElementById('bulk-delete').addEventListener('click', async () => {
      if (selectedIds.size === 0) return;
      if (!(await confirmDialog('Delete ' + selectedIds.size + ' image(s)?'))) return;
      fetch(API_BASE + '/delete_bulk.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ ids: Array.from(selectedIds) })
      }).then(r => r.json()).then(data => {
        if (data.success) {
          selectedIds.clear();
          selectedImages.clear();
          updateBulkBar();
          refreshGrid(false);
          loadStats();
          showToast('Deleted');
        }
      }).catch(() => showToast('Delete failed'));
    });
    document.getElementById('bulk-download').addEventListener('click', () => {
      if (selectedIds.size === 0) return;
      window.open(API_BASE + '/download_bulk.php?ids=' + Array.from(selectedIds).join(','), '_blank');
    });
    document.getElementById('bulk-rename').addEventListener('click', () => {
      if (selectedIds.size === 0) return;
      document.getElementById('rename-dialog').hidden = false;
      document.getElementById('rename-base').value = '';
    });
    document.getElementById('rename-cancel').addEventListener('click', () => {
      document.getElementById('rename-dialog').hidden = true;
    });
    document.getElementById('rename-confirm').addEventListener('click', () => {
      const base = document.getElementById('rename-base').value.trim() || 'image';
      fetch(API_BASE + '/rename_bulk.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ ids: Array.from(selectedIds), base })
      }).then(r => r.json()).then(data => {
        document.getElementById('rename-dialog').hidden = true;
        if (data.success) {
          selectedIds.clear();
          selectedImages.clear();
          updateBulkBar();
          refreshGrid(false);
          showToast('Renamed');
        }
      }).catch(() => showToast('Rename failed'));
    });
    document.getElementById('bulk-tags').addEventListener('click', () => {
      if (selectedIds.size === 0) return;
      const tag = prompt('Add tag to ' + selectedIds.size + ' image(s):');
      if (!tag || !tag.trim()) return;
      fetch(API_BASE + '/tags.php', {
        method: 'PATCH',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ ids: Array.from(selectedIds), action: 'add', tag: tag.trim() })
      }).then(r => r.json()).then(data => {
        if (data.success) { refreshGrid(false); showToast('Tags updated'); }
      }).catch(() => showToast('Failed'));
    });
    document.getElementById('bulk-add-list').addEventListener('click', () => {
      if (selectedIds.size === 0) return;
      if (window.ImageKprLists && window.ImageKprLists.addToList) {
        window.ImageKprLists.addToList(Array.from(selectedIds));
      } else {
        showToast('Manage lists first');
      }
    });

    document.getElementById('inbox-import-btn').addEventListener('click', importInbox);

    document.getElementById('scroll-to-top').addEventListener('click', () => {
      window.scrollTo({ top: 0, behavior: 'smooth' });
    });

    document.getElementById('modal-delete').addEventListener('click', async () => {
      if (!currentModalImg) return;
      if (!(await confirmDialog('Delete this image?'))) return;
      fetch(API_BASE + '/delete.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id: currentModalImg.id })
      }).then(r => r.json()).then(data => {
        if (data.success !== false) {
          closeModal();
          refreshGrid(false);
          loadStats();
          showToast('Deleted');
        } else showToast(data.error || 'Delete failed');
      }).catch(() => showToast('Delete failed'));
    });
  });

  window.ImageKpr = { loadGrid, loadStats, refreshGrid, copyUrl, showToast, openModal, closeModal };
})();
