<?php
class ControllerAccountWishList extends Controller {
	private function getWishlistProductIds() {
		if ($this->customer->isLogged()) {
			$this->load->model('account/wishlist');

			$wishlist = $this->model_account_wishlist->getWishlist();
			$product_ids = array();

			foreach ($wishlist as $product) {
				$product_ids[] = (int)$product['product_id'];
			}

			return array_values(array_unique($product_ids));
		}

		if (!isset($this->session->data['wishlist']) || !is_array($this->session->data['wishlist'])) {
			return array();
		}

		return array_values(array_unique(array_map('intval', $this->session->data['wishlist'])));
	}

	private function getProductCardData($product_info) {
		if ($product_info['image']) {
			$image = $this->model_tool_image->resize($product_info['image'], $this->config->get('theme_' . $this->config->get('config_theme') . '_image_product_width'), $this->config->get('theme_' . $this->config->get('config_theme') . '_image_product_height'));
		} else {
			$image = $this->model_tool_image->resize('placeholder.png', $this->config->get('theme_' . $this->config->get('config_theme') . '_image_product_width'), $this->config->get('theme_' . $this->config->get('config_theme') . '_image_product_height'));
		}

		if ($this->customer->isLogged() || !$this->config->get('config_customer_price')) {
			$price = $this->currency->format($this->tax->calculate($product_info['price'], $product_info['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
		} else {
			$price = false;
		}

		if ((float)$product_info['special']) {
			$special = $this->currency->format($this->tax->calculate($product_info['special'], $product_info['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
		} else {
			$special = false;
		}

		return array(
			'product_id' => $product_info['product_id'],
			'thumb'      => $image,
			'name'       => $product_info['name'],
			'price'      => $price,
			'special'    => $special,
			'minimum'    => $product_info['minimum'] > 0 ? $product_info['minimum'] : 1,
			'href'       => $this->url->link('product/product', 'product_id=' . $product_info['product_id'])
		);
	}

	private function guest() {
		$this->load->language('account/wishlist');
		$this->load->model('catalog/product');
		$this->load->model('tool/image');

		$this->document->setTitle($this->language->get('heading_title'));

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/home')
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('account/wishlist')
		);

		$data['products'] = array();

		foreach ($this->getWishlistProductIds() as $product_id) {
			$product_info = $this->model_catalog_product->getProduct($product_id);

			if ($product_info) {
				$data['products'][] = $this->getProductCardData($product_info);
			}
		}

		$heading_title = $this->language->get('text_guest_heading');
		$button_catalog = $this->language->get('button_catalog');

		$data['heading_title'] = $heading_title != 'text_guest_heading' ? $heading_title : $this->language->get('heading_title');
		$data['text_empty'] = $this->language->get('text_empty');
		$data['button_cart'] = $this->language->get('button_cart');
		$data['button_view'] = $this->language->get('button_view');
		$data['button_catalog'] = $button_catalog != 'button_catalog' ? $button_catalog : 'Перейти в каталог';
		$data['shop'] = $this->url->link('product/shop');
		$data['content_top'] = $this->load->controller('common/content_top');
		$data['content_bottom'] = $this->load->controller('common/content_bottom');
		$data['footer'] = $this->load->controller('common/footer');
		$data['header'] = $this->load->controller('common/header');

		$this->response->setOutput($this->load->view('account/wishlist_guest', $data));
	}

	public function index() {
		if (!$this->customer->isLogged()) {
			return $this->guest();
		}

		$this->load->language('account/wishlist');

		$this->load->model('account/wishlist');

		$this->load->model('catalog/product');

		$this->load->model('tool/image');

		if (isset($this->request->get['remove'])) {
			// Remove Wishlist
			$this->model_account_wishlist->deleteWishlist($this->request->get['remove']);

			$this->session->data['success'] = $this->language->get('text_remove');

			$this->response->redirect($this->url->link('account/wishlist'));
		}

		$this->document->setTitle($this->language->get('heading_title'));

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/home')
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_account'),
			'href' => $this->url->link('account/account', '', true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('account/wishlist')
		);

		if (isset($this->session->data['success'])) {
			$data['success'] = $this->session->data['success'];

			unset($this->session->data['success']);
		} else {
			$data['success'] = '';
		}

		$data['products'] = array();

		$results = $this->model_account_wishlist->getWishlist();

		foreach ($results as $result) {
			$product_info = $this->model_catalog_product->getProduct($result['product_id']);

			if ($product_info) {
				if ($product_info['image']) {
					$image = $this->model_tool_image->resize($product_info['image'], $this->config->get('theme_' . $this->config->get('config_theme') . '_image_wishlist_width'), $this->config->get('theme_' . $this->config->get('config_theme') . '_image_wishlist_height'));
				} else {
					$image = false;
				}

				if ($product_info['quantity'] <= 0) {
					$stock = $product_info['stock_status'];
				} elseif ($this->config->get('config_stock_display')) {
					$stock = $product_info['quantity'];
				} else {
					$stock = $this->language->get('text_instock');
				}

				if ($this->customer->isLogged() || !$this->config->get('config_customer_price')) {
					$price = $this->currency->format($this->tax->calculate($product_info['price'], $product_info['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
				} else {
					$price = false;
				}

				if ((float)$product_info['special']) {
					$special = $this->currency->format($this->tax->calculate($product_info['special'], $product_info['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
				} else {
					$special = false;
				}

				$data['products'][] = array(
					'product_id' => $product_info['product_id'],
					'thumb'      => $image,
					'name'       => $product_info['name'],
					'model'      => $product_info['model'],
					'stock'      => $stock,
					'price'      => $price,
					'special'    => $special,
					'minimum'    => $product_info['minimum'] > 0 ? $product_info['minimum'] : 1,
					'href'       => $this->url->link('product/product', 'product_id=' . $product_info['product_id']),
					'remove'     => $this->url->link('account/wishlist', 'remove=' . $product_info['product_id'])
				);
			} else {
				$this->model_account_wishlist->deleteWishlist($result['product_id']);
			}
		}

		$data['continue'] = $this->url->link('account/account', '', true);

		// заголовок із кількістю: «Закладки (N)»
		$data['text_wishlist'] = sprintf($this->language->get('text_wishlist'), count($data['products']));

		$data['column_left'] = $this->load->controller('common/column_left');
		$data['column_right'] = $this->load->controller('common/column_right');
		$data['content_top'] = $this->load->controller('common/content_top');
		$data['content_bottom'] = $this->load->controller('common/content_bottom');
		$data['footer'] = $this->load->controller('common/footer');
		$data['header'] = $this->load->controller('common/header');

		$this->response->setOutput($this->load->view('account/wishlist', $data));
	}

	public function add() {
		$this->load->language('account/wishlist');

		$json = array();

		if (isset($this->request->post['product_id'])) {
			$product_id = $this->request->post['product_id'];
		} else {
			$product_id = 0;
		}

		$this->load->model('catalog/product');

		$product_info = $this->model_catalog_product->getProduct($product_id);

		if ($product_info) {
			if ($this->customer->isLogged()) {
				// Edit customers cart
				$this->load->model('account/wishlist');

				$this->model_account_wishlist->addWishlist($this->request->post['product_id']);

				$json['success'] = sprintf($this->language->get('text_success'), $this->url->link('product/product', 'product_id=' . (int)$this->request->post['product_id']), $product_info['name'], $this->url->link('account/wishlist'));

				$json['total'] = sprintf($this->language->get('text_wishlist'), $this->model_account_wishlist->getTotalWishlist());
			} else {
				if (!isset($this->session->data['wishlist'])) {
					$this->session->data['wishlist'] = array();
				}

				$this->session->data['wishlist'][] = $this->request->post['product_id'];

				$this->session->data['wishlist'] = array_unique($this->session->data['wishlist']);

				$json['success'] = sprintf($this->language->get('text_success'), $this->url->link('product/product', 'product_id=' . (int)$this->request->post['product_id']), $product_info['name'], $this->url->link('account/wishlist'));

				$json['total'] = sprintf($this->language->get('text_wishlist'), (isset($this->session->data['wishlist']) ? count($this->session->data['wishlist']) : 0));
			}
		}

		$json['product_ids'] = $this->getWishlistProductIds();
		$json['total_count'] = count($json['product_ids']);
		$json['active'] = in_array((int)$product_id, $json['product_ids']);
		$json['product_id'] = (int)$product_id;

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function remove() {
		$this->load->language('account/wishlist');

		$json = array();
		$product_id = isset($this->request->post['product_id']) ? (int)$this->request->post['product_id'] : 0;

		if ($product_id) {
			if ($this->customer->isLogged()) {
				$this->load->model('account/wishlist');
				$this->model_account_wishlist->deleteWishlist($product_id);
			} elseif (isset($this->session->data['wishlist']) && is_array($this->session->data['wishlist'])) {
				$this->session->data['wishlist'] = array_values(array_filter($this->session->data['wishlist'], function($wishlist_product_id) use ($product_id) {
					return (int)$wishlist_product_id !== $product_id;
				}));
			}

			$json['success'] = $this->language->get('text_remove');
		}

		$json['product_ids'] = $this->getWishlistProductIds();
		$json['total_count'] = count($json['product_ids']);
		$json['total'] = sprintf($this->language->get('text_wishlist'), $json['total_count']);
		$json['active'] = false;
		$json['product_id'] = $product_id;

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function clear() {
		$this->load->language('account/wishlist');

		if ($this->customer->isLogged()) {
			$this->load->model('account/wishlist');

			foreach ($this->model_account_wishlist->getWishlist() as $wishlist_product) {
				$this->model_account_wishlist->deleteWishlist($wishlist_product['product_id']);
			}
		} else {
			$this->session->data['wishlist'] = array();
		}

		$json = array(
			'success' => $this->language->get('text_remove'),
			'product_ids' => array(),
			'total_count' => 0,
			'total' => sprintf($this->language->get('text_wishlist'), 0)
		);

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}
}
