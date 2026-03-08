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

  function loadStats() {
    fetchJSON(API_BASE + '/stats.php').then(data => {
      document.getElementById('stat-total-images').textContent = data.total_images;
      document.getElementById('stat-total-storage').textContent = data.total_storage_gb + ' GB';
      const row = document.getElementById('last10-row');
      row.innerHTML = '';
      (data.last_10 || []).forEach(img => {
        const wrap = document.createElement('div');
        wrap.className = 'last10-thumb';
        wrap.style.cssText = 'width:48px;height:48px;cursor:pointer;overflow:hidden;border-radius:4px;flex-shrink:0';
        const im = document.createElement('img');
        im.src = img.url;
        im.alt = img.filename;
        im.style.cssText = 'width:100%;height:100%;object-fit:cover';
        im.addEventListener('click', e => { e.stopPropagation(); copyUrl(img.url); });
        wrap.appendChild(im);
        row.appendChild(wrap);
      });
    }).catch(() => {
      document.getElementById('stat-total-images').textContent = '—';
      document.getElementById('stat-total-storage').textContent = '—';
    });
  }

  document.addEventListener('DOMContentLoaded', () => {
    loadStats();
  });
})();
