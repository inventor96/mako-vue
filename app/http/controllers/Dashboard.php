<?php
namespace app\http\controllers;

class Dashboard extends ControllerBase
{
	/**
	 * Home page action.
	 */
	public function home()
	{
		return $this->safeRedirectResponse('boxes:home');
	}
}
