<?php
class ControllerExtensionModuleCustomScripts extends Controller {
    private $error = [];

    public function index() {
        $this->load->language('extension/module/custom_scripts');
        $this->document->setTitle($this->language->get('heading_title'));

        // Підключаємо модель для збереження
        $this->load->model('setting/setting');

        // Збереження
        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
            
            // Використовуємо ім'я модуля як префікс
            $this->model_setting_setting->editSetting('module_custom_scripts', [
                'module_custom_scripts_status' => (int)($this->request->post['module_custom_scripts_status'] ?? 0),
                'module_custom_scripts_items' => $this->request->post['module_custom_scripts_items'] ?? []
            ]);

            $this->session->data['success'] = $this->language->get('text_success');

            $this->response->redirect($this->url->link(
                'extension/module/custom_scripts',
                'user_token=' . $this->session->data['user_token'],
                true
            ));
        }

        $data['error_warning'] = $this->error['warning'] ?? '';

        // Дані для шаблону
        $data['module_custom_scripts_items'] = isset($this->request->post['module_custom_scripts_items'])
            ? $this->request->post['module_custom_scripts_items']
            : ($this->config->get('module_custom_scripts_items') ?? []);
        $data['status'] = (int)(isset($this->request->post['module_custom_scripts_status']) ? $this->request->post['module_custom_scripts_status'] : $this->config->get('module_custom_scripts_status'));

        $data['action'] = $this->url->link(
            'extension/module/custom_scripts',
            'user_token=' . $this->session->data['user_token'],
            true
        );
        $data['cancel'] = $this->url->link(
            'marketplace/extension',
            'user_token=' . $this->session->data['user_token'] . '&type=module',
            true
        );

        $data['user_token'] = $this->session->data['user_token'];

        // Хлібні крихти
        $data['breadcrumbs'] = [
            [
                'text' => $this->language->get('text_home'),
                'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
            ],
            [
                'text' => $this->language->get('text_extension'),
                'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true)
            ],
            [
                'text' => $this->language->get('heading_title'),
                'href' => $this->url->link('extension/module/custom_scripts', 'user_token=' . $this->session->data['user_token'], true)
            ]
        ];

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('extension/module/custom_scripts', $data));
    }

    public function install() {
        $this->load->model('user/user_group');

        $this->model_user_user_group->addPermission($this->user->getGroupId(), 'access', 'extension/module/custom_scripts');
        $this->model_user_user_group->addPermission($this->user->getGroupId(), 'modify', 'extension/module/custom_scripts');
    }

    public function uninstall() {
        $this->load->model('setting/setting');

        $this->model_setting_setting->deleteSetting('module_custom_scripts');
    }

    protected function validate() {
        if (!$this->user->hasPermission('modify', 'extension/module/custom_scripts')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }
        return !$this->error;
    }
}
