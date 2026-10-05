<?php
// «Купити в один клік» — попап (common/quick_order.twig) і лист менеджеру
// (common/quick_order/order). Кнопка на картках і сторінці товару —
// button_quick_order у глобальному uk-ua.php.

// Popup
$_['text_eyebrow']        = 'Швидке замовлення';
$_['heading_title']       = 'Купити в один клік';
$_['text_lead']           = 'Залиште імʼя і телефон — менеджер передзвонить, уточнить деталі й оформить замовлення за вас.';
$_['text_quantity']       = 'Кількість: %s';
$_['text_close']          = 'Закрити';
$_['text_note']           = 'Достатньо імені та телефону — решту узгодимо в дзвінку.';

// Fields
$_['entry_name']          = 'Імʼя';
$_['entry_phone']         = 'Телефон';
$_['placeholder_name']    = 'Як до вас звертатися';
$_['placeholder_phone']   = '+380';

// Buttons
$_['button_submit']       = 'Замовити';
$_['button_done']         = 'Продовжити покупки';

// Success screen
$_['text_done_title']     = 'Дякуємо!';
$_['text_done']           = 'Замовлення прийнято. Менеджер зателефонує вам найближчим часом, щоб підтвердити деталі.';
$_['text_success']        = 'Заявку надіслано — ми передзвонимо.';

// Mail to the manager (route=common/quick_order/order)
$_['text_mail_subject']   = 'Купити в один клік: %s';
$_['text_mail_title']     = 'Нове швидке замовлення з сайту %s (купити в один клік).';
$_['text_mail_product']   = 'Товар:';
$_['text_mail_model']     = 'Артикул:';
$_['text_mail_price']     = 'Ціна:';
$_['text_mail_quantity']  = 'Кількість:';
$_['text_mail_total']     = 'Сума:';
$_['text_mail_link']      = 'Посилання на товар:';
$_['text_mail_customer']  = 'Імʼя:';
$_['text_mail_phone']     = 'Телефон:';
$_['text_mail_account']   = 'Акаунт:';
$_['text_mail_guest']     = 'гість';
$_['text_mail_language']  = 'Мова інтерфейсу:';
$_['text_mail_page']      = 'Сторінка, з якої замовили:';
$_['text_mail_date']      = 'Дата:';
$_['text_mail_ip']        = 'IP:';

// Errors
$_['error_name']          = 'Вкажіть імʼя (від 2 до 64 символів).';
$_['error_phone']         = 'Вкажіть коректний номер телефону.';
$_['error_product']       = 'Товар не знайдено — оновіть сторінку і спробуйте ще раз.';
$_['error_stock']         = 'На жаль, товару зараз немає в наявності.';
$_['error_too_often']     = 'Заявку вже надіслано. Зачекайте пів хвилини перед повторною відправкою.';
$_['error_send']          = 'Не вдалося надіслати заявку. Зателефонуйте нам, будь ласка.';
$_['error_network']       = 'Не вдалося звʼязатися з сервером. Спробуйте ще раз.';
