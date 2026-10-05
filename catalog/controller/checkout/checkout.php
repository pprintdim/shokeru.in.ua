<?php
// Single-page checkout — the same flow as the other stores of the group (shoker.in.ua / secured.in.ua):
// the page only collects the data; catalog/view/js/checkout.js drives the stock endpoints
// (guest/save or payment_address/save + shipping_address/save -> GET shipping_method ->
// shipping_method/save -> GET payment_method -> payment_method/save -> GET confirm ->
// POST extension/payment/<code>/confirm -> success; WayForPay -> extension/payment/wayforpay/pay).
// Delivery = shipping extension `delivery` (novaposhta / ukrposhta / meest / courier / pickup).
class ControllerCheckoutCheckout extends Controller {
	// Which "receive as" options each carrier offers (one <option> per carrier x destination).
	private $carrier_destinations = array(
		'novaposhta' => array('branch', 'postomat', 'courier'),
		'ukrposhta'  => array('branch', 'courier'),
		'meest'      => array('branch', 'postomat', 'courier')
	);

	public function index() {
		// Nothing to order yet: the cart page shows its own empty state.
		if ((!$this->cart->hasProducts() && empty($this->session->data['vouchers'])) || (!$this->cart->hasStock() && !$this->config->get('config_stock_checkout'))) {
			$this->response->redirect($this->url->link('checkout/cart'));
		}

		// Validate minimum quantity requirements.
		$products = $this->cart->getProducts();

		foreach ($products as $product) {
			$product_total = 0;

			foreach ($products as $product_2) {
				if ($product_2['product_id'] == $product['product_id']) {
					$product_total += $product_2['quantity'];
				}
			}

			if ($product['minimum'] > $product_total) {
				$this->response->redirect($this->url->link('checkout/cart'));
			}
		}

		// Leftovers of the previous multi-step checkout must not leak into the new order.
		unset($this->session->data['np_delivery'], $this->session->data['recipient_data']);

		$this->load->language('checkout/checkout');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->document->addStyle('catalog/view/css/checkout.css?v=' . filemtime(DIR_APPLICATION . 'view/css/checkout.css'));
		$this->document->addScript('catalog/view/js/checkout.js?v=' . filemtime(DIR_APPLICATION . 'view/js/checkout.js'));

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/home')
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_cart'),
			'href' => $this->url->link('checkout/cart')
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('checkout/checkout', '', true)
		);

		$data['heading_title'] = $this->language->get('heading_title');

		// Own keys of the single-page checkout are prefixed co_ (stock entry_city / error_city /
		// text_payment_method are taken by the step templates).
		$keys = array(
			'co_text_eyebrow', 'co_text_lead', 'co_text_your_order', 'co_text_total',
			'co_text_step', 'co_text_contacts', 'co_text_delivery', 'co_text_payment',
			'co_entry_firstname', 'co_entry_lastname', 'co_entry_telephone', 'co_entry_email',
			'co_entry_shipping', 'co_entry_payment', 'co_entry_city', 'co_entry_branch', 'co_entry_postomat', 'co_entry_address', 'co_entry_comment',
			'co_text_pickup_info', 'co_text_pickup_prefix', 'co_text_pickup_city',
			'co_text_login_hint', 'co_text_login_link', 'co_text_logged_as', 'co_text_no_shipping', 'co_text_no_payment',
			'co_button_place_order', 'co_text_consent', 'co_text_back_to_cart', 'co_text_sending',
			'co_error_city', 'co_text_city_first', 'co_text_not_found', 'co_error_branch', 'co_error_postomat', 'co_error_address', 'co_error_firstname',
			'co_error_lastname', 'co_error_telephone', 'co_error_email', 'co_error_shipping', 'co_error_payment', 'co_error_failed', 'co_text_dest_reset'
		);

		foreach ($keys as $key) {
			$data[$key] = $this->language->get($key);
		}

