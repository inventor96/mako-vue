<?php
namespace app\http\routing\middleware;

use mako\http\Request;
use mako\http\Response;
use Closure;
use mako\gatekeeper\Gatekeeper;
use mako\http\exceptions\ForbiddenException;
use mako\http\response\senders\Redirect;
use mako\http\routing\middleware\MiddlewareInterface;
use mako\http\routing\Routes;
use mako\http\routing\URLBuilder;
use mako\session\Session;

class RequireAuth implements MiddlewareInterface {
	/**
	 * @var array An array of HTTP methods that would cause a change in the application state.
	 */
	protected const STATE_CHANGERS = [
		'POST',
		'PUT',
		'PATCH',
		'DELETE',
	];

	/**
	 * The time in milliseconds to wait before updating the original URL. This helps to prevent
	 * rapid successive updates to the original URL, which could lead to unexpected behavior
	 * (e.g. resource requests after the initial page load).
	 */
	protected const COOLDOWN_TIME = 1000;

	/**
	 * @param Gatekeeper $gatekeeper
	 * @param URLBuilder $urlBuilder
	 * @param Session|null $session Session instance or null if sessions are disabled.
	 * @param string|false $redirect Route name or URL to redirect to if unauthenticated. Set to `false` to throw a 403 Forbidden error. Defaults to `false`.
	 * @param bool $require Require authentication. Defaults to `true`.
	 * @param bool $backAfterAuth Enable functionality to redirect back to the original URL after authentication succeeds. Defaults to `true`. This applies to both the putting and getting of the original URL.
	 */
	public function __construct(
		protected Gatekeeper $gatekeeper,
		protected URLBuilder $urlBuilder,
		protected Routes $routes,
		protected ?Session $session = null,
		protected string|false $redirect = false,
		protected bool $require = true,
		protected bool $backAfterAuth = true,
	) {}

	public function execute(Request $request, Response $response, Closure $next): Response {
		// check if authentication is required
		if ($this->require && $this->gatekeeper->isGuest()) {
			// store the original URL to redirect back to after authentication if the path is part of the defined routes
			if ($this->backAfterAuth && !empty($request->getRoute())) {
				// check if we're due to update the original URL
				$last_updated = $this->session?->get('_auth_redirect_time_', 0);
				if ($last_updated + self::COOLDOWN_TIME < round(microtime(true) * 1000)) {
					// use referrer url (with current as backup) if the method is a state changer
					$this->session?->put('_auth_redirect_',
						in_array($request->getMethod(), self::STATE_CHANGERS)
							? $request->getReferrer($this->urlBuilder->current())
							: $this->urlBuilder->current()
					);
					$this->session?->put('_auth_redirect_time_', round(microtime(true) * 1000));
				}
			}

			// keep flash around
			$this->session?->reflash();

			// throw 403 Forbidden error if no redirect is set
			if ($this->redirect === false) {
				throw new ForbiddenException();
			}

			// redirect to the specified route or URL
			return $response->setBody(new Redirect(
				$this->routes->hasNamedRoute($this->redirect)
					? $this->urlBuilder->toRoute($this->redirect)
					: $this->redirect
				, Redirect::SEE_OTHER));
		}

		$response = $next($request, $response);

		// restore the original URL after authentication
		if (
			$this->backAfterAuth
			&& $this->gatekeeper->isLoggedIn()
			&& !empty($url = $this->session?->getAndRemove('_auth_redirect_'))
		) {
			// redirect to the specified route or URL
			return $response->setBody(new Redirect($url, Redirect::SEE_OTHER));
		}

		return $response;
	}
}