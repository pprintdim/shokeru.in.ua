<?php
class ModelExtensionPost extends Model {
	
	public function Createtable(){
		$this->db->query("CREATE TABLE IF NOT EXISTS `".DB_PREFIX."blog_category` (`blog_category_id` int(11) NOT NULL AUTO_INCREMENT,`image` varchar(255) DEFAULT NULL,`parent_id` int(11) NOT NULL DEFAULT '0',`sort_order` int(3) NOT NULL DEFAULT '0',`status` tinyint(1) NOT NULL,`date_added` datetime NOT NULL,`date_modified` datetime NOT NULL, PRIMARY KEY (`blog_category_id`))");
		
		$this->db->query("CREATE TABLE IF NOT EXISTS `".DB_PREFIX."blog_category_description` (`blog_category_id` int(11) NOT NULL AUTO_INCREMENT,`language_id` int(11) NOT NULL,`name` varchar(255) NOT NULL,`description` text NOT NULL,`meta_title` varchar(255) NOT NULL,`meta_description` varchar(255) NOT NULL,`meta_keyword` varchar(255) NOT NULL, PRIMARY KEY (`blog_category_id`,`language_id`))");
		
		$this->db->query("CREATE TABLE IF NOT EXISTS `".DB_PREFIX."blog_category_path` (`blog_category_id` int(11) NOT NULL AUTO_INCREMENT,`path_id` int(11) NOT NULL,`level` int(11) NOT NULL, PRIMARY KEY (`blog_category_id`,`path_id`))");
		
		$this->db->query("CREATE TABLE IF NOT EXISTS `".DB_PREFIX."blog_category_to_layout` (`blog_category_id` int(11) NOT NULL AUTO_INCREMENT,`store_id` int(11) NOT NULL,`layout_id` int(11) NOT NULL, PRIMARY KEY (`blog_category_id`,`store_id`))");
		
		$this->db->query("CREATE TABLE IF NOT EXISTS `".DB_PREFIX."blog_category_to_store` (`blog_category_id` int(11) NOT NULL AUTO_INCREMENT,`store_id` int(11) NOT NULL, PRIMARY KEY (`blog_category_id`,`store_id`))");
		
		$this->db->query("CREATE TABLE IF NOT EXISTS `".DB_PREFIX."post` (`post_id` int(11) NOT NULL AUTO_INCREMENT,`user_id` int(11) NOT NULL,`post_type` int(11) NOT NULL,`video_url` text NOT NULL,`image` varchar(255) DEFAULT NULL,`author` varchar(255) DEFAULT NULL,`date` date NOT NULL,`allowcoment` tinyint(4) NOT NULL,`sort_order` int(11) NOT NULL DEFAULT '0',`status` tinyint(1) NOT NULL DEFAULT '0',`viewed` int(5) NOT NULL DEFAULT '0',`date_added` datetime NOT NULL,`date_modified` datetime NOT NULL, PRIMARY KEY (`post_id`))");
		  
		$this->db->query("CREATE TABLE IF NOT EXISTS `".DB_PREFIX."post_comment` (`comment_id` int(11) NOT NULL AUTO_INCREMENT,`post_id` int(11) NOT NULL,`customer_id` int(11) NOT NULL,`author` varchar(64) NOT NULL,`email` varchar(96) NOT NULL,`text` text NOT NULL,`status` tinyint(1) NOT NULL DEFAULT '0',`date_added` datetime NOT NULL,`date_modified` datetime NOT NULL, PRIMARY KEY (`comment_id`))");
		
		$this->db->query("CREATE TABLE IF NOT EXISTS `".DB_PREFIX."post_description` (`post_id` int(11) NOT NULL AUTO_INCREMENT,`language_id` int(11) NOT NULL,`name` varchar(255) NOT NULL,`description` text NOT NULL,`short_description` varchar(300) NOT NULL,`tag` text NOT NULL,`meta_title` varchar(255) NOT NULL,`meta_description` varchar(255) NOT NULL,`meta_keyword` varchar(255) NOT NULL,`seo_meta_use_entity_meta` tinyint(1) NOT NULL DEFAULT '0', PRIMARY KEY (`post_id`,`language_id`))");
		
		$this->db->query("CREATE TABLE IF NOT EXISTS `".DB_PREFIX."post_image` (`post_image_id` int(11) NOT NULL AUTO_INCREMENT,`post_id` int(11) NOT NULL,`image` varchar(255) DEFAULT NULL,`sort_order` int(3) NOT NULL DEFAULT '0', PRIMARY KEY (`post_image_id`))");
		
		$this->db->query("CREATE TABLE IF NOT EXISTS `".DB_PREFIX."post_like` (`like_id` int(11) NOT NULL AUTO_INCREMENT,`post_id` int(11) NOT NULL,`customer_id` int(11) NOT NULL,`status` int(11) NOT NULL, PRIMARY KEY (`like_id`))");
		
		$this->db->query("CREATE TABLE IF NOT EXISTS `".DB_PREFIX."post_related` (`post_id` int(11) NOT NULL AUTO_INCREMENT,`related_id` int(11) NOT NULL, PRIMARY KEY (`post_id`,`related_id`))");
		
		$this->db->query("CREATE TABLE IF NOT EXISTS `".DB_PREFIX."post_related_product` (`post_id` int(11) NOT NULL AUTO_INCREMENT,
		`product_id` int(11) NOT NULL, PRIMARY KEY (`post_id`,`product_id`))");
		
		$this->db->query("CREATE TABLE IF NOT EXISTS `".DB_PREFIX."post_to_blog_category` (`post_id` int(11) NOT NULL AUTO_INCREMENT,
		`blog_category_id` int(11) NOT NULL, PRIMARY KEY (`post_id`,`blog_category_id`))");
		
		$this->db->query("CREATE TABLE IF NOT EXISTS `".DB_PREFIX."post_to_layout` (`post_id` int(11) NOT NULL AUTO_INCREMENT,
		`store_id` int(11) NOT NULL,`layout_id` int(11) NOT NULL, PRIMARY KEY (`post_id`,`store_id`))");
		
		$this->db->query("CREATE TABLE IF NOT EXISTS `".DB_PREFIX."post_to_store` (`post_id` int(11) NOT NULL AUTO_INCREMENT,
		`store_id` int(11) NOT NULL, PRIMARY KEY (`post_id`,`store_id`))");
	}
	
