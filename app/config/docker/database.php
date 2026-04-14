<?php

use function mako\env;

return (function() {
	$database = require(__DIR__.'/../database.php');
	$default = $database['default'];
	$database['configurations'][$default]['dsn'] = 'mysql:dbname=' . env('MARIADB_DATABASE') . ';host=db;port=3306';
	$database['configurations'][$default]['username'] = env('MARIADB_USER');
	$database['configurations'][$default]['password'] = env('MARIADB_PASSWORD');
	$database['configurations'][$default]['log_queries'] = true;
	return $database;
})();
