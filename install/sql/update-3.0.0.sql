ALTER TABLE `glpi_plugin_metademands_tasks`
    CHANGE `hideTable` `formatastable` TINYINT(1) NOT NULL DEFAULT '0';
UPDATE `glpi_plugin_metademands_tasks` SET `formatastable` = '0' WHERE `formatastable` = 1;
UPDATE `glpi_plugin_metademands_tasks` SET `formatastable` = '1' WHERE `formatastable` = 0;

ALTER TABLE `glpi_plugin_metademands_configs` ADD `use_draft` tinyint(1) default 1;

DROP TABLE IF EXISTS `glpi_plugin_metademands_forms`;
CREATE TABLE `glpi_plugin_metademands_forms`
(
    `id`                                int(11) NOT NULL AUTO_INCREMENT,
    `name`                              VARCHAR(255) NOT NULL                   DEFAULT '0',
    `plugin_metademands_metademands_id` int(11) NOT NULL DEFAULT '0',
    `items_id`                          int(11) NOT NULL DEFAULT '0',
    `itemtype`                          varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    `users_id`                          int(11) NOT NULL DEFAULT '0',
    `date`                              timestamp    NOT NULL,
    `is_model`                          tinyint(1) NOT NULL DEFAULT '0',
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

DROP TABLE IF EXISTS `glpi_plugin_metademands_forms_values`;
CREATE TABLE `glpi_plugin_metademands_forms_values`
(
    `id`                           int(11) NOT NULL AUTO_INCREMENT,
    `plugin_metademands_forms_id`  int(11) NOT NULL DEFAULT '0',
    `plugin_metademands_fields_id` int(11) NOT NULL DEFAULT '0',
    `value`                        TEXT NOT NULL,
    `value2`                       TEXT NOT NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
