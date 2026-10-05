<?php
class ControllerProductSpecial extends Controller {
	public function index() {
		$this->load->language('product/special');

		$this->load->model('catalog/product');

		$this->load->model('tool/image');
		$this->load->model('catalog/filter');

		if (!empty($this->request->get['filter'])) {
			$filter = is_array($this->request->get['filter'])
				? implode(',', $this->request->get['filter'])
				: $this->request->get['filter'];
		} else {
			$filter = '';
		}

		$price_from = isset($this->request->get['price_from']) ? $this->request->get['price_from'] : ($this->request->get['price-from'] ?? '');
		$price_to = isset($this->request->get['price_to']) ? $this->request->get['price_to'] : ($this->request->get['price-to'] ?? '');

		if (isset($this->request->get['sort'])) {
			$sort = $this->request->get['sort'];
		} else {
			$sort = 'p.sort_order';
		}

		if (isset($this->request->get['order'])) {
			$order = $this->request->get['order'];
		} else {
			$order = 'ASC';
		}

		if (isset($this->request->get['page'])) {
			$page = (int)$this->request->get['page'];
		} else {
			$page = 1;
		}

		if (isset($this->request->get['limit']) && (int)$this->request->get['limit'] > 0) {
			$limit = (int)$this->request->get['limit'];
		} else {
			$limit = (int)$this->config->get('theme_' . $this->config->get('config_theme') . '_product_limit');
			if ($limit < 1) {
				$limit = 5;
			}
		}

		$this->document->setTitle($this->language->get('heading_title') . ($page > 1 ? ' — ' . sprintf($this->language->get('text_page_n'), $page) : ''));
		$this->document->setDescription($this->language->get('text_special_meta_description'));
		$data['heading_title'] = $this->language->get('heading_title');

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/home')
		);

		$url = '';

		if (isset($this->request->get['sort'])) {
			$url .= '&sort=' . $this->request->get['sort'];
		}

		if (isset($this->request->get['order'])) {
			$url .= '&order=' . $this->request->get['order'];
		}

		if (isset($this->request->get['page'])) {
			$url .= '&page=' . $this->request->get['page'];
		}

		if (isset($this->request->get['limit'])) {
			$url .= '&limit=' . $this->request->get['limit'];
		}

		if ($filter !== '') {
			$url .= '&filter=' . $filter;
		}

		if ($price_from !== '') {
			$url .= '&price_from=' . $price_from;
		}

