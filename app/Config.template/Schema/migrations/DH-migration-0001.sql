drop table universities;
update institutions as i set i.course_count = (select count(*) from courses as c where i.id = c.institution_id);
update nwo_disciplines set name = 'Computational Linguistics' where id = 7;
ALTER TABLE `institutions`
	CHANGE COLUMN `is_university` `can_have_course` TINYINT(1) NOT NULL DEFAULT '1' COMMENT 'show only universities in the Course Registry option listings' AFTER `country_id`;