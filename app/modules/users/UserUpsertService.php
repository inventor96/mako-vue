<?php

namespace app\modules\users;

use app\models\User;
use mako\database\exceptions\DatabaseException;
use mako\gatekeeper\adapters\Adapter;
use mako\gatekeeper\Gatekeeper;
use mako\validator\exceptions\ValidationException;

/**
 * @property array $assignable This actually exists on the User model, but we have this here to satisfy the IDE
 */
class UserUpsertService
{
	/**
	 * Fields that are assignable but should be excluded from the extras array
	 * when creating a user. This typically includes fields that are handled
	 * separately (like foreign key fields that require special processing).
	 */
	protected const EXCLUDE_FIELDS = [];

	/**
	 * @param Adapter|Gatekeeper $gatekeeper The gatekeeper instance for managing users.
	 */
	public function __construct(protected Gatekeeper $gatekeeper) {}

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
								(fn() => $this->assignable)->call($user), // `$assignable` is protected in the User model
								// these fields are handled separately by the gatekeeper instance
								['email', 'username', 'password', 'is_active', 'groups'],
							),
							self::EXCLUDE_FIELDS,
						)
					)
				);

				// TODO: excluded foreign key fields get processed here

				// create the user record
				$created = $this->gatekeeper->createUser(
					$fields['email'],
					$fields['username'] ?? $fields['email'],
					$fields['password'] ?? '',
					$fields['is_active'] ?? false,
					$extras
				);

				if (!$created instanceof User) {
					throw new \RuntimeException('Gatekeeper returned an unexpected user model type.');
				}

				$user = $created;
			}
		} catch (DatabaseException $e) {
			$username = $fields['username'] ?? '';
			if (
				$username !== '' && strpos($e->getMessage(), "Duplicate entry '{$username}' for key 'username'") !== false
			) {
				throw new ValidationException(
					['username' => "A user already exists with the username '{$username}'."],
					"A user already exists with the username '{$username}'.",
					0,
					$e
				);
			}
			$email = $fields['email'] ?? '';
			if (
				$email !== '' && (
					strpos($e->getMessage(), "Duplicate entry '{$email}' for key 'username'") !== false
					|| strpos($e->getMessage(), "Duplicate entry '{$email}' for key 'email'") !== false
				)
			) {
				throw new ValidationException(
					['email' => "A user already exists with the email '{$email}'."],
					"A user already exists with the email '{$email}'.",
					0,
					$e
				);
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
