CREATE TABLE `glpi_plugin_metademands_freetablefields`
(
    `id`                           int unsigned NOT NULL AUTO_INCREMENT,
    `plugin_metademands_fields_id` int unsigned NOT NULL           DEFAULT '0',
    `internal_name`                VARCHAR(255) NOT NULL           DEFAULT '0',
    `type`                         VARCHAR(255)                    DEFAULT NULL,
    `name`                         VARCHAR(255) NOT NULL           DEFAULT '0',
    `dropdown_values`              text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    `is_mandatory`                 int          NOT NULL           DEFAULT '0',
    `comment`                      text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    `rank`                         int          NOT NULL           DEFAULT '0',
    PRIMARY KEY (`id`),
    KEY `plugin_metademands_fields_id` (`plugin_metademands_fields_id`)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_ci
  ROW_FORMAT = DYNAMIC;
