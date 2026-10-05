-- shokeru: головна — секції в потрібному порядку (content_top): slideshow, hits, benefits, about, faq, reviews, feedback, video, safemode.
-- Ідемпотентно. Не видаляє існуючих рядків макета: наявні модулі лише перевпорядковуються (якщо вони вже в content_top),
-- нові створюються тільки для faq, reviews, benefits, feedback і лише якщо їх ще нема в макеті.
-- Префікс oc_, MySQL 8. Перед виконанням — зняти дамп oc_layout_module і oc_module (backups/).

-- 1) макет «Головна» (route common/home; запасний варіант — за назвою)
SET @lid := (SELECT `layout_id` FROM `oc_layout_route` WHERE `route` = 'common/home' AND `store_id` = 0 ORDER BY `layout_id` LIMIT 1);
SET @lid := COALESCE(@lid, (SELECT `layout_id` FROM `oc_layout` WHERE `name` = 'Home' ORDER BY `layout_id` LIMIT 1));

-- 2) модуль «Відгуки покупців»: розширення, права адміністратора, екземпляр
INSERT INTO `oc_extension` (`type`, `code`) SELECT 'module', 'reviews' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `oc_extension` WHERE `type` = 'module' AND `code` = 'reviews');
UPDATE `oc_user_group` SET `permission` = JSON_ARRAY_APPEND(JSON_ARRAY_APPEND(`permission`, '$.access', 'extension/module/reviews'), '$.modify', 'extension/module/reviews')
WHERE `user_group_id` = 1 AND JSON_SEARCH(`permission`, 'one', 'extension/module/reviews') IS NULL;
INSERT INTO `oc_setting` (`store_id`, `code`, `key`, `value`, `serialized`)
SELECT 0, 'module_reviews', 'module_reviews_status', '1', 0 FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `oc_setting` WHERE `store_id` = 0 AND `key` = 'module_reviews_status');
INSERT INTO `oc_module` (`name`, `code`, `setting`) SELECT 'Відгуки покупців', 'reviews',
  '{"name":"Відгуки покупців","status":1,"limit":6,"heading":{"1":"Customer reviews","2":"Відгуки покупців","3":"Отзывы покупателей"}}' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `oc_module` WHERE `code` = 'reviews');

-- 3) FAQ: 6 питань, 3 мови (language_id: 1 en-gb, 2 uk-ua, 3 ru-ru) — лише якщо екземпляра faq ще нема
INSERT INTO `oc_module` (`name`, `code`, `setting`) SELECT 'FAQ', 'faq', '{"name": "FAQ", "status": 1, "heading": {"1": "Frequently asked ", "2": "Поширені ", "3": "Частые "}, "heading_accent": {"1": "questions", "2": "запитання", "3": "вопросы"}, "items": [{"question": {"1": "Do I need a permit to buy a stun gun?", "2": "Чи потрібен дозвіл, щоб купити електрошокер?", "3": "Нужно ли разрешение, чтобы купить электрошокер?"}, "content": {"1": "&lt;p&gt;Civilian stun guns are self-defence devices and are sold freely, no separate permit is required. Read more in our article about the legality of stun guns in the Blog section.&lt;/p&gt;", "2": "&lt;p&gt;Цивільні електрошокери належать до засобів самозахисту й продаються вільно, окремого дозволу не потрібно. Докладніше — у нашій статті про законність електрошокерів у розділі «Блог».&lt;/p&gt;", "3": "&lt;p&gt;Гражданские электрошокеры относятся к средствам самозащиты и продаются свободно, отдельного разрешения не нужно. Подробнее — в нашей статье о законности электрошокеров в разделе «Блог».&lt;/p&gt;"}, "sort_order": "0", "status": "1"}, {"question": {"1": "How do I choose a stun gun?", "2": "Як вибрати електрошокер?", "3": "Как выбрать электрошокер?"}, "content": {"1": "&lt;p&gt;Look at three things: discharge voltage (million volts), stun time and form factor - classic, flashlight or phone. Use the catalog filters or call us and we will help you choose.&lt;/p&gt;", "2": "&lt;p&gt;Орієнтуйтеся на три параметри: напруга розряду (у млн вольт), час ураження та форм-фактор — класичний, ліхтарик чи телефон. Скористайтеся фільтрами в каталозі або зателефонуйте нам — підкажемо під ваше завдання.&lt;/p&gt;", "3": "&lt;p&gt;Ориентируйтесь на три параметра: напряжение разряда (в млн вольт), время поражения и форм-фактор — классический, фонарик или телефон. Воспользуйтесь фильтрами в каталоге или позвоните нам — подскажем под вашу задачу.&lt;/p&gt;"}, "sort_order": "1", "status": "1"}, {"question": {"1": "How does delivery work?", "2": "Як відбувається доставка?", "3": "Как происходит доставка?"}, "content": {"1": "&lt;p&gt;We ship across Ukraine by courier services on the day the order is confirmed. Cost and delivery time depend on the carrier you pick at checkout.&lt;/p&gt;", "2": "&lt;p&gt;Відправляємо по всій Україні службами доставки в день підтвердження замовлення. Вартість і терміни залежать від вибраного перевізника — їх видно під час оформлення замовлення.&lt;/p&gt;", "3": "&lt;p&gt;Отправляем по всей Украине службами доставки в день подтверждения заказа. Стоимость и сроки зависят от выбранного перевозчика — они видны при оформлении заказа.&lt;/p&gt;"}, "sort_order": "2", "status": "1"}, {"question": {"1": "Which payment methods are available?", "2": "Які способи оплати доступні?", "3": "Какие способы оплаты доступны?"}, "content": {"1": "&lt;p&gt;You can pay on delivery or online by card. Available methods are shown at checkout; see the Payment and delivery page for details.&lt;/p&gt;", "2": "&lt;p&gt;Оплатити можна під час отримання або онлайн карткою. Доступні способи показані на кроці оформлення замовлення; докладніше — на сторінці «Оплата і доставка».&lt;/p&gt;", "3": "&lt;p&gt;Оплатить можно при получении или онлайн картой. Доступные способы показаны на шаге оформления заказа; подробнее — на странице «Оплата и доставка».&lt;/p&gt;"}, "sort_order": "3", "status": "1"}, {"question": {"1": "Is there a warranty and can I return an item?", "2": "Чи є гарантія і можна повернути товар?", "3": "Есть ли гарантия и можно ли вернуть товар?"}, "content": {"1": "&lt;p&gt;Yes, products carry the manufacturer warranty, and returns and exchanges follow the law. The terms are on the Returns page; call us if you have any questions.&lt;/p&gt;", "2": "&lt;p&gt;Так, на товари діє гарантія виробника, а повернення й обмін — відповідно до законодавства. Умови описані на сторінці «Повернення»; якщо виникло питання — зателефонуйте, допоможемо.&lt;/p&gt;", "3": "&lt;p&gt;Да, на товары действует гарантия производителя, а возврат и обмен — в соответствии с законодательством. Условия описаны на странице «Возврат»; если возник вопрос — позвоните, поможем.&lt;/p&gt;"}, "sort_order": "4", "status": "1"}, {"question": {"1": "How do I charge and use it safely?", "2": "Як заряджати і безпечно користуватися?", "3": "Как заряжать и безопасно пользоваться?"}, "content": {"1": "&lt;p&gt;The charger is included and a full cycle usually takes a few hours. A manual is included: do not touch the contacts during discharge and keep the device away from children.&lt;/p&gt;", "2": "&lt;p&gt;Зарядка йде з комплекту, повний цикл зазвичай займає кілька годин. Інструкція входить у комплект: не торкайтеся контактів під час розряду й зберігайте пристрій поза зоною доступу дітей.&lt;/p&gt;", "3": "&lt;p&gt;Зарядка идёт из комплекта, полный цикл обычно занимает несколько часов. Инструкция входит в комплект: не касайтесь контактов во время разряда и храните устройство вне доступа детей.&lt;/p&gt;"}, "sort_order": "5", "status": "1"}]}' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `oc_module` WHERE `code` = 'faq');

