<?php
namespace app\modules\ui\navbar;

use app\models\User;

class NavLinks {
	public function __construct(
		protected NavLinkFactory $nav,
	) {}

	/**
	 * Generate the navbar links to show on the left side of the navbar
	 *
	 * @param User|null $user The current user, or null if not logged in
	 * @return NavLink[]
	 */
	public function generateLeftLinks(?User $user): array {
		// guest
		if ($user === null) {
			return [];
		}

		// logged in
		$links = [
		];

		return $links;
	}

	/**
	 * Generates the navbar links to show on the right side of the navbar
	 *
	 * @param User|null $user The current user, or null if not logged in
	 * @return NavLink[]
	 */
	public function generateRightLinks(?User $user): array {
		// guest
		if ($user === null) {
			return [
				$this->nav->createFromRoute('Sign Up', 'bi-person-plus', 'auth:signup'),
				$this->nav->createFromRoute('Log In', 'bi-box-arrow-in-right', 'auth:login'),
			];
		}

		// logged in
		return [
			$this->nav->createFromRoute('Account', 'bi-person', 'account:home'),
			$this->nav->createFromRoute('Log Out', 'bi-box-arrow-right', 'auth:logout'),
		];
	}
}