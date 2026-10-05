<?php
// Порожня модель: перевізник «Самовивіз» їде через спільний двигун
// extension/shipping/delivery, окремого методу доставки не додає.
class ModelExtensionShippingPickup extends Model {
	public function getQuote($address) {
		return false;
	}
}
