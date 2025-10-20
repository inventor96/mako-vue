<?php

namespace app\migrations;

use mako\database\migrations\Migration;

class Migration_20250822021015 extends Migration
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
		CREATE TABLE `groups` (
			`id` int(11) unsigned NOT NULL AUTO_INCREMENT,
			`created_at` datetime NOT NULL,
			`updated_at` datetime NOT NULL,
			`name` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
			PRIMARY KEY (`id`),
			UNIQUE KEY `name` (`name`)
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
		DROP TABLE IF EXISTS `groups`;
		SQL);
	}
}
