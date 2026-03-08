/* ImageKpr - Favourites & folders (localStorage) */
(function () {
  'use strict';

  const KEY = 'imagekpr_folders';
  const LEGACY_KEY = 'imagekpr_lists'; /* Migration: lists = folders */

  function load() {
    try {
      let s = localStorage.getItem(KEY);
      if (s) return JSON.parse(s);
      /* Migrate from legacy "lists" key (lists = folders) */
      const legacy = localStorage.getItem(LEGACY_KEY);
      if (legacy) {
        const data = JSON.parse(legacy);
        if (data && typeof data === 'object') {
          localStorage.setItem(KEY, JSON.stringify(data));
          return data;
        }
      }
      return { 'Favourites': [] };
    } catch (_) {
      return { 'Favourites': [] };
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
    return (sel && sel.value && data[sel.value]) ? sel.value : 'Favourites';
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
