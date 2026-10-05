<?php
class ControllerExtensionModuleBestSeller extends Controller {
    private $error = [];

    public function index() {
        $this->load->language('extension/module/bestseller');
        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('setting/module');
        $this->load->model('localisation/language');
        // Фільтри (якщо потрібен модуль filter)
        $data['filter'] = $this->load->controller('extension/module/filter');

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

            // зберігаємо i18n окремо
            $save['name_i18n'] = $save['name'] ?? [];

            // name для таблиці oc_module.name має бути STRING
            $admin_language_id = (int)$this->config->get('config_language_id');

            if (is_array($save['name_i18n']) && isset($save['name_i18n'][$admin_language_id])) {
                $save['name'] = (string)$save['name_i18n'][$admin_language_id];
            } elseif (is_array($save['name_i18n'])) {
                $first = reset($save['name_i18n']);
                $save['name'] = (string)($first !== false ? $first : '');
            } else {
                $save['name'] = (string)$save['name_i18n'];
            }

            if (!$module_id) {
                $this->model_setting_module->addModule('bestseller', $save);
            } else {
                $this->model_setting_module->editModule($module_id, $save);
            }

            $this->cache->delete('product');

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
        $data['error_width']   = $this->error['width'] ?? '';
        $data['error_height']  = $this->error['height'] ?? '';

        // ========= BREADCRUMBS =========
        $data['breadcrumbs'] = [];

        $data['breadcrumbs'][] = [
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        ];

        $data['breadcrumbs'][] = [
            'text' => $this->language->get('text_extension'),
            'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true)
        ];

        $data['breadcrumbs'][] = [
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link(
                'extension/module/bestseller',
                'user_token=' . $this->session->data['user_token'] . ($module_id ? '&module_id=' . $module_id : ''),
                true
            )
        ];

        $data['action'] = $this->url->link(
            'extension/module/bestseller',
            'user_token=' . $this->session->data['user_token'] . ($module_id ? '&module_id=' . $module_id : ''),
            true
        );

        $data['cancel'] = $this->url->link(
            'marketplace/extension',
            'user_token=' . $this->session->data['user_token'] . '&type=module',
            true
        );

        // ========= FORM VALUES =========

        // name_i18n в формі
        $name_i18n = $this->request->post['name']
            ?? ($module_info['name_i18n'] ?? []);

        $data['name'] = [];
        foreach ($data['languages'] as $language) {
            $lid = (int)$language['language_id'];
            $data['name'][$lid] = $name_i18n[$lid] ?? '';
        }

        // інші поля — через ?? щоб не було undefined
        $data['limit']  = $this->request->post['limit']  ?? ($module_info['limit']  ?? 5);
        $data['width']  = $this->request->post['width']  ?? ($module_info['width']  ?? 200);
        $data['height'] = $this->request->post['height'] ?? ($module_info['height'] ?? 200);
        $data['status'] = $this->request->post['status'] ?? ($module_info['status'] ?? 0);

        // ========= OUTPUT =========
        $data['header']      = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer']      = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('extension/module/bestseller', $data));
    }

    protected function validate() {
        if (!$this->user->hasPermission('modify', 'extension/module/bestseller')) {
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
            $this->error['warning'] = 'Name is missing';
        }

        if (empty($this->request->post['width'])) {
            $this->error['width'] = $this->language->get('error_width');
        }

        if (empty($this->request->post['height'])) {
            $this->error['height'] = $this->language->get('error_height');
        }

        return !$this->error;
    }
}
