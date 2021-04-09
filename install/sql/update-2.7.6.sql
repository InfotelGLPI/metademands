
ALTER TABLE `glpi_plugin_metademands_fields` ADD `informations_to_display` varchar(255) NOT NULL default '[]';


CREATE TABLE `glpi_plugin_metademands_drafts_values`
(
    `id`                                int(11) NOT NULL AUTO_INCREMENT,
    `plugin_metademands_drafts_id` int(11) NOT NULL    DEFAULT '0',
    `plugin_metademands_fields_id` int(11) NOT NULL    DEFAULT '0',
    `value`                        TEXT    NOT NULL,
    PRIMARY KEY (`id`)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8
  COLLATE = utf8_unicode_ci
  AUTO_INCREMENT = 1;


  CREATE TABLE `glpi_plugin_metademands_drafts`
(
    `id`                                int(11) NOT NULL AUTO_INCREMENT,
    `name`                              VARCHAR (255) NOT NULL    DEFAULT '0',
    `plugin_metademands_metademands_id` int(11) NOT NULL    DEFAULT '0',
    `users_id`                          int(11) NOT NULL DEFAULT '0',
    `date`                              timestamp NOT NULL,
    PRIMARY KEY (`id`)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8
  COLLATE = utf8_unicode_ci
  AUTO_INCREMENT = 1;