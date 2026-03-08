/* ImageKpr - Folders (localStorage) */
(function () {
  'use strict';

  const KEY = 'imagekpr_folders';
  const LEGACY_KEY = 'imagekpr_lists'; /* Migration: lists = folders */

  function withoutFavourites(data) {
    if (!data || typeof data !== 'object') return {};
    const out = {};
    Object.keys(data).filter(k => k !== 'Favourites').forEach(k => { out[k] = data[k] || []; });
    return out;
  }

  function load() {
    try {
      let s = localStorage.getItem(KEY);
      if (s) return withoutFavourites(JSON.parse(s));
      /* Migrate from legacy "lists" key (lists = folders) */
      const legacy = localStorage.getItem(LEGACY_KEY);
      if (legacy) {
        const data = withoutFavourites(JSON.parse(legacy));
        if (Object.keys(data).length > 0) {
          localStorage.setItem(KEY, JSON.stringify(data));
          return data;
        }
      }
      return {};
    } catch (_) {
      return {};
    }
  }

  function save(data) {
    localStorage.setItem(KEY, JSON.stringify(data));
    if (window.ImageKprFolders && window.ImageKprFolders.onChange) window.ImageKprFolders.onChange();
  }

  function getFilterIds() {
    const sel = document.getElementById('folder-filter');
    if (!sel || !sel.value) return null;
    const data = load();
    return data[sel.value] || null;
  }

  function addToFolder(folderName, ids) {
    const data = load();
    if (!data[folderName]) data[folderName] = [];
    ids.forEach(id => {
      if (!data[folderName].includes(id)) data[folderName].push(id);
    });
    save(data);
  }

  function removeFromFolder(folderName, id) {
    const data = load();
    if (data[folderName]) {
      data[folderName] = data[folderName].filter(x => x !== id);
      save(data);
    }
  }

  function toggleInFolder(folderName, id) {
    const data = load();
    if (!data[folderName]) data[folderName] = [];
    const i = data[folderName].indexOf(id);
    if (i >= 0) data[folderName].splice(i, 1);
    else data[folderName].push(id);
    save(data);
  }

  function isInFolder(folderName, id) {
    const data = load();
    return data[folderName] ? data[folderName].includes(id) : false;
  }

  function getDefaultFolder() {
    const data = load();
    const sel = document.getElementById('folder-filter');
    if (sel && sel.value && data[sel.value]) return sel.value;
    const names = Object.keys(data).sort();
    return names.length ? names[0] : null;
  }

  window.ImageKprFolders = {
    load,
    save,
    getFilterIds,
    addToFolder,
    removeFromFolder,
    toggleInFolder,
    isInFolder,
    getDefaultFolder,
    onChange: null
  };
})();