	public function Createtable2(){
		$this->db->query("CREATE TABLE IF NOT EXISTS `".DB_PREFIX."blogemaillist` (`blogemail_id` int(11) NOT NULL AUTO_INCREMENT,
		`post_id` int(11) NOT NULL,`date_added` datetime NOT NULL, PRIMARY KEY (`blogemail_id`))");
		
		$this->db->query("CREATE TABLE IF NOT EXISTS `".DB_PREFIX."blogsubscribe` (`subscribe_id` int(11) NOT NULL AUTO_INCREMENT,
		`email` varchar(128) NOT NULL,`status` int(11) NOT NULL, PRIMARY KEY (`subscribe_id`))");
	}
	
	public function sethomeblogurl($keyword){
		$this->db->query("DELETE FROM " . DB_PREFIX . "url_alias WHERE query = 'blog/home'");
		if(isset($keyword)){
			$this->db->query("INSERT INTO " . DB_PREFIX . "url_alias SET query = 'blog/home', keyword = '" . $this->db->escape($keyword) . "'");
		}
	}
	
	public function gethomeblogurl($path){
	  $query = $this->db->query("SELECT keyword FROM ".DB_PREFIX."url_alias WHERE query = '".$this->db->escape($path)."'")->row;
	  if(!empty($query['keyword'])){
		  return $query['keyword'];
	  }else{
		  return '';
	  }
	}
	
