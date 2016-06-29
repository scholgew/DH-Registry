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

-- Exportiere Struktur von Tabelle cc_development.cc_config_actions
DROP TABLE IF EXISTS `cc_config_actions`;
CREATE TABLE IF NOT EXISTS `cc_config_actions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cc_config_table_id` int(11) NOT NULL,
  `position` int(3) DEFAULT NULL,
  `show` tinyint(1) NOT NULL DEFAULT '1' COMMENT 'To disable an action without deleting it with it''s whole subtree you may just uncheck this box.',
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `controller` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'If this action belongs to a foreign controller, mention the controller here.',
  `label` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `comment` text COLLATE utf8_unicode_ci,
  `contextual` tinyint(1) NOT NULL DEFAULT '1' COMMENT 'Makes clear if the action belongs into a record''s context or not. If yes, the record identifier will be appended, if bulk_processing ability is false. If not, it will also appear in more general areas such as the main menu or on top of an index view.',
  `has_form` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'If the view contains a form, option lists will be generated from related models.',
  `bulk_processing` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'If the action is contextual, if it accepts a POSTed array of record IDs for bulk processing or not.',
  `has_view` tinyint(1) NOT NULL DEFAULT '1' COMMENT 'If the action does not have a view, it cannot have child-actions that appear in the view. ',
  PRIMARY KEY (`id`),
  KEY `FK_cc_config_actions_cc_config_tables` (`cc_config_table_id`),
  CONSTRAINT `FK_cc_config_actions_cc_config_tables` FOREIGN KEY (`cc_config_table_id`) REFERENCES `cc_config_tables` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=104 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- Exportiere Daten aus Tabelle cc_development.cc_config_actions: ~80 rows (ungefähr)
