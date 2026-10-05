<?php
/**
 * Нормалізація телефонів у вхідних даних — один раз для всього магазину.
 *
 * Поля з маскою (intl-tel-input, [data-phone-input]) надсилають уже повний
 * міжнародний номер, але форму можуть відправити без JS або напряму, тому
 * будь-який POST-ключ `telephone`/`phone` доводиться до `+380XXXXXXXXX`
 * (Phone::normalize) ще до того, як його прочитає контролер: чекаут, кабінет,
 * повернення, попап входу, «купити в один клік», API.
 */
class ControllerStartupPhone extends Controller {
	public function index() {
		if (empty($this->request->post)) {
			return;
		}

		$this->load->library('phone');

		foreach (array('telephone', 'phone') as $key) {
			if (isset($this->request->post[$key]) && is_string($this->request->post[$key])) {
				$this->request->post[$key] = Phone::normalize($this->request->post[$key]);
			}
		}
	}
}
