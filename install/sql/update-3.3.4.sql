ALTER TABLE `glpi_plugin_metademands_tickettasks` ADD `entities_id` int unsigned NOT NULL DEFAULT '0';
ALTER TABLE `glpi_plugin_metademands_conditions` CHANGE `order` `order` INT NOT NULL DEFAULT '0';
ALTER TABLE `glpi_plugin_metademands_fields` ADD `hidden` tinyint DEFAULT 0;