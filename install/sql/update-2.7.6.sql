ALTER TABLE `glpi_plugin_metademands_fields` ADD `informations_to_display` varchar(255) NOT NULL default '[]';
ALTER TABLE `glpi_plugin_metademands_metademands` ADD `is_deleted` tinyint(1) NOT NULL DEFAULT '0';


