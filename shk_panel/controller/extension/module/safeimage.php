<?php
class ControllerExtensionModuleSafeimage extends Controller {
    private $error = array();

    public function index() {
        $this->load->language('extension/module/safeimage');
        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('setting/module');
        $this->load->model('localisation/language');
        $this->load->model('tool/image');

        $module_id = isset($this->request->get['module_id']) ? (int)$this->request->get['module_id'] : 0;

        if ($this->request->server['REQUEST_METHOD'] === 'POST' && $this->validate()) {
            $post   = $this->request->post['module_safeimage'];
            $first  = !empty($post['content']) ? reset($post['content']) : array();
            $name   = !empty($first['name']) ? $first['name'] : 'Safe Image';
            $status = isset($post['status']) ? (int)$post['status'] : 1;

            $module_data = array(
                'name'    => $name,
                'status'  => $status,
                'setting' => $post
            );

            if ($module_id) {
                $this->model_setting_module->editModule($module_id, $module_data);
            } else {
                $this->model_setting_module->addModule('safeimage', $module_data);
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
                'href' => $this->url->link('extension/module/safeimage', $url_suffix . ($module_id ? '&module_id=' . $module_id : ''), true)
            )
        );

        $data['action']     = $this->url->link('extension/module/safeimage', $url_suffix . ($module_id ? '&module_id=' . $module_id : ''), true);
        $data['cancel']     = $this->url->link('marketplace/extension', $url_suffix . '&type=module', true);
        $data['languages']  = $this->model_localisation_language->getLanguages();
        $data['user_token'] = $this->session->data['user_token'];

        $stored = !empty($module_info['setting']) ? $module_info['setting'] : array();
        $module = isset($this->request->post['module_safeimage']) ? $this->request->post['module_safeimage'] : $stored;

        $data['module_safeimage'] = $this->buildModuleData($module, $data['languages']);

        $data['image_rows'] = array();

        foreach ($data['module_safeimage']['images'] as $image) {
            if ($image && is_file(DIR_IMAGE . $image)) {
                $thumb = $this->model_tool_image->resize($image, 100, 100);
            } else {
                $thumb = $this->model_tool_image->resize('no_image.png', 100, 100);
            }

            $data['image_rows'][] = array(
                'path'  => $image,
                'thumb' => $thumb
            );
        }

        if (!$data['image_rows']) {
            $data['image_rows'][] = array(
                'path'  => '',
                'thumb' => $this->model_tool_image->resize('placeholder.png', 100, 100)
            );
        }

        

        $data['header']      = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer']      = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('extension/module/safeimage', $data));
    }

    protected function validate() {
        if (!$this->user->hasPermission('modify', 'extension/module/safeimage')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        return !$this->error;
    }

    private function buildModuleData($module, $languages) {
        $data = array(
            'status'       => isset($module['status']) ? (int)$module['status'] : 1,
            'strip_status' => isset($module['strip_status']) ? (int)$module['strip_status'] : 1,
            'images'       => isset($module['images']) && is_array($module['images']) ? $module['images'] : array(),
            'content'      => array()
        );

        foreach ($languages as $language) {
            $language_id = (int)$language['language_id'];

            $data['content'][$language_id] = array(
                'name'         => isset($module['content'][$language_id]['name']) ? $module['content'][$language_id]['name'] : '',
                'title_accent' => isset($module['content'][$language_id]['title_accent']) ? $module['content'][$language_id]['title_accent'] : '',
                'description'  => isset($module['content'][$language_id]['description']) ? $module['content'][$language_id]['description'] : ''
            );
        }

        return $data;
    }
}
