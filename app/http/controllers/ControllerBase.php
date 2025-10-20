<?php
namespace app\http\controllers;

use app\interfaces\ValidatorSpecInterface;
use app\models\NavLinkFactory;
use app\models\NavLinks;
use app\models\User;
use InvalidArgumentException;
use mako\application\Application;
use mako\gatekeeper\adapters\Session as GKSession;
use mako\gatekeeper\authorization\http\routing\traits\AuthorizationTrait;
use mako\http\Request;
use mako\http\response\senders\Redirect;
use mako\http\routing\Controller;
use mako\session\Session;
use mako\validator\ValidatorFactory;
use mako\validator\exceptions\ValidationException;
use mako\validator\Validator;
use mako\view\ViewFactory;

/**
 * @property GKSession $gatekeeper
 */
abstract class ControllerBase extends Controller {
	use AuthorizationTrait;

	public function __construct(
		protected ViewFactory $view,
		protected ValidatorFactory $validator,
		protected Request $request,
		Application $app, Session $session, NavLinkFactory $nav
	) {
		// shared data
		$this->view->autoAssign('*', function() use ($app, $nav, $session) {
			$navlinks = new NavLinks($nav, $this->getUser());
			$time = (string)$this->request->server->get('REQUEST_TIME_FLOAT');
			$success = $session->getFlash('success');
			$warning = $session->getFlash('warning');
			$error = $session->getFlash('error');
			return [
				'_env' => $app->getEnvironment(),
				'_left_navlinks' => $navlinks->generateLeftLinks(),
				'_right_navlinks' => $navlinks->generateRightLinks(),
				'_container_success' => $success ? [$time => $success] : [],
				'_container_warning' => $warning ? [$time => $warning] : [],
				'_container_error' => $error ? [$time => $error] : [],
			];
		});
	}

	/**
	 * Returns the current user
	 *
	 * @return User|null
	 */
	protected function getUser(): ?User {
		return $this->gatekeeper->getUser();
	}

	/**
	 * Same thing as `redirectResponse()`, but using the 303 status code so the browser should use the GET method when accessing the target URL.
	 * This is most applicable after processing (successfully or unsuccessfully) a state-changing request, like those that use the POST or DELETE methods.
	 *
	 * @param string $location Location
	 * @param array $routeParams Route parameters
	 * @param array $queryParams Associative array used to build URL-encoded query string
	 * @param string $separator Argument separator
	 * @param boolean $language Request language
	 * @return Redirect
	 */
	protected function safeRedirectResponse(string $location, array $routeParams = [], array $queryParams = [], string $separator = '&', $language = true): Redirect {
		return $this->redirectResponse($location, $routeParams, $queryParams, $separator, $language)->seeOther();
	}

	/**
	 * Causes the browser to refresh/redirect to the page that initiated the request.
	 *
	 * @param mixed $default The default value to use if no referrer is found.
	 * @return Redirect
	 */
	protected function redirectSamePage(string $fallbackLocation, array $routeParams = [], array $queryParams = [], string $separator = '&', $language = true): Redirect {
		// use referrer if available
		if (!empty($location = $this->request->getReferrer())) {
			return $this->redirectResponse($location)->seeOther();
		}

		// require fallback location
		if (empty($fallbackLocation)) {
			throw new InvalidArgumentException("A fallback location is required if no referrer is found.");
		}

		// use fallback
		return $this->redirectResponse($fallbackLocation, $routeParams, $queryParams, $separator, $language)->seeOther();
	}

	/**
	 * Fetches and validates the request's input. The input is pulled from the request's POST fields by default.
	 *
	 * @param array|ValidatorSpecInterface $rules The list of rules to validate against, or an object implementing the `ValidatorSpecInterface`.
	 * @param array|null $input The input to check. Defaults to the request's POST fields.
	 * @param callable|null $add_rules A callback function to enable the use of the `Validator::addRulesIf()` method.
	 *     The callback should accept two parameters: the `Validator` instance, and the array of input.
	 * @return array The validated input.
	 * @throws ValidationException
	 * @see https://makoframework.com/docs/8.1/learn-more:validation
	 */
	protected function getValidatedInput($rules, ?array $input = null, ?callable $add_rules = null): array {
		// rules requirements
		if (is_a($rules, ValidatorSpecInterface::class)) {
			$rules = $rules->getValidatorSpec();
		} elseif (!is_array($rules)) {
			throw new InvalidArgumentException("rules needs to be an array, or object implementing ValidatorSpecInterface");
		}

		// input override
		if ($input === null) {
			$input = $this->request->getData()->all();
		}

		// create the validator instance
		$v = $this->validator->create($input, $rules);

		// run customizations
		if ($add_rules !== null && is_callable($add_rules)) {
			$add_rules($v, $input);
		}

		// throw a ValidationException or return the values
		return $v->getValidatedInput();
	}

	/**
	 * Adds validator rules to a `Validator` instance from an object that implements the `ValidatorSpecInterface`.
	 * This method is intended to be used inside the `$add_rules` callback function for the `ControllerBase::getValidatedInput()` method.
	 *
	 * @param Validator $v
	 * @param ValidatorSpecInterface $obj
	 * @return Validator
	 */
	protected function addRulesFromObject(Validator $v, ValidatorSpecInterface $obj): Validator {
		$spec = $obj->getValidatorSpec();
		foreach ($spec as $key => $rules) {
			$v->addRules($key, $rules);
		}
		return $v;
	}
}