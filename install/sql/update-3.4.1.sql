ALTER TABLE glpi_plugin_metademands_fieldoptions ADD COLUMN `check_type_value` int unsigned NOT NULL DEFAULT '1';
ALTER TABLE glpi_plugin_metademands_fieldoptions ADD COLUMN `check_value_regex` TEXT COLLATE utf8mb4_unicode_ci DEFAULT NULL;
