/* video-warmup — прогрів роликів під прелоадером/велкомом (спільний модуль для всіх сайтів групи).
 *
 * Що робить: поки на екрані логотип завантаження, у фоні докачує ролики першого екрана
 * й ті, що зʼявляються при скролі (на телефоні — мобільні версії, на ПК — десктопні),
 * щоб після згортання прелоадера вони стартували миттєво.
 *   1) усі ролики — у HTTP-кеш браузера (fetch; Cache-Control на mp4 уже 10 років);
 *   2) ролики першого екрана («hero») — ще й як blob: підміняємо src/data-src на blob:-URL,
 *      бо Safari інколи перепитує медіа range-запитом повз кеш, а blob грає з памʼяті гарантовано.
 *
 * Підключення (див. INTEGRATION.md):
 *   videoWarmup.start();                       // зібрати ролики з DOM і почати
 *   videoWarmup.gate(6000).then(...)            // чекати hero-ролики (або таймаут) — у гейт прелоадера
 *   videoWarmup.resolve(url) → blob:-URL або url // для власних лінивих завантажувачів теми
 *
 * Розмітка-підказки (необовʼязкові):
 *   <video data-warm="hero">   — точно перший екран (інакше hero = у межах 1.5 висоти екрана)
 *   <video data-warm="skip">   — не чіпати (напр. сам ролик велкому, він уже грає)
 *   <video data-warm="lazy">   — лише кеш, без blob, найнижчий пріоритет (лайтбокси, модалки)
 */
