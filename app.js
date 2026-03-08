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

  function renderCard(img) {
    const article = document.createElement('article');
    article.className = 'grid-item card';
    article.dataset.id = img.id;
    article.dataset.url = img.url;
    article.dataset.filename = img.filename;
    const name = truncate(img.filename, 24);
    const size = formatBytes(img.size_bytes || 0);
    const date = formatDate(img.date_uploaded);
    article.innerHTML =
      '<div class="card-inner">' +
      '<img class="card-img" data-src="' + (img.url || '') + '" alt="' + (img.filename || 'Image') + '" loading="lazy">' +
      '<div class="card-info">' +
      '<span class="card-name" title="' + (img.filename || '') + '">' + name + '</span>' +
      '<span class="card-meta">' + size + ' • ' + date + '</span>' +
      '</div>' +
      '<button type="button" class="card-expand" aria-label="View full size"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 3h6v6M9 21H3v-6M21 3l-7 7M3 21l7-7"/></svg></button>' +
      '</div>';
    const inner = article.querySelector('.card-inner');
    const expandBtn = article.querySelector('.card-expand');
    inner.addEventListener('click', () => copyUrl(img.url));
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

  let gridState = { page: 1, perPage: 50, sort: 'date_desc', search: '', total: 0, loading: false };

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
      imgs.forEach(el => {
        el.src = el.dataset.src || '';
        el.removeAttribute('data-src');
      });
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

  function refreshGrid(append) {
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
    fetchJSON(API_BASE + '/images.php?per_page=1000&sort=date_desc').then(data => {
      const filtered = (data.images || []).filter(img => ids.includes(Number(img.id)));
      const grid = document.getElementById('grid');
      if (!append) grid.innerHTML = '';
      filtered.forEach(img => grid.appendChild(renderCard(img)));
      gridState.total = filtered.length;
      document.getElementById('load-more').innerHTML = '';
      const imgs = grid.querySelectorAll('img[data-src]');
      imgs.forEach(el => { el.src = el.dataset.src || ''; el.removeAttribute('data-src'); });
    }).catch(() => { if (!append) document.getElementById('grid').innerHTML = '<p class="empty">No images in this list.</p>'; });
  }

  function debounce(fn, ms) {
    let t;
    return function (...args) { clearTimeout(t); t = setTimeout(() => fn.apply(this, args), ms); };
  }

  function loadStats() {
    fetchJSON(API_BASE + '/stats.php').then(data => {
      document.getElementById('stat-total-images').textContent = data.total_images;
      document.getElementById('stat-total-storage').textContent = data.total_storage_gb + ' GB';
      const row = document.getElementById('last10-row');
      row.innerHTML = '';
      (data.last_10 || []).forEach(img => {
        const wrap = document.createElement('div');
        wrap.className = 'last10-thumb';
        wrap.style.cssText = 'position:relative;width:56px;height:56px;cursor:pointer;overflow:hidden;border-radius:4px;flex-shrink:0;border:1px solid #eee';
        const im = document.createElement('img');
        im.src = img.url;
        im.alt = img.filename;
        im.style.cssText = 'width:100%;height:100%;object-fit:cover';
        im.addEventListener('click', e => { e.stopPropagation(); copyUrl(img.url); });
        const expand = document.createElement('button');
        expand.type = 'button';
        expand.className = 'last10-expand';
        expand.innerHTML = '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 3h6v6M9 21H3v-6M21 3l-7 7M3 21l7-7"/></svg>';
        expand.setAttribute('aria-label', 'View full size');
        expand.addEventListener('click', e => { e.stopPropagation(); openModal(img); });
        wrap.appendChild(im);
        wrap.appendChild(expand);
        row.appendChild(wrap);
      });
    }).catch(() => {
      document.getElementById('stat-total-images').textContent = '—';
      document.getElementById('stat-total-storage').textContent = '—';
    });
  }

  document.addEventListener('DOMContentLoaded', () => {
    loadStats();
    refreshGrid(false);

    document.getElementById('search').addEventListener('input', debounce(() => {
      gridState.search = document.getElementById('search').value.trim();
      gridState.page = 1;
      refreshGrid(false);
    }, 1000));

    document.getElementById('sort').addEventListener('change', () => {
      gridState.sort = document.getElementById('sort').value;
      gridState.page = 1;
      refreshGrid(false);
    });

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
    document.getElementById('modal-delete').addEventListener('click', () => {
      if (!currentModalImg) return;
      if (!confirm('Delete this image?')) return;
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
