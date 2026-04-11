<?php
namespace app\models;

use app\traits\AssignRequireTrait;
use app\traits\AutoIdRelationTrait;
use app\traits\OrmInstanceGetTrait;
use inventor96\MakoMailer\interfaces\EmailUserInterface;
use mako\database\midgard\ResultSet;
use mako\chrono\Time;
use mako\gatekeeper\entities\group\Group;
use mako\gatekeeper\entities\user\User as GatekeeperUser;

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
	 * Updates this persisted user instance from the provided fields.
	 */
	public function updateFrom(array $fields): self {
		$this->requireAndAssign($fields);
		$this->save();

		return $this;
	}

	/**
	 * Resolves a group entity by id.
	 *
	 * The primary use of this wrapper is to provide a seam for cleaner unit tests
	 * without requiring static-call replacement hacks.
	 *
	 * @codeCoverageIgnore
	 */
	protected function resolveGroupById(int $groupId): Group {
		return Group::getInstanceOrThrow($groupId);
	}

	/**
	 * Syncs user group memberships to the provided group id list.
	 */
	public function syncGroups(array $groupIds): self {
		$groupIds = array_filter(array_map('intval', $groupIds));

		// remove from groups that aren't in the new list
		$currentGroupIds = [];
		if ($this->groups()->count() > 0) {
			foreach ($this->groups as $group) {
				if (!in_array($group->id, $groupIds)) {
					$group->removeUser($this);
				} else {
					$currentGroupIds[] = $group->id;
				}
			}
		}

		// add to groups that aren't in the current list
		foreach ($groupIds as $groupId) {
			if (!in_array($groupId, $currentGroupIds)) {
				$group = $this->resolveGroupById($groupId);
				$group->addUser($this);
			}
		}

		return $this;
	}
}