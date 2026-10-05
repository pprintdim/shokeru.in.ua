<?php
class ControllerExtensionModuleOneVideo extends Controller {
    private $error = [];

    public function index() {
        // Завантажуємо мову
        $this->load->language('extension/module/one_video');

        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('setting/module');

        // Збереження модуля
        if ($this->request->server['REQUEST_METHOD'] === 'POST' && $this->validate()) {
            if (!isset($this->request->get['module_id'])) {
                $this->model_setting_module->addModule('one_video', $this->request->post);
            } else {
                $this->model_setting_module->editModule($this->request->get['module_id'], $this->request->post);
            }

            $this->session->data['success'] = $this->language->get('text_success');

            $this->response->redirect($this->url->link(
                'marketplace/extension',
                'user_token=' . $this->session->data['user_token'] . '&type=module',
                true
            ));
        }

        // Помилки
        $data['error_warning'] = $this->error['warning'] ?? '';
        $data['error_name'] = $this->error['name'] ?? '';
        $data['error_frame'] = $this->error['frame'] ?? '';

        // Хлібні крихти
        $data['breadcrumbs'] = [
            [
                'text' => $this->language->get('text_home'),
                'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
            ],
            [
                'text' => $this->language->get('text_extension'),
                'href' => $this->url->link('marketplace/extension', 'type=module&user_token=' . $this->session->data['user_token'], true)
            ],
            [
                'text' => $this->language->get('heading_title'),
                'href' => isset($this->request->get['module_id'])
                    ? $this->url->link('extension/module/one_video', 'user_token=' . $this->session->data['user_token'] . '&module_id=' . $this->request->get['module_id'], true)
                    : $this->url->link('extension/module/one_video', 'user_token=' . $this->session->data['user_token'], true)
            ]
        ];

        // Дія форми та кнопка скасування
        $data['action'] = isset($this->request->get['module_id'])
            ? $this->url->link('extension/module/one_video', 'user_token=' . $this->session->data['user_token'] . '&module_id=' . $this->request->get['module_id'], true)
            : $this->url->link('extension/module/one_video', 'user_token=' . $this->session->data['user_token'], true);

        $data['cancel'] = $this->url->link('marketplace/extension', 'type=module&user_token=' . $this->session->data['user_token'], true);

        // Дані модуля
        $module_info = [];
        if (isset($this->request->get['module_id']) && $this->request->server['REQUEST_METHOD'] !== 'POST') {
            $module_info = $this->model_setting_module->getModule($this->request->get['module_id']);
        }

        $data['name'] = $this->request->post['name'] ?? ($module_info['name'] ?? '');
        $data['frame'] = $this->request->post['frame'] ?? ($module_info['frame'] ?? '');
        $data['status'] = $this->request->post['status'] ?? ($module_info['status'] ?? 1);

        // Завантажуємо шапку, колонку та футер
        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        // Вивід шаблону
        $this->response->setOutput($this->load->view('extension/module/one_video', $data));
    }

    protected function validate() {
        if (!$this->user->hasPermission('modify', 'extension/module/one_video')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        if (utf8_strlen($this->request->post['name'] ?? '') < 3 || utf8_strlen($this->request->post['name'] ?? '') > 64) {
            $this->error['name'] = $this->language->get('error_name');
        }

        if (empty($this->request->post['frame'])) {
            $this->error['frame'] = $this->language->get('error_frame');
        }

        return !$this->error;
    }
}
