function getURLVar(key) {
    var value = [];

    var query = String(document.location).split('?');

    if (query[1]) {
        var part = query[1].split('&');

        for (i = 0; i < part.length; i++) {
            var data = part[i].split('=');

            if (data[0] && data[1]) {
                value[data[0]] = data[1];
            }
        }

        if (value[key]) {
            return value[key];
        } else {
            return '';
        }
    }
}

function getThemeCartProductIds() {
    var cartNode = document.querySelector('#cart');
    var productIds = [];

    if (cartNode && cartNode.dataset.cartProducts) {
        try {
            productIds = JSON.parse(cartNode.dataset.cartProducts);
        } catch (e) {
            productIds = [];
        }
    }

    return productIds.map(function(productId) {
        return String(productId);
    });
}

function openThemeCart() {
    var cartPopup = document.querySelector('.busket');
    var cartOpenButton = document.querySelector('.cart-open');

    if (cartPopup) {
        cartPopup.classList.add('active');
    } else {
        window.location.href = 'index.php?route=checkout/cart';
    }

    if (cartOpenButton) {
        cartOpenButton.classList.add('opened');
    }
}

function reloadThemeCart(callback) {
    $('#cart').load('index.php?route=common/cart/info #cart > *', function(responseText) {
        var updatedCart = $('<div>').append($.parseHTML(responseText)).find('#cart');

        if (updatedCart.length && updatedCart.attr('data-cart-products') !== undefined) {
            $('#cart').attr('data-cart-products', updatedCart.attr('data-cart-products'));
        }

        syncProductCartButtons();

        if (typeof callback === 'function') {
            callback();
        }
    });
}

function setProductButtonAdded(button) {
    if (!button) {
        return;
    }

    if (!button.dataset.defaultHtml) {
        button.dataset.defaultHtml = button.innerHTML;
    }

    var label = button.querySelector('span');
    var icon = button.querySelector('svg');
    var addedText = button.dataset.addedText || 'Додано';

    if (label) {
        label.textContent = addedText;
    } else {
        button.textContent = addedText;
    }

    if (icon) {
        icon.remove();
    }

    button.classList.add('added');
}

function resetProductButtonAdded(button) {
    if (!button) {
        return;
    }

    if (button.dataset.defaultHtml) {
        button.innerHTML = button.dataset.defaultHtml;
    }

    button.classList.remove('added');
}

function addThemeProductCardToCart(button, productId, quantity) {
    if (!button || !productId) {
        return;
    }

    if (button.classList.contains('added')) {
        openThemeCart();
        return;
    }

    cart.add(productId, quantity || 1, function() {
        if (window.ShokeruGA4 && typeof window.ShokeruGA4.trackAddToCartFromElement === 'function') {
            window.ShokeruGA4.trackAddToCartFromElement(button, quantity || 1);
        }

        setProductButtonAdded(button);
        openThemeCart();
    });
}

function syncProductCartButtons() {
    var productIds = getThemeCartProductIds();

    document.querySelectorAll('[data-product-id][data-added-text]').forEach(function(button) {
        if (productIds.indexOf(String(button.dataset.productId)) !== -1) {
            setProductButtonAdded(button);
        } else {
            resetProductButtonAdded(button);
        }
    });
}

document.addEventListener('DOMContentLoaded', syncProductCartButtons);

function getThemeWishlistProductIds() {
    var body = document.body;
    var productIds = [];

    if (body && body.dataset.wishlistProducts) {
        try {
            productIds = JSON.parse(body.dataset.wishlistProducts);
        } catch (e) {
            productIds = [];
        }
    }

    return productIds.map(function(productId) {
        return String(productId);
    });
}

function setWishlistButtonActive(button) {
    if (!button) {
        return;
    }

    button.classList.add('active');
}

function resetWishlistButtonActive(button) {
    if (!button) {
        return;
    }

    button.classList.remove('active');
}

function syncWishlistButtons() {
    var productIds = getThemeWishlistProductIds();

    document.querySelectorAll('.product__item-fav[data-product-id], .prod__fav[data-product-id]').forEach(function(button) {
        if (productIds.indexOf(String(button.dataset.productId)) !== -1) {
            setWishlistButtonActive(button);
        } else {
            resetWishlistButtonActive(button);
        }
    });
}

