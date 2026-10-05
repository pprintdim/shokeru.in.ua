<?php
class ControllerCommonDevtools extends Controller {

	// Перемініфікація CSS/JS бандлів (атомарний запис)
	public function minify() {
		$json = array();

		if (!$this->user->isLogged()) {
			$json['error'] = 'Немає доступу';
			return $this->out($json);
		}

		// має збігатися з assetMin() у catalog/controller/common/header.php
		$assets = array(
			array('catalog/view/css/style.css', 'css'),
			array('catalog/view/css/animations.css', 'css'),
			array('catalog/view/js/common.js', 'js'),
		);

		$dir = DIR_IMAGE . 'cache/min/';
		if (!is_dir($dir)) {
			@mkdir($dir, 0777, true);
		}

		$report = array();

		foreach ($assets as $a) {
			list($rel, $type) = $a;
			$src = DIR_ROOT . $rel;

			if (!is_file($src)) {
				$report[] = basename($rel) . ': нема джерела';
				continue;
			}

			$content = (string)file_get_contents($src);
			$content = ($type === 'css') ? $this->minifyCss($content) : $this->minifyJs($content);

			$name = md5($rel) . '.' . $type;
			$min  = $dir . $name;
			$tmp  = $min . '.' . getmypid() . '.tmp';

			if (@file_put_contents($tmp, $content) !== false && @rename($tmp, $min)) {
				$report[] = basename($rel) . ': ' . number_format(strlen($content), 0, '.', ' ') . ' б';
			} else {
				@unlink($tmp);
				$report[] = basename($rel) . ': помилка запису';
			}
		}

		$json['success'] = 'Перемініфіковано: ' . implode(', ', $report);

		return $this->out($json);
	}

	// Очистити системний кеш (system/storage/cache)
	public function cache() {
		$json = array();

		if (!$this->user->isLogged()) {
			$json['error'] = 'Немає доступу';
			return $this->out($json);
		}

		$count = $this->clearDir(DIR_CACHE);

		$json['success'] = 'Системний кеш очищено. Видалено файлів: ' . $count;

		return $this->out($json);
	}

	private function clearDir($dir) {
		$count = 0;

		if (!is_dir($dir)) {
			return $count;
		}

		$items = glob(rtrim($dir, '/') . '/*');

		foreach ((array)$items as $item) {
			if (is_dir($item)) {
				$count += $this->clearDir($item);
				@rmdir($item);
			} elseif (is_file($item)) {
				if (basename($item) === 'index.html' || basename($item) === '.htaccess') {
					continue;
				}
				if (@unlink($item)) {
					$count++;
				}
			}
		}

		return $count;
	}

	private function minifyCss($css) {
		$css = preg_replace('!/\*.*?\*/!s', '', $css);
		$css = preg_replace('/\s+/', ' ', $css);
		$css = preg_replace('/\s*([{}:;,>~])\s*/', '$1', $css);
		$css = str_replace(';}', '}', $css);

		return trim($css);
	}

	private function minifyJs($js) {
		$lines = array();
		foreach (explode("\n", $js) as $l) {
			$t  = rtrim($l);
			$lt = ltrim($t);
			if ($lt === '' || strpos($lt, '//') === 0) {
				continue;
			}
			$lines[] = $t;
		}

		return implode("\n", $lines);
	}

	private function out($json) {
		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}
}
