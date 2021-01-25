ALTER TABLE `glpi_plugin_metademands_metademands` ADD `validation_subticket` TINYINT(1) NOT NULL DEFAULT '0';

DROP TABLE IF EXISTS `glpi_plugin_metademands_metademandvalidations`;
CREATE TABLE `glpi_plugin_metademands_metademandvalidations`
(
    `id`       int(11) NOT NULL AUTO_INCREMENT,
    `tickets_id` int(11) NOT NULL                     DEFAULT '0',
    `plugin_metademands_id` int(11) NOT NULL                     DEFAULT '0',
    `users_id` int(11) NOT NULL                     DEFAULT '0',
    `validate` tinyint(1) NOT NULL                     DEFAULT '0',
    `date` timestamp NOT NULL,
    `tickets_to_create` text NOT NULL,
    PRIMARY KEY (`id`)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8
  COLLATE = utf8_unicode_ci
  AUTO_INCREMENT = 1;