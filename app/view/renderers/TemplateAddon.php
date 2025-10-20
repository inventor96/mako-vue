<?php
namespace app\view\renderers;

use app\view\compilers\TemplateAddon as CompilersTemplateAddon;
use DateTime;
use mako\application\Application;
use mako\config\Config;
use mako\file\FileSystem;
use mako\http\routing\Routes;
use mako\http\routing\URLBuilder;
use mako\session\Session;
use mako\view\renderers\Template;

class TemplateAddon extends Template {
	protected URLBuilder $builder;
	protected Session $session;
	protected Config $config;
	protected Routes $routes;

	public function __construct(FileSystem $fs, Application $app, URLBuilder $builder, Session $session, Config $config, Routes $routes) {
		// path is the same one used in the ViewFactoryService
		parent::__construct($fs, "{$app->getStoragePath()}/cache/views");
		$this->builder = $builder;
		$this->session = $session;
		$this->config = $config;
		$this->routes = $routes;
	}

	/**
	 * Override to use our template add-ons
	 *
	 * @param string $view
	 * @return void
	 * 
	 * @codeCoverageIgnore
	 */
	protected function compile(string $view): void {
		(new CompilersTemplateAddon($this->fileSystem, $this->cachePath, $view))->compile();
	}

	/**
	 * Replace route name and params to generate URL
	 *
	 * @param string $route_name
	 * @param array $params
	 * @return string
	 */
	protected function genRoute(string $route_name, array $params = []): string {
		return $this->builder->toRoute($route_name, $params);
	}

	/**
	 * Formats a DateTime object to a humanly-readable string.
	 *
	 * @param DateTime|Time|string|null $obj
	 * @param string $empty_replacement If $obj is empty, what replacement string should be returned instead.
	 * @return string
	 */
	protected function dateDisplay($obj, string $empty_replacement = '---'): string {
		// empty replacement
		if ($obj === null) {
			return $empty_replacement;
		}

		// convert it
		if (!is_a($obj, DateTime::class)) {
			$obj = new DateTime($obj);
		}
		return $obj->format(DT_FMT_DISPLAY);
	}
}