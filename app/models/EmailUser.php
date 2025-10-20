<?php
namespace app\models;

use InvalidArgumentException;

class EmailUser {
	public ?string $name;
	public string $email;

	public function __construct(string $email, ?string $name = null) {
		if (empty($email)) {
			throw new InvalidArgumentException("Email cannot be empty");
		}
		$this->email = $email;
		$this->name = $name;
	}

	public static function fromUser(User $user): self {
		return new self($user->email, $user->first_name.' '.$user->last_name);
	}
}