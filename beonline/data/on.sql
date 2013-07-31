# Таблица идентификаторов пользователей
CREATE TABLE IF NOT EXISTS `prefix_beonline`
(
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) UNSIGNED NOT NULL,
  `reg_id` varchar(255) NOT NULL,
  `user_key` char(32) NOT NULL,
PRIMARY KEY (`id`),
KEY `user_reg_id_index` (`user_id`, `reg_id`),
KEY  `user_id_index` (`user_id`),
KEY  `user_key_index` (`user_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

# Таблица неотправленных сообщений
CREATE TABLE IF NOT EXISTS `prefix_beonline_package`
(
    `id` INT(11) PRIMARY KEY NOT NULL,
    `user_id` INT(11) NOT NULL,
    `package` text  NOT NULL,
    `error` varchar (200) NOT NULL,
    `status` enum ('opened', 'closed') DEFAULT 'opened' NOT NULL,
  KEY  `user_id_index` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
