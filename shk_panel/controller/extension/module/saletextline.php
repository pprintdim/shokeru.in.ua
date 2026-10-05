<?php
class ControllerExtensionModuleSaletextline extends Controller {
    private $error = array();

    public function index() {
        $this->load->language('extension/module/saletextline');
        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('setting/module');
        $this->load->model('localisation/language');

        $module_id = isset($this->request->get['module_id']) ? (int)$this->request->get['module_id'] : 0;

        if ($this->request->server['REQUEST_METHOD'] === 'POST' && $this->validate()) {
            $post   = $this->request->post['module_saletextline'];
            $name   = !empty($post['name']) ? $post['name'] : 'Sale Text Line';
            $status = isset($post['status']) ? (int)$post['status'] : 1;

            $module_data = array(
                'name'    => $name,
                'status'  => $status,
                'setting' => $post
            );

            if ($module_id) {
                $this->model_setting_module->editModule($module_id, $module_data);
            } else {
                $this->model_setting_module->addModule('saletextline', $module_data);
            }

            $this->session->data['success'] = $this->language->get('text_success');
            $this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true));
        }

        $module_info = $module_id ? $this->model_setting_module->getModule($module_id) : array();

        $data['error_warning'] = isset($this->error['warning']) ? $this->error['warning'] : '';

        $url_suffix = 'user_token=' . $this->session->data['user_token'];

        $data['breadcrumbs'] = array(
            array(
                'text' => $this->language->get('text_home'),
                'href' => $this->url->link('common/dashboard', $url_suffix, true)
            ),
            array(
                'text' => $this->language->get('text_extension'),
                'href' => $this->url->link('marketplace/extension', $url_suffix . '&type=module', true)
            ),
            array(
                'text' => $this->language->get('heading_title'),
                'href' => $this->url->link('extension/module/saletextline', $url_suffix . ($module_id ? '&module_id=' . $module_id : ''), true)
            )
        );

        $data['action']    = $this->url->link('extension/module/saletextline', $url_suffix . ($module_id ? '&module_id=' . $module_id : ''), true);
        $data['cancel']    = $this->url->link('marketplace/extension', $url_suffix . '&type=module', true);
        $data['languages'] = $this->model_localisation_language->getLanguages();

        $stored = !empty($module_info['setting']) ? $module_info['setting'] : array();
        $module = isset($this->request->post['module_saletextline']) ? $this->request->post['module_saletextline'] : $stored;

        $data['module_saletextline'] = $this->buildModuleData($module, $data['languages']);

        $data['header']      = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer']      = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('extension/module/saletextline', $data));
    }

    protected function validate() {
        if (!$this->user->hasPermission('modify', 'extension/module/saletextline')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        return !$this->error;
    }

    private function buildModuleData($module, $languages) {
        $data = array(
            'name'    => isset($module['name']) ? $module['name'] : '',
            'status'  => isset($module['status']) ? (int)$module['status'] : 1,
            'content' => array()
        );

        foreach ($languages as $language) {
            $language_id = (int)$language['language_id'];

            $data['content'][$language_id] = array(
                'items' => isset($module['content'][$language_id]['items']) ? $module['content'][$language_id]['items'] : ''
            );
        }

        return $data;
    }
}
