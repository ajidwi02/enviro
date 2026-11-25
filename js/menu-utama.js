(function () {
  'use strict';

  var STAGGER_MS = 250;
  var TIMEOUT_MS = 25000;
  var RETRY_DELAYS = [30000, 60000, 120000];
  var LABELS = { online: 'Aktif', loading: 'Memuat', error: 'Gagal', empty: 'Kosong' };

  var cards = Array.prototype.slice.call(document.querySelectorAll('.mu-card'));
  var loadQueue = cards.filter(function (card) {
    return card.getAttribute('data-src');
  });

  function $(selector, root) {
    return (root || document).querySelector(selector);
  }

  function fit(card) {
    var frame = $('.mu-frame', card);
    var view = $('.mu-card__view', card);
    if (!frame || !view) return;

    var w = view.clientWidth;
    var h = view.clientHeight;
    if (!w || !h) return;

    var designW = parseInt(card.getAttribute('data-design-width'), 10) || 1600;
    var scale = w / designW;
    var designH = Math.round(h / scale);

    frame.style.width = designW + 'px';
    frame.style.height = designH + 'px';
    frame.style.transform = 'scale(' + scale + ')';
  }

  function setState(card, state) {
    card.setAttribute('data-state', state);

    var footer = $('[data-role="status"]', card);
    if (footer) footer.textContent = LABELS[state] || '';

    if (state === 'loading') {
      var loaded = $('[data-role="loaded"]', card);
      if (loaded) loaded.textContent = '';
    }

    var row = $('.mu-list__row[data-slot="' + card.getAttribute('data-id') + '"]');
    if (row) {
      row.setAttribute('data-state', state);
      var rowState = $('[data-role="summary-status"]', row);
      if (rowState) rowState.textContent = LABELS[state] || '';
    }

    updateSummary();
  }

  function updateSummary() {
    var counts = { online: 0, loading: 0, error: 0, empty: 0 };
    cards.forEach(function (card) {
      var s = card.getAttribute('data-state');
      if (counts[s] !== undefined) counts[s]++;
    });

    var defs = [
      ['online', 'count-online', 'aktif'],
      ['loading', 'count-loading', 'memuat'],
      ['error', 'count-error', 'gagal'],
      ['empty', 'count-empty', 'kosong']
    ];

    defs.forEach(function (def) {
      var chip = document.querySelector('.mu-chip[data-chip="' + def[0] + '"]');
      var label = document.querySelector('[data-role="' + def[1] + '"]');
      if (label) label.textContent = counts[def[0]] + ' ' + def[2];
      if (chip) chip.hidden = counts[def[0]] === 0;
    });
  }

  function stampLoaded(card) {
    var el = $('[data-role="loaded"]', card);
    var ts = parseInt(card.getAttribute('data-loaded-at'), 10);
    if (!el || !ts) return;
    var date = new Date(ts);
    el.textContent = 'Dimuat ' + pad2(date.getHours()) + ':' + pad2(date.getMinutes());
  }

  function clearTimer(card) {
    if (card._muTimer) {
      clearTimeout(card._muTimer);
      card._muTimer = null;
    }
  }

  function clearRetry(card) {
    if (card._muRetry) {
      clearTimeout(card._muRetry);
      card._muRetry = null;
    }
  }

  function fail(card) {
    clearTimer(card);
    setState(card, 'error');

    var attempt = parseInt(card.getAttribute('data-attempt') || '0', 10);
    if (attempt < RETRY_DELAYS.length) {
      card.setAttribute('data-attempt', String(attempt + 1));
      clearRetry(card);
      card._muRetry = setTimeout(function () { load(card); }, RETRY_DELAYS[attempt]);
    }
  }

  function load(card) {
    var frame = $('.mu-frame', card);
    var url = card.getAttribute('data-src');
    if (!frame || !url) return;

    clearTimer(card);
    clearRetry(card);
    setState(card, 'loading');
    fit(card);

    if (!frame._muWired) {
      frame._muWired = true;

      frame.addEventListener('load', function () {
        clearTimer(card);
        clearRetry(card);
        card.setAttribute('data-attempt', '0');
        card.setAttribute('data-loaded-at', String(Date.now()));
        stampLoaded(card);
        setState(card, 'online');
        fit(card);
      });

      frame.addEventListener('error', function () {
        fail(card);
      });
    }

    card._muTimer = setTimeout(function () { fail(card); }, TIMEOUT_MS);
    frame.src = url;
  }

  function pad2(value) {
    return value < 10 ? '0' + value : String(value);
  }

  function tick() {
    var now = new Date();
    var clock = document.getElementById('mu-clock');
    var date = document.getElementById('mu-date');

    if (clock) {
      clock.textContent = pad2(now.getHours()) + ':' + pad2(now.getMinutes()) + ':' + pad2(now.getSeconds());
      clock.setAttribute('datetime', now.toISOString());
    }

    if (date) {
      date.textContent = new Intl.DateTimeFormat('id-ID', {
        weekday: 'long', day: 'numeric', month: 'long', year: 'numeric'
      }).format(now);
      date.setAttribute('datetime', now.toISOString().slice(0, 10));
    }
  }

  function bindReload() {
    cards.forEach(function (card) {
      var button = $('[data-action="reload"]', card);
      if (!button) return;
      button.addEventListener('click', function () {
        card.setAttribute('data-attempt', '0');
        clearRetry(card);
        load(card);
      });
    });
  }

  function initNavDrawer() {
    var toggle = document.getElementById('muNavToggle');
    var drawer = document.getElementById('leftSidebar');
    var topbar = $('.topnav');
    var scrim = $('.mu-nav-scrim');
    var main = $('.main-content');
    if (!toggle || !drawer) return;

    var isOpen = false;
    var supportsInert = 'inert' in HTMLElement.prototype;

    function visibleFocusables(root) {
      return Array.prototype.slice.call(root.querySelectorAll(
        'a[href], button:not([disabled]), [tabindex]:not([tabindex="-1"])'
      )).filter(function (el) {
        return el.getClientRects().length > 0;
      });
    }

    function drawerFocusables() {
      return visibleFocusables(drawer);
    }

    function sync(open) {
      isOpen = open;
      var scrollbarGap = 0;
      var basePaddingRight = 0;

      if (open && main) {
        scrollbarGap = window.innerWidth - document.documentElement.clientWidth;
        basePaddingRight = parseFloat(window.getComputedStyle(main).paddingRight) || 0;
      }

      document.body.classList.toggle('mu-nav-open', open);

      if (main) {
        main.style.paddingRight = scrollbarGap > 0
          ? (basePaddingRight + scrollbarGap) + 'px'
          : '';
      }

      toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
      toggle.setAttribute('aria-label', open ? 'Tutup menu navigasi' : 'Buka menu navigasi');

      if (open) {
        drawer.removeAttribute('aria-hidden');
      } else {
        drawer.setAttribute('aria-hidden', 'true');
      }

      if (supportsInert) {
        drawer.inert = !open;
        if (main) main.inert = open;
      }

      if (main) {
        if (open) {
          main.setAttribute('aria-hidden', 'true');
        } else {
          main.removeAttribute('aria-hidden');
        }
      }
    }

    function openDrawer() {
      if (isOpen) return;
      sync(true);
      var items = drawerFocusables();
      var firstNav = drawer.querySelector('.nav-link');
      (firstNav || items[0] || drawer).focus({ preventScroll: true });
    }

    function closeDrawer(returnFocus) {
      if (!isOpen) return;
      sync(false);
      if (returnFocus !== false) toggle.focus({ preventScroll: true });
    }

    sync(false);

    toggle.addEventListener('click', function (event) {
      event.preventDefault();
      if (isOpen) {
        closeDrawer();
      } else {
        openDrawer();
      }
    });

    if (scrim) {
      scrim.addEventListener('click', function () { closeDrawer(); });
    }

    drawer.addEventListener('click', function (event) {
      var target = event.target;
      var link = target && target.closest ? target.closest('a[href]') : null;
      if (!link || link.hasAttribute('data-toggle')) return;
      closeDrawer(false);
    });

    document.addEventListener('keydown', function (event) {
      if (!isOpen) return;

      if (event.key === 'Escape') {
        event.preventDefault();
        closeDrawer();
        return;
      }

      if (event.key !== 'Tab') return;

      var items = visibleFocusables(topbar || toggle).concat(drawerFocusables());
      var index = items.indexOf(document.activeElement);

      if (event.shiftKey) {
        if (index <= 0) {
          event.preventDefault();
          items[items.length - 1].focus();
        }
      } else if (index === -1 || index === items.length - 1) {
        event.preventDefault();
        items[0].focus();
      }
    });
  }

  function init() {
    bindReload();
    initNavDrawer();
    updateSummary();
    tick();
    setInterval(tick, 1000);

    loadQueue.forEach(function (card, index) {
      setTimeout(function () { load(card); }, index * STAGGER_MS);
    });

    var resizeTimer = null;
    function scheduleFit() {
      clearTimeout(resizeTimer);
      resizeTimer = setTimeout(function () {
        cards.forEach(fit);
      }, 150);
    }

    window.addEventListener('resize', scheduleFit);

    if ('ResizeObserver' in window) {
      var observer = new ResizeObserver(scheduleFit);
      var tiles = document.querySelector('.mu-tiles');
      if (tiles) observer.observe(tiles);
    }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