function updateThemeWishlistState(productIds, totalCount) {
    var body = document.body;
    var normalizedIds = Array.isArray(productIds) ? productIds.map(function(productId) {
        return String(productId);
    }) : [];
    var count = typeof totalCount === 'number' ? totalCount : normalizedIds.length;
    var headerWishlistCount = document.querySelector('.header__favourite span');
    var profileWishlistCount = document.querySelector('[data-profile-btn="tracked-products"] span');

    if (body) {
        body.dataset.wishlistProducts = JSON.stringify(normalizedIds);
        body.dataset.wishlistTotal = String(count);
    }

    if (headerWishlistCount) {
        headerWishlistCount.textContent = count;
    }

    if (profileWishlistCount) {
        profileWishlistCount.textContent = count;
    }

    syncWishlistButtons();
}

function renderWishlistEmptyState() {
    var profileResult = document.querySelector('.profile-products[data-empty-text]');
    var productsBlock = profileResult ? profileResult.querySelector('.profile__products-block') : null;
    var emptyText;
    var emptyNode;

    if (!profileResult || productsBlock) {
        return;
    }

    emptyText = profileResult.dataset.emptyText || '';
    emptyNode = profileResult.querySelector('.profile__result-name');

    if (!emptyNode && emptyText) {
        emptyNode = document.createElement('p');
        emptyNode.className = 'profile__result-name';
        emptyNode.textContent = emptyText;
        profileResult.appendChild(emptyNode);
    }
}

function removeWishlistProductCard(productId) {
    var wishlistBlock = document.querySelector('.profile__products-block') || document.querySelector('.shop__results[data-wishlist-results]');

    if (!wishlistBlock) {
        return;
    }

    wishlistBlock.querySelectorAll('[data-product-id="' + productId + '"]').forEach(function(button) {
        var productCard = button.closest('.product__item');

        if (productCard && productCard.parentNode === wishlistBlock) {
            productCard.remove();
        }
    });

    if (wishlistBlock.querySelector('.product__item')) {
        return;
    }

    if (wishlistBlock.matches('.profile__products-block')) {
        wishlistBlock.remove();
        renderWishlistEmptyState();
        return;
    }

    if (wishlistBlock.matches('.shop__results[data-wishlist-results]')) {
        var emptyText = wishlistBlock.dataset.emptyText || '';
        var emptyNode = document.createElement('p');

        emptyNode.className = 'shop__empty';
        emptyNode.setAttribute('data-wishlist-empty', '');
        emptyNode.textContent = emptyText;
        wishlistBlock.replaceWith(emptyNode);
    }
}

document.addEventListener('DOMContentLoaded', syncWishlistButtons);

function formatInternationalPhone(value) {
    var clean = String(value || '')
        .replace(/[^\d+]/g, '')
        .replace(/(?!^)\+/g, '');

    if (clean.charAt(0) !== '+') {
        clean = '+' + clean.replace(/\+/g, '');
    }

    var digits = clean.replace(/\D/g, '').slice(0, 15);

    if (!digits) {
        return '';
    }

    var groups = [];
    var cursor = 0;
    var pattern = [3, 2, 3, 2, 2, 2];

    pattern.forEach(function(size) {
        if (cursor >= digits.length) {
            return;
        }

        groups.push(digits.slice(cursor, cursor + size));
        cursor += size;
    });

    if (cursor < digits.length) {
        groups.push(digits.slice(cursor));
    }

    return '+' + groups.join(' ');
}

function applyInternationalPhoneMask(input) {
    // поля з [data-phone-input] ведуть intl-tel-input (theme/shokeru/js/phone.js) — стара маска їх не чіпає
    if (!input || input.hasAttribute('data-phone-input')) {
        return;
    }

    input.setAttribute('inputmode', 'tel');
    input.setAttribute('autocomplete', 'tel');

    if (!input.placeholder) {
        input.placeholder = '+380 67 123 45 67';
    }

    input.value = formatInternationalPhone(input.value);
}

