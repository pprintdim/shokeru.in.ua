<?php
class ControllerCommonHeader extends Controller {
	public function index() {
		// Analytics
		$this->load->model('setting/extension');

		$data['analytics'] = array();

		$analytics = $this->model_setting_extension->getExtensions('analytics');

		foreach ($analytics as $analytic) {
			if ($this->config->get('analytics_' . $analytic['code'] . '_status')) {
				$data['analytics'][] = $this->load->controller('extension/analytics/' . $analytic['code'], $this->config->get('analytics_' . $analytic['code'] . '_status'));
			}
		}

		if ($this->request->server['HTTPS']) {
			$server = $this->config->get('config_ssl');
		} else {
			$server = $this->config->get('config_url');
		}

		if (is_file(DIR_IMAGE . $this->config->get('config_icon'))) {
			$this->document->addLink($server . 'image/' . $this->config->get('config_icon'), 'icon');
		} elseif (is_file(DIR_ROOT . 'favicon.ico')) {
			$this->document->addLink($server . 'favicon.ico', 'icon');
		}

		if (is_file(DIR_IMAGE . 'catalog/apple-touch-icon.png')) {
			$this->document->addLink($server . 'image/catalog/apple-touch-icon.png', 'apple-touch-icon');
		}
		$language_id = (int)$this->config->get('config_language_id');

		if (is_array($this->document->getTitle())) {
		    $titles = $this->document->getTitle();
		    $data['title'] = $titles[$language_id] ?? '';
		} else {
		    $data['title'] = $this->document->getTitle();
		}

		if (is_array($this->document->getDescription())) {
		    $descriptions = $this->document->getDescription();
		    $data['description'] = $descriptions[$language_id] ?? '';
		} else {
		    $data['description'] = $this->document->getDescription();
		}



		$data['base'] = $server;
		$data['keywords'] = $this->document->getKeywords();
		$data['links'] = $this->getHeaderLinks($server);
		$data['styles'] = $this->document->getStyles();
		$data['scripts'] = $this->document->getScripts('header');
		$data['custom_scripts'] = array();

		if ((int)$this->config->get('module_custom_scripts_status')) {
			$custom_scripts_items = $this->config->get('module_custom_scripts_items') ?: array();

			foreach ($custom_scripts_items as $script) {
				if (!empty($script['code']) && (empty($script['position']) || $script['position'] == 'head')) {
					$data['custom_scripts'][] = array(
						'name' => $script['name'] ?? '',
						'code' => html_entity_decode(trim($script['code'], '"\''), ENT_QUOTES, 'UTF-8')
					);
				}
			}
		}

		$data['lang'] = $this->language->get('code');
		$data['direction'] = $this->language->get('direction');

		$data['name'] = $this->config->get('config_name');

		if (is_file(DIR_IMAGE . $this->config->get('config_logo'))) {
			$data['logo'] = $server . 'image/' . $this->config->get('config_logo');
		} else {
			$data['logo'] = '';
		}

		// окремий логотип для мобільної навігації
		if ($this->config->get('config_logo_mob') && is_file(DIR_IMAGE . $this->config->get('config_logo_mob'))) {
			$data['logo_mob'] = $server . 'image/' . $this->config->get('config_logo_mob');
		} else {
			$data['logo_mob'] = '';
		}

		$this->load->language('common/header');

		// Wishlist
		if ($this->customer->isLogged()) {
			$this->load->model('account/wishlist');

			$wishlist_rows = $this->model_account_wishlist->getWishlist();
			$wishlist_total = (int)$this->model_account_wishlist->getTotalWishlist();
			$wishlist_product_ids = array();

			foreach ($wishlist_rows as $wishlist_product) {
				$wishlist_product_ids[] = (int)$wishlist_product['product_id'];
			}

			$data['text_wishlist'] = sprintf($this->language->get('text_wishlist'), $wishlist_total);
		} else {
			$wishlist_product_ids = isset($this->session->data['wishlist']) && is_array($this->session->data['wishlist']) ? array_values(array_unique(array_map('intval', $this->session->data['wishlist']))) : array();
			$wishlist_total = count($wishlist_product_ids);
			$data['text_wishlist'] = sprintf($this->language->get('text_wishlist'), $wishlist_total);
		}

		$data['wishlist_total'] = $wishlist_total;
		$data['wishlist_product_ids'] = $wishlist_product_ids;
		$data['cart_total']     = $this->cart->countProducts();

		// Порівняння — іконка з лічильником поруч із обраним (список у сесії, оновлює compare.state у common.js)
		$compare_total = isset($this->session->data['compare']) && is_array($this->session->data['compare']) ? count($this->session->data['compare']) : 0;
		$data['compare_total'] = $compare_total;
		$data['text_compare'] = sprintf($this->language->get('text_compare'), $compare_total);
		$data['compare'] = $this->url->link('product/compare');


		$data['text_logged'] = sprintf($this->language->get('text_logged'), $this->url->link('account/account', '', true), $this->customer->getFirstName(), $this->url->link('account/logout', '', true));
		
		$data['home'] = $this->url->link('common/home');
		$data['latest'] = $this->url->link('product/latest');
		$data['blog'] = $this->url->link('extension/blog');
		$data['shop'] = $this->url->link('product/shop');
		$data['wishlist'] = $this->url->link('account/wishlist', '', true);
		$data['logged'] = $this->customer->isLogged();
		$data['account'] = $this->url->link('account/account', '', true);
		$data['register'] = $this->url->link('account/register', '', true);
		$data['login'] = $this->url->link('account/login', '', true);
		$data['order'] = $this->url->link('account/order', '', true);
		$data['transaction'] = $this->url->link('account/transaction', '', true);
		$data['download'] = $this->url->link('account/download', '', true);
		$data['logout'] = $this->url->link('account/logout', '', true);
		$data['shopping_cart'] = $this->url->link('checkout/cart');
		$data['checkout'] = $this->url->link('checkout/checkout', '', true);
		$data['contact'] = $this->url->link('information/contact');
		$data['special'] = $this->url->link('product/special');
		$data['about'] = $this->url->link('information/about');
		$data['customers'] = $this->url->link('information/customer');
		
		$data['language'] = $this->load->controller('common/language');
		$data['currency'] = $this->load->controller('common/currency');
		$data['search'] = $this->load->controller('common/search');
		
		$data['menu'] = $this->load->controller('common/menu');

		// категорії каталогу в мобільному меню-бургері
		$this->load->model('catalog/category');

		$data['menu_categories'] = array();

		foreach ($this->model_catalog_category->getCategories(0) as $category) {
			if (!empty($category['top'])) {
				$data['menu_categories'][] = array(
					'name' => $category['name'],
					'href' => $this->url->link('product/category', 'path=' . $category['category_id'])
				);
			}
		}
		$data['email'] = $this->config->get('config_email');
		$data['instagram'] = $this->config->get('config_instagram');
		$data['facebook'] = $this->config->get('config_facebook');
		$data['phones'] = [];

		$phones = [
		    $this->config->get('config_telephone'),
		    $this->config->get('config_fax'),
		];

		foreach ($phones as $phone) {
		    $phone = trim((string)$phone);
		    if ($phone === '') continue;

		    $data['phones'][] = [
		        'href'  => 'tel:' . preg_replace('/\D+/', '', $phone),
		        'label' => $phone,
		    ];
		}



		// CSS/JS: мініфікація з авто-перегенерацією за mtime (зміниш файл — перемініфікується сам)
		$data['css_main']  = $this->assetMin('catalog/view/css/style.css', 'css', '20261006');
		$data['css_anim']  = $this->assetMin('catalog/view/css/animations.css', 'css', '20260626b');
		$data['css_features'] = $this->assetMin('catalog/view/css/features.css', 'css', '20261006h');
		$data['js_common'] = $this->assetMin('catalog/view/js/common.js', 'js', '20261006b');
		// «Показувати в шапці» (info_flags.sql): сторінки інформації в верхньому меню
		$this->load->model('catalog/information');

		$data['informations_top'] = array();

		foreach ($this->model_catalog_information->getInformations() as $information) {
			if (!empty($information['top'])) {
				$data['informations_top'][] = array(
					'title' => $information['title'],
					'href'  => $this->url->link('information/information', 'information_id=' . $information['information_id'])
				);
			}
		}

		$data['currency_code'] = isset($this->session->data['currency']) ? $this->session->data['currency'] : $this->config->get('config_currency');
		$data['ga4_id'] = preg_match('~^G-[A-Z0-9]{6,}$~', (string)$this->config->get('config_ga4_id')) ? $this->config->get('config_ga4_id') : '';

		return $this->load->view('common/header', $data);
	}