/*!40000 ALTER TABLE `cc_config_actions` DISABLE KEYS */;
INSERT INTO `cc_config_actions` (`id`, `cc_config_table_id`, `position`, `show`, `name`, `controller`, `label`, `comment`, `contextual`, `has_form`, `bulk_processing`, `has_view`) VALUES
	(1, 2, 6, 1, 'add', NULL, 'Clone', NULL, 1, 1, 0, 0),
	(2, 2, 7, 1, 'update_tables', NULL, 'Update Tables', NULL, 1, 0, 1, 0),
	(3, 2, 8, 1, 'tidy_tables', NULL, 'Tidy Tables', NULL, 1, 0, 1, 0),
	(4, 2, 1, 1, 'index', '', 'Index', '', 0, 0, 0, 1),
	(5, 2, 2, 1, 'edit', NULL, 'Edit', NULL, 1, 1, 0, 1),
	(6, 2, 4, 1, 'view', NULL, 'View', NULL, 1, 0, 0, 1),
	(9, 2, 3, 1, 'add', NULL, 'Add', 'Standard CRUD add.', 0, 1, 0, 1),
	(21, 5, 3, 1, 'tidy_actions', '', 'Tidy Actions', '', 1, 0, 1, 0),
	(23, 5, 6, 1, 'index', NULL, 'Index', NULL, 0, 0, 0, 1),
	(24, 5, 7, 1, 'add', NULL, 'Add', NULL, 0, 0, 0, 1),
	(25, 5, 8, 1, 'edit', '', 'Edit', '', 1, 1, 0, 1),
	(26, 5, 9, 1, 'view', NULL, 'View', NULL, 1, 0, 0, 1),
	(27, 5, 10, 1, 'delete', '', 'Delete', '', 1, 0, 1, 0),
	(29, 2, 5, 1, 'delete', NULL, 'Delete', NULL, 1, 0, 1, 0),
	(30, 5, 2, 1, 'store_actions', '', 'Read Actions', '', 1, 0, 1, 0),
	(32, 5, 5, 1, 'tidy_relations', '', 'Tidy Relations', '', 1, 0, 1, 0),
	(33, 1, 3, 1, 'edit', NULL, 'Edit', NULL, 1, 1, 0, 1),
	(34, 1, 1, 1, 'index', NULL, 'Index', NULL, 0, 0, 0, 1),
	(35, 1, 2, 1, 'add', NULL, 'Add', NULL, 0, 1, 0, 1),
	(36, 1, 4, 1, 'view', NULL, 'View', NULL, 1, 0, 0, 1),
	(37, 1, 5, 1, 'delete', NULL, 'Delete', NULL, 1, 0, 1, 0),
	(38, 10, 1, 1, 'index', NULL, 'Index', '', 0, 0, 0, 1),
	(39, 10, 2, 1, 'add', NULL, 'Add', 'Standard CRUD add.', 0, 1, 0, 1),
	(40, 10, 3, 1, 'add', NULL, 'Clone', NULL, 1, 1, 0, 0),
	(41, 10, 4, 1, 'update_tables', NULL, 'Update Tables', NULL, 1, 0, 1, 0),
	(42, 10, 5, 1, 'tidy_tables', NULL, 'Tidy Tables', NULL, 1, 0, 1, 0),
	(43, 10, 6, 1, 'edit', NULL, 'Edit', NULL, 1, 1, 0, 1),
	(44, 10, 7, 1, 'view', NULL, 'View', NULL, 1, 0, 0, 1),
	(45, 10, 8, 1, 'store_tables', NULL, 'Store Tables', NULL, 1, 0, 0, 0),
	(46, 10, 9, 1, 'delete', NULL, 'Delete', NULL, 1, 0, 1, 0),
	(47, 11, 1, 1, 'update_actions', NULL, 'Update Actions', NULL, 1, 0, 0, 0),
	(48, 11, 2, 1, 'tidy_actions', NULL, 'Tidy Actions', NULL, 1, 0, 0, 0),
	(49, 11, 3, 1, 'update_relations', NULL, 'Update Relations', NULL, 1, 0, 0, 0),
	(50, 11, 4, 1, 'index', NULL, 'Index', NULL, 0, 0, 0, 1),
	(51, 11, 5, 1, 'add', NULL, 'Add', NULL, 0, 0, 0, 1),
	(52, 11, 6, 1, 'edit', '', 'Edit', '', 1, 1, 0, 1),
	(53, 11, 7, 1, 'view', NULL, 'View', NULL, 1, 0, 0, 1),
	(54, 11, 8, 1, 'delete', NULL, 'Delete', NULL, 1, 0, 0, 0),
	(55, 11, 9, 1, 'read_actions', NULL, 'Read Actions', NULL, 1, 0, 0, 0),
	(56, 11, 10, 1, 'store_relations', NULL, 'Store Relations', NULL, 1, 0, 0, 0),
	(57, 11, 11, 1, 'tidy_relations', NULL, 'Tidy Relations', NULL, 1, 0, 0, 0),
	(58, 12, 1, 1, 'edit', NULL, 'Edit', NULL, 1, 1, 0, 1),
	(59, 12, 2, 1, 'index', NULL, 'Index', NULL, 0, 0, 0, 1),
	(60, 12, 3, 1, 'add', NULL, 'Add', NULL, 0, 1, 0, 1),
	(61, 12, 4, 1, 'view', NULL, 'View', NULL, 1, 0, 0, 1),
	(62, 12, 5, 1, 'delete', NULL, 'Delete', NULL, 1, 0, 1, 0),
	(63, 19, 1, 1, 'index', NULL, 'Index', '', 0, 0, 0, 1),
	(64, 19, 2, 1, 'add', NULL, 'Add', 'Standard CRUD add.', 0, 1, 0, 1),
	(65, 19, 3, 1, 'add', NULL, 'Clone', NULL, 1, 1, 0, 0),
	(66, 19, 4, 1, 'update_tables', NULL, 'Update Tables', NULL, 1, 0, 1, 0),
	(67, 19, 5, 1, 'tidy_tables', NULL, 'Tidy Tables', NULL, 1, 0, 1, 0),
	(68, 19, 6, 1, 'edit', NULL, 'Edit', NULL, 1, 1, 0, 1),
	(69, 19, 7, 1, 'view', NULL, 'View', NULL, 1, 0, 0, 1),
	(70, 19, 8, 1, 'store_tables', NULL, 'Store Tables', NULL, 1, 0, 0, 0),
	(71, 19, 9, 1, 'delete', NULL, 'Delete', NULL, 1, 0, 1, 0),
	(72, 20, 1, 1, 'update_actions', NULL, 'Update Actions', NULL, 1, 0, 0, 0),
	(73, 20, 2, 1, 'tidy_actions', NULL, 'Tidy Actions', NULL, 1, 0, 0, 0),
	(74, 20, 3, 1, 'update_relations', NULL, 'Update Relations', NULL, 1, 0, 0, 0),
	(75, 20, 4, 1, 'index', NULL, 'Index', NULL, 0, 0, 0, 1),
	(76, 20, 5, 1, 'add', NULL, 'Add', NULL, 0, 0, 0, 1),
	(77, 20, 6, 1, 'edit', '', 'Edit', '', 1, 1, 0, 1),
	(78, 20, 7, 1, 'view', NULL, 'View', NULL, 1, 0, 0, 1),
	(79, 20, 8, 1, 'delete', NULL, 'Delete', NULL, 1, 0, 0, 0),
	(80, 20, 9, 1, 'read_actions', NULL, 'Read Actions', NULL, 1, 0, 0, 0),
	(81, 20, 10, 1, 'store_relations', NULL, 'Store Relations', NULL, 1, 0, 0, 0),
	(82, 20, 11, 1, 'tidy_relations', NULL, 'Tidy Relations', NULL, 1, 0, 0, 0),
	(83, 21, 1, 1, 'edit', NULL, 'Edit', NULL, 1, 1, 0, 1),
	(84, 21, 2, 1, 'index', NULL, 'Index', NULL, 0, 0, 0, 1),
	(85, 21, 3, 1, 'add', NULL, 'Add', NULL, 0, 1, 0, 1),
	(86, 21, 4, 1, 'view', NULL, 'View', NULL, 1, 0, 0, 1),
	(87, 21, 5, 1, 'delete', NULL, 'Delete', NULL, 1, 0, 1, 0),
	(88, 28, 1, 1, 'fix_order', '', 'Fix Order', '', 0, 0, 0, 0),
	(89, 28, 2, 1, 'add', NULL, 'Add', NULL, 0, 1, 0, 1),
	(90, 28, 3, 1, 'edit', NULL, 'Edit', NULL, 1, 1, 0, 1),
	(91, 28, 4, 1, 'index', NULL, 'Index', NULL, 0, 0, 0, 1),
	(92, 28, 5, 1, 'view', NULL, 'View', NULL, 1, 0, 0, 1),
	(93, 28, 6, 1, 'delete', NULL, 'Delete', NULL, 1, 0, 1, 0),
	(94, 5, 4, 1, 'store_relations', '', 'Store Relations', '', 1, 0, 1, 0),
	(101, 5, 11, 1, 'fix_order', '', 'Fix Order', 'Position-Fix on actual tables', 1, 0, 1, 0),
	(103, 5, 1, 1, 'fix_order', '', 'Fix Order', 'Fix-Order of the table-table!', 0, 0, 0, 0);
