<?php
namespace app\http\routing\middleware;

use mako\http\Request;
use mako\http\Response;
use Closure;
use mako\http\routing\middleware\MiddlewareInterface;
use mako\logger\Logger;

class Throttle implements MiddlewareInterface {
	protected Logger $logger;
	protected float $seconds;

	/**
	 * @param Logger $logger
	 * @param float $seconds The minimum number of seconds before returning the response
	 */
	public function __construct(Logger $logger, float $seconds = 2) {
		$this->logger = $logger;
		$this->seconds = $seconds;
	}

	public function execute(Request $request, Response $response, Closure $next): Response {
		// note start time
		$start = microtime(true);

		// continue execution
		$response = $next($request, $response);

		// pause as necessary
		if (($diff = microtime(true) - $start) < $this->seconds) {
			usleep(intval(($this->seconds - $diff) * 1000000));
		} else if ($diff > $this->seconds) {
			// log the timing violation
			$this->logger->warning("Throttle time exceeded: {$request->getMethod()} {$request->getPath()} ({$diff} > {$this->seconds})");
		}

		return $response;
	}
}