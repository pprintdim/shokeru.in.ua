<?php
class ControllerExtensionpost extends Controller {
	private $error = array();

	public function index(){
		$this->load->language('extension/post');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('extension/category');
		
		$this->load->model('extension/post');
		
		$this->load->model('user/user');

		$this->getList();
	}
	
	public function add() {
		$this->load->language('extension/post');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('extension/post');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
			$this->model_extension_post->addpost($this->request->post);

			$this->session->data['success'] = $this->language->get('text_success');

			$url = '';

			if (isset($this->request->get['filter_name'])){
				$url .= '&filter_name=' . urlencode(html_entity_decode($this->request->get['filter_name'], ENT_QUOTES, 'UTF-8'));
			}
			
			if (isset($this->request->get['filter_to_date_added'])) {
				$url .= '&filter_to_date_added=' . $this->request->get['filter_to_date_added'];
			}
			
			if (isset($this->request->get['filter_from_date_added'])) {
				$url .= '&filter_from_date_added=' . $this->request->get['filter_from_date_added'];
			}
			
			if (isset($this->request->get['filter_status'])) {
				$url .= '&filter_status=' . $this->request->get['filter_status'];
			}
			
			if (isset($this->request->get['filter_category_id'])) {
				$url .= '&filter_category_id=' . $this->request->get['filter_category_id'];
			}

			if (isset($this->request->get['sort'])) {
				$url .= '&sort=' . $this->request->get['sort'];
			}

			if (isset($this->request->get['order'])) {
				$url .= '&order=' . $this->request->get['order'];
			}

			if (isset($this->request->get['page'])) {
				$url .= '&page=' . $this->request->get['page'];
			}

			$this->response->redirect($this->url->link('extension/post', 'user_token=' . $this->session->data['user_token'] . $url, 'SSL'));
		}