/*!40000 ALTER TABLE `cc_config_actions` ENABLE KEYS */;


-- Exportiere Struktur von Tabelle cc_development.cc_config_actions_views
DROP TABLE IF EXISTS `cc_config_actions_views`;
CREATE TABLE IF NOT EXISTS `cc_config_actions_views` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `position` int(3) NOT NULL,
  `parent_action_id` int(11) NOT NULL,
  `child_action_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `FK_cc_config_actions_views_cc_config_actions` (`parent_action_id`),
  KEY `FK_cc_config_actions_views_cc_config_actions_2` (`child_action_id`),
  CONSTRAINT `FK_cc_config_actions_views_cc_config_actions` FOREIGN KEY (`parent_action_id`) REFERENCES `cc_config_actions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `FK_cc_config_actions_views_cc_config_actions_2` FOREIGN KEY (`child_action_id`) REFERENCES `cc_config_actions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- Exportiere Daten aus Tabelle cc_development.cc_config_actions_views: ~4 rows (ungefähr)
/*!40000 ALTER TABLE `cc_config_actions_views` DISABLE KEYS */;
INSERT INTO `cc_config_actions_views` (`id`, `position`, `parent_action_id`, `child_action_id`) VALUES
	(8, 1, 4, 1),
	(12, 4, 4, 9),
	(13, 3, 4, 5),
	(14, 2, 4, 6);
/*!40000 ALTER TABLE `cc_config_actions_views` ENABLE KEYS */;


-- Exportiere Struktur von Tabelle cc_development.cc_config_configurations
DROP TABLE IF EXISTS `cc_config_configurations`;
CREATE TABLE IF NOT EXISTS `cc_config_configurations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `key` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `value` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- Exportiere Daten aus Tabelle cc_development.cc_config_configurations: ~7 rows (ungefähr)
/*!40000 ALTER TABLE `cc_config_configurations` DISABLE KEYS */;
INSERT INTO `cc_config_configurations` (`id`, `key`, `value`) VALUES
	(1, 'caching', '0'),
	(2, 'debugging', '2'),
	(3, 'page_title', 'CakeClient'),
	(4, 'description', NULL),
	(5, 'robots', 'noindex, nofollow'),
	(6, 'logo_image', '/cakeclient/img/logo.png'),
	(7, 'logo_url', NULL);
