ALTER TABLE `glpi_plugin_metademands_tasks`
    CHANGE `hideTable` `formatastable` TINYINT(1) NOT NULL DEFAULT '0';
UPDATE `glpi_plugin_metademands_tasks` SET `formatastable` = '0' WHERE `formatastable` = 1;
UPDATE `glpi_plugin_metademands_tasks` SET `formatastable` = '1' WHERE `formatastable` = 0;
