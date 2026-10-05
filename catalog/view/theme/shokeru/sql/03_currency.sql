-- shokeru: мультивалютність — UAH базова (value 1), USD/EUR за курсом (коригувати в Система → Локалізація → Валюти).
-- Перемикач валют у шапці з'являється, коли активних валют більше однієї. Ідемпотентно.
UPDATE `oc_currency` SET `value` = 1.00000000, `status` = 1 WHERE `code` = 'UAH';
INSERT INTO `oc_currency` (`title`, `code`, `symbol_left`, `symbol_right`, `decimal_place`, `value`, `status`, `date_modified`)
SELECT 'US Dollar', 'USD', '$', '', '2', 0.02410000, 1, NOW() FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `oc_currency` WHERE `code` = 'USD');
INSERT INTO `oc_currency` (`title`, `code`, `symbol_left`, `symbol_right`, `decimal_place`, `value`, `status`, `date_modified`)
SELECT 'Euro', 'EUR', '€', '', '2', 0.02060000, 1, NOW() FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `oc_currency` WHERE `code` = 'EUR');
-- стокові рядки OC мали курси до USD-бази — перевести на UAH-базу і ввімкнути
UPDATE `oc_currency` SET `status` = 1, `value` = 0.02410000 WHERE `code` = 'USD' AND `status` = 0;
UPDATE `oc_currency` SET `status` = 1, `value` = 0.02060000 WHERE `code` = 'EUR' AND `status` = 0;
UPDATE `oc_setting` SET `value` = 'UAH' WHERE `key` = 'config_currency';
UPDATE `oc_setting` SET `value` = '0' WHERE `key` = 'config_currency_auto';
SELECT `code`, `symbol_left`, `symbol_right`, `value`, `status` FROM `oc_currency`;
