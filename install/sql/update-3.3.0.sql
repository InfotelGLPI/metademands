CREATE TABLE `glpi_plugin_metademands_fieldoptions`
(
    `id`                           int unsigned NOT NULL AUTO_INCREMENT,
    `plugin_metademands_fields_id` int unsigned NOT NULL DEFAULT '0',
    `check_value`                  int unsigned NOT NULL DEFAULT '0',
    `plugin_metademands_tasks_id`  int unsigned NOT NULL DEFAULT '0',
    `fields_link`                  int unsigned NOT NULL DEFAULT '0',
    `hidden_link`                  int unsigned NOT NULL DEFAULT '0',
    `hidden_block`                 int unsigned NOT NULL DEFAULT '0',
    `users_id_validate`            int unsigned NOT NULL DEFAULT '0',
    `childs_blocks`                varchar(255) NOT NULL DEFAULT '[]',
    `checkbox_value`               int unsigned NOT NULL DEFAULT '0',
    `checkbox_id`                  int unsigned NOT NULL DEFAULT '0',
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

CREATE TABLE `glpi_plugin_metademands_stepforms_actors`
(
    `id`                                int unsigned NOT NULL AUTO_INCREMENT,
    `plugin_metademands_stepforms_id`   int unsigned NOT NULL           DEFAULT '0',
    `users_id`                          int unsigned NOT NULL           DEFAULT '0',
    PRIMARY KEY (`id`),
    KEY `plugin_metademands_stepforms_id` (`plugin_metademands_stepforms_id`),
    KEY `users_id` (`users_id`)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_ci
  ROW_FORMAT = DYNAMIC;

ALTER TABLE `glpi_plugin_metademands_stepforms` ADD `users_id_dest` int unsigned NOT NULL DEFAULT '0';

ALTER TABLE `glpi_plugin_metademands_metademands` ADD `is_template` tinyint NOT NULL DEFAULT '0';
ALTER TABLE `glpi_plugin_metademands_metademands` ADD `template_name` varchar(255) DEFAULT NULL;
ALTER TABLE `glpi_plugin_metademands_metademandtasks` ADD `entities_id` int unsigned NOT NULL DEFAULT '0';
ALTER TABLE `glpi_plugin_metademands_groups` ADD `entities_id` int unsigned NOT NULL DEFAULT '0';
ALTER TABLE `glpi_plugin_metademands_tasks` ADD `is_recursive` int NOT NULL DEFAULT '0';

ALTER TABLE `glpi_plugin_metademands_configs` CHANGE `show_form_changes` `show_form_changes` tinyint NOT NULL DEFAULT 0;
ALTER TABLE `glpi_plugin_metademands_fields` CHANGE `default_use_id_requester` `default_use_id_requester` int unsigned DEFAULT 0;

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

ALTER TABLE `glpi_plugin_metademands_drafts` ADD KEY `plugin_metademands_metademands_id` (`plugin_metademands_metademands_id`);
ALTER TABLE `glpi_plugin_metademands_drafts_values` ADD KEY `plugin_metademands_drafts_id` (`plugin_metademands_drafts_id`);
ALTER TABLE `glpi_plugin_metademands_drafts_values` ADD KEY `plugin_metademands_fields_id` (`plugin_metademands_fields_id`);

ALTER TABLE `glpi_plugin_metademands_metademandvalidations` ADD KEY `users_id` (`users_id`);
ALTER TABLE `glpi_plugin_metademands_metademandvalidations` ADD KEY `tickets_id` (`tickets_id`);
ALTER TABLE `glpi_plugin_metademands_metademandvalidations` ADD KEY `plugin_metademands_metademands_id` (`plugin_metademands_metademands_id`);
ALTER TABLE `glpi_plugin_metademands_metademands` ADD KEY `itilcategories_id` (`itilcategories_id`);
ALTER TABLE `glpi_plugin_metademands_fields` CHANGE `use_date_now` `use_date_now` tinyint DEFAULT 0;
ALTER TABLE `glpi_plugin_metademands_fields` CHANGE `used_by_child` `used_by_child` tinyint DEFAULT 0;
ALTER TABLE `glpi_plugin_metademands_fields` CHANGE `link_to_user` `link_to_user` int DEFAULT 0;
ALTER TABLE `glpi_plugin_metademands_tickets_tasks` ADD KEY `parent_tickets_id` (`parent_tickets_id`);
ALTER TABLE `glpi_plugin_metademands_tickets_metademands` ADD KEY `parent_tickets_id` (`parent_tickets_id`);
ALTER TABLE `glpi_plugin_metademands_tickets_metademands` ADD KEY `tickettemplates_id` (`tickettemplates_id`);
ALTER TABLE `glpi_plugin_metademands_metademands_resources` ADD KEY `entities_id` (`entities_id`);
ALTER TABLE `glpi_plugin_metademands_ticketfields` ADD KEY `entities_id` (`entities_id`);
ALTER TABLE `glpi_plugin_metademands_basketlines` ADD KEY `plugin_metademands_fields_id` (`plugin_metademands_fields_id`);
ALTER TABLE `glpi_plugin_metademands_basketlines` ADD KEY `plugin_metademands_metademands_id` (`plugin_metademands_metademands_id`);
ALTER TABLE `glpi_plugin_metademands_basketlines` ADD KEY `users_id` (`users_id`);
ALTER TABLE glpi_plugin_metademands_basketlines
    DROP INDEX unicity,
    ADD UNIQUE KEY `unicity` (`plugin_metademands_metademands_id`,`plugin_metademands_fields_id`,`line`,`name`,`users_id`);