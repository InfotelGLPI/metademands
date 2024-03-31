ALTER TABLE `glpi_plugin_metademands_metademands` DROP INDEX `itilcategories_id`;
ALTER TABLE `glpi_plugin_metademands_metademands` CHANGE `itilcategories_id` `itilcategories_id` text COLLATE utf8mb4_unicode_ci NOT NULL;
