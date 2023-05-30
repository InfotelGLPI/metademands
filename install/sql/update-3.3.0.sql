ALTER TABLE `glpi_plugin_metademands_metademands` ADD `is_template` tinyint NOT NULL DEFAULT '0';
ALTER TABLE `glpi_plugin_metademands_metademands` ADD `template_name` varchar(255) DEFAULT NULL;
ALTER TABLE `glpi_plugin_metademands_metademandtasks` ADD `entities_id` int unsigned NOT NULL DEFAULT '0';
ALTER TABLE `glpi_plugin_metademands_groups` ADD `entities_id` int unsigned NOT NULL DEFAULT '0';
ALTER TABLE `glpi_plugin_metademands_metademandtasks` ADD KEY `entities_id` (`entities_id`);
ALTER TABLE `glpi_plugin_metademands_groups` ADD KEY `entities_id` (`entities_id`);

ALTER TABLE `glpi_plugin_metademands_metademands` ADD KEY `name` (`name`);
ALTER TABLE `glpi_plugin_metademands_metademands` ADD KEY `entities_id` (`entities_id`);
ALTER TABLE `glpi_plugin_metademands_metademands` ADD KEY `is_recursive` (`is_recursive`);
ALTER TABLE `glpi_plugin_metademands_metademands` ADD KEY `is_template` (`is_template`);
ALTER TABLE `glpi_plugin_metademands_metademands` ADD KEY `is_deleted` (`is_deleted`);


ALTER TABLE `glpi_plugin_metademands_stepforms` ADD `users_id_dest` int unsigned NOT NULL DEFAULT '0';
CREATE TABLE `glpi_plugin_metademands_configsteps`
(
    `id`                                int unsigned NOT NULL AUTO_INCREMENT,
    `plugin_metademands_metademands_id` int unsigned NOT NULL           DEFAULT '0',
    `link_user_block`                   tinyint      NOT NULL           DEFAULT '0',
    `multiple_link_groups_blocks`       tinyint      NOT NULL           DEFAULT '0',
    PRIMARY KEY (`id`)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_ci
  ROW_FORMAT = DYNAMIC;