function bindInternationalPhoneMasks(root) {
    (root || document).querySelectorAll('input[type="tel"], input[name="telephone"], input[name="recipient_telephone"]').forEach(applyInternationalPhoneMask);
}

document.addEventListener('DOMContentLoaded', function() {
    bindInternationalPhoneMasks(document);
});

document.addEventListener('focusin', function(event) {
    if (event.target.matches('input[type="tel"], input[name="telephone"], input[name="recipient_telephone"]')) {
        applyInternationalPhoneMask(event.target);
    }
});

document.addEventListener('input', function(event) {
    if (event.target.matches('input[type="tel"], input[name="telephone"], input[name="recipient_telephone"]') && !event.target.hasAttribute('data-phone-input')) {
        event.target.value = formatInternationalPhone(event.target.value);
        event.target.setSelectionRange(event.target.value.length, event.target.value.length);
    }
});


$('#search-form').on('submit', function(e) {
    e.preventDefault(); // щоб форма не сабмітилася стандартно

    var action = $(this).attr('action'); // базовий URL
    var query = $(this).find('input[name="search"]').val().trim();

    if (!query) return; // якщо пусто, нічого не робимо

    // Створюємо URL пошуку
    var url = action + '&search=' + encodeURIComponent(query);

    // Перенаправляємо
    window.location.href = url;
});


/* Єдині тости (window.shokeruToast, js/features.js): кошик, обране, порівняння, помилки ajax */
function themeToast(message, type) {
    if (message && typeof window.shokeruToast === 'function') {
        window.shokeruToast(message, type);
    }
}

function themeFirstError(error) {
    if (!error) {
        return '';
    }

    if (typeof error === 'string') {
        return error;
    }

    var first = '';

    Object.keys(error).some(function(key) {
        var value = error[key];

        if (value && typeof value === 'object') {
            value = themeFirstError(value);
        }

        first = value ? String(value) : '';

        return !!first;
    });

    return first;
}

function themeAjaxError(xhr, thrownError) {
    themeToast(thrownError || (xhr && xhr.statusText) || '', 'danger');

    if (window.console && xhr) {
        console.error(thrownError, xhr.status, xhr.responseText);
    }
}

// Cart add remove functions
var cart = {
    'add': function(product_id, quantity, callback) {
        $.ajax({
            url: 'index.php?route=checkout/cart/add',
            type: 'post',
            data: 'product_id=' + product_id + '&quantity=' + (typeof(quantity) != 'undefined' ? quantity : 1),
            dataType: 'json',
            success: function(json) {

                if (json['redirect']) {
                    location = json['redirect'];
                }

                if (json['error']) {
                    themeToast(themeFirstError(json['error']), 'danger');
                }

                if (json['success']) {
                    $('.header__cart span').text(json['total']);
                    reloadThemeCart();
                    themeToast(json['success'], 'success');

                    if (typeof callback === 'function') {
                        callback(json);
                    }
                }
            },
            error: function(xhr, ajaxOptions, thrownError) {
                themeAjaxError(xhr, thrownError);
            }
        });
    },
    'update': function(key, quantity) {
        $.ajax({
            url: 'index.php?route=checkout/cart/edit',
            type: 'post',
            data: 'key=' + key + '&quantity=' + (typeof(quantity) != 'undefined' ? quantity : 1),
            dataType: 'json',
            success: function(json) {
                $('.header__cart span').text(json['total']);
                if (getURLVar('route') == 'checkout/cart' || getURLVar('route') == 'checkout/checkout') {
                    location = 'index.php?route=checkout/cart';
                } 

                else {
                    reloadThemeCart();
                }
            },
            error: function(xhr, ajaxOptions, thrownError) {
                themeAjaxError(xhr, thrownError);
            }
        });
    },
    'remove': function(key) {
        var cartItem = document.querySelector('#cart [data-cart-id="' + key + '"]');
        var ga4Item = null;

        if (window.ShokeruGA4 && cartItem && typeof window.ShokeruGA4.getItemFromNode === 'function') {
            ga4Item = window.ShokeruGA4.getItemFromNode(cartItem);
        }

        $.ajax({
            url: 'index.php?route=checkout/cart/remove',
            type: 'post',
            data: 'key=' + key,
            dataType: 'json',
            success: function(json) {
                // Need to set timeout otherwise it wont update the total
                $('.header__cart span').text(json['total']);

                if (window.ShokeruGA4 && ga4Item && typeof window.ShokeruGA4.trackRemoveFromCart === 'function') {
                    window.ShokeruGA4.trackRemoveFromCart(ga4Item);
                }

                if (getURLVar('route') == 'checkout/cart' || getURLVar('route') == 'checkout/checkout') {
                    location = 'index.php?route=checkout/cart';
                } else {
                    reloadThemeCart();
                }
            },
            error: function(xhr, ajaxOptions, thrownError) {
                themeAjaxError(xhr, thrownError);
            }
        });
    }
}