/*!40000 ALTER TABLE `cc_config_configurations` ENABLE KEYS */;


-- Exportiere Struktur von Tabelle cc_development.cc_config_displayedrelations
DROP TABLE IF EXISTS `cc_config_displayedrelations`;
CREATE TABLE IF NOT EXISTS `cc_config_displayedrelations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cc_config_table_id` int(11) NOT NULL,
  `visible` tinyint(1) NOT NULL DEFAULT '1',
  `position` int(3) NOT NULL,
  `type` varchar(255) COLLATE utf8_unicode_ci NOT NULL COMMENT 'association type',
  `label` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `classname` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `tablename` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `foreign_key` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `primary_key` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'id' COMMENT 'The name of the primary key of the related model.',
  PRIMARY KEY (`id`),
  KEY `visisble` (`visible`),
  KEY `FK_cc_config_displayedrelations_cc_config_tables` (`cc_config_table_id`),
  CONSTRAINT `FK_cc_config_displayedrelations_cc_config_tables` FOREIGN KEY (`cc_config_table_id`) REFERENCES `cc_config_tables` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- Exportiere Daten aus Tabelle cc_development.cc_config_displayedrelations: ~4 rows (ungefähr)
/*!40000 ALTER TABLE `cc_config_displayedrelations` DISABLE KEYS */;
INSERT INTO `cc_config_displayedrelations` (`id`, `cc_config_table_id`, `visible`, `position`, `type`, `label`, `classname`, `tablename`, `foreign_key`, `primary_key`) VALUES
	(1, 5, 1, 1, 'belongsTo', 'Configuration', 'CcConfigConfiguration', 'cc_config_configurations', 'cc_config_configuration_id', 'id'),
	(2, 5, 1, 4, 'hasMany', 'Actions', 'CcConfigAction', 'cc_config_actions', 'cc_config_table_id', 'id'),
	(3, 5, 1, 3, 'hasMany', 'Fielddefinitions', 'CcConfigFielddefinition', 'cc_config_fielddefinitions', 'cc_config_table_id', 'id'),
	(4, 5, 1, 2, 'hasMany', 'Displayed Relations', 'CcConfigDisplayedrelation', 'cc_config_displayedrelations', 'cc_config_table_id', 'id');
/*!40000 ALTER TABLE `cc_config_displayedrelations` ENABLE KEYS */;


-- Exportiere Struktur von Tabelle cc_development.cc_config_fielddefinitions
DROP TABLE IF EXISTS `cc_config_fielddefinitions`;
CREATE TABLE IF NOT EXISTS `cc_config_fielddefinitions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cc_config_table_id` int(11) DEFAULT NULL COMMENT 'Define a fieldlist belonging to the table directly as a default fieldlist, applying to any action. Leave empty action_id then.',
  `cc_config_action_id` int(11) DEFAULT NULL COMMENT 'If assigned to a specific action, the fielddefinition overrides the default fielddefinition of the table.',
  `context` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'main' COMMENT 'What this fieldlist''s fielddefinition is for. "main" serves the main purpose of a view, but there might be other areas in the same view requiring an additional fieldlist, possibly one from a foreign model.',
  `position` int(3) DEFAULT NULL,
  `fieldname` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `label` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `display_method` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'auto' COMMENT 'value must be one of available methods from DisplayHelper',
  `display_options` text COLLATE utf8_unicode_ci COMMENT 'a list of options, depends from the selected display method',
  PRIMARY KEY (`id`),
  KEY `FK_cc_config_fielddefinitions_cc_config_tables` (`cc_config_table_id`),
  KEY `FK_cc_config_fielddefinitions_cc_config_actions` (`cc_config_action_id`),
  CONSTRAINT `FK_cc_config_fielddefinitions_cc_config_actions` FOREIGN KEY (`cc_config_action_id`) REFERENCES `cc_config_actions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `FK_cc_config_fielddefinitions_cc_config_tables` FOREIGN KEY (`cc_config_table_id`) REFERENCES `cc_config_tables` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- Exportiere Daten aus Tabelle cc_development.cc_config_fielddefinitions: ~0 rows (ungefähr)
/*!40000 ALTER TABLE `cc_config_fielddefinitions` DISABLE KEYS */;
/*!40000 ALTER TABLE `cc_config_fielddefinitions` ENABLE KEYS */;


-- Exportiere Struktur von Tabelle cc_development.cc_config_menus
DROP TABLE IF EXISTS `cc_config_menus`;
CREATE TABLE IF NOT EXISTS `cc_config_menus` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_role_id` int(11) DEFAULT NULL,
  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `comment` text COLLATE utf8_unicode_ci,
  PRIMARY KEY (`id`),
  KEY `FK_cc_config_menus_user_roles` (`user_role_id`),
  CONSTRAINT `FK_cc_config_menus_user_roles` FOREIGN KEY (`user_role_id`) REFERENCES `user_roles` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- Exportiere Daten aus Tabelle cc_development.cc_config_menus: ~0 rows (ungefähr)
