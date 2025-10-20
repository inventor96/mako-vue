<?php
namespace app\http\controllers;

use mako\validator\exceptions\ValidationException;

class Account extends ControllerBase
{
	public function home()
	{
		return $this->view->render('Pages/Account/Home', [
			'user' => $this->getUser(),
			'delete_confirm' => $this->session->getFlash('account_delete_confirm', false),
		]);
	}

	public function updateAction()
	{
		// input validation
		$post = $this->getValidatedInput([
			'first_name' => ['required', 'string'],
			'last_name' => ['required', 'string'],
		]);

		// update user information
		$user = $this->getUser();
		$user->first_name = $post['first_name'];
		$user->last_name = $post['last_name'];
		$user->save();

		$this->session->putFlash('success', 'Account information updated successfully.');
		return $this->redirectSamePage('account:home');
	}

	public function updatePasswordAction()
	{
		// input validation
		$post = $this->getValidatedInput([
			'current_password' => ['required', 'string'],
			'new_password' => ['required', 'string', 'min_length(8)'],
			'new_password_confirmation' => ['required', 'match("new_password")'],
		]);

		// confirm current password
		$user = $this->getUser();
		if (!$user->validatePassword($post['current_password'])) {
			throw new ValidationException(['current_password' => 'Current password is incorrect.']);
		}

		// update password
		$user->setPassword($post['new_password']);
		$user->save();
		$this->session->putFlash('success', 'Password updated successfully.');
		return $this->redirectSamePage('account:home');
	}

	public function deleteAction()
	{
		// check if the user has already confirmed deletion
		$post = $this->getValidatedInput([
			'delete_confirm' => ['optional', 'in(["true", "false"])'],
		]);
		if ($post['delete_confirm'] !== 'true') {
			$this->session->putFlash('warning', 'Deleting your account is irreversable and cannot be undone once confirmed. If you are absolutely sure you want to delete your account, please click the "Delete Account" button a second time to confirm the immediate deletion of your account.');
			$this->session->putFlash('account_delete_confirm', true);
			return $this->redirectSamePage('account:home');
		}

		// delete the user's account
		$user = $this->getUser();
		$this->gatekeeper->logout();
		$user->delete();

		$this->session->putFlash('success', 'Account deleted successfully.');
		return $this->safeRedirectResponse('auth:login');
	}
}