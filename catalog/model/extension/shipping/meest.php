<?php
// Порожня модель: перевізник «Meest» їде через спільний двигун
// extension/shipping/delivery, окремого методу доставки не додає.
class ModelExtensionShippingMeest extends Model {
	public function getQuote($address) {
		return false;
	}
}
