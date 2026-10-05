<?php
// Порожня модель: перевізник «Нова Пошта» їде через спільний двигун
// extension/shipping/delivery, окремого методу доставки не додає.
class ModelExtensionShippingNovaposhta extends Model {
	public function getQuote($address) {
		return false;
	}
}
