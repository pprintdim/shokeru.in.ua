<?php
/**
 * Налаштування вітального тизера (Дизайн → Вітальний екран): відео-тизер поверх головної на першому візиті
 * і кілька випадкових разів на місяць — як у hydrophob. Зберігається звичайними рядками oc_setting з кодом `welcome`,
 * тож вітрина читає все через $this->config->get('welcome_*').
 */
class ControllerDesignWelcome extends Controller {
	private $error = array();

	// медіа-поля плиткою медіатеки: ключ налаштування => id поля
	private $media = array('welcome_video', 'welcome_video_mobile', 'welcome_poster', 'welcome_logo');

	public function index() {
		$this->load->language('design/welcome');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('setting/setting');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			$this->model_setting_setting->editSetting('welcome', $this->request->post);

			$this->session->data['success'] = $this->language->get('text_success');

			$this->response->redirect($this->url->link('design/welcome', 'user_token=' . $this->session->data['user_token'], true));
		}

		$this->getForm();
	}

	private function getForm() {
		$data['error_warning'] = isset($this->error['warning']) ? $this->error['warning'] : '';

		if (isset($this->session->data['success'])) {
			$data['success'] = $this->session->data['success'];
			unset($this->session->data['success']);
		} else {
			$data['success'] = '';
		}

		$data['breadcrumbs'] = array(
			array(
				'text' => $this->language->get('text_home'),
				'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
			),
			array(
				'text' => $this->language->get('heading_title'),
				'href' => $this->url->link('design/welcome', 'user_token=' . $this->session->data['user_token'], true)
			)
		);

		$data['action'] = $this->url->link('design/welcome', 'user_token=' . $this->session->data['user_token'], true);
		$data['cancel'] = $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true);

		// значення форми: свіжий POST важливіший за збережене, збережене — за типове
		$defaults = array(
			'welcome_status'       => 1,
			'welcome_shows'        => 4,
			'welcome_chance'       => 20,
			'welcome_interval'     => 6,
			'welcome_video'        => '',
			'welcome_video_mobile' => '',
			'welcome_poster'       => '',
			'welcome_logo'         => ''
		);

		foreach ($defaults as $field => $default) {
			if (isset($this->request->post[$field])) {
				$data[$field] = $this->request->post[$field];
			} elseif ($this->config->has($field) && $this->config->get($field) !== null && $this->config->get($field) !== '') {
				$data[$field] = $this->config->get($field);
			} else {
				$data[$field] = $default;
			}
		}

		// прев'ю плиток: зображення — ресайз, відео — заглушка медіатеки
		$this->load->model('tool/image');

		$data['placeholder'] = $this->model_tool_image->resize('no_image.png', 100, 100);

		foreach ($this->media as $field) {
			$path = trim((string)$data[$field]);
			$thumb = $data['placeholder'];

			if ($path !== '' && is_file(DIR_IMAGE . $path)) {
				if (preg_match('~\.(mp4|webm|ogv|mov|m4v)$~i', $path)) {
					$thumb = HTTP_CATALOG . 'image/catalog/video-placeholder.svg';
				} else {
					$thumb = $this->model_tool_image->resize($path, 100, 100);
				}
			}

			$data['thumb_' . substr($field, 8)] = $thumb;
		}

		$data['user_token'] = $this->session->data['user_token'];

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('design/welcome', $data));
	}

	private function validate() {
		if (!$this->user->hasPermission('modify', 'design/welcome')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		return !$this->error;
	}
}
