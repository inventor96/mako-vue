CREATE DATABASE boxbeacon CHARSET utf8mb4 COLLATE utf8mb4_general_ci;
CREATE USER 'boxbeacon'@'%' IDENTIFIED BY 'localtest';
GRANT ALL PRIVILEGES ON boxbeacon.* TO 'boxbeacon'@'%';
USE boxbeacon;
CREATE TABLE `mako_migrations`
(
	`batch` int(10) unsigned NOT NULL,
	`package` varchar(255) DEFAULT NULL,
	`version` varchar(255) NOT NULL
);