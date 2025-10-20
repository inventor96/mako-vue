<?php
return (function() {
	$application = require(__DIR__.'/../application.php');
	$application['error_handler']['display_errors'] = true;
	return $application;
})();