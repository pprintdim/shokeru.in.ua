<?php
// WayForPay hosted-page payment (ported from well, trimmed for the shop):
// renders a signed auto-submit form, handles the browser return (response)
// and the server-to-server callback, marks orders paid/declined/cancelled.
class ControllerExtensionPaymentWayforpay extends Controller {
	public $codesCurrency = array(
		980 => 'UAH',
		840 => 'USD',
		978 => 'EUR'
	);

	private function getOrderIdFromReference($reference) {
		$parts = explode(WayForPay::ORDER_SEPARATOR, (string)$reference);

		return (int)($parts[0] ?? 0);
	}

	private function isOrderPaid($order_id) {
		if (!$order_id) {
			return false;
		}

		$this->load->model('checkout/order');

		return $this->model_checkout_order->getOrder($order_id) ? $this->model_checkout_order->isPaymentPaid($order_id) : false;
	}

	private function getPaymentToken($order_id) {
		$this->load->model('checkout/order');

		$order_info = $this->model_checkout_order->getOrder($order_id);

		if (!$order_info) {
			return '';
		}

		return hash_hmac('sha256', (int)$order_id . ':' . (int)$order_info['customer_id'], (string)$this->config->get('payment_wayforpay_secretkey'));
	}

	private function getPaymentReturnArgs($order_id) {
		$token = $this->getPaymentToken($order_id);

		return 'order_id=' . (int)$order_id . ($token ? '&payment_token=' . $token : '');
	}

	private function markOrderPaid($order_id, $message = 'WayForPay') {
		if (!$order_id) {
			return false;
		}

		$this->load->model('checkout/order');

		$order_info = $this->model_checkout_order->getOrder($order_id);

		if (!$order_info) {
			return false;
		}

		$paid_status = (int)$this->config->get('payment_wayforpay_order_status_id') ?: (int)$this->config->get('config_order_status_id');

		$this->model_checkout_order->markPaymentPaid($order_id, 'wayforpay', $message);

		if ((int)$order_info['order_status_id'] !== $paid_status) {
			$this->model_checkout_order->addOrderHistory($order_id, $paid_status, $message, true);
		}

		return true;
	}

	private function markOrderFailed($order_id, $transactionStatus = '', $reasonCode = '') {
		$order_id = (int)$order_id;

		if (!$order_id) {
			return;
		}

		$this->load->model('checkout/order');

		$order_info = $this->model_checkout_order->getOrder($order_id);

		$ts = (string)$transactionStatus;

		// повернення приходить уже по оплаченому замовленню — його пропускати
		// не можна, на відміну від решти невдалих статусів
		$refund = stripos($ts, 'Refunded') !== false || stripos($ts, 'Voided') !== false;

		if (!$order_info || (!$refund && $this->isOrderPaid($order_id))) {
			return;
		}

		// проміжні статуси нічого не вирішують — замовлення чекає далі
		foreach (array('Pending', 'InProcessing', 'WaitingAuthComplete', 'Created') as $wait) {
			if (stripos($ts, $wait) !== false) {
				return;
			}
		}

		if (stripos($ts, 'Declined') !== false) {
			$status = (int)$this->config->get('payment_wayforpay_decline_status_id');
			$message = 'WayForPay: відхилено (' . $ts . ($reasonCode !== '' ? ', reason ' . $reasonCode : '') . ')';
		} elseif (stripos($ts, 'Refunded') !== false || stripos($ts, 'Voided') !== false) {
			// гроші повернули вже після оплати — це теж кінцевий стан
			$status = (int)$this->config->get('payment_wayforpay_cancel_status_id');
			$message = 'WayForPay: повернення (' . $ts . ')';
		} else {
			$status = (int)$this->config->get('payment_wayforpay_cancel_status_id');
			$message = 'WayForPay: скасовано (' . ($ts !== '' ? $ts : 'Expired/Cancelled') . ')';
		}

		if (!$status || (int)$order_info['order_status_id'] === $status) {
			return;
		}

		$this->model_checkout_order->addOrderHistory($order_id, $status, $message, true);
	}

	public function index() {
		$fields = $this->generateFields();

		if (!$fields) {
			return '';
		}

		$data['prod_name'] = $fields['productName'];
		$data['prod_price'] = $fields['productPrice'];
		$data['prod_count'] = $fields['productCount'];

		unset($fields['productName'], $fields['productPrice'], $fields['productCount']);

		$data['action'] = WayForPay::URL;
		$data['fields'] = $fields;

		return $this->load->view('extension/payment/wayforpay', $data);
	}

