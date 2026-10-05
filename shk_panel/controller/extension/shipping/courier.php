<?php
// Окрема картка перевізника в списку розширень. Двигун доставки спільний
// (model extension/shipping/delivery) — тут лише статус і вартість «Кур'єр по місту»,
// які зберігаються в загальні налаштування shipping_delivery.
class ControllerExtensionShippingCourier extends Controller {
	private $error = array();

	public function index() {
		$this->load->language('extension/shipping/courier');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('setting/setting');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			$settings = $this->model_setting_setting->getSetting('shipping_delivery');

			$settings['shipping_delivery_courier_cost'] = $this->request->post['shipping_delivery_courier_cost'];
			$settings['shipping_delivery_courier_enabled'] = $this->request->post['shipping_delivery_courier_enabled'];
			$settings['shipping_delivery_status'] = isset($settings['shipping_delivery_status']) ? $settings['shipping_delivery_status'] : 1;

			$this->model_setting_setting->editSetting('shipping_delivery', $settings);

			$this->session->data['success'] = $this->language->get('text_success');

			$this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=shipping', true));
		}

		$data['error_warning'] = isset($this->error['warning']) ? $this->error['warning'] : '';

		$data['breadcrumbs'] = array(
			array('text' => $this->language->get('text_home'), 'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)),
			array('text' => $this->language->get('text_extension'), 'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=shipping', true)),
			array('text' => $this->language->get('heading_title'), 'href' => $this->url->link('extension/shipping/courier', 'user_token=' . $this->session->data['user_token'], true))
		);

		$data['action'] = $this->url->link('extension/shipping/courier', 'user_token=' . $this->session->data['user_token'], true);
		$data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=shipping', true);

		$cost_key = 'shipping_delivery_courier_cost';
		$enabled_key = 'shipping_delivery_courier_enabled';

		$data['cost'] = isset($this->request->post[$cost_key]) ? $this->request->post[$cost_key] : $this->config->get($cost_key);
		$enabled = isset($this->request->post[$enabled_key]) ? $this->request->post[$enabled_key] : $this->config->get($enabled_key);
		$data['enabled'] = ($enabled === null || $enabled === '') ? 1 : (int)$enabled;

		$data['cost_key'] = $cost_key;
		$data['enabled_key'] = $enabled_key;
		$data['sync_url'] = '';

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/shipping/hp_carrier', $data));
	}

	protected function validate() {
		if (!$this->user->hasPermission('modify', 'extension/shipping/courier')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		return !$this->error;
	}
}
