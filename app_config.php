<?php
/**
 * Shared project settings for local and hosting environments.
 *
 * For hosting-specific secrets, create app_config.local.php in the project root.
 * Values in app_config.local.php override the defaults below.
 */

$root = rtrim(str_replace('\\', '/', __DIR__), '/') . '/';
$host = $_SERVER['HTTP_HOST'] ?? '';
$is_mamp_root = strpos($root, '/Applications/MAMP/htdocs/') === 0 || strpos($root, '/Volumes/Projects/MAMP/htdocs/') === 0;
$is_local = $is_mamp_root || in_array($host, array('localhost:8888', '127.0.0.1:8888', 'localhost', '127.0.0.1'), true);

$settings = array(
	'root' => $root,
	'http_catalog' => $is_local ? 'http://localhost:8888/NanoChemicalGroup/central-office/trading-house/shokeru.in.ua/' : 'https://shokeru.autochemicals.s3.monosite.com.ua/',
	'https_catalog' => $is_local ? 'http://localhost:8888/NanoChemicalGroup/central-office/trading-house/shokeru.in.ua/' : 'https://shokeru.autochemicals.s3.monosite.com.ua/',
	'admin_path' => $is_local ? 'admin' : 'shk_panel',
	'storage' => $is_local ? $root . 'system/storage/' : '/home/m366/web/shokeru.autochemicals.s3.monosite.com.ua/private/storage/',
	'db' => array(
		'driver' => 'mysqli',
		'hostname' => $is_local ? 'localhost' : 'localhost',
		'username' => $is_local ? 'root' : 'm366_shokeru',
		'password' => $is_local ? 'root' : '{@HVVuF2j(?l:k#b',
		'database' => $is_local ? 'shokeru' : 'm366_shokeru',
		'port' => $is_local ? '8889' : '3306',
		'prefix' => 'oc_',
	),
);

$local_config = __DIR__ . '/app_config.local.php';

if (is_file($local_config)) {
	$local_settings = require $local_config;

	if (is_array($local_settings)) {
		$settings = array_replace_recursive($settings, $local_settings);
	}
}

$settings['root'] = rtrim(str_replace('\\', '/', $settings['root']), '/') . '/';
$settings['http_catalog'] = rtrim($settings['http_catalog'], '/') . '/';
$settings['https_catalog'] = rtrim($settings['https_catalog'], '/') . '/';
$settings['storage'] = rtrim(str_replace('\\', '/', $settings['storage']), '/') . '/';

return $settings;
