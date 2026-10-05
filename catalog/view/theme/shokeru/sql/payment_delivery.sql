-- Shokeru: delivery + payment options identical to the other stores of the group (shoker.in.ua).
-- NOT executed automatically — run only after the user confirms (rule: any SQL needs explicit approval).
-- Prefix oc_, store_id 0, languages: 1 en-gb, 2 uk-ua, 3 ru-ru. Idempotent: re-running gives the same state.
-- WayForPay merchant / secret are NOT here — copy them DB->DB AFTER this file (it never touches those two keys).
-- Run order: 1) this file  2) copy payment_wayforpay_merchant + payment_wayforpay_secretkey from shoker
--            3) Admin -> Extensions -> Shipping -> «Нова Пошта» / «Meest»: «Sync» (fills oc_np_warehouse)
--               or copy oc_np_warehouse DB->DB from shoker.

SET NAMES utf8mb4;

-- 1. Tables used by the new code (both are also created lazily by the PHP code).
CREATE TABLE IF NOT EXISTS `oc_np_warehouse` (
  `np_warehouse_id` int(11) NOT NULL AUTO_INCREMENT,
  `carrier` varchar(16) NOT NULL DEFAULT 'novaposhta',
  `city` varchar(128) NOT NULL DEFAULT '',
  `city_ru` varchar(128) NOT NULL DEFAULT '',
  `type` varchar(16) NOT NULL DEFAULT 'branch',
  `description` varchar(255) NOT NULL DEFAULT '',
  `description_ru` varchar(255) NOT NULL DEFAULT '',
  `number` varchar(16) NOT NULL DEFAULT '',
  `ref` varchar(64) NOT NULL DEFAULT '',
  PRIMARY KEY (`np_warehouse_id`),
  KEY `carrier_city` (`carrier`, `city`(64), `type`),
  KEY `city_ru` (`city_ru`(64))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `oc_order_payment_status` (
  `order_id` int(11) NOT NULL,
  `payment_code` varchar(64) NOT NULL DEFAULT '',
  `paid` tinyint(1) NOT NULL DEFAULT '0',
  `provider` varchar(64) NOT NULL DEFAULT '',
  `provider_status` varchar(128) NOT NULL DEFAULT '',
  `provider_response` mediumtext,
  `date_paid` datetime DEFAULT NULL,
  `date_modified` datetime NOT NULL,
  PRIMARY KEY (`order_id`),
  KEY `paid` (`paid`),
  KEY `payment_code` (`payment_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Installed extensions. Shipping: `delivery` is the engine; courier / meest / novaposhta / pickup /
--    ukrposhta are the per-carrier cards (their models return no quote, they only edit delivery settings
--    and sync the warehouse directory). `novaposhta` and `pickup` keep their codes — the files were replaced.
INSERT INTO `oc_extension` (`type`, `code`) SELECT 'shipping', 'delivery' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `oc_extension` WHERE `type` = 'shipping' AND `code` = 'delivery');
INSERT INTO `oc_extension` (`type`, `code`) SELECT 'shipping', 'courier' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `oc_extension` WHERE `type` = 'shipping' AND `code` = 'courier');
INSERT INTO `oc_extension` (`type`, `code`) SELECT 'shipping', 'meest' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `oc_extension` WHERE `type` = 'shipping' AND `code` = 'meest');
INSERT INTO `oc_extension` (`type`, `code`) SELECT 'shipping', 'novaposhta' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `oc_extension` WHERE `type` = 'shipping' AND `code` = 'novaposhta');
INSERT INTO `oc_extension` (`type`, `code`) SELECT 'shipping', 'pickup' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `oc_extension` WHERE `type` = 'shipping' AND `code` = 'pickup');
INSERT INTO `oc_extension` (`type`, `code`) SELECT 'shipping', 'ukrposhta' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `oc_extension` WHERE `type` = 'shipping' AND `code` = 'ukrposhta');
INSERT INTO `oc_extension` (`type`, `code`) SELECT 'payment', 'wayforpay' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `oc_extension` WHERE `type` = 'payment' AND `code` = 'wayforpay');
INSERT INTO `oc_extension` (`type`, `code`) SELECT 'payment', 'cod' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `oc_extension` WHERE `type` = 'payment' AND `code` = 'cod');
INSERT INTO `oc_extension` (`type`, `code`) SELECT 'payment', 'bank_transfer' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `oc_extension` WHERE `type` = 'payment' AND `code` = 'bank_transfer');
INSERT INTO `oc_extension` (`type`, `code`) SELECT 'payment', 'free_checkout' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `oc_extension` WHERE `type` = 'payment' AND `code` = 'free_checkout');

-- 3. Delivery engine: all 5 carriers on; courier 150 UAH, the rest 0 (= carrier tariff / free pickup).
DELETE FROM `oc_setting` WHERE `store_id` = 0 AND `code` = 'shipping_delivery';
INSERT INTO `oc_setting` (`store_id`, `code`, `key`, `value`, `serialized`) VALUES
(0, 'shipping_delivery', 'shipping_delivery_status', '1', 0),
(0, 'shipping_delivery', 'shipping_delivery_sort_order', '1', 0),
(0, 'shipping_delivery', 'shipping_delivery_tax_class_id', '0', 0),
(0, 'shipping_delivery', 'shipping_delivery_geo_zone_id', '0', 0),
(0, 'shipping_delivery', 'shipping_delivery_novaposhta_cost', '0', 0),
(0, 'shipping_delivery', 'shipping_delivery_ukrposhta_cost', '0', 0),
(0, 'shipping_delivery', 'shipping_delivery_meest_cost', '0', 0),
(0, 'shipping_delivery', 'shipping_delivery_courier_cost', '150', 0),
(0, 'shipping_delivery', 'shipping_delivery_pickup_cost', '0', 0),
(0, 'shipping_delivery', 'shipping_delivery_novaposhta_enabled', '1', 0),
(0, 'shipping_delivery', 'shipping_delivery_ukrposhta_enabled', '1', 0),
(0, 'shipping_delivery', 'shipping_delivery_meest_enabled', '1', 0),
(0, 'shipping_delivery', 'shipping_delivery_courier_enabled', '1', 0),
(0, 'shipping_delivery', 'shipping_delivery_pickup_enabled', '1', 0);

-- 4. Stock / old shipping methods off (rows only updated when present; the old Nova Poshta extension
--    `shipping_novaposhta_*` settings — incl. its API key — are no longer read by any code).
UPDATE `oc_setting` SET `value` = '0' WHERE `store_id` = 0 AND `code` = 'shipping_flat' AND `key` = 'shipping_flat_status';
UPDATE `oc_setting` SET `value` = '0' WHERE `store_id` = 0 AND `code` = 'shipping_free' AND `key` = 'shipping_free_status';
UPDATE `oc_setting` SET `value` = '0' WHERE `store_id` = 0 AND `code` = 'shipping_pickup' AND `key` = 'shipping_pickup_status';
UPDATE `oc_setting` SET `value` = '0' WHERE `store_id` = 0 AND `code` = 'shipping_novaposhta' AND `key` = 'shipping_novaposhta_status';

-- 5. Guest checkout on (the single-page checkout posts checkout/guest/save for visitors).
DELETE FROM `oc_setting` WHERE `store_id` = 0 AND `code` = 'config' AND `key` = 'config_checkout_guest';
INSERT INTO `oc_setting` (`store_id`, `code`, `key`, `value`, `serialized`) VALUES (0, 'config', 'config_checkout_guest', '1', 0);

-- 6. WayForPay (card): sort 1, paid -> 2 Processing, cancel -> 7 Canceled, decline -> 8 Denied, no pending
--    status (the order stays unconfirmed until the gateway answers). merchant / secretkey are left untouched.
DELETE FROM `oc_setting` WHERE `store_id` = 0 AND `code` = 'payment_wayforpay'
  AND `key` NOT IN ('payment_wayforpay_merchant', 'payment_wayforpay_secretkey');
INSERT INTO `oc_setting` (`store_id`, `code`, `key`, `value`, `serialized`) VALUES
(0, 'payment_wayforpay', 'payment_wayforpay_status', '1', 0),
(0, 'payment_wayforpay', 'payment_wayforpay_sort_order', '1', 0),
(0, 'payment_wayforpay', 'payment_wayforpay_order_status_id', '2', 0),
(0, 'payment_wayforpay', 'payment_wayforpay_pending_status_id', '0', 0),
(0, 'payment_wayforpay', 'payment_wayforpay_cancel_status_id', '7', 0),
(0, 'payment_wayforpay', 'payment_wayforpay_decline_status_id', '8', 0),
(0, 'payment_wayforpay', 'payment_wayforpay_geo_zone_id', '0', 0),
(0, 'payment_wayforpay', 'payment_wayforpay_total', '', 0),
(0, 'payment_wayforpay', 'payment_wayforpay_title1', 'Card payment online', 0),
(0, 'payment_wayforpay', 'payment_wayforpay_title2', 'Оплата карткою онлайн', 0),
(0, 'payment_wayforpay', 'payment_wayforpay_title3', 'Оплата картой онлайн', 0);
-- placeholders so the keys exist; INSERT only when missing, so copied values are never overwritten
INSERT INTO `oc_setting` (`store_id`, `code`, `key`, `value`, `serialized`) SELECT 0, 'payment_wayforpay', 'payment_wayforpay_merchant', '', 0 FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `oc_setting` WHERE `store_id` = 0 AND `code` = 'payment_wayforpay' AND `key` = 'payment_wayforpay_merchant');
INSERT INTO `oc_setting` (`store_id`, `code`, `key`, `value`, `serialized`) SELECT 0, 'payment_wayforpay', 'payment_wayforpay_secretkey', '', 0 FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `oc_setting` WHERE `store_id` = 0 AND `code` = 'payment_wayforpay' AND `key` = 'payment_wayforpay_secretkey');

-- 7. Cash on delivery: sort 2, order status 2 Processing, no minimum total.
DELETE FROM `oc_setting` WHERE `store_id` = 0 AND `code` = 'payment_cod';
INSERT INTO `oc_setting` (`store_id`, `code`, `key`, `value`, `serialized`) VALUES
(0, 'payment_cod', 'payment_cod_status', '1', 0),
(0, 'payment_cod', 'payment_cod_sort_order', '2', 0),
(0, 'payment_cod', 'payment_cod_order_status_id', '2', 0),
(0, 'payment_cod', 'payment_cod_geo_zone_id', '0', 0),
(0, 'payment_cod', 'payment_cod_total', '0', 0);

-- 8. Bank transfer: sort 3, requisites text per language (the manager sends the details).
DELETE FROM `oc_setting` WHERE `store_id` = 0 AND `code` = 'payment_bank_transfer';
INSERT INTO `oc_setting` (`store_id`, `code`, `key`, `value`, `serialized`) VALUES
(0, 'payment_bank_transfer', 'payment_bank_transfer_status', '1', 0),
(0, 'payment_bank_transfer', 'payment_bank_transfer_sort_order', '3', 0),
(0, 'payment_bank_transfer', 'payment_bank_transfer_order_status_id', '1', 0),
(0, 'payment_bank_transfer', 'payment_bank_transfer_geo_zone_id', '0', 0),
(0, 'payment_bank_transfer', 'payment_bank_transfer_total', '0', 0),
(0, 'payment_bank_transfer', 'payment_bank_transfer_bank1', 'Our manager will send the bank details after confirming the order.', 0),
(0, 'payment_bank_transfer', 'payment_bank_transfer_bank2', 'Реквізити для оплати на рахунок надішле менеджер після підтвердження замовлення.', 0),
(0, 'payment_bank_transfer', 'payment_bank_transfer_bank3', 'Реквизиты для оплаты на счёт пришлёт менеджер после подтверждения заказа.', 0);

-- 9. Free checkout (zero total only): sort 1.
DELETE FROM `oc_setting` WHERE `store_id` = 0 AND `code` = 'payment_free_checkout';
INSERT INTO `oc_setting` (`store_id`, `code`, `key`, `value`, `serialized`) VALUES
(0, 'payment_free_checkout', 'payment_free_checkout_status', '1', 0),
(0, 'payment_free_checkout', 'payment_free_checkout_sort_order', '1', 0),
(0, 'payment_free_checkout', 'payment_free_checkout_order_status_id', '1', 0);

-- 10. Admin permissions (access + modify) for the Administrator group; the JSON_SEARCH guard prevents duplicates
--     (route slashes are JSON-escaped in the column, so LIKE '%a/b%' would give a false negative).
UPDATE `oc_user_group` SET `permission` = JSON_ARRAY_APPEND(JSON_ARRAY_APPEND(`permission`, '$.access', 'extension/shipping/delivery'), '$.modify', 'extension/shipping/delivery') WHERE `user_group_id` = 1 AND JSON_SEARCH(`permission`, 'one', 'extension/shipping/delivery') IS NULL;
UPDATE `oc_user_group` SET `permission` = JSON_ARRAY_APPEND(JSON_ARRAY_APPEND(`permission`, '$.access', 'extension/shipping/courier'), '$.modify', 'extension/shipping/courier') WHERE `user_group_id` = 1 AND JSON_SEARCH(`permission`, 'one', 'extension/shipping/courier') IS NULL;
UPDATE `oc_user_group` SET `permission` = JSON_ARRAY_APPEND(JSON_ARRAY_APPEND(`permission`, '$.access', 'extension/shipping/meest'), '$.modify', 'extension/shipping/meest') WHERE `user_group_id` = 1 AND JSON_SEARCH(`permission`, 'one', 'extension/shipping/meest') IS NULL;
UPDATE `oc_user_group` SET `permission` = JSON_ARRAY_APPEND(JSON_ARRAY_APPEND(`permission`, '$.access', 'extension/shipping/novaposhta'), '$.modify', 'extension/shipping/novaposhta') WHERE `user_group_id` = 1 AND JSON_SEARCH(`permission`, 'one', 'extension/shipping/novaposhta') IS NULL;
UPDATE `oc_user_group` SET `permission` = JSON_ARRAY_APPEND(JSON_ARRAY_APPEND(`permission`, '$.access', 'extension/shipping/pickup'), '$.modify', 'extension/shipping/pickup') WHERE `user_group_id` = 1 AND JSON_SEARCH(`permission`, 'one', 'extension/shipping/pickup') IS NULL;
UPDATE `oc_user_group` SET `permission` = JSON_ARRAY_APPEND(JSON_ARRAY_APPEND(`permission`, '$.access', 'extension/shipping/ukrposhta'), '$.modify', 'extension/shipping/ukrposhta') WHERE `user_group_id` = 1 AND JSON_SEARCH(`permission`, 'one', 'extension/shipping/ukrposhta') IS NULL;
UPDATE `oc_user_group` SET `permission` = JSON_ARRAY_APPEND(JSON_ARRAY_APPEND(`permission`, '$.access', 'extension/payment/wayforpay'), '$.modify', 'extension/payment/wayforpay') WHERE `user_group_id` = 1 AND JSON_SEARCH(`permission`, 'one', 'extension/payment/wayforpay') IS NULL;
UPDATE `oc_user_group` SET `permission` = JSON_ARRAY_APPEND(JSON_ARRAY_APPEND(`permission`, '$.access', 'extension/payment/cod'), '$.modify', 'extension/payment/cod') WHERE `user_group_id` = 1 AND JSON_SEARCH(`permission`, 'one', 'extension/payment/cod') IS NULL;
UPDATE `oc_user_group` SET `permission` = JSON_ARRAY_APPEND(JSON_ARRAY_APPEND(`permission`, '$.access', 'extension/payment/bank_transfer'), '$.modify', 'extension/payment/bank_transfer') WHERE `user_group_id` = 1 AND JSON_SEARCH(`permission`, 'one', 'extension/payment/bank_transfer') IS NULL;
UPDATE `oc_user_group` SET `permission` = JSON_ARRAY_APPEND(JSON_ARRAY_APPEND(`permission`, '$.access', 'extension/payment/free_checkout'), '$.modify', 'extension/payment/free_checkout') WHERE `user_group_id` = 1 AND JSON_SEARCH(`permission`, 'one', 'extension/payment/free_checkout') IS NULL;

-- 11. Order status names: shoker has no SQL for them (statuses 1/2/7/8 are the stock ones, translated in
--     its DB). Compare oc_order_status (language_id 2/3) with shoker and copy DB->DB if they differ.
