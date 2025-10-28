<?php
namespace app\modules\ui\navbar;

class NavLink {
	public string $name;
	public string $icon;
	public string $path;
	public bool $active;

	/** @var self[] */
	public array $dropdowns = [];

	public function __construct(string $name, string $icon, string $path, bool $active) {
		$this->name = $name;
		$this->icon = $icon;
		$this->path = $path;
		$this->active = $active;
	}

	/**
	 * Sets up this navlink as a dropdown with the given sub-navlinks
	 *
	 * @param self ...$dropdowns
	 * @return self
	 */
	public function dropdown(self ...$dropdowns): self {
		$this->dropdowns = array_merge($this->dropdowns, $dropdowns);
		return $this;
	}

	/**
	 * Returns true if this navlink is the parent of a group of dropdown navlinks
	 *
	 * @return boolean
	 */
	public function isDropdown(): bool {
		return count($this->dropdowns) > 0;
	}

	/**
	 * Gets the sub-navlinks of this parent navlink. Returns an empty array if this is not a dropdown parent.
	 *
	 * @return self[]
	 */
	public function getDropdowns(): array {
		return $this->dropdowns;
	}
}