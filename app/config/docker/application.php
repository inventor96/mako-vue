<?php
return (function() {
	$application = require(__DIR__.'/../application.php');

	// get the IP address of the 'caddy' service
	$ip = gethostbyname('caddy');
	$application['trusted_proxies'] = ($ip && $ip !== 'caddy' && filter_var($ip, FILTER_VALIDATE_IP)) ? [$ip] : [];

	$application['error_handler']['display_errors'] = true;

	return $application;
})();