	public function generateFields() {
		$w4p = new WayForPay();
		$w4p->setSecretKey($this->config->get('payment_wayforpay_secretkey'));

		$this->load->model('checkout/order');

		$order_id = !empty($this->session->data['order_id']) ? (int)$this->session->data['order_id'] : 0;
		$order = $order_id ? $this->model_checkout_order->getOrder($order_id) : false;

		if (!$order) {
			return array();
		}

		$currency = $this->codesCurrency[$order['currency_code']] ?? $order['currency_code'];

		$fields = array(
			'orderReference'                => $order['order_id'] . WayForPay::ORDER_SEPARATOR . time(),
			'merchantAccount'               => $this->config->get('payment_wayforpay_merchant'),
			'orderDate'                     => !empty($order['date_added']) ? strtotime($order['date_added']) : time(),
			'merchantAuthType'              => 'simpleSignature',
			'merchantDomainName'            => $this->request->server['HTTP_HOST'] ?? '',
			'merchantTransactionSecureType' => 'AUTO',
			'amount'                        => round($order['total'] * $order['currency_value'], 2),
			'currency'                      => $currency,
			'serviceUrl'                    => str_replace('&amp;', '&', $this->url->link('extension/payment/wayforpay/callback', '', true)),
			'returnUrl'                     => str_replace('&amp;', '&', $this->url->link('extension/payment/wayforpay/response', '', true)),
			'language'                      => 'AUTO'
		);

		$this->load->model('account/order');

		$names = array();
		$prices = array();
		$counts = array();

		foreach ($this->model_account_order->getOrderProducts($order_id) as $product) {
			$names[] = str_replace(array("'", '"', '&#39;', '&'), '', htmlspecialchars_decode((string)($product['name'] ?? '')));
			$prices[] = $product['price'] ?? 0;
			$counts[] = (int)($product['quantity'] ?? 0);
		}

		$fields['productName'] = $names;
		$fields['productPrice'] = $prices;
		$fields['productCount'] = $counts;

		$phone = str_replace(array('+', ' ', '(', ')', '-'), '', (string)($order['telephone'] ?? ''));

		if (strlen($phone) == 10) {
			$phone = '38' . $phone;
		} elseif (strlen($phone) == 11) {
			$phone = '3' . $phone;
		}

		$fields['clientFirstName'] = $order['payment_firstname'] ?: ($order['firstname'] ?? '');
		$fields['clientLastName'] = $order['payment_lastname'] ?: ($order['lastname'] ?? '');
		$fields['clientEmail'] = $order['email'] ?? '';
		$fields['clientPhone'] = $phone;
		$fields['clientCity'] = $order['payment_city'] ?? '';
		$fields['clientAddress'] = trim(($order['payment_address_1'] ?? '') . ' ' . ($order['payment_address_2'] ?? ''));
		$fields['clientCountry'] = $order['payment_iso_code_3'] ?? '';

		$fields['merchantSignature'] = $w4p->getRequestSignature($fields);

		return $fields;
	}

	/**
	 * Готова форма оплати окремою відповіддю.
	 *
	 * Чекаут оформлює замовлення ajax-ом і не перезавантажує сторінку, тому
	 * йому потрібна сама форма WayForPay: він вставляє її в документ і
	 * сабмітить, і браузер іде на платіжку. index() для цього не годиться —
	 * його викликає лише шар оплати всередині сторінки.
	 */
	public function pay() {
		$this->response->addHeader('Content-Type: text/html; charset=utf-8');
		$this->response->setOutput($this->index());
	}

	public function confirm() {
		if (($this->session->data['payment_method']['code'] ?? '') == 'wayforpay') {
			$this->load->model('checkout/order');

			$order_id = (int)$this->session->data['order_id'];
			$order_info = $this->model_checkout_order->getOrder($order_id);

			// До оплати замовлення НЕ отримує жодного статусу: воно лишається
			// «незавершеним» (order_status_id = 0) і не потрапляє в список
			// замовлень. Статус зʼявиться лише з відповіді платіжки:
			// оплачено -> payment_wayforpay_order_status_id, відхилено/скасовано
			// -> decline/cancel. Проміжний статус робив вигляд, ніби замовлення
			// вже прийняте, хоча гроші ще не проходили.
			if ($order_info && $order_info['order_status_id'] == 0) {
				$pending_status = (int)$this->config->get('payment_wayforpay_pending_status_id');

				if ($pending_status) {
					$this->model_checkout_order->addOrderHistory($order_id, $pending_status, 'WayForPay: очікує оплати', false);
				}
			}
		}
	}

