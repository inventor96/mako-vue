<?php
namespace app\http\controllers;

use mako\http\response\builders\JSON;

class API extends ControllerBase
{
	/**
	 * Status endpoint for health checks.
	 */
	public function status()
	{
		// add any necessary logic here, e.g. checking database connection, etc.
		return new JSON([
			'status' => 'ok',
			'timestamp' => time(),
		]);
	}
}