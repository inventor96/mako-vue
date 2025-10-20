<?php

namespace app\migrations;

use mako\database\migrations\Migration;

class Migration_20250822020847 extends Migration
{
	/**
	 * Description.
	 */
	protected string $description = '';

	/**
	 * Makes changes to the database structure.
	 */
	public function up(): void
	{
		$this->getConnection()->query
		(<<<SQL
		CREATE TABLE `users` (
			`id` int(11) unsigned NOT NULL AUTO_INCREMENT,
			`created_at` datetime NOT NULL,
			`updated_at` datetime NOT NULL,
			`ip` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
			`username` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
			`email` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
			`first_name` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
			`last_name` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
			`password` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
			`action_token` char(64) COLLATE utf8mb4_general_ci DEFAULT '',
			`access_token` char(64) COLLATE utf8mb4_general_ci DEFAULT '',
			`activated` tinyint(1) NOT NULL DEFAULT 0,
			`banned` tinyint(1) NOT NULL DEFAULT 0,
			`failed_attempts` int(11) NOT NULL DEFAULT '0',
			`last_fail_at` datetime DEFAULT NULL,
			`locked_until` datetime DEFAULT NULL,
			PRIMARY KEY (`id`),
			UNIQUE KEY `username` (`username`),
			UNIQUE KEY `email` (`email`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
		SQL);
	}

	/**
	 * Reverts the database changes.
	 */
	public function down(): void
	{
		$this->getConnection()->query
		(<<<SQL
		DROP TABLE IF EXISTS `users`;
		SQL);
	}
}
