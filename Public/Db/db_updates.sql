
# Add email message table
# Default column for DATETIME will probably fail for older MySQL versions
# CREATE TABLE `email` (
#   `email_id` INT NOT NULL AUTO_INCREMENT,
#   `gid` INT NOT NULL DEFAULT '0',
#   `eid` INT NULL DEFAULT '0',
#   `subject` TINYTEXT NULL,
#   `message` TEXT NULL,
#   `toaddress` TEXT NULL,
#   `fromaddress` TEXT NULL,
#   `submitted` DATETIME NULL DEFAULT CURRENT_TIMESTAMP,
#   `sent` DATETIME NULL,
#   PRIMARY KEY (`email_id`)
# )
#   COLLATE='utf8_general_ci'
#   ENGINE=InnoDB
# ;

# Replace deleted expenses table

DROP TABLE `expenses_del`;
CREATE TABLE `expenses_del` (
  `expense_id` INT(11) NOT NULL,
  `type` INT(11) NOT NULL,
  `cid` INT(11) NOT NULL,
  `user_id` INT(11) NOT NULL,
  `group_id` INT(11) NOT NULL,
  `uids` VARCHAR(240) NOT NULL,
  `description` VARCHAR(60) NOT NULL,
  `amount` FLOAT(10,2) NOT NULL,
  `expense_date` DATETIME NOT NULL,
  `event_id` INT(11) NOT NULL DEFAULT '0',
  `deposit_id` INT(11) NULL DEFAULT NULL,
  `timestamp` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `currency` INT(11) NOT NULL,
  `timezoneoffset` SMALLINT(6) NOT NULL DEFAULT '0',
  `delete_date` DATETIME NOT NULL,
  PRIMARY KEY (`expense_id`),
  FULLTEXT INDEX `description` (`description`)
)
  COLLATE 'utf8_general_ci' ENGINE=InnoDB;


# Add currency to groups and groups_del
ALTER TABLE `groups` ADD COLUMN `currency` VARCHAR(6) NOT NULL DEFAULT 'EUR' AFTER `reg_date`;
ALTER TABLE `groups_del` ADD COLUMN `currency` VARCHAR(6) NOT NULL DEFAULT 'EUR' AFTER `description`;

# Add removed to groups_del
ALTER TABLE `users_groups_del` ADD COLUMN `removed` TINYINT NOT NULL DEFAULT '0' AFTER `role_id`;

# Set delete date to current timestamp in groups_del
ALTER TABLE `users_groups_del`
  CHANGE COLUMN `del_date` `del_date` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER `join_date`;

# Add sort to user groups
ALTER TABLE `users_groups` ADD COLUMN `sort` INT(3) NOT NULL AFTER `group_id`;
ALTER TABLE `users_groups` ADD COLUMN `send_mail` TINYINT(4) NOT NULL DEFAULT '1' AFTER `role_id`;

# Make sure join date is not overwritten with sort updates
ALTER TABLE `users_groups` CHANGE COLUMN `join_date` `join_date` TIMESTAMP NULL AFTER `removed`;

# Add updated, firstname and lastname column to users
ALTER TABLE `users` ADD COLUMN `updated` INT(11) NOT NULL DEFAULT '0' AFTER `last_login`;
ALTER TABLE `users` ADD COLUMN `firstName` VARCHAR(100) NOT NULL AFTER `realname`,
  ADD COLUMN `lastName` VARCHAR(100) NOT NULL AFTER `firstName`;

ALTER TABLE `users`  ADD COLUMN `pwd_recovery` VARCHAR(35) NOT NULL DEFAULT '0' AFTER `password`;
ALTER TABLE `users`  ADD COLUMN `account_deleted` INT(1) NOT NULL DEFAULT '0' AFTER `activated`;

# Add categories table
CREATE TABLE `categories` (
  `cid` INT(11) NOT NULL,
  `group_id` INT(11) NOT NULL,
  `title` VARCHAR(50) NOT NULL,
  `presents` INT(11) NOT NULL DEFAULT '0',
  `inactive` TINYINT(4) NOT NULL DEFAULT '0',
  `can_delete` TINYINT(4) NOT NULL DEFAULT '0',
  `sort` INT(11) NOT NULL DEFAULT '1',
  PRIMARY KEY (`cid`, `group_id`)
)
  COLLATE='utf8_general_ci'
  ENGINE=InnoDB;

# Add timezoneoffset to expenses table
#ALTER TABLE `expenses` ADD COLUMN `timezoneoffset` SMALLINT NOT NULL DEFAULT '0' AFTER `currency`;

# Add cid to expenses table
ALTER TABLE `expenses` ADD COLUMN `cid` INT(11) NOT NULL AFTER `type`;


# Copy existing expense types over as categories

