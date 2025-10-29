<?php
namespace app\http\controllers;

class Dashboard extends ControllerBase
{
	/**
	 * Home page action.
	 */
	public function home()
	{
		return $this->view->render('Pages/Dashboard/home');
	}
}
