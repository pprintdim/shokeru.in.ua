-- shokeru: ЧПУ кошика /koshyk (uk), /korzina (ru), /cart (en). Ідемпотентно (не дублює query+мову і не бере зайнятий keyword).
INSERT INTO `oc_seo_url` (`store_id`, `language_id`, `query`, `keyword`)
SELECT 0, 2, 'checkout/cart', 'koshyk' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `oc_seo_url` WHERE `query` = 'checkout/cart' AND `language_id` = 2 AND `store_id` = 0)
  AND NOT EXISTS (SELECT 1 FROM `oc_seo_url` WHERE `keyword` = 'koshyk' AND `store_id` = 0);
INSERT INTO `oc_seo_url` (`store_id`, `language_id`, `query`, `keyword`)
SELECT 0, 3, 'checkout/cart', 'korzina' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `oc_seo_url` WHERE `query` = 'checkout/cart' AND `language_id` = 3 AND `store_id` = 0)
  AND NOT EXISTS (SELECT 1 FROM `oc_seo_url` WHERE `keyword` = 'korzina' AND `store_id` = 0);
INSERT INTO `oc_seo_url` (`store_id`, `language_id`, `query`, `keyword`)
SELECT 0, 1, 'checkout/cart', 'cart' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `oc_seo_url` WHERE `query` = 'checkout/cart' AND `language_id` = 1 AND `store_id` = 0)
  AND NOT EXISTS (SELECT 1 FROM `oc_seo_url` WHERE `keyword` = 'cart' AND `store_id` = 0);
SELECT `language_id`, `query`, `keyword` FROM `oc_seo_url` WHERE `query` = 'checkout/cart';
