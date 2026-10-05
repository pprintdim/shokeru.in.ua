-- shokeru: слаги службових сторінок. Ідемпотентно: рядок додається лише якщо для маршруту й мови ще нема слага
-- і сам слаг у цій мові вільний. Розбір URL (startup/seo_url) віддаємо другому агентові — тут тільки дані.
-- language_id беремо за кодом мови, не за числом. Префікс oc_, MySQL 8.
INSERT INTO `oc_seo_url` (`store_id`, `language_id`, `query`, `keyword`)
SELECT 0, l.`language_id`, 'information/sitemap', 'karta-saitu' FROM `oc_language` l WHERE l.`code` = 'uk-ua'
  AND NOT EXISTS (SELECT 1 FROM `oc_seo_url` s WHERE s.`store_id` = 0 AND s.`language_id` = l.`language_id` AND s.`query` = 'information/sitemap')
  AND NOT EXISTS (SELECT 1 FROM `oc_seo_url` s WHERE s.`store_id` = 0 AND s.`language_id` = l.`language_id` AND s.`keyword` = 'karta-saitu');
INSERT INTO `oc_seo_url` (`store_id`, `language_id`, `query`, `keyword`)
SELECT 0, l.`language_id`, 'information/sitemap', 'karta-saita' FROM `oc_language` l WHERE l.`code` = 'ru-ru'
  AND NOT EXISTS (SELECT 1 FROM `oc_seo_url` s WHERE s.`store_id` = 0 AND s.`language_id` = l.`language_id` AND s.`query` = 'information/sitemap')
  AND NOT EXISTS (SELECT 1 FROM `oc_seo_url` s WHERE s.`store_id` = 0 AND s.`language_id` = l.`language_id` AND s.`keyword` = 'karta-saita');
INSERT INTO `oc_seo_url` (`store_id`, `language_id`, `query`, `keyword`)
SELECT 0, l.`language_id`, 'information/sitemap', 'sitemap' FROM `oc_language` l WHERE l.`code` = 'en-gb'
  AND NOT EXISTS (SELECT 1 FROM `oc_seo_url` s WHERE s.`store_id` = 0 AND s.`language_id` = l.`language_id` AND s.`query` = 'information/sitemap')
  AND NOT EXISTS (SELECT 1 FROM `oc_seo_url` s WHERE s.`store_id` = 0 AND s.`language_id` = l.`language_id` AND s.`keyword` = 'sitemap');
SELECT `language_id`, `query`, `keyword` FROM `oc_seo_url` WHERE `query` = 'information/sitemap';