	public function addpost($data) {
		$this->ensureSeoMetaColumn();
		

		$this->db->query("INSERT INTO " . DB_PREFIX . "post SET date = '" . $this->db->escape($data['date']) . "', author = '" . $this->db->escape($data['author']) . "', status = '" . (isset($data['status']) ? $data['status'] : 0) . "', allowcoment = '".(isset($data['allowcomment']) ? $data['allowcomment'] : 0)."', user_id = '".(int)$this->user->getId()."', sort_order = '" . (int)$data['sort_order'] . "', brand_id = '".(int)$data['brand_id']."', post_type = '".(int)$data['post_type']."', video_url = '".$this->db->escape($data['videourl'])."', date_added = NOW(), date_modified = NOW()");

		$post_id = $this->db->getLastId();

		if (isset($data['image'])) {
			$this->db->query("UPDATE " . DB_PREFIX . "post SET image = '" . $this->db->escape($data['image']) . "' WHERE post_id = '" . (int)$post_id . "'");
		}

		foreach ($data['post_description'] as $language_id => $value) {
			$this->db->query("INSERT INTO " . DB_PREFIX . "post_description SET post_id = '" . (int)$post_id . "', language_id = '" . (int)$language_id . "', name = '" . $this->db->escape($value['name']) . "', description = '" . $this->db->escape($value['description']) . "', tag = '" . $this->db->escape($value['tag']) . "', meta_title = '" . $this->db->escape($value['meta_title']) . "', short_description = '".$this->db->escape($value['short_description'])."', meta_description = '" . $this->db->escape($value['meta_description']) . "', meta_keyword = '" . $this->db->escape($value['meta_keyword']) . "', seo_meta_use_entity_meta = '" . (int)!empty($value['seo_meta_use_entity_meta']) . "'");
		}

		if (isset($data['post_store'])) {
			foreach ($data['post_store'] as $store_id) {
				$this->db->query("INSERT INTO " . DB_PREFIX . "post_to_store SET post_id = '" . (int)$post_id . "', store_id = '" . (int)$store_id . "'");
			}
		}

		if (isset($data['post_image'])) {
			foreach ($data['post_image'] as $post_image) {
				$this->db->query("INSERT INTO " . DB_PREFIX . "post_image SET post_id = '" . (int)$post_id . "', image = '" . $this->db->escape($post_image['image']) . "', sort_order = '" . (int)$post_image['sort_order'] . "'");
			}
		}

		if (isset($data['post_category'])) {
			foreach ($data['post_category'] as $blog_category_id) {
				$this->db->query("INSERT INTO " . DB_PREFIX . "post_to_blog_category SET post_id = '" . (int)$post_id . "',blog_category_id = '" . (int)$blog_category_id . "'");
			}
		}
		
		if(isset($data['product_related'])){
			foreach($data['product_related'] as $product_id){
			  $this->db->query("INSERT INTO " . DB_PREFIX . "post_related_product SET post_id = '" . (int)$post_id . "', product_id = '" . (int)$product_id . "'");
			}
		}

		if (isset($data['post_related'])) {
			foreach ($data['post_related'] as $related_id) {
				$this->db->query("DELETE FROM " . DB_PREFIX . "post_related WHERE post_id = '" . (int)$post_id . "' AND related_id = '" . (int)$related_id . "'");
				$this->db->query("INSERT INTO " . DB_PREFIX . "post_related SET post_id = '" . (int)$post_id . "', related_id = '" . (int)$related_id . "'");
				$this->db->query("DELETE FROM " . DB_PREFIX . "post_related WHERE post_id = '" . (int)$related_id . "' AND related_id = '" . (int)$post_id . "'");
				$this->db->query("INSERT INTO " . DB_PREFIX . "post_related SET post_id = '" . (int)$related_id . "', related_id = '" . (int)$post_id . "'");
			}
		}

		if (isset($data['post_layout'])) {
			foreach ($data['post_layout'] as $store_id => $layout_id) {
				$this->db->query("INSERT INTO " . DB_PREFIX . "post_to_layout SET post_id = '" . (int)$post_id . "', store_id = '" . (int)$store_id . "', layout_id = '" . (int)$layout_id . "'");
			}
		}

		if (isset($data['post_seo_url'])) {
			foreach ($data['post_seo_url'] as $store_id => $language) {
				foreach ($language as $language_id => $keyword) {
					if (!empty($keyword)) {
						$this->db->query("INSERT INTO " . DB_PREFIX . "seo_url SET store_id = '" . (int)$store_id . "', language_id = '" . (int)$language_id . "', query = 'post_id=" . (int)$post_id . "', keyword = '" . $this->db->escape($keyword) . "'");
					}
				}
			}
		}

		

		

		return $post_id;
	}

