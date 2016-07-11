drop table universities;
ALTER TABLE `institutions` ADD COLUMN `course_count` int(11) DEFAULT 0 AFTER `is_university`;
update institutions as i set i.course_count = (select count(*) from courses as c where i.id = c.institution_id);
update nwo_disciplines set name = 'Computational Linguistics' where id = 7;
ALTER TABLE `institutions`
	CHANGE COLUMN `is_university` `can_have_course` TINYINT(1) NOT NULL DEFAULT '1' COMMENT 'show only universities in the Course Registry option listings' AFTER `country_id`;
	
ALTER TABLE `user_roles` ADD COLUMN `cakeclient_prefix` VARCHAR(255) NULL DEFAULT NULL AFTER `name`;
UPDATE `dh-registry`.`user_roles` SET `cakeclient_prefix`='admin' WHERE  `id`=1;
UPDATE `dh-registry`.`user_roles` SET `cakeclient_prefix`='moderator' WHERE  `id`=2;

ALTER TABLE `users` CHANGE COLUMN `authority` `about` TEXT NULL COLLATE 'utf8_unicode_ci' AFTER `telephone`;