/*!40000 ALTER TABLE `cc_config_menus` DISABLE KEYS */;
/*!40000 ALTER TABLE `cc_config_menus` ENABLE KEYS */;


-- Exportiere Struktur von Tabelle cc_development.cc_config_tables
DROP TABLE IF EXISTS `cc_config_tables`;
CREATE TABLE IF NOT EXISTS `cc_config_tables` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cc_config_menu_id` int(11) DEFAULT NULL,
  `position` int(3) DEFAULT NULL COMMENT 'sorting for menu generation',
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `label` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `modelclass` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `displayfield` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `displayfield_label` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `show_associations` tinyint(1) NOT NULL DEFAULT '1' COMMENT 'if associated tables are shown AT ALL',
  PRIMARY KEY (`id`),
  KEY `FK_cc_config_tables_user_roles` (`cc_config_menu_id`),
  CONSTRAINT `FK_cc_config_tables_user_roles` FOREIGN KEY (`cc_config_menu_id`) REFERENCES `cc_config_menus` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=29 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- Exportiere Daten aus Tabelle cc_development.cc_config_tables: ~28 rows (ungefähr)
/*!40000 ALTER TABLE `cc_config_tables` DISABLE KEYS */;
INSERT INTO `cc_config_tables` (`id`, `cc_config_menu_id`, `position`, `name`, `label`, `modelclass`, `displayfield`, `displayfield_label`, `show_associations`) VALUES
	(1, NULL, 3, 'cc_config_actions', 'Actions', 'CcConfigAction', '', 'Action', 1),
	(2, NULL, 1, 'cc_config_configurations', 'Configuration', 'CcConfigConfiguration', '', 'Config Revision', 1),
	(3, NULL, 6, 'cc_config_fielddefinitions', 'Fielddefinitions', 'CcConfigFielddefinition', '', 'Fielddefinition', 1),
	(4, NULL, 7, 'cc_config_menus', 'Menus', 'CcConfigMenu', '', 'Menu', 1),
	(5, NULL, 2, 'cc_config_tables', 'Tables', 'CcConfigTable', 'label', 'Table', 1),
	(6, NULL, 9, 'roles', 'Roles', 'Role', NULL, 'Role', 1),
	(7, NULL, 8, 'users', 'Users', 'User', '', 'User', 1),
	(8, NULL, 5, 'cc_config_actions_views', 'View Actions', 'CcConfigActionsView', '', 'View Action', 1),
	(9, NULL, 4, 'cc_config_displayedrelations', 'Displayed Relations', 'CcConfigDisplayedrelation', 'label', 'Displayed Relation', 1),
	(10, NULL, 1, 'cc_config_configurations', 'Configuration', 'CcConfigConfiguration', '', 'Config Revision', 1),
	(11, NULL, 2, 'cc_config_tables', 'Tables', 'CcConfigTable', 'label', 'Table', 1),
	(12, NULL, 3, 'cc_config_actions', 'Actions', 'CcConfigAction', '', 'Action', 1),
	(13, NULL, 4, 'cc_config_displayedrelations', 'Displayed Relations', 'CcConfigDisplayedrelation', 'label', 'Displayed Relation', 1),
	(14, NULL, 5, 'cc_config_actions_views', 'View Actions', 'CcConfigActionsView', '', 'View Action', 1),
	(15, NULL, 6, 'cc_config_fielddefinitions', 'Fielddefinitions', 'CcConfigFielddefinition', '', 'Fielddefinition', 1),
	(16, NULL, 7, 'cc_config_menus', 'Menus', 'CcConfigMenu', '', 'Menu', 1),
	(17, NULL, 8, 'roles', 'Roles', 'Role', NULL, 'Role', 1),
	(18, NULL, 9, 'users', 'Users', 'User', NULL, 'User', 1),
	(19, NULL, 1, 'cc_config_configurations', 'Configuration', 'CcConfigConfiguration', '', 'Config Revision', 1),
	(20, NULL, 2, 'cc_config_tables', 'Tables', 'CcConfigTable', 'label', 'Table', 1),
	(21, NULL, 3, 'cc_config_actions', 'Actions', 'CcConfigAction', '', 'Action', 1),
	(22, NULL, 4, 'cc_config_displayedrelations', 'Displayed Relations', 'CcConfigDisplayedrelation', 'label', 'Displayed Relation', 1),
	(23, NULL, 5, 'cc_config_actions_views', 'View Actions', 'CcConfigActionsView', '', 'View Action', 1),
	(24, NULL, 6, 'cc_config_fielddefinitions', 'Fielddefinitions', 'CcConfigFielddefinition', '', 'Fielddefinition', 1),
	(25, NULL, 7, 'cc_config_menus', 'Menus', 'CcConfigMenu', '', 'Menu', 1),
	(26, NULL, 8, 'roles', 'Roles', 'Role', NULL, 'Role', 1),
	(27, NULL, 9, 'users', 'Users', 'User', NULL, 'User', 1),
	(28, NULL, 10, 'tasks', 'Tasks', 'Task', NULL, 'Task', 1);
/*!40000 ALTER TABLE `cc_config_tables` ENABLE KEYS */;


-- Exportiere Struktur von Tabelle cc_development.tasks
DROP TABLE IF EXISTS `tasks`;
CREATE TABLE IF NOT EXISTS `tasks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `parent_task_id` int(11) DEFAULT NULL,
  `position` int(4) DEFAULT NULL,
  `closed` tinyint(1) NOT NULL DEFAULT '0',
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `description` text COLLATE utf8_unicode_ci,
  `bugs` text COLLATE utf8_unicode_ci,
  `guesswork` text COLLATE utf8_unicode_ci,
  `created` datetime DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `parent_id` (`parent_task_id`),
  CONSTRAINT `FK_tasks_tasks` FOREIGN KEY (`parent_task_id`) REFERENCES `tasks` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=28 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- Exportiere Daten aus Tabelle cc_development.tasks: ~17 rows (ungefähr)
