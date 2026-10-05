<?php
class ControllerCommonFooter extends Controller {
	private function getLangText($value, $language_id) {

	    // якщо мультимовне поле
	    if (is_array($value) && isset($value[$language_id])) {
	        $value = $value[$language_id];
	    }

	    // гарантуємо string
	    $value = (string)$value;

	    // decode html
	    $value = html_entity_decode($value, ENT_QUOTES, 'UTF-8');

	    // зберігаємо переноси рядків
	    return nl2br($value);
	}

	public function index() {
		$this->load->language('common/footer');

		$this->load->model('catalog/information');

		$data['informations'] = array();
		$data['legal'] = array();

		foreach ($this->model_catalog_information->getInformations() as $result) {
			// «Показувати в підфутері» (info_flags.sql): політика, умови — у нижньому рядку поруч із ©
			if (!empty($result['subfooter'])) {
				$data['legal'][] = array(
					'title' => $result['title'],
					'href'  => $this->url->link('information/information', 'information_id=' . $result['information_id'])
				);
			}

			if ($result['bottom']) {
				$data['informations'][] = array(
					'title' => $result['title'],
					'href'  => $this->url->link('information/information', 'information_id=' . $result['information_id'])
				);
			}
		}


		if ($this->request->server['HTTPS']) {
			$server = $this->config->get('config_ssl');
		} else {
			$server = $this->config->get('config_url');
		}

		$data['base'] = $server;
		$data['name'] = $this->config->get('config_name');
		if (is_file(DIR_IMAGE . $this->config->get('config_logo'))) {
			$data['logo'] = $server . 'image/' . $this->config->get('config_logo');
		} else {
			$data['logo'] = '';
		}

		$data['cart'] = $this->load->controller('common/cart');
		$data['user_popup'] = $this->load->controller('common/user_popup');
		// «Купити в один клік» (попап) і cookie-банер — на кожній сторінці
		$data['quick_order'] = $this->load->controller('common/quick_order');
		$data['cookie'] = $this->load->controller('common/cookie');
		$data['contact'] = $this->url->link('information/contact', '', true);
		$data['about'] = $this->url->link('information/about', '', true);
		$data['return'] = $this->url->link('account/return/add', '', true);
		$data['sitemap'] = $this->url->link('information/sitemap');
		$data['tracking'] = $this->url->link('information/tracking');
		$data['manufacturer'] = $this->url->link('product/manufacturer');
		$data['voucher'] = $this->url->link('account/voucher', '', true);
		$data['affiliate'] = $this->url->link('affiliate/login', '', true);
		$data['special'] = $this->url->link('product/special');
		$data['blog'] = $this->url->link('extension/blog');
		$data['account'] = $this->url->link('account/account', '', true);
		$data['order'] = $this->url->link('account/order', '', true);
		$data['wishlist'] = $this->url->link('account/wishlist', '', true);
		$data['newsletter'] = $this->url->link('account/newsletter', '', true);
		$data['logged'] = $this->customer->isLogged();
		// плаваючі кнопки: перший телефон магазину
		$data['phones'] = array();

		foreach (array($this->config->get('config_telephone'), $this->config->get('config_fax')) as $phone) {
			$phone = trim((string)$phone);

			if ($phone !== '') {
				$data['phones'][] = array(
					'href'  => 'tel:' . preg_replace('/[^\d+]/', '', $phone),
					'label' => $phone
				);
			}
		}

		$data['powered'] = sprintf($this->language->get('text_powered'), $this->config->get('config_name'), date('Y', time()));
		$data['facebook'] = $this->config->get('config_facebook');
		$data['instagram'] = $this->config->get('config_instagram');
		$data['customers'] = $this->url->link('information/customer', '', true);
		$data['payment_delivery'] = $this->url->link('information/payment_delivery', '', true);

		$language_id = (int)$this->config->get('config_language_id');

		$data['address'] = $this->getLangText(
		    $this->config->get('config_address'),
		    $language_id
		);

		$data['open'] = $this->getLangText(
		    $this->config->get('config_open'),
		    $language_id
		);



		// Whos Online
		if ($this->config->get('config_customer_online')) {
			$this->load->model('tool/online');

			if (isset($this->request->server['REMOTE_ADDR'])) {
				$ip = $this->request->server['REMOTE_ADDR'];
			} else {
				$ip = '';
			}

			if (isset($this->request->server['HTTP_HOST']) && isset($this->request->server['REQUEST_URI'])) {
				$url = ($this->request->server['HTTPS'] ? 'https://' : 'http://') . $this->request->server['HTTP_HOST'] . $this->request->server['REQUEST_URI'];
			} else {
				$url = '';
			}

			if (isset($this->request->server['HTTP_REFERER'])) {
				$referer = $this->request->server['HTTP_REFERER'];
			} else {
				$referer = '';
			}

			$this->model_tool_online->addOnline($ip, $this->customer->getId(), $url, $referer);
		}

		$data['scripts'] = $this->document->getScripts('footer');
		$data['styles'] = $this->document->getStyles('footer');
		$data['custom_scripts'] = array();

		if ((int)$this->config->get('module_custom_scripts_status')) {
			$custom_scripts_items = $this->config->get('module_custom_scripts_items') ?: array();

			foreach ($custom_scripts_items as $script) {
				if (!empty($script['code']) && (!empty($script['position']) && $script['position'] == 'footer')) {
					$data['custom_scripts'][] = array(
						'name' => $script['name'] ?? '',
						'code' => html_entity_decode(trim($script['code'], '"\''), ENT_QUOTES, 'UTF-8')
					);
				}
			}
		}
		
		return $this->load->view('common/footer', $data);
	}
}
