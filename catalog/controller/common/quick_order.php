<?php
/**
 * shokeru theme: "Buy in one click" (port of the hydrophob.ua quick order, without oc_order).
 *
 * index() — the popup markup, rendered once per page from common/header ({{ quick_order }}).
 *           A logged-in customer gets the name / phone from the profile prefilled.
 * order() — JSON: validates product_id / name / phone (+ optional quantity), throttles
 *           repeated sends in the session (30 s) and e-mails the manager with the stock
 *           Mail (config_mail_engine = brevo). No order is created: the manager calls back.
 *
 * Response contract (shared with common/user_popup and the checkout): {success, message}
 * or {error: "<first message>", errors: {"<field>": "<message>"}}.
 *
 * Front-end: catalog/view/js/quick.js ([data-quick-order="<product_id>"]),
 * styles in css/features.css.
 */
class ControllerCommonQuickOrder extends Controller {
	const RESEND_AFTER = 30;

	public function index() {
		$this->load->language('common/quick_order');

		$data = array();

		foreach (array('text_eyebrow', 'heading_title', 'text_lead', 'text_quantity', 'text_close', 'text_note', 'entry_name', 'entry_phone', 'placeholder_name', 'placeholder_phone', 'button_submit', 'button_done', 'text_done_title', 'text_done', 'error_name', 'error_phone', 'error_network') as $key) {
			$data[$key] = $this->language->get($key);
		}

		$data['order_url'] = $this->url->link('common/quick_order/order', '', true);

		// a signed-in customer: the contacts from the profile, still editable in the popup
		if ($this->customer->isLogged()) {
			$data['customer_name'] = trim($this->customer->getFirstName() . ' ' . $this->customer->getLastName());
			$data['customer_phone'] = (string)$this->customer->getTelephone();
		} else {
			$data['customer_name'] = '';
			$data['customer_phone'] = '';
		}

		return $this->load->view('common/quick_order', $data);
	}

	public function order() {
		$this->load->language('common/quick_order');

		$json = array();

		$product_id = isset($this->request->post['product_id']) ? (int)$this->request->post['product_id'] : 0;
		$quantity = isset($this->request->post['quantity']) ? (int)$this->request->post['quantity'] : 1;
		$name = isset($this->request->post['name']) ? trim((string)$this->request->post['name']) : '';
		$phone = isset($this->request->post['phone']) ? trim((string)$this->request->post['phone']) : '';

		$name = preg_replace('/\s+/u', ' ', $name);

		if ((utf8_strlen($name) < 2) || (utf8_strlen($name) > 64)) {
			$json['error']['name'] = $this->language->get('error_name');
		}

		$phone = $this->normalizePhone($phone);

		if (!$this->validPhone($phone)) {
			$json['error']['phone'] = $this->language->get('error_phone');
		}

		$product_info = false;

		if (!$json) {
			$this->load->model('catalog/product');

			// getProduct() returns active products of the current store only
			$product_info = $this->model_catalog_product->getProduct($product_id);

			if (!$product_info) {
				$json['error']['warning'] = $this->language->get('error_product');
			} elseif ((int)$product_info['quantity'] <= 0) {
				// mirrors the disabled button: an out-of-stock product is refused on the back end too
				$json['error']['warning'] = $this->language->get('error_stock');
			}
		}

		// anti-spam: one request per RESEND_AFTER seconds per session
		if (!$json && isset($this->session->data['quick_order_sent']) && (time() - (int)$this->session->data['quick_order_sent']) < self::RESEND_AFTER) {
			$json['error']['warning'] = $this->language->get('error_too_often');
		}

		if (!$json && $product_info) {
			if ($quantity < 1) {
				$quantity = 1;
			}

			if ((int)$product_info['minimum'] > 0 && $quantity < (int)$product_info['minimum']) {
				$quantity = (int)$product_info['minimum'];
			}

			try {
				$this->mailManager($product_info, $quantity, $name, $phone);

				$this->session->data['quick_order_sent'] = time();

				$json['success'] = true;
				$json['message'] = $this->language->get('text_success');
			} catch (\Exception $e) {
				$this->log->write('Quick order mail error: ' . $e->getMessage());

				$json['error']['warning'] = $this->language->get('error_send');
			}
		}

		$this->response->addHeader('Content-Type: application/json; charset=utf-8');
		$this->response->setOutput(json_encode($this->formJson($json), JSON_UNESCAPED_UNICODE));
	}

