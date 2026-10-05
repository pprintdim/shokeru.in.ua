<?php
// Підказки міст для полів доставки. Джерело — локальний довідник Нової Пошти
// (oc_np_warehouse): показуємо тільки ті населені пункти, куди реально є
// доставка. Якщо довідник ще не імпортовано — запасний список із файла.
class ControllerToolCity extends Controller {
	public function index() {
		$json = array();

		$query = isset($this->request->get['q']) ? trim((string)$this->request->get['q']) : '';

		if (utf8_strlen($query) >= 2) {
			$json = $this->fromDatabase($query);

			if (!$json) {
				$json = $this->fallback($query);
			}
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode(array_slice($json, 0, 10), JSON_UNESCAPED_UNICODE));
	}

	private function fromDatabase($query) {
		$ru = (substr((string)$this->language->get('code'), 0, 2) == 'ru'); // active language (config_language stays the store default here)

		$field = $ru ? 'city_ru' : 'city';
		$escaped = $this->db->escape($query);

		// спершу ті, що починаються із запиту, далі — де він усередині назви
		$sql = "SELECT `" . $field . "` AS name, COUNT(*) AS points,
					CASE WHEN `" . $field . "` LIKE '" . $escaped . "%' THEN 0 ELSE 1 END AS weight
				FROM " . DB_PREFIX . "np_warehouse
				WHERE `" . $field . "` LIKE '%" . $escaped . "%' AND `" . $field . "` != ''
				GROUP BY `" . $field . "`
				ORDER BY weight ASC, points DESC, name ASC
				LIMIT 10";

		$result = $this->db->query($sql);

		$cities = array();

		foreach ($result->rows as $row) {
			if (trim((string)$row['name']) !== '') {
				$cities[] = $row['name'];
			}
		}

		return $cities;
	}

	private function fallback($query) {
		$file = DIR_APPLICATION . 'data/cities.json';

		if (!is_file($file)) {
			return array();
		}

		$all = json_decode(file_get_contents($file), true);

		if (!is_array($all)) {
			return array();
		}

		$query = utf8_strtolower($query);
		$starts = array();
		$contains = array();

		foreach ($all as $city) {
			$lower = utf8_strtolower($city);

			if (strpos($lower, $query) === 0) {
				$starts[] = $city;
			} elseif (strpos($lower, $query) !== false) {
				$contains[] = $city;
			}
		}

		return array_merge($starts, $contains);
	}
}