	public function response() {
		$w4p = new WayForPay();
		$w4p->setSecretKey($this->config->get('payment_wayforpay_secretkey'));

		$post = $this->request->post;
		$order_id = $this->getOrderIdFromReference($post['orderReference'] ?? '');
		$paymentInfo = $w4p->isPaymentValid($post);

		if ($paymentInfo === true) {
			$this->markOrderPaid($order_id, 'WayForPay status: ' . ($post['transactionStatus'] ?? 'Approved'));

			if ($order_id) {
				$this->session->data['order_id'] = $order_id;
			}

			$this->response->redirect($this->url->link('checkout/success', $this->getPaymentReturnArgs($order_id), true));
		} elseif ($order_id && $this->isOrderPaid($order_id)) {
			$this->session->data['order_id'] = $order_id;

			$this->response->redirect($this->url->link('checkout/success', $this->getPaymentReturnArgs($order_id), true));
		} else {
			if (!$order_id && !empty($this->session->data['order_id'])) {
				$order_id = (int)$this->session->data['order_id'];
			}

			// Valid signature but not approved — cancelled/declined
			if ($paymentInfo === false) {
				$this->markOrderFailed($order_id, $post['transactionStatus'] ?? '', $post['reasonCode'] ?? '');
			}

			if ($order_id) {
				$this->session->data['order_id'] = $order_id;

				$this->response->redirect($this->url->link('checkout/payment_retry', $this->getPaymentReturnArgs($order_id), true));
			} else {
				$this->response->redirect($this->url->link('checkout/failure'));
			}
		}
	}

	public function callback() {
		$data = json_decode(file_get_contents('php://input'), true);

		if (!is_array($data)) {
			$data = array();
		}

		$w4p = new WayForPay();
		$w4p->setSecretKey($this->config->get('payment_wayforpay_secretkey'));

		$paymentInfo = $w4p->isPaymentValid($data);
		$order_id = $this->getOrderIdFromReference($data['orderReference'] ?? '');

		if ($paymentInfo === true) {
			$this->markOrderPaid($order_id, 'WayForPay status: ' . ($data['transactionStatus'] ?? 'Approved'));
		} elseif ($paymentInfo === false) {
			$this->markOrderFailed($order_id, $data['transactionStatus'] ?? '', $data['reasonCode'] ?? '');
		}

		echo $w4p->getAnswerToGateWay($data);
		exit();
	}
}

class WayForPay {
	const ORDER_APPROVED = 'Approved';
	const ORDER_HOLD_APPROVED = 'WaitingAuthComplete';

	const ORDER_SEPARATOR = '#';
	const SIGNATURE_SEPARATOR = ';';

	const URL = 'https://secure.wayforpay.com/pay';

	protected $secret_key = '';

	protected $keysForResponseSignature = array(
		'merchantAccount',
		'orderReference',
		'amount',
		'currency',
		'authCode',
		'cardPan',
		'transactionStatus',
		'reasonCode'
	);

	protected $keysForSignature = array(
		'merchantAccount',
		'merchantDomainName',
		'orderReference',
		'orderDate',
		'amount',
		'currency',
		'productName',
		'productCount',
		'productPrice'
	);

	public function getSignature($option, $keys) {
		$hash = array();

		foreach ($keys as $dataKey) {
			if (!isset($option[$dataKey])) {
				$option[$dataKey] = '';
			}

			if (is_array($option[$dataKey])) {
				foreach ($option[$dataKey] as $v) {
					$hash[] = $v;
				}
			} else {
				$hash[] = $option[$dataKey];
			}
		}

		return hash_hmac('md5', implode(self::SIGNATURE_SEPARATOR, $hash), $this->getSecretKey());
	}

	public function getRequestSignature($options) {
		return $this->getSignature($options, $this->keysForSignature);
	}

	public function getResponseSignature($options) {
		return $this->getSignature($options, $this->keysForResponseSignature);
	}

	public function getAnswerToGateWay($data) {
		$responseToGateway = array(
			'orderReference' => $data['orderReference'] ?? '',
			'status'         => 'accept',
			'time'           => time()
		);

		$responseToGateway['signature'] = hash_hmac('md5', implode(self::SIGNATURE_SEPARATOR, $responseToGateway), $this->getSecretKey());

		return json_encode($responseToGateway);
	}

	public function isPaymentValid($response) {
		if (!is_array($response) || empty($response)) {
			return 'Empty WayForPay response';
		}

		if (!isset($response['merchantSignature'])) {
			return $response['reason'] ?? 'Missing WayForPay signature';
		}

		if ($this->getResponseSignature($response) != $response['merchantSignature']) {
			return 'An error has occurred during payment';
		}

		return $response['transactionStatus'] == self::ORDER_APPROVED || $response['transactionStatus'] == self::ORDER_HOLD_APPROVED;
	}

	public function setSecretKey($key) {
		$this->secret_key = $key;
	}

	public function getSecretKey() {
		return $this->secret_key;
	}
}
