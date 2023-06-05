CREATE TABLE `glpi_plugin_metademands_fieldoptions`
(
    `id`                           int unsigned NOT NULL AUTO_INCREMENT,
    `plugin_metademands_fields_id` int unsigned NOT NULL DEFAULT '0',
    `check_value`                  varchar(255)          DEFAULT NULL,
    `plugin_metademands_tasks_id`  varchar(255)          DEFAULT NULL,
    `fields_link`                  varchar(255) NOT NULL DEFAULT '0',
    `hidden_link`                  varchar(255) NOT NULL DEFAULT '0',
    `hidden_block`                 varchar(255) NOT NULL DEFAULT '0',
    `users_id_validate`            varchar(255) NOT NULL DEFAULT '0',
    `childs_blocks`                varchar(255) NOT NULL DEFAULT '[]',
    `checkbox_value`               varchar(255) NOT NULL DEFAULT '[]',
    `checkbox_id`                  varchar(255) NOT NULL DEFAULT '[]',
    `parent_field_id`              int unsigned NOT NULL DEFAULT '0',
    PRIMARY KEY (`id`),
    KEY `plugin_metademands_fields_id` (`plugin_metademands_fields_id`),
    KEY `plugin_metademands_tasks_id` (`plugin_metademands_tasks_id`)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_ci
  ROW_FORMAT = DYNAMIC;

CREATE TABLE `glpi_plugin_metademands_configsteps`
(
    `id`                                int unsigned NOT NULL AUTO_INCREMENT,
    `plugin_metademands_metademands_id` int unsigned NOT NULL           DEFAULT '0',
    `link_user_block`                   tinyint      NOT NULL           DEFAULT '0',
    `multiple_link_groups_blocks`       tinyint      NOT NULL           DEFAULT '0',
    `add_user_as_requester`             tinyint      NOT NULL           DEFAULT '0',

    PRIMARY KEY (`id`),
    KEY `plugin_metademands_metademands_id` (`plugin_metademands_metademands_id`)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_ci
  ROW_FORMAT = DYNAMIC;

ALTER TABLE `glpi_plugin_metademands_stepforms` ADD `users_id_dest` int unsigned NOT NULL DEFAULT '0';

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

ALTER TABLE `glpi_plugin_metademands_steps` ADD KEY `plugin_metademands_metademands_id` (`plugin_metademands_metademands_id`);
ALTER TABLE `glpi_plugin_metademands_stepforms_values` ADD KEY `plugin_metademands_stepforms_id` (`plugin_metademands_stepforms_id`);
ALTER TABLE `glpi_plugin_metademands_stepforms_values` ADD KEY `plugin_metademands_fields_id` (`plugin_metademands_fields_id`);
ALTER TABLE `glpi_plugin_metademands_stepforms` ADD KEY `plugin_metademands_metademands_id` (`plugin_metademands_metademands_id`);
ALTER TABLE `glpi_plugin_metademands_forms_values` ADD KEY `plugin_metademands_forms_id` (`plugin_metademands_forms_id`);
ALTER TABLE `glpi_plugin_metademands_forms_values` ADD KEY `plugin_metademands_fields_id` (`plugin_metademands_fields_id`);
ALTER TABLE `glpi_plugin_metademands_forms` ADD KEY `plugin_metademands_metademands_id` (`plugin_metademands_metademands_id`);
ALTER TABLE `glpi_plugin_metademands_pluginfields` ADD KEY `plugin_fields_fields_id` (`plugin_fields_fields_id`);
ALTER TABLE `glpi_plugin_metademands_pluginfields` ADD KEY `plugin_metademands_fields_id` (`plugin_metademands_fields_id`);
ALTER TABLE `glpi_plugin_metademands_pluginfields` ADD KEY `plugin_metademands_metademands_id` (`plugin_metademands_metademands_id`);