	public function editpost($post_id, $data){
		$this->ensureSeoMetaColumn();
		

		$this->db->query("UPDATE " . DB_PREFIX . "post SET author = '" . $this->db->escape($data['author']) . "', date = '" . $this->db->escape($data['date']) . "', user_id = '".$this->user->getId()."', status = '" . (isset($data['status']) ? $data['status'] : 0) . "', allowcoment = '".(isset($data['allowcomment']) ? $data['allowcomment'] : 0)."', sort_order = '" . (int)$data['sort_order'] . "', brand_id = '".(int)$data['brand_id']."', post_type = '".(int)$data['post_type']."', video_url = '".$this->db->escape($data['videourl'])."', date_modified = NOW() WHERE post_id = '" . (int)$post_id . "'");

		if (isset($data['image'])) {
			$this->db->query("UPDATE " . DB_PREFIX . "post SET image = '" . $this->db->escape($data['image']) . "' WHERE post_id = '" . (int)$post_id . "'");
		}

		$this->db->query("DELETE FROM " . DB_PREFIX . "post_description WHERE post_id = '" . (int)$post_id . "'");

		foreach ($data['post_description'] as $language_id => $value) {
			$this->db->query("INSERT INTO " . DB_PREFIX . "post_description SET post_id = '" . (int)$post_id . "', language_id = '" . (int)$language_id . "', name = '" . $this->db->escape($value['name']) . "', description = '" . $this->db->escape($value['description']) . "', tag = '" . $this->db->escape($value['tag']) . "', meta_title = '" . $this->db->escape($value['meta_title']) . "', short_description = '".$this->db->escape($value['short_description'])."', meta_description = '" . $this->db->escape($value['meta_description']) . "', meta_keyword = '" . $this->db->escape($value['meta_keyword']) . "', seo_meta_use_entity_meta = '" . (int)!empty($value['seo_meta_use_entity_meta']) . "'");
		}

		$this->db->query("DELETE FROM " . DB_PREFIX . "post_to_store WHERE post_id = '" . (int)$post_id . "'");

		if (isset($data['post_store'])) {
			foreach ($data['post_store'] as $store_id) {
				$this->db->query("INSERT INTO " . DB_PREFIX . "post_to_store SET post_id = '" . (int)$post_id . "', store_id = '" . (int)$store_id . "'");
			}
		}

		$this->db->query("DELETE FROM " . DB_PREFIX . "post_image WHERE post_id = '" . (int)$post_id . "'");

		if(isset($data['post_image'])){
			foreach ($data['post_image'] as $post_image) {
				$this->db->query("INSERT INTO " . DB_PREFIX . "post_image SET post_id = '" . (int)$post_id . "', image = '" . $this->db->escape($post_image['image']) . "', sort_order = '" . (int)$post_image['sort_order'] . "'");
			}
		}

		$this->db->query("DELETE FROM " . DB_PREFIX . "post_to_blog_category WHERE post_id = '" . (int)$post_id . "'");

		if (isset($data['post_category'])) {
			foreach ($data['post_category'] as $blog_category_id){
				$this->db->query("INSERT INTO " . DB_PREFIX . "post_to_blog_category SET post_id = '" . (int)$post_id . "',blog_category_id = '" . (int)$blog_category_id . "'");
			}
		}

		$this->db->query("DELETE FROM " . DB_PREFIX . "post_related WHERE post_id = '" . (int)$post_id . "'");
		
		$this->db->query("DELETE FROM " . DB_PREFIX . "post_related WHERE related_id = '" . (int)$post_id . "'");

		if (isset($data['post_related'])) {
			foreach ($data['post_related'] as $related_id) {
				$this->db->query("DELETE FROM " . DB_PREFIX . "post_related WHERE post_id = '" . (int)$post_id . "' AND related_id = '" . (int)$related_id . "'");
				$this->db->query("INSERT INTO " . DB_PREFIX . "post_related SET post_id = '" . (int)$post_id . "', related_id = '" . (int)$related_id . "'");
				$this->db->query("DELETE FROM " . DB_PREFIX . "post_related WHERE post_id = '" . (int)$related_id . "' AND related_id = '" . (int)$post_id . "'");
				$this->db->query("INSERT INTO " . DB_PREFIX . "post_related SET post_id = '" . (int)$related_id . "', related_id = '" . (int)$post_id . "'");
			}
		}
		
		$this->db->query("DELETE FROM " . DB_PREFIX . "post_related_product WHERE post_id = '" . (int)$post_id . "'");
		if(isset($data['product_related'])){
			foreach($data['product_related'] as $product_id){
			  $this->db->query("INSERT INTO " . DB_PREFIX . "post_related_product SET post_id = '" . (int)$post_id . "', product_id = '" . (int)$product_id . "'");
			}
		}

		$this->db->query("DELETE FROM " . DB_PREFIX . "post_to_layout WHERE post_id = '" . (int)$post_id . "'");

		if (isset($data['post_layout'])) {
			foreach ($data['post_layout'] as $store_id => $layout_id) {
				$this->db->query("INSERT INTO " . DB_PREFIX . "post_to_layout SET post_id = '" . (int)$post_id . "', store_id = '" . (int)$store_id . "', layout_id = '" . (int)$layout_id . "'");
			}
		}

		$this->db->query("DELETE FROM `" . DB_PREFIX . "seo_url` WHERE query = 'post_id=" . (int)$post_id . "'");

		if (isset($data['post_seo_url'])) {
			foreach ($data['post_seo_url'] as $store_id => $language) {
				foreach ($language as $language_id => $keyword) {
					if (!empty($keyword)) {
						$this->db->query("INSERT INTO " . DB_PREFIX . "seo_url SET store_id = '" . (int)$store_id . "', language_id = '" . (int)$language_id . "', query = 'post_id=" . (int)$post_id . "', keyword = '" . $this->db->escape($keyword) . "'");
					}
				}
			}
		}

		

		
	}
	
