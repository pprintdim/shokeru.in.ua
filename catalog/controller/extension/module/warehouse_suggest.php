<?php
// Підказки відділень і поштоматів. Довідник Нової Пошти лежить локально
// в oc_np_warehouse (імпорт разовий, оновлюється скриптом), тож підказки
// не смикають зовнішнє API на кожен набраний символ.
class ControllerExtensionModuleWarehouseSuggest extends Controller {
	public function index() {
		$city = trim((string)($this->request->get['city'] ?? ''));
		$q = trim((string)($this->request->get['q'] ?? ''));
		$type = trim((string)($this->request->get['type'] ?? ''));

		$items = array();

		// перевізник приходить кодом методу доставки: delivery.novaposhta → novaposhta
		$carrier = trim((string)($this->request->get['carrier'] ?? 'novaposhta'));
		$carrier = in_array($carrier, array('novaposhta', 'meest')) ? $carrier : 'novaposhta';

		if ($city !== '') {
			// Meest у списковому виклику віддає лише номер і тип — адреси
			// добираємо ліниво, для міста, яке реально запитали, і зберігаємо
			if ($carrier == 'meest') {
				$this->fillMeestAddresses($city);
			}

			$items = $this->fromDatabase($city, $type, $q, $carrier);

			if (!$items) {
				$items = $this->fallback($city, $type, $q);
			}
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode(array('items' => $items), JSON_UNESCAPED_UNICODE));
	}

	private function fromDatabase($city, $type, $q, $carrier = 'novaposhta') {
		// у полі може стояти повне представлення НП («м. Київ, Київська обл.»)
		$city_name = trim((string)preg_replace('/^(м\.|с\.|смт\.?)\s*/ui', '', explode(',', $city)[0]));

		if (utf8_strlen($city_name) < 2) {
			return array();
		}

		$ru = (substr((string)$this->language->get('code'), 0, 2) == 'ru'); // active language (config_language stays the store default here)

		$name_field = $ru ? 'description_ru' : 'description';
		$city_field = $ru ? 'city_ru' : 'city';

		$sql = "SELECT `" . $name_field . "` AS name, `description` AS name_ua FROM " . DB_PREFIX . "np_warehouse
				WHERE `carrier` = '" . $this->db->escape($carrier) . "'
				  AND (`city` = '" . $this->db->escape($city_name) . "' OR `city_ru` = '" . $this->db->escape($city_name) . "')";

		if ($type == 'postomat' || $type == 'branch') {
			$sql .= " AND `type` = '" . $this->db->escape($type) . "'";
		}

		if ($q !== '') {
			$sql .= " AND (`description` LIKE '%" . $this->db->escape($q) . "%' OR `description_ru` LIKE '%" . $this->db->escape($q) . "%')";
		}

		// «Відділення №2» має стояти перед «№12» — сортуємо за номером
		$sql .= " ORDER BY CAST(`number` AS UNSIGNED), `description` LIMIT 100";

		$query = $this->db->query($sql);

		$items = array();

		foreach ($query->rows as $row) {
			$name = trim((string)$row['name']);

			if ($name === '') {
				$name = trim((string)$row['name_ua']);
			}

			if ($name !== '') {
				$items[] = $name;
			}
		}

		return $items;
	}

	// Добір адрес Meest для одного міста. Робимо не більше кількох звернень
	// за раз: публічне API Meest швидко відповідає 429 на серії запитів.
	private function fillMeestAddresses($city) {
		$city_name = trim((string)preg_replace('/^(м\.|с\.|смт\.?)\s*/ui', '', explode(',', $city)[0]));

		if (utf8_strlen($city_name) < 2) {
			return;
		}

		$query = $this->db->query("SELECT np_warehouse_id, ref, description FROM " . DB_PREFIX . "np_warehouse
			WHERE carrier = 'meest'
			  AND (`city` = '" . $this->db->escape($city_name) . "' OR `city_ru` = '" . $this->db->escape($city_name) . "')
			  AND ref <> '' AND description NOT LIKE '%: %'
			LIMIT 12");

		if (!$query->num_rows) {
			return;
		}

		foreach ($query->rows as $row) {
			$curl = curl_init('https://publicapi.meest.com/branches/' . rawurlencode($row['ref']));
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($curl, CURLOPT_TIMEOUT, 4);
			$out = curl_exec($curl);
			$code = (int)curl_getinfo($curl, CURLINFO_HTTP_CODE);
			curl_close($curl);

			if ($code !== 200) {
				break;   // ліміт або збій — спробуємо наступного разу
			}

			$data = json_decode((string)$out, true);
			$item = isset($data['result'][0]) ? $data['result'][0] : (isset($data['result']) && is_array($data['result']) ? $data['result'] : null);

			if (!is_array($item)) {
				continue;
			}

			$parts = array();

			foreach (array('street', 'building', 'addr', 'address_more') as $field) {
				$value = isset($item[$field]) ? $item[$field] : '';
				$value = is_array($value) ? (isset($value['ua']) ? $value['ua'] : '') : (string)$value;
				$value = trim($value);

				if ($value !== '' && !in_array($value, $parts)) {
					$parts[] = $value;
				}
			}

			if (!$parts) {
				continue;
			}

			$label = $row['description'] . ': ' . implode(', ', $parts);

			$this->db->query("UPDATE " . DB_PREFIX . "np_warehouse
				SET description = '" . $this->db->escape($label) . "',
					description_ru = '" . $this->db->escape($label) . "'
				WHERE np_warehouse_id = " . (int)$row['np_warehouse_id']);
		}
	}

	// запасний список із файла — на випадок, якщо довідник ще не імпортовано
	private function fallback($city, $type, $q) {
		static $data = null;

		if ($data === null) {
			$file = DIR_APPLICATION . 'data/warehouses.json';
			$json = is_file($file) ? file_get_contents($file) : false;
			$data = $json !== false ? (json_decode($json, true) ?? array()) : array();
		}

		$items = $data[$city] ?? array();

		if ($type == 'postomat' || $type == 'branch') {
			$items = array_filter($items, static function($item) use ($type) {
				$is_postomat = mb_stripos($item, 'оштомат') !== false;

				return $type == 'postomat' ? $is_postomat : !$is_postomat;
			});
		}

		if ($q !== '') {
			$items = array_filter($items, static function($item) use ($q) {
				return mb_stripos($item, $q) !== false;
			});
		}

		return array_values($items);
	}
}