/*!40000 ALTER TABLE `tasks` DISABLE KEYS */;
INSERT INTO `tasks` (`id`, `parent_task_id`, `position`, `closed`, `name`, `description`, `bugs`, `guesswork`, `created`, `modified`) VALUES
	(4, NULL, 3, 0, 'Divide menu', 'Create a configuration section, that includes all cc_config_* tables. \r\n\r\nCreate an administration section, that lets users administer the applicational tables. \r\n\r\nTherefore, filter for tables with the defined prefix constant in cakeclient.bootstrap.php.', '', NULL, NULL, '2014-07-21 00:31:32'),
	(6, NULL, NULL, 1, 'FixOrder completion', 'Add checking if the affected model uses Sortable, then add the method. \r\nFind a way to access the model from both component and other model.  \r\n- in CrudComponent, line 219\r\n- in CcConfigAction::update, line 67', '', '', '2014-06-22 03:33:00', '2014-07-20 14:25:24'),
	(7, NULL, NULL, 1, 'ForeignKey Value of PARENT_ID', 'How to create the options list?', '', '', '2014-06-22 03:42:00', '2014-07-20 14:25:39'),
	(9, NULL, NULL, 1, 'Sortable add-options', 'Make use of Sortable add-options in forms, create a select field by default', '', '', '2014-06-22 03:47:16', '2014-07-20 14:26:08'),
	(10, NULL, NULL, 1, 'Created, Modified in forms', 'Exclude the fields created, modified from all forms.', '', '', '2014-06-22 03:49:43', '2014-07-20 14:25:54'),
	(11, NULL, NULL, 1, 'Remove submit buttons with js', '', '', '', '2014-06-22 03:51:15', '2014-07-20 14:26:38'),
	(15, NULL, NULL, 1, 'js to head', 'Move all inline js into the head section. \r\nIntroduced a new block "onload" to handle onload scripts', '', 'affected views: \r\nposition_form.ctp\r\npager.ctp\r\nbulkprocessor.ctp', '2014-07-11 02:47:01', '2014-07-20 14:29:00'),
	(18, 27, 3, 0, 'Configuration Extension', 'Make configurable: \r\n\r\nDebug level (overall).\r\n\r\nRelated models display (table level).\r\n\r\nFilters and filter display (table level).\r\n\r\nContextual linkeage of child and habtm models (table level).\r\n\r\nShow tooltips (table level).', '', '', '2014-07-20 14:37:56', '2014-07-21 00:32:13'),
	(19, 27, 2, 0, 'Invisible Actions', 'Apply the config variable Action.visible during menu/actionslist generation!', '', '', '2014-07-20 14:39:21', '2014-07-21 00:32:03'),
	(20, NULL, 4, 0, 'Tasks to Tickets', 'Extend and rename the Task model to a ticketing system.', '', '', '2014-07-20 14:42:22', '2014-07-21 00:31:32'),
	(21, 20, 1, 0, 'Rename', 'Rename Tasks as Tickets. \r\nchange fieldname "position" to "priority".', '', '', '2014-07-20 14:44:54', '2014-07-20 15:45:01'),
	(22, 20, 2, 0, 'Time calculation fields', 'Add fields time_estimation & time_elapsed. ', '', '', '2014-07-20 14:45:48', '2014-07-20 14:45:48'),
	(23, 20, 3, 0, 'Time calculation view', 'Make an index view that calculates time budgets. ', '', '', '2014-07-20 14:46:22', '2014-07-20 14:46:22'),
	(24, 20, 4, 0, 'Guesswork to Comments', 'Enhance Tickets with comments.\r\nDrop the field "guesswork" instead.', '', '', '2014-07-20 14:47:57', '2014-07-20 14:47:57'),
	(25, NULL, 2, 0, 'Global Functions', 'Move ApAppController helper functions to plugin bootstrap.php', '', '', '2014-07-20 17:47:32', '2014-07-21 00:30:55'),
	(26, 27, 1, 0, 'No Config Error', 'Throw an error in CrudComponent if no loadable Config was found', '', '', NULL, '2014-07-21 00:31:53'),
	(27, NULL, 1, 0, 'Configuration Update', '', '', '', '2014-07-21 00:28:55', '2014-07-21 00:28:55');
