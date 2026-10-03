(() => {
    let catalogLoading = false;
    let autoLoadObserver = null;
    let autoLoadFallbackBound = false;

    function getAutoLoadMargin() {
        if (window.matchMedia && window.matchMedia("(max-width: 767px)").matches) {
            return 900;
        }

        if (window.matchMedia && window.matchMedia("(max-width: 1199px)").matches) {
            return 750;
        }

        return 600;
    }

    function getCatalogParts(html) {
        const doc = new DOMParser().parseFromString(html, "text/html");

        return {
            items: doc.querySelectorAll(".shop__results .shop__item"),
            bottom: doc.querySelector(".shop__bottom")
        };
    }

    function updateCatalog(url, mode) {
        const resultsContainer = document.querySelector(".shop__results");
        const bottomContainer = document.querySelector(".shop__bottom");

        if (!url || !resultsContainer || catalogLoading) return;

        catalogLoading = true;
        document.querySelectorAll(".shop__more[data-next-url]").forEach(button => {
            button.disabled = true;
            button.classList.add("loading");
        });

        fetch(url, {
            headers: {
                "X-Requested-With": "XMLHttpRequest"
            }
        })
            .then(response => {
                if (!response.ok) {
                    throw new Error("Failed to load products");
                }

                return response.text();
            })
            .then(html => {
                const catalogParts = getCatalogParts(html);

                if (mode === "append") {
                    catalogParts.items.forEach(item => {
                        resultsContainer.appendChild(item);
                    });
                } else {
                    resultsContainer.replaceChildren(...catalogParts.items);
                    resultsContainer.scrollIntoView({ behavior: "smooth", block: "start" });
                }

                if (catalogParts.bottom) {
                    if (bottomContainer) {
                        bottomContainer.replaceWith(catalogParts.bottom);
                    } else {
                        resultsContainer.insertAdjacentElement("afterend", catalogParts.bottom);
                    }
                } else if (bottomContainer) {
                    bottomContainer.remove();
                }

                if (typeof window.syncProductCartButtons === "function") {
                    window.syncProductCartButtons();
                }

                if (typeof window.syncWishlistButtons === "function") {
                    window.syncWishlistButtons();
                }

                // URL оновлюємо лише для номерної пагінації (SEO/прямі посилання).
                // Автоскрол-догрузка ("append") не чіпає адресний рядок.
                if (mode !== "append") {
                    window.history.replaceState({}, "", url);
                }
            })
            .catch(() => {
                document.querySelectorAll(".shop__more[data-next-url]").forEach(button => {
                    button.disabled = false;
                    button.classList.remove("loading");
                });
            })
            .finally(() => {
                catalogLoading = false;
                observeCatalogAutoLoad();
                checkCatalogAutoLoadFallback();
            });
    }

    function checkCatalogAutoLoadFallback() {
        const loadMoreButton = document.querySelector(".shop__more[data-next-url]");

        if (!loadMoreButton || catalogLoading) {
            return;
        }

        const rect = loadMoreButton.getBoundingClientRect();
        const viewportHeight = window.innerHeight || document.documentElement.clientHeight;

        if (rect.top <= viewportHeight + getAutoLoadMargin()) {
            updateCatalog(loadMoreButton.dataset.nextUrl, "append");
        }
    }

    function bindCatalogAutoLoadFallback() {
        if (autoLoadFallbackBound) {
            return;
        }

        autoLoadFallbackBound = true;
        window.addEventListener("scroll", checkCatalogAutoLoadFallback, { passive: true });
        window.addEventListener("resize", checkCatalogAutoLoadFallback);
        window.addEventListener("orientationchange", checkCatalogAutoLoadFallback);
    }

    function observeCatalogAutoLoad() {
        const loadMoreButton = document.querySelector(".shop__more[data-next-url]");

        if (autoLoadObserver) {
            autoLoadObserver.disconnect();
            autoLoadObserver = null;
        }

        if (!loadMoreButton) {
            return;
        }

        bindCatalogAutoLoadFallback();

        if (!("IntersectionObserver" in window)) {
            checkCatalogAutoLoadFallback();
            return;
        }

        const margin = getAutoLoadMargin();

        autoLoadObserver = new IntersectionObserver(entries => {
            entries.forEach(entry => {
                if (!entry.isIntersecting || catalogLoading) {
                    return;
                }

                const button = entry.target;
                const nextUrl = button.dataset.nextUrl;

                if (!nextUrl) {
                    return;
                }

                autoLoadObserver.disconnect();
                updateCatalog(nextUrl, "append");
            });
        }, {
            root: null,
            rootMargin: margin + "px 0px",
            threshold: 0
        });

        autoLoadObserver.observe(loadMoreButton);
        checkCatalogAutoLoadFallback();
    }

    document.addEventListener("click", event => {
        const loadMoreButton = event.target.closest(".shop__more[data-next-url]");
        if (loadMoreButton) {
            event.preventDefault();
            updateCatalog(loadMoreButton.dataset.nextUrl, "append");
            return;
        }

        const pageLink = event.target.closest(".shop__pages-content a[href]");
        if (pageLink) {
            event.preventDefault();
            if (autoLoadObserver) {
                autoLoadObserver.disconnect();
            }
            updateCatalog(pageLink.href, "replace");
        }
    });

    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", observeCatalogAutoLoad);
    } else {
        observeCatalogAutoLoad();
    }
})();

