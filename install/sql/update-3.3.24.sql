ALTER TABLE `glpi_plugin_metademands_fieldoptions` ADD `hidden_block_same_block` tinyint DEFAULT '0';
ALTER TABLE `glpi_plugin_metademands_configsteps` ADD `supervisor_validation` tinyint DEFAULT '0';
ALTER TABLE `glpi_plugin_metademands_steps` ADD `only_by_supervisor` tinyint DEFAULT '0';
