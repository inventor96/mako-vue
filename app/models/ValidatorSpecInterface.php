<?php
namespace app\models;

interface ValidatorSpecInterface {
	/**
	 * Returns an associative array containing `Validator` rules for the model's fields.
	 *
	 * @return array
	 */
	public function getValidatorSpec(): array;
}