/* Shokeru — UI-функції групи: єдині тости (window.shokeruToast), cookie-банер + Google Consent Mode,
   плаваючі кнопки «Подзвонити / Нагору», вітальний екран (welcome). Підключається з common/footer.
   Тости доступні одразу (до DOMContentLoaded), решта — після нього. */
(function () {
  'use strict';

  /* ---------- Тости: фіксовані по центру екрана, по одному, хрестик закриття ---------- */
  var ICONS = {
    success: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="m9 12 2 2 4-4"/></svg>',
    danger: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="m15 9-6 6"/><path d="m9 9 6 6"/></svg>',
    info: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>'
  };

  function strip(html) {
    return String(html || '').replace(/<[^>]+>/g, '').replace(/&nbsp;/g, ' ').replace(/\s+/g, ' ').trim();
  }

  function esc(value) {
    return String(value).replace(/[&<>"']/g, function (ch) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[ch];
    });
  }

  function toast(message, type, description) {
    var text = strip(message);

    if (!text) return;

    var kind = type === 'danger' || type === 'error' ? 'danger' : (type === 'info' ? 'info' : 'success');
    var reduced = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    var stack = document.querySelector('[data-toaster]');

    if (!stack) {
      stack = document.createElement('div');
      stack.className = 'sk-toaster';
      stack.setAttribute('data-toaster', '');
      stack.setAttribute('aria-live', 'polite');
      document.body.appendChild(stack);
    }

    var el = document.createElement('div');
    var close = document.documentElement.getAttribute('data-text-close') || 'Close';

    el.className = 'sk-toast sk-toast--' + kind;
    el.setAttribute('role', kind === 'danger' ? 'alert' : 'status');
    el.innerHTML = '<span class="sk-toast__icon">' + ICONS[kind] + '</span>'
      + '<div class="sk-toast__body"><p class="sk-toast__text">' + esc(text) + '</p>'
      + (description ? '<p class="sk-toast__desc">' + esc(strip(description)) + '</p>' : '') + '</div>'
      + '<button type="button" class="sk-toast__close" aria-label="' + esc(close) + '"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg></button>';

    var dismiss = function () {
      clearTimeout(el._timer);
      el.classList.add('is-out');
      var remove = function () { if (el.parentNode) el.parentNode.removeChild(el); };
      if (reduced) remove(); else setTimeout(remove, 260);
    };

    el.querySelector('button').addEventListener('click', dismiss);

    // один тост за раз: новий замінює попередній
    while (stack.firstChild) stack.removeChild(stack.firstChild);
    stack.appendChild(el);
    el._timer = setTimeout(dismiss, kind === 'danger' ? 6000 : 3200);
  }

  window.shokeruToast = toast;

  // серверні повідомлення в шаблонах ([data-toast]) показуємо тим самим тостом
  function collectToasts(scope) {
    (scope || document).querySelectorAll('[data-toast]').forEach(function (node) {
      var type = node.getAttribute('data-toast') === 'danger' ? 'danger' : 'success';
      var clone = node.cloneNode(true);

      clone.querySelectorAll('button').forEach(function (b) { b.remove(); });

      var text = clone.textContent;
      if (node.parentNode) node.parentNode.removeChild(node);
      toast(text, type);
    });
  }

  document.addEventListener('DOMContentLoaded', function () {
    // випадайки мови/валюти з клавіатури (div[role=button])
    document.addEventListener('keydown', function (event) {
      var t = event.target;

      if ((event.key === 'Enter' || event.key === ' ') && t && t.matches && t.matches('.header__lang-selected[role="button"]')) {
        event.preventDefault();
        t.click();
      }
    });

    collectToasts(document);
    initCookie();
    initFab();
    initAuthOpen();
    initHeroSound();
    initWelcome();
  });

  /* ---------- Cookie-банер: показується раз, вибір — у localStorage і куці на 180 днів.
     Consent Mode: аналітика отримує згоду лише після «Прийняти всі». ---------- */
  function initCookie() {
    var banner = document.querySelector('[data-cookie-banner]');
    var KEY = 'shokeru_cookie_consent';

    function read() { try { return localStorage.getItem(KEY); } catch (e) { return null; } }

    function apply(granted) {
      if (typeof window.gtag === 'function') {
        var v = granted ? 'granted' : 'denied';
        window.gtag('consent', 'update', { analytics_storage: v, ad_storage: v, ad_user_data: v, ad_personalization: v });
      }
    }

    var stored = read();

    if (stored) {
      apply(stored === 'granted');
      return;
    }

    if (!banner) return;

    apply(false);
    banner.hidden = false;
    requestAnimationFrame(function () { banner.classList.add('is-visible'); });

    banner.addEventListener('click', function (event) {
      var btn = event.target.closest('[data-cookie]');

      if (!btn) return;

      var granted = btn.getAttribute('data-cookie') === 'accept';

      try { localStorage.setItem(KEY, granted ? 'granted' : 'denied'); } catch (e) {}
      document.cookie = KEY + '=' + (granted ? 'granted' : 'denied') + '; path=/; max-age=' + (60 * 60 * 24 * 180) + '; SameSite=Lax';
      apply(granted);
      banner.classList.remove('is-visible');
      setTimeout(function () { banner.hidden = true; }, 350);
    });
  }

  /* ---------- Плаваючі кнопки: «Подзвонити» + «Нагору», одна колонка справа внизу ---------- */
  function initFab() {
    var fab = document.querySelector('[data-fab]');

    if (!fab) return;

    var topBtn = fab.querySelector('[data-scroll-top]');
    var banner = document.querySelector('[data-cookie-banner]');
    var bar = document.querySelector('[data-purchase-bar]');
    var ticking = false;
    var reduced = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    function sync() {
      ticking = false;
      var y = window.pageYOffset || document.documentElement.scrollTop;

      if (topBtn) topBtn.classList.toggle('is-shown', y > 600);

      // над cookie-банером і липкою панеллю покупки
      var lift = 0;

      if (bar && bar.classList.contains('is-visible')) lift = Math.max(lift, bar.offsetHeight);

      if (banner && !banner.hidden && banner.classList.contains('is-visible')) {
        var b = banner.getBoundingClientRect();
        var f = fab.getBoundingClientRect();

        if (b.right > f.left - 8) lift = Math.max(lift, window.innerHeight - b.top + 4);
      }

      fab.style.setProperty('--fab-lift', Math.round(lift) + 'px');
    }

    function queue() {
      if (!ticking) { ticking = true; requestAnimationFrame(sync); }
    }

    window.addEventListener('scroll', queue, { passive: true });
    window.addEventListener('resize', queue);

    if ('MutationObserver' in window && banner) {
      new MutationObserver(queue).observe(banner, { attributes: true, attributeFilter: ['class', 'hidden'] });
    }

    if (topBtn) {
      topBtn.addEventListener('click', function () {
        window.scrollTo({ top: 0, behavior: reduced ? 'auto' : 'smooth' });
      });
    }

    sync();
  }

  /* ---------- Попап входу: ?auth=login|register (301 з /login, /register) відкриває його на головній;
     посилання на account/login|register та [data-auth-open] теж відкривають попап, а не окрему сторінку ---------- */
  function initAuthOpen() {
    var popup = document.querySelector('.userPopup');

    if (!popup) return;

    function open(mode) {
      var tab = popup.querySelector('.userPopup__btn[data-userpopup-select="' + mode + '"]');
      var menu = document.querySelector('.header__menu');

      popup.classList.add('active');
      if (tab) tab.click();
      if (menu) menu.classList.remove('active');
    }

    window.shokeruAuth = { open: open };

    document.addEventListener('click', function (event) {
      var link = event.target.closest ? event.target.closest('a[href], [data-auth-open]') : null;

      if (!link) return;

      var mode = link.getAttribute('data-auth-open');

      if (!mode) {
        var m = /route=account(?:\/|%2F)(login|register)(?:&|$)/.exec(link.getAttribute('href') || '');
        mode = m ? m[1] : '';
      }

      if (mode === 'login' || mode === 'register') {
        event.preventDefault();
        open(mode);
      }
    });

    // обробники вкладок вішає script.js на DOMContentLoaded — відкриваємо вже після load
    function auto() {
      try {
        var params = new URLSearchParams(window.location.search);
        var mode = params.get('auth');

        if (mode !== 'login' && mode !== 'register') return;

        params.delete('auth');

        var query = params.toString();

        window.history.replaceState({}, '', window.location.pathname + (query ? '?' + query : '') + window.location.hash);
        open(mode);
      } catch (e) { /* старі браузери лишають ?auth= в адресі — не страшно */ }
    }

    if (document.readyState === 'complete') auto(); else window.addEventListener('load', auto);
  }

  /* ---------- Hero: кнопка «Звук» для відео-слайдів зі звуковою доріжкою (data-has-audio ставить контролер) ---------- */
  function initHeroSound() {
    var btn = document.querySelector('[data-hero-sound]');

    if (!btn) return;

    var hero = btn.closest('.hero') || document;
    var slides = hero.querySelectorAll('.hero__slide');
    var soundOn = false;

    // видиме відео активного слайда, що має звук (на ПК і телефоні різні <video>)
    function activeVideo() {
      var slide = hero.querySelector('.hero__slide.swiper-slide-active') || slides[0];

      if (!slide) return null;

      var found = null;

      slide.querySelectorAll('video[data-has-audio]').forEach(function (v) {
        if (!found && v.offsetParent !== null) found = v;
      });

      return found;
    }

    function apply() {
      var current = activeVideo();

      hero.querySelectorAll('video').forEach(function (v) { if (v !== current) v.muted = true; });

      if (current) {
        current.muted = !soundOn;
        if (soundOn && current.paused) current.play().catch(function () {});
      }

      btn.hidden = !current;
      btn.classList.toggle('is-on', soundOn);
      btn.setAttribute('aria-pressed', soundOn ? 'true' : 'false');

      var label = btn.getAttribute(soundOn ? 'data-label-off' : 'data-label-on') || '';

      btn.setAttribute('aria-label', label);
      btn.setAttribute('title', label);
    }

    btn.addEventListener('click', function () { soundOn = !soundOn; apply(); });

    if ('MutationObserver' in window) {
      var observer = new MutationObserver(apply);

      slides.forEach(function (slide) { observer.observe(slide, { attributes: true, attributeFilter: ['class'] }); });
    }

    window.addEventListener('resize', apply);
    apply();
  }

  /* ---------- Вітальний тизер (common/welcome_overlay; показ — ControllerCommonHome::shouldShowWelcome) ----------
     Як у hydrophob. Стани:
       1. завантаження — прелоадер із лого вантажить ролик у кеш;
       2. очікування   — ролик іде приглушеним і розмитим, друкується лід, зʼявляється cookie-банер сайту;
       3. перегляд     — відповідь на банер (або будь-яка дія, якщо банера нема) = дозвіл на звук: ролик з початку до кінця
                         зі звуком і без розмиття; «Пропустити» по центру внизу;
       4. вихід        — ролик догрався або «Пропустити»: накладка їде вниз.
     Поки людина дивиться тизер, у кеш тягнуться ролики першого екрана головної (data-prefetch). */
  function initWelcome() {
    var overlay = document.querySelector('[data-welcome]');

    if (!overlay) return;

    var root = document.documentElement;
    var body = document.body;
    var intro = overlay.querySelector('[data-welcome-intro]') || overlay;
    var vid = overlay.querySelector('[data-welcome-video]');
    var skip = overlay.querySelector('[data-welcome-skip]');
    var pre = overlay.querySelector('[data-welcome-preloader]');
    var progEl = overlay.querySelector('[data-welcome-progress]');
    var pctEl = overlay.querySelector('[data-welcome-pct]');
    var lead = overlay.querySelector('[data-welcome-type]');
    var sound = overlay.querySelector('[data-welcome-sound]');
    var reduced = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    var mobile = window.matchMedia && window.matchMedia('(max-width: 767px)').matches;

    if (!vid) return;

    // на телефоні — легкий ролик
    var mobileSrc = vid.getAttribute('data-src-mobile');

    if (mobileSrc && mobile) vid.src = mobileSrc;

    var hasAudio = vid.hasAttribute(mobile && mobileSrc ? 'data-has-audio-mobile' : 'data-has-audio');

    root.classList.add('welcome-lock');
    body.classList.add('welcome-behind', 'welcome-loading');

    var closed = false, playing = false, armed = false, pending = false, exitTimer = null;

    /* ── 4. вихід ── */
    function close() {
      if (closed) return;
      closed = true;

      if (exitTimer) clearTimeout(exitTimer);

      overlay.classList.add('is-leaving');
      body.classList.add('welcome-behind--out');

      // з DOM прибираємо, коли анімація виходу дограла; скрол лишається замкненим до того
      setTimeout(function () {
        try { vid.pause(); } catch (e) {}
        overlay.remove();
        body.classList.remove('welcome-behind', 'welcome-behind--out', 'welcome-loading');
        root.classList.remove('welcome-lock');
      }, 1700);
    }

    /* ── 1. завантаження ── */
    var prog = 0;
    var progTimer = setInterval(function () {
      prog = Math.min(90, prog + Math.random() * 7 + 2);
      if (progEl) progEl.style.width = prog + '%';
      if (pctEl) pctEl.textContent = Math.round(prog) + '%';
    }, 180);

    function showCta() {
      if (armed) return;
      armed = true;
      intro.classList.add('is-cta');
      if (pending) startShow();
    }

    // лід набирається по літерах; дію, зроблену до кінця набору, лише запамʼятовуємо
    function typeLead() {
      if (!lead || lead.dataset.typed) { showCta(); return; }
      lead.dataset.typed = '1';

      var full = lead.textContent;

      if (reduced) { showCta(); return; }

      lead.setAttribute('aria-label', full.trim());
      lead.textContent = '';

      var i = 0;

      setTimeout(function tick() {
        if (closed) return;
        if (i <= full.length) { lead.textContent = full.slice(0, i++); setTimeout(tick, 32); return; }
        showCta();
      }, 900);
    }

    /* ── 2. очікування: кадр готовий, ролик іде розмитим і без звуку ── */
    function ready() {
      if (!pre || pre.dataset.done) return;
      pre.dataset.done = '1';
      clearInterval(progTimer);
      if (progEl) progEl.style.width = '100%';
      if (pctEl) pctEl.textContent = '100%';

      setTimeout(function () {
        pre.classList.add('is-hidden');
        setTimeout(function () { pre.remove(); }, 800);

        // прелоадер поїхав — зʼявляється cookie-банер
        body.classList.remove('welcome-loading');
        intro.classList.add('is-ready');

        // автоплей приглушеного відео дозволений без жесту
        vid.muted = true;
        var bg = vid.play();
        if (bg && bg.catch) bg.catch(function () {});

        typeLead();
      }, 300);
    }

    var videoReady = new Promise(function (resolve) {
      if (vid.readyState >= 2) return resolve();
      vid.addEventListener('loadeddata', resolve, { once: true });
      vid.addEventListener('error', resolve, { once: true });
    });
    var pageReady = new Promise(function (resolve) {
      if (document.readyState === 'complete') resolve();
      else window.addEventListener('load', resolve, { once: true });
    });
    // мінімум, скільки прелоадер лишається: при закешованому ролику він інакше лише мигне
    var minShow = new Promise(function (resolve) { setTimeout(resolve, reduced ? 0 : 1200); });

    Promise.all([videoReady, pageReady, minShow]).then(ready);
    setTimeout(ready, 6000);

    /* Що вважаємо командою «грай зі звуком». Якщо на екрані cookie-банер — лише відповідь йому (клік або зникнення):
       саме вона дає браузеру право на звук, а реагувати на скрол, поки банер висить, неправильно.
       Банера немає — годиться будь-яка дія. Не раніше, ніж допечатається лід. */
    var MOVES = ['pointerdown', 'wheel', 'touchstart', 'keydown'];

    function interact() { if (armed) startShow(); else pending = true; }
    function listenMoves() { MOVES.forEach(function (n) { window.addEventListener(n, interact, { passive: true }); }); }
    function stopMoves() { MOVES.forEach(function (n) { window.removeEventListener(n, interact); }); }

    function visibleBanner() {
      var b = document.querySelector('[data-cookie-banner]');

      if (!b || b.hidden) return null;

      var r = b.getBoundingClientRect();

      return r.width > 0 && r.height > 0 ? b : null;
    }

    function waitForCookie(b) {
      b.addEventListener('click', function (event) {
        if (event.target.closest('[data-cookie]')) setTimeout(interact, 150);
      });

      var watcher = new MutationObserver(function () {
        if (!visibleBanner()) { watcher.disconnect(); interact(); }
      });

      watcher.observe(b, { attributes: true, attributeFilter: ['hidden', 'class', 'style'] });
    }

    var banner = visibleBanner() || document.querySelector('[data-cookie-banner]:not([hidden])');

    if (banner) {
      waitForCookie(banner);
    } else {
      listenMoves();
      // банер міг ще не встигнути зʼявитись — дивимось ще раз трохи згодом
      setTimeout(function () {
        var late = visibleBanner();
        if (late && !playing) { stopMoves(); waitForCookie(late); }
      }, 600);
    }

    // прогрів: ролики першого екрана головної (на телефоні — легкі копії)
    var prefetch = [];

    try { prefetch = JSON.parse(vid.getAttribute('data-prefetch') || '[]'); } catch (e) {}

    var picked = prefetch.filter(function (u) { return /mob/i.test(u) === !!mobile; });

    if (!picked.length) picked = prefetch;

    setTimeout(function () {
      picked.forEach(function (url) {
        try { fetch(url, { priority: 'low' }).catch(function () {}); } catch (e) {}
      });
    }, 1200);

    /* ── 3. перегляд ── */
    function syncSound() {
      if (!sound) return;

      sound.classList.toggle('is-muted', vid.muted);
      sound.setAttribute('aria-pressed', vid.muted ? 'true' : 'false');

      var label = sound.getAttribute(vid.muted ? 'data-label-off' : 'data-label-on') || '';

      sound.setAttribute('aria-label', label);
      sound.setAttribute('title', label);
    }

    function startShow() {
      if (playing || closed) return;
      playing = true;
      stopMoves();

      // тексти йдуть, розмиття сходить, ролик починається з нуля зі звуком і без повтору
      intro.classList.add('is-playing');
      intro.classList.remove('is-blurred');

      vid.loop = false;
      vid.removeAttribute('loop');
      vid.currentTime = 0;
      vid.muted = false;

      if (sound && hasAudio) { syncSound(); sound.classList.add('is-ready'); }

      var started = vid.play();

      // звук усе ж заблокували — краще показати без нього, ніж нічого
      if (started && started.catch) {
        started.catch(function () {
          vid.muted = true;
          syncSound();

          var retry = vid.play();
          if (retry && retry.catch) retry.catch(function () {});
        });
      }
    }

    if (sound) {
      sound.addEventListener('click', function () {
        vid.muted = !vid.muted;
        syncSound();
        if (!vid.muted && vid.paused) vid.play().catch(function () {});
      });
    }

    // ролик майже відкрутився — виходимо рівно на його кінці, без обриву
    vid.addEventListener('timeupdate', function () {
      if (!playing || !vid.duration || !isFinite(vid.duration) || exitTimer || closed) return;

      if (vid.currentTime / vid.duration >= 0.8) {
        exitTimer = setTimeout(close, Math.max(0, (vid.duration - vid.currentTime) * 1000) + 400);
      }
    });

    vid.addEventListener('ended', function () { if (playing) close(); });

    // ролик не має зупинятись від скролу чи чужих скриптів — тримаємо відтворення самі
    function keepPlaying() {
      if (closed || vid.ended) return;

      if (vid.paused) {
        var resume = vid.play();
        if (resume && resume.catch) resume.catch(function () {});
      }
    }

    vid.addEventListener('pause', keepPlaying);
    document.addEventListener('visibilitychange', function () { if (!document.hidden) keepPlaying(); });

    var guard = setInterval(function () {
      if (closed) { clearInterval(guard); return; }
      if (armed || playing) keepPlaying();
    }, 1000);

    // ролик не завантажився — показувати нічого, йдемо далі
    vid.addEventListener('error', close);

    if (skip) skip.addEventListener('click', close);

    document.addEventListener('keydown', function (event) { if (event.key === 'Escape') close(); });
  }
})();