		if ($price_to !== '') {
			$url .= '&price_to=' . $price_to;
		}

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('product/special', $url)
		);

		$data['text_compare'] = sprintf($this->language->get('text_compare'), (isset($this->session->data['compare']) ? count($this->session->data['compare']) : 0));

		$data['compare'] = $this->url->link('product/compare');

		$data['products'] = array();

		$filter_data = array(
			'filter_filter'     => $filter,
			'filter_price_from' => $price_from,
			'filter_price_to'   => $price_to,
			'sort'  => $sort,
			'order' => $order,
			'start' => ($page - 1) * $limit,
			'limit' => $limit
		);

		$product_total = $this->model_catalog_product->getTotalProductSpecials($filter_data);

		$results = $this->model_catalog_product->getProductSpecials($filter_data);
		$data['filter'] = $this->load->controller('extension/module/filter');

		$this->document->addStyle('https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css');
		$this->document->addScript('https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js');
		$data['text_recommended'] = $this->language->get('text_recommended');
		$data['recommended'] = array();
		$limit_rec = 10;
		$added_ids = array();
		$recently_viewed = $this->session->data['recently_viewed'] ?? array();

		$popular_products = $this->model_catalog_product->getProducts(array(
			'sort'  => 'p.viewed',
			'order' => 'DESC',
			'start' => 0,
			'limit' => 100
		));

		$rec_items = array();

		if ($recently_viewed) {
			$rv_ids = array_diff($recently_viewed, $added_ids);

			if ($rv_ids) {
				$rv_products = $this->model_catalog_product->getProducts(array(
					'filter_product_id' => $rv_ids,
					'start' => 0,
					'limit' => $limit_rec
				));

				foreach ($rv_products as $p) {
					if (!in_array($p['product_id'], $added_ids)) {
						$added_ids[] = $p['product_id'];
						$rec_items[] = $p;
					}
				}
			}
		}

		if (count($rec_items) < $limit_rec) {
			foreach ($popular_products as $p) {
				if (!in_array($p['product_id'], $added_ids)) {
					$added_ids[] = $p['product_id'];
					$rec_items[] = $p;
				}

				if (count($rec_items) >= $limit_rec) {
					break;
				}
			}
		}

		foreach ($rec_items as &$rec) {
			$rec['thumb'] = $rec['image']
				? $this->model_tool_image->resize(
					$rec['image'],
					$this->config->get('theme_' . $this->config->get('config_theme') . '_image_product_width'),
					$this->config->get('theme_' . $this->config->get('config_theme') . '_image_product_height')
				)
				: $this->model_tool_image->resize(
					'placeholder.png',
					$this->config->get('theme_' . $this->config->get('config_theme') . '_image_product_width'),
					$this->config->get('theme_' . $this->config->get('config_theme') . '_image_product_height')
				);

			$rec['href'] = $this->url->link('product/product', 'product_id=' . $rec['product_id']);
		}
		unset($rec);

		$data['recommended'] = array_slice($rec_items, 0, $limit_rec);

		foreach ($results as $result) {
			if ($result['image']) {
				$image = $this->model_tool_image->resize($result['image'], $this->config->get('theme_' . $this->config->get('config_theme') . '_image_product_width'), $this->config->get('theme_' . $this->config->get('config_theme') . '_image_product_height'));
			} else {
				$image = $this->model_tool_image->resize('placeholder.png', $this->config->get('theme_' . $this->config->get('config_theme') . '_image_product_width'), $this->config->get('theme_' . $this->config->get('config_theme') . '_image_product_height'));
			}

			if ($this->customer->isLogged() || !$this->config->get('config_customer_price')) {
				$price = $this->currency->format($this->tax->calculate($result['price'], $result['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
			} else {
				$price = false;
			}

			if (!is_null($result['special']) && (float)$result['special'] >= 0) {
				$special = $this->currency->format($this->tax->calculate($result['special'], $result['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
				$tax_price = (float)$result['special'];
			} else {
				$special = false;
				$tax_price = (float)$result['price'];
			}

			if ($this->config->get('config_tax')) {
				$tax = $this->currency->format($tax_price, $this->session->data['currency']);
			} else {
				$tax = false;
			}

			if ($this->config->get('config_review_status')) {
				$rating = (int)$result['rating'];
			} else {
				$rating = false;
			}

			$data['products'][] = array(
				'product_id'  => $result['product_id'],
				'thumb'       => $image,
				'name'        => $result['name'],
				'description' => utf8_substr(trim(strip_tags(html_entity_decode($result['description'], ENT_QUOTES, 'UTF-8'))), 0, $this->config->get('theme_' . $this->config->get('config_theme') . '_product_description_length')) . '..',
				'price'       => $price,
				'special'     => $special,
				'tax'         => $tax,
				'minimum'     => $result['minimum'] > 0 ? $result['minimum'] : 1,
				'rating'      => $result['rating'],
				'href'        => $this->url->link('product/product', 'product_id=' . $result['product_id'] . $url)
			);
		}

		$url = '';

		if ($filter !== '') {
			$url .= '&filter=' . $filter;
		}

		if ($price_from !== '') {
			$url .= '&price_from=' . $price_from;
		}

		if ($price_to !== '') {
			$url .= '&price_to=' . $price_to;
		}

		if (isset($this->request->get['limit'])) {
			$url .= '&limit=' . $this->request->get['limit'];
		}

		$data['sorts'] = array();

		$data['sorts'][] = array(
			'text'  => $this->language->get('text_default'),
			'value' => 'p.sort_order-ASC',
			'href'  => $this->url->link('product/special', 'sort=p.sort_order&order=ASC' . $url)
		);

		$data['sorts'][] = array(
			'text'  => $this->language->get('text_name_asc'),
			'value' => 'pd.name-ASC',
			'href'  => $this->url->link('product/special', 'sort=pd.name&order=ASC' . $url)
		);

		$data['sorts'][] = array(
			'text'  => $this->language->get('text_name_desc'),
			'value' => 'pd.name-DESC',
			'href'  => $this->url->link('product/special', 'sort=pd.name&order=DESC' . $url)
		);

		$data['sorts'][] = array(
			'text'  => $this->language->get('text_price_asc'),
			'value' => 'ps.price-ASC',
			'href'  => $this->url->link('product/special', 'sort=ps.price&order=ASC' . $url)
		);

		$data['sorts'][] = array(
			'text'  => $this->language->get('text_price_desc'),
			'value' => 'ps.price-DESC',
			'href'  => $this->url->link('product/special', 'sort=ps.price&order=DESC' . $url)
		);

		if ($this->config->get('config_review_status')) {
			$data['sorts'][] = array(
				'text'  => $this->language->get('text_rating_desc'),
				'value' => 'rating-DESC',
				'href'  => $this->url->link('product/special', 'sort=rating&order=DESC' . $url)
			);

			$data['sorts'][] = array(
				'text'  => $this->language->get('text_rating_asc'),
				'value' => 'rating-ASC',
				'href'  => $this->url->link('product/special', 'sort=rating&order=ASC' . $url)
			);
		}

		$data['sorts'][] = array(
				'text'  => $this->language->get('text_model_asc'),
				'value' => 'p.model-ASC',
				'href'  => $this->url->link('product/special', 'sort=p.model&order=ASC' . $url)
		);

		$data['sorts'][] = array(
			'text'  => $this->language->get('text_model_desc'),
			'value' => 'p.model-DESC',
			'href'  => $this->url->link('product/special', 'sort=p.model&order=DESC' . $url)
		);

		$url = '';

		if (isset($this->request->get['sort'])) {
			$url .= '&sort=' . $this->request->get['sort'];
		}

		if (isset($this->request->get['order'])) {
			$url .= '&order=' . $this->request->get['order'];
		}

		if ($filter !== '') {
			$url .= '&filter=' . $filter;
		}

		if ($price_from !== '') {
			$url .= '&price_from=' . $price_from;
		}

		if ($price_to !== '') {
			$url .= '&price_to=' . $price_to;
		}

		$data['limits'] = array();

		$limits = array_unique(array($this->config->get('theme_' . $this->config->get('config_theme') . '_product_limit'), 25, 50, 75, 100));

		sort($limits);

		foreach($limits as $value) {
			$data['limits'][] = array(
				'text'  => $value,
				'value' => $value,
				'href'  => $this->url->link('product/special', $url . '&limit=' . $value)
			);
		}

		$url = '';

		if (isset($this->request->get['sort'])) {
			$url .= '&sort=' . $this->request->get['sort'];
		}

		if (isset($this->request->get['order'])) {
			$url .= '&order=' . $this->request->get['order'];
		}

		if (isset($this->request->get['limit'])) {
			$url .= '&limit=' . $this->request->get['limit'];
		}

		if ($filter !== '') {
			$url .= '&filter=' . $filter;
		}

		if ($price_from !== '') {
			$url .= '&price_from=' . $price_from;
		}

		if ($price_to !== '') {
			$url .= '&price_to=' . $price_to;
		}

		$pagination = new Pagination();
		$pagination->total = $product_total;
		$pagination->page = $page;
		$pagination->limit = $limit;
		$pagination->url = $this->url->link('product/special', $url . '&page={page}');

		$data['pagination'] = $pagination->render();
		$data['current_page'] = $page;
		$data['total_pages'] = $limit ? (int)ceil($product_total / $limit) : 1;
		$data['pagination_pages'] = array();

		$pagination_url = $this->url->link('product/special', $url . '&page={page}');
		$pagination_url = str_replace(array('%7Bpage%7D', '%7bpage%7d'), '{page}', $pagination_url);
		$first_page_url = str_replace(array('/page-{page}', '&amp;page={page}', '?page={page}', '&page={page}'), '', $pagination_url);

		$tp = (int)$data['total_pages'];
		$show = array();
		$start = $page - 1;
		if ($start > $tp - 2) { $start = $tp - 2; }
		if ($start < 1) { $start = 1; }
		for ($i = $start; $i <= min($start + 2, $tp); $i++) { $show[$i] = true; }
		if ($tp >= 1) { $show[$tp] = true; }
		ksort($show);
		$prev = 0;
		foreach (array_keys($show) as $i) {
			if ($prev && ($i - $prev) > 1) {
				$data['pagination_pages'][] = array('text' => '...', 'href' => '', 'active' => false, 'ellipsis' => true);
			}
			$data['pagination_pages'][] = array(
				'text'     => $i,
				'href'     => ($i == 1) ? $first_page_url : str_replace('{page}', $i, $pagination_url),
				'active'   => ($i == $page),
				'ellipsis' => false
			);
			$prev = $i;
		}

		$data['next_page_url'] = ($page < $data['total_pages'])
			? str_replace('{page}', $page + 1, $pagination_url)
			: '';

		$data['results'] = sprintf($this->language->get('text_pagination'), ($product_total) ? (($page - 1) * $limit) + 1 : 0, ((($page - 1) * $limit) > ($product_total - $limit)) ? $product_total : ((($page - 1) * $limit) + $limit), $product_total, ceil($product_total / $limit));

		// http://googlewebmastercentral.blogspot.com/2011/09/pagination-with-relnext-and-relprev.html
		if ($page == 1) {
		    $this->document->addLink($this->url->link('product/special', '', true), 'canonical');
		} else {
		    $this->document->addLink($this->url->link('product/special', 'page='. $page , true), 'canonical');
		}		
		
		if ($page > 1) {
			$this->document->addLink($this->url->link('product/special', (($page - 2) ? '&page='. ($page - 1) : ''), true), 'prev');
		}

		if ($limit && ceil($product_total / $limit) > $page) {
		    $this->document->addLink($this->url->link('product/special', 'page='. ($page + 1), true), 'next');
		}

		$data['sort'] = $sort;
		$data['order'] = $order;
		$data['limit'] = $limit;

		$data['continue'] = $this->url->link('common/home');

		$data['column_left'] = $this->load->controller('common/column_left');
		$data['column_right'] = $this->load->controller('common/column_right');
		$data['content_top'] = $this->load->controller('common/content_top');
		$data['content_bottom'] = $this->load->controller('common/content_bottom');
		$data['footer'] = $this->load->controller('common/footer');
		$data['header'] = $this->load->controller('common/header');

		$this->response->setOutput($this->load->view('product/shop', $data));
	}
}
