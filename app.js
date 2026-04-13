/* ImageKpr - app logic */
(function () {
  'use strict';

  (function resetSlideshowDomOnLoad() {
    const p = document.getElementById('slideshow-player');
    const w = document.getElementById('slideshow-settings-wrap');
    const mf = document.getElementById('modal-fullscreen');
    if (p) {
      p.hidden = true;
      p.classList.remove('slideshow-player-locked');
      p.setAttribute('aria-hidden', 'true');
    }
    if (w) {
      w.hidden = true;
      w.classList.remove('slideshow-settings-open');
    }
    if (mf) {
      mf.hidden = true;
      mf.setAttribute('aria-hidden', 'true');
    }
  })();

  const API_BASE = 'api';
  const GRID_CARD_DRAG_MIME = 'application/x-imagekpr-grid-image';

  function redirectToLogin() {
    window.location.href = 'index.php#login';
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
  /** Display / slideshow / bulk id sequence (matches thumbnails left-to-right). */
  let selectedOrder = [];

  function syncSelectionOrderIntegrity() {
    selectedOrder = selectedOrder.filter((oid) => selectedIds.has(oid));
    selectedIds.forEach((sid) => {
      if (selectedOrder.indexOf(sid) === -1) selectedOrder.push(sid);
    });
  }

  function getSelectedIdsOrdered() {
    syncSelectionOrderIntegrity();
    return selectedOrder.slice();
  }

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

  function reorderSelectionThumbs(fromId, toId) {
    const a = Number(fromId);
    const b = Number(toId);
    if (a === b) return;
    const fromI = selectedOrder.indexOf(a);
    const toI = selectedOrder.indexOf(b);
    if (fromI < 0 || toI < 0) return;
    selectedOrder.splice(fromI, 1);
    const newToI = selectedOrder.indexOf(b);
    selectedOrder.splice(newToI, 0, a);
  }

  function updateSelectionBanner() {
    const banner = document.getElementById('selection-banner');
    const row = document.getElementById('selection-row');
    if (!banner || !row) return;
    row.innerHTML = '';
    syncSelectionOrderIntegrity();
    selectedOrder.forEach((id) => {
      const data = selectedImages.get(id);
      if (!data) return;
      const wrap = document.createElement('div');
      wrap.className = 'selection-thumb';
      wrap.draggable = true;
      wrap.dataset.id = String(id);
      wrap.title = 'Drag to reorder slideshow';
      const im = document.createElement('img');
      im.src = data.url;
      im.alt = data.filename || '';
      im.draggable = false;
      wrap.appendChild(im);
      row.appendChild(wrap);
    });
    banner.hidden = false;
  }

  /** Select every image card currently in the grid (same set as folder / tag / search view; infinite scroll = only loaded rows). */
  function selectAllVisibleInGrid() {
    const grid = document.getElementById('grid');
    if (!grid) return;
    const cards = grid.querySelectorAll('.grid-item.card');
    if (cards.length === 0) {
      showToast('No images in the current view');
      return;
    }
    let n = 0;
    let newly = 0;
    cards.forEach((article) => {
      const img = article._imageKprImg;
      if (!img) return;
      const checkEl = article.querySelector('.card-select');
      const inner = article.querySelector('.card-inner');
      if (!checkEl || !inner) return;
      if (!checkEl.checked) newly++;
      const id = Number(img.id);
      const wasSel = selectedIds.has(id);
      selectedIds.add(id);
      selectedImages.set(id, { url: img.url, filename: img.filename });
      if (!wasSel) selectedOrder.push(id);
      checkEl.checked = true;
      inner.classList.add('selected');
      n++;
    });
    updateBulkBar();
    updateHintBanner();
    const total = selectedIds.size;
    if (newly > 0) {
      showToast('Added ' + newly + ' image' + (newly === 1 ? '' : 's') + ' to selection (' + total + ' total)');
    } else if (n > 0) {
      showToast('All ' + n + ' visible image' + (n === 1 ? '' : 's') + ' already selected');
    }
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
    article.draggable = true;
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
          if (selectedOrder.indexOf(id) === -1) selectedOrder.push(id);
        } else {
          selectedIds.delete(id);
          selectedImages.delete(id);
          selectedOrder = selectedOrder.filter((x) => x !== id);
        }
        inner.classList.toggle('selected', checkEl.checked);
        updateBulkBar();
      });
      if (selectedIds.has(Number(img.id))) {
        checkEl.checked = true;
        inner.classList.add('selected');
        const rid = Number(img.id);
        selectedImages.set(rid, { url: img.url, filename: img.filename });
        if (selectedOrder.indexOf(rid) === -1) selectedOrder.push(rid);
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
    article.addEventListener('dragstart', (e) => {
      const target = e.target;
      if (target && target.closest && (target.closest('.card-select') || target.closest('.card-expand'))) {
        e.preventDefault();
        return;
      }
      const id = Number(img.id);
      if (!Number.isFinite(id) || id < 1 || !e.dataTransfer) {
        e.preventDefault();
        return;
      }
      const payload = {
        id: id,
        filename: String(img.filename || ('image-' + id))
      };
      e.dataTransfer.effectAllowed = 'copy';
      e.dataTransfer.setData(GRID_CARD_DRAG_MIME, JSON.stringify(payload));
      e.dataTransfer.setData('text/plain', String(id));
      article.classList.add('card-dragging');
    });
    article.addEventListener('dragend', () => {
      article.classList.remove('card-dragging');
      document.querySelectorAll('.folder-icon-wrap.folder-drop-over').forEach((el) => {
        el.classList.remove('folder-drop-over');
      });
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

  let modalFullscreenState = null;

  function closeModalFullscreen() {
    const shell = document.getElementById('modal-fullscreen');
    if (!shell || shell.hidden) return;
    if (modalFullscreenState) {
      if (modalFullscreenState.onKeyDown) document.removeEventListener('keydown', modalFullscreenState.onKeyDown, true);
      if (modalFullscreenState.onFsChange) document.removeEventListener('fullscreenchange', modalFullscreenState.onFsChange);
      if (modalFullscreenState.stageClickHandler && modalFullscreenState.stage) {
        modalFullscreenState.stage.removeEventListener('click', modalFullscreenState.stageClickHandler);
      }
      modalFullscreenState = null;
    }
    const exitBtn = document.getElementById('modal-fullscreen-exit');
    if (exitBtn) exitBtn.onclick = null;
    try {
      if (document.fullscreenElement === shell) document.exitFullscreen();
    } catch (_) {}
    const fsImg = document.getElementById('modal-fullscreen-img');
    if (fsImg) {
      fsImg.removeAttribute('src');
      fsImg.removeAttribute('alt');
    }
    shell.hidden = true;
    shell.setAttribute('aria-hidden', 'true');
  }

  function modalFullscreenNavigate(delta) {
    if (!currentModalImg) return false;
    const list = getOrderedVisibleImages();
    const id = Number(currentModalImg.id);
    const i = list.findIndex(img => Number(img.id) === id);
    if (i < 0) return false;
    const next = list[i + delta];
    if (!next) return false;
    openModal(next);
    const fsImg = document.getElementById('modal-fullscreen-img');
    if (fsImg) {
      fsImg.src = currentModalImg.url;
      fsImg.alt = currentModalImg.filename || '';
    }
    return true;
  }

  function openModalFullscreen() {
    const modal = document.getElementById('modal');
    if (!modal || modal.hidden || !currentModalImg) return;
    closeModalFullscreen();
    const shell = document.getElementById('modal-fullscreen');
    const stage = shell && shell.querySelector('.modal-fullscreen-stage');
    const fsImg = document.getElementById('modal-fullscreen-img');
    const exitBtn = document.getElementById('modal-fullscreen-exit');
    if (!shell || !stage || !fsImg || !exitBtn) return;

    fsImg.src = currentModalImg.url;
    fsImg.alt = currentModalImg.filename || '';

    modalFullscreenState = {
      stage,
      expectFullscreen: false,
      stageClickHandler: null,
      onKeyDown: null,
      onFsChange: null,
    };

    modalFullscreenState.stageClickHandler = (e) => {
      if (e.target === exitBtn || (exitBtn.contains && exitBtn.contains(e.target))) return;
      const r = slideshowContainedImageRect(fsImg, stage);
      const x = e.clientX;
      const y = e.clientY;
      if (r && x >= r.left && x <= r.right && y >= r.top && y <= r.bottom) return;
      closeModalFullscreen();
    };
    stage.addEventListener('click', modalFullscreenState.stageClickHandler);

    modalFullscreenState.onKeyDown = (e) => {
      if (shell.hidden) return;
      const t = e.target;
      if (t && t.nodeType === 1) {
        if (t.tagName === 'INPUT' || t.tagName === 'TEXTAREA' || t.tagName === 'SELECT' || t.isContentEditable) return;
        if (t.closest && t.closest('[contenteditable="true"]')) return;
      }
      if (e.key === 'Escape') {
        e.preventDefault();
        closeModalFullscreen();
        return;
      }
      if (e.key === ' ' || e.key === 'Spacebar') {
        e.preventDefault();
        modalFullscreenNavigate(1);
        return;
      }
      if (e.key === 'ArrowRight') {
        e.preventDefault();
        modalFullscreenNavigate(1);
        return;
      }
      if (e.key === 'ArrowLeft') {
        e.preventDefault();
        modalFullscreenNavigate(-1);
        return;
      }
    };
    document.addEventListener('keydown', modalFullscreenState.onKeyDown, true);

    modalFullscreenState.onFsChange = () => {
      if (shell.hidden || !modalFullscreenState) return;
      if (modalFullscreenState.expectFullscreen && !document.fullscreenElement) {
        closeModalFullscreen();
      }
    };
    document.addEventListener('fullscreenchange', modalFullscreenState.onFsChange);

    exitBtn.onclick = () => closeModalFullscreen();

    shell.hidden = false;
    shell.setAttribute('aria-hidden', 'false');

    if (shell.requestFullscreen) {
      try {
        const p = shell.requestFullscreen();
        if (p && typeof p.then === 'function') {
          p.then(() => {
            if (modalFullscreenState) modalFullscreenState.expectFullscreen = true;
          }).catch(() => {});
        } else if (modalFullscreenState) {
          modalFullscreenState.expectFullscreen = true;
        }
      } catch (_) {}
    }
  }

  let slideshowState = null;

  function getSlideshowImageById(id) {
    const nid = Number(id);
    const visible = getOrderedVisibleImages();
    for (let i = 0; i < visible.length; i++) {
      if (Number(visible[i].id) === nid) return visible[i];
    }
    const s = selectedImages.get(nid);
    if (s) return { id: nid, url: s.url, filename: s.filename };
    return null;
  }

  function getSlideshowSlides() {
    syncSelectionOrderIntegrity();
    return selectedOrder.map(getSlideshowImageById).filter(Boolean);
  }

  const SLIDESHOW_LETTERBOX = {
    black: '#000000',
    white: '#ffffff',
    red: '#c62828',
    green: '#2e7d32',
    blue: '#1565c0',
  };
  const SLIDESHOW_INTERVAL_MS_MIN = 1000;
  const SLIDESHOW_INTERVAL_MS_MAX = 600 * 1000;

  function clampSlideshowIntervalMs(ms) {
    return Math.max(SLIDESHOW_INTERVAL_MS_MIN, Math.min(SLIDESHOW_INTERVAL_MS_MAX, ms));
  }

  /** Viewport rect of the drawn bitmap for object-fit:contain in stage (not the img element box, which is full-bleed). */
  function slideshowContainedImageRect(imgEl, stageEl) {
    if (!imgEl || !stageEl) return null;
    const cr = stageEl.getBoundingClientRect();
    const nw = imgEl.naturalWidth;
    const nh = imgEl.naturalHeight;
    if (!nw || !nh) return null;
    const cw = cr.width;
    const ch = cr.height;
    const scale = Math.min(cw / nw, ch / nh);
    const dw = nw * scale;
    const dh = nh * scale;
    const left = cr.left + (cw - dw) / 2;
    const top = cr.top + (ch - dh) / 2;
    return { left, top, right: left + dw, bottom: top + dh };
  }

  function updateSlideshowCounter() {
    if (!slideshowState) return;
    const el = document.getElementById('slideshow-counter');
    el.textContent = (slideshowState.index + 1) + ' / ' + slideshowState.slides.length;
  }

  function updateSlideshowFilenameCaption() {
    const cap = document.getElementById('slideshow-filename-caption');
    if (!cap || !slideshowState) return;
    if (!slideshowState.showFilename) {
      cap.hidden = true;
      cap.setAttribute('aria-hidden', 'true');
      cap.textContent = '';
      return;
    }
    const slide = slideshowState.slides[slideshowState.index];
    const name = slide && slide.filename ? String(slide.filename) : '';
    cap.textContent = name;
    cap.hidden = !name;
    cap.setAttribute('aria-hidden', name ? 'false' : 'true');
  }

  function clearSlideshowTimer() {
    if (slideshowState && slideshowState.timerId != null) {
      clearInterval(slideshowState.timerId);
      slideshowState.timerId = null;
    }
  }

  function armSlideshowAutoTimer() {
    if (!slideshowState || !slideshowState.auto || slideshowState.paused) return;
    clearSlideshowTimer();
    slideshowState.timerId = setInterval(() => {
      if (!slideshowState || slideshowState.transitioning) return;
      const ok = slideshowAdvance(1);
      if (!ok) clearSlideshowTimer();
    }, slideshowState.intervalMs);
  }

  function shuffleSlideshowSlidesInPlace() {
    if (!slideshowState) return;
    const a = slideshowState.slides;
    for (let i = a.length - 1; i > 0; i--) {
      const j = Math.floor(Math.random() * (i + 1));
      const t = a[i];
      a[i] = a[j];
      a[j] = t;
    }
  }

  function slideshowAdvance(delta) {
    if (!slideshowState || slideshowState.transitioning) return false;
    const { slides, autoloop, index, randomizeOnLoop } = slideshowState;
    let i = index + delta;
    if (i >= slides.length) {
      if (!autoloop) return false;
      if (randomizeOnLoop) shuffleSlideshowSlidesInPlace();
      i = 0;
    } else if (i < 0) {
      if (!autoloop) return false;
      if (randomizeOnLoop) shuffleSlideshowSlidesInPlace();
      i = slides.length - 1;
    }
    goToSlideshowSlide(i, false);
    return true;
  }

  const SS_DIFFUSE_OUT_MS = 320;
  const SS_DIFFUSE_IN_MS = 420;
  const SS_FLY_OUT_MS = 320;
  const SS_FLY_IN_MS = 450;

  function goToSlideshowSlide(newIndex, instant) {
    if (!slideshowState) return;
    const { slides, transition } = slideshowState;
    if (newIndex < 0 || newIndex >= slides.length) return;
    const img = document.getElementById('slideshow-img');
    const slide = slides[newIndex];

    const applySlide = () => {
      slideshowState.index = newIndex;
      img.src = slide.url;
      img.alt = slide.filename || '';
      const onLoad = () => {
        img.removeEventListener('load', onLoad);
      };
      img.addEventListener('load', onLoad);
      updateSlideshowCounter();
      updateSlideshowFilenameCaption();
    };

    if (instant) {
      img.classList.remove('ss-diffuse-out', 'ss-diffuse-in', 'ss-fly-out', 'ss-fly-in');
      applySlide();
      return;
    }

    slideshowState.transitioning = true;
    if (transition === 'fly') {
      img.classList.remove('ss-diffuse-out', 'ss-diffuse-in', 'ss-fly-in');
      img.classList.add('ss-fly-out');
      setTimeout(() => {
        img.classList.remove('ss-fly-out');
        applySlide();
        img.classList.add('ss-fly-in');
        setTimeout(() => {
          img.classList.remove('ss-fly-in');
          slideshowState.transitioning = false;
        }, SS_FLY_IN_MS);
      }, SS_FLY_OUT_MS);
    } else {
      img.classList.remove('ss-fly-out', 'ss-fly-in', 'ss-diffuse-in');
      img.classList.add('ss-diffuse-out');
      setTimeout(() => {
        img.classList.remove('ss-diffuse-out');
        applySlide();
        img.classList.add('ss-diffuse-in');
        setTimeout(() => {
          img.classList.remove('ss-diffuse-in');
          slideshowState.transitioning = false;
        }, SS_DIFFUSE_IN_MS);
      }, SS_DIFFUSE_OUT_MS);
    }
  }

  function closeSlideshowPlayer() {
    if (slideshowState) {
      clearSlideshowTimer();
      if (slideshowState.onKeyDown) {
        document.removeEventListener('keydown', slideshowState.onKeyDown, true);
      }
      const pl = document.getElementById('slideshow-player');
      const st = pl && pl.querySelector('.slideshow-stage');
      if (slideshowState.stageClickHandler && st) {
        st.removeEventListener('click', slideshowState.stageClickHandler);
      }
    }
    const player = document.getElementById('slideshow-player');
    const img = document.getElementById('slideshow-img');
    const exitBtn = document.getElementById('slideshow-exit-btn');
    if (exitBtn) exitBtn.onclick = null;
    if (img) {
      img.classList.remove('ss-diffuse-out', 'ss-diffuse-in', 'ss-fly-out', 'ss-fly-in');
      img.removeAttribute('src');
      img.removeAttribute('alt');
    }
    const cap = document.getElementById('slideshow-filename-caption');
    if (cap) {
      cap.textContent = '';
      cap.hidden = true;
      cap.setAttribute('aria-hidden', 'true');
    }
    if (player) {
      const stageEl = player.querySelector('.slideshow-stage');
      if (stageEl) stageEl.style.backgroundColor = '';
      player.style.backgroundColor = '';
      player.hidden = true;
      player.classList.remove('slideshow-player-locked');
      player.setAttribute('aria-hidden', 'true');
    }
    slideshowState = null;
    document.body.style.overflow = '';
  }

  function openSlideshowPlayer(slides, options) {
    closeModalFullscreen();
    closeSlideshowPlayer();
    slideshowState = {
      slides,
      index: 0,
      auto: options.auto,
      intervalMs: clampSlideshowIntervalMs(options.intervalMs),
      autoloop: options.autoloop,
      randomizeOnLoop: options.randomizeOnLoop,
      transition: options.transition,
      showFilename: !!options.showFilename,
      paused: false,
      timerId: null,
      transitioning: false,
    };

    const player = document.getElementById('slideshow-player');
    const img = document.getElementById('slideshow-img');
    const exitBtn = document.getElementById('slideshow-exit-btn');
    const letterbox = options.letterboxColor || SLIDESHOW_LETTERBOX.black;
    const stage = player.querySelector('.slideshow-stage');
    if (player) {
      if (stage) stage.style.backgroundColor = letterbox;
      player.style.backgroundColor = letterbox;
    }

    exitBtn.hidden = false;

    player.hidden = false;
    player.setAttribute('aria-hidden', 'false');
    document.body.style.overflow = 'hidden';

    goToSlideshowSlide(0, true);

    if (stage) {
      slideshowState.stageClickHandler = (e) => {
        if (!slideshowState) return;
        const imgEl = document.getElementById('slideshow-img');
        const st = document.querySelector('#slideshow-player .slideshow-stage');
        if (!imgEl || !st) return;
        const r = slideshowContainedImageRect(imgEl, st);
        const x = e.clientX;
        const y = e.clientY;
        if (r && x >= r.left && x <= r.right && y >= r.top && y <= r.bottom) return;
        closeSlideshowPlayer();
      };
      stage.addEventListener('click', slideshowState.stageClickHandler);
    }

    slideshowState.onKeyDown = (e) => {
      if (player.hidden) return;
      if (e.key === 'Escape') {
        e.preventDefault();
        closeSlideshowPlayer();
        return;
      }
      if (slideshowState.auto) {
        if (e.key === 'Enter') {
          e.preventDefault();
          slideshowState.paused = !slideshowState.paused;
          if (slideshowState.paused) clearSlideshowTimer();
          else armSlideshowAutoTimer();
          return;
        }
        if (e.key === 'ArrowUp') {
          e.preventDefault();
          slideshowState.intervalMs = clampSlideshowIntervalMs(slideshowState.intervalMs + 1000);
          if (!slideshowState.paused) armSlideshowAutoTimer();
          showToast('Interval: ' + (slideshowState.intervalMs / 1000) + ' s', false);
          return;
        }
        if (e.key === 'ArrowDown') {
          e.preventDefault();
          slideshowState.intervalMs = clampSlideshowIntervalMs(slideshowState.intervalMs - 1000);
          if (!slideshowState.paused) armSlideshowAutoTimer();
          showToast('Interval: ' + (slideshowState.intervalMs / 1000) + ' s', false);
          return;
        }
      }
      if (e.key === ' ' || e.key === 'Spacebar') {
        e.preventDefault();
        if (slideshowAdvance(1) && slideshowState.auto && !slideshowState.paused) armSlideshowAutoTimer();
        return;
      }
      if (e.key === 'ArrowRight') {
        e.preventDefault();
        if (slideshowAdvance(1) && slideshowState.auto && !slideshowState.paused) armSlideshowAutoTimer();
        return;
      }
      if (e.key === 'ArrowLeft') {
        e.preventDefault();
        if (slideshowAdvance(-1) && slideshowState.auto && !slideshowState.paused) armSlideshowAutoTimer();
      }
    };
    document.addEventListener('keydown', slideshowState.onKeyDown, true);

    exitBtn.onclick = () => closeSlideshowPlayer();

    if (options.auto) armSlideshowAutoTimer();
  }

  function closeSlideshowSettingsPanel() {
    const wrap = document.getElementById('slideshow-settings-wrap');
    if (!wrap || wrap.hidden) return;
    wrap.classList.remove('slideshow-settings-open');
    setTimeout(() => {
      wrap.hidden = true;
      const player = document.getElementById('slideshow-player');
      if (!player || player.hidden) document.body.style.overflow = '';
    }, 380);
  }

  function openSlideshowSettingsPanel() {
    const slides = getSlideshowSlides();
    if (slides.length === 0) {
      showToast('Select at least one image');
      return;
    }
    closeModal();
    syncSlideshowSettingsForm();
    const wrap = document.getElementById('slideshow-settings-wrap');
    wrap.hidden = false;
    requestAnimationFrame(() => {
      requestAnimationFrame(() => wrap.classList.add('slideshow-settings-open'));
    });
    document.body.style.overflow = 'hidden';
  }

  function readSlideshowOptionsFromForm() {
    const auto = document.getElementById('slideshow-advance-auto').checked;
    let duration = parseInt(String(document.getElementById('slideshow-duration').value || '5'), 10);
    if (isNaN(duration) || duration < 1) duration = 5;
    if (duration > 600) duration = 600;
    const autoloop = document.getElementById('slideshow-autoloop').checked;
    const randomizeOnLoop = autoloop && document.getElementById('slideshow-randomize-loop').checked;
    const transition = document.getElementById('slideshow-trans-fly').checked ? 'fly' : 'diffuse';
    const lbEl = document.querySelector('input[name="slideshow-letterbox"]:checked');
    const lbKey = lbEl && Object.prototype.hasOwnProperty.call(SLIDESHOW_LETTERBOX, lbEl.value) ? lbEl.value : 'black';
    return {
      auto,
      intervalMs: duration * 1000,
      autoloop,
      randomizeOnLoop,
      transition,
      letterboxColor: SLIDESHOW_LETTERBOX[lbKey],
      showFilename: !!(document.getElementById('slideshow-show-filename') || {}).checked,
    };
  }

  function syncSlideshowSettingsForm() {
    const auto = document.getElementById('slideshow-advance-auto').checked;
    const row = document.getElementById('slideshow-duration-row');
    const dur = document.getElementById('slideshow-duration');
    row.style.opacity = auto ? '1' : '0.45';
    dur.disabled = !auto;
    const loopOn = document.getElementById('slideshow-autoloop').checked;
    const randEl = document.getElementById('slideshow-randomize-loop');
    const randLabel = document.getElementById('slideshow-randomize-loop-label');
    if (randEl && randLabel) {
      randEl.disabled = !loopOn;
      randLabel.style.opacity = loopOn ? '1' : '0.45';
    }
  }

  function startSlideshowFromForm() {
    const slides = getSlideshowSlides();
    if (slides.length === 0) {
      showToast('No images selected');
      return;
    }
    const opts = readSlideshowOptionsFromForm();
    const wrap = document.getElementById('slideshow-settings-wrap');
    wrap.classList.remove('slideshow-settings-open');
    setTimeout(() => {
      wrap.hidden = true;
      openSlideshowPlayer(slides, opts);
    }, 360);
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
          if (!window.ImageKprFolders) return;
          window.ImageKprFolders.removeFromFolder(name, imgId).then(() => {
            showToast('Removed from ' + name);
            refresh();
          }).catch(err => showToast(err.message || 'Failed', true));
        });
        pills.appendChild(span);
      });
    }
    function addToFolder() {
      const name = newInput.value.trim() || (selectEl.value ? selectEl.value.trim() : '');
      if (name && window.ImageKprFolders) {
        window.ImageKprFolders.addToFolder(name, [imgId]).then(() => {
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
        }).catch(err => showToast(err.message || 'Failed', true));
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
    const ids = getSelectedIdsOrdered();
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
    const ids = getSelectedIdsOrdered();
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
      window.ImageKprFolders.addToFolder(name, ids).then(() => {
        showToast('Added to ' + name);
        addNew.value = '';
        addSelect.value = '';
        refreshSelects();
        refreshGrid(false);
      }).catch(err => showToast(err.message || 'Failed', true));
    }
    function doRemove() {
      const name = removeSelect.value ? removeSelect.value.trim() : '';
      if (!name) return;
      window.ImageKprFolders.removeFromFolder(name, ids).then(() => {
        showToast('Removed from ' + name);
        refreshSelects();
        refreshGrid(false);
      }).catch(err => showToast(err.message || 'Failed', true));
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
    closeModalFullscreen();
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
        const q = (gridState.search || '').trim();
        const tag = (gridState.tagFilter || '').trim();
        if (q) {
          grid.innerHTML =
            '<p class="empty">No images match your search.</p>' +
            '<p class="empty" style="font-size:0.85rem;max-width:36rem;margin-left:auto;margin-right:auto;line-height:1.45">Try different words or clear the search box to see your whole library.</p>';
        } else if (tag) {
          grid.innerHTML =
            '<p class="empty">No images with this tag in the current view.</p>' +
            '<p class="empty" style="font-size:0.85rem;max-width:36rem;margin-left:auto;margin-right:auto;line-height:1.45">Clear the tag filter or choose <strong>All</strong> to browse everything.</p>';
        } else {
          grid.innerHTML =
            '<p class="empty">No images yet. Upload some!</p>' +
            '<p class="empty" style="font-size:0.85rem;max-width:36rem;margin-left:auto;margin-right:auto;line-height:1.45">' +
            'Use the upload area above, or drag and drop files onto it.</p>';
        }
      }
      if (append) return;
      if (gridState.total > 1000) {
        loadMore.innerHTML = '';
        const p = document.createElement('div');
        p.className = 'pagination';
        const prev = document.createElement('button');
        prev.textContent = 'Previous';
        prev.disabled = gridState.page <= 1;
        prev.onclick = () => { gridState.page--; refreshGrid(false, { keepPage: true }); };
        const next = document.createElement('button');
        next.textContent = 'Next';
        next.disabled = loaded >= gridState.total;
        next.onclick = () => { gridState.page++; refreshGrid(false, { keepPage: true }); };
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

  /**
   * Reload grid. When append is false, resets to page 1 unless opts.keepPage is true
   * (used by prev/next when total > 1000). Infinite scroll uses loadGrid(…, true) and
   * increments gridState.page; without this reset, a later refresh (e.g. after folder
   * API refresh) would request page 2+ and show only the tail slice while total still
   * reflects the full library count.
   */
  function refreshGrid(append, opts) {
    opts = opts || {};
    if (!append && !opts.keepPage) {
      gridState.page = 1;
    }
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
          '<p class="empty" style="font-size:0.85rem;max-width:36rem;margin-left:auto;margin-right:auto;line-height:1.45">' +
          'Click <strong>All</strong> above to see your full library, or choose another folder.</p>';
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

  let MAX_UPLOAD = 3 * 1024 * 1024;
  let MAX_UPLOAD_MB = 3;
  const MAX_WIDTH = 1920;

  function canvasToBlob(canvas, type, quality) {
    return new Promise((resolve) => canvas.toBlob(resolve, type, quality));
  }

  async function resizeIfNeeded(file, forceResizeForLimit) {
    if (!file.type.match(/image/)) return file;
    const forceResize = forceResizeForLimit === true || file.size > MAX_UPLOAD;
    return new Promise((resolve) => {
      const objectUrl = URL.createObjectURL(file);
      const img = new Image();
      img.onload = async () => {
        try {
          const needsByWidth = img.width > MAX_WIDTH;
          if (!forceResize && !needsByWidth) {
            resolve(file);
            return;
          }
          let scale = Math.min(1, MAX_WIDTH / img.width);
          if (!isFinite(scale) || scale <= 0) scale = 1;
          let outFile = file;
          for (let attempt = 0; attempt < 7; attempt++) {
            const canvas = document.createElement('canvas');
            canvas.width = Math.max(1, Math.round(img.width * scale));
            canvas.height = Math.max(1, Math.round(img.height * scale));
            const ctx = canvas.getContext('2d');
            if (!ctx) break;
            ctx.drawImage(img, 0, 0, canvas.width, canvas.height);
            const quality = Math.max(0.45, 0.9 - (attempt * 0.08));
            const blob = await canvasToBlob(canvas, file.type, quality);
            if (!blob) break;
            outFile = new File([blob], file.name, { type: file.type });
            if (outFile.size <= MAX_UPLOAD) {
              resolve(outFile);
              return;
            }
            scale *= 0.85;
          }
          resolve(outFile);
        } catch (_) {
          resolve(file);
        } finally {
          URL.revokeObjectURL(objectUrl);
        }
      };
      img.onerror = () => {
        URL.revokeObjectURL(objectUrl);
        resolve(file);
      };
      img.src = objectUrl;
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
    const validItems = items.filter(it => it.file && it.file.size > 0);
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
          const folderPromises = [];
          if (addToFolderName && ids.length > 0 && window.ImageKprFolders) {
            folderPromises.push(window.ImageKprFolders.addToFolder(addToFolderName, ids));
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
              folderPromises.push(window.ImageKprFolders.addToFolder(it.folder, [id]));
            }
          });
          const afterFolders = () => {
            /* Named folder filter hides images not in that folder’s list; reset to All after upload. */
            const folderFilter = document.getElementById('folder-filter');
            if (folderFilter) folderFilter.value = '';
            populateFolderIcons('');
            gridState.page = 1;
            refreshGrid(false);
            loadStats();
            setTimeout(loadStats, 300);
          };
          if (folderPromises.length) {
            Promise.all(folderPromises).then(afterFolders).catch(() => afterFolders());
          } else {
            afterFolders();
          }
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

  function processAndUpload(items, addToFolderName, forceResizeBig) {
    Promise.all(items.map(it => resizeIfNeeded(it.file, forceResizeBig))).then(resized => {
      const updated = items.map((it, i) => ({ ...it, file: resized[i] || it.file }));
      uploadFiles(updated, addToFolderName);
    });
  }

  function askOversizeDecision(tooBigCount) {
    const msg = tooBigCount + ' file(s) are above your ' + MAX_UPLOAD_MB + 'MB upload limit.\n\nContinue to auto-resize and upload, or Cancel to stop this batch.';
    return window.confirm(msg);
  }

  function showUploadConfirmModal(files) {
    const arr = Array.from(files).filter(f => f.type.startsWith('image/'));
    if (arr.length === 0) return;
    const tooBig = arr.filter(f => f.size > MAX_UPLOAD);
    const forceResizeBig = tooBig.length > 0;
    if (forceResizeBig && !askOversizeDecision(tooBig.length)) {
      showToast('Upload cancelled');
      return;
    }

    const dialog = document.getElementById('upload-confirm-dialog');
    const grid = document.getElementById('upload-confirm-grid');
    const countEl = document.getElementById('upload-confirm-count');
    const uploadBtn = document.getElementById('upload-confirm-upload');
    const cancelBtn = document.getElementById('upload-confirm-cancel');
    const folderSelect = document.getElementById('upload-add-to-folder-select');
    const folderNewInput = document.getElementById('upload-add-to-folder-new');

    let pendingItems = arr.map(f => ({ file: f, newName: '', tags: [], folder: null }));
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
          window.ImageKprFolders.createFolder(name).then(() => {
            item.folder = name;
            showToast('Will add to ' + name);
            renderGrid();
          }).catch(err => showToast(err.message || 'Failed', true));
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
      if (pendingItems.length > 0) processAndUpload(pendingItems, addToFolderName, forceResizeBig);
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
          window.ImageKprFolders.createFolder(name).then(() => {
            item.folder = name;
            showToast('Will add to ' + name);
            renderList();
          }).catch(err => showToast(err.message || 'Failed', true));
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
            const fp = [];
            Object.entries(folderMap).forEach(([name, idArr]) => {
              if (window.ImageKprFolders) fp.push(window.ImageKprFolders.addToFolder(name, idArr));
            });
            Promise.all(fp).then(() => {
              showToast('Imported ' + (data.imported || 0) + ' image(s)');
              loadInbox();
              loadStats();
              refreshGrid(false);
            }).catch(() => {
              showToast('Imported images; some folder assignments may have failed', true);
              loadInbox();
              loadStats();
              refreshGrid(false);
            });
          } else {
            showToast(data.error || 'Import failed', true);
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

    function addFolderIcon(label, value, title, iconSvgOrFolder, isDropTarget) {
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
      if (isDropTarget && window.ImageKprFolders) {
        const clearDropState = () => wrap.classList.remove('folder-drop-over');
        wrap.addEventListener('dragenter', (e) => {
          if (!e.dataTransfer) return;
          const types = Array.from(e.dataTransfer.types || []);
          if (!types.includes(GRID_CARD_DRAG_MIME)) return;
          e.preventDefault();
          wrap.classList.add('folder-drop-over');
        });
        wrap.addEventListener('dragover', (e) => {
          if (!e.dataTransfer) return;
          const types = Array.from(e.dataTransfer.types || []);
          if (!types.includes(GRID_CARD_DRAG_MIME)) return;
          e.preventDefault();
          e.dataTransfer.dropEffect = 'copy';
          wrap.classList.add('folder-drop-over');
        });
        wrap.addEventListener('dragleave', (e) => {
          if (e.relatedTarget && wrap.contains(e.relatedTarget)) return;
          clearDropState();
        });
        wrap.addEventListener('drop', (e) => {
          e.preventDefault();
          clearDropState();
          if (!e.dataTransfer) return;
          const raw = e.dataTransfer.getData(GRID_CARD_DRAG_MIME);
          if (!raw) return;
          let payload = null;
          try {
            payload = JSON.parse(raw);
          } catch (_) {
            payload = null;
          }
          const id = payload ? Number(payload.id) : NaN;
          if (!Number.isFinite(id) || id < 1) return;
          const imageName = payload && payload.filename ? String(payload.filename) : ('image-' + id);
          window.ImageKprFolders.addToFolder(value, [id]).then(() => {
            showToast('Added "' + imageName + '" to folder "' + value + '"');
          }).catch((err) => {
            showToast((err && err.message) || 'Failed to add image to folder', true);
          });
        });
      }
      container.appendChild(wrap);
    }

    addFolderIcon('All', '', 'Show all images', 'folder', false);
    addFolderIcon('Last uploaded', LATEST_FILTER, 'Show only the last uploaded batch of images', CLOCK_SVG, false);
    addFolderIcon('Uncategorized', UNCATEGORIZED_FILTER, 'Show images not in any folder', 'folder', false);
    Object.keys(data).sort().forEach(name => {
      const label = name + ' (' + (data[name]?.length || 0) + ')';
      addFolderIcon(label, name, label, 'folder', true);
    });
  }

  function renderManageFoldersList() {
    const listEl = document.getElementById('manage-folders-list');
    if (!listEl || !window.ImageKprFolders) return;
    const data = window.ImageKprFolders.load();
    listEl.innerHTML = '';
    Object.keys(data).sort((a, b) => a.localeCompare(b)).forEach(name => {
      const ids = data[name] || [];
      const div = document.createElement('div');
      const span = document.createElement('span');
      span.textContent = name + ' (' + ids.length + ')';
      const renameBtn = document.createElement('button');
      renameBtn.type = 'button';
      renameBtn.className = 'manage-rename ikpr-btn-folders ikpr-btn-compact';
      renameBtn.dataset.name = name;
      renameBtn.textContent = 'Rename';
      const delBtn = document.createElement('button');
      delBtn.type = 'button';
      delBtn.className = 'manage-delete ikpr-btn-delete ikpr-btn-compact';
      delBtn.dataset.name = name;
      delBtn.textContent = 'Delete';
      div.appendChild(span);
      div.appendChild(document.createTextNode(' '));
      div.appendChild(renameBtn);
      div.appendChild(document.createTextNode(' '));
      div.appendChild(delBtn);
      listEl.appendChild(div);
    });
  }

  function syncMaintenanceUiFromWhoami() {
    fetchJSON(API_BASE + '/whoami.php').then(d => {
      if (Number.isFinite(Number(d.upload_max_bytes)) && Number(d.upload_max_bytes) > 0) {
        MAX_UPLOAD = Number(d.upload_max_bytes);
      }
      if (Number.isFinite(Number(d.upload_size_mb)) && Number(d.upload_size_mb) > 0) {
        MAX_UPLOAD_MB = Number(d.upload_size_mb);
      }
      const hintEl = document.querySelector('.upload-zone-hint');
      if (hintEl) {
        hintEl.textContent = 'Files above your ' + MAX_UPLOAD_MB + 'MB limit can be auto-resized if you choose Continue.';
      }
      const on = d.maintenance === true;
      if (on) {
        document.body.classList.add('ikpr-maintenance');
        if (!document.querySelector('.ikpr-maintenance-banner')) {
          const b = document.createElement('div');
          b.className = 'ikpr-maintenance-banner';
          b.setAttribute('role', 'alert');
          b.textContent = typeof d.maintenance_message === 'string' ? d.maintenance_message : '';
          document.body.insertBefore(b, document.body.firstChild);
        }
      } else {
        document.body.classList.remove('ikpr-maintenance');
        document.querySelectorAll('.ikpr-maintenance-banner').forEach(el => el.remove());
      }
    }).catch(() => {});
  }

  document.addEventListener('DOMContentLoaded', async () => {
    try {
      if (window.ImageKprFolders && window.ImageKprFolders.refresh) {
        await window.ImageKprFolders.refresh();
      }
    } catch (_) {}
    syncMaintenanceUiFromWhoami();
    /* Reset folder filter on load (IDs are server-backed; avoid a stale hidden filter). */
    const folderFilterBoot = document.getElementById('folder-filter');
    if (folderFilterBoot) folderFilterBoot.value = '';
    populateFolderIcons();
    populateSortPills();
    if (window.ImageKprFolders) window.ImageKprFolders.onChange = () => { populateFolderIcons(); refreshGrid(false); };
    populateTagsRow();
    document.getElementById('manage-folders-btn').addEventListener('click', () => {
      const d = document.getElementById('manage-folders-dialog');
      renderManageFoldersList();
      d.hidden = false;
    });
    document.getElementById('manage-close').addEventListener('click', () => { document.getElementById('manage-folders-dialog').hidden = true; });
    document.getElementById('manage-create-folder').addEventListener('click', () => {
      const n = document.getElementById('new-folder-name').value.trim();
      if (!n || !window.ImageKprFolders) return;
      window.ImageKprFolders.createFolder(n).then(() => {
        renderManageFoldersList();
        populateFolderIcons();
        document.getElementById('new-folder-name').value = '';
        document.getElementById('manage-folders-dialog').hidden = true;
        showToast('Folder "' + n + '" created');
      }).catch(err => {
        if (err.status === 409) {
          showToast('Folder already exists', true);
        } else {
          showToast(err.message || 'Failed', true);
        }
      });
    });
    document.getElementById('manage-folders-list').addEventListener('click', async (e) => {
      const name = e.target.dataset.name;
      if (!name || !window.ImageKprFolders) return;
      if (e.target.classList.contains('manage-rename')) {
        addToFolderDialog(name).then(newName => {
          if (!newName || newName === name) return;
          window.ImageKprFolders.renameFolder(name, newName).then(() => {
            const ff = document.getElementById('folder-filter');
            if (ff && ff.value === name) ff.value = newName;
            renderManageFoldersList();
            populateFolderIcons();
            showToast('Renamed to "' + newName + '"');
          }).catch(err => showToast(err.message || 'Rename failed', true));
        });
      } else if (e.target.classList.contains('manage-delete')) {
        if (!(await confirmDialog('Delete folder "' + name + '"? Items will be removed from this folder.'))) return;
        window.ImageKprFolders.deleteFolder(name).then(() => {
          const ff = document.getElementById('folder-filter');
          if (ff && ff.value === name) ff.value = '';
          renderManageFoldersList();
          populateFolderIcons();
          refreshGrid(false);
          showToast('Folder deleted');
        }).catch(err => showToast(err.message || 'Delete failed', true));
      }
    });
    const origAddToFolder = window.ImageKprFolders.addToFolder;
    window.ImageKprFolders.addToFolder = (folderNameOrIds, idsMaybe) => {
      if (idsMaybe !== undefined) {
        /* Two args: (folderName, ids) – direct add from upload, no dialog */
        return origAddToFolder(folderNameOrIds, idsMaybe);
      }
      /* One arg: (ids) – bulk action, show select of pre-existing folders */
      return addToFolderSelectDialog().then(chosen => {
        if (chosen) {
          return origAddToFolder(chosen, folderNameOrIds).then(() => {
            showToast('Added');
            populateFolderIcons();
          });
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
      const ssPlayer = document.getElementById('slideshow-player');
      if (ssPlayer && !ssPlayer.hidden) return;
      const modalFs = document.getElementById('modal-fullscreen');
      if (modalFs && !modalFs.hidden) return;
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
      const filename = img.alt || 'image';
      const src = img.src.replace(/^http:\/\//i, 'https://');
      fetch(src)
        .then(r => r.blob())
        .then(blob => {
          const url = URL.createObjectURL(blob);
          const a = document.createElement('a');
          a.href = url;
          a.download = filename;
          document.body.appendChild(a);
          a.click();
          a.remove();
          URL.revokeObjectURL(url);
        })
        .catch(err => {
          const a = document.createElement('a');
          a.href = src;
          a.download = filename;
          document.body.appendChild(a);
          a.click();
          a.remove();
        });
    });
    const modalFullscreenBtn = document.getElementById('modal-fullscreen-btn');
    if (modalFullscreenBtn) modalFullscreenBtn.addEventListener('click', openModalFullscreen);
    document.getElementById('bulk-clear').addEventListener('click', () => {
      selectedIds.clear();
      selectedImages.clear();
      selectedOrder = [];
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
        body: JSON.stringify({ ids: getSelectedIdsOrdered() })
      }).then(r => r.json()).then(data => {
        if (data.success) {
          selectedIds.clear();
          selectedImages.clear();
          selectedOrder = [];
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
      window.open(API_BASE + '/download_bulk.php?ids=' + getSelectedIdsOrdered().join(','), '_blank');
    });
    document.getElementById('bulk-slideshow').addEventListener('click', () => {
      if (selectedIds.size === 0) return;
      openSlideshowSettingsPanel();
    });
    document.getElementById('slideshow-settings-backdrop').addEventListener('click', closeSlideshowSettingsPanel);
    document.getElementById('slideshow-settings-cancel').addEventListener('click', closeSlideshowSettingsPanel);
    document.getElementById('slideshow-start').addEventListener('click', startSlideshowFromForm);
    document.querySelectorAll('input[name="slideshow-advance"]').forEach((el) => {
      el.addEventListener('change', syncSlideshowSettingsForm);
    });
    document.getElementById('slideshow-autoloop').addEventListener('change', syncSlideshowSettingsForm);
    document.addEventListener('keydown', (e) => {
      if (e.key !== 'Escape') return;
      const player = document.getElementById('slideshow-player');
      if (player && !player.hidden) return;
      const wrap = document.getElementById('slideshow-settings-wrap');
      if (!wrap || wrap.hidden || !wrap.classList.contains('slideshow-settings-open')) return;
      e.preventDefault();
      closeSlideshowSettingsPanel();
    }, true);
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
        body: JSON.stringify({ ids: getSelectedIdsOrdered(), base })
      }).then(r => r.json()).then(data => {
        document.getElementById('rename-dialog').hidden = true;
        if (data.success) {
          selectedIds.clear();
          selectedImages.clear();
          selectedOrder = [];
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

    document.getElementById('bulk-select-all').addEventListener('click', selectAllVisibleInGrid);
    document.getElementById('bulk-select-all-bar').addEventListener('click', selectAllVisibleInGrid);

    (function initGridScaleToggle() {
      const KEY = 'imagekprMainGridScale';
      const root = document.documentElement;
      const options = ['0.5', '0.75', '1', '1.5', '2'];
      const radios = Array.from(document.querySelectorAll('input[name="grid-scale"]'));
      if (radios.length === 0 || !root) return;
      function applyScale(scale, persist) {
        const value = options.includes(scale) ? scale : '1';
        root.style.setProperty('--grid-scale', value);
        radios.forEach((r) => { r.checked = r.value === value; });
        if (!persist) return;
        try {
          localStorage.setItem(KEY, value);
        } catch (_) {}
      }
      let initial = '1';
      try {
        const saved = localStorage.getItem(KEY);
        if (saved && options.includes(saved)) initial = saved;
      } catch (_) {}
      applyScale(initial, false);
      radios.forEach((r) => {
        r.addEventListener('change', () => {
          if (!r.checked) return;
          applyScale(r.value, true);
        });
      });
    })();

    (function initSelectionThumbsLargeToggle() {
      const banner = document.getElementById('selection-banner');
      const cb = document.getElementById('selection-thumbs-large');
      if (!banner || !cb) return;
      const key = 'imagekprSelectionThumbsLarge';
      function applySelectionThumbsLarge() {
        banner.classList.toggle('selection-thumbs-large', cb.checked);
        try {
          localStorage.setItem(key, cb.checked ? '1' : '0');
        } catch (_) {}
      }
      try {
        cb.checked = localStorage.getItem(key) === '1';
      } catch (_) {}
      applySelectionThumbsLarge();
      cb.addEventListener('change', applySelectionThumbsLarge);
    })();

    (function initSelectionRowDragDrop() {
      const row = document.getElementById('selection-row');
      if (!row) return;
      let dragFrom = null;
      let dragEl = null;
      let previewTargetId = null;
      let dragStartOrder = [];
      let dropCommitted = false;
      let lastPointerX = null;
      let lastReorderAt = 0;

      function clearReflowTransitions() {
        row.querySelectorAll('.selection-thumb').forEach((el) => {
          el.style.transition = '';
        });
      }

      function animateThumbReflow(mutator) {
        const thumbsBefore = Array.from(row.querySelectorAll('.selection-thumb'));
        const firstRects = new Map();
        thumbsBefore.forEach((el) => firstRects.set(el, el.getBoundingClientRect()));
        mutator();
        const thumbsAfter = Array.from(row.querySelectorAll('.selection-thumb'));
        thumbsAfter.forEach((el) => {
          const fr = firstRects.get(el);
          if (!fr) return;
          const lr = el.getBoundingClientRect();
          const dx = fr.left - lr.left;
          const dy = fr.top - lr.top;
          if (!dx && !dy) return;
          el.style.transition = 'none';
          el.style.transform = 'translate(' + dx + 'px,' + dy + 'px)';
        });
        row.offsetWidth;
        thumbsAfter.forEach((el) => {
          if (!el.style.transform) return;
          el.style.transition = 'transform 110ms ease';
          el.style.transform = '';
        });
        setTimeout(clearReflowTransitions, 140);
      }

      function applySelectionOrderFromDom() {
        const domIds = Array.from(row.querySelectorAll('.selection-thumb'))
          .map((el) => Number(el.dataset.id))
          .filter((id) => selectedIds.has(id));
        if (domIds.length > 0) selectedOrder = domIds;
      }

      row.addEventListener('dragstart', (e) => {
        const thumb = e.target.closest('.selection-thumb');
        if (!thumb) return;
        dragFrom = Number(thumb.dataset.id);
        dragEl = thumb;
        previewTargetId = null;
        dropCommitted = false;
        dragStartOrder = selectedOrder.slice();
        lastPointerX = null;
        lastReorderAt = 0;
        e.dataTransfer.setData('text/plain', String(dragFrom));
        e.dataTransfer.effectAllowed = 'move';
        thumb.classList.add('selection-thumb-dragging');
      });
      row.addEventListener('dragend', () => {
        row.querySelectorAll('.selection-thumb-dragging').forEach((el) => el.classList.remove('selection-thumb-dragging'));
        row.querySelectorAll('.selection-thumb-dropeffect').forEach((el) => el.classList.remove('selection-thumb-dropeffect'));
        clearReflowTransitions();
        if (!dropCommitted && dragStartOrder.length > 0) {
          selectedOrder = dragStartOrder.slice();
          updateSelectionBanner();
        }
        dragFrom = null;
        dragEl = null;
        previewTargetId = null;
        dragStartOrder = [];
        dropCommitted = false;
        lastPointerX = null;
        lastReorderAt = 0;
      });
      row.addEventListener('dragover', (e) => {
        if (!dragEl || dragFrom == null) return;
        e.preventDefault();
        e.dataTransfer.dropEffect = 'move';
        const EDGE_TRIGGER_RATIO = 0.16;
        const MIN_REORDER_INTERVAL_MS = 95;
        const x = e.clientX;
        const now = Date.now();
        const dx = lastPointerX == null ? 0 : (x - lastPointerX);
        const direction = dx > 0 ? 1 : (dx < 0 ? -1 : 0);
        lastPointerX = x;
        const thumb = e.target.closest('.selection-thumb');
        if (!thumb || !row.contains(thumb)) {
          const last = row.querySelector('.selection-thumb:last-child');
          if (!last || last === dragEl) return;
          if (direction <= 0) return;
          if (now - lastReorderAt < MIN_REORDER_INTERVAL_MS) return;
          if (previewTargetId === '__end__') return;
          previewTargetId = '__end__';
          lastReorderAt = now;
          row.querySelectorAll('.selection-thumb-dropeffect').forEach((el) => el.classList.remove('selection-thumb-dropeffect'));
          last.classList.add('selection-thumb-dropeffect');
          animateThumbReflow(() => {
            row.appendChild(dragEl);
          });
          return;
        }
        if (thumb === dragEl) {
          const r = thumb.getBoundingClientRect();
          if (direction > 0 && e.clientX > r.left + (r.width * (0.5 + EDGE_TRIGGER_RATIO))) {
            const last2 = row.querySelector('.selection-thumb:last-child');
            if (last2 && last2 !== dragEl && previewTargetId !== '__end__' && now - lastReorderAt >= MIN_REORDER_INTERVAL_MS) {
              previewTargetId = '__end__';
              lastReorderAt = now;
              row.querySelectorAll('.selection-thumb-dropeffect').forEach((el) => el.classList.remove('selection-thumb-dropeffect'));
              last2.classList.add('selection-thumb-dropeffect');
              animateThumbReflow(() => {
                row.appendChild(dragEl);
              });
            }
          }
          return;
        }
        const overId = Number(thumb.dataset.id);
        if (!overId || overId === dragFrom) return;
        const dragRect = dragEl.getBoundingClientRect();
        const overRect = thumb.getBoundingClientRect();
        const draggingRight = direction > 0 || (direction === 0 && dragRect.left < overRect.left);
        const draggingLeft = direction < 0 || (direction === 0 && dragRect.left > overRect.left);
        if (draggingRight) {
          const leftGate = overRect.left + (overRect.width * EDGE_TRIGGER_RATIO);
          if (e.clientX < leftGate) return;
        } else if (draggingLeft) {
          const rightGate = overRect.right - (overRect.width * EDGE_TRIGGER_RATIO);
          if (e.clientX > rightGate) return;
        } else {
          return;
        }
        if (now - lastReorderAt < MIN_REORDER_INTERVAL_MS) return;
        if (overId === previewTargetId) return;
        previewTargetId = overId;
        lastReorderAt = now;
        row.querySelectorAll('.selection-thumb-dropeffect').forEach((el) => el.classList.remove('selection-thumb-dropeffect'));
        thumb.classList.add('selection-thumb-dropeffect');
        animateThumbReflow(() => {
          row.insertBefore(dragEl, thumb);
        });
      });
      row.addEventListener('drop', (e) => {
        e.preventDefault();
        row.querySelectorAll('.selection-thumb-dropeffect').forEach((el) => el.classList.remove('selection-thumb-dropeffect'));
        if (!dragEl || dragFrom == null) return;
        applySelectionOrderFromDom();
        dropCommitted = true;
        updateSelectionBanner();
      });
    })();

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
