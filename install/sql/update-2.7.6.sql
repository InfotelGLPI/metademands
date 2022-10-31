ALTER TABLE `glpi_plugin_metademands_fields`
    ADD `informations_to_display` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL default '[]';
ALTER TABLE `glpi_plugin_metademands_metademands`
    ADD `is_deleted` tinyint NOT NULL DEFAULT '0';
ALTER TABLE `glpi_plugin_metademands_fields`
    ADD `use_richtext` tinyint NOT NULL DEFAULT '1';
ALTER TABLE `glpi_plugin_metademands_metademands`
    ADD `object_to_create` varchar(255) COLLATE utf8mb4_unicode_ci default NULL;
ALTER TABLE `glpi_plugin_metademands_metademands`
    ADD `hide_no_field` tinyint default '0';
ALTER TABLE `glpi_plugin_metademands_metademands`
    ADD `background_color` varchar(255) COLLATE utf8mb4_unicode_ci default '#FFFFFF';
ALTER TABLE `glpi_plugin_metademands_metademands`
    ADD `title_color` varchar(255) COLLATE utf8mb4_unicode_ci default '#000000';
UPDATE `glpi_plugin_metademands_metademands`
SET `object_to_create` = 'Ticket';


CREATE TABLE `glpi_plugin_metademands_drafts_values`
(
    `id`                           int unsigned NOT NULL AUTO_INCREMENT,
    `plugin_metademands_drafts_id` int unsigned NOT NULL DEFAULT '0',
    `plugin_metademands_fields_id` int unsigned NOT NULL DEFAULT '0',
    `value`                        TEXT NOT NULL,
    `value2`                       TEXT NOT NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;


CREATE TABLE `glpi_plugin_metademands_drafts`
(
    `id`                                int unsigned NOT NULL AUTO_INCREMENT,
    `name`                              VARCHAR(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '0',
    `plugin_metademands_metademands_id` int unsigned NOT NULL DEFAULT '0',
    `users_id`                          int unsigned NOT NULL DEFAULT '0',
    `date`                              timestamp    NOT NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
