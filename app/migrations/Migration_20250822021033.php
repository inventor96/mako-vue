<?php

namespace app\migrations;

use mako\database\migrations\Migration;

class Migration_20250822021033 extends Migration
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
		CREATE TABLE `groups_users` (
			`group_id` int(11) unsigned NOT NULL,
			`user_id` int(11) unsigned NOT NULL,
			UNIQUE KEY `group_user` (`group_id`,`user_id`),
			KEY `group_id` (`group_id`),
			KEY `user_id` (`user_id`),
			CONSTRAINT `groups`
				FOREIGN KEY (`group_id`)
				REFERENCES `groups` (`id`)
				ON DELETE CASCADE ON UPDATE NO ACTION,
			CONSTRAINT `users`
				FOREIGN KEY (`user_id`)
				REFERENCES `users` (`id`)
				ON DELETE CASCADE ON UPDATE NO ACTION
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
		DROP TABLE IF EXISTS `groups_users`;
		SQL);
	}
}
