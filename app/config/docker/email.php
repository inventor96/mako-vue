<?php
return (function() {
	$email = require(__DIR__.'/../email.php');
	$email['host'] = 'mailpit';
	$email['port'] = 1025;
	return $email;
})();