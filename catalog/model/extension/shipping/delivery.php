<?php
class ModelExtensionShippingDelivery extends Model {
	public function getQuote($address) {
		$this->load->language('extension/shipping/delivery');

		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "zone_to_geo_zone WHERE geo_zone_id = '" . (int)$this->config->get('shipping_delivery_geo_zone_id') . "' AND country_id = '" . (int)$address['country_id'] . "' AND (zone_id = '" . (int)$address['zone_id'] . "' OR zone_id = '0')");

		if (!$this->config->get('shipping_delivery_status')) {
			$status = false;
		} elseif (!$this->config->get('shipping_delivery_geo_zone_id')) {
			$status = true;
		} elseif ($query->num_rows) {
			$status = true;
		} else {
			$status = false;
		}

		$method_data = array();

		if ($status) {
			$quote_data = array();
			$options = array(
				'novaposhta' => 'text_novaposhta',
				'ukrposhta'  => 'text_ukrposhta',
				'meest'      => 'text_meest',
				'courier'    => 'text_courier',
				'pickup'     => 'text_pickup'
			);

			foreach ($options as $code => $language_key) {
				// перевізника можна вимкнути в його картці в адмінці
				if ($this->config->get('shipping_delivery_' . $code . '_enabled') === '0') {
					continue;
				}

				$cost = $this->config->get('shipping_delivery_' . $code . '_cost');

				if ($code == 'pickup' && $cost === null) {
					$cost = 0;
				}

				$quote_data[$code] = array(
					'code'         => 'delivery.' . $code,
					'title'        => $this->language->get($language_key),
					'cost'         => $cost,
					'tax_class_id' => $this->config->get('shipping_delivery_tax_class_id'),
					'text'         => $this->currency->format($this->tax->calculate($cost, $this->config->get('shipping_delivery_tax_class_id'), $this->config->get('config_tax')), $this->session->data['currency'])
				);
			}

			$method_data = array(
				'code'       => 'delivery',
				'title'      => $this->language->get('text_title'),
				'quote'      => $quote_data,
				'sort_order' => $this->config->get('shipping_delivery_sort_order'),
				'error'      => false
			);
		}

		return $method_data;
	}
}
