<?php
class ControllerExtensionModuleTextrepeater extends Controller {
    private $error = array();

    public function index() {
        $this->load->language('extension/module/textrepeater');

        $this->document->setTitle($this->language->get('heading_title'));
        $this->document->addScript('view/javascript/summernote/summernote.min.js');
        $this->document->addScript('view/javascript/summernote/summernote-image-attributes.js');
        $this->document->addScript('view/javascript/summernote/opencart.js');
        $this->document->addStyle('view/javascript/summernote/summernote.css');

        $lang_code = $this->config->get('config_language');

        if (is_file(DIR_APPLICATION . 'view/javascript/summernote/lang/summernote-' . $lang_code . '.js')) {
            $this->document->addScript('view/javascript/summernote/lang/summernote-' . $lang_code . '.js');
        }

        $this->load->model('setting/setting');
        $this->load->model('localisation/language');

        if ($this->request->server['REQUEST_METHOD'] === 'POST' && $this->validate()) {
            $normalized = $this->normalizeModuleData($this->request->post['module_textrepeater']);
            $this->model_setting_setting->editSetting('module_textrepeater', array(
                'module_textrepeater'        => $normalized,
                'module_textrepeater_status' => isset($normalized['status']) ? (int)$normalized['status'] : 0
            ));

            $this->session->data['success'] = $this->language->get('text_success');

            $this->response->redirect($this->url->link(
                'marketplace/extension',
                'user_token=' . $this->session->data['user_token'] . '&type=module',
                true
            ));
        }

        $data['error_warning'] = isset($this->error['warning']) ? $this->error['warning'] : '';
        $data['error_name'] = isset($this->error['name']) ? $this->error['name'] : '';

        $data['breadcrumbs'] = array(
            array(
                'text' => $this->language->get('text_home'),
                'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
            ),
            array(
                'text' => $this->language->get('text_extension'),
                'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true)
            ),
            array(
                'text' => $this->language->get('heading_title'),
                'href' => $this->url->link('extension/module/textrepeater', 'user_token=' . $this->session->data['user_token'], true)
            )
        );

        $data['action'] = $this->url->link('extension/module/textrepeater', 'user_token=' . $this->session->data['user_token'], true);
        $data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true);
        $data['languages'] = $this->model_localisation_language->getLanguages();
        $data['user_token'] = $this->session->data['user_token'];

        $stored = $this->config->get('module_textrepeater');
        $module = isset($this->request->post['module_textrepeater']) ? $this->request->post['module_textrepeater'] : (is_array($stored) ? $stored : array());

        $data['module_textrepeater'] = $this->buildModuleData($module, $data['languages']);

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('extension/module/textrepeater', $data));
    }

    protected function validate() {
        if (!$this->user->hasPermission('modify', 'extension/module/textrepeater')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        if (isset($this->request->post['module_textrepeater']['name'])) {
            $name = trim($this->request->post['module_textrepeater']['name']);
        } else {
            $name = '';
        }

        if (utf8_strlen($name) < 3 || utf8_strlen($name) > 64) {
            $this->error['name'] = $this->language->get('error_name');
        }

        return !$this->error;
    }

    private function buildModuleData($module, $languages) {
        $data = array(
            'name' => isset($module['name']) ? $module['name'] : '',
            'status' => isset($module['status']) ? (int)$module['status'] : 1,
            'content' => array(),
            'items' => array()
        );

        foreach ($languages as $language) {
            $language_id = (int)$language['language_id'];

            $data['content'][$language_id] = array(
                'html' => isset($module['content'][$language_id]['html']) ? $module['content'][$language_id]['html'] : ''
            );
        }

        if (!empty($module['items']) && is_array($module['items'])) {
            foreach ($module['items'] as $index => $item) {
                $item_data = array(
                    'sort_order' => isset($item['sort_order']) ? (int)$item['sort_order'] : (int)$index,
                    'status'     => isset($item['status']) ? (int)$item['status'] : 1,
                    'btn_link'   => isset($item['btn_link']) ? $item['btn_link'] : ''
                );

                foreach ($languages as $language) {
                    $language_id = (int)$language['language_id'];

                    $item_data[$language_id] = array(
                        'title'    => isset($item[$language_id]['title']) ? $item[$language_id]['title'] : '',
                        'text'     => isset($item[$language_id]['text']) ? $item[$language_id]['text'] : '',
                        'btn_text' => isset($item[$language_id]['btn_text']) ? $item[$language_id]['btn_text'] : ''
                    );
                }

                $data['items'][] = $item_data;
            }
        }

        usort($data['items'], function($a, $b) {
            return (int)$a['sort_order'] - (int)$b['sort_order'];
        });

        return $data;
    }

    private function normalizeModuleData($module) {
        if (!is_array($module)) {
            return array();
        }

        if (isset($module['items']) && is_array($module['items'])) {
            $module['items'] = array_values($module['items']);
        } else {
            $module['items'] = array();
        }

        return $module;
    }
}
