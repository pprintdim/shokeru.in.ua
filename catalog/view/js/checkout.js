/* Shokeru — single-page checkout (same flow as the other stores of the group: shoker.in.ua /
   secured.in.ua). The form only collects the data; this script drives the stock endpoints in a
   fetch chain: guest/save (shipping_address=1) or payment_address/save + shipping_address/save
   -> GET shipping_method -> shipping_method/save -> GET payment_method -> payment_method/save
   -> GET confirm (creates the order) -> POST extension/payment/<code>/confirm -> success.
   WayForPay: extension/payment/wayforpay/pay returns the signed form, the browser goes there.
   No jQuery. Loaded only on checkout/checkout (document->addScript). */
(function () {
  'use strict';

  // Phone mask (intl-tel-input) normally comes globally from the header; if this page has none,
  // load the theme copy on demand so the checkout phone field still gets the country picker.
  function ensurePhone() {
    if (window.shokeruPhone || window.intlTelInput || document.querySelector('script[src*="/js/phone.js"]')) return;
    if (!document.querySelector('[data-phone-input]')) return;

    var base = 'catalog/view/theme/shokeru/';
    var css = document.createElement('link');
    css.rel = 'stylesheet';
    css.href = base + 'vendor/intlTelInput.css';
    document.head.appendChild(css);

    function load(src, done) {
      var s = document.createElement('script');
      s.src = src;
      s.onload = done;
      document.head.appendChild(s);
    }

    load(base + 'vendor/intlTelInput.min.js', function () {
      load(base + 'js/phone.js', function () {
        // phone.js initialises on DOMContentLoaded, which has already fired here
        if (window.shokeruPhone && window.shokeruPhone.init) window.shokeruPhone.init(document);
      });
    });
  }

  function phoneValue(input) {
    if (!input) return '';
    if (window.shokeruPhone && window.shokeruPhone.value) return window.shokeruPhone.value(input);
    return input.value;
  }

  function init() {
    var form = document.querySelector('[data-checkout-form]');
    if (!form) return;

    ensurePhone();

    var configNode = form.querySelector('[data-checkout-config]');
    var config = {};

    try { config = JSON.parse(configNode ? configNode.textContent : '{}'); } catch (e) { config = {}; }

    var logged = !!config.logged;
    var shippingRequired = !!config.shippingRequired;
    var destinationTypes = config.destinationTypes || {};
    var pickup = config.pickup || { city: '', address: '' };
    var text = config.text || {};
    var successUrl = config.success || '';

    var base = (form.getAttribute('data-base') || '').replace(/\/?$/, '/');
    var errorBox = form.querySelector('[data-checkout-error]');
    var submitBtn = form.querySelector('[data-checkout-submit]');
    var shippingSelect = form.querySelector('[data-shipping-select]');
    var pickupInfo = form.querySelector('[data-pickup-info]');
    var destinationBlocks = form.querySelectorAll('[data-destination-block]');
    var cityField = form.querySelector('[data-city]');
    var destField = form.querySelector('[data-destination]');
    var destLabel = form.querySelector('[data-destination-label]');
    var loader = document.querySelector('[data-checkout-loader]');

    // "Sign in" opens the theme login popup when it is on the page
    var loginLink = form.querySelector('[data-co-login]');
    if (loginLink) {
      loginLink.addEventListener('click', function (event) {
        var popup = document.querySelector('.userPopup');
        if (!popup) return;
        event.preventDefault();
        popup.classList.add('active');
      });
    }

    function url(route) {
      return base + 'index.php?route=' + route;
    }

    function value(name) {
      // radios (payment_method) -> the checked one; everything else -> the field itself
      var field = form.querySelector('[name="' + name + '"]:checked') || form.querySelector('[name="' + name + '"]');
      if (field && field.hasAttribute('data-phone-input')) return phoneValue(field).trim();
      return field ? field.value.trim() : '';
    }

    function selectedShipping() {
      if (!shippingSelect) return null;

      var option = shippingSelect.options[shippingSelect.selectedIndex];
      if (!option) return null;

      return { code: option.getAttribute('data-code'), receive: option.getAttribute('data-receive'), title: option.textContent.trim() };
    }

    function holder(field) {
      return field.closest('.checkout-field') || field.closest('label') || field.parentNode;
    }

    function clearErrors() {
      if (errorBox) {
        errorBox.hidden = true;
        errorBox.textContent = '';
      }

      form.querySelectorAll('.has-error').forEach(function (el) { el.classList.remove('has-error'); });
    }

    function setLoading(on) {
      if (loader) {
        loader.hidden = !on;
        loader.classList.toggle('is-visible', on);
      }

      if (!submitBtn) return;

      if (on) {
        submitBtn.disabled = true;
        submitBtn.classList.add('loading');
        submitBtn.setAttribute('data-reset-text', submitBtn.textContent);

        var loading = submitBtn.getAttribute('data-loading-text');
        if (loading) submitBtn.textContent = loading;
      } else {
        submitBtn.disabled = false;
        submitBtn.classList.remove('loading');

        var reset = submitBtn.getAttribute('data-reset-text');
        if (reset) submitBtn.textContent = reset;
      }
    }

    function fail(message, field) {
      setLoading(false);

      if (errorBox) {
        errorBox.textContent = String(message || text.failed || '').replace(/<[^>]+>/g, '');
        errorBox.hidden = false;
      }

      if (field) {
        holder(field).classList.add('has-error');
        field.focus();
        field.scrollIntoView({ block: 'center', behavior: 'smooth' });
      } else if (errorBox) {
        errorBox.scrollIntoView({ block: 'center', behavior: 'smooth' });
      }
    }

    // Selected delivery option -> pickup note or city + destination field with the matching label.
    function syncShipping() {
      var shipping = selectedShipping();
      var isPickup = !shipping || shipping.receive === 'pickup';

      if (pickupInfo) pickupInfo.hidden = !isPickup;

      destinationBlocks.forEach(function (block) { block.hidden = isPickup; });

      if (!isPickup && destField) {
        var type = destinationTypes[shipping.receive] || destinationTypes.branch || { label: '' };

        destField.setAttribute('data-receive-type', shipping.receive);
        destField.setAttribute('placeholder', type.label || '');
        destField.setAttribute('autocomplete', shipping.receive === 'courier' ? 'street-address' : 'off');

        if (destLabel) destLabel.textContent = type.label || '';
      }
    }

    if (shippingSelect) {
      shippingSelect.addEventListener('change', function () { clearErrors(); syncShipping(); });
      syncShipping();
    }

    form.querySelectorAll('input, select, textarea').forEach(function (field) {
      field.addEventListener('input', function () {
        holder(field).classList.remove('has-error');
      });
    });

    // ---- Delivery suggestions (pattern hydrophob): the city comes from tool/city (local Nova Poshta
    // directory oc_np_warehouse, file fallback), branches / postomats from extension/module/warehouse_suggest
    // for the chosen city. Ukrposhta and courier keep a free-text field.
    function suggest(input, list, source) {
      if (!input || !list) return;

      var timer = null;
      var active = -1;
      var seq = 0;

      function items() { return list.querySelectorAll('.co-suggest__item'); }

      function close() {
        list.hidden = true;
        active = -1;
        input.setAttribute('aria-expanded', 'false');
      }

      function mark(index) {
        var rows = items();
        if (!rows.length) return;
        active = (index + rows.length) % rows.length;
        rows.forEach(function (row, i) { row.setAttribute('aria-selected', i === active ? 'true' : 'false'); });
        rows[active].scrollIntoView({ block: 'nearest' });
      }

      function pick(row) {
        input.value = row.textContent;
        close();
        input.dispatchEvent(new Event('input', { bubbles: true }));
        input.dispatchEvent(new CustomEvent('suggest:pick', { bubbles: true }));
      }

      function render(values, emptyText) {
        list.innerHTML = '';
        active = -1;

        values.forEach(function (value) {
          var li = document.createElement('li');
          li.className = 'co-suggest__item';
          li.setAttribute('role', 'option');
          li.textContent = value;
          list.appendChild(li);
        });

        if (!values.length) {
          if (!emptyText) { close(); return; }
          var empty = document.createElement('li');
          empty.className = 'co-suggest__empty';
          empty.textContent = emptyText;
          list.appendChild(empty);
        }

        list.hidden = false;
        input.setAttribute('aria-expanded', 'true');
      }

      function load() {
        var ticket = ++seq;

        source(input.value.trim()).then(function (result) {
          if (ticket !== seq || document.activeElement !== input) return;
          if (!result) { close(); return; }
          render(result.items, result.empty);
        }).catch(close);
      }

      input.addEventListener('input', function (event) {
        if (!event.isTrusted) return;
        clearTimeout(timer);
        timer = setTimeout(load, 220);
      });
      input.addEventListener('focus', load);
      input.addEventListener('blur', function () { setTimeout(close, 150); });
      input.addEventListener('keydown', function (event) {
        if (list.hidden) return;

        if (event.key === 'ArrowDown') { event.preventDefault(); mark(active + 1); }
        else if (event.key === 'ArrowUp') { event.preventDefault(); mark(active - 1); }
        else if (event.key === 'Enter' && active > -1) { event.preventDefault(); pick(items()[active]); }
        else if (event.key === 'Escape') { close(); }
      });
      // mousedown fires before blur, so the list is still there when the row is taken
      list.addEventListener('mousedown', function (event) {
        var row = event.target.closest('.co-suggest__item');
        if (!row) return;
        event.preventDefault();
        pick(row);
      });
    }

    function getJson(route) {
      return fetch(url(route), { credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(function (response) { return response.json(); });
    }

    suggest(cityField, form.querySelector('[data-city-list]'), function (q) {
      if (q.length < 2) return Promise.resolve(null);

      return getJson('tool/city&q=' + encodeURIComponent(q)).then(function (cities) {
        return { items: Array.isArray(cities) ? cities : [], empty: '' };
      });
    });

    suggest(destField, form.querySelector('[data-destination-list]'), function (q) {
      var shipping = selectedShipping();
      var carrier = shipping ? String(shipping.code || '').replace('delivery.', '') : '';
      var receive = shipping ? shipping.receive : '';

      // warehouse directory exists for Nova Poshta and Meest; branch / postomat only
      if (['novaposhta', 'meest'].indexOf(carrier) === -1 || ['branch', 'postomat'].indexOf(receive) === -1) {
        return Promise.resolve(null);
      }

      var city = cityField ? cityField.value.trim() : '';
      if (city.length < 2) return Promise.resolve({ items: [], empty: text.cityFirst || '' });

      return getJson('extension/module/warehouse_suggest&carrier=' + carrier + '&type=' + receive +
        '&city=' + encodeURIComponent(city) + '&q=' + encodeURIComponent(q)).then(function (json) {
        return { items: (json && json.items) || [], empty: text.notFound || '' };
      });
    });

    // a new city or another carrier / receive type makes the chosen branch meaningless
    if (cityField && destField) {
      cityField.addEventListener('suggest:pick', function () {
        if (destField.getAttribute('data-receive-type') !== 'courier') destField.value = '';
      });
    }

    if (shippingSelect && destField) {
      var lastReceive = selectedShipping() ? selectedShipping().code + selectedShipping().receive : '';

      shippingSelect.addEventListener('change', function () {
        var current = selectedShipping();
        var key = current ? current.code + current.receive : '';
        if (key !== lastReceive) destField.value = '';
        lastReceive = key;
      });
    }

    function request(route, data) {
      var options = { credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' } };

      if (data) {
        options.method = 'POST';
        options.body = new URLSearchParams(data);
      }

      return fetch(url(route), options);
    }

    function post(route, data) {
      return request(route, data).then(function (response) {
        return response.json().catch(function () { return {}; });
      }).then(function (json) {
        if (json && json.error) throw json.error;
        if (json && json.redirect) throw text.failed;
        return json;
      });
    }

    function track(method, arg) {
      try {
        if (window.ShokeruGA4 && typeof window.ShokeruGA4[method] === 'function') window.ShokeruGA4[method](arg);
      } catch (e) { /* analytics must never block the order */ }
    }

    form.addEventListener('submit', function (event) {
      event.preventDefault();
      clearErrors();

      // Contacts
      var contacts = ['firstname', 'lastname', 'telephone', 'email'];

      for (var i = 0; i < contacts.length; i++) {
        var field = form.querySelector('[name="' + contacts[i] + '"]');
        if (!field) continue;

        var raw = value(contacts[i]);
        var bad = !raw;

        if (!bad && contacts[i] === 'email') bad = !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(raw);
        if (!bad && contacts[i] === 'telephone') bad = raw.replace(/\D/g, '').length < 10;

        if (bad) { fail(field.getAttribute('data-required'), field); return; }
      }

      // Shipping
      var shipping = shippingRequired ? selectedShipping() : null;
      var city = '', address1 = '', address2 = '';

      if (shippingRequired) {
        if (!shipping || !shipping.code) { fail(text.shipping, shippingSelect); return; }

        if (shipping.receive === 'pickup') {
          city = pickup.city;
          address1 = pickup.address;
        } else {
          var type = destinationTypes[shipping.receive] || destinationTypes.courier || { error: text.failed };

          city = cityField ? cityField.value.trim() : '';
          address1 = destField ? destField.value.trim() : '';

          if (city.length < 2) { fail(text.city, cityField); return; }
          if (address1.length < 3) { fail(type.error, destField); return; }

          // The chosen "carrier — receive as" option goes into address_2 for the manager.
          address2 = shipping.title;
        }
      }

      // Payment
      var payment = value('payment_method');
      if (!payment) { fail(text.payment); return; }

      var comment = value('comment');

      setLoading(true);

      if (shipping) track('trackShippingInfo', shipping.title);
      track('trackPaymentInfo', payment);

      var addressData = {
        firstname: value('firstname'),
        lastname: value('lastname'),
        email: value('email'),
        telephone: value('telephone'),
        company: '',
        address_1: address1,
        address_2: address2,
        city: city,
        postcode: '',
        country_id: config.countryId || '',
        zone_id: config.zoneId || '',
        shipping_address: '1'
      };

      var chain;

      if (logged) {
        chain = post('checkout/payment_address/save', addressData).then(function () {
          return shippingRequired ? post('checkout/shipping_address/save', addressData) : null;
        });
      } else {
        chain = post('checkout/guest/save', addressData);
      }

      if (shippingRequired) {
        chain = chain.then(function () {
          // The GET fills the session quotes the save step validates against.
          return request('checkout/shipping_method');
        }).then(function () {
          return post('checkout/shipping_method/save', { shipping_method: shipping.code, comment: comment });
        });
      }

      chain.then(function () {
        return request('checkout/payment_method');
      }).then(function () {
        return post('checkout/payment_method/save', { payment_method: payment, comment: comment, agree: '1' });
      }).then(function () {
        // The order is created here.
        return request('checkout/confirm').then(function (response) { return response.text(); });
      }).then(function () {
        // Offline payments: confirm marks the order and the browser goes to success.
        return request('extension/payment/' + payment + '/confirm', {});
      }).then(function () {
        if (payment !== 'wayforpay') {
          window.location = successUrl;
          return;
        }

        // Card: the order stays unconfirmed until WayForPay answers; the signed form takes the browser there.
        return request('extension/payment/wayforpay/pay').then(function (response) { return response.text(); }).then(function (html) {
          var box = document.createElement('div');
          box.hidden = true;
          box.innerHTML = html;

          var payForm = box.querySelector('#wayforpay_submit, form[action*="wayforpay"]');

          if (!payForm) throw text.failed;

          document.body.appendChild(box);
          payForm.submit();
        });
      }).catch(function (error) {
        var message = text.failed;

        if (typeof error === 'string') {
          message = error;
        } else if (error && typeof error === 'object') {
          var first = Object.keys(error).map(function (key) { return error[key]; })[0];
          if (typeof first === 'string') message = first;
        }

        fail(message);
      });
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
