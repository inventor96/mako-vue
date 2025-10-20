<?php

use app\http\controllers\Account;
use app\http\controllers\Auth;
use app\http\controllers\Dashboard;
use app\http\controllers\Fonts;
use app\http\routing\middleware\RequireAuth;
use mako\http\routing\Routes;

/** @var \mako\http\routing\Routes $routes */
/** @var \mako\application\Application $app */
/** @var \mako\syringe\Container $container */

$routes->group([
	'patterns' => [
	],
], function (Routes $routes) {
	// no auth requirement
	$routes->group([
		'middleware' => [
			[RequireAuth::class, ['require' => false]],
		],
	], function (Routes $routes) {
		$routes->get('/assets/fonts/{font}', [Fonts::class, 'fonts'], 'fonts:fonts');

		#region authentication
		$routes->get('/login', [Auth::class, 'login'], 'auth:login');
		$routes->post('/login', [Auth::class, 'loginAction'], 'auth:loginAction');
		$routes->get('/logout', [Auth::class, 'logout'], 'auth:logout');
		$routes->get('/signup', [Auth::class, 'signup'], 'auth:signup');
		$routes->post('/signup', [Auth::class, 'signupAction'], 'auth:signupAction');
		$routes->get('/activate/{token}', [Auth::class, 'activate'], 'auth:activate');
		$routes->get('/forgot', [Auth::class, 'forgotPassword'], 'auth:forgotPassword');
		$routes->post('/forgot', [Auth::class, 'forgotPasswordAction'], 'auth:forgotPasswordAction');
		$routes->get('/reset/{token}', [Auth::class, 'resetPassword'], 'auth:resetPassword');
		$routes->post('/reset/{token}', [Auth::class, 'resetPasswordAction'], 'auth:resetPasswordAction');
		#endregion
	});
	
	#region dashboard
	$routes->get('/', [Dashboard::class, 'home'], 'dashboard:home');
	#endregion
	
	#region account
	$routes->get('/account', [Account::class, 'home'], 'account:home');
	$routes->put('/account', [Account::class, 'updateAction'], 'account:updateAction');
	$routes->post('/account/password', [Account::class, 'updatePasswordAction'], 'account:updatePasswordAction');
	$routes->delete('/account', [Account::class, 'deleteAction'], 'account:deleteAction');
	#endregion
});
