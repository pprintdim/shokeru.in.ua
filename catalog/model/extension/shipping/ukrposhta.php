<?php
// Порожня модель: перевізник «Укрпошта» їде через спільний двигун
// extension/shipping/delivery, окремого методу доставки не додає.
class ModelExtensionShippingUkrposhta extends Model {
	public function getQuote($address) {
		return false;
	}
}
