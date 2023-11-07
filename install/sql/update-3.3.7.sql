ALTER TABLE `glpi_plugin_metademands_metademands` ADD `initial_requester_childs_tickets` tinyint NOT NULL DEFAULT 1;
ALTER TABLE `glpi_plugin_metademands_tasks` ADD `block_parent_ticket_resolution` tinyint NOT NULL DEFAULT 1;
