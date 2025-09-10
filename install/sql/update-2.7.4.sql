DROP TABLE IF EXISTS `glpi_plugin_metademands_fieldtranslations`;
CREATE TABLE `glpi_plugin_metademands_fieldtranslations`
(
    `id`       int unsigned NOT NULL AUTO_INCREMENT,
    `items_id` int unsigned NOT NULL                   DEFAULT '0',
    `itemtype` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    `language` varchar(5) COLLATE utf8mb4_unicode_ci   DEFAULT NULL,
    `field`    varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    `value`    text COLLATE utf8mb4_unicode_ci         DEFAULT NULL,
    PRIMARY KEY (`id`)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_ci
  ROW_FORMAT = DYNAMIC;

DROP TABLE IF EXISTS `glpi_plugin_metademands_metademandtranslations`;
CREATE TABLE `glpi_plugin_metademands_metademandtranslations`
(
    `id`       int unsigned NOT NULL AUTO_INCREMENT,
    `items_id` int unsigned NOT NULL                   DEFAULT '0',
    `itemtype` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    `language` varchar(5) COLLATE utf8mb4_unicode_ci   DEFAULT NULL,
    `field`    varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    `value`    text COLLATE utf8mb4_unicode_ci         DEFAULT NULL,
    PRIMARY KEY (`id`)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_ci
  ROW_FORMAT = DYNAMIC;

ALTER TABLE `glpi_plugin_metademands_fields`
    ADD `hidden_block` VARCHAR(255) NULL AFTER `hidden_link`;
ALTER TABLE `glpi_plugin_metademands_fields`
    CHANGE `label` `name` VARCHAR(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL;

ALTER TABLE `glpi_plugin_metademands_configs`
    DROP `enable_application_environment`;
ALTER TABLE `glpi_plugin_metademands_configs`
    DROP `enable_families`;
DROP TABLE IF EXISTS `glpi_plugin_metademands_tickets_itilapplications`;
DROP TABLE IF EXISTS `glpi_plugin_metademands_tickets_itilenvironments`;
DROP TABLE IF EXISTS `glpi_plugin_metademands_itilenvironments`;
DROP TABLE IF EXISTS `glpi_plugin_metademands_itilapplications`;
ALTER TABLE `glpi_plugin_metademands_tickettasks`
    DROP `plugin_metademands_itilapplications_id`;
ALTER TABLE `glpi_plugin_metademands_tickettasks`
    DROP `plugin_metademands_itilenvironments_id`;
ALTER TABLE `glpi_plugin_metademands_configs`
    ADD `display_buttonlist_servicecatalog` tinyint(1) default 1;
ALTER TABLE `glpi_plugin_metademands_configs`
    ADD `title_servicecatalog` varchar(255) DEFAULT NULL;
ALTER TABLE `glpi_plugin_metademands_configs`
    ADD `comment_servicecatalog` TEXT DEFAULT NULL;
ALTER TABLE `glpi_plugin_metademands_configs`
    ADD `fa_servicecatalog` varchar(100) NOT NULL DEFAULT 'ti ti-share';