	public function deletepost($post_id){
		

		$this->db->query("DELETE FROM " . DB_PREFIX . "post WHERE post_id = '" . (int)$post_id . "'");
		$this->db->query("DELETE FROM " . DB_PREFIX . "post_description WHERE post_id = '" . (int)$post_id . "'");
		$this->db->query("DELETE FROM " . DB_PREFIX . "post_image WHERE post_id = '" . (int)$post_id . "'");
		$this->db->query("DELETE FROM " . DB_PREFIX . "post_related WHERE post_id = '" . (int)$post_id . "'");
		$this->db->query("DELETE FROM " . DB_PREFIX . "post_comment WHERE post_id = '" . (int)$post_id . "'");
		$this->db->query("DELETE FROM " . DB_PREFIX . "post_related WHERE related_id = '" . (int)$post_id . "'");
		$this->db->query("DELETE FROM " . DB_PREFIX . "post_to_blog_category WHERE post_id = '" . (int)$post_id . "'");
		$this->db->query("DELETE FROM " . DB_PREFIX . "post_to_layout WHERE post_id = '" . (int)$post_id . "'");
		$this->db->query("DELETE FROM " . DB_PREFIX . "post_to_store WHERE post_id = '" . (int)$post_id . "'");
		$this->db->query("DELETE FROM " . DB_PREFIX . "post_comment WHERE post_id = '" . (int)$post_id . "'");
		$this->db->query("DELETE FROM " . DB_PREFIX . "seo_url WHERE query = 'post_id=" . (int)$post_id . "'");
}	public function copyPost($post_id){
		$query = $this->db->query("SELECT DISTINCT * FROM " . DB_PREFIX . "post p LEFT JOIN " . DB_PREFIX . "post_description pd ON (p.post_id = pd.post_id) WHERE p.post_id = '" . (int)$post_id . "' AND pd.language_id = '" . (int)$this->config->get('config_language_id') . "'");

		if ($query->num_rows){
			$data = $query->row;

			$data['viewed'] 	 = '0';
			$data['keyword'] 	 = '';
			$data['status'] 	 = '0';
			$data['date']		 = date('Y-m-d');
			
			$data['post_description'] = $this->getPostDescriptions($post_id);
			$data['post_related'] = $this->getpostRelated($post_id);
			$data['product_related'] = $this->getpostRelatedproduct($post_id);
			$data['post_category'] = $this->getPostCategories($post_id);
			$data['post_layout'] = $this->getPostLayouts($post_id);
			$data['post_store'] = $this->getPostStores($post_id);
			
			$this->addpost($data);
		}
	}
	

	public function getpost($post_id) {
		$query = $this->db->query("SELECT DISTINCT * FROM " . DB_PREFIX . "post p LEFT JOIN " . DB_PREFIX . "post_description pd ON (p.post_id = pd.post_id) WHERE p.post_id = '" . (int)$post_id . "' AND pd.language_id = '" . (int)$this->config->get('config_language_id') . "'");

		return $query->row;
	}

