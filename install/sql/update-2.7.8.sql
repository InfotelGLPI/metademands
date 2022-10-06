ALTER TABLE `glpi_plugin_metademands_metademands` ADD `maintenance_mode` tinyint NOT NULL DEFAULT '0';

CREATE TABLE `glpi_plugin_metademands_pluginfields`
(
    `id`                                int unsigned NOT NULL AUTO_INCREMENT,
    `plugin_fields_fields_id`           int unsigned NOT NULL DEFAULT '0',
    `plugin_metademands_fields_id`      int unsigned NOT NULL DEFAULT '0',
    `plugin_metademands_metademands_id` int unsigned NOT NULL DEFAULT '0',
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
