<?php
namespace app\traits;

use mako\database\exceptions\NotFoundException;

/**
 * Mimics the `ORM::get()` and `ORM::getOrThrow()` static methods, making them available as instance methods.
 * 
 * @codeCoverageIgnore
 */
trait OrmInstanceGetTrait {
	/**
	 * Returns a record using the value of its primary key.
	 *
	 * @param mixed $id Primary key
	 * @param array $columns Columns to select
	 * @return static|null
	 */
	public function getInstance($id, array $columns = []) {
		return static::get($id, $columns);
	}

	/**
	 * Returns a record using the value of its primary key.
	 *
	 * @param mixed $id Primary key
	 * @param array $columns Columns to select
	 * @param string $exception Exception class
	 * @return static
	 */
	public function getInstanceOrThrow($id, array $columns = [], string $exception = NotFoundException::class) {
		return static::getOrThrow($id, $columns, $exception);
	}
}