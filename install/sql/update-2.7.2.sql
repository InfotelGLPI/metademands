ALTER TABLE glpi_plugin_metademands_metademands ADD `is_order` tinyint(1) default 0;
ALTER TABLE glpi_plugin_metademands_fields ADD `is_basket` tinyint(1) default 0;

CREATE TABLE `glpi_plugin_metademands_basketlines` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `users_id` int(11) NOT NULL default '0',
    `plugin_metademands_metademands_id` int(11) NOT NULL default '0',
    `plugin_metademands_fields_id` int(11) NOT NULL default '0',
    `line` int(11) NOT NULL default '0',
    `name` varchar(150) COLLATE utf8_unicode_ci DEFAULT NULL,
    `value` text COLLATE utf8_unicode_ci,
    `value2` text COLLATE utf8_unicode_ci,
    PRIMARY KEY (`id`),
    UNIQUE KEY `unicity` (`plugin_metademands_metademands_id`,`line`,`name`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;