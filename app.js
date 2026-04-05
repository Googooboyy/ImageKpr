/* ImageKpr - app logic */
(function () {
  'use strict';

  const API_BASE = 'api';

  function redirectToLogin() {
    window.location.href = 'login.php';
  }

  function fetchJSON(url) {
    return fetch(url, { credentials: 'same-origin' }).then(r => {
      const status = r.status;
      if (status === 401) {
        redirectToLogin();
        const e = new Error('Unauthorized');
        e.status = 401;
        throw e;
      }
      return r.text().then(text => {
        if (!r.ok) {
          const err = new Error(r.statusText || 'Request failed');
          err.status = status;
          throw err;
        }
        try {
          return JSON.parse(text);
        } catch (_) {
          const err = new Error('Invalid JSON response');
          err.status = status;
          throw err;
        }
      });
    });
  }

  function apiFetch(url, opts) {
    return fetch(url, Object.assign({ credentials: 'same-origin' }, opts)).then(r => {
      if (r.status === 401) {
        redirectToLogin();
        throw new Error('Unauthorized');
      }
      return r;
    });
  }

  function showToast(msg, prominent) {
    const el = document.getElementById('toast');
    el.textContent = msg;
    el.className = 'toast' + (prominent ? ' toast-prominent' : '');
    el.hidden = false;
    clearTimeout(showToast._t);
    showToast._t = setTimeout(() => { el.hidden = true; }, prominent ? 3500 : 2000);
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

  function addToFolderSelectDialog() {
    return new Promise((resolve) => {
      const d = document.getElementById('add-to-folder-select-dialog');
      const selectEl = document.getElementById('add-to-folder-select');
      const newInput = document.getElementById('add-to-folder-new');
      const okBtn = document.getElementById('add-to-folder-select-ok');
      const cancelBtn = document.getElementById('add-to-folder-select-cancel');
      const data = window.ImageKprFolders ? window.ImageKprFolders.load() : {};
      selectEl.innerHTML = '<option value="">— Select or type new —</option>';
      Object.keys(data).sort().forEach(name => {
        const opt = document.createElement('option');
        opt.value = name;
        opt.textContent = name + ' (' + (data[name]?.length || 0) + ')';
        selectEl.appendChild(opt);
      });
      newInput.value = '';
      d.hidden = false;
      document.body.style.overflow = 'hidden';
      newInput.focus();
      const cleanup = () => {
        d.hidden = true;
        document.body.style.overflow = '';
        okBtn.onclick = null;
        cancelBtn.onclick = null;
        newInput.onkeydown = null;
        d.onclick = null;
        document.removeEventListener('keydown', onEscape);
      };
      const getFolderName = () => {
        const v = newInput.value.trim();
        if (v) return v;
        return selectEl.value ? selectEl.value.trim() : null;
      };
      const submit = () => {
        const name = getFolderName();
        cleanup();
        resolve(name);
      };
      const cancel = () => {
        cleanup();
        resolve(null);
      };
      const onEscape = (e) => { if (e.key === 'Escape') cancel(); };
      newInput.onkeydown = (e) => { if (e.key === 'Enter') { e.preventDefault(); submit(); } };
      selectEl.addEventListener('change', () => {
        if (selectEl.value) newInput.value = '';
      });
      newInput.addEventListener('input', () => {
        if (newInput.value.trim()) selectEl.value = '';
      });
      okBtn.onclick = submit;
      cancelBtn.onclick = cancel;
      d.onclick = (e) => { if (e.target === d) cancel(); };
      document.addEventListener('keydown', onEscape);
    });
  }

  function addTagDialog(imageCount) {
    return new Promise((resolve) => {
      const d = document.getElementById('add-tag-dialog');
      const selectEl = document.getElementById('add-tag-select');
      const newInput = document.getElementById('add-tag-new');
      const okBtn = document.getElementById('add-tag-ok');
      const cancelBtn = document.getElementById('add-tag-cancel');
      const titleEl = document.getElementById('add-tag-title');
      if (titleEl) titleEl.textContent = 'Add tag to ' + imageCount + ' image(s)';
      selectEl.innerHTML = '<option value="">— Select or type new —</option>';
      newInput.value = '';
      fetchJSON(API_BASE + '/tags.php').then(data => {
        const tags = data.tags || [];
        tags.forEach(tag => {
          const opt = document.createElement('option');
          opt.value = tag;
          opt.textContent = tag;
          selectEl.appendChild(opt);
        });
      }).catch(() => {});
      d.hidden = false;
      document.body.style.overflow = 'hidden';
      newInput.focus();
      const cleanup = () => {
        d.hidden = true;
        document.body.style.overflow = '';
        okBtn.onclick = null;
        cancelBtn.onclick = null;
        newInput.onkeydown = null;
        d.onclick = null;
        document.removeEventListener('keydown', onEscape);
      };
      const getTag = () => {
        const v = newInput.value.trim();
        if (v) return v;
        return selectEl.value ? selectEl.value.trim() : null;
      };
      const submit = () => {
        const tag = getTag();
        cleanup();
        resolve(tag);
      };
      const cancel = () => {
        cleanup();
        resolve(null);
      };
      const onEscape = (e) => { if (e.key === 'Escape') cancel(); };
      newInput.onkeydown = (e) => { if (e.key === 'Enter') { e.preventDefault(); submit(); } };
      selectEl.addEventListener('change', () => {
        if (selectEl.value) newInput.value = '';
      });
      newInput.addEventListener('input', () => {
        if (newInput.value.trim()) selectEl.value = '';
      });
      okBtn.onclick = submit;
      cancelBtn.onclick = cancel;
      d.onclick = (e) => { if (e.target === d) cancel(); };
      document.addEventListener('keydown', onEscape);
    });
  }

  function addToFolderDialog(defaultValue) {
    return new Promise((resolve) => {
      const d = document.getElementById('add-to-folder-dialog');
      const input = document.getElementById('add-to-folder-input');
      const okBtn = document.getElementById('add-to-folder-ok');
      const cancelBtn = document.getElementById('add-to-folder-cancel');
      input.value = defaultValue || '';
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

  function copyUrl(url, prominent) {
    if (!url) return;
    try {
      const absolute = new URL(url, window.location.origin).href;
      navigator.clipboard.writeText(absolute).then(() => showToast(prominent ? 'URL copied to clipboard!' : 'Copied!', prominent)).catch(() => showToast('Copy failed', prominent));
    } catch {
      navigator.clipboard.writeText(url).then(() => showToast(prominent ? 'URL copied to clipboard!' : 'Copied!', prominent)).catch(() => showToast('Copy failed', prominent));
    }
  }

  function absoluteImageUrl(url) {
    if (!url) return '';
    try {
      return new URL(url, window.location.href).href;
    } catch {
      return url;
    }
  }

  function clipboardMimeForImageBlob(blob, urlHint) {
    let t = (blob.type || '').toLowerCase();
    if (t === 'image/jpg') t = 'image/jpeg';
    if (/^image\/(png|jpeg|gif|webp)$/.test(t)) return t;
    const u = (urlHint || '').toLowerCase();
    if (u.endsWith('.png')) return 'image/png';
    if (u.endsWith('.jpg') || u.endsWith('.jpeg')) return 'image/jpeg';
    if (u.endsWith('.gif')) return 'image/gif';
    if (u.endsWith('.webp')) return 'image/webp';
    return 'image/png';
  }

  function copyImageFromModalImgElement(imgEl) {
    if (!imgEl || !imgEl.complete || !imgEl.naturalWidth) {
      return Promise.reject(new Error('image not ready'));
    }
    const c = document.createElement('canvas');
    c.width = imgEl.naturalWidth;
    c.height = imgEl.naturalHeight;
    c.getContext('2d').drawImage(imgEl, 0, 0);
    return new Promise((resolve, reject) => {
      c.toBlob((blob) => {
        if (!blob) {
          reject(new Error('toBlob failed'));
          return;
        }
        navigator.clipboard.write([new ClipboardItem({ 'image/png': blob })]).then(resolve).catch(reject);
      }, 'image/png');
    });
  }

  /** Copy raster image to clipboard; optional in unsupported browsers (button hidden on init). */
  function copyImageToClipboard(url, prominent) {
    if (!url || !navigator.clipboard?.write || !window.ClipboardItem) {
      showToast('Copy image is not supported in this browser', prominent);
      return;
    }
    const abs = absoluteImageUrl(url);
    let sameOrigin = true;
    try {
      sameOrigin = new URL(abs).origin === window.location.origin;
    } catch (_) {
      sameOrigin = false;
    }
    const imgEl = document.getElementById('modal-img');
    const tryClipboard = (blob) => {
      const mime = clipboardMimeForImageBlob(blob, abs);
      return navigator.clipboard.write([new ClipboardItem({ [mime]: blob })]);
    };
    fetch(abs, { credentials: sameOrigin ? 'same-origin' : 'omit', mode: 'cors' })
      .then((r) => {
        if (!r.ok) throw new Error('fetch failed');
        return r.blob();
      })
      .then((blob) => tryClipboard(blob))
      .then(() => showToast(prominent ? 'Image copied to clipboard!' : 'Copied!', prominent))
      .catch(() => {
        copyImageFromModalImgElement(imgEl)
          .then(() => showToast(prominent ? 'Image copied to clipboard!' : 'Copied!', prominent))
          .catch(() => showToast('Copy image failed — try Download instead', prominent));
      });
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

  function escapeHtml(s) {
    if (s == null) return '';
    const t = String(s);
    return t.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#39;');
  }

  let selectedIds = new Set();
  let selectedImages = new Map();
  function getSelectMode() { return selectedIds.size > 0; }

  function updateBulkBar() {
    const selectMode = getSelectMode();
    const count = document.getElementById('bulk-count');
    const banner = document.getElementById('selection-banner');
    const hintRow = document.getElementById('hint-banner-row');
    count.textContent = selectedIds.size + ' selected';
    updateSelectionBanner();
    if (selectMode) {
      if (banner) banner.hidden = false;
      if (hintRow) hintRow.hidden = true;
      document.body.classList.add('selection-active');
    } else {
      if (banner) banner.hidden = true;
      if (hintRow) hintRow.hidden = false;
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

  function parseImageTags(img) {
    const raw = img && img.tags;
    if (Array.isArray(raw)) return raw;
    if (raw == null || raw === '') return [];
    if (typeof raw === 'string') {
      try {
        const t = JSON.parse(raw);
        return Array.isArray(t) ? t : [];
      } catch (_) {
        return [];
      }
    }
    return [];
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
    const tags = parseImageTags(img);
    const tagsHtml = tags.length > 0
      ? '<div class="card-tags">' + tags.map(t => '<span class="card-tag" title="' + escapeHtml(t || '') + '">' + escapeHtml(t || '') + '</span>').join('') + '</div>'
      : '';
    const cb = '<input type="checkbox" class="card-select" data-id="' + img.id + '">';
    article.innerHTML =
      '<div class="card-inner">' + cb +
      '<img class="card-img" data-src="' + (img.url || '') + '" alt="' + (img.filename || 'Image') + '" loading="lazy">' +
      '<div class="card-info">' +
      '<span class="card-name" title="' + (img.filename || '') + '">' + name + '</span>' +
      '<span class="card-meta">' + size + ' • ' + date + '</span>' +
      tagsHtml +
      '</div>' +
      '<button type="button" class="card-expand card-copy-url" aria-label="Copy URL"><img class="copy-url-icon" src="assets/copyurl-passive.png" alt=""></button>' +
      '</div>';
    const inner = article.querySelector('.card-inner');
    const expandBtn = article.querySelector('.card-expand');
    const checkEl = article.querySelector('.card-select');
    if (checkEl) {
      checkEl.addEventListener('click', e => e.stopPropagation());
      checkEl.addEventListener('change', () => {
        const id = Number(img.id);
        if (checkEl.checked) {
          selectedIds.add(id);
          selectedImages.set(id, { url: img.url, filename: img.filename });
        } else {
          selectedIds.delete(id);
          selectedImages.delete(id);
        }
        inner.classList.toggle('selected', checkEl.checked);
        updateBulkBar();
      });
      if (selectedIds.has(Number(img.id))) {
        checkEl.checked = true;
        inner.classList.add('selected');
        selectedImages.set(Number(img.id), { url: img.url, filename: img.filename });
      }
    }
    inner.addEventListener('click', (e) => {
      if (e.target.closest('.card-select') || e.target.closest('.card-expand')) return;
      if (getSelectMode()) {
        const c = article.querySelector('.card-select');
        if (c) { c.checked = !c.checked; c.dispatchEvent(new Event('change')); }
      } else {
        openModal(img);
      }
    });
    expandBtn.addEventListener('click', e => {
      e.stopPropagation();
      copyUrl(img.url, true);
      const icon = expandBtn.querySelector('.copy-url-icon');
      if (icon) {
        icon.src = 'assets/copyurl-active.png';
        setTimeout(() => { icon.src = 'assets/copyurl-passive.png'; }, 2000);
      }
    });
    article._imageKprImg = img;
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
    const content = document.getElementById('modal-content');
    const filenameInput = document.getElementById('modal-filename');
    imgEl.src = img.url;
    imgEl.alt = img.filename;
    if (filenameInput) filenameInput.value = img.filename || '';
    imgEl.onload = () => {
      const w = imgEl.naturalWidth || imgEl.width;
      const h = imgEl.naturalHeight || imgEl.height;
      content.classList.toggle('modal-content-wide', w > h);
      content.classList.toggle('modal-content-tall', h >= w);
    };
    if (imgEl.complete) imgEl.onload();
    modal.hidden = false;
    document.body.style.overflow = 'hidden';
    updateModalNavUi();
  }

  function getOrderedVisibleImages() {
    const grid = document.getElementById('grid');
    if (!grid) return [];
    return Array.from(grid.querySelectorAll('.grid-item.card'))
      .map(el => el._imageKprImg)
      .filter(Boolean);
  }

  function navigateModal(delta) {
    const modal = document.getElementById('modal');
    if (!modal || modal.hidden || !currentModalImg) return false;
    const list = getOrderedVisibleImages();
    const id = Number(currentModalImg.id);
    const i = list.findIndex(img => Number(img.id) === id);
    if (i < 0) return false;
    const next = list[i + delta];
    if (!next) return false;
    openModal(next);
    return true;
  }

  function updateModalNavUi() {
    const prevBtn = document.getElementById('modal-prev');
    const nextBtn = document.getElementById('modal-next');
    if (!prevBtn || !nextBtn) return;
    const modal = document.getElementById('modal');
    if (!modal || modal.hidden || !currentModalImg) {
      prevBtn.hidden = true;
      nextBtn.hidden = true;
      return;
    }
    const list = getOrderedVisibleImages();
    const id = Number(currentModalImg.id);
    const i = list.findIndex(img => Number(img.id) === id);
    if (list.length <= 1 || i < 0) {
      prevBtn.hidden = true;
      nextBtn.hidden = true;
      return;
    }
    prevBtn.hidden = false;
    nextBtn.hidden = false;
    prevBtn.disabled = i === 0;
    nextBtn.disabled = i === list.length - 1;
  }

  function openManageTagsImageDialog() {
    if (!currentModalImg) return;
    const img = currentModalImg;
    const tags = [...parseImageTags(img)];
    const d = document.getElementById('manage-tags-image-dialog');
    const pills = document.getElementById('manage-tags-image-pills');
    const selectEl = document.getElementById('manage-tags-image-select');
    const newInput = document.getElementById('manage-tags-image-new');
    const addBtn = document.getElementById('manage-tags-image-add');
    const closeBtn = document.getElementById('manage-tags-image-close');

    function refresh() {
      renderTagPills(pills, tags, (removed) => {
        const i = tags.indexOf(removed);
        if (i >= 0) tags.splice(i, 1);
        updateImageTags(img.id, tags);
        refresh();
      });
    }
    function addTag() {
      const t = newInput.value.trim() || (selectEl.value ? selectEl.value.trim() : '');
      if (t && !tags.includes(t)) {
        tags.push(t);
        updateImageTags(img.id, tags);
        refresh();
        newInput.value = '';
        selectEl.value = '';
      }
    }

    selectEl.innerHTML = '<option value="">— Select or type new —</option>';
    fetchJSON(API_BASE + '/tags.php').then(data => {
      (data.tags || []).forEach(tag => {
        const opt = document.createElement('option');
        opt.value = tag;
        opt.textContent = tag;
        selectEl.appendChild(opt);
      });
    }).catch(() => {});
    newInput.value = '';
    refresh();
    d.hidden = false;
    document.body.style.overflow = 'hidden';
    const doCleanup = () => {
      d.hidden = true;
      document.body.style.overflow = '';
      addBtn.onclick = null;
      closeBtn.onclick = null;
      d.onclick = null;
      document.removeEventListener('keydown', onEscape);
    };
    const onEscape = (e) => { if (e.key === 'Escape') doCleanup(); };
    addBtn.onclick = addTag;
    closeBtn.onclick = doCleanup;
    newInput.onkeydown = (e) => { if (e.key === 'Enter') { e.preventDefault(); addTag(); } };
    d.onclick = (e) => { if (e.target === d) doCleanup(); };
    document.addEventListener('keydown', onEscape);
  }

  function openManageFoldersImageDialog() {
    if (!currentModalImg) return;
    const img = currentModalImg;
    const imgId = Number(img.id);
    const d = document.getElementById('manage-folders-image-dialog');
    const pills = document.getElementById('manage-folders-image-pills');
    const selectEl = document.getElementById('manage-folders-image-select');
    const newInput = document.getElementById('manage-folders-image-new');
    const addBtn = document.getElementById('manage-folders-image-add');
    const closeBtn = document.getElementById('manage-folders-image-close');

    function refresh() {
      const data = window.ImageKprFolders ? window.ImageKprFolders.load() : {};
      const folders = Object.entries(data).filter(([, ids]) => (ids || []).some(id => Number(id) === imgId)).map(([name]) => name);
      pills.innerHTML = '';
      folders.forEach(name => {
        const span = document.createElement('span');
        span.className = 'folder-pill';
        span.innerHTML = escapeHtml(name) + ' <button type="button" aria-label="Remove from ' + escapeHtml(name) + '">&times;</button>';
        span.querySelector('button').addEventListener('click', () => {
          window.ImageKprFolders && window.ImageKprFolders.removeFromFolder(name, imgId);
          if (window.ImageKprFolders && window.ImageKprFolders.onChange) window.ImageKprFolders.onChange();
          showToast('Removed from ' + name);
          refresh();
        });
        pills.appendChild(span);
      });
    }
    function addToFolder() {
      const name = newInput.value.trim() || (selectEl.value ? selectEl.value.trim() : '');
      if (name && window.ImageKprFolders) {
        window.ImageKprFolders.addToFolder(name, [imgId]);
        if (window.ImageKprFolders.onChange) window.ImageKprFolders.onChange();
        showToast('Added to ' + name);
        refresh();
        selectEl.innerHTML = '<option value="">— Select or type new —</option>';
        const data = window.ImageKprFolders.load();
        Object.keys(data).sort().forEach(n => {
          const opt = document.createElement('option');
          opt.value = n;
          opt.textContent = n + ' (' + (data[n]?.length || 0) + ')';
          selectEl.appendChild(opt);
        });
        newInput.value = '';
        selectEl.value = '';
      }
    }

    const data = window.ImageKprFolders ? window.ImageKprFolders.load() : {};
    selectEl.innerHTML = '<option value="">— Select or type new —</option>';
    Object.keys(data).sort().forEach(name => {
      const opt = document.createElement('option');
      opt.value = name;
      opt.textContent = name + ' (' + (data[name]?.length || 0) + ')';
      selectEl.appendChild(opt);
    });
    newInput.value = '';
    refresh();
    d.hidden = false;
    document.body.style.overflow = 'hidden';
    const onEscape = (e) => { if (e.key === 'Escape') doCleanup(); };
    const doCleanup = () => {
      d.hidden = true;
      document.body.style.overflow = '';
      addBtn.onclick = null;
      closeBtn.onclick = null;
      d.onclick = null;
      document.removeEventListener('keydown', onEscape);
    };
    addBtn.onclick = addToFolder;
    closeBtn.onclick = doCleanup;
    newInput.onkeydown = (e) => { if (e.key === 'Enter') { e.preventDefault(); addToFolder(); } };
    d.onclick = (e) => { if (e.target === d) doCleanup(); };
    document.addEventListener('keydown', onEscape);
  }

  function openBulkManageTagsDialog() {
    const ids = Array.from(selectedIds);
    if (ids.length === 0) return;
    const d = document.getElementById('bulk-manage-tags-dialog');
    const addSelect = document.getElementById('bulk-tags-add-select');
    const addNew = document.getElementById('bulk-tags-add-new');
    const addBtn = document.getElementById('bulk-tags-add-btn');
    const removeSelect = document.getElementById('bulk-tags-remove-select');
    const removeBtn = document.getElementById('bulk-tags-remove-btn');
    const closeBtn = document.getElementById('bulk-tags-dialog-close');

    function refreshRemoveSelect() {
      fetchJSON(API_BASE + '/tags.php').then(data => {
        const tags = data.tags || [];
        removeSelect.innerHTML = '<option value="">— Select tag —</option>';
        tags.forEach(tag => {
          const opt = document.createElement('option');
          opt.value = tag;
          opt.textContent = tag;
          removeSelect.appendChild(opt);
        });
      }).catch(() => {});
    }
    function doAdd() {
      const tag = addNew.value.trim() || (addSelect.value ? addSelect.value.trim() : '');
      if (!tag) return;
      apiFetch(API_BASE + '/tags.php', {
        method: 'PATCH',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ ids, action: 'add', tag })
      }).then(r => r.json()).then(data => {
        if (data.success) {
          showToast('Tag added');
          addNew.value = '';
          addSelect.value = '';
          refreshRemoveSelect();
          populateTagsRow();
          refreshGrid(false);
        }
      }).catch(() => showToast('Failed'));
    }
    function doRemove() {
      const tag = removeSelect.value ? removeSelect.value.trim() : '';
      if (!tag) return;
      apiFetch(API_BASE + '/tags.php', {
        method: 'PATCH',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ ids, action: 'remove', tag })
      }).then(r => r.json()).then(data => {
        if (data.success) {
          showToast('Tag removed');
          refreshRemoveSelect();
          populateTagsRow();
          refreshGrid(false);
        }
      }).catch(() => showToast('Failed'));
    }

    addSelect.innerHTML = '<option value="">— Select or type new —</option>';
    fetchJSON(API_BASE + '/tags.php').then(data => {
      (data.tags || []).forEach(tag => {
        const opt = document.createElement('option');
        opt.value = tag;
        opt.textContent = tag;
        addSelect.appendChild(opt);
      });
    }).catch(() => {});
    addNew.value = '';
    refreshRemoveSelect();
    d.hidden = false;
    document.body.style.overflow = 'hidden';
    const doCleanup = () => {
      d.hidden = true;
      document.body.style.overflow = '';
      addBtn.onclick = null;
      removeBtn.onclick = null;
      closeBtn.onclick = null;
      d.onclick = null;
      document.removeEventListener('keydown', onEscape);
    };
    const onEscape = (e) => { if (e.key === 'Escape') doCleanup(); };
    addBtn.onclick = doAdd;
    removeBtn.onclick = doRemove;
    closeBtn.onclick = doCleanup;
    addNew.onkeydown = (e) => { if (e.key === 'Enter') { e.preventDefault(); doAdd(); } };
    d.onclick = (e) => { if (e.target === d) doCleanup(); };
    document.addEventListener('keydown', onEscape);
  }

  function openBulkManageFoldersDialog() {
    const ids = Array.from(selectedIds);
    if (ids.length === 0) return;
    if (!window.ImageKprFolders) return;
    const d = document.getElementById('bulk-manage-folders-dialog');
    const addSelect = document.getElementById('bulk-folders-add-select');
    const addNew = document.getElementById('bulk-folders-add-new');
    const addBtn = document.getElementById('bulk-folders-add-btn');
    const removeSelect = document.getElementById('bulk-folders-remove-select');
    const removeBtn = document.getElementById('bulk-folders-remove-btn');
    const closeBtn = document.getElementById('bulk-folders-dialog-close');

    function refreshSelects() {
      const data = window.ImageKprFolders.load();
      addSelect.innerHTML = '<option value="">— Select or type new —</option>';
      removeSelect.innerHTML = '<option value="">— Select folder —</option>';
      Object.keys(data).sort().forEach(name => {
        const optAdd = document.createElement('option');
        optAdd.value = name;
        optAdd.textContent = name + ' (' + (data[name]?.length || 0) + ')';
        addSelect.appendChild(optAdd);
        const optRem = document.createElement('option');
        optRem.value = name;
        optRem.textContent = name + ' (' + (data[name]?.length || 0) + ')';
        removeSelect.appendChild(optRem);
      });
    }
    function doAdd() {
      const name = addNew.value.trim() || (addSelect.value ? addSelect.value.trim() : '');
      if (!name) return;
      window.ImageKprFolders.addToFolder(name, ids);
      if (window.ImageKprFolders.onChange) window.ImageKprFolders.onChange();
      showToast('Added to ' + name);
      addNew.value = '';
      addSelect.value = '';
      refreshSelects();
      refreshGrid(false);
    }
    function doRemove() {
      const name = removeSelect.value ? removeSelect.value.trim() : '';
      if (!name) return;
      ids.forEach(id => window.ImageKprFolders.removeFromFolder(name, id));
      if (window.ImageKprFolders.onChange) window.ImageKprFolders.onChange();
      showToast('Removed from ' + name);
      refreshSelects();
      refreshGrid(false);
    }

    refreshSelects();
    addNew.value = '';
    d.hidden = false;
    document.body.style.overflow = 'hidden';
    const doCleanup = () => {
      d.hidden = true;
      document.body.style.overflow = '';
      addBtn.onclick = null;
      removeBtn.onclick = null;
      closeBtn.onclick = null;
      d.onclick = null;
      document.removeEventListener('keydown', onEscape);
    };
    const onEscape = (e) => { if (e.key === 'Escape') doCleanup(); };
    addBtn.onclick = doAdd;
    removeBtn.onclick = doRemove;
    closeBtn.onclick = doCleanup;
    addNew.onkeydown = (e) => { if (e.key === 'Enter') { e.preventDefault(); doAdd(); } };
    d.onclick = (e) => { if (e.target === d) doCleanup(); };
    document.addEventListener('keydown', onEscape);
  }

  function updateImageTags(id, tags) {
    apiFetch(API_BASE + '/tags.php', {
      method: 'PATCH',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ id, tags })
    }).then(() => {
      if (currentModalImg && currentModalImg.id === id) currentModalImg.tags = tags;
      populateTagsRow();
      const card = document.querySelector('.grid-item.card[data-id="' + id + '"]');
      if (card) {
        let container = card.querySelector('.card-tags');
        const tagsHtml = tags.map(t => '<span class="card-tag" title="' + escapeHtml(t || '') + '">' + escapeHtml(t || '') + '</span>').join('');
        if (container) {
          container.innerHTML = tagsHtml;
        } else if (tags.length > 0) {
          const info = card.querySelector('.card-info');
          if (info) {
            const div = document.createElement('div');
            div.className = 'card-tags';
            div.innerHTML = tagsHtml;
            info.appendChild(div);
          }
        }
      }
    }).catch(() => showToast('Failed to update tags'));
  }

  function closeModal() {
    document.getElementById('modal').hidden = true;
    document.body.style.overflow = '';
    updateModalNavUi();
  }

  let gridState = { page: 1, perPage: 50, sort: 'date_desc', search: '', tagFilter: '', total: 0, loading: false };

  function showApiImagesError(err, append) {
    if (append) {
      showToast('Could not load more images.', true);
      return;
    }
    if (err && err.status === 401) return;
    const st = err && err.status;
    const hint = st ? ' (HTTP ' + st + ')' : '';
    gridState.total = 0;
    const loadMore = document.getElementById('load-more');
    if (loadMore) {
      loadMore.innerHTML = '';
      loadMore.style.display = 'none';
    }
    const grid = document.getElementById('grid');
    if (grid) {
      grid.innerHTML =
        '<p class="empty grid-load-error"><strong>Could not load images' + escapeHtml(hint) + '.</strong><br>' +
        'The server may be returning an error. Check <code>api/images.php</code> or your host PHP error log. ' +
        'If you expect images here, confirm <code>config.php</code> uses the same database you checked in phpMyAdmin.</p>';
    }
    showToast('Could not load images' + hint, true);
  }

  function loadGrid(params, append) {
    const q = new URLSearchParams(params || {});
    fetchJSON(API_BASE + '/images.php?' + q).then(data => {
      const grid = document.getElementById('grid');
      const loadMore = document.getElementById('load-more');
      const total = data.total || 0;
      const rows = data.images || [];
      if (!append && total > 0 && rows.length === 0) {
        grid.innerHTML =
          '<p class="empty grid-load-error"><strong>Library reports ' + escapeHtml(String(total)) + ' image(s) but none were returned.</strong><br>' +
          'This is often a MySQL/PDO issue with paginated queries. Deploy the latest <code>api/images.php</code> from the repo, or ask your host to enable PDO emulated prepares.</p>';
        gridState.total = total;
        showToast('Images missing from response — check api/images.php (LIMIT/OFFSET fix).', true);
        if (loadMore) { loadMore.innerHTML = ''; loadMore.style.display = 'none'; }
        updateHintBanner();
        return;
      }
      if (!append) grid.innerHTML = '';
      rows.forEach(img => {
        grid.appendChild(renderCard(img));
      });
      gridState.total = total;
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
        grid.innerHTML =
          '<p class="empty">No images yet. Upload some!</p>' +
          '<p class="empty" style="font-size:0.85rem;max-width:36rem;margin-left:auto;margin-right:auto;line-height:1.45">' +
          'If the database already has rows, open <a href="' + API_BASE + '/whoami.php" target="_blank" rel="noopener">api/whoami.php</a> ' +
          'and confirm <code>user_id</code> matches <code>images.user_id</code>. In DevTools → Network, <code>images.php</code> responses include ' +
          'header <code>X-ImageKpr-User-Id</code>. Or set <code>IMAGEKPR_SHARE_NULL_USER_ROWS</code> in <code>config.php</code> temporarily if rows are still NULL.</p>';
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
      updateHintBanner();
    }).catch((err) => {
      showApiImagesError(err, append);
      updateHintBanner();
    });
  }

  const LATEST_FILTER = '__latest__';
  const UNCATEGORIZED_FILTER = '__uncategorized__';
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
    const filterInput = document.getElementById('folder-filter');
    const filterVal = filterInput ? filterInput.value : '';
    if (filterVal === LATEST_FILTER) {
      const ids = getLastBatchIds();
      if (ids.length > 0) {
        loadGridFiltered(ids, append);
      } else {
        const grid = document.getElementById('grid');
        if (!append) grid.innerHTML = '<p class="empty">No last batch — upload to see your most recent batch here.</p>';
        document.getElementById('load-more').innerHTML = '';
        gridState.total = 0;
        updateHintBanner();
      }
      return;
    }
    if (filterVal === UNCATEGORIZED_FILTER) {
      loadUncategorizedGrid(append);
      return;
    }
    const ids = (window.ImageKprFolders && window.ImageKprFolders.getFilterIds && window.ImageKprFolders.getFilterIds()) || null;
    if (ids && ids.length > 0) {
      loadGridFiltered(ids, append);
      return;
    }
    const p = { page: gridState.page, per_page: gridState.perPage, sort: gridState.sort };
    if (gridState.search) p.search = gridState.search;
    if (gridState.tagFilter) p.tag = gridState.tagFilter;
    loadGrid(p, append);
  }

  function loadGridFiltered(ids, append) {
    let url = API_BASE + '/images.php?per_page=5000&sort=' + gridState.sort;
    if (gridState.tagFilter) url += '&tag=' + encodeURIComponent(gridState.tagFilter);
    if (gridState.search) url += '&search=' + encodeURIComponent(gridState.search);
    fetchJSON(url).then(data => {
      let filtered = (data.images || []).filter(img => ids.includes(Number(img.id)));
      if (!append && filtered.length === 0 && (data.total || 0) > 0 && ids.length > 0) {
        showToast('This folder’s saved IDs do not match your library (often after DB changes). Showing all images.', true);
        const fi = document.getElementById('folder-filter');
        if (fi) fi.value = '';
        populateFolderIcons('');
        refreshGrid(false);
        return;
      }
      const grid = document.getElementById('grid');
      if (!append) grid.innerHTML = '';
      filtered.forEach(img => grid.appendChild(renderCard(img)));
      gridState.total = filtered.length;
      if (!append && filtered.length === 0) {
        grid.innerHTML =
          '<p class="empty">No images in this folder view.</p>' +
          '<p class="empty" style="font-size:0.85rem;max-width:36rem;margin-left:auto;margin-right:auto">Click <strong>All</strong> above, or open ' +
          '<a href="' + API_BASE + '/whoami.php" target="_blank" rel="noopener">api/whoami.php</a> to verify your account.</p>';
      }
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
      updateHintBanner();
    }).catch((err) => {
      showApiImagesError(err, append);
      updateHintBanner();
    });
  }

  function loadUncategorizedGrid(append) {
    const data = window.ImageKprFolders ? window.ImageKprFolders.load() : {};
    const idsInFolders = new Set();
    Object.values(data).forEach(arr => {
      (arr || []).forEach(id => idsInFolders.add(Number(id)));
    });
    let url = API_BASE + '/images.php?per_page=5000&sort=' + gridState.sort;
    if (gridState.tagFilter) url += '&tag=' + encodeURIComponent(gridState.tagFilter);
    if (gridState.search) url += '&search=' + encodeURIComponent(gridState.search);
    fetchJSON(url).then(data => {
      const filtered = (data.images || []).filter(img => !idsInFolders.has(Number(img.id)));
      const grid = document.getElementById('grid');
      if (!append) grid.innerHTML = '';
      filtered.forEach(img => grid.appendChild(renderCard(img)));
      gridState.total = filtered.length;
      document.getElementById('load-more').innerHTML = '';
      if (!append && filtered.length === 0) {
        grid.innerHTML = '<p class="empty">No uncategorized images. All images are in at least one folder.</p>';
      }
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
      updateHintBanner();
    }).catch((err) => {
      showApiImagesError(err, append);
      updateHintBanner();
    });
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

  function uploadFiles(items, addToFolderName) {
    const prog = document.getElementById('upload-progress');
    const text = document.getElementById('upload-text');
    const validItems = items.filter(it => it.file.size <= MAX_UPLOAD);
    const tooBig = items.filter(it => it.file.size > MAX_UPLOAD);
    if (tooBig.length) showToast(tooBig.length + ' file(s) skipped (max 3MB)');
    if (validItems.length === 0) return;
    text.hidden = true;
    prog.hidden = false;
    prog.innerHTML = '<div class="upload-progress-bar" id="upload-bar"></div>';
    const bar = document.getElementById('upload-bar');
    const fd = new FormData();
    validItems.forEach((it, i) => fd.append('file[]', it.file, it.newName || it.file.name));
    const xhr = new XMLHttpRequest();
    xhr.withCredentials = true;
    xhr.upload.onprogress = (e) => {
      if (e.lengthComputable) bar.style.width = (e.loaded / e.total * 100) + '%';
    };
    xhr.onload = () => {
      prog.hidden = true;
      text.hidden = false;
      if (xhr.status === 401) {
        redirectToLogin();
        return;
      }
      try {
        const d = JSON.parse(xhr.responseText);
        if (d.success !== false) {
          showToast('Uploaded');
          const ids = extractUploadedIds(d);
          if (ids.length > 0) setLastBatchIds(ids);
          if (addToFolderName && ids.length > 0 && window.ImageKprFolders) {
            window.ImageKprFolders.addToFolder(addToFolderName, ids);
          }
          validItems.forEach((it, idx) => {
            const id = ids[idx];
            if (id && it.tags && it.tags.length > 0) {
              apiFetch(API_BASE + '/tags.php', {
                method: 'PATCH',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id, tags: it.tags })
              }).catch(() => {});
            }
            if (id && it.folder && window.ImageKprFolders) {
              window.ImageKprFolders.addToFolder(it.folder, [id]);
            }
          });
          /* Folder filter uses localStorage IDs; a named folder hides uploads not in that list. Show “All” after upload. */
          const folderFilter = document.getElementById('folder-filter');
          if (folderFilter) folderFilter.value = '';
          populateFolderIcons('');
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

  function processAndUpload(items, addToFolderName) {
    Promise.all(items.map(it => resizeIfNeeded(it.file))).then(resized => {
      const updated = items.map((it, i) => ({ ...it, file: resized[i] || it.file }));
      uploadFiles(updated, addToFolderName);
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
    const folderSelect = document.getElementById('upload-add-to-folder-select');
    const folderNewInput = document.getElementById('upload-add-to-folder-new');

    let pendingItems = valid.map(f => ({ file: f, newName: '', tags: [], folder: null }));
    let objectUrls = [];

    function refreshFolderSelect() {
      const data = window.ImageKprFolders ? window.ImageKprFolders.load() : {};
      folderSelect.innerHTML = '<option value="">— None —</option>';
      Object.keys(data).sort().forEach(name => {
        const opt = document.createElement('option');
        opt.value = name;
        opt.textContent = name + ' (' + (data[name]?.length || 0) + ')';
        folderSelect.appendChild(opt);
      });
    }
    refreshFolderSelect();
    folderNewInput.value = '';

    function getAddToFolderName() {
      const newName = folderNewInput.value.trim();
      if (newName) return newName;
      return folderSelect.value || null;
    }

    function openUploadRenameModal(item) {
      const d = document.getElementById('upload-rename-dialog');
      const input = document.getElementById('upload-rename-input');
      const okBtn = document.getElementById('upload-rename-ok');
      const cancelBtn = document.getElementById('upload-rename-cancel');
      input.value = item.newName || item.file.name;
      input.select();
      d.hidden = false;
      document.body.style.overflow = 'hidden';
      const doCleanup = () => {
        d.hidden = true;
        document.body.style.overflow = '';
        okBtn.onclick = null;
        cancelBtn.onclick = null;
        d.onclick = null;
        document.removeEventListener('keydown', onEscape);
      };
      const onEscape = (e) => { if (e.key === 'Escape') doCleanup(); };
      okBtn.onclick = () => {
        const v = input.value.trim();
        if (v) item.newName = v;
        doCleanup();
        renderGrid();
      };
      cancelBtn.onclick = doCleanup;
      d.onclick = (e) => { if (e.target === d) doCleanup(); };
      document.addEventListener('keydown', onEscape);
    }

    function openUploadManageTags(item) {
      const d = document.getElementById('manage-tags-image-dialog');
      const pills = document.getElementById('manage-tags-image-pills');
      const addSelect = document.getElementById('manage-tags-image-select');
      const addNew = document.getElementById('manage-tags-image-new');
      const addBtn = document.getElementById('manage-tags-image-add');
      const closeBtn = document.getElementById('manage-tags-image-close');
      document.getElementById('manage-tags-image-title').textContent = 'Manage tags for: ' + (item.newName || item.file.name);
      pills.innerHTML = '';
      (item.tags || []).forEach(tag => {
        const span = document.createElement('span');
        span.className = 'tag-pill';
        span.innerHTML = escapeHtml(tag) + ' <button type="button" aria-label="Remove ' + escapeHtml(tag) + '">&times;</button>';
        span.querySelector('button').addEventListener('click', () => {
          item.tags = item.tags.filter(t => t !== tag);
          openUploadManageTags(item);
        });
        pills.appendChild(span);
      });
      addSelect.innerHTML = '<option value="">— Select or type new —</option>';
      fetchJSON(API_BASE + '/tags.php').then(data => {
        (data.tags || []).forEach(tag => {
          const opt = document.createElement('option');
          opt.value = tag;
          opt.textContent = tag;
          addSelect.appendChild(opt);
        });
      }).catch(() => {});
      addNew.value = '';
      d.hidden = false;
      document.body.style.overflow = 'hidden';
      const doCleanup = () => {
        d.hidden = true;
        document.body.style.overflow = '';
        addBtn.onclick = null;
        closeBtn.onclick = null;
        d.onclick = null;
        document.removeEventListener('keydown', onEscape);
      };
      const onEscape = (e) => { if (e.key === 'Escape') doCleanup(); };
      addBtn.onclick = () => {
        const tag = addNew.value.trim() || (addSelect.value ? addSelect.value.trim() : '');
        if (tag && !item.tags.includes(tag)) {
          item.tags.push(tag);
          addNew.value = '';
          addSelect.value = '';
          showToast('Tag added');
          openUploadManageTags(item);
        }
      };
      closeBtn.onclick = doCleanup;
      d.onclick = (e) => { if (e.target === d) doCleanup(); };
      document.addEventListener('keydown', onEscape);
    }

    function openUploadManageFolders(item) {
      addToFolderSelectDialog().then(name => {
        if (name && window.ImageKprFolders) {
          const d = window.ImageKprFolders.load();
          if (!d[name]) d[name] = [];
          window.ImageKprFolders.save(d);
          item.folder = name;
          showToast('Will add to ' + name);
          renderGrid();
        }
      });
    }

    function renderGrid() {
      objectUrls.forEach(u => URL.revokeObjectURL(u));
      objectUrls = [];
      grid.innerHTML = '';
      pendingItems.forEach((item, i) => {
        const row = document.createElement('div');
        row.className = 'upload-confirm-row';
        row.dataset.index = String(i);
        const url = URL.createObjectURL(item.file);
        objectUrls.push(url);
        const thumbWrap = document.createElement('div');
        thumbWrap.className = 'upload-confirm-thumb';
        const im = document.createElement('img');
        im.src = url;
        im.alt = item.file.name;
        thumbWrap.appendChild(im);
        const removeBtn = document.createElement('button');
        removeBtn.type = 'button';
        removeBtn.className = 'upload-confirm-thumb-remove upload-remove-x';
        removeBtn.setAttribute('aria-label', 'Remove image');
        removeBtn.title = 'Remove image';
        removeBtn.textContent = '×';
        thumbWrap.appendChild(removeBtn);
        row.appendChild(thumbWrap);
        const nameSpan = document.createElement('span');
        nameSpan.className = 'upload-confirm-name';
        nameSpan.textContent = item.newName || item.file.name;
        row.appendChild(nameSpan);
        const renameBtn = document.createElement('button');
        renameBtn.type = 'button';
        renameBtn.className = 'upload-confirm-btn';
        renameBtn.textContent = 'Rename';
        renameBtn.onclick = () => openUploadRenameModal(item);
        row.appendChild(renameBtn);
        const tagsBtn = document.createElement('button');
        tagsBtn.type = 'button';
        tagsBtn.className = 'upload-confirm-btn ikpr-btn-tags';
        tagsBtn.textContent = 'Apply/Manage Tags';
        tagsBtn.onclick = () => openUploadManageTags(item);
        row.appendChild(tagsBtn);
        const foldersBtn = document.createElement('button');
        foldersBtn.type = 'button';
        foldersBtn.className = 'upload-confirm-btn ikpr-btn-folders';
        foldersBtn.textContent = 'Apply/Manage Folders';
        foldersBtn.onclick = () => openUploadManageFolders(item);
        row.appendChild(foldersBtn);
        removeBtn.addEventListener('click', async () => {
          if (!(await confirmDialog('Remove from upload? File will not be uploaded. You can add it again later.'))) return;
          const idx = pendingItems.indexOf(item);
          if (idx >= 0) pendingItems.splice(idx, 1);
          renderGrid();
          updateCount();
        });
        grid.appendChild(row);
      });
    }

    function updateCount() {
      const n = pendingItems.length;
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
      const addToFolderName = getAddToFolderName();
      closeModal();
      if (pendingItems.length > 0) processAndUpload(pendingItems, addToFolderName);
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

  function formatBytes(n) {
    if (n < 1024) return n + ' B';
    if (n < 1024 * 1024) return (n / 1024).toFixed(1) + ' KB';
    return (n / (1024 * 1024)).toFixed(1) + ' MB';
  }

  function showInboxImportModal() {
    const dialog = document.getElementById('inbox-import-dialog');
    const listEl = document.getElementById('inbox-import-list');
    const countEl = document.getElementById('inbox-import-count');
    const confirmBtn = document.getElementById('inbox-import-confirm');
    const cancelBtn = document.getElementById('inbox-import-cancel');
    const bulkTags = document.getElementById('inbox-import-bulk-tags');
    const bulkFolder = document.getElementById('inbox-import-bulk-folder');
    const bulkApply = document.getElementById('inbox-import-bulk-apply');

    let pendingItems = [];

    function openInboxRenameModal(item) {
      const d = document.getElementById('inbox-rename-dialog');
      const currentEl = document.getElementById('inbox-rename-current');
      const input = document.getElementById('inbox-rename-input');
      const okBtn = document.getElementById('inbox-rename-ok');
      const cancelBtn = document.getElementById('inbox-rename-cancel');
      currentEl.textContent = item.filename;
      input.value = item.newName ?? item.filename;
      input.select();
      d.hidden = false;
      document.body.style.overflow = 'hidden';
      const doCleanup = () => {
        d.hidden = true;
        document.body.style.overflow = '';
        okBtn.onclick = null;
        cancelBtn.onclick = null;
        d.onclick = null;
        document.removeEventListener('keydown', onEscape);
      };
      const onEscape = (e) => { if (e.key === 'Escape') doCleanup(); };
      okBtn.onclick = () => {
        const v = input.value.trim();
        if (v) item.newName = v;
        doCleanup();
        renderList();
      };
      cancelBtn.onclick = doCleanup;
      d.onclick = (e) => { if (e.target === d) doCleanup(); };
      document.addEventListener('keydown', onEscape);
    }

    function openInboxManageTags(item) {
      if (!item.tags) item.tags = [];
      const d = document.getElementById('manage-tags-image-dialog');
      const pills = document.getElementById('manage-tags-image-pills');
      const addSelect = document.getElementById('manage-tags-image-select');
      const addNew = document.getElementById('manage-tags-image-new');
      const addBtn = document.getElementById('manage-tags-image-add');
      const closeBtn = document.getElementById('manage-tags-image-close');
      document.getElementById('manage-tags-image-title').textContent = 'Manage tags for: ' + item.filename;
      pills.innerHTML = '';
      (item.tags || []).forEach(tag => {
        const span = document.createElement('span');
        span.className = 'tag-pill';
        span.innerHTML = escapeHtml(tag) + ' <button type="button" aria-label="Remove ' + escapeHtml(tag) + '">&times;</button>';
        span.querySelector('button').addEventListener('click', () => {
          item.tags = item.tags.filter(t => t !== tag);
          openInboxManageTags(item);
        });
        pills.appendChild(span);
      });
      const removeRow = document.getElementById('bulk-tags-remove-row');
      if (removeRow) removeRow.style.display = 'none';
      addSelect.innerHTML = '<option value="">— Select or type new —</option>';
      fetchJSON(API_BASE + '/tags.php').then(data => {
        (data.tags || []).forEach(tag => {
          const opt = document.createElement('option');
          opt.value = tag;
          opt.textContent = tag;
          addSelect.appendChild(opt);
        });
      }).catch(() => {});
      addNew.value = '';
      d.hidden = false;
      document.body.style.overflow = 'hidden';
      const doCleanup = () => {
        d.hidden = true;
        document.body.style.overflow = '';
        const removeRow = document.getElementById('bulk-tags-remove-row');
        if (removeRow) removeRow.style.display = '';
        addBtn.onclick = null;
        closeBtn.onclick = null;
        d.onclick = null;
        document.removeEventListener('keydown', onEscape);
      };
      const onEscape = (e) => { if (e.key === 'Escape') doCleanup(); };
      addBtn.onclick = () => {
        const tag = addNew.value.trim() || (addSelect.value ? addSelect.value.trim() : '');
        if (tag && !item.tags.includes(tag)) {
          item.tags.push(tag);
          addNew.value = '';
          addSelect.value = '';
          openInboxManageTags(item);
        }
      };
      closeBtn.onclick = doCleanup;
      d.onclick = (e) => { if (e.target === d) doCleanup(); };
      document.addEventListener('keydown', onEscape);
    }

    function openInboxManageFolders(item) {
      addToFolderSelectDialog().then(name => {
        if (name && window.ImageKprFolders) {
          const d = window.ImageKprFolders.load();
          if (!d[name]) d[name] = [];
          window.ImageKprFolders.save(d);
          item.folder = name;
          showToast('Will add to ' + name);
          renderList();
        }
      });
    }

    function renderList() {
      listEl.innerHTML = '';
      const data = window.ImageKprFolders ? window.ImageKprFolders.load() : {};
      bulkFolder.innerHTML = '<option value="">— None —</option>';
      Object.keys(data).sort().forEach(name => {
        const opt = document.createElement('option');
        opt.value = name;
        opt.textContent = name;
        bulkFolder.appendChild(opt);
      });

      pendingItems.forEach((item, i) => {
        const row = document.createElement('div');
        row.className = 'inbox-import-row';
        row.dataset.index = String(i);

        const thumbWrap = document.createElement('div');
        thumbWrap.className = 'inbox-import-row-thumb';
        const thumbImg = document.createElement('img');
        thumbImg.src = API_BASE + '/inbox_preview.php?file=' + encodeURIComponent(item.filename);
        thumbImg.alt = '';
        thumbImg.loading = 'lazy';
        thumbImg.onerror = () => { thumbWrap.classList.add('inbox-import-thumb-failed'); };
        thumbWrap.appendChild(thumbImg);

        const removeBtn = document.createElement('button');
        removeBtn.type = 'button';
        removeBtn.className = 'inbox-import-row-remove inbox-remove-x';
        removeBtn.setAttribute('aria-label', 'Remove image');
        removeBtn.title = 'Remove image';
        removeBtn.textContent = '×';
        thumbWrap.appendChild(removeBtn);

        const nameSpan = document.createElement('span');
        nameSpan.className = 'inbox-import-row-name';
        nameSpan.textContent = item.newName || item.filename;
        nameSpan.title = item.newName || item.filename;

        const sizeSpan = document.createElement('span');
        sizeSpan.className = 'inbox-import-row-size';
        sizeSpan.textContent = formatBytes(item.size || 0);

        const renameBtn = document.createElement('button');
        renameBtn.type = 'button';
        renameBtn.className = 'upload-confirm-btn';
        renameBtn.textContent = 'Rename';
        renameBtn.onclick = () => openInboxRenameModal(item);

        const tagsBtn = document.createElement('button');
        tagsBtn.type = 'button';
        tagsBtn.className = 'upload-confirm-btn ikpr-btn-tags';
        tagsBtn.textContent = 'Apply/Manage Tags';
        tagsBtn.onclick = () => openInboxManageTags(item);

        const foldersBtn = document.createElement('button');
        foldersBtn.type = 'button';
        foldersBtn.className = 'upload-confirm-btn ikpr-btn-folders';
        foldersBtn.textContent = 'Apply/Manage Folders';
        foldersBtn.onclick = () => openInboxManageFolders(item);

        row.appendChild(thumbWrap);
        row.appendChild(nameSpan);
        row.appendChild(sizeSpan);
        row.appendChild(renameBtn);
        row.appendChild(tagsBtn);
        row.appendChild(foldersBtn);
        listEl.appendChild(row);

        removeBtn.addEventListener('click', async () => {
          if (!(await confirmDialog('Skip for now? File will stay in inbox for later review. It will not be imported.'))) return;
          const idx = pendingItems.indexOf(item);
          if (idx >= 0) pendingItems.splice(idx, 1);
          renderList();
          updateCount();
        });
      });

      updateCount();
    }

    function updateCount() {
      const n = pendingItems.length;
      countEl.textContent = n + ' file' + (n === 1 ? '' : 's') + ' to import';
      confirmBtn.disabled = n === 0;
    }

    let escapeHandler = null;
    function closeModal() {
      dialog.hidden = true;
      document.body.style.overflow = '';
      if (escapeHandler) document.removeEventListener('keydown', escapeHandler);
      confirmBtn.onclick = null;
      cancelBtn.onclick = null;
      bulkApply.onclick = null;
    }

    fetchJSON(API_BASE + '/inbox.php').then(data => {
      const pending = data.pending || [];
      if (pending.length === 0) {
        showToast('No files in inbox');
        return;
      }
      pendingItems = pending.map(p => ({ filename: p.filename, size: p.size }));

      bulkTags.value = '';
      renderList();
      dialog.hidden = false;
      document.body.style.overflow = 'hidden';

      bulkApply.onclick = () => {
        const tags = bulkTags.value.split(',').map(t => t.trim()).filter(Boolean);
        const folder = bulkFolder.value || null;
        pendingItems.forEach(item => {
          if (tags.length) item.tags = [...new Set([...(item.tags || []), ...tags])];
          if (folder) item.folder = folder;
        });
        renderList();
      };

      confirmBtn.onclick = () => {
        const items = pendingItems.map(({ filename, newName, tags, folder }) => {
          const o = { filename };
          if (newName) o.newName = newName;
          if (tags && tags.length) o.tags = tags;
          return o;
        });

        closeModal();
        apiFetch(API_BASE + '/inbox.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ items })
        }).then(r => r.json()).then(data => {
          if (data.success) {
            const ids = data.imported_ids || [];
            const folderMap = {};
            pendingItems.forEach((item, idx) => {
              const folder = item.folder;
              if (folder && ids[idx] !== undefined) {
                if (!folderMap[folder]) folderMap[folder] = [];
                folderMap[folder].push(ids[idx]);
              }
            });
            Object.entries(folderMap).forEach(([name, idArr]) => {
              if (window.ImageKprFolders) {
                window.ImageKprFolders.addToFolder(name, idArr);
                if (window.ImageKprFolders.onChange) window.ImageKprFolders.onChange();
              }
            });
            showToast('Imported ' + (data.imported || 0) + ' image(s)');
            loadInbox();
            loadStats();
            refreshGrid(false);
            if (window.ImageKprFolders && window.ImageKprFolders.onChange) window.ImageKprFolders.onChange();
          }
        }).catch(() => showToast('Import failed'));
      };

      cancelBtn.onclick = closeModal;
      dialog.onclick = (e) => { if (e.target === dialog) closeModal(); };
      escapeHandler = (e) => { if (e.key === 'Escape') closeModal(); };
      document.addEventListener('keydown', escapeHandler);
    }).catch(() => showToast('Failed to load inbox'));
  }

  function importInbox() {
    showInboxImportModal();
  }

  let cachedStats = { total_images: '—', total_storage_gb: '—' };

  function updateHintBanner() {
    const hintEl = document.getElementById('user-hint-text');
    if (!hintEl) return;
    if (selectedIds.size > 0) return; /* Phase 2: selection banner overrides */
    const filterInput = document.getElementById('folder-filter');
    const filterVal = filterInput ? filterInput.value : '';
    let folderLabel = 'all';
    if (filterVal === LATEST_FILTER) folderLabel = 'Last uploaded';
    else if (filterVal === UNCATEGORIZED_FILTER) folderLabel = 'Uncategorized';
    else if (filterVal) folderLabel = filterVal;
    const tagLabel = gridState.tagFilter ? "tags '" + gridState.tagFilter + "'" : 'no tags selected';
    const storageLabel = cachedStats.total_storage_gb + ' GB';
    hintEl.textContent = "Showing '" + gridState.total + "' images from '" + folderLabel + "' | " + storageLabel + " storage used | with " + tagLabel;
  }

  function loadStats() {
    loadInbox();
    fetchJSON(API_BASE + '/stats.php?t=' + Date.now()).then(data => {
      cachedStats.total_images = data.total_images != null ? data.total_images : '—';
      cachedStats.total_storage_gb = data.total_storage_gb != null ? data.total_storage_gb : '0.00';
      updateHintBanner();
    }).catch(() => {
      cachedStats.total_images = '—';
      cachedStats.total_storage_gb = '—';
      updateHintBanner();
    });
  }

  const DEFAULT_SORT = 'date_desc';
  const SORT_OPTIONS = [
    { value: 'date_desc', label: 'Latest' },
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
        if (gridState.sort === opt.value) {
          gridState.sort = DEFAULT_SORT;
        } else {
          gridState.sort = opt.value;
        }
        gridState.page = 1;
        refreshGrid(false);
        populateSortPills();
      });
      container.appendChild(pill);
    });
  }

  const FOLDER_IMG_CLOSED = 'assets/folder-closed-icon.png';
  const FOLDER_IMG_OPEN = 'assets/folder-open-icon.png';
  const CLOCK_SVG = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>';

  function populateTagsRow() {
    const container = document.getElementById('tag-filters');
    if (!container) return;
    fetchJSON(API_BASE + '/tags.php').then(data => {
      const tags = data.tags || [];
      container.innerHTML = '';
      tags.forEach(tag => {
        const chip = document.createElement('button');
        chip.type = 'button';
        chip.className = 'tag-filter-chip' + (gridState.tagFilter === tag ? ' active' : '');
        chip.textContent = tag;
        chip.addEventListener('click', () => {
          if (gridState.tagFilter === tag) {
            gridState.tagFilter = '';
          } else {
            gridState.tagFilter = tag;
          }
          gridState.page = 1;
          refreshGrid(false);
          populateTagsRow();
        });
        container.appendChild(chip);
      });
    }).catch(() => {});
  }

  function populateFolderIcons(activeValue) {
    const container = document.getElementById('folder-icons');
    const filterInput = document.getElementById('folder-filter');
    if (!container || !filterInput) return;
    const data = window.ImageKprFolders ? window.ImageKprFolders.load() : {};
    const cur = activeValue !== undefined ? activeValue : filterInput.value;
    container.innerHTML = '';

    function addFolderIcon(label, value, title, iconSvgOrFolder) {
      const wrap = document.createElement('div');
      wrap.className = 'folder-icon-wrap' + (cur === value ? ' active' : '');
      const btnWrap = document.createElement('div');
      btnWrap.className = 'folder-icon-btn-wrap';
      const tooltip = document.createElement('span');
      tooltip.className = 'folder-icon-tooltip';
      tooltip.textContent = label;
      tooltip.setAttribute('role', 'tooltip');
      const btn = document.createElement('button');
      btn.type = 'button';
      btn.className = 'folder-icon' + (cur === value ? ' active' : '');
      if (iconSvgOrFolder === 'folder') {
        const img = document.createElement('img');
        img.src = cur === value ? FOLDER_IMG_OPEN : FOLDER_IMG_CLOSED;
        img.alt = '';
        btn.appendChild(img);
      } else {
        btn.innerHTML = iconSvgOrFolder;
      }
      btn.setAttribute('aria-label', title || label);
      btnWrap.appendChild(tooltip);
      btnWrap.appendChild(btn);
      wrap.appendChild(btnWrap);
      const labelEl = document.createElement('span');
      labelEl.className = 'folder-icon-label';
      labelEl.textContent = truncate(label, 14);
      wrap.appendChild(labelEl);
      wrap.addEventListener('click', () => {
        if (cur === value) {
          filterInput.value = '';
          refreshGrid(false);
          populateFolderIcons('');
        } else {
          filterInput.value = value;
          refreshGrid(false);
          populateFolderIcons(value);
        }
      });
      container.appendChild(wrap);
    }

    addFolderIcon('All', '', 'Show all images', 'folder');
    addFolderIcon('Last uploaded', LATEST_FILTER, 'Show only the last uploaded batch of images', CLOCK_SVG);
    addFolderIcon('Uncategorized', UNCATEGORIZED_FILTER, 'Show images not in any folder', 'folder');
    Object.keys(data).sort().forEach(name => {
      const label = name + ' (' + (data[name]?.length || 0) + ')';
      addFolderIcon(label, name, label, 'folder');
    });
  }

  document.addEventListener('DOMContentLoaded', () => {
    /* Avoid restored/stale folder filter (localStorage IDs ≠ current library). */
    const folderFilterBoot = document.getElementById('folder-filter');
    if (folderFilterBoot) folderFilterBoot.value = '';
    populateFolderIcons();
    populateSortPills();
    if (window.ImageKprFolders) window.ImageKprFolders.onChange = () => { populateFolderIcons(); refreshGrid(false); };
    populateTagsRow();
    document.getElementById('manage-folders-btn').addEventListener('click', () => {
      const d = document.getElementById('manage-folders-dialog');
      const listEl = document.getElementById('manage-folders-list');
      const data = window.ImageKprFolders ? window.ImageKprFolders.load() : {};
      listEl.innerHTML = '';
      Object.entries(data).forEach(([name, ids]) => {
        const div = document.createElement('div');
        div.innerHTML = '<span>' + name + ' (' + ids.length + ')</span> <button type="button" data-name="' + name + '" class="manage-rename ikpr-btn-folders ikpr-btn-compact">Rename</button> <button type="button" data-name="' + name + '" class="manage-delete ikpr-btn-delete ikpr-btn-compact">Delete</button>';
        listEl.appendChild(div);
      });
      d.hidden = false;
    });
    document.getElementById('manage-close').addEventListener('click', () => { document.getElementById('manage-folders-dialog').hidden = true; });
    document.getElementById('manage-create-folder').addEventListener('click', () => {
      const n = document.getElementById('new-folder-name').value.trim();
      if (!n) return;
      const data = window.ImageKprFolders ? window.ImageKprFolders.load() : {};
      data[n] = [];
      window.ImageKprFolders.save(data);
      populateFolderIcons();
      document.getElementById('new-folder-name').value = '';
      document.getElementById('manage-folders-dialog').hidden = true;
      showToast('Folder "' + n + '" created');
    });
    document.getElementById('manage-folders-list').addEventListener('click', async (e) => {
      const name = e.target.dataset.name;
      if (!name) return;
      const data = window.ImageKprFolders.load();
      if (e.target.classList.contains('manage-rename')) {
        addToFolderDialog(name).then(newName => {
          if (!newName || newName === name) return;
          data[newName] = data[name] || [];
          delete data[name];
          window.ImageKprFolders.save(data);
          populateFolderIcons();
          const listEl = document.getElementById('manage-folders-list');
          listEl.innerHTML = '';
          Object.entries(data).forEach(([n, ids]) => {
            const div = document.createElement('div');
            div.innerHTML = '<span>' + n + ' (' + ids.length + ')</span> <button type="button" data-name="' + n + '" class="manage-rename ikpr-btn-folders ikpr-btn-compact">Rename</button> <button type="button" data-name="' + n + '" class="manage-delete ikpr-btn-delete ikpr-btn-compact">Delete</button>';
            listEl.appendChild(div);
          });
          showToast('Renamed to "' + newName + '"');
        });
      } else if (e.target.classList.contains('manage-delete')) {
        if (!(await confirmDialog('Delete folder "' + name + '"? Items will be removed from this folder.'))) return;
        delete data[name];
        window.ImageKprFolders.save(data);
        populateFolderIcons();
        const listEl = document.getElementById('manage-folders-list');
        listEl.innerHTML = '';
        Object.entries(data).forEach(([n, ids]) => {
          const div = document.createElement('div');
          div.innerHTML = '<span>' + n + ' (' + ids.length + ')</span> <button type="button" data-name="' + n + '" class="manage-rename ikpr-btn-folders ikpr-btn-compact">Rename</button> <button type="button" data-name="' + n + '" class="manage-delete ikpr-btn-delete ikpr-btn-compact">Delete</button>';
          listEl.appendChild(div);
        });
        showToast('Folder deleted');
      }
    });
    document.getElementById('manage-export').addEventListener('click', () => {
      const a = document.createElement('a');
      a.href = 'data:application/json,' + encodeURIComponent(JSON.stringify(window.ImageKprFolders.load(), null, 2));
      a.download = 'imagekpr-folders.json';
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
          const data = window.ImageKprFolders.load();
          const count = typeof imported === 'object' && imported !== null ? Object.keys(imported).length : 0;
          Object.assign(data, imported);
          window.ImageKprFolders.save(data);
          populateFolderIcons();
          const listEl = document.getElementById('manage-folders-list');
          listEl.innerHTML = '';
          Object.entries(data).forEach(([name, ids]) => {
            const div = document.createElement('div');
            div.innerHTML = '<span>' + name + ' (' + ids.length + ')</span> <button type="button" data-name="' + name + '" class="manage-rename ikpr-btn-folders ikpr-btn-compact">Rename</button> <button type="button" data-name="' + name + '" class="manage-delete ikpr-btn-delete ikpr-btn-compact">Delete</button>';
            listEl.appendChild(div);
          });
          const successEl = document.getElementById('manage-import-success');
          successEl.textContent = '✓ Imported ' + count + ' folder(s)';
          successEl.hidden = false;
          showToast('Imported ' + count + ' folder(s)');
          setTimeout(() => {
            successEl.hidden = true;
            document.getElementById('manage-folders-dialog').hidden = true;
          }, 1500);
        } catch (_) {
          showToast('Invalid file');
        }
      };
      r.readAsText(f);
      e.target.value = '';
    });
    const origAddToFolder = window.ImageKprFolders.addToFolder;
    window.ImageKprFolders.addToFolder = (folderNameOrIds, idsMaybe) => {
      if (idsMaybe !== undefined) {
        /* Two args: (folderName, ids) – direct add from upload, no dialog */
        origAddToFolder(folderNameOrIds, idsMaybe);
      } else {
        /* One arg: (ids) – bulk action, show select of pre-existing folders */
        addToFolderSelectDialog().then(name => {
          if (name) {
            origAddToFolder(name, folderNameOrIds);
            showToast('Added');
            populateFolderIcons();
          }
        });
      }
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
            if (gridState.tagFilter) p.tag = gridState.tagFilter;
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
    const modalPrev = document.getElementById('modal-prev');
    const modalNext = document.getElementById('modal-next');
    if (modalPrev) {
      modalPrev.addEventListener('click', (e) => {
        e.stopPropagation();
        navigateModal(-1);
      });
    }
    if (modalNext) {
      modalNext.addEventListener('click', (e) => {
        e.stopPropagation();
        navigateModal(1);
      });
    }
    document.addEventListener('keydown', (e) => {
      const modal = document.getElementById('modal');
      if (!modal || modal.hidden) return;
      if (e.key !== 'ArrowLeft' && e.key !== 'ArrowRight') return;
      const t = e.target;
      if (t && t.nodeType === 1) {
        if (t.tagName === 'INPUT' || t.tagName === 'TEXTAREA' || t.tagName === 'SELECT' || t.isContentEditable) return;
        if (t.closest && t.closest('[contenteditable="true"]')) return;
      }
      if (e.key === 'ArrowLeft' && navigateModal(-1)) e.preventDefault();
      else if (e.key === 'ArrowRight' && navigateModal(1)) e.preventDefault();
    });
    document.getElementById('modal').addEventListener('click', e => {
      if (e.target.id === 'modal') closeModal();
    });
    document.getElementById('modal-copy').addEventListener('click', () => {
      const url = document.getElementById('modal-img').src;
      if (url) copyUrl(url, true);
    });
    const modalCopyImageBtn = document.getElementById('modal-copy-image');
    if (modalCopyImageBtn) {
      if (!navigator.clipboard?.write || !window.ClipboardItem) {
        modalCopyImageBtn.hidden = true;
      } else {
        modalCopyImageBtn.addEventListener('click', () => {
          const url = document.getElementById('modal-img').src;
          if (url) copyImageToClipboard(url, true);
        });
      }
    }
    document.getElementById('modal-manage-tags').addEventListener('click', openManageTagsImageDialog);
    document.getElementById('modal-manage-folders').addEventListener('click', openManageFoldersImageDialog);
    document.getElementById('modal-download').addEventListener('click', () => {
      const img = document.getElementById('modal-img');
      const a = document.createElement('a');
      a.href = img.src;
      a.download = img.alt || 'image';
      a.click();
    });
    document.getElementById('bulk-clear').addEventListener('click', () => {
      selectedIds.clear();
      selectedImages.clear();
      document.querySelectorAll('.card-select:checked').forEach(c => { c.checked = false; });
      document.querySelectorAll('.card-inner.selected').forEach(el => el.classList.remove('selected'));
      updateBulkBar();
      updateHintBanner();
    });
    document.getElementById('bulk-delete').addEventListener('click', async () => {
      if (selectedIds.size === 0) return;
      if (!(await confirmDialog('Delete ' + selectedIds.size + ' image(s)?'))) return;
      apiFetch(API_BASE + '/delete_bulk.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ ids: Array.from(selectedIds) })
      }).then(r => r.json()).then(data => {
        if (data.success) {
          selectedIds.clear();
          selectedImages.clear();
          updateBulkBar();
          updateHintBanner();
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
      apiFetch(API_BASE + '/rename_bulk.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ ids: Array.from(selectedIds), base })
      }).then(r => r.json()).then(data => {
        document.getElementById('rename-dialog').hidden = true;
        if (data.success) {
          selectedIds.clear();
          selectedImages.clear();
          updateBulkBar();
          updateHintBanner();
          refreshGrid(false);
          showToast('Renamed');
        }
      }).catch(() => showToast('Rename failed'));
    });
    document.getElementById('bulk-tags').addEventListener('click', () => {
      if (selectedIds.size === 0) return;
      openBulkManageTagsDialog();
    });
    document.getElementById('bulk-folders').addEventListener('click', () => {
      if (selectedIds.size === 0) return;
      openBulkManageFoldersDialog();
    });
    document.getElementById('add-to-folder-select-cancel').addEventListener('click', () => {
      document.getElementById('add-to-folder-select-dialog').hidden = true;
      document.body.style.overflow = '';
    });
    document.getElementById('manage-tags-btn').addEventListener('click', () => {
      const d = document.getElementById('manage-tags-dialog');
      const listEl = document.getElementById('manage-tags-list');
      fetchJSON(API_BASE + '/tags.php').then(data => {
        const tags = data.tags || [];
        listEl.innerHTML = '';
        tags.forEach(tag => {
          const div = document.createElement('div');
          div.className = 'manage-tag-item';
          div.innerHTML = '<span>' + escapeHtml(tag) + '</span><button type="button" class="manage-tag-rename ikpr-btn-tags ikpr-btn-compact" data-tag="' + escapeHtml(tag) + '">Rename</button><button type="button" class="manage-tag-remove ikpr-btn-tags ikpr-btn-compact" data-tag="' + escapeHtml(tag) + '">Remove from all</button>';
          listEl.appendChild(div);
        });
        d.hidden = false;
        document.body.style.overflow = 'hidden';
      }).catch(() => showToast('Failed to load tags'));
    });
    document.getElementById('manage-tags-close').addEventListener('click', () => {
      document.getElementById('manage-tags-dialog').hidden = true;
      document.body.style.overflow = '';
    });
    document.getElementById('manage-tags-list').addEventListener('click', async (e) => {
      const tag = e.target.dataset.tag;
      if (!tag) return;
      if (e.target.classList.contains('manage-tag-rename')) {
        const newTag = prompt('Rename tag "' + tag + '" to:', tag);
        if (!newTag || newTag.trim() === tag.trim()) return;
        fetchJSON(API_BASE + '/images.php?per_page=10000&tag=' + encodeURIComponent(tag)).then(data => {
          const images = data.images || [];
          if (images.length === 0) { showToast('No images with this tag'); return; }
          Promise.all(images.map(img => {
            const tags = Array.isArray(img.tags) ? img.tags : [];
            const updated = tags.map(t => t === tag ? newTag.trim() : t);
            return apiFetch(API_BASE + '/tags.php', {
              method: 'PATCH',
              headers: { 'Content-Type': 'application/json' },
              body: JSON.stringify({ id: img.id, tags: updated })
            });
          })).then(() => {
            showToast('Tag renamed');
            populateTagsRow();
            document.getElementById('manage-tags-dialog').hidden = true;
            document.body.style.overflow = '';
            refreshGrid(false);
          }).catch(() => showToast('Failed'));
        }).catch(() => showToast('Failed'));
      } else if (e.target.classList.contains('manage-tag-remove')) {
        if (!(await confirmDialog('Remove tag "' + tag + '" from all images?'))) return;
        fetchJSON(API_BASE + '/images.php?per_page=10000&tag=' + encodeURIComponent(tag)).then(data => {
          const images = data.images || [];
          if (images.length === 0) { showToast('No images with this tag'); return; }
          const ids = images.map(img => img.id);
          apiFetch(API_BASE + '/tags.php', {
            method: 'PATCH',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ ids, action: 'remove', tag })
          }).then(r => r.json()).then(d => {
            if (d.success) {
              showToast('Tag removed');
              populateTagsRow();
              document.getElementById('manage-tags-dialog').hidden = true;
              document.body.style.overflow = '';
              refreshGrid(false);
            } else showToast('Failed');
          }).catch(() => showToast('Failed'));
        }).catch(() => showToast('Failed'));
      }
    });

    document.getElementById('inbox-import-btn').addEventListener('click', importInbox);

    document.getElementById('scroll-to-top').addEventListener('click', () => {
      window.scrollTo({ top: 0, behavior: 'smooth' });
    });

    document.getElementById('modal-rename-btn').addEventListener('click', () => {
      if (!currentModalImg) return;
      const filenameInput = document.getElementById('modal-filename');
      const newName = filenameInput ? filenameInput.value.trim() : '';
      if (!newName) return;
      apiFetch(API_BASE + '/rename.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id: currentModalImg.id, filename: newName })
      }).then(r => r.json()).then(data => {
        if (data.success) {
          currentModalImg.filename = data.filename;
          currentModalImg.url = data.url;
          document.getElementById('modal-img').alt = data.filename;
          if (filenameInput) filenameInput.value = data.filename;
          refreshGrid(false);
          showToast('Renamed');
        } else showToast(data.error || 'Rename failed');
      }).catch(() => showToast('Rename failed'));
    });
    document.getElementById('modal-delete').addEventListener('click', async () => {
      if (!currentModalImg) return;
      if (!(await confirmDialog('Delete this image?'))) return;
      apiFetch(API_BASE + '/delete.php', {
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
