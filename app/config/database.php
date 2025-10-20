<?php

return
[
	/*
	 * ---------------------------------------------------------
	 * Default
	 * ---------------------------------------------------------
	 *
	 * Default configuration to use.
	 */
	'default' => 'makovue',

	/*
	 * ---------------------------------------------------------
	 * Configurations
	 * ---------------------------------------------------------
	 *
	 * You can define as many database configurations as you want.
	 *
	 * dsn        : PDO data source name
	 * username   : (optional) Username of the database server
	 * password   : (optional) Password of the database server
	 * persistent : (optional) Set to true to make the connection persistent
	 * log_queries: (optional) Enable query logging?
	 * reconnect  : (optional) Should the connection automatically be reestablished?
	 * options    : (optional) An array of PDO options
	 * queries    : (optional) Queries that will be executed right after a connection has been made
	 */
	'configurations' =>
	[
		'makovue' =>
		[
			'dsn'         => 'mysql:dbname=makovue;host=127.0.0.1;port=3306',
			'username'    => 'makovue',
			'password'    => '',
			'persistent'  => false,
			'log_queries' => false,
			'reconnect'   => false,
			'queries'     =>
			[
				'SET NAMES utf8mb4',
			],
		],
	],
];
