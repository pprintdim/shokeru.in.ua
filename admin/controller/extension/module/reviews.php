<?php
// Модуль «Відгуки покупців» (головна): останні схвалені відгуки з oc_review
class ControllerExtensionModuleReviews extends Controller {
	private $error = array();

	public function index() {
		$this->load->language('extension/module/reviews');
		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('setting/module');
		$this->load->model('localisation/language');

		$module_id = isset($this->request->get['module_id']) ? (int)$this->request->get['module_id'] : 0;
		$module_info = array();

		if ($module_id && $this->request->server['REQUEST_METHOD'] !== 'POST') {
			$module_info = (array)$this->model_setting_module->getModule($module_id);
		}

		if ($this->request->server['REQUEST_METHOD'] === 'POST' && $this->validate()) {
			$save = array(
				'name'    => $this->request->post['name'],
				'status'  => isset($this->request->post['status']) ? (int)$this->request->post['status'] : 0,
				'limit'   => max(1, min(24, (int)$this->request->post['limit'])),
				'heading' => isset($this->request->post['heading']) ? (array)$this->request->post['heading'] : array()
			);

			if (!$module_id) {
				$this->model_setting_module->addModule('reviews', $save);
			} else {
				$this->model_setting_module->editModule($module_id, $save);
			}

			$this->session->data['success'] = $this->language->get('text_success');

			$this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true));
		}

		$token = 'user_token=' . $this->session->data['user_token'];

		$data['error_warning'] = isset($this->error['warning']) ? $this->error['warning'] : '';
		$data['error_name'] = isset($this->error['name']) ? $this->error['name'] : '';

		$data['breadcrumbs'] = array(
			array('text' => $this->language->get('text_home'), 'href' => $this->url->link('common/dashboard', $token, true)),
			array('text' => $this->language->get('text_extension'), 'href' => $this->url->link('marketplace/extension', $token . '&type=module', true)),
			array('text' => $this->language->get('heading_title'), 'href' => $this->url->link('extension/module/reviews', $token . ($module_id ? '&module_id=' . $module_id : ''), true))
		);

		$data['action'] = $this->url->link('extension/module/reviews', $token . ($module_id ? '&module_id=' . $module_id : ''), true);
		$data['cancel'] = $this->url->link('marketplace/extension', $token . '&type=module', true);
		$data['languages'] = $this->model_localisation_language->getLanguages();

		$post = $this->request->post;

		$data['name'] = isset($post['name']) ? $post['name'] : (isset($module_info['name']) ? $module_info['name'] : '');
		$data['status'] = isset($post['status']) ? (int)$post['status'] : (isset($module_info['status']) ? (int)$module_info['status'] : 1);
		$data['limit'] = isset($post['limit']) ? (int)$post['limit'] : (isset($module_info['limit']) ? (int)$module_info['limit'] : 6);
		$data['heading'] = isset($post['heading']) ? (array)$post['heading'] : (isset($module_info['heading']) ? (array)$module_info['heading'] : array());

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/module/reviews', $data));
	}

	protected function validate() {
		if (!$this->user->hasPermission('modify', 'extension/module/reviews')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		if ((utf8_strlen($this->request->post['name']) < 3) || (utf8_strlen($this->request->post['name']) > 64)) {
			$this->error['name'] = $this->language->get('error_name');
		}

		return !$this->error;
	}
}
