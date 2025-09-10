ALTER TABLE `glpi_plugin_metademands_metademands` ADD `forms_categories_id` int unsigned NOT NULL DEFAULT '0';
ALTER TABLE `glpi_plugin_metademands_metademands` ADD `illustration` varchar(255) DEFAULT NULL;
ALTER TABLE `glpi_plugin_metademands_metademands` ADD `is_pinned` tinyint NOT NULL DEFAULT '0';
ALTER TABLE `glpi_plugin_metademands_metademands` ADD `usage_count` int unsigned NOT NULL DEFAULT '0';
ALTER TABLE `glpi_plugin_metademands_metademands` ADD `description` longtext;
