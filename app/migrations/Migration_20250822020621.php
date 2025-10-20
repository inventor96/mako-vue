<?php

namespace app\migrations;

use mako\database\migrations\Migration;

class Migration_20250822020621 extends Migration
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
		CREATE TABLE `mako_sessions` (
			`id` varchar(128) COLLATE utf8mb4_unicode_ci NOT NULL,
			`data` text COLLATE utf8mb4_unicode_ci NOT NULL,
			`expires` int(11) DEFAULT NULL,
			PRIMARY KEY (`id`),
			KEY `expires` (`expires`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
		SQL);
	}

	/**
	 * Reverts the database changes.
	 */
	public function down(): void
	{
		$this->getConnection()->query
		(<<<SQL
		DROP TABLE IF EXISTS `mako_sessions`;
		SQL);
	}
}
