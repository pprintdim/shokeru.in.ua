-- shokeru: прапорці інформаційних сторінок «Показувати в шапці» (top) і «Показувати в підфутері» (subfooter).
-- Колонки додаються лише якщо їх ще нема (MySQL 8 без IF NOT EXISTS для ADD COLUMN → через information_schema).
-- Адмін-модель (shk_panel/model/catalog/information.php) працює і без них, але прапорці збережуться лише після цього скрипта.
SET @has_top := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'oc_information' AND COLUMN_NAME = 'top');
SET @sql := IF(@has_top = 0, 'ALTER TABLE `oc_information` ADD COLUMN `top` TINYINT(1) NOT NULL DEFAULT 0 AFTER `bottom`', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @has_sub := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'oc_information' AND COLUMN_NAME = 'subfooter');
SET @sql := IF(@has_sub = 0, 'ALTER TABLE `oc_information` ADD COLUMN `subfooter` TINYINT(1) NOT NULL DEFAULT 0 AFTER `bottom`', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SELECT information_id, bottom, top, subfooter, status FROM `oc_information`;
