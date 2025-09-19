ALTER TABLE `glpi_plugin_metademands_metademands` ADD `forms_categories_id` int unsigned NOT NULL DEFAULT '0';
ALTER TABLE `glpi_plugin_metademands_metademands` ADD `illustration` varchar(255) DEFAULT NULL;
ALTER TABLE `glpi_plugin_metademands_metademands` ADD `is_pinned` tinyint NOT NULL DEFAULT '0';
ALTER TABLE `glpi_plugin_metademands_metademands` ADD `usage_count` int unsigned NOT NULL DEFAULT '0';
ALTER TABLE `glpi_plugin_metademands_metademands` ADD `description` longtext;


UPDATE `glpi_displaypreferences` SET `itemtype` = 'GlpiPlugin\\Metademands\\Metademand' WHERE `glpi_displaypreferences`.`itemtype` = 'PluginMetademandsMetademand';
UPDATE `glpi_notificationtemplates` SET `itemtype` = 'GlpiPlugin\\Metademands\\Metademand' WHERE `itemtype` = 'PluginMetademandsMetademand';
UPDATE `glpi_notifications` SET `itemtype` = 'GlpiPlugin\\Metademands\\Metademand' WHERE `itemtype` = 'PluginMetademandsMetademand';

UPDATE `glpi_savedsearches` SET `itemtype` = 'GlpiPlugin\\Metademands\\Stepform' WHERE `itemtype` = 'PluginMetademandsStepform';
UPDATE `glpi_savedsearches_users` SET `itemtype` = 'GlpiPlugin\\Metademands\\Stepform' WHERE `itemtype` = 'PluginMetademandsStepform';

UPDATE `glpi_notificationtemplates` SET `itemtype` = 'GlpiPlugin\\Metademands\\Interticketfollowup' WHERE `itemtype` = 'PluginMetademandsInterticketfollowup';
UPDATE `glpi_notifications` SET `itemtype` = 'GlpiPlugin\\Metademands\\Interticketfollowup' WHERE `itemtype` = 'PluginMetademandsInterticketfollowup';