var voucher = {
    'add': function() {

    },
    'remove': function(key) {
        $.ajax({
            url: 'index.php?route=checkout/cart/remove',
            type: 'post',
            data: 'key=' + key,
            dataType: 'json',
            beforeSend: function() {
                $('#cart > button').button('loading');
            },
            complete: function() {
                $('#cart > button').button('reset');
            },
            success: function(json) {


                if (getURLVar('route') == 'checkout/cart' || getURLVar('route') == 'checkout/checkout') {
                    location = 'index.php?route=checkout/cart';
                } else {
                    $('#cart > ul').load('index.php?route=common/cart/info ul li');
                }
            },
            error: function(xhr, ajaxOptions, thrownError) {
                themeAjaxError(xhr, thrownError);
            }
        });
    }
}

var wishlist = {
    'request': function(route, data, callback) {
        $.ajax({
            url: 'index.php?route=' + route,
            type: 'post',
            data: data,
            dataType: 'json',
            success: function(json) {
                if (json['redirect']) {
                    location = json['redirect'];
                    return;
                }

                if (Array.isArray(json['product_ids'])) {
                    updateThemeWishlistState(json['product_ids'], parseInt(json['total_count'], 10) || json['product_ids'].length);
                }

                if (json['success']) {
                    themeToast(json['success'], 'success');
                }

                if (typeof callback === 'function') {
                    callback(json);
                }
            },
            error: function(xhr, ajaxOptions, thrownError) {
                themeAjaxError(xhr, thrownError);
            }
        });
    },
    'add': function(product_id, callback) {
        this.request('account/wishlist/add', 'product_id=' + product_id, callback);
    },
    'remove': function(product_id, callback) {
        this.request('account/wishlist/remove', 'product_id=' + product_id, callback);
    },
    'clear': function(callback) {
        this.request('account/wishlist/clear', '', callback);
    },
    'toggle': function(product_id, button, options) {
        var settings = options || {};
        var isActive = button && button.classList.contains('active');
        var onComplete = function(json) {
            var productId = String(product_id);

            if (json['active']) {
                setWishlistButtonActive(button);
            } else {
                resetWishlistButtonActive(button);
            }

            if (settings.removeCard || (!json['active'] && (getURLVar('route') === 'account/wishlist' || document.querySelector('.shop__results[data-wishlist-results]')))) {
                removeWishlistProductCard(productId);
            }
        };

        if (isActive) {
            this.remove(product_id, onComplete);
        } else {
            this.add(product_id, onComplete);
        }
    }
}

document.addEventListener('click', function(event) {
    var productCartButton = event.target.closest('.product__item-btn[data-product-id][data-added-text]');
    var wishlistButton = event.target.closest('.product__item-fav[data-product-id], .prod__fav[data-product-id]');
    var wishlistDeleteButton = event.target.closest('.product__item-delete[data-product-id]');
    var wishlistResetButton = event.target.closest('.profile__products-reset');

    if (productCartButton) {
        event.preventDefault();
        addThemeProductCardToCart(productCartButton, productCartButton.dataset.productId, productCartButton.dataset.quantity);
        return;
    }

    if (wishlistButton) {
        event.preventDefault();
        wishlist.toggle(wishlistButton.dataset.productId, wishlistButton);
        return;
    }

    if (wishlistDeleteButton) {
        event.preventDefault();
        wishlist.remove(wishlistDeleteButton.dataset.productId, function() {
            removeWishlistProductCard(String(wishlistDeleteButton.dataset.productId));
        });
        return;
    }

    if (wishlistResetButton) {
        event.preventDefault();
        wishlist.clear(function() {
            var wishlistBlock = document.querySelector('.profile__products-block');

            if (wishlistBlock) {
                wishlistBlock.remove();
            }

            renderWishlistEmptyState();
        });
    }
});

