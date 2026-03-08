/* ImageKpr - Favourites & lists (localStorage) */
(function () {
  'use strict';

  const KEY = 'imagekpr_lists';

  function load() {
    try {
      const s = localStorage.getItem(KEY);
      return s ? JSON.parse(s) : { 'Favourites': [] };
    } catch (_) {
      return { 'Favourites': [] };
    }
  }

  function save(data) {
    localStorage.setItem(KEY, JSON.stringify(data));
    if (window.ImageKprLists && window.ImageKprLists.onChange) window.ImageKprLists.onChange();
  }

  function getFilterIds() {
    const sel = document.getElementById('list-filter');
    if (!sel || !sel.value) return null;
    const data = load();
    return data[sel.value] || null;
  }

  function addToList(listName, ids) {
    const data = load();
    if (!data[listName]) data[listName] = [];
    ids.forEach(id => {
      if (!data[listName].includes(id)) data[listName].push(id);
    });
    save(data);
  }

  function removeFromList(listName, id) {
    const data = load();
    if (data[listName]) {
      data[listName] = data[listName].filter(x => x !== id);
      save(data);
    }
  }

  function toggleInList(listName, id) {
    const data = load();
    if (!data[listName]) data[listName] = [];
    const i = data[listName].indexOf(id);
    if (i >= 0) data[listName].splice(i, 1);
    else data[listName].push(id);
    save(data);
  }

  function isInList(listName, id) {
    const data = load();
    return data[listName] ? data[listName].includes(id) : false;
  }

  function getDefaultList() {
    const data = load();
    const sel = document.getElementById('list-filter');
    return (sel && sel.value && data[sel.value]) ? sel.value : 'Favourites';
  }

  window.ImageKprLists = {
    load,
    save,
    getFilterIds,
    addToList,
    removeFromList,
    toggleInList,
    isInList,
    getDefaultList,
    onChange: null
  };
})();
