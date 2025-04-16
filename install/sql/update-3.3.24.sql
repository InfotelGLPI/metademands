ALTER TABLE `glpi_plugin_metademands_fieldoptions` ADD `hidden_block_same_block` tinyint NOT NULL DEFAULT '0';
ALTER TABLE `glpi_plugin_metademands_configsteps` ADD `supervisor_validation` tinyint NOT NULL DEFAULT '0';
ALTER TABLE `glpi_plugin_metademands_steps` ADD `only_by_supervisor` tinyint NOT NULL DEFAULT '0';
