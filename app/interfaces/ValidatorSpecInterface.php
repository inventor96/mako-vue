<?php
namespace app\interfaces;

interface ValidatorSpecInterface {
	/**
	 * Returns an associative array containing `Validator` rules for the model's fields.
	 *
	 * @return array
	 */
	public function getValidatorSpec(): array;
}