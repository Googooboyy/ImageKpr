/* ImageKpr — folders (Phase 13: MySQL via api/folders.php) */
(function () {
  'use strict';

  const API_BASE = 'api';

  let _cache = {};

  function normIds(ids) {
    return (ids || []).map(id => Number(id)).filter(id => id >= 1);
  }

  function foldersFetch(method, body) {
    return fetch(API_BASE + '/folders.php', {
      method: method,
      credentials: 'same-origin',
      headers: { 'Content-Type': 'application/json' },
      body: body !== undefined ? JSON.stringify(body) : undefined
    }).then(r => {
      if (r.status === 401) {
        window.location.href = 'login.php';
        const e = new Error('Unauthorized');
        e.status = 401;
        throw e;
      }
      return r.text().then(text => {
        let j = {};
        try {
          j = text ? JSON.parse(text) : {};
        } catch (_) {}
        if (!r.ok) {
          const err = new Error(j.error || text || ('HTTP ' + r.status));
          err.status = r.status;
          err.payload = j;
          throw err;
        }
        return j;
      });
    });
  }

  function applyFoldersPayload(folders) {
    const out = {};
    if (folders && typeof folders === 'object') {
      Object.keys(folders).forEach(k => {
        out[k] = normIds(folders[k]);
      });
    }
    _cache = out;
  }

  function notify() {
    if (window.ImageKprFolders && window.ImageKprFolders.onChange) {
      window.ImageKprFolders.onChange();
    }
  }

  function refresh() {
    return foldersFetch('GET')
      .then(d => {
        applyFoldersPayload(d.folders);
        notify();
        return _cache;
      });
  }

  function load() {
    return _cache;
  }

  function createFolder(name) {
    const n = String(name || '').trim();
    if (!n) {
      return Promise.reject(new Error('Empty folder name'));
    }
    return foldersFetch('POST', { name: n })
      .then(() => refresh())
      .catch(err => {
        if (err.status === 409) {
          return refresh();
        }
        throw err;
      });
  }

  function addToFolder(folderName, ids) {
    const n = String(folderName || '').trim();
    const idList = normIds(Array.isArray(ids) ? ids : [ids]);
    if (!n || idList.length === 0) {
      return Promise.resolve();
    }
    return foldersFetch('PATCH', { action: 'add', name: n, image_ids: idList }).then(() => refresh());
  }

  function removeFromFolder(folderName, idOrIds) {
    const n = String(folderName || '').trim();
    const idList = Array.isArray(idOrIds) ? normIds(idOrIds) : normIds([idOrIds]);
    if (!n || idList.length === 0) {
      return Promise.resolve();
    }
    return foldersFetch('PATCH', { action: 'remove', name: n, image_ids: idList }).then(() => refresh());
  }

  function toggleInFolder(folderName, id) {
    if (isInFolder(folderName, id)) {
      return removeFromFolder(folderName, id);
    }
    return addToFolder(folderName, [id]);
  }

  function isInFolder(folderName, id) {
    const iid = Number(id);
    const arr = _cache[folderName] || [];
    return arr.some(x => Number(x) === iid);
  }

  function renameFolder(oldName, newName) {
    const o = String(oldName || '').trim();
    const nn = String(newName || '').trim();
    if (!o || !nn) {
      return Promise.reject(new Error('Invalid folder name'));
    }
    return foldersFetch('PATCH', { action: 'rename', name: o, new_name: nn }).then(() => refresh());
  }

  function deleteFolder(name) {
    const n = String(name || '').trim();
    if (!n) {
      return Promise.resolve();
    }
    const url = API_BASE + '/folders.php?name=' + encodeURIComponent(n);
    return fetch(url, { method: 'DELETE', credentials: 'same-origin' }).then(r => {
      if (r.status === 401) {
        window.location.href = 'login.php';
        throw new Error('Unauthorized');
      }
      return r.text().then(text => {
        let j = {};
        try {
          j = text ? JSON.parse(text) : {};
        } catch (_) {}
        if (!r.ok) {
          const err = new Error(j.error || text || ('HTTP ' + r.status));
          err.status = r.status;
          throw err;
        }
        return refresh().then(() => j);
      });
    });
  }

  function getFilterIds() {
    const sel = document.getElementById('folder-filter');
    if (!sel || !sel.value) {
      return null;
    }
    const data = load();
    return data[sel.value] || null;
  }

  function getDefaultFolder() {
    const data = load();
    const sel = document.getElementById('folder-filter');
    if (sel && sel.value && data[sel.value]) {
      return sel.value;
    }
    const names = Object.keys(data).sort();
    return names.length ? names[0] : null;
  }

  window.ImageKprFolders = {
    refresh,
    load,
    createFolder,
    renameFolder,
    deleteFolder,
    addToFolder,
    removeFromFolder,
    toggleInFolder,
    isInFolder,
    getFilterIds,
    getDefaultFolder,
    onChange: null
  };
})();
