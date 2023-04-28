ALTER TABLE `glpi_plugin_metademands_metademands` ADD `is_template` tinyint NOT NULL DEFAULT '0';
ALTER TABLE `glpi_plugin_metademands_metademands` ADD `template_name` varchar(255) DEFAULT NULL;
ALTER TABLE `glpi_plugin_metademands_metademands` ADD KEY `name` (`name`);
ALTER TABLE `glpi_plugin_metademands_metademands` ADD KEY `entities_id` (`entities_id`);
ALTER TABLE `glpi_plugin_metademands_metademands` ADD KEY `is_recursive` (`is_recursive`);
ALTER TABLE `glpi_plugin_metademands_metademands` ADD KEY `is_template` (`is_template`);
ALTER TABLE `glpi_plugin_metademands_metademands` ADD KEY `is_deleted` (`is_deleted`);