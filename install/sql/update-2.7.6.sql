ALTER TABLE `glpi_plugin_metademands_fields` ADD `informations_to_display` varchar(255) NOT NULL default '[]';
ALTER TABLE `glpi_plugin_metademands_metademands` ADD `is_deleted` tinyint(1) NOT NULL DEFAULT '0';
ALTER TABLE `glpi_plugin_metademands_fields` ADD `use_richtext` TINYINT(1) NOT NULL DEFAULT '1';
ALTER TABLE `glpi_plugin_metademands_metademands` ADD `object_to_create` varchar(255) collate utf8_unicode_ci default NULL;
ALTER TABLE `glpi_plugin_metademands_metademands` ADD `hide_no_field` tinyint(1)  default '0';
ALTER TABLE `glpi_plugin_metademands_metademands` ADD `background_color` varchar(255) default '#FFFFFF';
ALTER TABLE `glpi_plugin_metademands_metademands` ADD `title_color` varchar(255) default '#000000';
UPDATE `glpi_plugin_metademands_metademands` SET `object_to_create` = 'Ticket';


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