		if (isset($this->session->data['error'])) {
			$data['error_warning'] = $this->session->data['error'];
			unset($this->session->data['error']);
		} else {
			$data['error_warning'] = '';
		}

		$data['logged'] = $this->customer->isLogged();

		$data['cart'] = $this->url->link('checkout/cart');
		$data['login'] = $this->url->link('account/login', '', true);

		$data['customer_firstname'] = $this->customer->getFirstName() ?: '';
		$data['customer_lastname'] = $this->customer->getLastName() ?: '';
		$data['customer_email'] = $this->customer->getEmail() ?: '';
		$data['customer_telephone'] = $this->customer->getTelephone() ?: '';

		// The stock address validators need a country/zone; the store defaults are used for every order.
		$data['country_id'] = (int)$this->config->get('config_country_id');
		$data['zone_id'] = (int)$this->config->get('config_zone_id');

		// A signed-in customer starts from their default address.
		$data['default_city'] = '';
		$data['default_address'] = '';

		if ($this->customer->isLogged()) {
			$this->load->model('account/address');

			$address = $this->model_account_address->getAddress($this->customer->getAddressId());

			if (!$address) {
				$addresses = $this->model_account_address->getAddresses();

				if ($addresses) {
					$address = reset($addresses);
				}
			}

			if ($address) {
				$data['default_city'] = isset($address['city']) ? $address['city'] : '';
				$data['default_address'] = isset($address['address_1']) ? $address['address_1'] : '';
			}
		}

		$this->summary($data);
		$this->shipping($data);
		$total = $data['summary_total_value'];
		$this->payment($data, $total);

		$data['success'] = $this->url->link('checkout/success');

		// Absolute base for checkout.js (SEO URLs make relative index.php?route= links unreliable).
		if ($this->request->server['HTTPS']) {
			$data['base'] = $this->config->get('config_ssl');
		} else {
			$data['base'] = $this->config->get('config_url');
		}

		$data['column_left'] = $this->load->controller('common/column_left');
		$data['column_right'] = $this->load->controller('common/column_right');
		$data['content_top'] = $this->load->controller('common/content_top');
		$data['content_bottom'] = $this->load->controller('common/content_bottom');
		$data['footer'] = $this->load->controller('common/footer');
		$data['header'] = $this->load->controller('common/header');

