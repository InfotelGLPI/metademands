ALTER TABLE `glpi_plugin_metademands_fields` ADD `display_type` INT(11) NOT NULL DEFAULT '0' AFTER `date_mod`;
ALTER TABLE `glpi_plugin_metademands_fields` ADD `used_by_ticket` INT(11) NOT NULL DEFAULT '0' AFTER `display_type`;
