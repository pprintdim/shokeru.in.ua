<?php
class ControllerStartupSeoUrl extends Controller {
	public function index() {
		// Add rewrite to url class
		if ($this->config->get('config_seo_url')) {
			$this->url->addRewrite($this);

			// 301-канонікалізація: index.php?route=X → ЧПУ (лише GET, не AJAX, і лише якщо є слаг)
			if (!isset($this->request->get['_route_'])
				&& isset($this->request->get['route'])
				&& (($this->request->server['REQUEST_METHOD'] ?? 'GET') === 'GET')
				&& empty($this->request->server['HTTP_X_REQUESTED_WITH'])) {

				$route = (string)$this->request->get['route'];
				$params = $this->request->get;
				unset($params['route'], $params['_route_']);

				$link = $this->url->link($route, http_build_query($params));

				// якщо лінк став ЧПУ (без index.php) — це сторінка зі слагом → редірект
				if (strpos($link, 'index.php') === false) {
					$this->response->redirect(str_replace('&amp;', '&', $link), 301);
				}
			}
		}

		// Знято зі сторінок: вхід і реєстрація — попап у шапці, тож старі слаги (і слаги з БД) 301 на головну,
		// яка відкриває попап з ?auth= (js/features.js)
		if (isset($this->request->get['_route_'])) {
			$retired = array('login' => 'login', 'register' => 'register', 'forgot-password' => 'login', 'forgotten' => 'login', 'vhod' => 'login', 'registraciya' => 'register');
			$slug = trim((string)$this->request->get['_route_'], '/');

			if (isset($retired[$slug])) {
				$this->response->redirect($this->url->link('common/home', 'auth=' . $retired[$slug]), 301);
			}
		}

		// Decode URL
		if (isset($this->request->get['_route_'])) {
			$parts = explode('/', $this->request->get['_route_']);

			// remove any empty arrays from trailing
			if (utf8_strlen(end($parts)) == 0) {
				array_pop($parts);
			}

			// SEO-сортування: слаг → sort + order
			$sort_seo = array(
				'name-az'   => array('pd.name', 'ASC'),
				'name-za'   => array('pd.name', 'DESC'),
				'cheap'     => array('p.price', 'ASC'),
				'expensive' => array('p.price', 'DESC'),
				'popular'   => array('rating', 'DESC'),
				'rating-low'=> array('rating', 'ASC'),
			);

			foreach ($parts as $part) {
				if (isset($sort_seo[$part])) {
					$this->request->get['sort']  = $sort_seo[$part][0];
					$this->request->get['order'] = $sort_seo[$part][1];
					continue;
				}

				$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "seo_url WHERE keyword = '" . $this->db->escape($part) . "' AND store_id = '" . (int)$this->config->get('config_store_id') . "'");

				if ($query->num_rows) {
					$url = explode('=', $query->row['query']);

					if ($url[0] == 'product_id') {
						$this->request->get['product_id'] = $url[1];
					}

					if ($url[0] == 'category_id') {
						if (!isset($this->request->get['path'])) {
							$this->request->get['path'] = $url[1];
						} else {
							$this->request->get['path'] .= '_' . $url[1];
						}
					}

					if ($url[0] == 'manufacturer_id') {
						$this->request->get['manufacturer_id'] = $url[1];
					}

					if ($url[0] == 'information_id') {
						$this->request->get['information_id'] = $url[1];
					}

					if ($url[0] == 'filter') {
						if (empty($this->request->get['filter'])) {
							$this->request->get['filter'] = $url[1];
						} else {
							$this->request->get['filter'] .= ',' . $url[1];
						}
					}

					// Блог: /blog/post-slug та /blog/cat-slug
					if ($url[0] == 'post_id') {
						$this->request->get['post_id'] = $url[1];
					}

					if ($url[0] == 'blog_category_id') {
						$this->request->get['bpath'] = $url[1];
					}

					if ($query->row['query'] && $url[0] != 'information_id' && $url[0] != 'manufacturer_id' && $url[0] != 'category_id' && $url[0] != 'product_id' && $url[0] != 'filter' && $url[0] != 'post_id' && $url[0] != 'blog_category_id') {
						$this->request->get['route'] = $query->row['query'];
					}
				} else {
					$this->request->get['route'] = 'error/not_found';

					break;
				}
			}

			// Блог: пост має пріоритет над route=extension/blog (від сегмента 'blog')
			if (isset($this->request->get['post_id'])) {
				$this->request->get['route'] = 'extension/post';
			}

			if (!isset($this->request->get['route'])) {
				if (isset($this->request->get['product_id'])) {
					$this->request->get['route'] = 'product/product';
				} elseif (isset($this->request->get['path'])) {
					$this->request->get['route'] = 'product/category';
				} elseif (isset($this->request->get['manufacturer_id'])) {
					$this->request->get['route'] = 'product/manufacturer/info';
				} elseif (isset($this->request->get['bpath'])) {
					$this->request->get['route'] = 'extension/blog';
				} elseif (isset($this->request->get['information_id'])) {
					$this->request->get['route'] = 'information/information';
				}
			}
		}
	}

	public function rewrite($link) {
		$url_info = parse_url(str_replace('&amp;', '&', $link));

		$url = '';

		$data = array();

		parse_str($url_info['query'], $data);

		// Custom: common/home → корінь домену (/)
		if (isset($data['route']) && $data['route'] == 'common/home' && !isset($data['product_id']) && !isset($data['path']) && !isset($data['manufacturer_id']) && !isset($data['information_id'])) {
			unset($data['route']);
			$base = $url_info['scheme'] . '://' . $url_info['host'] . (isset($url_info['port']) ? ':' . $url_info['port'] : '') . rtrim(str_replace('/index.php', '', $url_info['path']), '/') . '/';
			$query = '';
			if ($data) {
				foreach ($data as $key => $value) {
					$query .= '&' . rawurlencode((string)$key) . '=' . rawurlencode((is_array($value) ? http_build_query($value) : (string)$value));
				}
				if ($query) { $query = '?' . str_replace('&', '&amp;', trim($query, '&')); }
			}
			return $base . $query;
		}

		// Блог: /blog/post-slug і /blog/cat-slug
		if (isset($data['route']) && ($data['route'] == 'extension/post' || $data['route'] == 'extension/blog')) {
			$bq = '';

			if ($data['route'] == 'extension/post' && isset($data['post_id'])) {
				$bq = 'post_id=' . (int)$data['post_id'];
				unset($data['post_id']);
			} elseif ($data['route'] == 'extension/blog' && isset($data['bpath'])) {
				$bq = 'blog_category_id=' . (int)$data['bpath'];
				unset($data['bpath']);
			}

			if ($bq) {
				$kq = $this->db->query("SELECT keyword FROM " . DB_PREFIX . "seo_url WHERE `query` = '" . $this->db->escape($bq) . "' AND store_id = '" . (int)$this->config->get('config_store_id') . "' LIMIT 1");

				if ($kq->num_rows && $kq->row['keyword']) {
					unset($data['route']);

					$query = '';
					if ($data) {
						foreach ($data as $key => $value) {
							$query .= '&' . rawurlencode((string)$key) . '=' . rawurlencode((is_array($value) ? http_build_query($value) : (string)$value));
						}
						if ($query) { $query = '?' . str_replace('&', '&amp;', trim($query, '&')); }
					}

					return $url_info['scheme'] . '://' . $url_info['host'] . (isset($url_info['port']) ? ':' . $url_info['port'] : '') . str_replace('/index.php', '', $url_info['path']) . '/blog/' . $kq->row['keyword'] . $query;
				}
			}
		}

		// Custom: ЧПУ для route-сторінок без сутності (product/shop, information/about тощо)
		if (isset($data['route']) && !isset($data['product_id']) && !isset($data['path']) && !isset($data['manufacturer_id']) && !isset($data['information_id'])) {
			$rq = $this->db->query("SELECT keyword FROM " . DB_PREFIX . "seo_url WHERE `query` = '" . $this->db->escape($data['route']) . "' AND store_id = '" . (int)$this->config->get('config_store_id') . "' AND language_id = '" . (int)$this->config->get('config_language_id') . "' LIMIT 1");

			if ($rq->num_rows && $rq->row['keyword']) {
				unset($data['route']);

				$fpath = '';

				if (!empty($data['filter'])) {
					$okall = true;
					foreach (explode(',', (string)$data['filter']) as $fid) {
						$fq = $this->db->query("SELECT keyword FROM " . DB_PREFIX . "seo_url WHERE `query` = 'filter=" . (int)$fid . "' AND store_id = '" . (int)$this->config->get('config_store_id') . "' AND language_id = '" . (int)$this->config->get('config_language_id') . "' LIMIT 1");
						if ($fq->num_rows && $fq->row['keyword']) {
							$fpath .= '/' . $fq->row['keyword'];
						} else {
							$okall = false;
							break;
						}
					}
					if ($okall && $fpath !== '') { unset($data['filter']); } else { $fpath = ''; }
				}

				$query = '';

				if ($data) {
					foreach ($data as $key => $value) {
						$query .= '&' . rawurlencode((string)$key) . '=' . rawurlencode((is_array($value) ? http_build_query($value) : (string)$value));
					}

					if ($query) {
						$query = '?' . str_replace('&', '&amp;', trim($query, '&'));
					}
				}

				return $url_info['scheme'] . '://' . $url_info['host'] . (isset($url_info['port']) ? ':' . $url_info['port'] : '') . str_replace('/index.php', '', $url_info['path']) . '/' . $rq->row['keyword'] . $fpath . $query;
			}
		}

		foreach ($data as $key => $value) {
			if (isset($data['route'])) {
				if (($data['route'] == 'product/product' && $key == 'product_id') || (($data['route'] == 'product/manufacturer/info' || $data['route'] == 'product/product') && $key == 'manufacturer_id') || ($data['route'] == 'information/information' && $key == 'information_id')) {
					$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "seo_url WHERE `query` = '" . $this->db->escape($key . '=' . (int)$value) . "' AND store_id = '" . (int)$this->config->get('config_store_id') . "' AND language_id = '" . (int)$this->config->get('config_language_id') . "'");

					if ($query->num_rows && $query->row['keyword']) {
						$url .= '/' . $query->row['keyword'];

						unset($data[$key]);
					}
				} elseif ($key == 'path') {
					$categories = explode('_', $value);

					foreach ($categories as $category) {
						$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "seo_url WHERE `query` = 'category_id=" . (int)$category . "' AND store_id = '" . (int)$this->config->get('config_store_id') . "' AND language_id = '" . (int)$this->config->get('config_language_id') . "'");

						if ($query->num_rows && $query->row['keyword']) {
							$url .= '/' . $query->row['keyword'];
						} else {
							$url = '';

							break;
						}
					}

					unset($data[$key]);
				} elseif ($key == 'filter' && $data['route'] != 'product/product' && $data['route'] != 'product/manufacturer/info') {
					$filters = explode(',', (string)$value);
					$fpath = '';
					$okall = true;

					foreach ($filters as $fid) {
						$query = $this->db->query("SELECT keyword FROM " . DB_PREFIX . "seo_url WHERE `query` = 'filter=" . (int)$fid . "' AND store_id = '" . (int)$this->config->get('config_store_id') . "' AND language_id = '" . (int)$this->config->get('config_language_id') . "' LIMIT 1");

						if ($query->num_rows && $query->row['keyword']) {
							$fpath .= '/' . $query->row['keyword'];
						} else {
							$okall = false;
							break;
						}
					}

					if ($okall && $fpath !== '') {
						$url .= $fpath;
						unset($data['filter']);
					}
				}
			}
		}

		if ($url) {
			unset($data['route']);

			$query = '';

			if ($data) {
				foreach ($data as $key => $value) {
					$query .= '&' . rawurlencode((string)$key) . '=' . rawurlencode((is_array($value) ? http_build_query($value) : (string)$value));
				}

				if ($query) {
					$query = '?' . str_replace('&', '&amp;', trim($query, '&'));
				}
			}

			return $url_info['scheme'] . '://' . $url_info['host'] . (isset($url_info['port']) ? ':' . $url_info['port'] : '') . str_replace('/index.php', '', $url_info['path']) . $url . $query;
		} else {
			return $link;
		}
	}
}
