ALTER TABLE `glpi_plugin_metademands_fields` CHANGE `fields_display` `hidden_link` VARCHAR(255) NOT NULL;
ALTER TABLE `glpi_plugin_metademands_fields` CHANGE `plugin_metademands_tasks_id` `plugin_metademands_tasks_id` VARCHAR(255) DEFAULT NULL;
ALTER TABLE `glpi_plugin_metademands_tickets_metademands` ADD `tickettemplates_id` INT(11) NOT NULL DEFAULT '0';
ALTER TABLE `glpi_plugin_metademands_fields` ADD `max_upload` INT(11) NOT NULL DEFAULT 0;
ALTER TABLE `glpi_plugin_metademands_fields` ADD `regex` VARCHAR(255) NOT NULL DEFAULT '';