const headerTopMenu = document.querySelector('.header__top-menu');
const headerTopContainer = document.querySelector('.header__top');
const headerMenuInner = document.querySelector('.header__menu-inner');
let moved = false;
function handleResize() {
    if (window.innerWidth < 1000 && !moved) {
        if (headerTopMenu && headerMenuInner) {
            headerMenuInner.appendChild(headerTopMenu);
            moved = true;
        }
    } else if (window.innerWidth >= 1000 && moved) {
        if (headerTopMenu && headerTopContainer) {
            headerTopContainer.appendChild(headerTopMenu);
            moved = false;
        }
    }
}
handleResize();
window.addEventListener('resize', handleResize);

document.addEventListener('DOMContentLoaded', function() {
    const langSelectedAll = document.querySelectorAll('.header__lang-selected');
    langSelectedAll.forEach(langSelected => {
        const langBlock = langSelected.closest('.header__lang');
        langSelected.addEventListener('click', function(e) {
            e.preventDefault();
            langBlock.classList.toggle('active');
        });
        document.addEventListener('click', function(e) {
            if (!langBlock.contains(e.target)) {
                langBlock.classList.remove('active');
            }
        });
    });

    const openMenuBtn = document.querySelector('.menu-open');
    const closeMenuBtn = document.querySelector('.menu-close');
    const headerMenu = document.querySelector('.header__menu');
    openMenuBtn.addEventListener('click', () => {
        headerMenu.classList.add('active');
    });
    closeMenuBtn.addEventListener('click', () => {
        headerMenu.classList.remove('active');
    });

    const loginOpen = document.querySelectorAll('.header__login');
    const loginClose = document.querySelector('.userPopup__close');
    const userPopup = document.querySelector('.userPopup');
    loginOpen.forEach(element => {
        element.addEventListener('click', () => {
            userPopup.classList.add('active');
            setTimeout(() => {
                headerMenu.classList.remove('active');
            }, 300);
        });
    });
    loginClose.addEventListener('click', () => {
        userPopup.classList.remove('active');
    });
    const forgotPasswordLink = document.querySelector('.userPopup__form-forgot');
    const passwordRecoverySection = document.querySelector('section[data-userData="password-recovery"]');
    if (forgotPasswordLink && passwordRecoverySection) {
        forgotPasswordLink.addEventListener('click', function(event) {
            event.preventDefault();
            userPopup.classList.remove('active');
            passwordRecoverySection.classList.add('active');
        });
    }

    const closeButtons = document.querySelectorAll('.userData__close');
    closeButtons.forEach(button => {
        button.addEventListener('click', function() {
            const parentUserData = this.closest('[data-userData]');
            if (parentUserData) {
                parentUserData.classList.remove('active');
            }
        });
    });


    const userPopuBtn = document.querySelectorAll('.userPopup__btn');
    const userPopuResult = document.querySelectorAll('.userPopup__result');
    const userPopypSelect = document.querySelector('.userPopup__select');

    userPopuBtn.forEach(button => {
        button.addEventListener('click', () => {
            userPopuBtn.forEach(btn => btn.classList.remove('active'));
            button.classList.add('active');
            userPopuResult.forEach(result => result.classList.remove('active'));
            const targetResult = document.querySelector(`.userPopup__result[data-userPopup-result="${button.dataset.userpopupSelect}"]`);
            targetResult.classList.add('active');
            if (button.dataset.userpopupSelect === 'login') {
                userPopypSelect.classList.add('userPopup__select-login');
            } else {
                userPopypSelect.classList.remove('userPopup__select-login');
            }
        });
    });


    const showPasswordButtons = document.querySelectorAll('.form-show-password');
    showPasswordButtons.forEach(button => {
        button.addEventListener('click', () => {
            const passwordInput = button.nextElementSibling;
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                button.classList.add('showed');
            } else {
                passwordInput.type = 'password';
                button.classList.remove('showed');
            }
        });
    });

    const searchBtn = document.querySelector('.header__search-open');
    const searchBlock = document.querySelector('.header__search');
    if (searchBtn && searchBlock) {
        searchBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            searchBlock.classList.add('active');
        });
        searchBlock.addEventListener('click', function (e) {
            e.stopPropagation();
        });
        document.addEventListener('click', function () {
            searchBlock.classList.remove('active');
        });
    }

    const busket = document.querySelector('.busket');
    const busketOpenButton = document.querySelector('.cart-open');
    const busketCloseButton = document.querySelector('.busket__close');
    const busketInner = document.querySelector('.busket__inner');
    if (busketOpenButton) {
        busketOpenButton.addEventListener('click', () => {
            if (busket) {
                busket.classList.toggle('active');
                busketOpenButton.classList.toggle('opened');
            }
        });
        busketCloseButton.addEventListener('click', () => {
            if (busket) {
                busket.classList.remove('active');
                busketOpenButton.classList.remove('opened');
            }
        });
    }
    document.addEventListener('click', (event) => {
        if (busket && busket.classList.contains('active')) {
            if (!busketInner.contains(event.target) && event.target !== busketOpenButton) {
                busket.classList.remove('active');
                busketOpenButton.classList.remove('opened');
            }
        }
    });
    if (busketInner) {
        busketInner.addEventListener('click', (event) => {
            event.stopPropagation();
        });
    }
});