var compare = {
    'add': function(product_id) {
        $.ajax({
            url: 'index.php?route=product/compare/add',
            type: 'post',
            data: 'product_id=' + product_id,
            dataType: 'json',
            success: function(json) {
                $('.alert-dismissible').remove();

                if (json['success']) {

                    $('#compare-total').html(json['total']);

                    themeToast(json['success'], 'success');
                }
            },
            error: function(xhr, ajaxOptions, thrownError) {
                themeAjaxError(xhr, thrownError);
            }
        });
    },
    'remove': function() {

    }
}

/* alert dismiss (заміна bootstrap.js) */
document.addEventListener('click', function (e) {
    var b = e.target.closest ? e.target.closest('[data-dismiss="alert"]') : null;
    if (b) {
        var a = b.closest('.alert');
        if (a && a.parentNode) { a.parentNode.removeChild(a); }
    }
});

/* /o-nas — попап повного опису */
(function () {
    function modal() { return document.querySelector('[data-about-modal]'); }
    function open() { var m = modal(); if (!m) return; m.hidden = false; document.body.classList.add('about-modal-open'); }
    function close() { var m = modal(); if (!m) return; m.hidden = true; document.body.classList.remove('about-modal-open'); }
    document.addEventListener('click', function (e) {
        if (e.target.closest && e.target.closest('[data-about-open]')) { e.preventDefault(); open(); }
        else if (e.target.closest && e.target.closest('[data-about-close]')) { close(); }
    });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape') close(); });
})();

