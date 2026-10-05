<?php
/**
 * Телефон у єдиному вигляді для всього магазину: `+380XXXXXXXXX`.
 *
 * Фронт уже віддає міжнародний номер (intl-tel-input, поля [data-phone-input] —
 * див. catalog/view/theme/shoker/js/phone.js), але покластися лише на нього
 * не можна: форму можуть надіслати без JS, зі старого бекапу або ботом. Тому
 * той самий номер нормалізується ще раз на бекенді — у startup/phone.php,
 * який проходить по POST перед контролерами.
 */
class Phone {
	/**
	 * Локальні українські записи (`0XX…`, `380…`, `8 0XX…`) стають `+380XX…`;
	 * будь-який інший номер лишається з `+` і цифрами. Порожнє — порожнім.
	 */
	public static function normalize($value) {
		$value = trim((string)$value);

		if ($value === '') {
			return '';
		}

		$plus = (strpos($value, '+') === 0);
		$digits = preg_replace('/\D+/', '', $value);

		if ($digits === '') {
			return '';
		}

		// 8 0XX ... — старий міжміський формат
		if (strlen($digits) == 11 && substr($digits, 0, 2) == '80') {
			$digits = substr($digits, 1);
		}

		// 0XX XXX XX XX
		if (strlen($digits) == 10 && $digits[0] == '0') {
			return '+38' . $digits;
		}

		// 380XX XXX XX XX (з + або без)
		if (strlen($digits) == 12 && substr($digits, 0, 2) == '38') {
			return '+' . $digits;
		}

		// 9-значний без нуля (XX XXX XX XX)
		if (!$plus && strlen($digits) == 9) {
			return '+380' . $digits;
		}

		return '+' . $digits;
	}

	/**
	 * Чи це схоже на справжній номер: від 9 до 15 цифр (E.164), а для України —
	 * рівно 12 цифр разом із кодом країни.
	 */
	public static function valid($value) {
		$digits = preg_replace('/\D+/', '', (string)self::normalize($value));
		$length = strlen($digits);

		if (substr($digits, 0, 3) == '380') {
			return $length == 12;
		}

		return $length >= 9 && $length <= 15;
	}

	/**
	 * Для посилання `tel:` — тільки `+` і цифри.
	 */
	public static function href($value) {
		$value = self::normalize($value);

		return $value === '' ? '' : 'tel:' . $value;
	}
}
