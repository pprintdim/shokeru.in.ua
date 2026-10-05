<?php
/**
 * shokeru theme: there is no login page. Sign-in is the passwordless popup
 * (common/user_popup, opened from the header on every page), so this route only
 *  - keeps the admin "login as customer" override (?token=…),
 *  - remembers where the customer was going (?redirect=… / session redirect set by the
 *    account/* controllers) so the popup can return them there after the code is verified,
 *  - and sends everything else to the home page with ?auth=login, which auto-opens the popup.
 */
class ControllerAccountLogin extends Controller {
	public function index() {
		$this->load->model('account/customer');

		// Login override for admin users
		if (!empty($this->request->get['token'])) {
			$this->customer->logout();
			$this->cart->clear();

			unset($this->session->data['order_id']);
			unset($this->session->data['payment_address']);
			unset($this->session->data['payment_method']);
			unset($this->session->data['payment_methods']);
			unset($this->session->data['shipping_address']);
			unset($this->session->data['shipping_method']);
			unset($this->session->data['shipping_methods']);
			unset($this->session->data['comment']);
			unset($this->session->data['coupon']);
			unset($this->session->data['reward']);
			unset($this->session->data['voucher']);
			unset($this->session->data['vouchers']);

			$customer_info = $this->model_account_customer->getCustomerByToken($this->request->get['token']);

			if ($customer_info && $this->customer->login($customer_info['email'], '', true)) {
				// Default Addresses
				$this->load->model('account/address');

				if ($this->config->get('config_tax_customer') == 'payment') {
					$this->session->data['payment_address'] = $this->model_account_address->getAddress($this->customer->getAddressId());
				}

				if ($this->config->get('config_tax_customer') == 'shipping') {
					$this->session->data['shipping_address'] = $this->model_account_address->getAddress($this->customer->getAddressId());
				}

				$this->response->redirect($this->url->link('account/account', '', true));
			}
		}

		if ($this->customer->isLogged()) {
			$this->response->redirect($this->url->link('account/account', '', true));
		}

		$this->rememberRedirect();

		$this->popupRedirect('login');
	}

	// ?redirect=<own address> (core links and old bookmarks) → session, read by common/user_popup::verifyCode
	protected function rememberRedirect() {
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
	}

	// ajax callers get JSON with the home address; browsers a permanent redirect
	protected function popupRedirect($mode) {
		$url = $this->url->link('common/home', 'auth=' . $mode);

		if (!empty($this->request->server['HTTP_X_REQUESTED_WITH']) && strtolower($this->request->server['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
			$this->response->addHeader('Content-Type: application/json');
			$this->response->setOutput(json_encode(array('redirect' => str_replace('&amp;', '&', $url))));

			return;
		}

		$this->response->redirect($url, 301);
	}
}
