ALTER TABLE `glpi_plugin_metademands_tasks`
    ADD `useBlock` TINYINT(1) NOT NULL DEFAULT '1';
ALTER TABLE `glpi_plugin_metademands_tasks`
    ADD `hideTable` TINYINT(1) NOT NULL DEFAULT '0';
ALTER TABLE `glpi_plugin_metademands_configs`
    ADD `languageTech` varchar(100) DEFAULT NULL;



