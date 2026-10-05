<?php
/**
 * shokeru theme: accounts have no passwords (sign-in by a one-time email code in the
 * common/user_popup popup), so there is nothing to recover. The stock "forgotten password"
 * address sends the visitor to the home page with the sign-in popup open.
 */
class ControllerAccountForgotten extends Controller {
	public function index() {
		if ($this->customer->isLogged()) {
			$this->response->redirect($this->url->link('account/account', '', true));
		}

		$this->response->redirect($this->url->link('common/home', 'auth=login'), 301);
	}
}
