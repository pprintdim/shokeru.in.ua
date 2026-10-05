<?php
class ControllerProductLatest extends Controller {
    public function index() {
        $this->load->language('product/category');

        $this->load->model('catalog/product');
        $this->load->model('tool/image');

        $filter_category_id = $this->request->get['category_id'] ?? 0;
        $filter_filter = '';
        if (!empty($this->request->get['filter'])) {
            $filter_filter = is_array($this->request->get['filter'])
                ? implode(',', $this->request->get['filter'])
                : $this->request->get['filter'];
        }

        $price_from = isset($this->request->get['price_from']) ? $this->request->get['price_from'] : ($this->request->get['price-from'] ?? '');
        $price_to = isset($this->request->get['price_to']) ? $this->request->get['price_to'] : ($this->request->get['price-to'] ?? '');
        $filter_option      = $this->request->get['option'] ?? [];
        $filter_attribute   = $this->request->get['attribute'] ?? [];



        // ======================
        // Параметри пагінації / сортування
        // ======================
        $sort  = $this->request->get['sort'] ?? 'p.date_added';
        $order = $this->request->get['order'] ?? 'DESC';
        $page  = isset($this->request->get['page']) ? (int)$this->request->get['page'] : 1;
        $limit = isset($this->request->get['limit']) && (int)$this->request->get['limit'] > 0
            ? (int)$this->request->get['limit']
            : 8;


        $url = '';

        if ($filter_filter !== '') $url .= '&filter=' . $filter_filter;
        if ($price_from !== '') $url .= '&price_from=' . $price_from;
        if ($price_to !== '') $url .= '&price_to=' . $price_to;

        if (isset($this->request->get['limit'])) {
            $url .= '&limit=' . $this->request->get['limit'];
        }


        $data['sorts'] = [];




        $data['sorts'][] = array(
            'text'  => $this->language->get('text_default'),
            'value' => '',
            'href'  => $this->url->link('product/latest', $url)
        );

        $data['sorts'][] = [
            'text'  => $this->language->get('text_date_asc'), // старіші спочатку
            'value' => 'p.date_added-ASC',
            'href'  => $this->url->link('product/latest', 'sort=p.date_added&order=ASC' . $url)
        ];

        $data['sorts'][] = array(
            'text'  => $this->language->get('text_model_asc'),
            'value' => 'p.model-ASC',
            'href'  => $this->url->link('product/latest', 'sort=p.model&order=ASC' . $url)
        );

        $data['sorts'][] = array(
            'text'  => $this->language->get('text_model_desc'),
            'value' => 'p.model-DESC',
            'href'  => $this->url->link('product/latest', 'sort=p.model&order=DESC' . $url)
        );
        if ($this->config->get('config_review_status')) {

            $data['sorts'][] = [
                'text'  => $this->language->get('text_rating_asc'),
                'value' => 'rating-ASC',
                'href'  => $this->url->link('product/latest', 'sort=rating&order=ASC' . $url)
            ];

            $data['sorts'][] = [
                'text'  => $this->language->get('text_rating_desc'),
                'value' => 'rating-DESC',
                'href'  => $this->url->link('product/latest', 'sort=rating&order=DESC' . $url)
            ];
        }
        $data['sorts'][] = array(
            'text'  => $this->language->get('text_name_asc'),
            'value' => 'pd.name-ASC',
            'href'  => $this->url->link('product/latest', 'sort=pd.name&order=ASC' . $url)
        );

        $data['sorts'][] = array(
            'text'  => $this->language->get('text_name_desc'),
            'value' => 'pd.name-DESC',
            'href'  => $this->url->link('product/latest', 'sort=pd.name&order=DESC' . $url)
        );

        $data['sorts'][] = array(
            'text'  => $this->language->get('text_price_asc'),
            'value' => 'p.price-ASC',
            'href'  => $this->url->link('product/latest', 'sort=p.price&order=ASC' . $url)
        );

        $data['sorts'][] = array(
            'text'  => $this->language->get('text_price_desc'),
            'value' => 'p.price-DESC',
            'href'  => $this->url->link('product/latest', 'sort=p.price&order=DESC' . $url)
        );        

        $data['sort_selected'] = $sort . '-' . $order;

        // ======================
        // SEO заголовки
        // ======================
        $this->document->setTitle('Новинки');
        $this->document->setDescription('Останні надходження в магазині');
        $this->document->setKeywords('нові товари, latest products');
        $data['heading_title'] = $this->language->get('text_latest');

        // ======================
        // Підключення Swiper
        // ======================
        $this->document->addStyle('https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css');
        $this->document->addScript('https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js');

        // ======================
        // Отримуємо останні товари
        // ======================
        $data['products'] = [];
        $filter_data = [
            'filter_category_id' => $filter_category_id,
            'filter_filter'      => $filter_filter,
            'filter_price_from'  => $price_from,
            'filter_price_to'    => $price_to,
            'filter_option'      => $filter_option,
            'filter_attribute'   => $filter_attribute,
            'sort'  => $sort,
            'order' => $order,
            'start' => ($page - 1) * $limit,
            'limit' => $limit
        ];

        $results = $this->model_catalog_product->getProducts($filter_data);
        $product_total = $this->model_catalog_product->getTotalProducts($filter_data);

        foreach ($results as $result) {
            $image = $result['image']
                ? $this->model_tool_image->resize(
                    $result['image'],
                    $this->config->get('theme_' . $this->config->get('config_theme') . '_image_product_width'),
                    $this->config->get('theme_' . $this->config->get('config_theme') . '_image_product_height')
                  )
                : $this->model_tool_image->resize(
                    'placeholder.png',
                    $this->config->get('theme_' . $this->config->get('config_theme') . '_image_product_width'),
                    $this->config->get('theme_' . $this->config->get('config_theme') . '_image_product_height')
                  );

            $price = ($this->customer->isLogged() || !$this->config->get('config_customer_price'))
                ? $this->currency->format(
                    $this->tax->calculate($result['price'], $result['tax_class_id'], $this->config->get('config_tax')),
                    $this->session->data['currency']
                  )
                : false;

            $special = (!is_null($result['special']) && (float)$result['special'] >= 0)
                ? $this->currency->format(
                    $this->tax->calculate($result['special'], $result['tax_class_id'], $this->config->get('config_tax')),
                    $this->session->data['currency']
                  )
                : false;

            $data['products'][] = [
                'product_id' => $result['product_id'],
                'thumb'      => $image,
                'name'       => $result['name'],
                'price'      => $price,
                'special'    => $special,
                'minimum'    => $result['minimum'] > 0 ? $result['minimum'] : 1,
                'href'       => $this->url->link('product/product', 'product_id=' . $result['product_id'])
            ];
        }

        $data['filter'] = $this->load->controller('extension/module/filter');


        // ======================
        // PAGINATION
        // ======================
        $pagination = new Pagination();
        $pagination->total = $product_total;
        $pagination->page  = $page;
        $pagination->limit = $limit;
        $pagination->url   = $this->url->link('product/latest', $url . '&page={page}');
        $data['pagination'] = $pagination->render();
        $data['current_page'] = $page;
        $data['total_pages'] = $limit ? (int)ceil($product_total / $limit) : 1;
        $data['pagination_pages'] = [];

        $pagination_url = $this->url->link('product/latest', $url . '&page={page}');
        $pagination_url = str_replace(['%7Bpage%7D', '%7bpage%7d'], '{page}', $pagination_url);
        $first_page_url = str_replace(['/page-{page}', '&amp;page={page}', '?page={page}', '&page={page}'], '', $pagination_url);

        $tp = (int)$data['total_pages'];
        $show = [];
        $start = $page - 1;
        if ($start > $tp - 2) { $start = $tp - 2; }
        if ($start < 1) { $start = 1; }
        for ($i = $start; $i <= min($start + 2, $tp); $i++) { $show[$i] = true; }
        if ($tp >= 1) { $show[$tp] = true; }
        ksort($show);
        $prev = 0;
        foreach (array_keys($show) as $i) {
            if ($prev && ($i - $prev) > 1) {
                $data['pagination_pages'][] = ['text' => '...', 'href' => '', 'active' => false, 'ellipsis' => true];
            }
            $data['pagination_pages'][] = [
                'text'     => $i,
                'href'     => ($i == 1) ? $first_page_url : str_replace('{page}', $i, $pagination_url),
                'active'   => ($i == $page),
                'ellipsis' => false
            ];
            $prev = $i;
        }

        $data['next_page_url'] = ($page < $data['total_pages'])
            ? str_replace('{page}', $page + 1, $pagination_url)
            : '';

        $data['sort']  = $sort;
        $data['order'] = $order;
        $data['limit'] = $limit;
        $data['home']  = $this->url->link('common/home');
        $data['latest'] = $this->url->link('product/latest');

        // ======================
        // Layout
        // ======================
        $data['column_left']    = $this->load->controller('common/column_left');
        $data['column_right']   = $this->load->controller('common/column_right');
        $data['content_top']    = $this->load->controller('common/content_top');
        $data['content_bottom'] = $this->load->controller('common/content_bottom');
        $data['footer']         = $this->load->controller('common/footer');
        $data['header']         = $this->load->controller('common/header');

        $this->response->setOutput($this->load->view('product/latest', $data));
    }
}
