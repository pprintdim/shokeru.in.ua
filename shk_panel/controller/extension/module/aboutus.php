<?php
class ControllerExtensionModuleAboutus extends Controller {
    private $error = array();

    public function index() {
        $this->load->language('extension/module/aboutus');

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
            $this->model_setting_setting->editSetting('module_aboutus', array(
                'module_aboutus'        => $this->request->post['module_aboutus'],
                'module_aboutus_status' => isset($this->request->post['module_aboutus']['status']) ? (int)$this->request->post['module_aboutus']['status'] : 0
            ));

            $this->session->data['success'] = $this->language->get('text_success');

            $this->response->redirect($this->url->link(
                'marketplace/extension',
                'user_token=' . $this->session->data['user_token'] . '&type=module',
                true
            ));
        }

        $data['error_warning'] = isset($this->error['warning']) ? $this->error['warning'] : '';
        $data['error_heading'] = isset($this->error['heading']) ? $this->error['heading'] : array();

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
                'href' => $this->url->link('extension/module/aboutus', 'user_token=' . $this->session->data['user_token'], true)
            )
        );

        $data['action'] = $this->url->link('extension/module/aboutus', 'user_token=' . $this->session->data['user_token'], true);
        $data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true);
        $data['languages'] = $this->model_localisation_language->getLanguages();

        $stored = $this->config->get('module_aboutus');
        $aboutus = isset($this->request->post['module_aboutus']) ? $this->request->post['module_aboutus'] : (is_array($stored) ? $stored : array());

        $data['module_aboutus'] = $this->buildModuleData($aboutus, $data['languages']);

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('extension/module/aboutus', $data));
    }

    protected function validate() {
        if (!$this->user->hasPermission('modify', 'extension/module/aboutus')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        if (isset($this->request->post['module_aboutus']['content']) && is_array($this->request->post['module_aboutus']['content'])) {
            foreach ($this->request->post['module_aboutus']['content'] as $language_id => $content) {
                $heading = trim(strip_tags(isset($content['content']) ? html_entity_decode($content['content'], ENT_QUOTES, 'UTF-8') : ''));

                if ($heading === '') {
                    $this->error['heading'][(int)$language_id] = $this->language->get('error_heading');
                }
            }
        }

        return !$this->error;
    }

    private function buildModuleData($aboutus, $languages) {
        $data = array(
            'status' => isset($aboutus['status']) ? (int)$aboutus['status'] : 1,
            'content' => array(),
            'stats' => array(),
            'cards' => array()
        );

        foreach ($languages as $language) {
            $language_id = (int)$language['language_id'];

            $data['content'][$language_id] = array(
                'content' => isset($aboutus['content'][$language_id]['content']) ? $aboutus['content'][$language_id]['content'] : ''
            );
        }

        for ($i = 0; $i < 4; $i++) {
            $data['stats'][$i] = array();

            foreach ($languages as $language) {
                $language_id = (int)$language['language_id'];

                $data['stats'][$i][$language_id] = array(
                    'value' => isset($aboutus['stats'][$i][$language_id]['value']) ? $aboutus['stats'][$i][$language_id]['value'] : '',
                    'suffix' => isset($aboutus['stats'][$i][$language_id]['suffix']) ? $aboutus['stats'][$i][$language_id]['suffix'] : '',
                    'label' => isset($aboutus['stats'][$i][$language_id]['label']) ? $aboutus['stats'][$i][$language_id]['label'] : ''
                );
            }
        }

        for ($i = 0; $i < 2; $i++) {
            $data['cards'][$i] = array(
                'link' => isset($aboutus['cards'][$i]['link']) ? $aboutus['cards'][$i]['link'] : ''
            );

            foreach ($languages as $language) {
                $language_id = (int)$language['language_id'];

                $data['cards'][$i][$language_id] = array(
                    'title' => isset($aboutus['cards'][$i][$language_id]['title']) ? $aboutus['cards'][$i][$language_id]['title'] : '',
                    'accent' => isset($aboutus['cards'][$i][$language_id]['accent']) ? $aboutus['cards'][$i][$language_id]['accent'] : ''
                );
            }
        }

        return $data;
    }
}