/* ===== Passwordless вхід/реєстрація по коду на email ===== */
window.ShokeruOtp = (function () {
    function txt(form, key, def) { return form.getAttribute('data-' + key) || def; }

    function init(form, cb) {
        if (!form || form.dataset.otpBound) return;
        form.dataset.otpBound = '1';

        var type       = form.getAttribute('data-otp-type') || 'login';
        var fieldsWrap = form.querySelector('.otp-step-fields');
        var codeStep   = form.querySelector('.otp-step-code');
        var submitBtn  = form.querySelector('.otp-submit');
        var codeInput  = form.querySelector('.otp-code-input');
        var emailInput = form.querySelector('input[name="email"]');
        var emailItem  = form.querySelector('.otp-email-item');
        var emailCheck = form.querySelector('.otp-email-check');
        var emailLabel = form.querySelector('.otp-email-label');
        var resendBtn  = form.querySelector('.otp-resend');
        var timerEl    = form.querySelector('.otp-timer');
        var successEl  = form.querySelector('.otp-success');
        var agree      = form.querySelector('input[name="agree"]');

        var LBL_GET    = submitBtn ? submitBtn.textContent.trim() : '';
        var LBL_VERIFY = txt(form, 'verify-label', '');
        var SEND_URL   = txt(form, 'send-url', 'index.php?route=common/user_popup/sendCode').replace(/&amp;/g, '&');
        var VERIFY_URL = txt(form, 'verify-url', 'index.php?route=common/user_popup/verifyCode').replace(/&amp;/g, '&');
        var ERR_NET    = txt(form, 'error-network', '');
        var ERR_CODE   = txt(form, 'code-required', '');
        var ERR_AGREE  = txt(form, 'error-agree', '');
        var stage = 'send';
        var timerId = null;

        function err(msg) { if (cb && cb.showError) cb.showError(msg); }
        function clr() { if (cb && cb.clearError) cb.clearError(); }
        function errText(e) { return (typeof e === 'object' && e) ? Object.keys(e).map(function (k) { return e[k]; }).join(' ') : (e || ERR_NET); }

        function startTimer() {
            var left = 60;
            if (resendBtn) { resendBtn.disabled = true; }
            if (timerEl) timerEl.textContent = '(' + left + ')';
            clearInterval(timerId);
            timerId = setInterval(function () {
                left--;
                if (left <= 0) { clearInterval(timerId); if (resendBtn) resendBtn.disabled = false; if (timerEl) timerEl.textContent = ''; }
                else if (timerEl) timerEl.textContent = '(' + left + ')';
            }, 1000);
        }

        function showCodeStep() {
            if (fieldsWrap) fieldsWrap.hidden = true;
            if (codeStep) codeStep.hidden = false;
            if (emailLabel) emailLabel.textContent = emailInput.value.trim();
            if (submitBtn) submitBtn.textContent = LBL_VERIFY;
            if (codeInput) { codeInput.value = ''; setTimeout(function () { codeInput.focus(); }, 60); }
            startTimer();
        }

        function onSuccess(j) {
            if (codeStep) codeStep.hidden = true;
            if (fieldsWrap) fieldsWrap.hidden = false;
            if (emailInput) emailInput.readOnly = true;
            if (emailItem) emailItem.classList.add('is-verified');
            if (emailCheck) emailCheck.classList.add('is-on');
            if (submitBtn) submitBtn.style.display = 'none';
            if (agree) { var row = agree.closest('.otp-agree-row'); if (row) row.style.display = 'none'; }
            if (successEl) { successEl.hidden = false; successEl.textContent = j.message || ''; }
            if (j.message && typeof window.shokeruToast === 'function') window.shokeruToast(j.message, 'success');
            clr();
            setTimeout(function () { window.location.href = j.redirect || 'index.php?route=account/account'; }, 1500);
        }

        function send() {
            clr();
            if (stage === 'send' && agree && agree.type === 'checkbox' && !agree.checked) { err(cb && cb.agreeError ? cb.agreeError : ERR_AGREE); return; }
            var data = new FormData(form);
            data.append('type', type);
            if (submitBtn) submitBtn.disabled = true;
            fetch(SEND_URL, { method: 'POST', body: data, credentials: 'same-origin' })
                .then(function (r) { return r.json(); })
                .then(function (j) {
                    if (submitBtn) submitBtn.disabled = false;
                    if (j.success) { stage = 'verify'; showCodeStep(); }
                    else err(errText(j.error));
                })
                .catch(function () { if (submitBtn) submitBtn.disabled = false; err(ERR_NET); });
        }

        function verify() {
            clr();
            var code = codeInput ? codeInput.value.trim() : '';
            if (!code) { err(ERR_CODE); return; }
            if (submitBtn) submitBtn.disabled = true;
            fetch(VERIFY_URL, {
                method: 'POST',
                body: new URLSearchParams({ email: emailInput.value.trim(), code: code, redirect: (form.querySelector('[data-popup-redirect]') || {}).value || '' }),
                credentials: 'same-origin'
            })
                .then(function (r) { return r.json(); })
                .then(function (j) {
                    if (submitBtn) submitBtn.disabled = false;
                    if (j.success) onSuccess(j);
                    else err(errText(j.error));
                })
                .catch(function () { if (submitBtn) submitBtn.disabled = false; err(ERR_NET); });
        }

        form.addEventListener('submit', function (e) { e.preventDefault(); (stage === 'send') ? send() : verify(); });
        if (resendBtn) resendBtn.addEventListener('click', function () { if (!resendBtn.disabled) send(); });
        if (emailInput) emailInput.addEventListener('input', function () { if (stage === 'verify') { stage = 'send'; if (codeStep) codeStep.hidden = true; if (fieldsWrap) fieldsWrap.hidden = false; if (submitBtn) submitBtn.textContent = LBL_GET; } });
    }

    // автопідключення всіх OTP-форм попапа: помилки — у .otp-error форми і тостом
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.otp-form').forEach(function (form) {
            var box = form.querySelector('.otp-error');

            init(form, {
                showError: function (msg) {
                    if (box) { box.textContent = msg; box.style.display = 'block'; }
                    if (msg && typeof window.shokeruToast === 'function') window.shokeruToast(msg, 'danger');
                },
                clearError: function () { if (box) { box.textContent = ''; box.style.display = 'none'; } },
                agreeError: form.getAttribute('data-error-agree') || ''
            });
        });
    });

    return { init: init };
})();
