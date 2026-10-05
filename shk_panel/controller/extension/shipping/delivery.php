<?php
// Delivery: Nova Poshta / Ukrposhta / Meest / courier / pickup in one shipping extension
// (pattern hydrophob.net). Each carrier has its own flat cost; 0 = paid by carrier tariff on receipt.
class ControllerExtensionShippingDelivery extends Controller {
	private $error = array();

	private $cost_codes = array('novaposhta', 'ukrposhta', 'meest', 'courier', 'pickup');

	public function index() {
		$this->load->language('extension/shipping/delivery');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('setting/setting');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			$this->model_setting_setting->editSetting('shipping_delivery', $this->request->post);

			$this->session->data['success'] = $this->language->get('text_success');

			$this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=shipping', true));
		}

		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_extension'),
			'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=shipping', true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('extension/shipping/delivery', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['action'] = $this->url->link('extension/shipping/delivery', 'user_token=' . $this->session->data['user_token'], true);

		$data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=shipping', true);

		$data['costs'] = array();

		foreach ($this->cost_codes as $code) {
			$key = 'shipping_delivery_' . $code . '_cost';

			if (isset($this->request->post[$key])) {
				$value = $this->request->post[$key];
			} else {
				$value = $this->config->get($key);
			}

			if ($value === null) {
				$value = 0;
			}

			$data['costs'][] = array(
				'code'  => $code,
				'name'  => $key,
				'label' => $this->language->get('entry_' . $code . '_cost'),
				'value' => $value
			);
		}

		if (isset($this->request->post['shipping_delivery_tax_class_id'])) {
			$data['shipping_delivery_tax_class_id'] = $this->request->post['shipping_delivery_tax_class_id'];
		} else {
			$data['shipping_delivery_tax_class_id'] = $this->config->get('shipping_delivery_tax_class_id');
		}

		$this->load->model('localisation/tax_class');

		$data['tax_classes'] = $this->model_localisation_tax_class->getTaxClasses();

		if (isset($this->request->post['shipping_delivery_geo_zone_id'])) {
			$data['shipping_delivery_geo_zone_id'] = $this->request->post['shipping_delivery_geo_zone_id'];
		} else {
			$data['shipping_delivery_geo_zone_id'] = $this->config->get('shipping_delivery_geo_zone_id');
		}

		$this->load->model('localisation/geo_zone');

		$data['geo_zones'] = $this->model_localisation_geo_zone->getGeoZones();

		if (isset($this->request->post['shipping_delivery_status'])) {
			$data['shipping_delivery_status'] = $this->request->post['shipping_delivery_status'];
		} else {
			$data['shipping_delivery_status'] = $this->config->get('shipping_delivery_status');
		}

		if (isset($this->request->post['shipping_delivery_sort_order'])) {
			$data['shipping_delivery_sort_order'] = $this->request->post['shipping_delivery_sort_order'];
		} else {
			$data['shipping_delivery_sort_order'] = $this->config->get('shipping_delivery_sort_order');
		}

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/shipping/delivery', $data));
	}

	public function install() {
		$this->load->model('setting/setting');

		$this->model_setting_setting->editSetting('shipping_delivery', array(
			'shipping_delivery_status'          => 0,
			'shipping_delivery_novaposhta_cost' => 0,
			'shipping_delivery_ukrposhta_cost'  => 0,
			'shipping_delivery_meest_cost'      => 0,
			'shipping_delivery_courier_cost'    => 0,
			'shipping_delivery_pickup_cost'     => 0,
			'shipping_delivery_tax_class_id'    => 0,
			'shipping_delivery_geo_zone_id'     => 0,
			'shipping_delivery_sort_order'      => 0
		));
	}

	public function uninstall() {
		$this->load->model('setting/setting');

		$this->model_setting_setting->deleteSetting('shipping_delivery');
	}

	protected function validate() {
		if (!$this->user->hasPermission('modify', 'extension/shipping/delivery')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		foreach ($this->cost_codes as $code) {
			$key = 'shipping_delivery_' . $code . '_cost';

			if (isset($this->request->post[$key]) && $this->request->post[$key] !== '' && !is_numeric($this->request->post[$key])) {
				$this->error['warning'] = $this->language->get('error_cost');
			}
		}

		return !$this->error;
	}
}
