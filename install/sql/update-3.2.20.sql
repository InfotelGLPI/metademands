ALTER TABLE `glpi_plugin_metademands_configs` ADD `link_user_block` tinyint DEFAULT 0;
ALTER TABLE `glpi_plugin_metademands_configs` ADD `multiple_link_groups_blocks` tinyint DEFAULT 0;
ALTER TABLE `glpi_plugin_metademands_stepforms` ADD `users_id_dest` int unsigned NOT NULL DEFAULT '0';