document.addEventListener("DOMContentLoaded", function () {
    if (typeof Swiper !== "undefined") {
        // усі слайдери гортаються трекпадом: лише горизонтальний жест, вертикальний скрол сторінки не чіпаємо
        if (Swiper.extendDefaults) {
            Swiper.extendDefaults({ mousewheel: { forceToAxis: true, thresholdDelta: 10, releaseOnEdges: true } });
        }

        let heroSwiper = new Swiper(".hero__slider", {
            loop: false,
            spaceBetween: 0,
            slidesPerView: 1,
            allowTouchMove: true,
            navigation: {
                nextEl: ".hero__slider-next",
                prevEl: ".hero__slider-prev",
            },
            pagination: {
                el: ".hero__slider-pagination",
                clickable: true,
            },
            mousewheel: { forceToAxis: true, releaseOnEdges: true },
        });

        document.querySelectorAll(".productSlider__slider").forEach(function (sliderEl) {
            var slideCount = sliderEl.querySelectorAll(".swiper-slide").length;
            var scope = sliderEl.closest(".productSlider") || document;

            // "Товари з цієї статті" (блог) — по 2 товари; решта слайдерів — до 4
            var isBlog = scope.classList && scope.classList.contains("bp-products");
            var maxPerView = isBlog ? 2 : 4;

            var breakpoints = isBlog
                ? {
                    768:  { spaceBetween: 20, slidesPerView: 2 },
                    1280: { spaceBetween: 24, slidesPerView: 2 },
                }
                : {
                    768:  { spaceBetween: 20, slidesPerView: 2 },
                    1000: { spaceBetween: 20, slidesPerView: 3 },
                    1280: { spaceBetween: 24, slidesPerView: 4 },
                };

            new Swiper(sliderEl, {
                // loop лише коли слайдів більше за максимум на екран (інакше swiper ламається)
                loop: slideCount > maxPerView,
                watchOverflow: true,            // вимикає слайдер/навігацію, якщо все вміщається
                centerInsufficientSlides: true, // центрує, коли слайдів мало (напр. 1 товар)
                spaceBetween: 12,
                slidesPerView: 2,               // моб: по 2 мінікартки
                allowTouchMove: true,
                navigation: {
                    nextEl: scope.querySelector(".productSlider-next"),
                    prevEl: scope.querySelector(".productSlider-prev"),
                },
                mousewheel: { forceToAxis: true, releaseOnEdges: true },
                breakpoints: breakpoints,
            });
        });

        // safemode: ініт по кожній секції окремо, з прив'язкою контент↔зображення
        document.querySelectorAll(".safemode").forEach(function (section) {
            var contentEl = section.querySelector(".safemode__slider");
            if (!contentEl) return;
            var sm = new Swiper(contentEl, {
                loop: false,
                spaceBetween: 10,
                slidesPerView: 1,
                allowTouchMove: true,
                navigation: {
                    nextEl: section.querySelector(".safemode__slider-next"),
                    prevEl: section.querySelector(".safemode__slider-prev"),
                },
                pagination: {
                    el: section.querySelector(".safemode__slider-pagination"),
                    clickable: true,
                },
                mousewheel: { forceToAxis: true, releaseOnEdges: true },
            });
            var imageEl = section.querySelector(".safemode__image-slider");
            if (imageEl) {
                var img = new Swiper(imageEl, {
                    loop: false,
                    spaceBetween: 10,
                    slidesPerView: 1,
                    allowTouchMove: true,
                });
                if (sm && img && sm.controller && img.controller) {
                    sm.controller.control = img;
                    img.controller.control = sm;
                }
            }
        });

        if (document.querySelector(".safeImage__slider")) {
            new Swiper(".safeImage__slider", {
                loop: true,
                spaceBetween: 0,
                slidesPerView: 1.5,
                allowTouchMove: true,
                centeredSlides: true,
                autoplay: { delay: 2500, disableOnInteraction: false, pauseOnMouseEnter: true },
                speed: 700,
                mousewheel: { forceToAxis: true, releaseOnEdges: true },
                breakpoints: {
                    768: {
                        slidesPerView: 2,
                    },
                    1000: {
                        slidesPerView: 3,
                    },
                },
            });
        }
    }

});


