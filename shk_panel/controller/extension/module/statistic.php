<?php
class ControllerExtensionModuleStatistic extends Controller {

    private $error = [];

    public function index() {

        $this->load->language('extension/module/statistic');
        $this->load->model('setting/module');
        $this->load->model('localisation/language');

        $this->document->setTitle($this->language->get('heading_title'));

        $module_id = (int)($this->request->get['module_id'] ?? 0);

        /* ================= SAVE ================= */
        if ($this->request->server['REQUEST_METHOD'] === 'POST' && $this->validate()) {

            $data = [
                'name'    => $this->request->post['name'] ?? '',
                'status'  => (int)($this->request->post['status'] ?? 0),
                'html_id' => (int)($this->request->post['html_id'] ?? 0),

                // 🔽 multilingual textareas
                'blocks'  => $this->request->post['blocks'] ?? []
            ];

            if (!$module_id) {
                $module_id = $this->model_setting_module->addModule('statistic', $data);
            } else {
                $this->model_setting_module->editModule($module_id, $data);
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

        /* ================= LOAD ================= */
        $module = $module_id
            ? $this->model_setting_module->getModule($module_id)
            : [];

        $data['name']    = $module['name']    ?? '';
        $data['status']  = $module['status']  ?? 0;
        $data['html_id'] = (int)($module['html_id'] ?? 0);
        $data['blocks']  = $module['blocks']  ?? [];

        /* ---------- languages ---------- */
        $data['languages'] = $this->model_localisation_language->getLanguages();

        /* ---------- html_repeater modules ---------- */
        $data['html_modules'] = [];

        foreach ($this->model_setting_module->getModulesByCode('html') as $m) {
            $data['html_modules'][] = [
                'module_id' => $m['module_id'],
                'name'      => $m['name']
            ];
        }

        /* ---------- common ---------- */
        $data['user_token'] = $this->session->data['user_token'];

        $data['action'] = $this->url->link(
            'extension/module/statistic',
            'user_token=' . $this->session->data['user_token'] . ($module_id ? '&module_id=' . $module_id : ''),
            true
        );

        $data['cancel'] = $this->url->link(
            'marketplace/extension',
            'user_token=' . $this->session->data['user_token'] . '&type=module',
            true
        );

        $data['header']      = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer']      = $this->load->controller('common/footer');

        $this->response->setOutput(
            $this->load->view('extension/module/statistic', $data)
        );
    }

    protected function validate(): bool {
        if (!$this->user->hasPermission('modify', 'extension/module/statistic')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }
        return !$this->error;
    }
}
