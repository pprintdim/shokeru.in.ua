<?php
class ControllerExtensionFeedGoogleSitemap extends Controller {

	private function xmlText($str) {
		$str = html_entity_decode((string)$str, ENT_QUOTES | ENT_HTML5, 'UTF-8');
		$str = strip_tags($str);
		$str = preg_replace('/[^\x09\x0A\x0D\x20-\x{D7FF}\x{E000}-\x{FFFD}\x{10000}-\x{10FFFF}]/u', '', $str);
		return htmlspecialchars($str, ENT_XML1 | ENT_QUOTES, 'UTF-8');
	}

	private function xmlUrl($url) {
		$url = (string)$url;
		$url = str_replace('&amp;', '&', $url);
		return htmlspecialchars($url, ENT_XML1, 'UTF-8');
	}

	private function baseUrl() {
		$server = !empty($this->request->server['HTTPS'])
			? $this->config->get('config_ssl')
			: $this->config->get('config_url');
		return rtrim($server, '/');
	}

	public function index() {
		if (!$this->config->get('feed_google_sitemap_status')) {
			return;
		}

		ob_start();

		$uri  = parse_url($this->request->server['REQUEST_URI'], PHP_URL_PATH);
		$type = trim(str_replace('/sitemap', '', $uri), '/');
		$type = preg_replace('/\.xml$/', '', $type);
		if (!$type && isset($this->request->get['sitemap_section'])) {
			$type = preg_replace('/[^a-z]/', '', (string)$this->request->get['sitemap_section']);
		}

		$this->response->addHeader('Content-Type: application/xml; charset=UTF-8');

		switch ($type) {
			case 'products':
				$output = $this->sitemapProducts();
				break;
			case 'pages':
				$output = $this->sitemapPages();
				break;
			case 'brands':
				$output = $this->sitemapBrands();
				break;
			case 'images':
				$output = $this->sitemapImages();
				break;
			default:
				$output = $this->sitemapIndex();
				break;
		}

		ob_end_clean();

		$this->response->setOutput($output);
	}

	private function sitemapIndex() {
		$base = $this->baseUrl();

		$output  = '<?xml version="1.0" encoding="UTF-8"?>';
		$output .= '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

		foreach (array('pages', 'products', 'brands', 'images') as $section) {
			$output .= '<sitemap>';
			$output .= '<loc>' . $this->xmlUrl($base . '/sitemap/' . $section . '.xml') . '</loc>';
			$output .= '<lastmod>' . date('Y-m-d') . '</lastmod>';
			$output .= '</sitemap>';
		}

		$output .= '</sitemapindex>';

		return $output;
	}

