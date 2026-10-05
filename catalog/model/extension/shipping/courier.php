<?php
// Порожня модель: перевізник «Кур'єр» їде через спільний двигун
// extension/shipping/delivery, окремого методу доставки не додає.
class ModelExtensionShippingCourier extends Model {
	public function getQuote($address) {
		return false;
	}
}
