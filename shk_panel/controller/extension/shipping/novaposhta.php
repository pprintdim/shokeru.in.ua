<?php
// Окрема картка перевізника в списку розширень. Двигун доставки спільний
// (model extension/shipping/delivery) — тут лише статус і вартість «Нова Пошта»,
// які зберігаються в загальні налаштування shipping_delivery.
class ControllerExtensionShippingNovaposhta extends Controller {
	private $error = array();

	public function index() {
		$this->load->language('extension/shipping/novaposhta');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('setting/setting');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			$settings = $this->model_setting_setting->getSetting('shipping_delivery');

			$settings['shipping_delivery_novaposhta_cost'] = $this->request->post['shipping_delivery_novaposhta_cost'];
			$settings['shipping_delivery_novaposhta_enabled'] = $this->request->post['shipping_delivery_novaposhta_enabled'];
			$settings['shipping_delivery_status'] = isset($settings['shipping_delivery_status']) ? $settings['shipping_delivery_status'] : 1;

			$this->model_setting_setting->editSetting('shipping_delivery', $settings);

			$this->session->data['success'] = $this->language->get('text_success');

			$this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=shipping', true));
		}

		$data['error_warning'] = isset($this->error['warning']) ? $this->error['warning'] : '';

		$data['breadcrumbs'] = array(
			array('text' => $this->language->get('text_home'), 'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)),
			array('text' => $this->language->get('text_extension'), 'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=shipping', true)),
			array('text' => $this->language->get('heading_title'), 'href' => $this->url->link('extension/shipping/novaposhta', 'user_token=' . $this->session->data['user_token'], true))
		);

		$data['action'] = $this->url->link('extension/shipping/novaposhta', 'user_token=' . $this->session->data['user_token'], true);
		$data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=shipping', true);

		$cost_key = 'shipping_delivery_novaposhta_cost';
		$enabled_key = 'shipping_delivery_novaposhta_enabled';

		$data['cost'] = isset($this->request->post[$cost_key]) ? $this->request->post[$cost_key] : $this->config->get($cost_key);
		$enabled = isset($this->request->post[$enabled_key]) ? $this->request->post[$enabled_key] : $this->config->get($enabled_key);
		$data['enabled'] = ($enabled === null || $enabled === '') ? 1 : (int)$enabled;

		$data['cost_key'] = $cost_key;
		$data['enabled_key'] = $enabled_key;

		// синхронізація довідника відділень (порційно, як у delivery)
		$data['sync_url'] = $this->url->link('extension/shipping/novaposhta/syncNp', 'user_token=' . $this->session->data['user_token'], true);
		$data['sync_param'] = 'page';
		$data['sync_start'] = 1;
		$data['button_sync'] = $this->language->get('button_sync');

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/shipping/hp_carrier', $data));
	}

	private function ensureTable() {
		$this->db->query("
			CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "np_warehouse` (
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
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
		");
	}

	private function httpJson($url, $payload = null) {
		$curl = curl_init($url);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_TIMEOUT, 30);

		if ($payload !== null) {
			curl_setopt($curl, CURLOPT_POST, true);
			curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
			curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($payload, JSON_UNESCAPED_UNICODE));
		}

		$out = curl_exec($curl);
		curl_close($curl);

		return json_decode((string)$out, true);
	}

	public function syncNp() {
		$this->load->language('extension/shipping/novaposhta');

		$json = array();

		if (!$this->user->hasPermission('modify', 'extension/shipping/novaposhta')) {
			$json['error'] = $this->language->get('error_permission');
		} else {
			$this->ensureTable();

			$page = max(1, (int)($this->request->get['page'] ?? 1));
			$limit = 500;

			// перша сторінка починає новий довідник
			if ($page == 1) {
				$this->db->query("DELETE FROM " . DB_PREFIX . "np_warehouse WHERE carrier = 'novaposhta'");
			}

			$result = $this->httpJson('https://api.novaposhta.ua/v2.0/json/', array(
				'apiKey'           => '',
				'modelName'        => 'AddressGeneral',
				'calledMethod'     => 'getWarehouses',
				'methodProperties' => array('Limit' => (string)$limit, 'Page' => (string)$page)
			));

			$rows = array();

			foreach (($result['data'] ?? array()) as $warehouse) {
				$city = trim((string)($warehouse['CityDescription'] ?? ''));
				$desc = trim((string)($warehouse['Description'] ?? ''));

				if ($city === '' || $desc === '') {
					continue;
				}

				$type = (mb_stripos($desc, 'поштомат') !== false) ? 'postomat' : 'branch';

				$rows[] = "('novaposhta','" . $this->db->escape($city) . "','" . $this->db->escape(trim((string)($warehouse['CityDescriptionRu'] ?? ''))) . "','"
					. $type . "','" . $this->db->escape($desc) . "','" . $this->db->escape(trim((string)($warehouse['DescriptionRu'] ?? ''))) . "','"
					. $this->db->escape(trim((string)($warehouse['Number'] ?? ''))) . "','" . $this->db->escape(trim((string)($warehouse['Ref'] ?? ''))) . "')";
			}

			if ($rows) {
				$this->db->query("INSERT INTO " . DB_PREFIX . "np_warehouse (carrier, city, city_ru, type, description, description_ru, number, ref) VALUES " . implode(',', $rows));
			}

			$received = count($result['data'] ?? array());

			$json['added'] = count($rows);
			$json['done'] = ($received < $limit);
			$json['next'] = $page + 1;
			$json['total'] = (int)$this->db->query("SELECT COUNT(*) AS c FROM " . DB_PREFIX . "np_warehouse WHERE carrier = 'novaposhta'")->row['c'];
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	protected function validate() {
		if (!$this->user->hasPermission('modify', 'extension/shipping/novaposhta')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		return !$this->error;
	}
}