	public function getposts($data = array()) {
		$sql = "SELECT * FROM " . DB_PREFIX . "post p LEFT JOIN " . DB_PREFIX . "post_description pd ON (p.post_id = pd.post_id)";
		
		if(!empty($data['filter_category_id'])){
			$sql .=" LEFT JOIN ". DB_PREFIX ."post_to_blog_category p2c ON (p.post_id = p2c.post_id)";	
		}

		$sql .=" WHERE pd.language_id = '" . (int)$this->config->get('config_language_id') . "'";
		
		
		if (!empty($data['filter_name'])){
			$sql .= " AND pd.name LIKE '" . $this->db->escape($data['filter_name']) . "%'";
		}
		
		if (isset($data['filter_status']) && $data['filter_status'] !== null) {
			$sql .= " AND p.status = '" . (int)$data['filter_status'] . "'";
		}
		
		if(!empty($data['filter_category_id'])){
			$sql .= " AND p2c.blog_category_id = '" . (int)$data['filter_category_id'] . "'";
		}
		
		if(!empty($data['filter_from_date_added']) && !empty($data['filter_to_date_added'])){	
			$date_added_to = date('Y-m-d',strtotime($data['filter_to_date_added']));
			$date_added_form = date('Y-m-d',strtotime($data['filter_from_date_added']));
			$sql .=" AND DATE(p.date) BETWEEN DATE('" . $this->db->escape($date_added_to) . "') AND DATE('" . $this->db->escape($date_added_form) . "')";
		}
		
		if(!empty($data['filter_from_date_added']) && empty($data['filter_to_date_added'])){
			$date_added = date('Y-m-d',strtotime($data['filter_from_date_added']));
			$sql .=" AND DATE(p.date_added) = DATE('" . $this->db->escape($date_added) . "')";
		}
		
		
		if(empty($data['filter_from_date_added']) && !empty($data['filter_to_date_added'])){
			$date_modified = date('Y-m-d',strtotime($data['filter_to_date_added']));
			$sql .=" AND DATE(p.date_added) = '" . $this->db->escape($date_modified) . "'";
		}
		
		$sql .= " GROUP BY p.post_id";
		
		$sort_data = array(
			'pd.name',
			'p.status',
			'p.sort_order',
			'p.viewed'
		);

		if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
			$sql .= " ORDER BY " . $data['sort'];
		} else {
			$sql .= " ORDER BY pd.name";
		}

		if (isset($data['order']) && ($data['order'] == 'DESC')) {
			$sql .= " DESC";
		} else {
			$sql .= " ASC";
		}

		if (isset($data['start']) || isset($data['limit'])) {
			if ($data['start'] < 0) {
				$data['start'] = 0;
			}

			if ($data['limit'] < 1) {
				$data['limit'] = 20;
			}

			$sql .= " LIMIT " . (int)$data['start'] . "," . (int)$data['limit'];
		}

		$query = $this->db->query($sql);

