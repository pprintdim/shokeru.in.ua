<?php
// Landing page for unconfirmed/declined WayForPay payments (ported from well,
// adapted for guest checkout): access by owner session OR payment token from
// the return link; "retry" re-renders the WayForPay form for the SAME order.
class ControllerCheckoutPaymentRetry extends Controller {
	private function isValidPaymentToken($order_info) {
		$token = (string)($this->request->get['payment_token'] ?? '');

		if (!$token || !$order_info) {
			return false;
		}

		$expected = hash_hmac('sha256', (int)$order_info['order_id'] . ':' . (int)$order_info['customer_id'], (string)$this->config->get('payment_wayforpay_secretkey'));

		return hash_equals($expected, $token);
	}

	public function index() {
		$this->load->language('checkout/payment_retry');

		$order_id = (int)($this->request->get['order_id'] ?? $this->session->data['order_id'] ?? 0);

		if (!$order_id) {
			$this->response->redirect($this->url->link('checkout/checkout', '', true));

			return;
		}

		$this->load->model('checkout/order');

		$order_info = $this->model_checkout_order->getOrder($order_id);

		// Owner session, or a valid signed token from the payment return link
		$is_owner = !empty($this->session->data['order_id']) && (int)$this->session->data['order_id'] == $order_id;

		if (!$order_info || (!$is_owner && !$this->isValidPaymentToken($order_info))) {
			return new Action('error/not_found');
		}

		$this->session->data['order_id'] = $order_id;

		if ($this->model_checkout_order->isPaymentPaid($order_id)) {
			$this->response->redirect($this->url->link('checkout/success', '', true));

			return;
		}

		$data['heading_title'] = $this->language->get('heading_title');
		$data['text_message'] = $this->language->get('text_message');
		$data['text_order_number'] = $this->language->get('text_order_number');
		$data['text_order_amount'] = $this->language->get('text_order_amount');
		$data['button_retry_payment'] = $this->language->get('button_retry_payment');
		$data['text_home'] = $this->language->get('text_home');
		$data['text_questions'] = $this->language->get('text_questions');
		$data['order_id'] = $order_id;
		$data['order_total'] = $this->currency->format($order_info['total'], $order_info['currency_code'], $order_info['currency_value']);

		// Re-render the WayForPay form for this same order
		$data['payment'] = $this->load->controller('extension/payment/wayforpay');

		$data['continue'] = $this->url->link('common/home');
		$data['store_email'] = $this->config->get('config_email');
		$data['store_telephone'] = $this->config->get('config_telephone');
		$data['store_telephone_link'] = 'tel:' . preg_replace('/\D+/', '', (string)$this->config->get('config_telephone'));

		$this->document->setTitle($data['heading_title']);

		$data['column_left'] = $this->load->controller('common/column_left');
		$data['column_right'] = $this->load->controller('common/column_right');
		$data['content_top'] = $this->load->controller('common/content_top');
		$data['content_bottom'] = $this->load->controller('common/content_bottom');
		$data['footer'] = $this->load->controller('common/footer');
		$data['header'] = $this->load->controller('common/header');

		$this->response->setOutput($this->load->view('checkout/payment_retry', $data));
	}
}
