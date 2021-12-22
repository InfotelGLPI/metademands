ALTER TABLE `glpi_plugin_metademands_tasks`
    ADD `hideTable` TINYINT(1) NOT NULL DEFAULT '0';
ALTER TABLE `glpi_plugin_metademands_configs`
    ADD `languageTech` varchar(100) DEFAULT NULL;
ALTER TABLE `glpi_plugin_metademands_fields`
   ADD `users_id_validate` varchar(255) NOT NULL default '0';
ALTER TABLE `glpi_plugin_metademands_tasks`
    ADD `useBlock` TINYINT(1) NOT NULL DEFAULT '1';
