<?php
class ControllerCommonHome extends Controller {
	public function index() {
		$this->document->setTitle($this->config->get('config_meta_title'));
		$this->document->setDescription($this->config->get('config_meta_description'));
		$this->document->setKeywords($this->config->get('config_meta_keyword'));

		if (isset($this->request->get['route'])) {
			$this->document->addLink($this->config->get('config_url'), 'canonical');
		}

		$sale_lines = $this->config->get('config_sale_line');

		// беремо рядки для поточної мови
		$sale_line = isset($sale_lines[$this->config->get('config_language_id')]) 
		    ? $sale_lines[$this->config->get('config_language_id')] 
		    : '';

		// розбиваємо по рядках, обрізаємо пробіли і видаляємо порожні
		$data['sale_line'] = array_filter(array_map('trim', explode("\n", $sale_line)));

			
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['column_right'] = $this->load->controller('common/column_right');
		$data['content_top'] = $this->load->controller('common/content_top');
		$data['content_bottom'] = $this->load->controller('common/content_bottom');
		$data['footer'] = $this->load->controller('common/footer');

		// вітальний екран — шар поверх головної, не маршрут; див. shouldShowWelcome()
		$data['welcome_overlay'] = $this->shouldShowWelcome() ? $this->welcomeOverlay() : '';

		$data['header'] = $this->load->controller('common/header');

		$this->response->setOutput($this->load->view('common/home', $data));
	}

	/**
	 * Вітальний тизер поверх головної (як у hydrophob): прелоадер із лого вантажить ролик у кеш, ролик одразу йде
	 * приглушеним і розмитим; після відповіді на cookie-банер він грає з початку до кінця зі звуком і без розмиття,
	 * «Пропустити» — по центру внизу. Файли — з Дизайн → Вітальний екран, інакше hero-ролик слайдера.
	 */
	private function welcomeOverlay() {
		$this->load->language('common/welcome');

		foreach (array('heading_title', 'text_aria', 'text_eyebrow', 'text_lead', 'text_skip', 'text_skip_aria', 'text_sound_on', 'text_sound_off') as $key) {
			$data[$key] = $this->language->get($key);
		}

		$server = $this->request->server['HTTPS'] ? $this->config->get('config_ssl') : $this->config->get('config_url');

		$data['home'] = $this->url->link('common/home');
		$data['logo'] = $this->welcomeAsset($this->config->get('welcome_logo'), is_file(DIR_IMAGE . $this->config->get('config_logo')) ? 'image/' . $this->config->get('config_logo') : '');
		$data['video'] = $this->welcomeAsset($this->config->get('welcome_video'), 'image/catalog/hero/hero-video.mp4');

		// мобільний варіант: з адмінки; для типового ролика — його легка копія hero-video-mobile.mp4
		$data['video_mobile'] = $this->welcomeAsset($this->config->get('welcome_video_mobile'), $this->config->get('welcome_video') ? '' : 'image/catalog/hero/hero-video-mobile.mp4');
		$data['poster'] = $this->welcomeAsset($this->config->get('welcome_poster'), 'image/catalog/hero/slide.png');

		// чи є в роликах звукова доріжка (кнопка «звук» потрібна лише тоді)
		$data['has_audio'] = $this->hasAudio($data['video']);
		$data['has_audio_mobile'] = $data['video_mobile'] ? $this->hasAudio($data['video_mobile']) : $data['has_audio'];

		// ролики першого екрана головної — прогріваємо в кеш, поки йде тизер
		$data['prefetch'] = $this->welcomeHeroVideos();

		return $this->load->view('common/welcome_overlay', $data);
	}

	/**
	 * Відео hero-слайдера (модулі slideshow зі статусом «увімкнено», банери типу video): коли тизер поїде,
	 * головна вже має їх у кеші. Фолбек — типовий hero-ролик теми. Лише ті, що є на диску.
	 */
	private function welcomeHeroVideos() {
		$videos = array();

		$this->load->model('design/banner');

		$query = $this->db->query("SELECT setting FROM " . DB_PREFIX . "module WHERE code = 'slideshow' ORDER BY module_id ASC");

		foreach ($query->rows as $row) {
			$setting = json_decode($row['setting'], true);

			if (!is_array($setting) || empty($setting['status']) || empty($setting['banner_id'])) {
				continue;
			}

			foreach ($this->model_design_banner->getBanner($setting['banner_id']) as $banner) {
				if (!empty($banner['type']) && $banner['type'] === 'video') {
					foreach (array('video', 'mob_video') as $field) {
						if (!empty($banner[$field]) && is_file(DIR_IMAGE . $banner[$field])) {
							$videos[] = 'image/' . $banner[$field];
						}
					}
				}
			}
		}

		if (!$videos) {
			foreach (array('hero-video.mp4', 'hero-video-mobile.mp4') as $file) {
				if (is_file(DIR_IMAGE . 'catalog/hero/' . $file)) {
					$videos[] = 'image/catalog/hero/' . $file;
				}
			}
		}

		return array_values(array_unique($videos));
	}

