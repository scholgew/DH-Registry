-- phpMyAdmin SQL Dump
-- version 3.2.4
-- http://www.phpmyadmin.net
--
-- Host: 127.0.0.1
-- Erstellungszeit: 23. September 2012 um 16:20
-- Server Version: 5.1.41
-- PHP-Version: 5.3.1

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;


-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `menus`
--

CREATE TABLE IF NOT EXISTS `menus` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `role_id` int(10) DEFAULT NULL,
  `parent_id` int(10) DEFAULT NULL,
  `lft` int(10) DEFAULT NULL,
  `rght` int(10) DEFAULT NULL,
  `menu_type_id` int(10) NOT NULL,
  `title` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `path` varchar(200) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `context` (`role_id`,`path`),
  KEY `parent_id` (`parent_id`),
  KEY `type_id` (`menu_type_id`),
  KEY `path` (`path`),
  KEY `role_id` (`role_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=9 ;

--
-- Daten für Tabelle `menus`
--

INSERT INTO `menus` (`id`, `role_id`, `parent_id`, `lft`, `rght`, `menu_type_id`, `title`, `path`) VALUES
(1, NULL, NULL, 1, 2, 1, 'JahrgÃ¤nge', '/years/index'),
(2, NULL, NULL, 3, 4, 1, 'Rennen', '/races/index'),
(3, NULL, NULL, 5, 6, 1, 'Cupwertung', '/years/results'),
(4, 1, NULL, 7, 8, 1, 'JahrgÃ¤nge', '/admin/years/index'),
(5, 1, NULL, 9, 10, 1, 'Rennen', '/admin/races/index'),
(6, 1, NULL, 11, 12, 1, 'Cupwertung', '/admin/years/results'),
(7, NULL, NULL, 13, 14, 3, 'Ergebnisse', '/races/results'),
(8, 1, NULL, 15, 16, 1, 'Add Result', '/admin/competitors_races/add_many');

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `menu_types`
--

CREATE TABLE IF NOT EXISTS `menu_types` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `name` varchar(20) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=4 ;

--
-- Daten für Tabelle `menu_types`
--

INSERT INTO `menu_types` (`id`, `name`) VALUES
(1, 'main'),
(2, 'context'),
(3, 'action');

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
