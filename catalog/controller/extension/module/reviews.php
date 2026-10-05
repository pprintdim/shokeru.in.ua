<?php
// Блок «Відгуки покупців» на головній: останні схвалені відгуки з oc_review
class ControllerExtensionModuleReviews extends Controller {
	public function index($setting) {
		if (isset($setting['status']) && !$setting['status']) {
			return '';
		}

		$this->load->language('extension/module/reviews');
		$this->load->model('extension/module/reviews');

		$limit = !empty($setting['limit']) ? max(1, min(24, (int)$setting['limit'])) : 6;
		$language_id = (int)$this->config->get('config_language_id');

		$data['heading'] = !empty($setting['heading'][$language_id]) ? $setting['heading'][$language_id] : $this->language->get('heading_title');
		$data['reviews'] = array();

		foreach ($this->model_extension_module_reviews->getLatest($limit) as $result) {
			$text = trim(strip_tags(html_entity_decode($result['text'], ENT_QUOTES, 'UTF-8')));

			if (utf8_strlen($text) > 240) {
				$text = rtrim(utf8_substr($text, 0, 240)) . '…';
			}

			$data['reviews'][] = array(
				'author'  => $result['author'],
				'text'    => $text,
				'rating'  => (int)$result['rating'],
				'date'    => date($this->language->get('date_format_short'), strtotime($result['date_added'])),
				'product' => $result['name'],
				'href'    => $this->url->link('product/product', 'product_id=' . (int)$result['product_id'])
			);
		}

		if (!$data['reviews']) {
			return '';
		}

		return $this->load->view('extension/module/reviews', $data);
	}
}
