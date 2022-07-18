CREATE TABLE `glpi_plugin_metademands_stepforms`
(
    `id`                                int unsigned NOT NULL AUTO_INCREMENT,
    `name`                              VARCHAR(255) NOT NULL                   DEFAULT '0',
    `plugin_metademands_metademands_id` int unsigned NOT NULL DEFAULT '0',
    `items_id`                          int unsigned NOT NULL DEFAULT '0',

    `users_id`                          int unsigned NOT NULL DEFAULT '0',
    `groups_id_dest`                     int unsigned NOT NULL DEFAULT '0',
    `date`                              timestamp    NULL DEFAULT NULL,
    `reminder_date`                     timestamp    NULL DEFAULT NULL,
    `bloc_id`                           int unsigned NOT NULL DEFAULT '0',
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

CREATE TABLE `glpi_plugin_metademands_stepforms_values`
(
    `id`                           int unsigned NOT NULL AUTO_INCREMENT,
    `plugin_metademands_stepforms_id`  int unsigned NOT NULL DEFAULT '0',
    `plugin_metademands_fields_id` int unsigned NOT NULL DEFAULT '0',
    `value`                        TEXT NOT NULL,
    `value2`                       TEXT NOT NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

CREATE TABLE `glpi_plugin_metademands_steps`
(
    `id`                           int unsigned NOT NULL AUTO_INCREMENT,
    `plugin_metademands_metademands_id`  int unsigned NOT NULL DEFAULT '0',
    `bloc_id`  int unsigned NOT NULL DEFAULT '0',
    `groups_id` int unsigned NOT NULL DEFAULT '0',
    `reminder_delay`                        TEXT NOT NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

ALTER TABLE `glpi_plugin_metademands_metademands` ADD `step_by_step_mode` tinyint NOT NULL DEFAULT '0';