/*!40000 ALTER TABLE `tasks` ENABLE KEYS */;


-- Exportiere Struktur von Tabelle cc_development.users
DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `is_admin` tinyint(1) NOT NULL DEFAULT '0',
  `user_role_id` int(11) DEFAULT NULL,
  `email` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  KEY `FK_users_user_roles` (`user_role_id`),
  CONSTRAINT `FK_users_user_roles` FOREIGN KEY (`user_role_id`) REFERENCES `user_roles` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- Exportiere Daten aus Tabelle cc_development.users: ~1 rows (ungefähr)
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` (`id`, `is_admin`, `user_role_id`, `email`, `password`) VALUES
	(1, 1, 1, 'hendrik.schmeer@yahoo.de', '38f914b36b16ca94470d48d03769d0ae5dd7a526');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;


-- Exportiere Struktur von Tabelle cc_development.user_roles
DROP TABLE IF EXISTS `user_roles`;
CREATE TABLE IF NOT EXISTS `user_roles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- Exportiere Daten aus Tabelle cc_development.user_roles: ~1 rows (ungefähr)
/*!40000 ALTER TABLE `user_roles` DISABLE KEYS */;
INSERT INTO `user_roles` (`id`, `name`) VALUES
	(1, 'Guest');
/*!40000 ALTER TABLE `user_roles` ENABLE KEYS */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IF(@OLD_FOREIGN_KEY_CHECKS IS NULL, 1, @OLD_FOREIGN_KEY_CHECKS) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
