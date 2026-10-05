<?php
$settings = require dirname(__DIR__) . '/app_config.php';

// Root
define('DIR_ROOT', $settings['root']);

// HTTP
define('HTTP_SERVER', $settings['http_catalog'] . $settings['admin_path'] . '/');
define('HTTP_CATALOG', $settings['http_catalog']);

// HTTPS
define('HTTPS_SERVER', $settings['https_catalog'] . $settings['admin_path'] . '/');
define('HTTPS_CATALOG', $settings['https_catalog']);

// DIR
define('DIR_APPLICATION', DIR_ROOT . $settings['admin_path'] . '/');
define('DIR_SYSTEM', DIR_ROOT . 'system/');
define('DIR_IMAGE', DIR_ROOT . 'image/');

define('DIR_STORAGE', $settings['storage']);

define('DIR_CATALOG', DIR_ROOT . 'catalog/');
define('DIR_LANGUAGE', DIR_APPLICATION . 'language/');
define('DIR_TEMPLATE', DIR_APPLICATION . 'view/template/');
define('DIR_CONFIG', DIR_SYSTEM . 'config/');
define('DIR_CACHE', DIR_STORAGE . 'cache/');
define('DIR_DOWNLOAD', DIR_STORAGE . 'download/');
define('DIR_LOGS', DIR_STORAGE . 'logs/');
define('DIR_MODIFICATION', DIR_STORAGE . 'modification/');
define('DIR_SESSION', DIR_STORAGE . 'session/');
define('DIR_UPLOAD', DIR_STORAGE . 'upload/');

// DB
define('DB_DRIVER', $settings['db']['driver']);
define('DB_HOSTNAME', $settings['db']['hostname']);
define('DB_USERNAME', $settings['db']['username']);
define('DB_PASSWORD', $settings['db']['password']);
define('DB_DATABASE', $settings['db']['database']);
define('DB_PORT', $settings['db']['port']);
define('DB_PREFIX', $settings['db']['prefix']);

// OpenCart API
define('OPENCART_SERVER', 'https://www.opencart.com/');
