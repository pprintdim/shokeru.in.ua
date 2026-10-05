<?php
/**
 * shokeru theme: there is no registration page. An account is created by the passwordless
 * popup (common/user_popup, type=register: name, email, phone, consent, then the code from
 * the email). This route sends everyone to the home page with ?auth=register, which
 * auto-opens the popup; customfield() stays for the stock ajax callers.
 */
class ControllerAccountRegister extends Controller {
	public function index() {
		if ($this->customer->isLogged()) {
			$this->response->redirect($this->url->link('account/account', '', true));
		}

		$redirect = '';

		if (isset($this->request->get['redirect'])) {
			$redirect = (string)$this->request->get['redirect'];
		} elseif (isset($this->request->post['redirect'])) {
			$redirect = (string)$this->request->post['redirect'];
		}

		$redirect = str_replace('&amp;', '&', trim($redirect));

		if ($redirect && ((strpos($redirect, (string)$this->config->get('config_url')) === 0) || (strpos($redirect, (string)$this->config->get('config_ssl')) === 0))) {
			$this->session->data['redirect'] = $redirect;
		}

		$url = $this->url->link('common/home', 'auth=register');

		if (!empty($this->request->server['HTTP_X_REQUESTED_WITH']) && strtolower($this->request->server['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
			$this->response->addHeader('Content-Type: application/json');
			$this->response->setOutput(json_encode(array('redirect' => str_replace('&amp;', '&', $url))));

			return;
		}

		$this->response->redirect($url, 301);
	}

	public function customfield() {
		$json = array();

		$this->load->model('account/custom_field');

		// Customer Group
		if (isset($this->request->get['customer_group_id']) && is_array($this->config->get('config_customer_group_display')) && in_array($this->request->get['customer_group_id'], $this->config->get('config_customer_group_display'))) {
			$customer_group_id = $this->request->get['customer_group_id'];
		} else {
			$customer_group_id = $this->config->get('config_customer_group_id');
		}

		$custom_fields = $this->model_account_custom_field->getCustomFields($customer_group_id);

		foreach ($custom_fields as $custom_field) {
			$json[] = array(
				'custom_field_id' => $custom_field['custom_field_id'],
				'required'        => $custom_field['required']
			);
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}
}
