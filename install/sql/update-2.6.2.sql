ALTER TABLE glpi_plugin_metademands_fields ADD `row_display` tinyint(1) default 0;
ALTER TABLE glpi_plugin_metademands_fields ADD `default_values` text COLLATE utf8_unicode_ci default NULL;
ALTER TABLE glpi_plugin_metademands_metademands ADD `icon` varchar(255) default NULL;
ALTER TABLE glpi_plugin_metademands_configs ADD `display_type` tinyint(1) default 0;