resizeHeight()
function resizeHeight(){
  let vh = window.innerHeight * 0.01;
  document.documentElement.style.setProperty('--vh', `${vh}px`);
}
window.addEventListener('resize', () => {
  resizeHeight()
});





document.querySelectorAll('.faq__item-title').forEach(title => {
    title.addEventListener('click', () => {
        const item = title.closest('.faq__item');
        const isActive = item.classList.contains('active');
        document.querySelectorAll('.faq__item').forEach(i => i.classList.remove('active'));
        if (!isActive) {
            item.classList.add('active');
        }
    });
});

// зелений прогрес-бар прокрутки (глобально)
(function () {
    var bar = document.getElementById('page-progress');
    if (!bar) return;
    function upd() {
        var h = document.documentElement;
        var max = (h.scrollHeight - h.clientHeight) || 1;
        bar.style.width = Math.min(100, (h.scrollTop / max) * 100) + '%';
    }
    upd();
    window.addEventListener('scroll', upd, { passive: true });
    window.addEventListener('resize', upd, { passive: true });
})();

/* shokeru-ua-phone-mask: єдина маска українського номера для всіх полів телефону */
(function () {
    function formatUA(raw) {
        var d = (raw || '').replace(/\D/g, '');
        if (d.indexOf('380') === 0) d = d.slice(3);
        else if (d.indexOf('80') === 0) d = d.slice(2);
        else if (d.indexOf('0') === 0) d = d.slice(1);
        d = d.slice(0, 9);
        if (!d) return '';
        var out = '+38 (0' + d.slice(0, 2);
        if (d.length >= 2) out += ')';
        if (d.length > 2) out += ' ' + d.slice(2, 5);
        if (d.length > 5) out += '-' + d.slice(5, 7);
        if (d.length > 7) out += '-' + d.slice(7, 9);
        return out;
    }

    function bind(input) {
        if (input.dataset.uaPhone) return;
        input.dataset.uaPhone = '1';
        input.setAttribute('inputmode', 'tel');
        if (!input.getAttribute('placeholder')) input.setAttribute('placeholder', '+38 (0__) ___-__-__');

        input.addEventListener('input', function () {
            input.value = formatUA(input.value);
        });
        input.addEventListener('focus', function () {
            if (!input.value) input.value = '+38 (0';
        });
        input.addEventListener('blur', function () {
            if (input.value === '+38 (0' || input.value === '+38 (') input.value = '';
        });
        // якщо вже є значення — відформатувати
        if (input.value) input.value = formatUA(input.value);
    }

    function scan(root) {
        var nodes = (root || document).querySelectorAll('input[name="telephone"], input[name="phone"], input[type="tel"]');
        nodes.forEach(function (el) {
            if (el.readOnly || el.disabled) return;
            if (el.name === 'code') return;
            bind(el);
        });
    }

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', function () { scan(document); });
    else scan(document);

    // поля в попапах, що зʼявляються динамічно
    document.addEventListener('focusin', function (e) {
        var t = e.target;
        if (t && t.matches && t.matches('input[name="telephone"], input[name="phone"], input[type="tel"]') && !t.readOnly && !t.dataset.uaPhone) {
            bind(t);
        }
    });
})();
