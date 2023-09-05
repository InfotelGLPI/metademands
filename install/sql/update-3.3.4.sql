
CREATE TABLE `glpi_plugin_metademands_customdropdowns`
(
    `id`                                int unsigned NOT NULL AUTO_INCREMENT,
    `plugin_metademands_fields_id`      int unsigned NOT NULL DEFAULT '0',
    `default_value`                     int  NOT NULL DEFAULT '0',
    `order`                             int unsigned NOT NULL DEFAULT '1',
    `value`                             varchar(255)          DEFAULT NULL,
    `comment`                           varchar(255)          DEFAULT NULL,

    PRIMARY KEY (`id`),
    KEY `plugin_metademands_fields_id` (`plugin_metademands_fields_id`)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_ci
  ROW_FORMAT = DYNAMIC;