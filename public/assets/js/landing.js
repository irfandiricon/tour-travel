(function () {
  'use strict';

  // FAQ: opening/closing is handled only by Bootstrap's accordion (data-bs-toggle="collapse").
  // Do not add a second click handler here: toggling `hidden` / aria-expanded by hand gets out of
  // sync with Bootstrap (e.g. a click during the slide animation) and the answer stays closed.

  // Respect prefers-reduced-motion: no autoplay and no fade transition.
  var reducedMotion = window.matchMedia &&
    window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  if (reducedMotion) {
    document.querySelectorAll('.carousel').forEach(function (el) {
      el.removeAttribute('data-bs-ride');
      el.setAttribute('data-bs-interval', 'false');
      el.classList.remove('carousel-fade');
      if (window.bootstrap && window.bootstrap.Carousel) {
        var existing = window.bootstrap.Carousel.getInstance(el);
        if (existing) {
          existing.pause();
          existing.dispose();
        }
        window.bootstrap.Carousel.getOrCreateInstance(el, { interval: false, ride: false });
      }
    });
  }

  // Hero carousel pause/play control (WCAG 2.2.2). Tolerates missing elements.
  (function initHeroToggle() {
    var btn = document.querySelector('[data-hero-toggle]');
    var carouselEl = document.getElementById('hero-carousel');
    if (!btn || !carouselEl) {
      return;
    }
    var hasApi = !!(window.bootstrap && window.bootstrap.Carousel);
    var icon = btn.querySelector('i');
    var paused = false;

    function render() {
      var label = btn.getAttribute(paused ? 'data-label-play' : 'data-label-pause') || '';
      btn.setAttribute('aria-pressed', paused ? 'true' : 'false');
      if (label) {
        btn.setAttribute('aria-label', label);
      }
      if (icon) {
        icon.classList.remove('bi-pause-fill', 'bi-play-fill');
        icon.classList.add(paused ? 'bi-play-fill' : 'bi-pause-fill');
      }
    }

    function instance() {
      return hasApi ? window.bootstrap.Carousel.getOrCreateInstance(carouselEl) : null;
    }

    if (reducedMotion) {
      paused = true;
    }
    render();

    btn.addEventListener('click', function () {
      paused = !paused;
      var carousel = instance();
      if (carousel) {
        if (paused) {
          carousel.pause();
        } else {
          carousel.cycle();
        }
      }
      render();
    });
  })();

  // Fleet rail: keyboard focus on a card brings that card into view horizontally.
  (function initFleetRail() {
    var rails = document.querySelectorAll('.fleet-rail');
    rails.forEach(function (rail) {
      rail.addEventListener('focusin', function (e) {
        var card = e.target && e.target.closest ? e.target.closest('.fleet-card') : null;
        if (!card || !rail.contains(card) || rail.scrollWidth <= rail.clientWidth) {
          return;
        }
        var railRect = rail.getBoundingClientRect();
        var cardRect = card.getBoundingClientRect();
        var delta = 0;
        if (cardRect.left < railRect.left) {
          delta = cardRect.left - railRect.left;
        } else if (cardRect.right > railRect.right) {
          delta = cardRect.right - railRect.right;
        }
        if (delta !== 0) {
          rail.scrollBy({ left: delta, behavior: reducedMotion ? 'auto' : 'smooth' });
        }
      });
    });
  })();

  // Booking click tracking: fire-and-forget, never blocks wa.me navigation.
  function sendBookingClick(url, fleetId, fleetName) {
    try {
      var body = new URLSearchParams();
      body.set('fleet_id', fleetId);
      body.set('fleet_name', fleetName);
      body.set('lang', document.documentElement.getAttribute('lang') || 'en');

      if (navigator.sendBeacon && navigator.sendBeacon(url, body)) {
        return;
      }
      if (window.fetch) {
        fetch(url, {
          method: 'POST',
          body: body,
          keepalive: true,
          credentials: 'same-origin'
        }).catch(function () {});
      }
    } catch (e) {
      // Ignore tracking failures silently.
    }
  }

  document.addEventListener('click', function (e) {
    var target = e.target;
    var link = target && target.closest ? target.closest('[data-track-booking]') : null;
    if (!link) {
      return;
    }
    var url = link.getAttribute('data-track-booking');
    var fleetId = link.getAttribute('data-fleet-id');
    if (!url || !fleetId) {
      return;
    }
    sendBookingClick(url, fleetId, link.getAttribute('data-fleet-name') || '');
  });
})();

