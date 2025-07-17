ALTER TABLE `glpi_plugin_metademands_fieldcustomvalues` ADD `icon` VARCHAR(255) DEFAULT NULL;
ALTER TABLE `glpi_plugin_metademands_metademands` ADD `use_confirm` tinyint NOT NULL DEFAULT '0';
ALTER TABLE `glpi_plugin_metademands_forms` ADD `is_private` tinyint NOT NULL DEFAULT '0';
