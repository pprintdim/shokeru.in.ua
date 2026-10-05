<?php
/**
 * Cookie-банер (як на hydrophob): показується раз, вибір памʼятається в localStorage і в куці.
 * Аналітика (gtag, Consent Mode) отримує згоду лише після «Прийняти всі».
 * На головній відповідь на банер — ще й жест, після якого welcome-відео грає зі звуком.
 * Логіка — js/features.js ([data-cookie-banner]); підключення — common/footer.
 */
class ControllerCommonCookie extends Controller {
	public function index() {
		$this->load->language('common/cookie');

		foreach (array('text_title', 'text_message', 'text_more', 'button_necessary', 'button_accept') as $key) {
			$data[$key] = $this->language->get($key);
		}

		// політика конфіденційності — за назвою інфо-сторінки, щоб не зашивати id
		$data['privacy_href'] = '';

		$this->load->model('catalog/information');

		foreach ($this->model_catalog_information->getInformations() as $information) {
			$title = utf8_strtolower($information['title']);

			if (utf8_strpos($title, 'конфіденц') !== false || utf8_strpos($title, 'конфиденц') !== false || utf8_strpos($title, 'privacy') !== false) {
				$data['privacy_href'] = $this->url->link('information/information', 'information_id=' . (int)$information['information_id']);
				break;
			}
		}

		return $this->load->view('common/cookie', $data);
	}
}
