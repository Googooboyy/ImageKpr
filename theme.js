/* ImageKpr — theme toggle + account Appearance (requires meta[name="ikpr-app-csrf"]) */
(function () {
  'use strict';

  var LS_KEY = 'ikpr-theme-override';

  function getCsrf() {
    var m = document.querySelector('meta[name="ikpr-app-csrf"]');
    return m && m.getAttribute('content') ? m.getAttribute('content') : '';
  }

  function apiThemeUrl() {
    if (typeof window.IKPR_THEME_API === 'string' && window.IKPR_THEME_API !== '') {
      return window.IKPR_THEME_API;
    }
    return 'api/account_theme.php';
  }

  function setDocumentTheme(theme) {
    if (theme !== 'light' && theme !== 'dark') {
      return;
    }
    document.documentElement.setAttribute('data-theme', theme);
    try {
      localStorage.setItem(LS_KEY, theme);
    } catch (e) {}
  }

  function postTheme(theme) {
    var csrf = getCsrf();
    if (!csrf) {
      return;
    }
    var fd = new FormData();
    fd.append('app_csrf', csrf);
    fd.append('theme', theme);
    fetch(apiThemeUrl(), { method: 'POST', body: fd, credentials: 'same-origin' }).catch(function () {});
  }

  function syncToggleUi(btn) {
    if (!btn) {
      return;
    }
    var t = document.documentElement.getAttribute('data-theme') || 'light';
    var dark = t === 'dark';
    btn.setAttribute('aria-pressed', dark ? 'true' : 'false');
    btn.setAttribute('aria-label', dark ? 'Switch to light mode' : 'Switch to dark mode');
  }

  function syncSelectUi(sel) {
    if (!sel) {
      return;
    }
    var t = document.documentElement.getAttribute('data-theme') || 'light';
    sel.value = t === 'dark' ? 'dark' : 'light';
  }

  function flipTheme() {
    var cur = document.documentElement.getAttribute('data-theme') || 'light';
    var next = cur === 'dark' ? 'light' : 'dark';
    setDocumentTheme(next);
    postTheme(next);
    syncToggleUi(document.getElementById('ikpr-theme-toggle'));
    syncSelectUi(document.getElementById('theme_preference'));
  }

  document.addEventListener('DOMContentLoaded', function () {
    var btn = document.getElementById('ikpr-theme-toggle');
    if (btn) {
      btn.addEventListener('click', flipTheme);
      syncToggleUi(btn);
    }
    var sel = document.getElementById('theme_preference');
    if (sel) {
      sel.addEventListener('change', function () {
        var v = sel.value === 'dark' ? 'dark' : 'light';
        setDocumentTheme(v);
        postTheme(v);
        syncToggleUi(document.getElementById('ikpr-theme-toggle'));
      });
      syncSelectUi(sel);
    }
  });
})();