INSERT INTO `categories` (`cid`, `group_id`, `title`, `sort`) VALUES (1, 1, 'food/drinks', 1);
INSERT INTO `categories` (`cid`, `group_id`, `title`, `sort`) VALUES (2, 1, 'tickets', 2);
INSERT INTO `categories` (`cid`, `group_id`, `title`, `sort`, `presents`) VALUES (3, 1, 'presents', 3, 1);
INSERT INTO `categories` (`cid`, `group_id`, `title`, `sort`) VALUES (4, 1, 'games', 4);
INSERT INTO `categories` (`cid`, `group_id`, `title`, `sort`) VALUES (5, 1, 'payment', 5);
INSERT INTO `categories` (`cid`, `group_id`, `title`, `sort`) VALUES (6, 1, 'beer', 6);

INSERT INTO `categories` (`cid`, `group_id`, `title`, `sort`) VALUES (1, 2, 'food/drinks', 1);
INSERT INTO `categories` (`cid`, `group_id`, `title`, `sort`) VALUES (2, 2, 'tickets', 2);
INSERT INTO `categories` (`cid`, `group_id`, `title`, `sort`, `presents`) VALUES (3, 2, 'presents', 3, 1);
INSERT INTO `categories` (`cid`, `group_id`, `title`, `sort`) VALUES (4, 2, 'games', 4);
INSERT INTO `categories` (`cid`, `group_id`, `title`, `sort`) VALUES (5, 2, 'payment', 5);
INSERT INTO `categories` (`cid`, `group_id`, `title`, `sort`) VALUES (6, 2, 'beer', 6);

INSERT INTO `categories` (`cid`, `group_id`, `title`, `sort`) VALUES (1, 3, 'food/drinks', 1);
INSERT INTO `categories` (`cid`, `group_id`, `title`, `sort`) VALUES (2, 3, 'tickets', 2);
INSERT INTO `categories` (`cid`, `group_id`, `title`, `sort`, `presents`) VALUES (3, 3, 'presents', 3, 1);
INSERT INTO `categories` (`cid`, `group_id`, `title`, `sort`) VALUES (4, 3, 'games', 4);
INSERT INTO `categories` (`cid`, `group_id`, `title`, `sort`) VALUES (5, 3, 'payment', 5);
INSERT INTO `categories` (`cid`, `group_id`, `title`, `sort`) VALUES (6, 3, 'beer', 6);

INSERT INTO `categories` (`cid`, `group_id`, `title`, `sort`) VALUES (1, 4, 'food/drinks', 1);
INSERT INTO `categories` (`cid`, `group_id`, `title`, `sort`) VALUES (2, 4, 'tickets', 2);
INSERT INTO `categories` (`cid`, `group_id`, `title`, `sort`, `presents`) VALUES (3, 4, 'presents', 3, 1);
INSERT INTO `categories` (`cid`, `group_id`, `title`, `sort`) VALUES (4, 4, 'games', 4);
INSERT INTO `categories` (`cid`, `group_id`, `title`, `sort`) VALUES (5, 4, 'payment', 5);
INSERT INTO `categories` (`cid`, `group_id`, `title`, `sort`) VALUES (6, 4, 'beer', 6);

INSERT INTO `categories` (`cid`, `group_id`, `title`, `sort`) VALUES (1, 5, 'food/drinks', 1);
INSERT INTO `categories` (`cid`, `group_id`, `title`, `sort`) VALUES (2, 5, 'tickets', 2);
INSERT INTO `categories` (`cid`, `group_id`, `title`, `sort`, `presents`) VALUES (3, 5, 'presents', 3, 1);
INSERT INTO `categories` (`cid`, `group_id`, `title`, `sort`) VALUES (4, 5, 'games', 4);
INSERT INTO `categories` (`cid`, `group_id`, `title`, `sort`) VALUES (5, 5, 'payment', 5);
INSERT INTO `categories` (`cid`, `group_id`, `title`, `sort`) VALUES (6, 5, 'beer', 6);

INSERT INTO `categories` (`cid`, `group_id`, `title`, `sort`) VALUES (1, 6, 'food/drinks', 1);
INSERT INTO `categories` (`cid`, `group_id`, `title`, `sort`) VALUES (2, 6, 'tickets', 2);
INSERT INTO `categories` (`cid`, `group_id`, `title`, `sort`, `presents`) VALUES (3, 6, 'presents', 3, 1);
INSERT INTO `categories` (`cid`, `group_id`, `title`, `sort`) VALUES (4, 6, 'games', 4);
INSERT INTO `categories` (`cid`, `group_id`, `title`, `sort`) VALUES (5, 6, 'payment', 5);
INSERT INTO `categories` (`cid`, `group_id`, `title`, `sort`) VALUES (6, 6, 'beer', 6);

