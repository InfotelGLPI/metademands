ALTER TABLE `glpi_plugin_metademands_fields` CHANGE `comment` `comment` TEXT COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL;
ALTER TABLE `glpi_plugin_metademands_fields` CHANGE `label2` `label2` TEXT COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL;

ALTER TABLE `glpi_plugin_metademands_fields`
    ADD `link_to_user` int unsigned NOT NULL DEFAULT '0';
ALTER TABLE `glpi_plugin_metademands_fields`
    ADD `default_use_id_requester` tinyint NOT NULL DEFAULT '1';
ALTER TABLE `glpi_plugin_metademands_fields`
    ADD `additional_number_day` int unsigned NOT NULL DEFAULT '0' AFTER `default_use_id_requester`;
ALTER TABLE `glpi_plugin_metademands_fields`
    ADD `use_date_now` tinyint NOT NULL DEFAULT '0' AFTER `additional_number_day`;
ALTER TABLE `glpi_plugin_metademands_tasks`
    ADD `block_use` VARCHAR(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '[]';

ALTER TABLE glpi_plugin_metademands_tickets_metademands DROP FOREIGN KEY glpi_plugin_metademands_tickets_metademands_ibfk_2;
ALTER TABLE glpi_plugin_metademands_tickets_tasks DROP FOREIGN KEY glpi_plugin_metademands_tickets_tasks_ibfk_2;