		return $query->rows;
	}

	public function getpostsByCategoryId($category_id) {
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "post p LEFT JOIN " . DB_PREFIX . "post_description pd ON (p.post_id = pd.post_id) LEFT JOIN " . DB_PREFIX . "post_to_blog_category p2c ON (p.post_id = p2c.post_id) WHERE pd.language_id = '" . (int)$this->config->get('config_language_id') . "' AND p2c.blog_category_id = '" . (int)$blog_category_id . "' ORDER BY pd.name ASC");

		return $query->rows;
	}

	public function getpostDescriptions($post_id) {
		$this->ensureSeoMetaColumn();

		$post_description_data = array();

		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "post_description WHERE post_id = '" . (int)$post_id . "'");

		foreach ($query->rows as $result) {
			$post_description_data[$result['language_id']] = array(
				'name'             	=> $result['name'],
				'description'      	=> $result['description'],
				'meta_title'       	=> $result['meta_title'],
				'meta_description' 	=> $result['meta_description'],
				'meta_keyword'     	=> $result['meta_keyword'],
				'seo_meta_use_entity_meta' => (int)($result['seo_meta_use_entity_meta'] ?? 0),
				'short_description' => $result['short_description'],
				'tag'              => $result['tag']
			);
		}

		return $post_description_data;
	}

	private function ensureSeoMetaColumn(): void {
		$query = $this->db->query("SHOW COLUMNS FROM `" . DB_PREFIX . "post_description` LIKE 'seo_meta_use_entity_meta'");

		if (!$query->num_rows) {
			$this->db->query("ALTER TABLE `" . DB_PREFIX . "post_description` ADD `seo_meta_use_entity_meta` TINYINT(1) NOT NULL DEFAULT 0 AFTER `meta_description`");
		}
	}

	public function getpostCategories($post_id) {
		$post_category_data = array();

		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "post_to_blog_category WHERE post_id = '" . (int)$post_id . "'");

		foreach ($query->rows as $result) {
			$post_category_data[] = $result['blog_category_id'];
		}

		return $post_category_data;
	}

	public function getpostImages($post_id) {
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "post_image WHERE post_id = '" . (int)$post_id . "' ORDER BY sort_order ASC");

		return $query->rows;
	}
	
	public function getpostStores($post_id) {
		$post_store_data = array();

		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "post_to_store WHERE post_id = '" . (int)$post_id . "'");

		foreach ($query->rows as $result) {
			$post_store_data[] = $result['store_id'];
		}

		return $post_store_data;
	}
	
	public function getPostSeoUrls($post_id) {
		$post_seo_url_data = array();
		
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "seo_url WHERE query = 'post_id=" . (int)$post_id . "'");

		foreach ($query->rows as $result) {
			$post_seo_url_data[$result['store_id']][$result['language_id']] = $result['keyword'];
		}

		return $post_seo_url_data;
	}

	public function getpostLayouts($post_id) {
		$post_layout_data = array();

		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "post_to_layout WHERE post_id = '" . (int)$post_id . "'");

		foreach ($query->rows as $result) {
			$post_layout_data[$result['store_id']] = $result['layout_id'];
		}

		return $post_layout_data;
	}

	public function getpostRelated($post_id) {
		$post_related_data = array();

		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "post_related WHERE post_id = '" . (int)$post_id . "'");

		foreach ($query->rows as $result) {
			$post_related_data[] = $result['related_id'];
		}

		return $post_related_data;
	}
	
	public function getpostRelatedproduct($post_id){
		$post_related_data = array();

		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "post_related_product WHERE post_id = '" . (int)$post_id . "'");

		foreach($query->rows as $result){
			$post_related_data[] = $result['product_id'];
		}

		return $post_related_data;
	}

	public function getTotalposts($data = array()) {
		$sql = "SELECT COUNT(DISTINCT p.post_id) AS total FROM " . DB_PREFIX . "post p LEFT JOIN " . DB_PREFIX . "post_description pd ON (p.post_id = pd.post_id)";

		if(!empty($data['filter_category_id'])){
			$sql .=" LEFT JOIN ". DB_PREFIX ."post_to_blog_category p2c ON (p.post_id = p2c.post_id)";	
		}

		$sql .=" WHERE pd.language_id = '" . (int)$this->config->get('config_language_id') . "'";
		
		if(!empty($data['filter_name'])){
			$sql .= " AND pd.name LIKE '" . $this->db->escape($data['filter_name']) . "%'";
		}
		
		if (isset($data['filter_status']) && $data['filter_status'] !== null) {
			$sql .= " AND p.status = '" . (int)$data['filter_status'] . "'";
		}
		
		if(!empty($data['filter_category_id'])){
			$sql .= " AND p2c.blog_category_id = '" . (int)$data['filter_category_id'] . "'";
		}
		
		if(!empty($data['filter_from_date_added']) && !empty($data['filter_to_date_added'])){	
			$sql .=" AND DATE(p.date) BETWEEN DATE('" . $this->db->escape($data['filter_to_date_added']) . "') AND DATE('" . $this->db->escape($data['filter_from_date_added']) . "')";
		}
		
		if(!empty($data['filter_from_date_added']) && empty($data['filter_to_date_added'])){
			$sql .=" AND DATE(p.date) = DATE('" . $this->db->escape($data['filter_from_date_added']) . "')";
		}
		
		
		if(empty($data['filter_from_date_added']) && !empty($data['filter_to_date_added'])){
			$sql .=" AND DATE(p.date) = DATE('" . $this->db->escape($data['filter_to_date_added']) . "')";
		}
		
		$query = $this->db->query($sql);

		return $query->row['total'];
	}

	public function getTotalpostsByLayoutId($layout_id) {
		$query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "post_to_layout WHERE layout_id = '" . (int)$layout_id . "'");

		return $query->row['total'];
	}
}
