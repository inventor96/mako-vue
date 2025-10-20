<?php
namespace app\traits;

use mako\validator\exceptions\ValidationException;

/**
 * @property array $required_fields
 */
trait AssignRequireTrait {
	/**
	 * Makes sure that fields have content if they're set in the input and part of the required set.
	 *
	 * @param array $input
	 * @param bool $only_set Check only the fields that are present in `$input`. Set to false to require all fields even if they're not in `$input`.
	 * @param array|null $fields An array of fields to require. Defaults to `$this->required_fields`.
	 * @return void
	 * @throws ValidationException
	 */
	public function requireFields(array $input, bool $only_set = true, ?array $fields = null): void {
		$msgs = [];
		foreach ($fields ?? $this->required_fields as $field) {
			// require field
			if (method_exists($this, $field) // allow *_id field if it's a relation
				? ((array_key_exists($field, $input) || array_key_exists($field.'_id', $input) || !$only_set) && empty($input[$field]) && empty($input[$field.'_id']))
				: (array_key_exists($field, $input) || !$only_set) && empty($input[$field])
			) {
				$msgs[] = "The {$field} field cannot be empty.";
			}
		}
		if (!empty($msgs)) {
			throw new ValidationException($msgs);
		}
	}

	/**
	 * Checks for field requirements and then assigns the column values to the model.
	 *
	 * @param array $columns Column values
	 * @param bool|null $only_set Check only the fields that are present in `$input`. Defaults to `$this->isPersisted()`. Set to false to require all fields even if they're not in `$input`.
	 * @param array|null $fields An array of fields to require. Defaults to `$this->required_fields`.
	 * @param bool $raw Set raw values?
	 * @param bool $whitelist Remove columns that are not in the whitelist?
	 * @return static
	 */
	public function requireAndAssign(array $columns, ?bool $only_set = null, ?array $fields = null, bool $raw = false, bool $whitelist = true): self {
		$this->requireFields($columns, $only_set ?? $this->isPersisted(), $fields);
		$this->assign($columns, $raw, $whitelist);
		return $this;
	}
}