INSERT INTO `categories` (`cid`, `group_id`, `title`, `sort`) VALUES (1, 7, 'food/drinks', 1);
INSERT INTO `categories` (`cid`, `group_id`, `title`, `sort`) VALUES (2, 7, 'tickets', 2);
INSERT INTO `categories` (`cid`, `group_id`, `title`, `sort`, `presents`) VALUES (3, 7, 'presents', 3, 1);
INSERT INTO `categories` (`cid`, `group_id`, `title`, `sort`) VALUES (4, 7, 'games', 4);
INSERT INTO `categories` (`cid`, `group_id`, `title`, `sort`) VALUES (5, 7, 'payment', 5);
INSERT INTO `categories` (`cid`, `group_id`, `title`, `sort`) VALUES (6, 7, 'beer', 6);

INSERT INTO `categories` (`cid`, `group_id`, `title`, `sort`) VALUES (1, 8, 'food/drinks', 1);
INSERT INTO `categories` (`cid`, `group_id`, `title`, `sort`) VALUES (2, 8, 'tickets', 2);
INSERT INTO `categories` (`cid`, `group_id`, `title`, `sort`, `presents`) VALUES (3, 8, 'presents', 3, 1);
INSERT INTO `categories` (`cid`, `group_id`, `title`, `sort`) VALUES (4, 8, 'games', 4);
INSERT INTO `categories` (`cid`, `group_id`, `title`, `sort`) VALUES (5, 8, 'payment', 5);
INSERT INTO `categories` (`cid`, `group_id`, `title`, `sort`) VALUES (6, 8, 'beer', 6);

INSERT INTO `categories` (`cid`, `group_id`, `title`, `sort`) VALUES (1, 9, 'food/drinks', 1);
INSERT INTO `categories` (`cid`, `group_id`, `title`, `sort`) VALUES (2, 9, 'tickets', 2);
INSERT INTO `categories` (`cid`, `group_id`, `title`, `sort`, `presents`) VALUES (3, 9, 'presents', 3, 1);
INSERT INTO `categories` (`cid`, `group_id`, `title`, `sort`) VALUES (4, 9, 'games', 4);
INSERT INTO `categories` (`cid`, `group_id`, `title`, `sort`) VALUES (5, 9, 'payment', 5);
INSERT INTO `categories` (`cid`, `group_id`, `title`, `sort`) VALUES (6, 9, 'beer', 6);

INSERT INTO `categories` (`cid`, `group_id`, `title`, `sort`) VALUES (1, 10, 'food/drinks', 1);
INSERT INTO `categories` (`cid`, `group_id`, `title`, `sort`) VALUES (2, 10, 'tickets', 2);
INSERT INTO `categories` (`cid`, `group_id`, `title`, `sort`, `presents`) VALUES (3, 10, 'presents', 3, 1);
INSERT INTO `categories` (`cid`, `group_id`, `title`, `sort`) VALUES (4, 10, 'games', 4);
INSERT INTO `categories` (`cid`, `group_id`, `title`, `sort`) VALUES (5, 10, 'payment', 5);
INSERT INTO `categories` (`cid`, `group_id`, `title`, `sort`) VALUES (6, 10, 'beer', 6);

INSERT INTO `categories` (`cid`, `group_id`, `title`, `sort`) VALUES (1, 11, 'food/drinks', 1);
INSERT INTO `categories` (`cid`, `group_id`, `title`, `sort`) VALUES (2, 11, 'tickets', 2);
INSERT INTO `categories` (`cid`, `group_id`, `title`, `sort`, `presents`) VALUES (3, 11, 'presents', 3, 1);
INSERT INTO `categories` (`cid`, `group_id`, `title`, `sort`) VALUES (4, 11, 'games', 4);
INSERT INTO `categories` (`cid`, `group_id`, `title`, `sort`) VALUES (5, 11, 'payment', 5);
INSERT INTO `categories` (`cid`, `group_id`, `title`, `sort`) VALUES (6, 11, 'beer', 6);

INSERT INTO `categories` (`cid`, `group_id`, `title`, `sort`) VALUES (1, 12, 'food/drinks', 1);
INSERT INTO `categories` (`cid`, `group_id`, `title`, `sort`) VALUES (2, 12, 'tickets', 2);
INSERT INTO `categories` (`cid`, `group_id`, `title`, `sort`, `presents`) VALUES (3, 12, 'presents', 3, 1);
INSERT INTO `categories` (`cid`, `group_id`, `title`, `sort`) VALUES (4, 12, 'games', 4);
INSERT INTO `categories` (`cid`, `group_id`, `title`, `sort`) VALUES (5, 12, 'payment', 5);
INSERT INTO `categories` (`cid`, `group_id`, `title`, `sort`) VALUES (6, 12, 'beer', 6);