-- 4) форма консультації: якщо екземпляра feedback нема — простий із заголовком (контролер читає name[language_id])
INSERT INTO `oc_module` (`name`, `code`, `setting`) SELECT 'Консультація', 'feedback',
  '{"name":{"1":"Need a consultation?","2":"Потрібна консультація?","3":"Нужна консультация?"},"status":1}' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `oc_module` WHERE `code` = 'feedback');

-- 5) порядок секцій
DROP TEMPORARY TABLE IF EXISTS `tmp_home_order`;
CREATE TEMPORARY TABLE `tmp_home_order` (`mcode` VARCHAR(40) NOT NULL, `alt` VARCHAR(40) NULL, `sort_order` INT NOT NULL, `do_insert` TINYINT NOT NULL DEFAULT 0) DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
INSERT INTO `tmp_home_order` (`mcode`, `alt`, `sort_order`, `do_insert`) VALUES
  ('slideshow', NULL, 10, 0),
  ('featured', 'bestseller', 20, 0),
  ('benefits', NULL, 30, 1),
  ('aboutus', 'html', 40, 0),
  ('faq', NULL, 50, 1),
  ('reviews', NULL, 60, 1),
  ('feedback', NULL, 70, 1),
  ('one_video', NULL, 80, 0),
  ('html_repeater', NULL, 90, 0);

-- 5a) наявні рядки в content_top — лише змінюємо sort_order
UPDATE `oc_layout_module` lm JOIN `tmp_home_order` t
  ON (lm.`code` = CONCAT(t.`mcode`) OR lm.`code` LIKE CONCAT(t.`mcode`, '.%')
      OR (t.`alt` IS NOT NULL AND (lm.`code` = CONCAT(t.`alt`) OR lm.`code` LIKE CONCAT(t.`alt`, '.%'))))
SET lm.`sort_order` = t.`sort_order`
WHERE lm.`layout_id` = @lid AND lm.`position` = 'content_top';

-- 5b) нових рядків додаємо лише для do_insert=1 і лише якщо цього модуля ще нема в макеті на жодній позиції
INSERT INTO `oc_layout_module` (`layout_id`, `code`, `position`, `sort_order`)
SELECT @lid, CONCAT(m.`code`, '.', m.`module_id`), 'content_top', t.`sort_order`
FROM `tmp_home_order` t
JOIN (SELECT `code`, MIN(`module_id`) AS `module_id` FROM `oc_module` GROUP BY `code`) m ON m.`code` = t.`mcode`
WHERE t.`do_insert` = 1 AND @lid IS NOT NULL AND NOT EXISTS (
  SELECT 1 FROM `oc_layout_module` x WHERE x.`layout_id` = @lid
    AND (x.`code` = CONCAT(t.`mcode`) OR x.`code` LIKE CONCAT(t.`mcode`, '.%')));

DROP TEMPORARY TABLE IF EXISTS `tmp_home_order`;

-- перевірка
SELECT lm.`layout_id`, lm.`code`, lm.`position`, lm.`sort_order` FROM `oc_layout_module` lm WHERE lm.`layout_id` = @lid ORDER BY lm.`position`, lm.`sort_order`;
