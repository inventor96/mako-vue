<?php
namespace app\http\controllers;

class API extends ControllerBase
{
	/**
	 * Status endpoint for health checks.
	 */
	public function status()
	{
		// add any necessary logic here, e.g. checking database connection, etc.
		return $this->jsonResponse([
			'status' => 'ok',
			'timestamp' => time(),
		]);
	}
}