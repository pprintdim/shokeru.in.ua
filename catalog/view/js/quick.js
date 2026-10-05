/* shokeru theme — "Buy in one click" popup (common/quick_order.twig).
   Posts product_id / quantity / name / phone to common/quick_order/order,
   which e-mails the manager (no order is created) and answers with the theme
   ajax contract: {success, message} or {error, errors: {field: text}}.

   Opens from:
   - any [data-quick-order="<product_id>"] element; optional data-quick-quantity
     (else the [name="quantity"] of the enclosing form, else 1), data-quick-name
     and data-quick-price for the product line inside the popup (else the card /
     page heading nearby);
   - window.shokeruQuickOrder.open(productId, qty, meta) from other scripts.

   Close on the cross / backdrop /
   Escape, focus trap inside the panel, focus returns to the opener. Notices go
   through window.shokeruToast(message, type) from features.js; the phone field is
   [data-phone-input] and is read through window.shokeruPhone when the mask is on. */
(function () {
  'use strict';

  var modal = document.querySelector('[data-quick-modal]');
  if (!modal) return;

  var panel = modal.querySelector('.qo-modal__panel');
  var form = modal.querySelector('[data-quick-form]');
  var stepForm = form.querySelector('[data-quick-step="form"]');
  var stepDone = form.querySelector('[data-quick-step="done"]');
  var errorBox = form.querySelector('[data-quick-error]');
  var submitBtn = form.querySelector('[data-quick-submit]');
  var productBox = form.querySelector('[data-quick-product]');
  var productName = form.querySelector('[data-quick-product-name]');
  var productPrice = form.querySelector('[data-quick-product-price]');
  var productQty = form.querySelector('[data-quick-product-qty]');

  var inputs = {};
  Array.prototype.forEach.call(form.querySelectorAll('[data-quick-input]'), function (el) {
    inputs[el.getAttribute('data-quick-input')] = el;
  });

  var busy = false;
  var lastFocused = null;

  function toast(message, type) {
    if (!message) return;
    if (typeof window.shokeruToast === 'function') {
      window.shokeruToast(message, type);
    }
  }

  function post(url, data) {
    return fetch(url.replace(/&amp;/g, '&'), {
      method: 'POST',
      body: new URLSearchParams(data),
      credentials: 'same-origin',
      headers: { 'X-Requested-With': 'XMLHttpRequest' }
    }).then(function (r) { return r.json(); });
  }

  /* ---- errors ---- */

  function clearErrors() {
    Array.prototype.forEach.call(form.querySelectorAll('[data-quick-field-error]'), function (el) {
      el.textContent = '';
      el.hidden = true;
    });
    Array.prototype.forEach.call(form.querySelectorAll('.has-error'), function (el) {
      el.classList.remove('has-error');
    });
    errorBox.textContent = '';
    errorBox.hidden = true;
  }

  function fieldError(name, message) {
    var box = form.querySelector('[data-quick-field-error="' + name + '"]');
    var input = inputs[name];
    if (box) {
      box.textContent = message;
      box.hidden = false;
    }
    if (input) {
      var wrap = input.closest('.qo-modal__field');
      if (wrap) wrap.classList.add('has-error');
    }
  }

  // {error: "<text>", errors: {field: text}} — fields inline, the rest as a box + toast
  function showErrors(json) {
    var fields = json.errors && typeof json.errors === 'object' ? json.errors : {};
    var general = [];
    var focusTarget = null;

    Object.keys(fields).forEach(function (name) {
      if (form.querySelector('[data-quick-field-error="' + name + '"]')) {
        fieldError(name, fields[name]);
        if (!focusTarget && inputs[name]) focusTarget = inputs[name];
      } else {
        general.push(fields[name]);
      }
    });

    if (!Object.keys(fields).length && json.error) general.push(json.error);

    if (general.length) {
      errorBox.textContent = general.join(' ');
      errorBox.hidden = false;
      toast(general.join(' '), 'danger');
    }

    if (focusTarget) focusTarget.focus();
  }

  function setBusy(on) {
    busy = on;
    form.classList.toggle('is-busy', on);
    submitBtn.disabled = on;
  }

  function setStep(step) {
    stepForm.hidden = step === 'done';
    stepDone.hidden = step !== 'done';
  }

  /* ---- product line ---- */

  function phoneValue() {
    var input = inputs.phone;
    if (window.shokeruPhone && typeof window.shokeruPhone.value === 'function') {
      return String(window.shokeruPhone.value(input) || '').trim();
    }
    return input.value.trim();
  }

  function setProduct(productId, qty, meta) {
    meta = meta || {};
    inputs.product_id.value = String(productId || '');
    inputs.quantity.value = String(qty || 1);
    inputs.page.value = window.location.href;

    var name = (meta.name || '').trim();
    var price = (meta.price || '').trim();
    var template = form.getAttribute('data-quantity-template') || '%s';

    productName.textContent = name;
    productPrice.textContent = price;
    productQty.textContent = qty > 1 ? template.replace('%s', String(qty)) : '';
    productBox.hidden = !name && !price;
  }

  /* ---- client-side checks (the server repeats them) ---- */

  function validate() {
    var ok = true;
    var name = inputs.name.value.trim();
    var digits = phoneValue().replace(/\D/g, '');

    if (name.length < 2 || name.length > 64) {
      fieldError('name', form.getAttribute('data-error-name'));
      ok = false;
    }

    if (digits.length < 9 || digits.length > 15) {
      fieldError('phone', form.getAttribute('data-error-phone'));
      ok = false;
    }

    return ok;
  }

  function send() {
    if (busy) return;
    clearErrors();

    if (!validate()) {
      var first = form.querySelector('.has-error input');
      if (first) first.focus();
      return;
    }

    setBusy(true);

    post(form.getAttribute('action'), {
      product_id: inputs.product_id.value,
      quantity: inputs.quantity.value,
      name: inputs.name.value.trim(),
      phone: phoneValue(),
      page: inputs.page.value
    }).then(function (json) {
      setBusy(false);

      if (json.error || json.errors) {
        showErrors(json);
        return;
      }

      if (json.success) {
        // a lead for GA4 / Ads when the tag is on the page
        if (typeof window.gtag === 'function') window.gtag('event', 'generate_lead', { form: 'quick_order' });
        setStep('done');
        toast(json.message, 'success');
        window.setTimeout(function () { stepDone.querySelector('button').focus(); }, 30);
      }
    }).catch(function () {
      setBusy(false);
      errorBox.textContent = form.getAttribute('data-error-network') || '';
      errorBox.hidden = false;
      toast(form.getAttribute('data-error-network'), 'danger');
    });
  }

  /* ---- open / close / focus trap ---- */

  function focusable() {
    return Array.prototype.filter.call(
      panel.querySelectorAll('button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'),
      function (el) {
        if (el.disabled || el.hidden || el.type === 'hidden') return false;
        var node = el;
        while (node && node !== panel) {
          if (node.hidden) return false;
          node = node.parentElement;
        }
        return el.offsetParent !== null || el === document.activeElement;
      }
    );
  }

  function open(productId, qty, meta) {
    if (!productId) return;

    lastFocused = document.activeElement;
    clearErrors();
    setStep('form');
    setProduct(productId, parseInt(qty, 10) || 1, meta);

    modal.hidden = false;
    document.documentElement.classList.add('quick-open');
    // the transition needs a frame between display and the class
    requestAnimationFrame(function () {
      requestAnimationFrame(function () {
        modal.classList.add('is-open');
        var target = inputs.name.value.trim() ? inputs.phone : inputs.name;
        if (inputs.name.value.trim() && phoneValue().replace(/\D/g, '').length >= 9) target = submitBtn;
        target.focus();
      });
    });
  }

  function close() {
    if (modal.hidden) return;
    modal.classList.remove('is-open');
    document.documentElement.classList.remove('quick-open');

    window.setTimeout(function () {
      modal.hidden = true;
      setStep('form');
      if (lastFocused && typeof lastFocused.focus === 'function' && document.contains(lastFocused)) {
        lastFocused.focus();
      }
    }, 250);
  }

  function isOpen() {
    return !modal.hidden;
  }

  /* ---- wiring ---- */

  form.addEventListener('submit', function (event) {
    event.preventDefault();
    if (stepDone.hidden) send();
  });

  Array.prototype.forEach.call(modal.querySelectorAll('[data-quick-close]'), function (el) {
    el.addEventListener('click', close);
  });

  document.addEventListener('keydown', function (event) {
    if (!isOpen()) return;

    if (event.key === 'Escape') {
      event.preventDefault();
      close();
      return;
    }

    if (event.key === 'Tab') {
      var items = focusable();
      if (!items.length) return;
      var first = items[0];
      var last = items[items.length - 1];

      if (event.shiftKey && (document.activeElement === first || !panel.contains(document.activeElement))) {
        event.preventDefault();
        last.focus();
      } else if (!event.shiftKey && document.activeElement === last) {
        event.preventDefault();
        first.focus();
      }
    }
  });

  // назва/ціна для рядка товару, якщо в тригера нема data-quick-name/-price: картка або сторінка товару
  function nearbyName(trigger) {
    var card = trigger.closest('.product__item');
    var node = card ? card.querySelector('.product__item-title h3, .product__item-title') : document.querySelector('.prod__top-title, h1');
    return node ? node.textContent.trim() : '';
  }

  function nearbyPrice(trigger) {
    var card = trigger.closest('.product__item');
    var node = card ? card.querySelector('.product__item-price') : document.querySelector('.prod__about-price');
    if (!node) return '';
    // стара ціна лежить у <span> — беремо лише поточну (текст поза span)
    var current = '';
    node.childNodes.forEach(function (n) { if (n.nodeType === 3) current += n.textContent; });
    return (current.trim() || node.textContent).replace(/\s+/g, ' ').trim();
  }

  // any element with data-quick-order="<product_id>"
  document.addEventListener('click', function (event) {
    var trigger = event.target.closest('[data-quick-order]');
    if (!trigger || trigger.disabled) return;

    event.preventDefault();

    var qty = parseInt(trigger.getAttribute('data-quick-quantity'), 10) || 0;

    if (!qty) {
      var formQty = trigger.form ? trigger.form.querySelector('[name="quantity"]') : null;
      if (!formQty) {
        var host = trigger.closest('form');
        formQty = host ? host.querySelector('[name="quantity"]') : null;
      }
      if (!formQty && trigger.closest('.prod')) formQty = document.querySelector('.prod .quantity-count');
      qty = formQty ? parseInt(formQty.value, 10) || 1 : 1;
    }

    open(trigger.getAttribute('data-quick-order'), qty, {
      name: trigger.getAttribute('data-quick-name') || nearbyName(trigger),
      price: trigger.getAttribute('data-quick-price') || nearbyPrice(trigger)
    });
  });

  window.shokeruQuickOrder = { open: open, close: close, isOpen: isOpen };
})();
