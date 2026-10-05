/* Телефонні поля з маскою і вибором країни — одна поведінка на весь магазин.
 *
 * Розмітка: будь-який <input data-phone-input> (чекаут, кабінет, повернення,
 * попап входу, «купити в один клік», зворотний звʼязок). Бібліотека —
 * intl-tel-input із теми (vendor/intlTelInput.min.js), типова країна — Україна,
 * код країни окремим селектом, у полі лишаються тільки цифри номера.
 *
 * Перед відправкою у value кладеться повний міжнародний номер (+380…): у
 * capture-фазі submit — раніше за обробники форм (чекаут, auth, quick order),
 * і на blur — для кнопок, які читають value без submit. На бекенді номер
 * нормалізується ще раз (startup/phone.php).
 */
(function () {
  // шлях до теми беремо з тега цього ж скрипта — тема може лежати за будь-яким префіксом
  function themeBase() {
    var tag = document.querySelector('script[src*="/js/phone.js"]');

    var base = tag ? tag.getAttribute('src').replace(/js\/phone\.js.*$/, '') : 'catalog/view/theme/shokeru/';

    // абсолютна адреса: бібліотека тягне utils через import(), а той рахує відносний шлях
    // від власного файла (vendor/intlTelInput.min.js), а не від сторінки — звідси був 404
    return new URL(base, document.baseURI).href;
  }

  var options = {
    initialCountry: 'ua',
    separateDialCode: true,
    strictMode: true,
    autoPlaceholder: 'aggressive',
    // маска за форматом країни прямо під час набору (з utils)
    formatAsYouType: true,
    placeholderNumberType: 'MOBILE',
    // на телефоні — та сама компактна випадайка, без повноекранного списку
    useFullscreenPopup: false,
    countryOrder: ['ua', 'pl', 'de', 'cz', 'us', 'gb'],
    // utils лежить у темі (без CDN): дає довжини номерів для strictMode і плейсхолдери
    utilsScript: themeBase() + 'vendor/intlTelInputUtils.js'
  };

  var instances = new WeakMap();

  function sync(input) {
    var iti = instances.get(input);

    if (!iti || !iti.getNumber) return;

    var full = iti.getNumber();

    if (full) input.value = full;
  }

  // Після зміни країни номер переформатовується під її маску, а зайві цифри
  // відкидаються з кінця: strictMode обмежує лише набір, а не вже введене.
  function fit(input) {
    var iti = instances.get(input);
    var utils = window.intlTelInput && window.intlTelInput.utils;

    if (!iti || !utils || !input.value) return;

    var country = iti.getSelectedCountryData();
    var dial = country && country.dialCode ? country.dialCode : '';
    var digits = input.value.replace(/\D/g, '');

    if (!dial) return;

    // номер із кодом країни (вставили «+380…» або «380…») — код відкидаємо
    if (input.value.trim().charAt(0) === '+' && digits.indexOf(dial) === 0) digits = digits.slice(dial.length);

    // національний префікс «0» (набрали 050…) іншій країні не потрібен — маска його не має
    var sample = (input.getAttribute('placeholder') || '').replace(/\D/g, '');
    if (sample && sample.charAt(0) !== '0') digits = digits.replace(/^0+/, '');

    var tooLong = utils.validationError ? utils.validationError.TOO_LONG : 3;

    while (digits.length && utils.getValidationError('+' + dial + digits, country.iso2) === tooLong) {
      digits = digits.slice(0, -1);
    }

    input.value = utils.formatNumberAsYouType ? utils.formatNumberAsYouType('+' + dial + digits, country.iso2).replace(new RegExp('^\\+' + dial + '\\s*'), '') : digits;
  }

  function init(scope) {
    if (!window.intlTelInput) return;

    (scope || document).querySelectorAll('[data-phone-input]').forEach(function (input) {
      if (instances.has(input)) return;

      // плейсхолдер ставить бібліотека — приклад номера для обраної країни
      input.removeAttribute('placeholder');

      var iti = window.intlTelInput(input, options);

      instances.set(input, iti);
      input.addEventListener('blur', function () { sync(input); });
      // «050…» → «50 …»: у масці з окремим кодом країни нуль зайвий, і з ним не форматується
      input.addEventListener('input', function () { if (/^\s*0/.test(input.value)) fit(input); });
      input.addEventListener('countrychange', function () { setTimeout(function () { fit(input); }, 0); });
      if (iti.promise) iti.promise.then(function () { fit(input); });
    });
  }

  document.addEventListener('DOMContentLoaded', function () {
    init(document);

    // значення нормалізується до того, як його прочитають обробники форм
    document.addEventListener('submit', function (event) {
      if (!event.target || !event.target.querySelectorAll) return;

      event.target.querySelectorAll('[data-phone-input]').forEach(sync);
    }, true);

    // попапи та секції, що приїжджають ajax-ом (чекаут, кошик, кабінет)
    var observer = new MutationObserver(function (records) {
      records.forEach(function (record) {
        record.addedNodes.forEach(function (node) {
          if (node.nodeType !== 1) return;

          if (node.matches && node.matches('[data-phone-input]')) init(node.parentNode || document);
          else if (node.querySelector && node.querySelector('[data-phone-input]')) init(node);
        });
      });
    });

    observer.observe(document.body, { childList: true, subtree: true });
  });

  // для інших скриптів: дістати готовий номер перед ajax-відправкою
  window.shokeruPhone = {
    init: init,
    value: function (input) {
      sync(input);

      return input ? input.value : '';
    }
  };
})();
