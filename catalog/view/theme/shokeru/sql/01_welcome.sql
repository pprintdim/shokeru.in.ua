-- shokeru: вітальний екран (відео-тизер поверх головної) — право на Дизайн → Вітальний екран і налаштування показу.
-- Ідемпотентно: право додається лише якщо його ще нема; налаштування створюються лише якщо їх нема
-- (не перетирають змінене в адмінці). Префікс oc_, MySQL 8.
UPDATE `oc_user_group` SET `permission` = JSON_ARRAY_APPEND(JSON_ARRAY_APPEND(`permission`, '$.access', 'design/welcome'), '$.modify', 'design/welcome')
WHERE `user_group_id` = 1 AND JSON_SEARCH(`permission`, 'one', 'design/welcome') IS NULL;

INSERT INTO `oc_setting` (`store_id`, `code`, `key`, `value`, `serialized`)
SELECT 0, 'welcome', 'welcome_status', '1', 0 FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `oc_setting` WHERE `store_id` = 0 AND `key` = 'welcome_status');
INSERT INTO `oc_setting` (`store_id`, `code`, `key`, `value`, `serialized`)
SELECT 0, 'welcome', 'welcome_shows', '4', 0 FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `oc_setting` WHERE `store_id` = 0 AND `key` = 'welcome_shows');
INSERT INTO `oc_setting` (`store_id`, `code`, `key`, `value`, `serialized`)
SELECT 0, 'welcome', 'welcome_chance', '20', 0 FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `oc_setting` WHERE `store_id` = 0 AND `key` = 'welcome_chance');
INSERT INTO `oc_setting` (`store_id`, `code`, `key`, `value`, `serialized`)
SELECT 0, 'welcome', 'welcome_interval', '6', 0 FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `oc_setting` WHERE `store_id` = 0 AND `key` = 'welcome_interval');
-- файли: порожньо = hero-ролик слайдера (image/catalog/hero/hero-video.mp4, hero-video-mobile.mp4) і його постер
INSERT INTO `oc_setting` (`store_id`, `code`, `key`, `value`, `serialized`)
SELECT 0, 'welcome', 'welcome_video', '', 0 FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `oc_setting` WHERE `store_id` = 0 AND `key` = 'welcome_video');
INSERT INTO `oc_setting` (`store_id`, `code`, `key`, `value`, `serialized`)
SELECT 0, 'welcome', 'welcome_video_mobile', '', 0 FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `oc_setting` WHERE `store_id` = 0 AND `key` = 'welcome_video_mobile');
INSERT INTO `oc_setting` (`store_id`, `code`, `key`, `value`, `serialized`)
SELECT 0, 'welcome', 'welcome_poster', '', 0 FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `oc_setting` WHERE `store_id` = 0 AND `key` = 'welcome_poster');
INSERT INTO `oc_setting` (`store_id`, `code`, `key`, `value`, `serialized`)
SELECT 0, 'welcome', 'welcome_logo', '', 0 FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `oc_setting` WHERE `store_id` = 0 AND `key` = 'welcome_logo');

SELECT `key`, `value` FROM `oc_setting` WHERE `code` = 'welcome' ORDER BY `key`;
