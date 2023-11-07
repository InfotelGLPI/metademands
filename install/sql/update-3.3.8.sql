DROP TABLE IF EXISTS `glpi_plugin_metademands_basketobjecttypes`;
CREATE TABLE `glpi_plugin_metademands_basketobjecttypes`
(
    `id`           int unsigned NOT NULL auto_increment,
    `name`         varchar(255) collate utf8mb4_unicode_ci default NULL,
    PRIMARY KEY (`id`)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_ci
  ROW_FORMAT = DYNAMIC;

DROP TABLE IF EXISTS `glpi_plugin_metademands_basketobjects`;
CREATE TABLE `glpi_plugin_metademands_basketobjects`
(
    `id`                                    int unsigned   NOT NULL auto_increment,
    `name`                                  varchar(255) collate utf8mb4_unicode_ci default NULL,
    `description`                           longtext,
    `reference`                             varchar(255) collate utf8mb4_unicode_ci,
    `plugin_metademands_basketobjecttypes_id` int unsigned   NOT NULL                 DEFAULT '0',
    PRIMARY KEY (`id`),
    KEY `plugin_metademands_basketobjecttypes_id` (`plugin_metademands_basketobjecttypes_id`)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_ci
  ROW_FORMAT = DYNAMIC;