(function (w, d) {
    'use strict';
    if (w.videoWarmup) return;

    var mobile = w.matchMedia && w.matchMedia('(max-width: 767px)').matches;
    var conn = navigator.connection || {};
    var saveData = !!conn.saveData || /(^|-)2g$/.test(conn.effectiveType || '');
    // бюджети: скільки максимум докачуємо (байт) і скільки hero тримаємо як blob
    var BUDGET = mobile ? 25 * 1024 * 1024 : 80 * 1024 * 1024;
    var BLOB_BUDGET = mobile ? 8 * 1024 * 1024 : 40 * 1024 * 1024;
    var CONCURRENCY = mobile ? 2 : 3;

    var items = [];          // {url, el, kind: 'hero'|'scroll'|'lazy', size, blob, done, fetching}
    var byUrl = {};
    var spent = 0, blobSpent = 0;
    var started = false;
    var heroResolve, heroDone = false;
    var heroPromise = new Promise(function (r) { heroResolve = r; });

    function abs(u) { try { return new URL(u, d.baseURI).href; } catch (e) { return u; } }

    /* джерело ролика за брейкпоїнтом: data-src-mobile → data-src → src; webm пропускаємо, якщо є mp4 */
    function pickSrc(video) {
        var cands = [];
        var push = function (v) { if (v && !/^blob:/.test(v) && !/\.webm(\?|$)/i.test(v)) cands.push(v); };
        if (mobile) push(video.getAttribute('data-src-mobile') || video.getAttribute('data-mobile-src'));
        push(video.getAttribute('data-src'));
        push(video.getAttribute('src'));
        var sources = video.querySelectorAll('source');
        for (var i = 0; i < sources.length; i++) {
            var s = sources[i];
            if (mobile) push(s.getAttribute('data-src-mobile'));
            push(s.getAttribute('data-src') || s.getAttribute('src'));
        }
        return cands.length ? abs(cands[0]) : null;
    }

    function classify(video) {
        var hint = (video.getAttribute('data-warm') || '').toLowerCase();
        if (hint === 'skip') return null;
        if (hint === 'hero' || hint === 'scroll' || hint === 'lazy') return hint;
        if (video.closest && video.closest('dialog, [hidden], .lightbox, .modal, [data-lightbox], [aria-hidden="true"]')) return 'lazy';
        var r = video.getBoundingClientRect();
        return (r.top < w.innerHeight * 1.5 && r.bottom > -w.innerHeight) ? 'hero' : 'scroll';
    }

    function collect() {
        var videos = d.querySelectorAll('video');
        for (var i = 0; i < videos.length; i++) {
            var v = videos[i], kind = classify(v), url = kind && pickSrc(v);
            if (!url) continue;
            if (byUrl[url]) { if (kind === 'hero') byUrl[url].kind = 'hero'; byUrl[url].els.push(v); continue; }
            var it = { url: url, els: [v], kind: kind, size: 0, blob: null, done: false, fetching: false };
            byUrl[url] = it; items.push(it);
        }
        var order = { hero: 0, scroll: 1, lazy: 2 };
        items.sort(function (a, b) { return order[a.kind] - order[b.kind]; });
    }

    /* підміна джерел елемента на blob (лише якщо ролик ще не грає) */
    function swapToBlob(it) {
        it.els.forEach(function (v) {
            if (!v.paused && v.currentTime > 0) return;
            var attrs = ['data-src-mobile', 'data-mobile-src', 'data-src', 'src'];
            var touched = false;
            var apply = function (el) {
                attrs.forEach(function (a) {
                    var val = el.getAttribute(a);
                    if (val && abs(val) === it.url) {
                        if (!el.getAttribute(a + '-orig')) el.setAttribute(a + '-orig', val);
                        el.setAttribute(a, it.blob);
                        touched = true;
                    }
                });
            };
            apply(v);
            var sources = v.querySelectorAll('source');
            for (var i = 0; i < sources.length; i++) apply(sources[i]);
            // якщо src уже стояв (не лінивий) — перезавантажуємо з blob, постер лишається
            if (touched && (v.getAttribute('src') === it.blob || (sources.length && v.getAttribute('src') === null))) {
                try { v.load(); } catch (e) {}
            }
        });
    }

    function heroLeft() { return items.filter(function (i) { return i.kind === 'hero' && !i.done; }).length; }
    function checkHero() {
        if (heroDone) return;
        if (!heroLeft()) { heroDone = true; heroResolve(); }
    }

    function fetchItem(it) {
        it.fetching = true;
        var wantBlob = it.kind === 'hero';
        return fetch(it.url, { credentials: 'same-origin', priority: it.kind === 'hero' ? 'high' : 'low' })
            .then(function (res) {
                if (!res.ok) throw new Error(res.status);
                var len = +res.headers.get('content-length') || 0;
                if (len && spent + len > BUDGET && it.kind !== 'hero') throw new Error('budget');
                spent += len;
                if (wantBlob && (!len || blobSpent + len <= BLOB_BUDGET)) {
                    return res.blob().then(function (b) {
                        blobSpent += b.size;
                        it.blob = URL.createObjectURL(b);
                        swapToBlob(it);
                    });
                }
                // лише кеш: дочитуємо тіло, щоб запис у кеші був повним
                return res.arrayBuffer().then(function () {});
            })
            .catch(function () {})
            .then(function () { it.done = true; it.fetching = false; checkHero(); });
    }

    function pump() {
        var active = items.filter(function (i) { return i.fetching; }).length;
        var next = items.filter(function (i) { return !i.done && !i.fetching; });
        if (saveData) next = next.filter(function (i) { return i.kind === 'hero'; });
        while (active < CONCURRENCY && next.length) {
            fetchItem(next.shift()).then(pump);
            active++;
        }
        if (!next.length && !active) checkHero();
    }

    function start(opts) {
        if (started) return heroPromise;
        started = true;
        opts = opts || {};
        collect();
        if (opts.urls) { // додаткові URL поза DOM (напр. ролики наступних екранів із серверного списку)
            opts.urls.forEach(function (u) { u = abs(u); if (!byUrl[u]) { var it = { url: u, els: [], kind: 'scroll', size: 0, blob: null, done: false, fetching: false }; byUrl[u] = it; items.push(it); } });
        }
        if (!items.length || !w.fetch) { heroDone = true; heroResolve(); return heroPromise; }
        pump();
        return heroPromise;
    }

    /* гейт для прелоадера: hero докачано або минув таймаут */
    function gate(timeoutMs) {
        if (!started) start();
        return Promise.race([heroPromise, new Promise(function (r) { setTimeout(r, timeoutMs || 6000); })]);
    }

    function resolve(url) {
        var it = byUrl[abs(url)];
        return it && it.blob ? it.blob : url;
    }

    // звільняємо blob при виході зі сторінки
    w.addEventListener('pagehide', function () {
        items.forEach(function (i) { if (i.blob) { try { URL.revokeObjectURL(i.blob); } catch (e) {} } });
    });

    w.videoWarmup = { start: start, gate: gate, resolve: resolve, items: items, isMobile: mobile };
})(window, document);
