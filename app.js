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

  function openModal(img) {
    currentModalImg = img;
    const modal = document.getElementById('modal');
    const imgEl = document.getElementById('modal-img');
    imgEl.src = img.url;
    imgEl.alt = img.filename;
    modal.hidden = false;
    document.body.style.overflow = 'hidden';
  }

  function closeModal() {
    document.getElementById('modal').hidden = true;
    document.body.style.overflow = '';
  }

  function loadGrid(params) {
    const q = new URLSearchParams(params || {});
    fetchJSON(API_BASE + '/images.php?' + q).then(data => {
      const grid = document.getElementById('grid');
      grid.innerHTML = '';
      (data.images || []).forEach(img => {
        grid.appendChild(renderCard(img));
      });
      // Lazy load images
      const imgs = grid.querySelectorAll('img[data-src]');
      imgs.forEach(el => {
        el.src = el.dataset.src || '';
        el.removeAttribute('data-src');
      });
    }).catch(() => {
      document.getElementById('grid').innerHTML = '<p class="empty">No images yet. Upload some!</p>';
    });
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
    loadGrid({ page: 1, per_page: 50, sort: 'date_desc' });

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
  });

  window.ImageKpr = { loadGrid, loadStats, copyUrl, showToast, openModal, closeModal };
})();
