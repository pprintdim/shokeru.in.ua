<?php
class ControllerExtensionModuleFaq extends Controller {
    private $error = array();

    public function index() {
        $this->load->language('extension/module/faq');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->document->addScript('view/javascript/summernote/summernote.min.js');
        $this->document->addScript('view/javascript/summernote/summernote-image-attributes.js');
        $this->document->addScript('view/javascript/summernote/opencart.js');
        $this->document->addStyle('view/javascript/summernote/summernote.css');

        $lang_code = $this->config->get('config_language');

        if (is_file(DIR_APPLICATION . 'view/javascript/summernote/lang/summernote-' . $lang_code . '.js')) {
            $this->document->addScript('view/javascript/summernote/lang/summernote-' . $lang_code . '.js');
        }

        $this->load->model('setting/module');
        $this->load->model('localisation/language');

        $module_id = isset($this->request->get['module_id']) ? (int)$this->request->get['module_id'] : 0;
        $module_info = array();

        if ($module_id && $this->request->server['REQUEST_METHOD'] !== 'POST') {
            $module_info = (array)$this->model_setting_module->getModule($module_id);
        }

        if ($this->request->server['REQUEST_METHOD'] === 'POST' && $this->validate()) {
            $save = array(
                'name' => isset($this->request->post['name']) ? $this->request->post['name'] : '',
                'status' => isset($this->request->post['status']) ? (int)$this->request->post['status'] : 0,
                'heading' => isset($this->request->post['heading']) ? $this->request->post['heading'] : array(),
                'heading_accent' => isset($this->request->post['heading_accent']) ? $this->request->post['heading_accent'] : array(),
                'items' => isset($this->request->post['items']) ? array_values($this->request->post['items']) : array()
            );

            if (!$module_id) {
                $this->model_setting_module->addModule('faq', $save);
            } else {
                $this->model_setting_module->editModule($module_id, $save);
            }

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
                'href' => $this->url->link('extension/module/faq', 'user_token=' . $this->session->data['user_token'] . ($module_id ? '&module_id=' . $module_id : ''), true)
            )
        );

        $data['action'] = $this->url->link('extension/module/faq', 'user_token=' . $this->session->data['user_token'] . ($module_id ? '&module_id=' . $module_id : ''), true);
        $data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true);
        $data['languages'] = $this->model_localisation_language->getLanguages();
        $data['user_token'] = $this->session->data['user_token'];

        $data['name'] = isset($this->request->post['name']) ? $this->request->post['name'] : (isset($module_info['name']) ? $module_info['name'] : '');
        $data['status'] = isset($this->request->post['status']) ? (int)$this->request->post['status'] : (isset($module_info['status']) ? (int)$module_info['status'] : 1);
        $data['heading'] = isset($this->request->post['heading']) ? $this->request->post['heading'] : (isset($module_info['heading']) ? $module_info['heading'] : array());
        $data['heading_accent'] = isset($this->request->post['heading_accent']) ? $this->request->post['heading_accent'] : (isset($module_info['heading_accent']) ? $module_info['heading_accent'] : array());
        $data['items'] = isset($this->request->post['items']) ? $this->request->post['items'] : (isset($module_info['items']) && is_array($module_info['items']) ? $module_info['items'] : array());

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('extension/module/faq', $data));
    }

    protected function validate() {
        if (!$this->user->hasPermission('modify', 'extension/module/faq')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        $name = isset($this->request->post['name']) ? trim($this->request->post['name']) : '';

        if (utf8_strlen($name) < 3 || utf8_strlen($name) > 64) {
            $this->error['name'] = $this->language->get('error_name');
        }

        return !$this->error;
    }
}
