<?php
class ModelExtensionModuleReviews extends Model {
	// Останні схвалені відгуки (status=1) по активних товарах магазину
	public function getLatest($limit) {
		$query = $this->db->query("SELECT r.review_id, r.author, r.text, r.rating, r.date_added, r.product_id, pd.name FROM `" . DB_PREFIX . "review` r INNER JOIN `" . DB_PREFIX . "product` p ON (p.product_id = r.product_id) INNER JOIN `" . DB_PREFIX . "product_description` pd ON (pd.product_id = p.product_id AND pd.language_id = '" . (int)$this->config->get('config_language_id') . "') INNER JOIN `" . DB_PREFIX . "product_to_store` p2s ON (p2s.product_id = p.product_id AND p2s.store_id = '" . (int)$this->config->get('config_store_id') . "') WHERE r.status = '1' AND p.status = '1' AND p.date_available <= NOW() AND r.rating > 0 ORDER BY r.date_added DESC, r.review_id DESC LIMIT " . (int)$limit);

		return $query->rows;
	}
}