	private function sitemapPages() {
		$this->load->model('catalog/information');

		$output  = '<?xml version="1.0" encoding="UTF-8"?>';
		$output .= $this->openUrlset();

		$pages = [
			['route' => 'common/home',        'changefreq' => 'daily',   'priority' => '1.0'],
			['route' => 'product/shop',        'changefreq' => 'weekly',  'priority' => '0.8'],
			['route' => 'product/latest',      'changefreq' => 'weekly',  'priority' => '0.7'],
			['route' => 'product/special',     'changefreq' => 'weekly',  'priority' => '0.7'],
			['route' => 'information/about',    'changefreq' => 'monthly', 'priority' => '0.5'],
			['route' => 'information/contact',  'changefreq' => 'monthly', 'priority' => '0.5'],
			['route' => 'information/customer', 'changefreq' => 'monthly', 'priority' => '0.5'],
			['route' => 'information/payment_delivery', 'changefreq' => 'monthly', 'priority' => '0.5'],
		];

		foreach ($pages as $page) {
			$output .= $this->localizedUrlXml($page['route'], [], [
				'changefreq' => $page['changefreq'],
				'priority'   => $page['priority']
			]);
		}

		foreach ($this->model_catalog_information->getInformations() as $information) {
			$output .= $this->localizedUrlXml('information/information', ['information_id' => $information['information_id']], [
				'changefreq' => 'monthly',
				'priority'   => '0.5'
			]);
		}

		// Блог: лістинг + статті
		$output .= $this->localizedUrlXml('extension/blog', [], [
			'changefreq' => 'weekly',
			'priority'   => '0.6'
		]);

		$blog_query = $this->db->query("
			SELECT p.post_id, p.date_modified
			FROM " . DB_PREFIX . "post p
			LEFT JOIN " . DB_PREFIX . "post_to_store p2s ON (p.post_id = p2s.post_id)
			WHERE p.status = '1' AND DATE(p.date) <= NOW()
			GROUP BY p.post_id
		");

		foreach ($blog_query->rows as $post) {
			$output .= $this->localizedUrlXml('extension/post', ['post_id' => $post['post_id']], [
				'changefreq' => 'monthly',
				'priority'   => '0.5',
				'lastmod'    => date('Y-m-d', strtotime($post['date_modified']))
			]);
		}

		$output .= "\n</urlset>";

		return $output;
	}

	private function sitemapProducts() {
		$this->load->model('catalog/category');

		$output  = '<?xml version="1.0" encoding="UTF-8"?>';
		$output .= $this->openUrlset();

		$output .= $this->getCategories(0);

		$query = $this->db->query("
			SELECT p.product_id, p.date_modified
			FROM " . DB_PREFIX . "product p
			LEFT JOIN " . DB_PREFIX . "product_to_store p2s ON (p.product_id = p2s.product_id)
			WHERE p.status = '1'
			  AND p.date_available <= NOW()
			  AND p2s.store_id = '" . (int)$this->config->get('config_store_id') . "'
			GROUP BY p.product_id
			ORDER BY p.sort_order ASC
		");

		foreach ($query->rows as $product) {
			$output .= $this->localizedUrlXml('product/product', ['product_id' => $product['product_id']], [
				'changefreq' => 'weekly',
				'lastmod'    => date('Y-m-d\TH:i:sP', strtotime($product['date_modified'])),
				'priority'   => '1.0'
			]);
		}

		$output .= "\n</urlset>";

		return $output;
	}

	private function sitemapBrands() {
		$this->load->model('catalog/manufacturer');

		$output  = '<?xml version="1.0" encoding="UTF-8"?>';
		$output .= $this->openUrlset();

		foreach ($this->model_catalog_manufacturer->getManufacturers() as $manufacturer) {
			$output .= $this->localizedUrlXml('product/manufacturer/info', ['manufacturer_id' => $manufacturer['manufacturer_id']], [
				'changefreq' => 'weekly',
				'priority'   => '0.7'
			]);
		}

		$output .= "\n</urlset>";

		return $output;
	}

	private function sitemapImages() {
		$this->load->model('tool/image');

		$width  = (int)$this->config->get('theme_' . $this->config->get('config_theme') . '_image_popup_width')  ?: 1800;
		$height = (int)$this->config->get('theme_' . $this->config->get('config_theme') . '_image_popup_height') ?: 1800;

		$output  = '<?xml version="1.0" encoding="UTF-8"?>';
		$output .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">';

		$lang_id  = (int)$this->config->get('config_language_id');
		$store_id = (int)$this->config->get('config_store_id');

		$query = $this->db->query("
			SELECT p.product_id, p.image, pd.name
			FROM " . DB_PREFIX . "product p
			LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id AND pd.language_id = '" . $lang_id . "')
			LEFT JOIN " . DB_PREFIX . "product_to_store p2s ON (p.product_id = p2s.product_id)
			WHERE p.status = '1'
			  AND p.date_available <= NOW()
			  AND p2s.store_id = '" . $store_id . "'
			  AND p.image != ''
			GROUP BY p.product_id
			ORDER BY p.sort_order ASC
		");

		foreach ($query->rows as $product) {
			$image_url = $this->model_tool_image->resize($product['image'], $width, $height);
			if (empty($image_url)) continue;

			$raw_loc = html_entity_decode($this->url->link('product/product', 'product_id=' . $product['product_id']), ENT_QUOTES, 'UTF-8');
			if (strpos($raw_loc, 'index.php?route=') !== false || strpos($raw_loc, '?route=') !== false || strpos($raw_loc, '_route_=') !== false) continue;

			$loc       = $this->xmlUrl($raw_loc);
			$image_loc = $this->xmlUrl($image_url);
			$caption   = $this->xmlText($product['name']);

			if (!$loc || !$image_loc) continue;

			$output .= "\n<url>";
			$output .= '<loc>' . $loc . '</loc>';
			$output .= '<image:image>';
			$output .= '<image:loc>' . $image_loc . '</image:loc>';
			$output .= '<image:caption>' . $caption . '</image:caption>';
			$output .= '<image:title>' . $caption . '</image:title>';
			$output .= '</image:image>';
			$output .= '</url>';
		}

		$output .= "\n</urlset>";

		return $output;
	}

	protected function getCategories($parent_id) {
		$output = '';

		foreach ($this->model_catalog_category->getCategories($parent_id) as $result) {
			$output .= $this->localizedUrlXml('product/category', ['path' => $result['category_id']], [
				'changefreq' => 'weekly',
				'priority'   => '0.8'
			]);

			$output .= $this->getCategories($result['category_id']);
		}

		return $output;
	}

	private function openUrlset($extra_namespace = '') {
		return '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml"' . $extra_namespace . '>';
	}

	private function localizedUrlXml($route, array $args = [], array $meta = []) {
		$languages = $this->getActiveLanguages();
		$default_code = $this->config->get('config_language');
		$alternates = [];

		foreach ($languages as $code => $language) {
			$href = $this->localizedUrl($route, $args, $code, (int)$language['language_id']);
			if ($href) {
				$alternates[$code] = $href;
			}
		}

		if (!$alternates) {
			return '';
		}

		$x_default = isset($alternates[$default_code]) ? $alternates[$default_code] : reset($alternates);
		$output = '';

		// слаги каталогу однакові для всіх мов — один <url> на унікальну адресу, інакше кожен товар іде тричі
		foreach (array_unique($alternates) as $href) {
			$output .= "\n<url>";
			$output .= '<loc>' . $this->xmlUrl($href) . '</loc>';

			foreach ($alternates as $code => $alternate_href) {
				$output .= '<xhtml:link rel="alternate" hreflang="' . $this->xmlText($this->hreflang($code)) . '" href="' . $this->xmlUrl($alternate_href) . '" />';
			}

			$output .= '<xhtml:link rel="alternate" hreflang="x-default" href="' . $this->xmlUrl($x_default) . '" />';

			if (!empty($meta['lastmod'])) {
				$output .= '<lastmod>' . $this->xmlText($meta['lastmod']) . '</lastmod>';
			}
			if (!empty($meta['changefreq'])) {
				$output .= '<changefreq>' . $this->xmlText($meta['changefreq']) . '</changefreq>';
			}
			if (!empty($meta['priority'])) {
				$output .= '<priority>' . $this->xmlText($meta['priority']) . '</priority>';
			}

			$output .= '</url>';
		}

		return $output;
	}

	private function getActiveLanguages() {
		$this->load->model('localisation/language');

		$languages = [];

		foreach ($this->model_localisation_language->getLanguages() as $code => $language) {
			if (!empty($language['status'])) {
				$languages[$code] = $language;
			}
		}

		return $languages;
	}

	private function localizedUrl($route, array $args, $language_code, $language_id) {
		$old_language = isset($this->session->data['language']) ? $this->session->data['language'] : null;
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

		if (strpos($url, 'index.php?route=') !== false || strpos($url, '?route=') !== false || strpos($url, '_route_=') !== false) {
			return '';
		}

		return $url;
	}

	private function hreflang($code) {
		$parts = explode('-', strtolower($code));
		return $parts[0];
	}
}
