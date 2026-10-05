(function(window, document) {
    'use strict';

    // валюта сторінки (ISO) — з <body data-currency>; ціни парсяться з DOM саме в ній
    var currency = (document.body && document.body.getAttribute('data-currency')) || 'UAH';
    var logPrefix = '[Shokeru GA4]';

    window.dataLayer = window.dataLayer || [];

    function log() {
        if (!window.console || typeof window.console.log !== 'function') {
            return;
        }

        var args = Array.prototype.slice.call(arguments);
        args.unshift(logPrefix);
        window.console.log.apply(window.console, args);
    }

    if (!window.dataLayer.__shokeruGa4Logged) {
        var originalPush = window.dataLayer.push.bind(window.dataLayer);

        window.dataLayer.push = function() {
            log('dataLayer.push', Array.prototype.slice.call(arguments));
            return originalPush.apply(window.dataLayer, arguments);
        };

        window.dataLayer.__shokeruGa4Logged = true;
    }

    function parsePrice(value) {
        var normalized = String(value || '')
            .replace(/\s+/g, '')
            .replace(/&nbsp;/g, '')
            .replace(',', '.')
            .replace(/[^\d.]/g, '');
        var parsed = parseFloat(normalized);

        return isNaN(parsed) ? 0 : parsed;
    }

    function parseJson(value) {
        if (!value) {
            return null;
        }

        try {
            return JSON.parse(value);
        } catch (e) {
            log('JSON parse failed', value, e);
            return null;
        }
    }

    function normalizeItem(item, index) {
        item = item || {};

        var normalized = {
            item_id: String(item.item_id || item.id || item.product_id || ''),
            item_name: String(item.item_name || item.name || '').trim(),
            price: parsePrice(item.price),
            quantity: parseInt(item.quantity, 10) || 1
        };

        if (item.item_brand) {
            normalized.item_brand = item.item_brand;
        }

        if (item.item_category) {
            normalized.item_category = item.item_category;
        }

        if (item.item_list_name) {
            normalized.item_list_name = item.item_list_name;
        }

        if (typeof index === 'number') {
            normalized.index = index + 1;
        }

        return normalized;
    }

    function findText(root, selectors) {
        var node = null;

        selectors.some(function(selector) {
            node = root.querySelector(selector);
            return !!node;
        });

        return node ? node.textContent.trim() : '';
    }

    function getItemFromNode(node, index) {
        if (!node) {
            return null;
        }

        var jsonItem = parseJson(node.getAttribute('data-ga4-item'));

        if (!jsonItem) {
            var ga4Source = node.matches('[data-ga4-item], [data-product-id]') ? node : node.querySelector('[data-ga4-item], [data-product-id]');
            jsonItem = ga4Source ? parseJson(ga4Source.getAttribute('data-ga4-item')) : null;
        }

        if (jsonItem) {
            return normalizeItem(jsonItem, index);
        }

        var source = node.matches('[data-product-id]') ? node : node.querySelector('[data-product-id]');
        var productId = source ? source.getAttribute('data-product-id') : node.getAttribute('data-product-id');
        var price = node.getAttribute('data-ga4-price') || (source ? source.getAttribute('data-ga4-price') : '') || findText(node, [
            '.product__item-price',
            '.prod__about-price',
            '.busket__item-price',
            '.checkout-summary__sum',
            '.checkout-summary__meta span:last-child'
        ]);
        var name = node.getAttribute('data-ga4-name') || (source ? source.getAttribute('data-ga4-name') : '') || findText(node, [
            '.product__item-title',
            '.prod__top-title',
            '.busket__item-title',
            '.checkout-summary__name',
            'h1',
            'h3'
        ]);
        var brand = node.getAttribute('data-ga4-brand') || (source ? source.getAttribute('data-ga4-brand') : '');
        var quantity = node.getAttribute('data-ga4-quantity') || node.getAttribute('data-quantity') || (source ? source.getAttribute('data-quantity') : '') || 1;

        if (!productId && !name) {
            return null;
        }

        return normalizeItem({
            item_id: productId,
            item_name: name,
            item_brand: brand,
            price: price,
            quantity: quantity
        }, index);
    }

    function ecommerceValue(items) {
        return (items || []).reduce(function(total, item) {
            return total + ((parseFloat(item.price) || 0) * (parseInt(item.quantity, 10) || 1));
        }, 0);
    }

    function pushEcommerce(eventName, ecommerce) {
        ecommerce = ecommerce || {};
        ecommerce.currency = ecommerce.currency || currency;

        window.dataLayer.push({ ecommerce: null });
        window.dataLayer.push({
            event: eventName,
            ecommerce: ecommerce
        });

        // прямий gtag (config_ga4_id заданий): той самий e-commerce подією GA4; без ID — лише dataLayer (GTM)
        if (window.shokeruGa4Id && typeof window.gtag === 'function') {
            window.gtag('event', eventName, ecommerce);
        }

        log(eventName, ecommerce);
    }

    function getCheckoutData() {
        var data = window.ShokeruGA4Checkout || {};

        return {
            items: Array.isArray(data.items) ? data.items.map(function(item) {
                return normalizeItem(item);
            }) : [],
            value: parsePrice(data.value)
        };
    }

    function trackAddToCartFromElement(element, quantity) {
        var root = element ? element.closest('.product__item, .prod__content, .prod, [data-ga4-item], [data-product-id]') : null;
        var item = getItemFromNode(root || element);

        if (!item && element && element.getAttribute('data-product-id')) {
            item = getItemFromNode(element);
        }

        if (!item) {
            log('add_to_cart skipped: no item data', element);
            return;
        }

        item.quantity = parseInt(quantity, 10) || item.quantity || 1;

        pushEcommerce('add_to_cart', {
            value: ecommerceValue([item]),
            items: [item]
        });
    }

    function trackRemoveFromCart(item) {
        item = normalizeItem(item);

        if (!item.item_id && !item.item_name) {
            log('remove_from_cart skipped: no item data', item);
            return;
        }

        pushEcommerce('remove_from_cart', {
            value: ecommerceValue([item]),
            items: [item]
        });
    }

    function initViewItemList() {
        var cards = Array.prototype.slice.call(document.querySelectorAll('.shop__results .product__item, .productSlider .product__item'));

        if (!cards.length || document.body.classList.contains('checkout-cart')) {
            return;
        }

        var items = cards.map(getItemFromNode).filter(Boolean);

        if (!items.length) {
            return;
        }

        pushEcommerce('view_item_list', {
            item_list_name: document.querySelector('h1, .title') ? document.querySelector('h1, .title').textContent.trim() : document.title,
            items: items
        });
    }

    function initViewItem() {
        var productNode = document.querySelector('.prod');

        if (!productNode) {
            return;
        }

        var item = getItemFromNode(productNode);

        if (!item) {
            return;
        }

        pushEcommerce('view_item', {
            value: ecommerceValue([item]),
            items: [item]
        });
    }

    function initCheckout() {
        var data = getCheckoutData();

        if (!data.items.length) {
            return;
        }

        pushEcommerce('begin_checkout', {
            value: data.value || ecommerceValue(data.items),
            items: data.items
        });
    }

    function initPurchase() {
        var order = window.ShokeruGA4Purchase;

        if (!order || !order.transaction_id) {
            return;
        }

        var storageKey = 'shokeru_ga4_purchase_' + order.transaction_id;

        if (window.sessionStorage && sessionStorage.getItem(storageKey)) {
            log('purchase skipped: already sent', order.transaction_id);
            return;
        }

        pushEcommerce('purchase', {
            transaction_id: order.transaction_id,
            value: parsePrice(order.value),
            tax: parsePrice(order.tax),
            shipping: parsePrice(order.shipping),
            currency: order.currency || currency,
            items: Array.isArray(order.items) ? order.items.map(function(item) { return normalizeItem(item); }) : []
        });

        if (window.sessionStorage) {
            sessionStorage.setItem(storageKey, '1');
        }
    }

    document.addEventListener('click', function(event) {
        var productLink = event.target.closest('.product__item a[href]');
        var checkoutLink = event.target.closest('.busket__btn[href*="checkout/checkout"]');

        if (productLink) {
            var card = productLink.closest('.product__item');
            var item = getItemFromNode(card);

            if (item) {
                pushEcommerce('select_item', {
                    item_list_name: document.querySelector('h1, .title') ? document.querySelector('h1, .title').textContent.trim() : document.title,
                    items: [item]
                });
            }
        }

        if (checkoutLink) {
            var cartItems = Array.prototype.slice.call(document.querySelectorAll('#cart .busket__item')).map(getItemFromNode).filter(Boolean);

            if (cartItems.length) {
                pushEcommerce('begin_checkout', {
                    value: ecommerceValue(cartItems),
                    items: cartItems
                });
            }
        }
    });

    document.addEventListener('DOMContentLoaded', function() {
        initViewItem();
        initViewItemList();
        initCheckout();
        initPurchase();
    });

    window.ShokeruGA4 = {
        log: log,
        parsePrice: parsePrice,
        getItemFromNode: getItemFromNode,
        pushEcommerce: pushEcommerce,
        trackAddToCartFromElement: trackAddToCartFromElement,
        trackRemoveFromCart: trackRemoveFromCart,
        trackShippingInfo: function(shippingTier) {
            var data = getCheckoutData();

            if (!data.items.length) {
                return;
            }

            pushEcommerce('add_shipping_info', {
                value: data.value || ecommerceValue(data.items),
                shipping_tier: shippingTier || '',
                items: data.items
            });
        },
        trackPaymentInfo: function(paymentType) {
            var data = getCheckoutData();

            if (!data.items.length) {
                return;
            }

            pushEcommerce('add_payment_info', {
                value: data.value || ecommerceValue(data.items),
                payment_type: paymentType || '',
                items: data.items
            });
        }
    };
})(window, document);
