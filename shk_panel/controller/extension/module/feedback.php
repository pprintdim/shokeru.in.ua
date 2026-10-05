<?php
class ControllerExtensionModuleFeedback extends Controller {
    private $error = [];

    public function index() {
        $this->load->language('extension/module/feedback');

        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('setting/module');
        $this->load->model('localisation/language');

        $data['languages'] = $this->model_localisation_language->getLanguages();

        $module_id = isset($this->request->get['module_id']) ? (int)$this->request->get['module_id'] : 0;

        // ========= LOAD MODULE =========
        $module_info = [];
        if ($module_id && $this->request->server['REQUEST_METHOD'] !== 'POST') {
            $module_info = (array)$this->model_setting_module->getModule($module_id);
        }

        // ========= SAVE (POST only) =========
        if ($this->request->server['REQUEST_METHOD'] === 'POST' && $this->validate()) {

            $save = (array)$this->request->post;

            // name і description мультимовні
            $save['name_i18n']        = $save['name'] ?? [];

            // name для таблиці oc_module.name має бути STRING (беремо поточну мову адміна)
            $admin_language_id = (int)$this->config->get('config_language_id');

            $save['name'] = isset($save['name_i18n'][$admin_language_id])
                ? (string)$save['name_i18n'][$admin_language_id]
                : (string)reset($save['name_i18n']);

            if (!$module_id) {
                $this->model_setting_module->addModule('feedback', $save);
            } else {
                $this->model_setting_module->editModule($module_id, $save);
            }

            $this->session->data['success'] = $this->language->get('text_success');

            $this->response->redirect(
                $this->url->link(
                    'marketplace/extension',
                    'user_token=' . $this->session->data['user_token'] . '&type=module',
                    true
                )
            );
        }

        // ========= ERRORS =========
        $data['error_warning'] = $this->error['warning'] ?? '';
        $data['error_name']    = $this->error['name'] ?? [];

        // ========= BREADCRUMBS =========
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
                'href' => $this->url->link(
                    'extension/module/feedback',
                    'user_token=' . $this->session->data['user_token'] . ($module_id ? '&module_id=' . $module_id : ''),
                    true
                )
            ]
        ];

        $data['action'] = $this->url->link(
            'extension/module/feedback',
            'user_token=' . $this->session->data['user_token'] . ($module_id ? '&module_id=' . $module_id : ''),
            true
        );

        $data['cancel'] = $this->url->link(
            'marketplace/extension',
            'user_token=' . $this->session->data['user_token'] . '&type=module',
            true
        );

        // ========= FORM VALUES =========
        $name_i18n = $this->request->post['name'] ?? ($module_info['name_i18n'] ?? []);


        $data['name'] = [];
        foreach ($data['languages'] as $language) {
            $lid = (int)$language['language_id'];
            $data['name'][$lid] = $name_i18n[$lid] ?? '';
        }

        // ========= OUTPUT =========
        $data['header']      = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer']      = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('extension/module/feedback', $data));
    }

    protected function validate() {
        if (!$this->user->hasPermission('modify', 'extension/module/feedback')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        // name — мультимовний масив
        if (isset($this->request->post['name']) && is_array($this->request->post['name'])) {
            foreach ($this->request->post['name'] as $language_id => $name) {
                if ((utf8_strlen((string)$name) < 3) || (utf8_strlen((string)$name) > 64)) {
                    $this->error['name'][(int)$language_id] = $this->language->get('error_name');
                }
            }
        } else {
            $this->error['warning'] = $this->language->get('error_name');
        }

        return !$this->error;
    }
}
