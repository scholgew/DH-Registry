-- --------------------------------------------------------
-- Host:                         127.0.0.1
-- Server Version:               5.6.24 - MySQL Community Server (GPL)
-- Server Betriebssystem:        Win32
-- HeidiSQL Version:             9.3.0.4984
-- --------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8mb4 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;

-- Exportiere Struktur von Tabelle users
CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_role_id` int(11) NOT NULL DEFAULT '3',
  `email` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `email_verified` tinyint(1) NOT NULL DEFAULT '0',
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `approved` tinyint(1) NOT NULL DEFAULT '0',
  `is_admin` tinyint(1) NOT NULL DEFAULT '0',
  `last_login` datetime DEFAULT NULL,
  `password_token` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `email_token` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `approval_token` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `new_email` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `password_token_expires` datetime DEFAULT NULL,
  `email_token_expires` datetime DEFAULT NULL,
  `approval_token_expires` datetime DEFAULT NULL,
  `last_name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `first_name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `created` datetime DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_email` (`email`),
  KEY `email` (`email`),
  KEY `email_reset_token` (`email_token`),
  KEY `password_reset_token` (`password_token`),
  KEY `FK_users_user_roles` (`user_role_id`),
  CONSTRAINT `FK_users_user_roles` FOREIGN KEY (`user_role_id`) REFERENCES `user_roles` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=105 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- Exportiere Daten aus Tabelle users: ~67 rows (ungefähr)
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` (`id`, `user_role_id`, `email`, `password`, `email_verified`, `active`, `approved`, `is_admin`, `last_login`, `password_token`, `email_token`, `approval_token`, `new_email`, `password_token_expires`, `email_token_expires`, `approval_token_expires`, `last_name`, `first_name`, `created`, `modified`) VALUES
	(1, 1, 'admin@example.com', 'password_hash_value', 1, 1, 1, 1, '2014-10-10 13:00:10', '', NULL, NULL, NULL, NULL, NULL, NULL, 'Admin', 'Armin', '2014-07-24 12:49:03', '2014-10-10 13:00:10');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;


-- Exportiere Struktur von Tabelle user_roles
CREATE TABLE IF NOT EXISTS `user_roles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8;

-- Exportiere Daten aus Tabelle user_roles: ~3 rows (ungefähr)
/*!40000 ALTER TABLE `user_roles` DISABLE KEYS */;
INSERT INTO `user_roles` (`id`, `name`) VALUES
	(1, 'Administrator'),
	(2, 'Moderator'),
	(3, 'Contributor');
/*!40000 ALTER TABLE `user_roles` ENABLE KEYS */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IF(@OLD_FOREIGN_KEY_CHECKS IS NULL, 1, @OLD_FOREIGN_KEY_CHECKS) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