		$this->getForm();
	}

	public function edit() {
		$this->load->language('extension/post');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('extension/post');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
			$this->model_extension_post->editpost($this->request->get['post_id'], $this->request->post);

			$this->session->data['success'] = $this->language->get('text_success');

			$url = '';

			if (isset($this->request->get['filter_name'])){
				$url .= '&filter_name=' . urlencode(html_entity_decode($this->request->get['filter_name'], ENT_QUOTES, 'UTF-8'));
			}
			
			if (isset($this->request->get['filter_to_date_added'])) {
				$url .= '&filter_to_date_added=' . $this->request->get['filter_to_date_added'];
			}
			
			if (isset($this->request->get['filter_from_date_added'])) {
				$url .= '&filter_from_date_added=' . $this->request->get['filter_from_date_added'];
			}
			
			if (isset($this->request->get['filter_status'])) {
				$url .= '&filter_status=' . $this->request->get['filter_status'];
			}
			
			if (isset($this->request->get['filter_category_id'])) {
				$url .= '&filter_category_id=' . $this->request->get['filter_category_id'];
			}

			if (isset($this->request->get['sort'])) {
				$url .= '&sort=' . $this->request->get['sort'];
			}

			if (isset($this->request->get['order'])) {
				$url .= '&order=' . $this->request->get['order'];
			}

			if (isset($this->request->get['page'])) {
				$url .= '&page=' . $this->request->get['page'];
			}

			$this->response->redirect($this->url->link('extension/post', 'user_token=' . $this->session->data['user_token'] . $url, 'SSL'));
		}

		$this->getForm();
	}

	public function delete() {
		
		$this->load->language('extension/post');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('extension/post');

		if (isset($this->request->post['selected']) && $this->validateDelete()) {
			foreach ($this->request->post['selected'] as $post_id) {
				$this->model_extension_post->deletepost($post_id);
			}

			$this->session->data['success'] = $this->language->get('text_success');

			$url = '';

			if (isset($this->request->get['filter_name'])){
				$url .= '&filter_name=' . urlencode(html_entity_decode($this->request->get['filter_name'], ENT_QUOTES, 'UTF-8'));
			}
			
			if (isset($this->request->get['filter_to_date_added'])) {
				$url .= '&filter_to_date_added=' . $this->request->get['filter_to_date_added'];
			}
			
			if (isset($this->request->get['filter_from_date_added'])) {
				$url .= '&filter_from_date_added=' . $this->request->get['filter_from_date_added'];
			}
			
			if (isset($this->request->get['filter_status'])){
				$url .= '&filter_status=' . $this->request->get['filter_status'];
			}
			
			if (isset($this->request->get['filter_category_id'])){
				$url .= '&filter_category_id=' . $this->request->get['filter_category_id'];
			}

			if (isset($this->request->get['sort'])) {
				$url .= '&sort=' . $this->request->get['sort'];
			}

			if (isset($this->request->get['order'])) {
				$url .= '&order=' . $this->request->get['order'];
			}

			if (isset($this->request->get['page'])) {
				$url .= '&page=' . $this->request->get['page'];
			}

			$this->response->redirect($this->url->link('extension/post', 'user_token=' . $this->session->data['user_token'] . $url, 'SSL'));
		}

		$this->getList();
	}
	
	public function copy() {
		$this->language->load('extension/post');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('extension/post');

		if (isset($this->request->post['selected']) && $this->validateCopy()) {
			foreach($this->request->post['selected'] as $post_id) {
				$this->model_extension_post->copyPost($post_id);
			}

			$this->session->data['success'] = $this->language->get('text_success');

			$url = '';

			if (isset($this->request->get['filter_name'])){
				$url .= '&filter_name=' . urlencode(html_entity_decode($this->request->get['filter_name'], ENT_QUOTES, 'UTF-8'));
			}
			
			if (isset($this->request->get['filter_to_date_added'])) {
				$url .= '&filter_to_date_added=' . $this->request->get['filter_to_date_added'];
			}
			
			if (isset($this->request->get['filter_from_date_added'])) {
				$url .= '&filter_from_date_added=' . $this->request->get['filter_from_date_added'];
			}
			
			if (isset($this->request->get['filter_status'])){
				$url .= '&filter_status=' . $this->request->get['filter_status'];
			}
			
			if (isset($this->request->get['filter_category_id'])){
				$url .= '&filter_category_id=' . $this->request->get['filter_category_id'];
			}

			if (isset($this->request->get['sort'])) {
				$url .= '&sort=' . $this->request->get['sort'];
			}

			if (isset($this->request->get['order'])) {
				$url .= '&order=' . $this->request->get['order'];
			}

			if (isset($this->request->get['page'])) {
				$url .= '&page=' . $this->request->get['page'];
			}

			$this->response->redirect($this->url->link('extension/post', 'user_token=' . $this->session->data['user_token'] . $url, 'SSL'));
		}

		$this->getList();
	}

	protected function getList(){
		if (isset($this->request->get['filter_name'])) {
			$filter_name = $this->request->get['filter_name'];
		} else {
			$filter_name = null;
		}

		if (isset($this->request->get['filter_to_date_added'])) {
			$filter_to_date_added = $this->request->get['filter_to_date_added'];
		} else {
			$filter_to_date_added = null;
		}

		if (isset($this->request->get['filter_from_date_added'])) {
			$filter_from_date_added = $this->request->get['filter_from_date_added'];
		} else {
			$filter_from_date_added = null;
		}

		if (isset($this->request->get['filter_category_id'])) {
			$filter_category_id = $this->request->get['filter_category_id'];
		} else {
			$filter_category_id = null;
		}

		if (isset($this->request->get['filter_status'])) {
			$filter_status = $this->request->get['filter_status'];
		} else {
			$filter_status = null;
		}

		if(isset($this->request->get['sort'])){
			$sort = $this->request->get['sort'];
		} else {
			$sort = 'pd.name';
		}

		if (isset($this->request->get['order'])) {
			$order = $this->request->get['order'];
		} else {
			$order = 'ASC';
		}

		if (isset($this->request->get['page'])) {
			$page = $this->request->get['page'];
		} else {
			$page = 1;
		}

		$url = '';

		if (isset($this->request->get['filter_name'])){
			$url .= '&filter_name=' . urlencode(html_entity_decode($this->request->get['filter_name'], ENT_QUOTES, 'UTF-8'));
		}
			
		if (isset($this->request->get['filter_to_date_added'])) {
			$url .= '&filter_to_date_added=' . $this->request->get['filter_to_date_added'];
		}
			
		if (isset($this->request->get['filter_from_date_added'])) {
			$url .= '&filter_from_date_added=' . $this->request->get['filter_from_date_added'];
		}
			
		if (isset($this->request->get['filter_status'])) {
			$url .= '&filter_status=' . $this->request->get['filter_status'];
		}
			
		if (isset($this->request->get['filter_category_id'])) {
			$url .= '&filter_category_id=' . $this->request->get['filter_category_id'];
		}
		
		if (isset($this->request->get['sort'])) {
			$url .= '&sort=' . $this->request->get['sort'];
		}

		if (isset($this->request->get['order'])) {
			$url .= '&order=' . $this->request->get['order'];
		}

		if (isset($this->request->get['page'])) {
			$url .= '&page=' . $this->request->get['page'];
		}

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], 'SSL')
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('extension/post', 'user_token=' . $this->session->data['user_token'] . $url, 'SSL')
		);

		$data['insert'] = $this->url->link('extension/post/add', 'user_token=' . $this->session->data['user_token'] . $url, 'SSL');
		$data['copy'] = $this->url->link('extension/post/copy', 'user_token=' . $this->session->data['user_token'] . $url, 'SSL');
		$data['delete'] = $this->url->link('extension/post/delete', 'user_token=' . $this->session->data['user_token'] . $url, 'SSL');

		$data['posts'] = array();

		$filter_data = array(
			'filter_name'	  			=> $filter_name,
			'filter_from_date_added'	=> $filter_from_date_added,
			'filter_to_date_added'	  	=> $filter_to_date_added,
			'filter_category_id' 		=> $filter_category_id,
			'filter_status'   			=> $filter_status,
			'sort'            			=> $sort,
			'order'           			=> $order,
			'start'           			=> ($page - 1) * $this->config->get('config_limit_admin'),
			'limit'           			=> $this->config->get('config_limit_admin')
		);

		$this->load->model('tool/image');
		$this->load->model('extension/category');
		$this->load->model('user/user');
		
		//categories
		$data['categories'] = $this->model_extension_category->getblogCategories(array());
		
		$post_total = $this->model_extension_post->getTotalposts($filter_data);

		$results = $this->model_extension_post->getposts($filter_data);
		
		foreach($results as $result){
			if (is_file(DIR_IMAGE . $result['image'])) {
				$image = $this->model_tool_image->resize($result['image'], 40, 40);
			} else {
				$image = $this->model_tool_image->resize('no_image.png', 40, 40);
			}
			
			$data['posts'][] = array(
				'post_id'	 	=> $result['post_id'],
				'image'      	=> $image,
				'name'       	=> $result['name'],
				'username'   	=> $result['author'],
				'status'     	=> ($result['status']) ? $this->language->get('text_enabled') : $this->language->get('text_disabled'),
				'edit'       	=> $this->url->link('extension/post/edit', 'user_token=' . $this->session->data['user_token'] . '&post_id=' . $result['post_id'] . $url, 'SSL')
			);
		}

		$data['heading_title'] = $this->language->get('heading_title');
		$data['text_list'] = $this->language->get('text_list');
		$data['text_enabled'] = $this->language->get('text_enabled');
		$data['text_disabled'] = $this->language->get('text_disabled');
		$data['text_no_results'] = $this->language->get('text_no_results');
		$data['text_confirm'] = $this->language->get('text_confirm');

		$data['column_image'] = $this->language->get('column_image');
		$data['column_name'] = $this->language->get('column_name');
		$data['column_model'] = $this->language->get('column_model');
		$data['column_price'] = $this->language->get('column_price');
		$data['column_quantity'] = $this->language->get('column_quantity');
		$data['column_status'] = $this->language->get('column_status');
		$data['column_action'] = $this->language->get('column_action');

		$data['entry_name'] = $this->language->get('entry_name');
		$data['entry_model'] = $this->language->get('entry_model');
		$data['entry_price'] = $this->language->get('entry_price');
		$data['entry_quantity'] = $this->language->get('entry_quantity');
		$data['entry_status'] = $this->language->get('entry_status');
		$data['entry_to'] = $this->language->get('entry_to');
		$data['entry_from'] = $this->language->get('entry_from');
		$data['entry_category'] = $this->language->get('entry_category');

		$data['button_copy'] = $this->language->get('button_copy');
		$data['button_insert'] = $this->language->get('button_insert');
		$data['button_edit'] = $this->language->get('button_edit');
		$data['button_delete'] = $this->language->get('button_delete');
		$data['button_filter'] = $this->language->get('button_filter');

		$data['user_token'] = $this->session->data['user_token'];

		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

		if (isset($this->session->data['success'])) {
			$data['success'] = $this->session->data['success'];

			unset($this->session->data['success']);
		} else {
			$data['success'] = '';
		}

		if (isset($this->request->post['selected'])) {
			$data['selected'] = (array)$this->request->post['selected'];
		} else {
			$data['selected'] = array();
		}

		$url = '';

		if (isset($this->request->get['filter_name'])){
			$url .= '&filter_name=' . urlencode(html_entity_decode($this->request->get['filter_name'], ENT_QUOTES, 'UTF-8'));
		}
			
		if (isset($this->request->get['filter_to_date_added'])) {
			$url .= '&filter_to_date_added=' . $this->request->get['filter_to_date_added'];
		}
			
		if (isset($this->request->get['filter_from_date_added'])) {
			$url .= '&filter_from_date_added=' . $this->request->get['filter_from_date_added'];
		}
			
		if (isset($this->request->get['filter_status'])) {
			$url .= '&filter_status=' . $this->request->get['filter_status'];
		}
			
		if (isset($this->request->get['filter_category_id'])) {
			$url .= '&filter_category_id=' . $this->request->get['filter_category_id'];
		}

		if ($order == 'ASC') {
			$url .= '&order=DESC';
		} else {
			$url .= '&order=ASC';
		}

		if (isset($this->request->get['page'])) {
			$url .= '&page=' . $this->request->get['page'];
		}

		$data['sort_name'] = $this->url->link('extension/post', 'user_token=' . $this->session->data['user_token'] . '&sort=pd.name' . $url, 'SSL');
		$data['sort_status'] = $this->url->link('extension/post', 'user_token=' . $this->session->data['user_token'] . '&sort=p.status' . $url, 'SSL');
		$data['sort_order'] = $this->url->link('extension/post', 'user_token=' . $this->session->data['user_token'] . '&sort=p.sort_order' . $url, 'SSL');

		$url = '';

		if (isset($this->request->get['filter_name'])){
			$url .= '&filter_name=' . urlencode(html_entity_decode($this->request->get['filter_name'], ENT_QUOTES, 'UTF-8'));
		}
			
		if (isset($this->request->get['filter_to_date_added'])) {
			$url .= '&filter_to_date_added=' . $this->request->get['filter_to_date_added'];
		}
			
		if (isset($this->request->get['filter_from_date_added'])) {
			$url .= '&filter_from_date_added=' . $this->request->get['filter_from_date_added'];
		}
			
		if (isset($this->request->get['filter_status'])) {
			$url .= '&filter_status=' . $this->request->get['filter_status'];
		}
			
		if (isset($this->request->get['filter_category_id'])) {
			$url .= '&filter_category_id=' . $this->request->get['filter_category_id'];
		}

		if (isset($this->request->get['sort'])) {
			$url .= '&sort=' . $this->request->get['sort'];
		}

		if (isset($this->request->get['order'])) {
			$url .= '&order=' . $this->request->get['order'];
		}

		$pagination = new Pagination();
		$pagination->total = $post_total;
		$pagination->page = $page;
		$pagination->limit = $this->config->get('config_limit_admin');
		$pagination->url = $this->url->link('extension/post', 'user_token=' . $this->session->data['user_token'] . $url . '&page={page}', 'SSL');

		$data['pagination'] = $pagination->render();

		$data['results'] = sprintf($this->language->get('text_pagination'), ($post_total) ? (($page - 1) * $this->config->get('config_limit_admin')) + 1 : 0, ((($page - 1) * $this->config->get('config_limit_admin')) > ($post_total - $this->config->get('config_limit_admin'))) ? $post_total : ((($page - 1) * $this->config->get('config_limit_admin')) + $this->config->get('config_limit_admin')), $post_total, ceil($post_total / $this->config->get('config_limit_admin')));

		$data['filter_name'] = $filter_name;
		$data['filter_status'] = $filter_status;
		$data['filter_category_id'] = $filter_category_id;
		$data['filter_to_date_added'] = $filter_to_date_added;
		$data['filter_from_date_added'] = $filter_from_date_added;

		$data['sort'] = $sort;
		$data['order'] = $order;

		$data['header'] = $this->load->controller('common/header');
		$data['webxheader'] = $this->load->controller('extension/webxheader');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/post_list', $data));
	}
	

	protected function getForm() {
		$data['heading_title'] = $this->language->get('heading_title');
		
		$data['text_form'] = !isset($this->request->get['post_id']) ? $this->language->get('text_add') : $this->language->get('text_edit');
		$data['text_enabled'] = $this->language->get('text_enabled');
		$data['text_disabled'] = $this->language->get('text_disabled');
		$data['text_none'] = $this->language->get('text_none');
		$data['text_yes'] = $this->language->get('text_yes');
		$data['text_no'] = $this->language->get('text_no');
		$data['text_plus'] = $this->language->get('text_plus');
		$data['text_minus'] = $this->language->get('text_minus');
		$data['text_default'] = $this->language->get('text_default');
		$data['text_option'] = $this->language->get('text_option');
		$data['text_option_value'] = $this->language->get('text_option_value');
		$data['text_select'] = $this->language->get('text_select');
		$data['text_percent'] = $this->language->get('text_percent');
		$data['text_amount'] = $this->language->get('text_amount');
		$data['text_image'] = $this->language->get('text_image');
		$data['text_video'] = $this->language->get('text_video');
		$data['help_video'] = $this->language->get('help_video');
		$data['entry_author'] = $this->language->get('entry_author');
		$data['help_author'] = $this->language->get('help_author');
		

		$data['entry_videourl'] = $this->language->get('entry_videourl');
		$data['entry_product_type'] = $this->language->get('entry_product_type');
		$data['entry_name'] = $this->language->get('entry_name');
		$data['entry_description'] = $this->language->get('entry_description');
		$data['entry_meta_title'] = $this->language->get('entry_meta_title');
		$data['entry_meta_description'] = $this->language->get('entry_meta_description');
		$data['entry_meta_keyword'] = $this->language->get('entry_meta_keyword');
		$data['entry_keyword'] = $this->language->get('entry_keyword');
		$data['entry_date_available'] = $this->language->get('entry_date_available');
		$data['entry_image'] = $this->language->get('entry_image');
		$data['entry_store'] = $this->language->get('entry_store');
		$data['entry_category'] = $this->language->get('entry_category');
		$data['entry_related'] = $this->language->get('entry_related');
		$data['entry_text'] = $this->language->get('entry_text');
		$data['entry_required'] = $this->language->get('entry_required');
		$data['entry_sort_order'] = $this->language->get('entry_sort_order');
		$data['entry_status'] = $this->language->get('entry_status');
		$data['entry_tag'] = $this->language->get('entry_tag');
		$data['entry_customer_group'] = $this->language->get('entry_customer_group');
		$data['entry_layout'] = $this->language->get('entry_layout');
		$data['entry_short_description'] = $this->language->get('entry_short_description');
		$data['entry_allowcommet'] = $this->language->get('entry_allowcommet');
		$data['entry_product'] = $this->language->get('entry_product');
		

		$data['text_keyword'] = $this->language->get('text_keyword');
		$data['help_category'] = $this->language->get('help_category');
		$data['help_related'] = $this->language->get('help_related');
		$data['help_tag'] = $this->language->get('help_tag');
		$data['help_date'] = $this->language->get('help_date');
		$data['tab_seo'] = $this->language->get('tab_seo');

		$data['button_save'] = $this->language->get('button_save');
		$data['button_cancel'] = $this->language->get('button_cancel');
		$data['button_image_add'] = $this->language->get('button_image_add');
		$data['button_remove'] = $this->language->get('button_remove');
		

		$data['tab_general'] = $this->language->get('tab_general');
		$data['tab_data'] = $this->language->get('tab_data');
		$data['tab_image'] = $this->language->get('tab_image');
		$data['tab_links'] = $this->language->get('tab_links');
		$data['tab_design'] = $this->language->get('tab_design');
		
		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

		if (isset($this->error['name'])) {
			$data['error_name'] = $this->error['name'];
		} else {
			$data['error_name'] = array();
		}

		if (isset($this->error['meta_title'])) {
			$data['error_meta_title'] = $this->error['meta_title'];
		} else {
			$data['error_meta_title'] = array();
		}
		
		if (isset($this->error['short_description'])) {
			$data['error_short_description'] = $this->error['short_description'];
		} else {
			$data['error_short_description'] = array();
		}

		$url = '';

		if (isset($this->request->get['filter_name'])){
			$url .= '&filter_name=' . urlencode(html_entity_decode($this->request->get['filter_name'], ENT_QUOTES, 'UTF-8'));
		}
			
		if (isset($this->request->get['filter_to_date_added'])) {
			$url .= '&filter_to_date_added=' . $this->request->get['filter_to_date_added'];
		}
			
		if (isset($this->request->get['filter_from_date_added'])) {
			$url .= '&filter_from_date_added=' . $this->request->get['filter_from_date_added'];
		}
			
		if (isset($this->request->get['filter_status'])) {
			$url .= '&filter_status=' . $this->request->get['filter_status'];
		}
			
		if (isset($this->request->get['filter_category_id'])) {
			$url .= '&filter_category_id=' . $this->request->get['filter_category_id'];
		}

		if (isset($this->request->get['sort'])) {
			$url .= '&sort=' . $this->request->get['sort'];
		}

		if (isset($this->request->get['order'])) {
			$url .= '&order=' . $this->request->get['order'];
		}

		if (isset($this->request->get['page'])) {
			$url .= '&page=' . $this->request->get['page'];
		}

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], 'SSL')
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('extension/post', 'user_token=' . $this->session->data['user_token'] . $url, 'SSL')
		);

		if (!isset($this->request->get['post_id'])) {
			$data['action'] = $this->url->link('extension/post/add', 'user_token=' . $this->session->data['user_token'] . $url, 'SSL');
		} else {
			$data['action'] = $this->url->link('extension/post/edit', 'user_token=' . $this->session->data['user_token'] . '&post_id=' . $this->request->get['post_id'] . $url, 'SSL');
		}

		$data['cancel'] = $this->url->link('extension/post', 'user_token=' . $this->session->data['user_token'] . $url, 'SSL');

		if(isset($this->request->get['post_id']) && ($this->request->server['REQUEST_METHOD'] != 'POST')){
			$post_info = $this->model_extension_post->getpost($this->request->get['post_id']);
		}

		$data['user_token'] = $this->session->data['user_token'];

		$this->load->model('localisation/language');

		$data['languages'] = $this->model_localisation_language->getLanguages();

		if (isset($this->request->post['post_description'])) {
			$data['post_description'] = $this->request->post['post_description'];
		} elseif (isset($this->request->get['post_id'])) {
			$data['post_description'] = $this->model_extension_post->getpostDescriptions($this->request->get['post_id']);
		} else {
			$data['post_description'] = array();
		}	
		
		if (isset($this->request->post['post_seo_url'])) {
			$data['post_seo_url'] = $this->request->post['post_seo_url'];
		} elseif (isset($this->request->get['post_id'])) {
			$data['post_seo_url'] = $this->model_extension_post->getPostSeoUrls($this->request->get['post_id']);
		} else {
			$data['post_seo_url'] = array();
		}

		if (isset($this->request->post['image'])) {
			$data['image'] = $this->request->post['image'];
		} elseif (!empty($post_info)) {
			$data['image'] = $post_info['image'];
		} else {
			$data['image'] = '';
		}

		$this->load->model('tool/image');

		if (isset($this->request->post['image']) && is_file(DIR_IMAGE . $this->request->post['image'])) {
			$data['thumb'] = $this->model_tool_image->resize($this->request->post['image'], 100, 100);
		} elseif (!empty($post_info) && is_file(DIR_IMAGE . $post_info['image'])) {
			$data['thumb'] = $this->model_tool_image->resize($post_info['image'], 100, 100);
		} else {
			$data['thumb'] = $this->model_tool_image->resize('no_image.png', 100, 100);
		}

		$data['placeholder'] = $this->model_tool_image->resize('no_image.png', 100, 100);
		
		$this->load->model('setting/store');

		$data['stores'] = array();
		
		$data['stores'][] = array(
			'store_id' => 0,
			'name'     => $this->language->get('text_default')
		);
		
		$stores = $this->model_setting_store->getStores();

		foreach ($stores as $store) {
			$data['stores'][] = array(
				'store_id' => $store['store_id'],
				'name'     => $store['name']
			);
		}
		
		if (isset($this->request->post['post_store'])) {
			$data['post_store'] = $this->request->post['post_store'];
		} elseif (isset($this->request->get['post_id'])) {
			$data['post_store'] = $this->model_extension_post->getpostStores($this->request->get['post_id']);
		} else {
			$data['post_store'] = array(0);
		}

		
		if (isset($this->request->post['author'])) {
			$data['author'] = $this->request->post['author'];
		} elseif (!empty($post_info)) {
			$data['author'] = $post_info['author'];
		} else {
			$data['author'] = $this->user->getUserName();
		}

		if (isset($this->request->post['date'])) {
			$data['date'] = $this->request->post['date'];
		} elseif (!empty($post_info)) {
			$data['date'] = ($post_info['date'] != '0000-00-00') ? $post_info['date'] : '';
		} else {
			$data['date'] = date('Y-m-d');
		}

		if (isset($this->request->post['sort_order'])) {
			$data['sort_order'] = $this->request->post['sort_order'];
		} elseif (!empty($post_info)) {
			$data['sort_order'] = $post_info['sort_order'];
		} else {
			$data['sort_order'] = 1;
		}

		if (isset($this->request->post['status'])) {
			$data['status'] = $this->request->post['status'];
		} elseif (!empty($post_info)) {
			$data['status'] = $post_info['status'];
		} else {
			$data['status'] = 1;
		}
		
		if (isset($this->request->post['post_type'])) {
			$data['post_type'] = $this->request->post['post_type'];
		} elseif (!empty($post_info)) {
			$data['post_type'] = $post_info['post_type'];
		} else {
			$data['post_type'] = 1;
		}

		$this->load->model('catalog/manufacturer');
		$data['brands'] = $this->model_catalog_manufacturer->getManufacturers();

		if (isset($this->request->post['brand_id'])) {
			$data['brand_id'] = $this->request->post['brand_id'];
		} elseif (!empty($post_info)) {
			$data['brand_id'] = $post_info['brand_id'];
		} else {
			$data['brand_id'] = '';
		}
		
		
		if (isset($this->request->post['videourl'])) {
			$data['videourl'] = $this->request->post['videourl'];
		} elseif (!empty($post_info)) {
			$data['videourl'] = $post_info['video_url'];
		} else {
			$data['videourl'] = '';
		}
		
		if (isset($this->request->post['allowcomment'])) {
			$data['allowcomment'] = $this->request->post['allowcomment'];
		} elseif (!empty($post_info)) {
			$data['allowcomment'] = $post_info['allowcoment'];
		} else {
			$data['allowcomment'] = 1;
		}

		// Categories
		$this->load->model('extension/category');

		if (isset($this->request->post['post_category'])) {
			$categories = $this->request->post['post_category'];
		} elseif (isset($this->request->get['post_id'])) {
			$categories = $this->model_extension_post->getpostCategories($this->request->get['post_id']);
		} else {
			$categories = array();
		}

		$data['post_categories'] = array();

		foreach ($categories as $blog_category_id) {
			$category_info = $this->model_extension_category->getblogcategory($blog_category_id);

			if ($category_info) {
				$data['post_categories'][] = array(
					'blog_category_id' => $category_info['blog_category_id'],
					'name' => ($category_info['path']) ? $category_info['path'] . ' &gt; ' . $category_info['name'] : $category_info['name']
				);
			}
		}

		// Images
		if (isset($this->request->post['post_image'])) {
			$post_images = $this->request->post['post_image'];
		} elseif (isset($this->request->get['post_id'])) {
			$post_images = $this->model_extension_post->getpostImages($this->request->get['post_id']);
		} else {
			$post_images = array();
		}

		$data['post_images'] = array();

		foreach ($post_images as $post_image) {
			if (is_file(DIR_IMAGE . $post_image['image'])) {
				$image = $post_image['image'];
				$thumb = $post_image['image'];
			} else {
				$image = '';
				$thumb = 'no_image.png';
			}

			$data['post_images'][] = array(
				'image'      => $image,
				'thumb'      => $this->model_tool_image->resize($thumb, 100, 100),
				'sort_order' => $post_image['sort_order']
			);
		}

		if (isset($this->request->post['post_related'])) {
			$posts = $this->request->post['post_related'];
		} elseif (isset($this->request->get['post_id'])) {
			$posts = $this->model_extension_post->getpostRelated($this->request->get['post_id']);
		} else {
			$posts = array();
		}

		$data['post_relateds'] = array();

		foreach ($posts as $post_id) {
			$related_info = $this->model_extension_post->getpost($post_id);

			if ($related_info) {
				$data['post_relateds'][] = array(
					'post_id' => $related_info['post_id'],
					'name'       => $related_info['name']
				);
			}
		}
		
		
		$this->load->model('catalog/product');
		
		if (isset($this->request->post['product_related'])) {
			$product_related = $this->request->post['product_related'];
		} elseif (isset($this->request->get['post_id'])) {
			$product_related = $this->model_extension_post->getpostRelatedproduct($this->request->get['post_id']);
		} else {
			$product_related = array();
		}

		$data['post_products'] = array();

		foreach($product_related as $product_id){
			$related_info = $this->model_catalog_product->getProduct($product_id);
			if($related_info){
				$data['post_products'][] = array(
					'product_id'  => $related_info['product_id'],
					'name'        => $related_info['name']
				);
			}
		}
		
		if(isset($this->request->post['post_layout'])){
			$data['post_layout'] = $this->request->post['post_layout'];
		} elseif (isset($this->request->get['post_id'])) {
			$data['post_layout'] = $this->model_extension_post->getpostLayouts($this->request->get['post_id']);
		} else {
			$data['post_layout'] = array();
		}

		$this->load->model('design/layout');

		$data['layouts'] = $this->model_design_layout->getLayouts();

		$data['header'] = $this->load->controller('common/header');
		$data['webxheader'] = $this->load->controller('extension/webxheader');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/post_form', $data));
	}

	protected function validateForm() {
		if (!$this->user->hasPermission('modify', 'extension/post')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		foreach ($this->request->post['post_description'] as $language_id => $value) {
			if ((utf8_strlen($value['name']) < 3) || (utf8_strlen($value['name']) > 255)) {
				$this->error['name'][$language_id] = $this->language->get('error_name');
			}

			if ((utf8_strlen($value['meta_title']) < 3) || (utf8_strlen($value['meta_title']) > 255)) {
				$this->error['meta_title'][$language_id] = $this->language->get('error_meta_title');
			}
			
			/* if ((utf8_strlen($value['short_description']) < 10) || (utf8_strlen($value['short_description']) > 1000)){
				$this->error['short_description'][$language_id] = $this->language->get('error_short_description');
			} */
		}
		
		if($this->error && !isset($this->error['warning'])){
			$this->error['warning'] = $this->language->get('error_warning');
		}

		return !$this->error;
	}

	protected function validateDelete() {
		if (!$this->user->hasPermission('modify', 'extension/post')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		return !$this->error;
	}
	
	protected function validateCopy(){
		if (!$this->user->hasPermission('modify', 'extension/post')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		return !$this->error;
	}

	public function autocomplete() {
		$json = array();
		
		if(isset($this->request->get['filter_name'])){
			$this->load->model('extension/post');
			
			if (isset($this->request->get['filter_name'])) {
				$filter_name = $this->request->get['filter_name'];
			} else {
				$filter_name = '';
			}
			
			if(isset($this->request->get['limit'])){
				$limit = $this->request->get['limit'];
			}else{
				$limit = 5;
			}
			
			$filter_data = array(
				'filter_name'  => $filter_name,
				'start'        => 0,
				'limit'        => $limit
			);

			$results = $this->model_extension_post->getposts($filter_data);

			foreach ($results as $result){
				$json[] = array(
					'post_id'	=> $result['post_id'],
					'name'      => strip_tags(html_entity_decode($result['name'], ENT_QUOTES, 'UTF-8')),
				);
			}
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}
}