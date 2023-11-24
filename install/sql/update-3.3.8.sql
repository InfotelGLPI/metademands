ALTER TABLE `glpi_plugin_metademands_metademands` ADD `is_basket` tinyint DEFAULT 0;

DROP TABLE IF EXISTS `glpi_plugin_metademands_basketobjecttypes`;
CREATE TABLE `glpi_plugin_metademands_basketobjecttypes`
(
    `id`           int unsigned NOT NULL auto_increment,
    `name`         varchar(255) collate utf8mb4_unicode_ci default NULL,
    PRIMARY KEY (`id`)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_ci
  ROW_FORMAT = DYNAMIC;

DROP TABLE IF EXISTS `glpi_plugin_metademands_basketobjects`;
CREATE TABLE `glpi_plugin_metademands_basketobjects`
(
    `id`                                    int unsigned   NOT NULL auto_increment,
    `name`                                  varchar(255) collate utf8mb4_unicode_ci default NULL,
    `description`                           longtext,
    `reference`                             varchar(255) collate utf8mb4_unicode_ci,
    `plugin_metademands_basketobjecttypes_id` int unsigned   NOT NULL                 DEFAULT '0',
    PRIMARY KEY (`id`),
    KEY `plugin_metademands_basketobjecttypes_id` (`plugin_metademands_basketobjecttypes_id`)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_ci
  ROW_FORMAT = DYNAMIC;

CREATE TABLE `glpi_plugin_metademands_mailtasks`
(
    `id`                                int unsigned NOT NULL AUTO_INCREMENT,
    `content`                           text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    `users_id_recipient`                int unsigned NOT NULL DEFAULT '0',
    `groups_id_recipient`               int unsigned NOT NULL DEFAULT '0',
    `plugin_metademands_tasks_id`       int unsigned NOT NULL DEFAULT '0',
    `itilcategories_id`                 int unsigned          DEFAULT '0',
    `email`                             varchar(255) DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `plugin_metademands_tasks_id` (`plugin_metademands_tasks_id`),
    KEY `users_id_recipient` (`users_id_recipient`),
    KEY `groups_id_recipient` (`groups_id_recipient`),
    KEY `itilcategories_id` (`itilcategories_id`)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_ci
  ROW_FORMAT = DYNAMIC;

CREATE TABLE `glpi_plugin_metademands_basketobjecttranslations`
(
    `id`                                  int unsigned NOT NULL AUTO_INCREMENT,
    `items_id`                            int unsigned NOT NULL                   DEFAULT '0',
    `itemtype`                            varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    `field`                               varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    `language`                            varchar(5) COLLATE utf8mb4_unicode_ci   DEFAULT NULL,
    `value`                               text COLLATE utf8mb4_unicode_ci         DEFAULT NULL,
    PRIMARY KEY (`id`)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_ci
  ROW_FORMAT = DYNAMIC;

CREATE TABLE `glpi_plugin_metademands_basketobjecttypetranslations`
(
    `id`                                  int unsigned NOT NULL AUTO_INCREMENT,
    `items_id`                            int unsigned NOT NULL                   DEFAULT '0',
    `itemtype`                            varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    `language`                            varchar(5) COLLATE utf8mb4_unicode_ci   DEFAULT NULL,
    `field`                               varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    `value`                               text COLLATE utf8mb4_unicode_ci         DEFAULT NULL,
    PRIMARY KEY (`id`)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_ci
  ROW_FORMAT = DYNAMIC;