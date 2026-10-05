<?php
// «Купить в один клик» — попап (common/quick_order.twig) и письмо менеджеру
// (common/quick_order/order). Кнопка на карточках и странице товара —
// button_quick_order в глобальном ru-ru.php.

// Popup
$_['text_eyebrow']        = 'Быстрый заказ';
$_['heading_title']       = 'Купить в один клик';
$_['text_lead']           = 'Оставьте имя и телефон — менеджер перезвонит, уточнит детали и оформит заказ за вас.';
$_['text_quantity']       = 'Количество: %s';
$_['text_close']          = 'Закрыть';
$_['text_note']           = 'Достаточно имени и телефона — остальное согласуем по звонку.';

// Fields
$_['entry_name']          = 'Имя';
$_['entry_phone']         = 'Телефон';
$_['placeholder_name']    = 'Как к вам обращаться';
$_['placeholder_phone']   = '+380';

// Buttons
$_['button_submit']       = 'Заказать';
$_['button_done']         = 'Продолжить покупки';

// Success screen
$_['text_done_title']     = 'Спасибо!';
$_['text_done']           = 'Заказ принят. Менеджер позвонит вам в ближайшее время, чтобы подтвердить детали.';
$_['text_success']        = 'Заявка отправлена — мы перезвоним.';

// Mail to the manager (route=common/quick_order/order)
$_['text_mail_subject']   = 'Купить в один клик: %s';
$_['text_mail_title']     = 'Новый быстрый заказ с сайта %s (купить в один клик).';
$_['text_mail_product']   = 'Товар:';
$_['text_mail_model']     = 'Артикул:';
$_['text_mail_price']     = 'Цена:';
$_['text_mail_quantity']  = 'Количество:';
$_['text_mail_total']     = 'Сумма:';
$_['text_mail_link']      = 'Ссылка на товар:';
$_['text_mail_customer']  = 'Имя:';
$_['text_mail_phone']     = 'Телефон:';
$_['text_mail_account']   = 'Аккаунт:';
$_['text_mail_guest']     = 'гость';
$_['text_mail_language']  = 'Язык интерфейса:';
$_['text_mail_page']      = 'Страница, с которой заказали:';
$_['text_mail_date']      = 'Дата:';
$_['text_mail_ip']        = 'IP:';

// Errors
$_['error_name']          = 'Укажите имя (от 2 до 64 символов).';
$_['error_phone']         = 'Укажите корректный номер телефона.';
$_['error_product']       = 'Товар не найден — обновите страницу и попробуйте ещё раз.';
$_['error_stock']         = 'К сожалению, товара сейчас нет в наличии.';
$_['error_too_often']     = 'Заявка уже отправлена. Подождите полминуты перед повторной отправкой.';
$_['error_send']          = 'Не удалось отправить заявку. Позвоните нам, пожалуйста.';
$_['error_network']       = 'Не удалось связаться с сервером. Попробуйте ещё раз.';
