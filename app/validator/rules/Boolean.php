<?php
namespace app\validator\rules;

use mako\validator\rules\I18nAwareInterface;
use mako\validator\rules\RuleInterface;
use mako\validator\rules\traits\I18nAwareTrait;

class Boolean implements RuleInterface, I18nAwareInterface {
	use I18nAwareTrait;

	/**
	 * {@inheritDoc}
	 */
	public function validateWhenEmpty(): bool {
		return true;
	}

	/**
	 * {@inheritDoc}
	 */
	public function validate($value, string $field, array $input): bool {
		return in_array($value, [
			'1', 1, true,
			'0', 0, false,
		], true);
	}

	/**
	 * {@inheritDoc}
	 */
	public function getErrorMessage(string $field): string {
		return "The value of '{$field}' must be boolean.";
	}
}