	private function assetMin($rel, $type, $ver) {
		if (!$this->config->get('config_minify_' . $type)) {
			return $rel . '?v=' . $ver;
		}

		$src = DIR_ROOT . $rel;
		if (!is_file($src)) {
			return $rel . '?v=' . $ver;
		}

		$dir  = DIR_IMAGE . 'cache/min/';
		$name = md5($rel) . '.' . $type;
		$min  = $dir . $name;

		if (!is_file($min) || filemtime($src) > filemtime($min)) {
			if (!is_dir($dir)) @mkdir($dir, 0777, true);
			$content = (string)file_get_contents($src);
			$content = ($type === 'css') ? $this->minifyCss($content) : $this->minifyJs($content);
			// атомарний запис: temp + rename, щоб паралельні запити не читали обірваний файл
			$tmp = $min . '.' . getmypid() . '.tmp';
			if (@file_put_contents($tmp, $content) !== false) {
				@rename($tmp, $min);
			} else {
				@unlink($tmp);
			}
		}

		if (!is_file($min)) {
			return $rel . '?v=' . $ver;
		}

		return 'image/cache/min/' . $name . '?v=' . filemtime($min);
	}

	private function minifyCss($css) {
		$css = preg_replace('!/\*.*?\*/!s', '', $css);
		$css = preg_replace('/\s+/', ' ', $css);
		$css = preg_replace('/\s*([{}:;,>~])\s*/', '$1', $css);
		$css = str_replace(';}', '}', $css);

		return trim($css);
	}

