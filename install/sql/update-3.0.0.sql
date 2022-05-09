ALTER TABLE `glpi_plugin_metademands_tasks`
    CHANGE `hideTable` `formatastable` tinyint NOT NULL DEFAULT '0';
UPDATE `glpi_plugin_metademands_tasks` SET `formatastable` = '0' WHERE `formatastable` = 1;
UPDATE `glpi_plugin_metademands_tasks` SET `formatastable` = '1' WHERE `formatastable` = 0;

ALTER TABLE `glpi_plugin_metademands_configs` ADD `use_draft` tinyint(1) default 1;
ALTER TABLE `glpi_plugin_metademands_configs` ADD `show_form_changes` tinyint(1) default 0;

DROP TABLE IF EXISTS `glpi_plugin_metademands_forms`;
CREATE TABLE `glpi_plugin_metademands_forms`
(
    `id`                                int unsigned NOT NULL AUTO_INCREMENT,
    `name`                              VARCHAR(255) NOT NULL                   DEFAULT '0',
    `plugin_metademands_metademands_id` int unsigned NOT NULL DEFAULT '0',
    `items_id`                          int unsigned NOT NULL DEFAULT '0',
    `itemtype`                          varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    `users_id`                          int unsigned NOT NULL DEFAULT '0',
    `date`                              timestamp    NOT NULL,
    `is_model`                          tinyint NOT NULL DEFAULT '0',
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

DROP TABLE IF EXISTS `glpi_plugin_metademands_forms_values`;
CREATE TABLE `glpi_plugin_metademands_forms_values`
(
    `id`                           int unsigned NOT NULL AUTO_INCREMENT,
    `plugin_metademands_forms_id`  int unsigned NOT NULL DEFAULT '0',
    `plugin_metademands_fields_id` int unsigned NOT NULL DEFAULT '0',
    `value`                        TEXT NOT NULL,
    `value2`                       TEXT NOT NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

ALTER TABLE `glpi_plugin_metademands_fields`
    CHANGE `default_use_id_requester` `default_use_id_requester` int unsigned default 0;

ALTER TABLE `glpi_plugin_metademands_fields` ADD `checkbox_value` VARCHAR (255) NOT NULL DEFAULT '[]';
ALTER TABLE `glpi_plugin_metademands_fields` ADD `checkbox_id` VARCHAR (255) NOT NULL DEFAULT '[]';