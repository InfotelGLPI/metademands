ALTER TABLE `glpi_plugin_metademands_fields` ADD `checkbox_value` VARCHAR (255) NOT NULL DEFAULT '[]';
ALTER TABLE `glpi_plugin_metademands_fields` ADD `checkbox_id` VARCHAR (255) NOT NULL DEFAULT '[]';
ALTER TABLE `glpi_plugin_metademands_metademands` ADD `can_update` tinyint NOT NULL DEFAULT '0';
ALTER TABLE `glpi_plugin_metademands_metademands` ADD `can_clone` tinyint NOT NULL DEFAULT '0';
ALTER TABLE `glpi_plugin_metademands_configs` ADD `show_form_changes` tinyint NOT NULL DEFAULT '0';