	private function minifyJs($js) {
		// консервативно: прибрати порожні рядки та рядки-коментарі (//...), зберегти перенос рядків (без ризику ASI)
		$lines = array();
		foreach (explode("\n", $js) as $l) {
			$t  = rtrim($l);
			$lt = ltrim($t);
			if ($lt === '' || strpos($lt, '//') === 0) {
				continue;
			}
			$lines[] = $t;
		}

		return implode("\n", $lines);
	}

	// canonical (fallback) + hreflang-alternate лінки — як у well
	private function getHeaderLinks($server) {
		$links = array_values((array)$this->document->getLinks());

		// якщо контролер не виставив canonical — ставимо self (чистий шлях, без GET)
		$has_canonical = false;
		foreach ($links as $link) {
			if (strtolower((string)($link['rel'] ?? '')) === 'canonical') { $has_canonical = true; break; }
		}

		if (!$has_canonical) {
			$path = parse_url((string)($this->request->server['REQUEST_URI'] ?? '/'), PHP_URL_PATH);
			$path = '/' . ltrim((string)$path, '/');
			$self = ($path === '/') ? rtrim($server, '/') . '/' : rtrim($server, '/') . rtrim($path, '/');
			array_unshift($links, array('href' => $self, 'rel' => 'canonical'));
		}

		return array_merge($links, $this->getAlternateLinks());
	}

	private function getAlternateLinks() {
		$route = $this->request->get['route'] ?? 'common/home';
		$route = $route ?: 'common/home';
		$args = $this->getAlternateArgs($route);

		if ($args === null) {
			return array();
		}

		$languages = $this->getActiveLanguages();
		$default_code = $this->config->get('config_language');
		$links = array();

		foreach ($languages as $code => $language) {
			$href = $this->getLocalizedUrl($route, $args, $code, (int)$language['language_id']);
			if ($href) {
				$links[] = array('href' => $href, 'rel' => 'alternate', 'hreflang' => $this->getHreflang($code));
			}
		}

		if (isset($languages[$default_code])) {
			$href = $this->getLocalizedUrl($route, $args, $default_code, (int)$languages[$default_code]['language_id']);
			if ($href) {
				$links[] = array('href' => $href, 'rel' => 'alternate', 'hreflang' => 'x-default');
			}
		}

		return $links;
	}

	private function getAlternateArgs($route) {
		$params_by_route = array(
			'common/home'               => array(),
			'product/shop'              => array('page'),
			'product/latest'            => array('page'),
			'product/special'           => array('page'),
			'product/category'          => array('path', 'page'),
			'product/product'           => array('product_id'),
			'product/manufacturer/info' => array('manufacturer_id', 'page'),
			'information/information'    => array('information_id'),
			'information/about'         => array(),
			'information/contact'       => array(),
			'information/customer'      => array(),
			'information/payment_delivery' => array(),
		);

		if (!isset($params_by_route[$route])) {
			return null;
		}

		$args = array();
		foreach ($params_by_route[$route] as $key) {
			if (isset($this->request->get[$key]) && $this->request->get[$key] !== '') {
				$args[$key] = $this->request->get[$key];
			}
		}

		return $args;
	}

	private function getActiveLanguages() {
		$this->load->model('localisation/language');

		$languages = array();
		foreach ($this->model_localisation_language->getLanguages() as $code => $language) {
			if (!empty($language['status'])) {
				$languages[$code] = $language;
			}
		}

		return $languages;
	}

	private function getLocalizedUrl($route, array $args, $language_code, $language_id) {
		$old_language = $this->session->data['language'] ?? null;
		$old_language_id = $this->config->get('config_language_id');

		$this->session->data['language'] = $language_code;
		$this->config->set('config_language_id', $language_id);

		$query = http_build_query($args, '', '&');
		$url = html_entity_decode($this->url->link($route, $query, true), ENT_QUOTES, 'UTF-8');

		if ($old_language === null) {
			unset($this->session->data['language']);
		} else {
			$this->session->data['language'] = $old_language;
		}
		$this->config->set('config_language_id', $old_language_id);

		return $url;
	}

	private function getHreflang($code) {
		$parts = explode('-', strtolower($code));
		return $parts[0];
	}
}