		$this->response->setOutput($this->load->view('checkout/checkout', $data));
	}

	// Order summary (right column) + GA4 begin_checkout payload.
	private function summary(&$data) {
		$this->load->model('tool/image');

		$data['summary_products'] = array();
		$ga4_items = array();

		foreach ($this->cart->getProducts() as $product) {
			$options = array();

			foreach ($product['option'] as $option) {
				$options[] = $option['name'] . ': ' . (utf8_strlen($option['value']) > 20 ? utf8_substr($option['value'], 0, 20) . '..' : $option['value']);
			}

			$unit = $this->tax->calculate($product['price'], $product['tax_class_id'], $this->config->get('config_tax'));

			$data['summary_products'][] = array(
				'name'     => $product['name'],
				'thumb'    => $this->model_tool_image->resize($product['image'] ? $product['image'] : 'placeholder.png', 96, 96),
				'href'     => $this->url->link('product/product', 'product_id=' . $product['product_id']),
				'options'  => $options,
				'quantity' => $product['quantity'],
				'price'    => $this->currency->format($unit, $this->session->data['currency']),
				'total'    => $this->currency->format($unit * $product['quantity'], $this->session->data['currency'])
			);

			$ga4_items[] = array(
				'item_id'   => (string)$product['product_id'],
				'item_name' => $product['name'],
				'price'     => (float)$this->currency->format($unit, $this->session->data['currency'], '', false),
				'quantity'  => (int)$product['quantity']
			);
		}

		$data['summary_vouchers'] = array();

		if (!empty($this->session->data['vouchers'])) {
			foreach ($this->session->data['vouchers'] as $voucher) {
				$data['summary_vouchers'][] = array(
					'description' => $voucher['description'],
					'amount'      => $this->currency->format($voucher['amount'], $this->session->data['currency'])
				);
			}
		}

		$totals = array();
		$taxes = $this->cart->getTaxes();
		$total = 0;

		// __call can not keep references, so the totals travel in an array.
		$total_data = array('totals' => &$totals, 'taxes' => &$taxes, 'total' => &$total);

		$this->load->model('setting/extension');

		$sort_order = array();
		$results = $this->model_setting_extension->getExtensions('total');

		foreach ($results as $key => $value) {
			$sort_order[$key] = $this->config->get('total_' . $value['code'] . '_sort_order');
		}

		array_multisort($sort_order, SORT_ASC, $results);

		foreach ($results as $result) {
			if ($this->config->get('total_' . $result['code'] . '_status')) {
				$this->load->model('extension/total/' . $result['code']);

				$this->{'model_extension_total_' . $result['code']}->getTotal($total_data);
			}
		}

		$sort_order = array();

		foreach ($totals as $key => $value) {
			$sort_order[$key] = $value['sort_order'];
		}

		array_multisort($sort_order, SORT_ASC, $totals);

		$data['summary_totals'] = array();

		foreach ($totals as $item) {
			$data['summary_totals'][] = array(
				'code'  => $item['code'],
				'title' => $item['title'],
				'text'  => $this->currency->format($item['value'], $this->session->data['currency'])
			);
		}

		$data['summary_total_value'] = $total;

		// GA4: picked up by catalog/view/js/ga4.js (dataLayer) and by gtag directly when it is present.
		$data['ga4_checkout'] = json_encode(array(
			'currency' => $this->session->data['currency'],
			'value'    => (float)$this->currency->format($total, $this->session->data['currency'], '', false),
			'items'    => $ga4_items
		), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP);
	}

	// Shipping: the delivery extension gives one quote per carrier (delivery.<code>); the layout has a
	// single <select>, so every carrier x "receive as" pair becomes one option.
	private function shipping(&$data) {
		$this->load->language('extension/shipping/delivery');

		$data['shipping_options'] = array();
		$data['shipping_required'] = $this->cart->hasShipping();

		if ($data['shipping_required'] && $this->config->get('shipping_delivery_status')) {
			$carriers = array('novaposhta', 'ukrposhta', 'meest', 'courier', 'pickup');

			// free delivery overrides the tariffs once the cart reaches the threshold of the shipping `free` module
			$free_active = $this->config->get('shipping_free_status') && $this->cart->getSubTotal() >= (float)$this->config->get('shipping_free_total');

			foreach ($carriers as $code) {
				// a carrier is switched off in its own card (Extensions -> Shipping)
				if ($this->config->get('shipping_delivery_' . $code . '_enabled') === '0') {
					continue;
				}

				$cost = $free_active ? 0 : (float)$this->config->get('shipping_delivery_' . $code . '_cost');

				if ($cost > 0) {
					$cost_text = $this->currency->format($this->tax->calculate($cost, $this->config->get('shipping_delivery_tax_class_id'), $this->config->get('config_tax')), $this->session->data['currency']);
				} elseif ($code == 'pickup') {
					$cost_text = $this->language->get('co_text_free');
				} else {
					$cost_text = $this->language->get('co_text_carrier_tariff');
				}

				if (isset($this->carrier_destinations[$code])) {
					foreach ($this->carrier_destinations[$code] as $destination) {
						$data['shipping_options'][] = array(
							'code'    => 'delivery.' . $code,
							'receive' => $destination,
							'title'   => $this->language->get('text_' . $code) . ' — ' . $this->language->get('co_text_opt_' . $destination),
							'cost'    => $cost_text,
							'label'   => $this->language->get('co_text_dest_' . $destination)
						);
					}
				} elseif ($code == 'courier') {
					$data['shipping_options'][] = array(
						'code'    => 'delivery.courier',
						'receive' => 'courier',
						'title'   => $this->language->get('co_text_courier_title'),
						'cost'    => $cost_text,
						'label'   => $this->language->get('co_text_dest_courier')
					);
				} else {
					$data['shipping_options'][] = array(
						'code'    => 'delivery.pickup',
						'receive' => 'pickup',
						'title'   => $this->language->get('co_text_pickup_title'),
						'cost'    => $cost_text,
						'label'   => ''
					);
				}
			}
		}

		// Label / error of the single destination field per "receive as" type (used by checkout.js).
		$data['destination_types'] = json_encode(array(
			'branch'   => array('label' => $this->language->get('co_entry_branch'), 'error' => $this->language->get('co_error_branch')),
			'postomat' => array('label' => $this->language->get('co_entry_postomat'), 'error' => $this->language->get('co_error_postomat')),
			'courier'  => array('label' => $this->language->get('co_entry_address'), 'error' => $this->language->get('co_error_address'))
		), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP);

		// Pickup point = the store address from the settings.
		// shokeru зберігає адресу/графік масивом по мовах (language_id => текст)
		$localized = function ($key) {
			$value = $this->config->get($key);

			if (is_array($value)) {
				$lid = (int)$this->config->get('config_language_id');
				$value = isset($value[$lid]) ? $value[$lid] : (string)reset($value);
			}

			return (string)$value;
		};

		$address = $localized('config_address');
		$open = $localized('config_open');

		$data['pickup_address'] = nl2br($address);
		$data['pickup_address_line'] = trim(preg_replace('/\s*[\r\n]+\s*/', ', ', $address));
		$data['pickup_hours'] = $open !== '' ? nl2br($open) : '';
	}

	// Payment: WayForPay (card) + cod / bank_transfer for paid carts, free_checkout for a zero total.
	// Each language file shares the text_title key, so the title is read right after loading it.
	private function payment(&$data, $total) {
		$data['payment_methods'] = array();

		if ($total > 0) {
			foreach (array('cod', 'bank_transfer') as $code) {
				if ($this->config->get('payment_' . $code . '_status')) {
					$minimum = (float)$this->config->get('payment_' . $code . '_total');

					if ($minimum > 0 && $minimum > $total) {
						continue;
					}

					$this->load->language('extension/payment/' . $code);

					$data['payment_methods'][] = array('code' => $code, 'title' => $this->language->get('text_title'));
				}
			}

			// card: after the order is created checkout.js hands the browser over to the payment page
			if ($this->config->get('payment_wayforpay_status') && $this->config->get('payment_wayforpay_merchant')) {
				$this->load->language('extension/payment/wayforpay');

				$title = $this->config->get('payment_wayforpay_title' . (int)$this->config->get('config_language_id'));

				array_unshift($data['payment_methods'], array('code' => 'wayforpay', 'title' => $title ?: $this->language->get('text_title')));
			}
		} elseif ($this->config->get('payment_free_checkout_status')) {
			$this->load->language('extension/payment/free_checkout');

			$data['payment_methods'][] = array('code' => 'free_checkout', 'title' => $this->language->get('text_title'));
		}
	}

	public function country() {
		$json = array();

		$this->load->model('localisation/country');

		$country_info = $this->model_localisation_country->getCountry($this->request->get['country_id']);

		if ($country_info) {
			$this->load->model('localisation/zone');

			$json = array(
				'country_id'        => $country_info['country_id'],
				'name'              => $country_info['name'],
				'iso_code_2'        => $country_info['iso_code_2'],
				'iso_code_3'        => $country_info['iso_code_3'],
				'address_format'    => $country_info['address_format'],
				'postcode_required' => $country_info['postcode_required'],
				'zone'              => $this->model_localisation_zone->getZonesByCountryId($this->request->get['country_id']),
				'status'            => $country_info['status']
			);
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
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

