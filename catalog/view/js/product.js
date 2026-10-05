/* Картка товару: лайтбокс галереї (оверлей, ‹ ›, Esc, свайп) */
(function () {
    document.addEventListener('DOMContentLoaded', function () {
        var slider = document.querySelector('.prod__slider[data-lightbox-close]');
        if (!slider) { return; }
        var imgs = Array.prototype.slice.call(slider.querySelectorAll('.swiper-slide img'));
        if (!imgs.length) { return; }

        var L = {
            prev: slider.getAttribute('data-lightbox-prev') || '',
            next: slider.getAttribute('data-lightbox-next') || '',
            close: slider.getAttribute('data-lightbox-close') || '',
            counter: slider.getAttribute('data-lightbox-counter') || '%s / %s'
        };
        var box, pic, count, idx = 0, startX = 0, lastFocus = null;

        function build() {
            box = document.createElement('div');
            box.className = 'plb';
            box.setAttribute('role', 'dialog');
            box.setAttribute('aria-modal', 'true');
            box.innerHTML = '<button type="button" class="plb__close" aria-label="' + L.close + '"></button>' +
                '<button type="button" class="plb__prev" aria-label="' + L.prev + '"></button>' +
                '<img class="plb__img" alt="">' +
                '<button type="button" class="plb__next" aria-label="' + L.next + '"></button>' +
                '<p class="plb__count"></p>';
            document.body.appendChild(box);
            pic = box.querySelector('.plb__img');
            count = box.querySelector('.plb__count');
            box.querySelector('.plb__close').addEventListener('click', close);
            box.querySelector('.plb__prev').addEventListener('click', function (e) { e.stopPropagation(); go(-1); });
            box.querySelector('.plb__next').addEventListener('click', function (e) { e.stopPropagation(); go(1); });
            box.addEventListener('click', function (e) { if (e.target === box) { close(); } });
            box.addEventListener('touchstart', function (e) { startX = e.changedTouches[0].clientX; }, { passive: true });
            box.addEventListener('touchend', function (e) {
                var dx = e.changedTouches[0].clientX - startX;
                if (Math.abs(dx) > 50) { go(dx < 0 ? 1 : -1); }
            }, { passive: true });
        }
        function show() {
            var im = imgs[idx];
            pic.src = im.getAttribute('data-zoom') || im.currentSrc || im.src;
            pic.alt = im.alt || '';
            count.textContent = L.counter.replace('%s', idx + 1).replace('%s', imgs.length);
        }
        function go(d) { idx = (idx + d + imgs.length) % imgs.length; show(); }
        function open(i) {
            if (!box) { build(); }
            lastFocus = document.activeElement;
            idx = i; show();
            box.classList.add('active');
            document.documentElement.classList.add('plb-lock');
            box.querySelector('.plb__close').focus();
        }
        function close() {
            box.classList.remove('active');
            document.documentElement.classList.remove('plb-lock');
            if (lastFocus && lastFocus.focus) { lastFocus.focus(); }
        }
        imgs.forEach(function (im, i) {
            im.style.cursor = 'zoom-in';
            im.addEventListener('click', function () { open(i); });
        });
        document.addEventListener('keydown', function (e) {
            if (!box || !box.classList.contains('active')) { return; }
            if (e.key === 'Escape') { close(); }
            if (e.key === 'ArrowLeft') { go(-1); }
            if (e.key === 'ArrowRight') { go(1); }
        });
    });
})();
