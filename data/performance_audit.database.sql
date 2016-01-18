CREATE DATABASE `performance_audit` COLLATE = `utf8_unicode_ci`;

CREATE TABLE IF NOT EXISTS `performance_audit`.`metrics` (
    `id` INT(11) NOT NULL AUTO_INCREMENT, 
    `aid` INT(20) NOT NULL, 
	`uri` VARCHAR(255) NOT NULL,
    `name` VARCHAR(255) NOT NULL, 
    `type` TINYINT(2) NOT NULL DEFAULT 0,
    `realm` ENUM("backend", "frontend", "network", "system", "remote") NOT NULL,
    `source` ENUM("xhprof", "xdebug", "boomerang", "yslow", "pagespeed", "browsercache", "network") NOT NULL,
    `created` TIMESTAMP NOT NULL DEFAULT NOW(),
    `start` INT(10) NOT NULL,
    `end` INT(10) NOT NULL,
    `data` TEXT, 
    `counter` INT(3) NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`) 
) ENGINE=`InnoDB` DEFAULT CHARSET=`utf8` COLLATE=`utf8_unicode_ci` AUTO_INCREMENT=1;

CREATE USER 'op_perfaudit'@'localhost' IDENTIFIED BY 'btbpsaUJwN9x7RhvR3uNkTCh';

GRANT USAGE ON `performance_audit`.* TO 'op_perfaudit'@'localhost' IDENTIFIED BY 'btbpsaUJwN9x7RhvR3uNkTCh';

GRANT ALL PRIVILEGES ON `performance_audit`.* TO 'op_perfaudit'@'localhost' IDENTIFIED BY 'btbpsaUJwN9x7RhvR3uNkTCh';