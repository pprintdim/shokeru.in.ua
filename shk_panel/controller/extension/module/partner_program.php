<?php
class ControllerExtensionModulePartnerProgram extends Controller {
    private $error = array();

    public function index() {
        $this->load->language('extension/module/partner_program');
        $this->load->model('setting/setting'); // для editSetting
        $this->load->model('localisation/language');
        $this->load->model('tool/image');

        $this->document->setTitle($this->language->get('heading_title'));

        // Збереження налаштувань
        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {

            $languages = $this->model_localisation_language->getLanguages();

            $buttons = $this->request->post['module_partner_program_buttons'] ?? [];

            // Перевіряємо, щоб для кожної мови масив існував
            foreach ($languages as $language) {
                $lang_id = $language['language_id'];

                $buttons['name'][$lang_id] = $buttons['name'][$lang_id] ?? [];
                $buttons['link'][$lang_id] = $buttons['link'][$lang_id] ?? [];
            }

            $dataToSave = [
                'module_partner_program_title'   => $this->request->post['module_partner_program_title'] ?? [],
                'module_partner_program_content' => $this->request->post['module_partner_program_content'] ?? [],
                'module_partner_program_buttons' => $buttons,
                'module_partner_program_image'   => $this->request->post['module_partner_program_image'] ?? '',
                'module_partner_program_status'  => $this->request->post['module_partner_program_status'] ?? 0
            ];

            $this->model_setting_setting->editSetting('module_partner_program', $dataToSave);

            $this->session->data['success'] = $this->language->get('text_success');

            $this->response->redirect($this->url->link(
                'extension/module/partner_program',
                'user_token=' . $this->session->data['user_token'],
                true
            ));
        }
        // Помилки
        $data['error_warning'] = $this->error['warning'] ?? '';

        // Breadcrumbs
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
                'href' => $this->url->link('extension/module/partner_program', 'user_token=' . $this->session->data['user_token'], true)
            ]
        ];

        $data['action'] = $this->url->link('extension/module/partner_program', 'user_token=' . $this->session->data['user_token'], true);
        $data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true);

        $data['user_token'] = $this->session->data['user_token'];

        // Отримання мов
        $data['languages'] = $this->model_localisation_language->getLanguages();

        // Завантаження мультимовних даних
        if ($this->request->server['REQUEST_METHOD'] == 'POST') {
            $data['title']   = $this->request->post['module_partner_program_title'] ?? [];
            $data['content'] = $this->request->post['module_partner_program_content'] ?? [];
            $data['buttons'] = $this->config->get('module_partner_program_buttons') ?? [];
        } else {
            $data['title']   = $this->config->get('module_partner_program_title') ?? [];
            $data['content'] = $this->config->get('module_partner_program_content') ?? [];
            $data['buttons'] = $this->config->get('module_partner_program_buttons') ?? [];
        }


        // Підготовка кнопок для адмінки
        $buttons = $this->config->get('module_partner_program_buttons') ?? [];

        foreach ($data['languages'] as $language) {
            $lang_id = $language['language_id'];

            $data['buttons']['name'][$lang_id] = $buttons['name'][$lang_id] ?? [''];
            $data['buttons']['link'][$lang_id] = $buttons['link'][$lang_id] ?? [''];
        }

        // Завантаження модуля (одно зображення)
        if ($this->request->server['REQUEST_METHOD'] == 'POST') {
            $data['image'] = $this->request->post['module_partner_program_image'] ?? '';
        } else {
            $data['image'] = $this->config->get('module_partner_program_image') ?? '';
        }

        // Thumb (як у OpenCart 3)
        if ($data['image'] && is_file(DIR_IMAGE . $data['image'])) {
            $data['thumb'] = $this->model_tool_image->resize($data['image'], 100, 100);
        } else {
            $data['thumb'] = $this->model_tool_image->resize('no_image.png', 100, 100);
        }

        if ($this->request->server['REQUEST_METHOD'] == 'POST') {
            $data['status'] = $this->request->post['module_partner_program_status'] ?? 0;
        } else {
            $data['status'] = $this->config->get('module_partner_program_status') ?? 0;
        }

        // OpenCart стандартні блоки
        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('extension/module/partner_program', $data));
    }

    protected function validate() {
        if (!$this->user->hasPermission('modify', 'extension/module/partner_program')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }
        return !$this->error;
    }

    public function install() {
        $this->load->model('setting/event');
        $this->model_setting_event->deleteEventByCode('partner_program');
        $this->model_setting_event->addEvent('partner_program', 'catalog/controller/account/logout/after', 'extension/module/partner_program/logout');
    }

    public function uninstall() {
        $this->load->model('setting/event');
        $this->model_setting_event->deleteEventByCode('partner_program');
    }
}