<?php

namespace app\modules\users;

use app\models\User;
use mako\database\exceptions\DatabaseException;
use mako\gatekeeper\adapters\Adapter;
use mako\validator\exceptions\ValidationException;

class UserUpsertService
{
	/**
	 * Fields that are assignable but should be excluded from the extras array
	 * when creating a user. This typically includes fields that are handled
	 * separately (like foreign key fields that require special processing).
	 */
	protected const EXCLUDE_FIELDS = [];

	public function __construct(protected Adapter $gatekeeper) {}

	/**
	 * Create or update a user record.
	 *
	 * @param User $user The user model to create or update. If the ID is
	 *     present and valid, it will attempt to update; otherwise, it will
	 *     create a new user.
	 * @param array $fields An associative array of fields to set on the user.
	 *     This should include 'email', 'password' (for creation), and any
	 *     other relevant fields.
	 * @return User The created or updated user model.
	 * @throws ValidationException If validation fails (e.g., email already exists).
	 * @throws DatabaseException If there is a database error during creation or update.
	 */
	public function createOrUpdateFrom(User $user, array $fields): User
	{
		try {
			if ($user->isPersisted()) {
				// update existing user
				$user->updateFrom($fields);
			} else {
				// check required fields for creation
				$user->requireFields($fields, true);

				// build extras
				$extras = array_intersect_key(
					$fields,
					array_flip(
						array_merge(
							array_diff(
								$user->assignable,
								// these fields are handled by the gatekeeper
								['email', 'username', 'password'],
							),
							self::EXCLUDE_FIELDS,
						)
					)
				);

				// TODO: excluded foreign key fields get processed here

				// create the user record
				$created = $this->gatekeeper->createUser(
					$fields['email'],
					$fields['email'],
					$fields['password'] ?? '',
					false,
					$extras
				);

				if (!$created instanceof User) {
					throw new \RuntimeException('Gatekeeper returned an unexpected user model type.');
				}

				$user = $created;
			}
		} catch (DatabaseException $e) {
			$email = $fields['email'] ?? '';
			if (
				$email !== '' && (
					strpos($e->getMessage(), "Duplicate entry '{$email}' for key 'username'") !== false
					|| strpos($e->getMessage(), "Duplicate entry '{$email}' for key 'email'") !== false
				)
			) {
				throw new ValidationException(['email' => "A user already exists with the email '{$email}'."], '', 0, $e);
			}

			throw $e;
		}

		// process groups
		if (isset($fields['groups'])) {
			$user->syncGroups($fields['groups'] ?? []);
		}

		return $user;
	}
}
