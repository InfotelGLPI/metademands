ALTER TABLE `glpi_plugin_metademands_fields` ADD `childs_blocks` VARCHAR (255) NOT NULL DEFAULT '[]';
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
    `resources_id`                      int(11) NOT NULL DEFAULT '0',
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

ALTER TABLE `glpi_plugin_metademands_fields` ADD `checkbox_value` VARCHAR (255) NOT NULL DEFAULT '[]';
ALTER TABLE `glpi_plugin_metademands_fields` ADD `checkbox_id` VARCHAR (255) NOT NULL DEFAULT '[]';
ALTER TABLE `glpi_plugin_metademands_metademands` ADD `can_update` tinyint(1) NOT NULL DEFAULT '0';
ALTER TABLE `glpi_plugin_metademands_metademands` ADD `can_clone` tinyint(1) NOT NULL DEFAULT '0';


CREATE TABLE `glpi_plugin_metademands_interticketfollowups`
(
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `tickets_id` int(11) NOT NULL DEFAULT '0',
    `targets_id` int(11) NOT NULL DEFAULT '0',
    `date` timestamp NULL DEFAULT NULL,
    `users_id` int(11) NOT NULL DEFAULT '0',
    `users_id_editor` int(11) NOT NULL DEFAULT '0',
    `content` longtext COLLATE utf8_unicode_ci,
    `is_private` tinyint(1) NOT NULL DEFAULT '0',
    `requesttypes_id` int(11) NOT NULL DEFAULT '0', -- todo keep it ?
    `date_mod` timestamp NULL DEFAULT NULL,
    `date_creation` timestamp NULL DEFAULT NULL,
    `timeline_position` tinyint(1) NOT NULL DEFAULT '0',

    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;