	// Чи має MP4 звукову доріжку: у moov шукаємо hdlr із типом «soun». Результат кешується за шляхом і mtime.
	private function hasAudio($src) {
		$src = (string)$src;

		if ($src === '' || preg_match('~^(https?:)?//~i', $src)) {
			return false;
		}

		$file = strpos($src, 'image/') === 0 ? DIR_IMAGE . substr($src, 6) : DIR_APPLICATION . '../' . ltrim($src, '/');

		if (!is_file($file)) {
			return false;
		}

		$key = 'shokeru.welcome.audio.' . md5($file . filemtime($file));
		$cached = $this->cache->get($key);

		if ($cached !== false && $cached !== null) {
			return (bool)$cached;
		}

		$audio = (bool)preg_match('~hdlr.{8}soun~s', (string)file_get_contents($file));

		$this->cache->set($key, $audio ? 1 : 0);

		return $audio;
	}

	// значення з адмінки, якщо файл існує (медіатека image/… або шлях від кореня сайту), інакше запасний
	private function welcomeAsset($value, $fallback) {
		$value = trim((string)$value);

		if ($value !== '' && preg_match('~^(https?:)?//~i', $value)) {
			return $value;
		}

		if ($value !== '' && is_file(DIR_IMAGE . $value)) {
			return 'image/' . $value;
		}

		if ($value !== '' && is_file(DIR_APPLICATION . '../' . ltrim($value, '/'))) {
			return ltrim($value, '/');
		}

		return $fallback;
	}

	/**
	 * Політика показу як у групі hydrophob: перший візит — завжди; далі, поки живе кука (місяць), до welcome_shows
	 * показів у випадкові моменти, не частіше ніж раз на welcome_interval годин з імовірністю welcome_chance %.
	 * Ніколи для ботів і вимірювачів швидкості. ?welcome=1 примусово, ?welcome=0 ховає.
	 */
	private function shouldShowWelcome() {
		if ($this->config->has('welcome_status') && !$this->config->get('welcome_status')) {
			return false;
		}

		if ($this->isSearchBot()) {
			return false;
		}

		// 301 з /login, /register веде на головну з ?auth= — там має відкритись попап входу, а не тизер
		if (isset($this->request->get['auth'])) {
			return false;
		}

		if (isset($this->request->get['welcome'])) {
			return (bool)$this->request->get['welcome'];
		}

		$now = time();
		$month = 60 * 60 * 24 * 30;
		$raw = isset($this->request->cookie['shokeru_welcome']) ? (string)$this->request->cookie['shokeru_welcome'] : '';
		$parts = $raw !== '' ? array_map('intval', explode('|', $raw)) : array();

		$shows = isset($parts[0]) ? $parts[0] : 0;
		$last = isset($parts[1]) ? $parts[1] : 0;
		$first = isset($parts[2]) ? $parts[2] : 0;

		if ($first === 0 || $now - $first > $month) {
			$first = $now;
			$shows = 0;
		}

		$limit = (int)$this->config->get('welcome_shows') ?: 4;
		$chance = (int)$this->config->get('welcome_chance') ?: 20;
		$pause = $this->config->get('welcome_interval');
		$pause = ($pause === null || $pause === '') ? 6 : (int)$pause;

		$show = false;

		if ($shows === 0) {
			$show = true;
		} elseif ($shows < $limit && $now - $last > $pause * 3600 && mt_rand(1, 100) <= $chance) {
			$show = true;
		}

		if ($show) {
			$shows++;
			$last = $now;
		}

		$secure = isset($this->request->server['HTTPS']) && $this->request->server['HTTPS'] && $this->request->server['HTTPS'] != 'off';
		setcookie('shokeru_welcome', $shows . '|' . $last . '|' . $first, $first + $month, '/', '', $secure, true);

		return $show;
	}

	private function isSearchBot() {
		$agent = isset($this->request->server['HTTP_USER_AGENT']) ? strtolower($this->request->server['HTTP_USER_AGENT']) : '';

		if (!$agent) {
			return false;
		}

		// вимірювачі швидкості міряють головну — важкий шар зіпсував би метрики
		foreach (array('lighthouse', 'pagespeed', 'gtmetrix', 'pingdom', 'webpagetest') as $speed) {
			if (strpos($agent, $speed) !== false) {
				return true;
			}
		}

		return (bool)preg_match('/bot|crawl|spider|slurp|yandex|bingpreview|facebookexternalhit|telegrambot|whatsapp|twitterbot|linkedinbot|pinterest|embedly|quora|applebot|duckduck|semrush|ahrefs|mj12|petalbot|headlesschrome/', $agent);
	}
}
