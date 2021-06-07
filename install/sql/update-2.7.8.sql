
CREATE TABLE `glpi_plugin_metademands_pluginfields`
(
    `id`                                int(11) NOT NULL AUTO_INCREMENT,
    `plugin_fields_fields_id`           int(11) NOT NULL DEFAULT '0',
    `plugin_metademands_fields_id`      int(11) NOT NULL DEFAULT '0',
    `plugin_metademands_metademands_id` int(11) NOT NULL DEFAULT '0',
    PRIMARY KEY (`id`)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8
  COLLATE = utf8_unicode_ci
  AUTO_INCREMENT = 1;
