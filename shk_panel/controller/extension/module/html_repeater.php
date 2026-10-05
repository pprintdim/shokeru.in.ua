<?php
class ControllerExtensionModuleHtmlRepeater extends Controller {

    private $error = [];

    /* ===============================
       INSTALL / UNINSTALL
    =============================== */

    public function install() {
        $this->db->query("
            CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "html_repeater` (
              `row_id` INT NOT NULL AUTO_INCREMENT,
              `module_id` INT NOT NULL,
              `item_id` INT NOT NULL,
              `language_id` INT NOT NULL,

              `title` VARCHAR(255) NOT NULL,
              `content` MEDIUMTEXT NOT NULL,

              `image` VARCHAR(255) NOT NULL,
              `mob_image` VARCHAR(255) NOT NULL,
              `link` VARCHAR(255) NOT NULL DEFAULT '',

              `sort_order` INT NOT NULL DEFAULT 0,
              `status` TINYINT(1) NOT NULL DEFAULT 1,

              PRIMARY KEY (`row_id`),
              UNIQUE KEY `u_item_lang` (`module_id`,`item_id`,`language_id`),
              KEY `k_module` (`module_id`),
              KEY `k_sort` (`module_id`,`sort_order`)
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8;
        ");
    }

    public function uninstall() {
        $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "html_repeater`");
    }

    /* ===============================
       MAIN
    =============================== */

    public function index() {

        /* ---------- LOAD ---------- */
        $this->load->language('extension/module/html_repeater');
        $this->load->model('setting/module');
        $this->load->model('extension/module/html_repeater');
        $this->load->model('localisation/language');
        $this->load->model('tool/image');

        $this->document->setTitle($this->language->get('heading_title'));


        $lang_code = $this->config->get('config_language');
        if (is_file(DIR_APPLICATION . 'view/javascript/summernote/lang/summernote-' . $lang_code . '.js')) {
            $this->document->addScript('view/javascript/summernote/lang/summernote-' . $lang_code . '.js');
        }

        /* ---------- MODULE ID ---------- */

        $module_id = isset($this->request->get['module_id'])
            ? (int)$this->request->get['module_id']
            : 0;

        /* ===============================
           SAVE
        =============================== */

        if ($this->request->server['REQUEST_METHOD'] === 'POST' && $this->validate()) {

            $module_data = [
                'name'   => $this->request->post['name'] ?? '',
                'status' => (int)($this->request->post['status'] ?? 0),
            ];

            // 1️⃣ SAVE MODULE FIRST (GET module_id)
            if (!$module_id) {
                $module_id = (int)$this->model_setting_module->addModule('html_repeater', $module_data);
            } else {
                $this->model_setting_module->editModule($module_id, $module_data);
            }

            // 2️⃣ SAVE ITEMS (module_id GUARANTEED)
            $items = $this->request->post['item'] ?? [];

            if ($module_id > 0) {
                $this->model_extension_module_html_repeater->saveItems(
                    (int)$module_id,
                    $items
                );
            }

            $this->session->data['success'] = $this->language->get('text_success');

            // redirect to module list
            $this->response->redirect(
                $this->url->link(
                    'marketplace/extension',
                    'user_token=' . $this->session->data['user_token'] . '&type=module',
                    true
                )
            );
        }

        /* ===============================
           LOAD DATA FOR FORM
        =============================== */

        // module meta
        if ($module_id) {
            $module_info = $this->model_setting_module->getModule($module_id);
        } else {
            $module_info = [];
        }

        $data['name']   = $this->request->post['name']   ?? ($module_info['name']   ?? '');
        $data['status'] = $this->request->post['status'] ?? ($module_info['status'] ?? 0);

        $data['module_id']  = $module_id;
        $data['user_token'] = $this->session->data['user_token'];

        $data['action'] = $this->url->link(
            'extension/module/html_repeater',
            'user_token=' . $this->session->data['user_token'] . ($module_id ? '&module_id=' . $module_id : ''),
            true
        );

        $data['cancel'] = $this->url->link(
            'marketplace/extension',
            'user_token=' . $this->session->data['user_token'] . '&type=module',
            true
        );

        /* ---------- LANGUAGES ---------- */
        $data['languages'] = $this->model_localisation_language->getLanguages();

        /* ---------- ITEMS ---------- */
        $items = $this->model_extension_module_html_repeater->getItems($module_id);
        $data['items'] = [];

        foreach ($items as $item_id => $item) {

            $image = $item['meta']['image'] ?? '';
            $mob   = $item['meta']['mob_image'] ?? '';

            $thumb = ($image && is_file(DIR_IMAGE . $image))
                ? $this->model_tool_image->resize($image, 100, 100)
                : $this->model_tool_image->resize('no_image.png', 100, 100);

            $mob_thumb = ($mob && is_file(DIR_IMAGE . $mob))
                ? $this->model_tool_image->resize($mob, 100, 100)
                : $this->model_tool_image->resize('no_image.png', 100, 100);

            $data['items'][$item_id] = $item;
            $data['items'][$item_id]['meta']['thumb']     = $thumb;
            $data['items'][$item_id]['meta']['mob_thumb'] = $mob_thumb;
        }

        $data['placeholder'] = $this->model_tool_image->resize('no_image.png', 100, 100);

        /* ---------- MESSAGES ---------- */
        $data['error_warning'] = $this->error['warning'] ?? '';
        $data['success'] = $this->session->data['success'] ?? '';
        unset($this->session->data['success']);

        /* ---------- OUTPUT ---------- */
        $data['header']      = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer']      = $this->load->controller('common/footer');

        $this->response->setOutput(
            $this->load->view('extension/module/html_repeater', $data)
        );
    }

    /* ===============================
       VALIDATE
    =============================== */

    protected function validate(): bool {
        if (!$this->user->hasPermission('modify', 'extension/module/html_repeater')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }
        return !$this->error;
    }
}
