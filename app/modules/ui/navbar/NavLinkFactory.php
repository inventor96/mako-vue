<?php
namespace app\modules\ui\navbar;

use mako\http\Request;
use mako\http\routing\URLBuilder;

class NavLinkFactory {
	protected Request $request;
	protected URLBuilder $builder;

	public function __construct(Request $request, URLBuilder $builder) {
		$this->request = $request;
		$this->builder = $builder;
	}

	/**
	 * Generates a new NavLink instance.
	 *
	 * @param string $name
	 * @param string $icon
	 * @param string $path
	 * @param bool $active
	 * @return NavLink
	 */
	public function create(string $name, string $icon, string $path, bool $active): NavLink {
		return new NavLink($name, $icon, $path, $active);
	}

	/**
	 * Generates a NavLink, setting the path and active status based on the route name.
	 *
	 * @param string $name
	 * @param string $route_name
	 * @param array $params
	 * @param array $query
	 * @param string $separator
	 * @param bool $language
	 * @return NavLink
	 */
	public function createFromRoute(string $name, string $icon, string $route_name, array $params = [], array $query = [], string $separator = '&', bool $language = true): NavLink {
		return $this->create(
			$name,
			$icon,
			$this->builder->toRoute($route_name, $params, $query, $separator, $language),
			$this->request->getRoute()->getName() === $route_name
		);
	}

	/**
	 * Creates a dropdown navlink using route names.
	 *
	 * @param string $name
	 * @param array $dropdowns In the format [ ['<name>', '<route_name>', '<params>', '<query>', '<separator>', '<language>'], ... ]
	 * @return NavLink
	 */
	public function createDropdownFromRoutes(string $name, string $icon, array $dropdowns): NavLink {
		$root = new NavLink($name, $icon, '', false);
		$root->dropdown(
			...array_map(
				function($d) {
					return $this->createFromRoute(...$d);
				},
			$dropdowns)
		);
		$root->active = count(
			array_filter(
				$root->getDropdowns(),
				function(NavLink $d) {
					return $d->active;
				}
			)
		) > 0;
		return $root;
	}
}