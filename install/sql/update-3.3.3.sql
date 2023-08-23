ALTER TABLE `glpi_plugin_metademands_metademands` ADD `show_rule` tinyint   NOT NULL  DEFAULT '1';

CREATE TABLE `glpi_plugin_metademands_conditions`
(
    `id`                                int unsigned NOT NULL AUTO_INCREMENT,
    `plugin_metademands_fields_id`      int unsigned NOT NULL DEFAULT '0',
    `plugin_metademands_metademands_id` int unsigned NOT NULL DEFAULT '0',
    `items_id`                          int unsigned NOT NULL DEFAULT '0',
    `item`                              varchar(255)          DEFAULT NULL,
    `check_value`                       varchar(255)     NULL DEFAULT NULL,
    `show_logic`                        int(11)      NOT NULL DEFAULT '1',
    `show_condition`                    int(11)      NOT NULL DEFAULT '0',
    `order`                             int(11)      NOT NULL DEFAULT '1',
    `type`                              varchar(255)          DEFAULT NULL,

    PRIMARY KEY (`id`),
    KEY `plugin_metademands_fields_id` (`plugin_metademands_fields_id`),
    KEY `plugin_metademands_metademands_id` (`plugin_metademands_metademands_id`)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_ci
  ROW_FORMAT = DYNAMIC;