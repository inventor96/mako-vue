<?php
namespace app\traits;

use mako\database\midgard\relations\BelongsTo;
use mako\database\midgard\relations\Relation;

trait AutoIdRelationTrait {
	/**
	 * Assigns the column values to the model, converting relations to IDs.
	 *
	 * @param array $columns Column values
	 * @param bool $raw Set raw values?
	 * @param bool $whitelist Remove columns that are not in the whitelist?
	 * @return static
	 */
	public function assign(array $columns, bool $raw = false, bool $whitelist = true): static {
		// check for columns that use a targeted relation
		foreach ($columns as $col => $val) {
			/** @var Relation $rel */
			if (method_exists($this, $col) && is_a(($rel = call_user_func([$this, $col])), BelongsTo::class)) {
				// set the table column
				unset($columns[$col]);
				$new_col = (fn() => $this->getForeignKey())->call($rel);
				$columns[$new_col] = is_a($val, (fn() => $this->modelClass)->call($rel)) ? $val->id : $val; // assume the value is the id if it's not the related object

				// update assignables
				if (isset($this->assignable) && !in_array($new_col, $this->assignable)) {
					$this->assignable[] = $new_col;
				}
			}
		}

		// resume normal functionality
		parent::assign($columns, $raw, $whitelist);
		return $this;
	}
}