// Image popup (lightbox): any [data-lightbox] button opens #lightbox; items sharing a
// data-lightbox-group can be browsed with the arrows / arrow keys. Esc, the X button and a
// click outside the picture close it (Bootstrap modal), and focus returns to the opener.
(function initLightbox() {
  'use strict';

  var modalEl = document.getElementById('lightbox');
  if (!modalEl || !window.bootstrap || !window.bootstrap.Modal) {
    return;
  }

  var img = modalEl.querySelector('.lightbox__img');
  var caption = modalEl.querySelector('.lightbox__caption');
  var prevBtn = modalEl.querySelector('[data-lightbox-prev]');
  var nextBtn = modalEl.querySelector('[data-lightbox-next]');
  var modal = window.bootstrap.Modal.getOrCreateInstance(modalEl);
  var items = [];
  var index = 0;
  var opener = null;

  function show(i) {
    index = (i + items.length) % items.length;
    var el = items[index];
    var text = el.getAttribute('data-lightbox-caption') || '';

    img.src = el.getAttribute('data-lightbox-src') || '';
    img.alt = text;
    caption.textContent = text;
    caption.hidden = text === '';
    prevBtn.hidden = nextBtn.hidden = items.length < 2;
  }

  function step(delta) {
    if (items.length > 1) {
      show(index + delta);
    }
  }

  document.addEventListener('click', function (e) {
    var trigger = e.target && e.target.closest ? e.target.closest('[data-lightbox]') : null;
    if (!trigger) {
      return;
    }
    e.preventDefault();

    var group = trigger.getAttribute('data-lightbox-group');
    items = group
      ? Array.prototype.slice.call(document.querySelectorAll('[data-lightbox][data-lightbox-group="' + group.replace(/"/g, '\\"') + '"]'))
      : [trigger];
    opener = trigger;
    show(Math.max(0, items.indexOf(trigger)));
    modal.show();
  });

  prevBtn.addEventListener('click', function () { step(-1); });
  nextBtn.addEventListener('click', function () { step(1); });

  modalEl.addEventListener('keydown', function (e) {
    if (e.key === 'ArrowLeft') {
      e.preventDefault();
      step(-1);
    } else if (e.key === 'ArrowRight') {
      e.preventDefault();
      step(1);
    }
  });

  modalEl.addEventListener('hidden.bs.modal', function () {
    img.removeAttribute('src');
    if (opener && typeof opener.focus === 'function') {
      opener.focus();
    }
  });
})();

// Card carousels (News & Information, Customer Reviews). The prev/next buttons scroll the rail by one
// visible "page" of cards (scroll-snap aligns them); they are hidden when every card fits.
//   data-loop            prev/next wrap around (last -> first, first -> last) instead of stopping.
//   data-autoplay="ms"   advance one card every ms and rewind to the first card after the last.
//                        Pauses while hovered, while a card has keyboard focus, while the tab is hidden,
//                        and with the [data-carousel-toggle] button (WCAG 2.2.2). Off for reduced motion.
(function initCardCarousels() {
  'use strict';

  var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  document.querySelectorAll('[data-news-carousel]').forEach(function (root) {
    var rail = root.querySelector('.news-rail');
    var prev = root.querySelector('[data-news-prev]');
    var next = root.querySelector('[data-news-next]');
    if (!rail || !prev || !next) {
      return;
    }

    var loop = root.hasAttribute('data-loop');
    var delay = parseInt(root.getAttribute('data-autoplay') || '0', 10) || 0;
    var toggle = root.parentNode ? root.parentNode.querySelector('[data-carousel-toggle]') : null;
    var stopped = reduce;          // pause button (or reduced motion)
    var hovered = false;
    var focused = false;
    var timer = null;

    function maxScroll() { return rail.scrollWidth - rail.clientWidth; }
    function fits() { return maxScroll() <= 2; }
    function atStart() { return rail.scrollLeft <= 2; }
    function atEnd() { return rail.scrollLeft >= maxScroll() - 2; }
    function behavior() { return reduce ? 'auto' : 'smooth'; }

    function cardStep() {
      var card = rail.firstElementChild; // news or review card
      var gap = parseFloat(window.getComputedStyle(rail).columnGap) || 0;
      return card ? card.getBoundingClientRect().width + gap : rail.clientWidth;
    }

    function update() {
      var all = fits();
      prev.hidden = next.hidden = all;
      prev.disabled = !loop && atStart();
      next.disabled = !loop && atEnd();
      if (toggle) {
        toggle.hidden = all || delay <= 0;
      }
    }

    /** dir: 1 or -1; cards: how many cards to move (0 = one visible page). */
    function go(dir, cards) {
      var step = cardStep();
      var perPage = Math.max(1, Math.floor((rail.clientWidth + 1) / step));
      var by = step * (cards || perPage);

      if (loop && dir > 0 && atEnd()) {
        rail.scrollTo({ left: 0, behavior: behavior() });
      } else if (loop && dir < 0 && atStart()) {
        rail.scrollTo({ left: maxScroll(), behavior: behavior() });
      } else {
        rail.scrollBy({ left: dir * by, behavior: behavior() });
      }
    }

    function renderToggle() {
      if (!toggle) {
        return;
      }
      var label = toggle.getAttribute(stopped ? 'data-label-play' : 'data-label-pause') || '';
      var icon = toggle.querySelector('i');
      toggle.setAttribute('aria-pressed', stopped ? 'true' : 'false');
      if (label) {
        toggle.setAttribute('aria-label', label);
        toggle.setAttribute('title', label);
      }
      if (icon) {
        icon.classList.remove('bi-pause-fill', 'bi-play-fill');
        icon.classList.add(stopped ? 'bi-play-fill' : 'bi-pause-fill');
      }
    }

    function tick() {
      if (stopped || hovered || focused || document.hidden || fits()) {
        return;
      }
      go(1, 1);
    }

    function restart() {
      if (delay <= 0) {
        return;
      }
      if (timer) {
        window.clearInterval(timer);
      }
      timer = window.setInterval(tick, delay);
    }

    prev.addEventListener('click', function () { go(-1); restart(); });
    next.addEventListener('click', function () { go(1); restart(); });
    rail.addEventListener('keydown', function (e) {
      if (e.target !== rail) {
        return;
      }
      if (e.key === 'ArrowLeft') {
        e.preventDefault();
        go(-1);
        restart();
      } else if (e.key === 'ArrowRight') {
        e.preventDefault();
        go(1);
        restart();
      }
    });
    rail.addEventListener('scroll', update, { passive: true });
    window.addEventListener('resize', update);
    window.addEventListener('load', update);

    if (delay > 0) {
      root.addEventListener('mouseenter', function () { hovered = true; });
      root.addEventListener('mouseleave', function () { hovered = false; restart(); });
      root.addEventListener('focusin', function () { focused = true; });
      root.addEventListener('focusout', function (e) {
        if (!root.contains(e.relatedTarget)) {
          focused = false;
          restart();
        }
      });
      // A swipe on a touch screen: wait a full interval before moving on.
      rail.addEventListener('touchstart', restart, { passive: true });
      if (toggle) {
        toggle.addEventListener('click', function () {
          stopped = !stopped;
          renderToggle();
          restart();
        });
      }
      renderToggle();
      restart();
    }

    update();
  });
})();

// Mobile menu: after tapping a menu item that scrolls to a section on this page, close the menu
// so the section is not hidden behind it.
(function initNavCollapse() {
  'use strict';

  var menu = document.getElementById('site-nav');
  if (!menu) {
    return;
  }
  menu.addEventListener('click', function (e) {
    var link = e.target && e.target.closest ? e.target.closest('a.nav-link') : null;
    if (!link || !menu.classList.contains('show') || !window.bootstrap || !window.bootstrap.Collapse) {
      return;
    }
    window.bootstrap.Collapse.getOrCreateInstance(menu, { toggle: false }).hide();
  });
})();