<?php
namespace app\http\controllers;

use app\http\routing\middleware\Throttle;
use app\models\User;
use inventor96\MakoMailer\EmailUser;
use inventor96\MakoMailer\Mailer;
use mako\gatekeeper\Gatekeeper;
use mako\gatekeeper\repositories\user\UserRepository;
use mako\http\routing\attributes\Middleware;

class Auth extends ControllerBase
{
	/**
	 * Render login page.
	 */
	public function login() {
		// no need to be here if they're already logged in
		if ($this->gatekeeper->isLoggedIn()) {
			return $this->safeRedirectResponse('dashboard:home');
		}
		return $this->view->render('Pages/Auth/Login');
	}

	#[Middleware(Throttle::class)]
	public function loginAction() {
		// no need to be here if they're already logged in
		if ($this->gatekeeper->isLoggedIn()) {
			return $this->safeRedirectResponse('dashboard:home');
		}

		// validate values
		$post = $this->getValidatedInput([
			'email' => ['required'],
			'password' => ['required'],
			'remember' => ['required', 'boolean'],
		]);

		// attempt the login
		$result = $this->gatekeeper->login($post['email'], $post['password'], !!$post['remember']);
		if ($result === true) {
			return $this->safeRedirectResponse('dashboard:home');
		} else {
			$this->session->putFlash('error', match ($result) {
				Gatekeeper::LOGIN_INCORRECT => "We don't recognize that email or password. Please try again.",
				Gatekeeper::LOGIN_ACTIVATING => 'Your account has not been activated. Please check your email for the activation link.',
				Gatekeeper::LOGIN_BANNED => 'Your account has been banned. Please contact support.',
				Gatekeeper::LOGIN_LOCKED => 'You have made too many failed login attempts. Please wait a while before trying again.',
				default => "There was an error logging you in. Please try again later.",
			});
			return $this->safeRedirectResponse('auth:login');
		}
	}

	/**
	 * Logout action.
	 */
	public function logout() {
		$this->gatekeeper->logout();
		return $this->safeRedirectResponse('auth:login');
	}

	public function signup() {
		// no need to be here if they're already logged in
		if ($this->gatekeeper->isLoggedIn()) {
			return $this->safeRedirectResponse('dashboard:home');
		}
		return $this->view->render('Pages/Auth/Signup');
	}

	public function signupAction(User $user, Mailer $mailer) {
		// no need to be here if they're already logged in
		if ($this->gatekeeper->isLoggedIn()) {
			return $this->safeRedirectResponse('dashboard:home');
		}

		// validate values
		$post = $this->getValidatedInput([
			'first_name' => ['required'],
			'last_name' => ['required'],
			'email' => ['required', 'email'],
			'password' => ['required', 'min_length(8)'],
			'confirm_password' => ['required', 'match("password")'],
		]);

		// attempt the signup
		$u = $user->createOrUpdateFrom($post, $this->gatekeeper);

		// send the welcome email
		$this->sendWelcomeEmail($u, $mailer);

		$this->session->putFlash('success', 'Your account has been created! Please check your email for the activation link.');
		return $this->safeRedirectResponse('auth:login');
	}

	protected function sendWelcomeEmail(User $user, Mailer $mailer): bool {
		$token = $user->generateActionToken();
		$user->save();
		return $mailer->sendTemplate([EmailUser::fromUser($user)], 'Welcome!', 'welcome', [
			'first_name' => $user->first_name,
			'token' => $token,
		]);
	}

	public function activate(string $token) {
		// activate the user account
		$result = $this->gatekeeper->activateUser($token);
		if ($result === true) {
			$this->session->putFlash('success', 'Your account has been activated! You can now log in.');
			return $this->safeRedirectResponse('auth:login');
		} else {
			$this->session->putFlash('error', 'Invalid activation token.');
			return $this->safeRedirectResponse('auth:login');
		}
	}

	public function forgotPassword() {
		return $this->view->render('Pages/Auth/ForgotPassword');
	}

	#[Middleware(Throttle::class)]
	public function forgotPasswordAction(Mailer $mailer) {
		// validate values
		$post = $this->getValidatedInput([
			'email' => ['required', 'email'],
		]);

		// find user
		/** @var UserRepository */
		$repo = $this->gatekeeper->getUserRepository();
		/** @var ?User */
		$user = $repo->getByEmail($post['email']);
		if ($user !== null) {
			// check for alternative account states
			if (!$user->isActivated()) {
				// send activation email
				$this->sendWelcomeEmail($user, $mailer);
			} else {
				// send reset email
				$token = $user->generateActionToken();
				$user->save();
				return $mailer->sendTemplate([EmailUser::fromUser($user)], 'Password Reset', 'forgot-password', [
					'first_name' => $user->first_name,
					'token' => $token,
				]);
			}
		}

		// go home
		$this->session->putFlash('success', 'If you have an account, you will receive an email shortly.');
		return $this->safeRedirectResponse('auth:login');
	}

	public function resetPassword(string $token) {
		return $this->view->render('Pages/Auth/ResetPassword', ['token' => $token]);
	}

	#[Middleware(Throttle::class)]
	public function resetPasswordAction(string $token) {
		// validate values
		$post = $this->getValidatedInput([
			'password' => ['required', 'min_length(8)'],
			'confirm_password' => ['required', 'match("password")'],
		]);

		// find user
		/** @var UserRepository */
		$repo = $this->gatekeeper->getUserRepository();
		/** @var ?User */
		$user = $repo->getByActionToken($token);

		// check token
		if ($user === null) {
			$this->session->putFlash('error', 'Invalid password reset token.');
			return $this->safeRedirectResponse('auth:login');
		}

		// update password
		$user->setPassword($post['password']);
		$user->generateActionToken();
		$user->save();

		$this->session->putFlash('success', "You're password has been reset! Log in now with your username and new password.");
		return $this->safeRedirectResponse('auth:login');
	}
}