	// plain-text e-mail to the manager: product (link, SKU, price, quantity), contacts, context
	private function mailManager($product_info, $quantity, $name, $phone) {
		$store = html_entity_decode($this->config->get('config_name'), ENT_QUOTES, 'UTF-8');
		$product_name = html_entity_decode($product_info['name'], ENT_QUOTES, 'UTF-8');

		$price_value = ((float)$product_info['special'] > 0) ? (float)$product_info['special'] : (float)$product_info['price'];
		$price_value = $this->tax->calculate($price_value, $product_info['tax_class_id'], $this->config->get('config_tax'));

		$price = $this->currency->format($price_value, $this->session->data['currency']);
		$total = $this->currency->format($price_value * $quantity, $this->session->data['currency']);

		$href = str_replace('&amp;', '&', $this->url->link('product/product', 'product_id=' . (int)$product_info['product_id']));

		if ($this->customer->isLogged()) {
			$account = $this->customer->getEmail() . ' (#' . (int)$this->customer->getId() . ')';
		} else {
			$account = $this->language->get('text_mail_guest');
		}

		$language = (string)$this->language->get('code');

		if (!$language) {
			$language = (string)$this->config->get('config_language');
		}

		// the page the popup was opened on: sent by quick.js, else the referer; own domain only
		$page = isset($this->request->post['page']) ? (string)$this->request->post['page'] : '';

		if (!$page && isset($this->request->server['HTTP_REFERER'])) {
			$page = (string)$this->request->server['HTTP_REFERER'];
		}

		$page = $this->ownUrl($page);

		$lines = array(
			sprintf($this->language->get('text_mail_title'), $store),
			'',
			$this->language->get('text_mail_product') . ' ' . $product_name,
			$this->language->get('text_mail_model') . ' ' . $product_info['model'],
			$this->language->get('text_mail_price') . ' ' . $price,
			$this->language->get('text_mail_quantity') . ' ' . $quantity,
			$this->language->get('text_mail_total') . ' ' . $total,
			$this->language->get('text_mail_link') . ' ' . $href,
			'',
			$this->language->get('text_mail_customer') . ' ' . $name,
			$this->language->get('text_mail_phone') . ' ' . $phone,
			$this->language->get('text_mail_account') . ' ' . $account,
			'',
			$this->language->get('text_mail_language') . ' ' . $language,
			$this->language->get('text_mail_page') . ' ' . ($page ? $page : '-'),
			$this->language->get('text_mail_date') . ' ' . date('Y-m-d H:i:s'),
			$this->language->get('text_mail_ip') . ' ' . $this->request->server['REMOTE_ADDR']
		);

		// the manager address: quick_order_email setting (quick_order.sql) or the store e-mail
		$to = (string)$this->config->get('config_quick_order_email');

		if ($to === '') {
			// попередня назва ключа (quick_order.sql) — лишаємо, щоб не губити вже введену адресу
			$to = (string)$this->config->get('quick_order_email');
		}

		if (!$to || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
			$to = $this->config->get('config_email');
		}

		$mail = new Mail($this->config->get('config_mail_engine'));
		$mail->parameter = $this->config->get('config_mail_parameter');
		$mail->smtp_hostname = $this->config->get('config_mail_smtp_hostname');
		$mail->smtp_username = $this->config->get('config_mail_smtp_username');
		$mail->smtp_password = html_entity_decode($this->config->get('config_mail_smtp_password'), ENT_QUOTES, 'UTF-8');
		$mail->smtp_port = $this->config->get('config_mail_smtp_port');
		$mail->smtp_timeout = $this->config->get('config_mail_smtp_timeout');

		$mail->setTo($to);
		$mail->setFrom($this->config->get('config_email'));
		$mail->setSender($store);
		$mail->setSubject(html_entity_decode(sprintf($this->language->get('text_mail_subject'), $product_name), ENT_QUOTES, 'UTF-8'));
		$mail->setText(implode("\n", $lines));
		$mail->send();
	}

	// +380XXXXXXXXX for Ukrainian numbers, "+digits" for the rest (system/library/phone.php when present)
	private function normalizePhone($phone) {
		if (is_file(DIR_SYSTEM . 'library/phone.php')) {
			$this->load->library('phone');

			if (class_exists('Phone') && method_exists('Phone', 'normalize')) {
				return Phone::normalize($phone);
			}
		}

		$value = trim((string)$phone);
		$plus = (strpos($value, '+') === 0);
		$digits = preg_replace('/\D+/', '', $value);

		if ($digits === '') {
			return '';
		}

		if (strlen($digits) == 11 && substr($digits, 0, 2) == '80') {
			$digits = substr($digits, 1);
		}

		if (strlen($digits) == 10 && $digits[0] == '0') {
			return '+38' . $digits;
		}

		if (strlen($digits) == 12 && substr($digits, 0, 2) == '38') {
			return '+' . $digits;
		}

		if (!$plus && strlen($digits) == 9) {
			return '+380' . $digits;
		}

		return '+' . $digits;
	}

	// E.164 length (9–15 digits); a Ukrainian number is exactly 12 digits with the country code
	private function validPhone($phone) {
		if (class_exists('Phone') && method_exists('Phone', 'valid')) {
			return Phone::valid($phone);
		}

		$digits = preg_replace('/\D+/', '', (string)$phone);
		$length = strlen($digits);

		if (substr($digits, 0, 3) == '380') {
			return $length == 12;
		}

		return $length >= 9 && $length <= 15;
	}

	// keep only an address of this shop (no control characters, capped length)
	private function ownUrl($url) {
		$url = trim(preg_replace('/[\x00-\x1F\x7F]+/', '', (string)$url));

		if ($url === '' || utf8_strlen($url) > 500) {
			return '';
		}

		foreach (array((string)$this->config->get('config_url'), (string)$this->config->get('config_ssl')) as $base) {
			if ($base && strpos($url, $base) === 0) {
				return $url;
			}
		}

		return '';
	}

	// {error: {...}} -> {error: "<first>", errors: {...}} (the ajax contract of the theme)
	private function formJson($json) {
		if (!empty($json['error']) && is_array($json['error'])) {
			$errors = $json['error'];

			$json['error'] = (string)reset($errors);
			$json['errors'] = $errors;
		}

		return $json;
	}
}
