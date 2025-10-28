<?php
namespace app\models;

use app\traits\AssignRequireTrait;
use app\traits\AutoIdRelationTrait;
use app\traits\OrmInstanceGetTrait;
use InvalidArgumentException;
use inventor96\MakoMailer\interfaces\EmailUserInterface;
use mako\database\midgard\ResultSet;
use mako\chrono\Time;
use mako\database\exceptions\DatabaseException;
use mako\gatekeeper\adapters\Adapter;
use mako\gatekeeper\entities\group\Group;
use mako\gatekeeper\entities\user\User as GatekeeperUser;
use mako\gatekeeper\Gatekeeper;
use mako\validator\exceptions\ValidationException;

/**
 * @property int $id
 * @property Time $created_at
 * @property Time $updated_at
 * @property string $ip
 * @property string $username
 * @property string $email
 * @property string $password In hashed form
 * @property string $first_name
 * @property string $last_name
 * @property string $action_token
 * @property string $access_token
 * @property int $activated
 * @property int $banned
 * @property int $failed_attempts
 * @property ?Time $last_fail_at
 * @property ?Time $locked_until
 * @property Group[]|ResultSet $groups
 */
class User extends GatekeeperUser implements ValidatorSpecInterface, EmailUserInterface {
	use AutoIdRelationTrait;
	use AssignRequireTrait;
	use OrmInstanceGetTrait;

	protected array $cast = [
		'last_fail_at' => 'date',
		'locked_until' => 'date',
	];

	protected array $assignable = [
		'username',
		'email',
		'password',
		'first_name',
		'last_name',
	];

	protected array $required_fields = [
		'username',
		'email',
		'first_name',
		'last_name',
	];

	protected array $protected = [
		'created_at',
		'updated_at',
		'ip',
		'username',
		'password',
		'action_token',
		'access_token',
		'activated',
		'banned',
		'failed_attempts',
		'last_fail_at',
		'locked_until',
	];

	/**
	 * @inheritDoc
	 * @codeCoverageIgnore
	 */
	public function getValidatorSpec(): array {
		return [
			'first_name' => ['required'],
			'last_name' => ['required'],
			'email' => ['required', 'email'],
		];
	}

	public function getName(): ?string
	{
		return trim("{$this->first_name} {$this->last_name}") ?: null;
	}

	public function getEmail(): string
	{
		return $this->email;
	}

	/**
	 * Updates the user from the fields in the associative array.
	 *
	 * @param array $fields
	 * @param Gatekeeper|Adapter|null $gatekeeper
	 * @return self
	 */
	public function createOrUpdateFrom(array $fields, $gatekeeper = null): self {
		$user = null;
		try {
			// check if it's new or existing
			if ($this->isPersisted()) {
				// update the fields
				$this->requireAndAssign($fields);
				$this->save();
				$user = $this;
			} else {
				// requirements
				if ($gatekeeper === null) {
					throw new InvalidArgumentException("Missing gatekeeper instance");
				}
				$this->requireFields($fields, true);

				// build extras
				$extras = array_intersect_key(
					$fields,
					array_flip(
						array_merge(
							array_diff(
								$this->assignable,
								['email', 'username', 'password'], // assignable fields to exclude
							),
							[], // foreign key fields to exclude
						)
					)
				);

				// excluded foreign key fields get processed here

				// create a new user
				$user = $gatekeeper->createUser($fields['email'], $fields['email'], $fields['password'] ?? '', false, $extras);
			}
			/** @var self $user */
		} catch (DatabaseException $e) {
			// convert exceptions as needed
			if (strpos($e->getMessage(), "Duplicate entry '{$fields['email']}' for key 'username'") !== false) {
				throw new ValidationException(['email' => "A user already exists with the email '{$fields['email']}'."], '', 0, $e);
			} elseif (strpos($e->getMessage(), "Duplicate entry '{$fields['email']}' for key 'email'") !== false) {
				throw new ValidationException(['email' => "A user already exists with the email '{$fields['email']}'."], '', 0, $e);
			}
			throw $e;
		}

		// process groups
		if (isset($fields['groups'])) {
			// only ints
			$fields['groups'] = array_filter(array_map('intval', $fields['groups'] ?? []));

			// remove them from groups that aren't selected
			$current_groups = [];
			if ($user->groups()->count() > 0) {
				foreach ($user->groups as $group) {
					if (!in_array($group->id, $fields['groups'])) {
						$group->removeUser($user);
					} else {
						$current_groups[] = $group->id;
					}
				}
			}

			// add user to selected groups
			foreach ($fields['groups'] as $group_id) {
				if (!in_array($group_id, $current_groups)) {
					$group = Group::getInstanceOrThrow($group_id);
					$group->addUser($user);
				}
			}
		}

		return $user;
	}
}