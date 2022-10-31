ALTER TABLE `glpi_plugin_metademands_tasks`
    ADD `hideTable` TINYINT NOT NULL DEFAULT '0';
ALTER TABLE `glpi_plugin_metademands_configs`
    ADD `languageTech` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL;
ALTER TABLE `glpi_plugin_metademands_fields`
   ADD `users_id_validate` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL default '0';
ALTER TABLE `glpi_plugin_metademands_tasks`
    ADD `useBlock` TINYINT NOT NULL DEFAULT '1';
