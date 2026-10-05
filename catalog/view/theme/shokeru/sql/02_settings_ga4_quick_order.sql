-- shokeru: нові ключі Налаштувань (Сервер → GA4 Measurement ID, Пошта → email для «Купити в один клік»).
-- Порожній GA4 ID = тег не виводиться; порожній email = листи йдуть на config_email.
-- Ідемпотентно: рядок створюється лише якщо його ще нема.
INSERT INTO `oc_setting` (`store_id`, `code`, `key`, `value`, `serialized`)
SELECT 0, 'config', 'config_ga4_id', '', 0 FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `oc_setting` WHERE `store_id` = 0 AND `key` = 'config_ga4_id');
INSERT INTO `oc_setting` (`store_id`, `code`, `key`, `value`, `serialized`)
SELECT 0, 'config', 'config_quick_order_email', '', 0 FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `oc_setting` WHERE `store_id` = 0 AND `key` = 'config_quick_order_email');
SELECT `key`, `value` FROM `oc_setting` WHERE `key` IN ('config_ga4_id', 'config_quick_order_email');
