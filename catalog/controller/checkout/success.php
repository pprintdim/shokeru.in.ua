<?php
class ControllerCheckoutSuccess extends Controller {
	public function index() {
		$this->load->language('checkout/success');

		$data['order_id'] = isset($this->session->data['order_id']) ? (int)$this->session->data['order_id'] : 0;
		$data['ga4_purchase'] = '';
		$data['order'] = array();
		$data['order_items'] = array();
		$data['order_totals'] = array();

		if (isset($this->session->data['order_id'])) {
			$this->load->model('checkout/order');

			$order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);

			if ($order_info) {
				$order_products = $this->model_checkout_order->getOrderProducts($this->session->data['order_id']);
				$order_totals = $this->model_checkout_order->getOrderTotals($this->session->data['order_id']);
				$items = array();
				$shipping = 0;
				$tax = 0;

				foreach ($order_products as $product) {
					$items[] = array(
						'item_id'   => (string)$product['product_id'],
						'item_name' => $product['name'],
						'price'     => (float)$this->currency->format($product['price'], $order_info['currency_code'], $order_info['currency_value'], false),
						'quantity'  => (int)$product['quantity']
					);
				}

				foreach ($order_totals as $total) {
					if ($total['code'] == 'shipping') {
						$shipping = (float)$this->currency->format($total['value'], $order_info['currency_code'], $order_info['currency_value'], false);
					}

					if ($total['code'] == 'tax') {
						$tax += (float)$this->currency->format($total['value'], $order_info['currency_code'], $order_info['currency_value'], false);
					}
				}

				// Контракт для common/success.twig: order, order_items, order_totals
				$method_name = function ($m) {
					return is_array($m) ? (isset($m['name']) ? $m['name'] : '') : (string)$m;
				};

				$address_parts = array();
				foreach (array('shipping_city', 'shipping_address_1', 'shipping_address_2') as $k) {
					if (!empty($order_info[$k])) {
						$address_parts[] = $order_info[$k];
					}
				}

				$data['order'] = array(
					'number'    => (int)$order_info['order_id'],
					'email'     => $order_info['email'],
					'telephone' => $order_info['telephone'],
					'name'      => trim($order_info['firstname'] . ' ' . $order_info['lastname']),
					'shipping'  => $method_name($order_info['shipping_method']),
					'payment'   => $method_name($order_info['payment_method']),
					'address'   => implode(', ', $address_parts),
					'total'     => $this->currency->format($order_info['total'], $order_info['currency_code'], $order_info['currency_value']),
					'date'      => date($this->language->get('date_format_short'), strtotime($order_info['date_added']))
				);

				foreach ($order_products as $product) {
					$options = array();

					foreach ($this->model_checkout_order->getOrderOptions($order_info['order_id'], $product['order_product_id']) as $option) {
						$options[] = $option['name'] . ': ' . $option['value'];
					}

					$data['order_items'][] = array(
						'name'     => $product['name'],
						'model'    => $product['model'],
						'quantity' => (int)$product['quantity'],
						'price'    => $this->currency->format($product['price'], $order_info['currency_code'], $order_info['currency_value']),
						'total'    => $this->currency->format($product['total'], $order_info['currency_code'], $order_info['currency_value']),
						'options'  => $options
					);
				}

				foreach ($order_totals as $total) {
					$data['order_totals'][] = array(
						'title' => $total['title'],
						'text'  => $this->currency->format($total['value'], $order_info['currency_code'], $order_info['currency_value'])
					);
				}

				$data['ga4_purchase'] = json_encode(array(
					'transaction_id' => (string)$order_info['order_id'],
					'value'          => (float)$this->currency->format($order_info['total'], $order_info['currency_code'], $order_info['currency_value'], false),
					'tax'            => $tax,
					'shipping'       => $shipping,
					'currency'       => $order_info['currency_code'],
					'items'          => $items
				), JSON_UNESCAPED_UNICODE);
			}

			$this->cart->clear();

			unset($this->session->data['shipping_method']);
			unset($this->session->data['shipping_methods']);
			unset($this->session->data['payment_method']);
			unset($this->session->data['payment_methods']);
			unset($this->session->data['guest']);
			unset($this->session->data['comment']);
			unset($this->session->data['order_id']);
			unset($this->session->data['coupon']);
			unset($this->session->data['reward']);
			unset($this->session->data['voucher']);
			unset($this->session->data['vouchers']);
			unset($this->session->data['totals']);
		}

		$this->document->setTitle($this->language->get('heading_title'));

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/home')
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_basket'),
			'href' => $this->url->link('checkout/cart')
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_checkout'),
			'href' => $this->url->link('checkout/checkout', '', true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_success'),
			'href' => $this->url->link('checkout/success')
		);

		if ($this->customer->isLogged()) {
			$data['text_message'] = sprintf($this->language->get('text_customer'), $this->url->link('account/account', '', true), $this->url->link('account/order', '', true), $this->url->link('account/download', '', true), $this->url->link('information/contact'));
		} else {
			$data['text_message'] = sprintf($this->language->get('text_guest'), $this->url->link('information/contact'));
		}

		$data['continue'] = $this->url->link('common/home');

		$data['column_left'] = $this->load->controller('common/column_left');
		$data['column_right'] = $this->load->controller('common/column_right');
		$data['content_top'] = $this->load->controller('common/content_top');
		$data['content_bottom'] = $this->load->controller('common/content_bottom');
		$data['footer'] = $this->load->controller('common/footer');
		$data['header'] = $this->load->controller('common/header');

		$this->response->setOutput($this->load->view('common/success', $data));
	}
}
