-- ----------------------------------------------------------
-- Plugin Metademands                          --------------
-- ----------------------------------------------------------

-- -----------------------------------------------------------
--
-- Structure de la table 'glpi_plugin_metademands_metademands'
--
-- -----------------------------------------------------------
DROP TABLE IF EXISTS `glpi_plugin_metademands_metademands`;
CREATE TABLE `glpi_plugin_metademands_metademands`
(
    `id`                   int unsigned NOT NULL AUTO_INCREMENT,                 -- id metademands
    `name`                 varchar(255)                            default NULL, -- name metademands
    `entities_id`          int unsigned NOT NULL default '0',                    -- entites_id
    `is_recursive`         int unsigned NOT NULL default '0',                    -- is_recursive
    `is_active`            tinyint      NOT NULL                   DEFAULT '1',
    `maintenance_mode`     tinyint      NOT NULL                   DEFAULT '0',
    `can_update`           tinyint      NOT NULL                   DEFAULT '0',
    `can_clone`            tinyint      NOT NULL                   DEFAULT '0',
    `comment`              text COLLATE utf8mb4_unicode_ci         default NULL,
    `object_to_create`     varchar(255) collate utf8mb4_unicode_ci default NULL,
    `type`                 int unsigned NOT NULL default '0',                    -- metademand type : Incident, demand
    `itilcategories_id`    varchar(255) NOT NULL                   default '[]', -- references itilcategories glpi
    `icon`                 varchar(255)                            default NULL,
    `is_order`             tinyint                                 default 0,
    `create_one_ticket`    tinyint      NOT NULL                   default '0',  -- create_one_ticket
    `force_create_tasks`   tinyint      NOT NULL                   DEFAULT '0',
    `date_creation`        timestamp NULL DEFAULT NULL,
    `date_mod`             timestamp NULL DEFAULT NULL,
    `validation_subticket` tinyint      NOT NULL                   DEFAULT '0',
    `is_deleted`           tinyint      NOT NULL                   DEFAULT '0',
    `hide_no_field`        tinyint                                 default '0',
    `title_color`          varchar(255)                            default '#000000',
    `background_color`     varchar(255)                            default '#FFFFFF',
    `step_by_step_mode`    tinyint      NOT NULL                   DEFAULT '0',
    PRIMARY KEY (`id`),
    KEY                    `itilcategories_id` (`itilcategories_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

-- ----------------------------------------------------------
--
-- Structure de la table 'glpi_plugin_metademands_tasks'
--
-- ----------------------------------------------------------
DROP TABLE IF EXISTS `glpi_plugin_metademands_tasks`;
CREATE TABLE `glpi_plugin_metademands_tasks`
(
    `id`                                int unsigned NOT NULL AUTO_INCREMENT,
    `name`                              varchar(255)                    default NULL,
    `completename`                      varchar(255)                    default NULL,
    `comment`                           text COLLATE utf8mb4_unicode_ci default NULL,
    `entities_id`                       int unsigned NOT NULL default '0', -- entites_id
    `level`                             int unsigned NOT NULL default '0',
    `type`                              int unsigned NOT NULL default '0',
    `ancestors_cache`                   text COLLATE utf8mb4_unicode_ci default NULL,
    `sons_cache`                        text COLLATE utf8mb4_unicode_ci default NULL,
    `plugin_metademands_tasks_id`       int unsigned NOT NULL default '0',
    `plugin_metademands_metademands_id` int unsigned NOT NULL default '0',
    `block_use`                         varchar(255) NOT NULL           DEFAULT '[]',
    `useBlock`                          tinyint      NOT NULL           DEFAULT '1',
    `formatastable`                     tinyint      NOT NULL           DEFAULT '1',
    PRIMARY KEY (`id`),
    KEY                                 `plugin_metademands_metademands_id` (`plugin_metademands_metademands_id`),
    KEY                                 `plugin_metademands_tasks_id` (`plugin_metademands_tasks_id`),
    KEY                                 `entities_id` (`entities_id`),
    KEY                                 `type` (`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;


-- ------------------------------------------------------------
--
-- Structure de la table 'glpi_plugin_metademands_fields'
--
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `glpi_plugin_metademands_fields`;
CREATE TABLE `glpi_plugin_metademands_fields`
(
    `id`                                int unsigned NOT NULL AUTO_INCREMENT,
    `entities_id`                       int unsigned NOT NULL default '0', -- entites_id
    `is_recursive`                      int unsigned NOT NULL default '0', -- is_recursive
    `comment`                           text COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
    `custom_values`                     text COLLATE utf8mb4_unicode_ci default NULL,
    `default_values`                    text COLLATE utf8mb4_unicode_ci default NULL,
    `comment_values`                    text COLLATE utf8mb4_unicode_ci default NULL,
    `check_value`                       varchar(255)                    default NULL,
    `checkbox_value`                    varchar(255) NOT NULL           DEFAULT '[]',
    `checkbox_id`                       varchar(255) NOT NULL           DEFAULT '[]',
    `rank`                              int unsigned NOT NULL default '0',
    `order`                             int unsigned NOT NULL default '0',
    `name`                              varchar(255)                    default NULL,
    `hide_title`                        tinyint      NOT NULL           DEFAULT '0',
    `label2`                            text COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
    `type`                              varchar(255)                    default NULL,
    `item`                              varchar(255)                    default NULL,
    `is_mandatory`                      int unsigned NOT NULL default '0',
    `plugin_metademands_fields_id`      int unsigned NOT NULL default '0',
    `plugin_metademands_metademands_id` int unsigned NOT NULL default '0',
    `plugin_metademands_tasks_id`       varchar(255)                    DEFAULT NULL,
    `fields_link`                       varchar(255) NOT NULL           default '0',
    `hidden_link`                       varchar(255) NOT NULL           default '0',
    `hidden_block`                      varchar(255) NOT NULL           default '0',
    `users_id_validate`                 varchar(255) NOT NULL           default '0',
    `max_upload`                        int unsigned NOT NULL DEFAULT 0,
    `regex`                             VARCHAR(255) NOT NULL           DEFAULT '',
    `color`                             varchar(255)                    default NULL,
    `parent_field_id`                   int unsigned NOT NULL default '0',
    `row_display`                       tinyint                         default 0,
    `is_basket`                         tinyint                         default 0,
    `date_creation`                     timestamp NULL DEFAULT NULL,
    `date_mod`                          timestamp NULL DEFAULT NULL,
    `display_type`                      int unsigned DEFAULT 0,
    `used_by_ticket`                    int unsigned NOT NULL DEFAULT '0',
    `used_by_child`                     tinyint                         default 0,
    `link_to_user`                      int unsigned default 0,
    `default_use_id_requester`          int unsigned default 0,
    `use_date_now`                      tinyint                         default 0,
    `additional_number_day`             int unsigned default 0,
    `informations_to_display`           varchar(255) NOT NULL           default '[]',
    `use_richtext`                      tinyint      NOT NULL           DEFAULT '1',
    `childs_blocks`                     VARCHAR(255) NOT NULL           DEFAULT '[]',
    PRIMARY KEY (`id`),
    KEY                                 `plugin_metademands_metademands_id` (`plugin_metademands_metademands_id`),
    KEY                                 `plugin_metademands_fields_id` (`plugin_metademands_fields_id`),
    KEY                                 `plugin_metademands_tasks_id` (`plugin_metademands_tasks_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;


-- ------------------------------------------------------------
--
-- Structure de la table 'glpi_plugin_metademands_tickets_fields'
--
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `glpi_plugin_metademands_tickets_fields`;
CREATE TABLE `glpi_plugin_metademands_tickets_fields`
(
    `id`                           int unsigned NOT NULL AUTO_INCREMENT,
    `value`                        text COLLATE utf8mb4_unicode_ci default NULL,
    `tickets_id`                   int unsigned NOT NULL default '0',
    `plugin_metademands_fields_id` int unsigned NOT NULL default '0',
    PRIMARY KEY (`id`),
    KEY                            `plugin_metademands_fields_id` (`plugin_metademands_fields_id`),
    KEY                            `tickets_id` (`tickets_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

-- ------------------------------------------------------------
--
-- Structure de la table 'glpi_plugin_metademands_ticketfields'
--
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `glpi_plugin_metademands_ticketfields`;
CREATE TABLE `glpi_plugin_metademands_ticketfields`
(
    `id`                                int unsigned NOT NULL AUTO_INCREMENT,
    `num`                               int unsigned default NULL,
    `value`                             text COLLATE utf8mb4_unicode_ci default NULL,
    `entities_id`                       int unsigned NOT NULL default '0', -- entites_id
    `is_recursive`                      int unsigned NOT NULL default '0', -- is_recursive
    `is_mandatory`                      int unsigned NOT NULL default '0',
    `is_deletable`                      int unsigned NOT NULL default '1',
    `plugin_metademands_metademands_id` int unsigned NOT NULL default '0',
    PRIMARY KEY (`id`),
    KEY                                 `entities_id` (`entities_id`),
    KEY                                 `plugin_metademands_metademands_id` (`plugin_metademands_metademands_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

-- ------------------------------------------------------------
--
-- Structure de la table 'glpi_plugin_metademands_tickettasks'
--
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `glpi_plugin_metademands_tickettasks`;
CREATE TABLE `glpi_plugin_metademands_tickettasks`
(
    `id`                          int unsigned NOT NULL AUTO_INCREMENT,
    `content`                     text COLLATE utf8mb4_unicode_ci default NULL,
    `itilcategories_id`           int unsigned default '0',
    `type`                        int unsigned NOT NULL default '0',
    `status`                      varchar(255)                    default NULL,
    `actiontime`                  int unsigned NOT NULL default '0',
    `requesttypes_id`             int unsigned NOT NULL default '0',
    `groups_id_assign`            int unsigned NOT NULL default '0',
    `users_id_assign`             int unsigned NOT NULL default '0',
    `groups_id_requester`         int unsigned NOT NULL default '0',
    `users_id_requester`          int unsigned NOT NULL default '0',
    `groups_id_observer`          int unsigned NOT NULL default '0',
    `users_id_observer`           int unsigned NOT NULL default '0',
    `plugin_metademands_tasks_id` int unsigned NOT NULL default '0',
    PRIMARY KEY (`id`),
    KEY                           `plugin_metademands_tasks_id` (`plugin_metademands_tasks_id`),
    KEY                           `itilcategories_id` (`itilcategories_id`),
    KEY                           `groups_id_assign` (`groups_id_assign`),
    KEY                           `users_id_assign` (`users_id_assign`),
    KEY                           `groups_id_requester` (`groups_id_requester`),
    KEY                           `users_id_requester` (`users_id_requester`),
    KEY                           `groups_id_observer` (`groups_id_observer`),
    KEY                           `users_id_observer` (`users_id_observer`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

-- ----------------------------------------------------------
--
-- Structure de la table 'glpi_plugin_metademands_tickets_tasks'
--
-- ----------------------------------------------------------
DROP TABLE IF EXISTS `glpi_plugin_metademands_tickets_tasks`;
CREATE TABLE `glpi_plugin_metademands_tickets_tasks`
(
    `id`                          int unsigned NOT NULL AUTO_INCREMENT,
    `plugin_metademands_tasks_id` int unsigned NOT NULL default '0',
    `level`                       int unsigned NOT NULL default '0',
    `tickets_id`                  int unsigned NOT NULL default '0',
    `parent_tickets_id`           int unsigned NOT NULL default '0',
    PRIMARY KEY (`id`),
    KEY                           `plugin_metademands_tasks_id` (`plugin_metademands_tasks_id`),
    KEY                           `tickets_id` (`tickets_id`),
    KEY                           `parent_tickets_id` (`parent_tickets_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

-- ----------------------------------------------------------
--
-- Structure de la table 'glpi_plugin_metademands_tickets_metademands'
--
-- ----------------------------------------------------------
DROP TABLE IF EXISTS `glpi_plugin_metademands_tickets_metademands`;
CREATE TABLE `glpi_plugin_metademands_tickets_metademands`
(
    `id`                                int unsigned NOT NULL AUTO_INCREMENT,
    `plugin_metademands_metademands_id` int unsigned NOT NULL default '0',
    `tickets_id`                        int unsigned NOT NULL default '0',
    `parent_tickets_id`                 int unsigned NOT NULL default '0',
    `tickettemplates_id`                int unsigned NOT NULL DEFAULT '0',
    `status`                            tinyint NOT NULL DEFAULT '1',
    PRIMARY KEY (`id`),
    KEY                                 `plugin_metademands_metademands_id` (`plugin_metademands_metademands_id`),
    KEY                                 `tickets_id` (`tickets_id`),
    KEY                                 `parent_tickets_id` (`parent_tickets_id`),
    KEY                                 `tickettemplates_id` (`tickettemplates_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

-- ----------------------------------------------------------
--
-- Structure de la table 'glpi_plugin_metademands_metademandtasks'
--
-- ----------------------------------------------------------
DROP TABLE IF EXISTS `glpi_plugin_metademands_metademandtasks`;
CREATE TABLE `glpi_plugin_metademands_metademandtasks`
(
    `id`                                int unsigned NOT NULL AUTO_INCREMENT,
    `plugin_metademands_metademands_id` int unsigned NOT NULL default '0',
    `plugin_metademands_tasks_id`       int unsigned NOT NULL default '0',
    PRIMARY KEY (`id`),
    KEY                                 `plugin_metademands_metademands_id` (`plugin_metademands_metademands_id`),
    KEY                                 `plugin_metademands_tasks_id` (`plugin_metademands_tasks_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

-- ----------------------------------------------------------
--
-- Structure de la table 'glpi_plugin_metademands_groups'
--
-- ----------------------------------------------------------
DROP TABLE IF EXISTS `glpi_plugin_metademands_groups`;
CREATE TABLE `glpi_plugin_metademands_groups`
(
    `id`                                int unsigned NOT NULL AUTO_INCREMENT,
    `groups_id`                         int unsigned NOT NULL default '0',
    `plugin_metademands_metademands_id` int unsigned NOT NULL default '0',
    PRIMARY KEY (`id`),
    KEY (`plugin_metademands_metademands_id`),
    KEY (`groups_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;


-- --------------------------------------------------------
--
-- Structure de la table 'glpi_plugin_metademands_metademands_resources'
--
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `glpi_plugin_metademands_metademands_resources`;
CREATE TABLE `glpi_plugin_metademands_metademands_resources`
(
    `id`                                int unsigned NOT NULL AUTO_INCREMENT,
    `entities_id`                       int unsigned NOT NULL default '0',
    `plugin_resources_contracttypes_id` int unsigned NOT NULL default '0',
    `plugin_metademands_metademands_id` int unsigned NOT NULL default '0',
    PRIMARY KEY (`id`),
    KEY                                 `entities_id` (`entities_id`),
    KEY                                 `plugin_resources_contracttypes_id` (`plugin_resources_contracttypes_id`),
    KEY                                 `plugin_metademands_metademands_id` (`plugin_metademands_metademands_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------
--
-- Structure de la table 'glpi_plugin_metademands_configs'
--
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `glpi_plugin_metademands_configs`;
CREATE TABLE `glpi_plugin_metademands_configs`
(
    `id`                                int unsigned NOT NULL auto_increment,
    `simpleticket_to_metademand`        tinyint               default '0',
    `parent_ticket_tag`                 varchar(255)          default NULL,
    `son_ticket_tag`                    varchar(255)          default NULL,
    `create_pdf`                        tinyint               default '0',
    `show_requester_informations`       tinyint               default 0,
    `childs_parent_content`             tinyint               default 0,
    `display_type`                      tinyint               default 1,
    `display_buttonlist_servicecatalog` tinyint               default 1,
    `title_servicecatalog`              varchar(255)          DEFAULT NULL,
    `comment_servicecatalog`            text                  DEFAULT NULL,
    `fa_servicecatalog`                 varchar(100) NOT NULL DEFAULT 'ti ti-share',
    `languageTech`                      varchar(100)          DEFAULT NULL,
    `use_draft`                         tinyint               default 0,
    `show_form_changes`                 tinyint      NOT NULL DEFAULT '0',
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

INSERT INTO `glpi_plugin_metademands_configs` (`id`, `simpleticket_to_metademand`, `childs_parent_content`)
VALUES ('1', '1', '1');

-- --------------------------------------------------------
--
-- Structure de la table 'glpi_plugin_metademands_basketlines'
--
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `glpi_plugin_metademands_basketlines`;
CREATE TABLE `glpi_plugin_metademands_basketlines`
(
    `id`                                int unsigned NOT NULL AUTO_INCREMENT,
    `users_id`                          int unsigned NOT NULL default '0',
    `plugin_metademands_metademands_id` int unsigned NOT NULL default '0',
    `plugin_metademands_fields_id`      int unsigned NOT NULL default '0',
    `line`                              int unsigned NOT NULL default '0',
    `name`                              varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    `value`                             text COLLATE utf8mb4_unicode_ci,
    `value2`                            text COLLATE utf8mb4_unicode_ci,
    PRIMARY KEY (`id`),
    KEY                                 `users_id` (`users_id`),
    KEY                                 `plugin_metademands_metademands_id` (`plugin_metademands_metademands_id`),
    KEY                                 `plugin_metademands_fields_id` (`plugin_metademands_fields_id`),
    UNIQUE KEY `unicity` (`plugin_metademands_metademands_id`,`plugin_metademands_fields_id`,`line`,`name`,`users_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

DROP TABLE IF EXISTS `glpi_plugin_metademands_fieldtranslations`;
CREATE TABLE `glpi_plugin_metademands_fieldtranslations`
(
    `id`       int unsigned NOT NULL AUTO_INCREMENT,
    `items_id` int unsigned NOT NULL DEFAULT '0',
    `itemtype` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    `language` varchar(5) COLLATE utf8mb4_unicode_ci   DEFAULT NULL,
    `field`    varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    `value`    text COLLATE utf8mb4_unicode_ci         DEFAULT NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

DROP TABLE IF EXISTS `glpi_plugin_metademands_metademandtranslations`;
CREATE TABLE `glpi_plugin_metademands_metademandtranslations`
(
    `id`       int unsigned NOT NULL AUTO_INCREMENT,
    `items_id` int unsigned NOT NULL DEFAULT '0',
    `itemtype` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    `language` varchar(5) COLLATE utf8mb4_unicode_ci   DEFAULT NULL,
    `field`    varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    `value`    text COLLATE utf8mb4_unicode_ci         DEFAULT NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

DROP TABLE IF EXISTS `glpi_plugin_metademands_metademandvalidations`;
CREATE TABLE `glpi_plugin_metademands_metademandvalidations`
(
    `id`                                int unsigned NOT NULL AUTO_INCREMENT,
    `tickets_id`                        int unsigned NOT NULL DEFAULT '0',
    `plugin_metademands_metademands_id` int unsigned NOT NULL DEFAULT '0',
    `users_id`                          int unsigned NOT NULL DEFAULT '0',
    `validate`                          tinyint   NOT NULL DEFAULT '0',
    `date`                              timestamp NOT NULL,
    `tickets_to_create`                 text      NOT NULL,
    PRIMARY KEY (`id`),
    KEY                                 `users_id` (`users_id`),
    KEY                                 `tickets_id` (`tickets_id`),
    KEY                                 `plugin_metademands_metademands_id` (`plugin_metademands_metademands_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

DROP TABLE IF EXISTS `glpi_plugin_metademands_drafts_values`;
CREATE TABLE `glpi_plugin_metademands_drafts_values`
(
    `id`                           int unsigned NOT NULL AUTO_INCREMENT,
    `plugin_metademands_drafts_id` int unsigned NOT NULL DEFAULT '0',
    `plugin_metademands_fields_id` int unsigned NOT NULL DEFAULT '0',
    `value`                        text NOT NULL,
    `value2`                       text NOT NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

DROP TABLE IF EXISTS `glpi_plugin_metademands_drafts`;
CREATE TABLE `glpi_plugin_metademands_drafts`
(
    `id`                                int unsigned NOT NULL AUTO_INCREMENT,
    `name`                              VARCHAR(255) NOT NULL DEFAULT '0',
    `plugin_metademands_metademands_id` int unsigned NOT NULL DEFAULT '0',
    `users_id`                          int unsigned NOT NULL DEFAULT '0',
    `date`                              timestamp    NOT NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

DROP TABLE IF EXISTS `glpi_plugin_metademands_pluginfields`;
CREATE TABLE `glpi_plugin_metademands_pluginfields`
(
    `id`                                int unsigned NOT NULL AUTO_INCREMENT,
    `plugin_fields_fields_id`           int unsigned NOT NULL DEFAULT '0',
    `plugin_metademands_fields_id`      int unsigned NOT NULL DEFAULT '0',
    `plugin_metademands_metademands_id` int unsigned NOT NULL DEFAULT '0',
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

DROP TABLE IF EXISTS `glpi_plugin_metademands_forms`;
CREATE TABLE `glpi_plugin_metademands_forms`
(
    `id`                                int unsigned NOT NULL AUTO_INCREMENT,
    `name`                              VARCHAR(255) NOT NULL                   DEFAULT '0',
    `plugin_metademands_metademands_id` int unsigned NOT NULL DEFAULT '0',
    `items_id`                          int unsigned NOT NULL DEFAULT '0',
    `itemtype`                          varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    `users_id`                          int unsigned NOT NULL DEFAULT '0',
    `date`                              timestamp    NOT NULL,
    `is_model`                          tinyint      NOT NULL                   DEFAULT '0',
    `resources_id`                      int unsigned NOT NULL default '0',
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

DROP TABLE IF EXISTS `glpi_plugin_metademands_forms_values`;
CREATE TABLE `glpi_plugin_metademands_forms_values`
(
    `id`                           int unsigned NOT NULL AUTO_INCREMENT,
    `plugin_metademands_forms_id`  int unsigned NOT NULL DEFAULT '0',
    `plugin_metademands_fields_id` int unsigned NOT NULL DEFAULT '0',
    `value`                        text NOT NULL,
    `value2`                       text NOT NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

CREATE TABLE `glpi_plugin_metademands_interticketfollowups`
(
    `id`                int unsigned NOT NULL AUTO_INCREMENT,
    `tickets_id`        int unsigned NOT NULL DEFAULT '0',
    `targets_id`        int unsigned NOT NULL DEFAULT '0',
    `date`              timestamp NULL DEFAULT NULL,
    `users_id`          int unsigned NOT NULL DEFAULT '0',
    `users_id_editor`   int unsigned NOT NULL DEFAULT '0',
    `content`           longtext COLLATE utf8_unicode_ci,
    `is_private`        tinyint NOT NULL DEFAULT '0',
    `requesttypes_id`   int unsigned NOT NULL DEFAULT '0', -- todo keep it ?
    `date_mod`          timestamp NULL DEFAULT NULL,
    `date_creation`     timestamp NULL DEFAULT NULL,
    `timeline_position` tinyint NOT NULL DEFAULT '0',
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

CREATE TABLE `glpi_plugin_metademands_stepforms`
(
    `id`                                int unsigned NOT NULL AUTO_INCREMENT,
    `name`                              VARCHAR(255) NOT NULL DEFAULT '0',
    `plugin_metademands_metademands_id` int unsigned NOT NULL DEFAULT '0',
    `items_id`                          int unsigned NOT NULL DEFAULT '0',
    `users_id`                          int unsigned NOT NULL DEFAULT '0',
    `groups_id_dest`                    int unsigned NOT NULL DEFAULT '0',
    `date`                              timestamp NULL DEFAULT NULL,
    `reminder_date`                     timestamp NULL DEFAULT NULL,
    `block_id`                          int unsigned NOT NULL DEFAULT '0',
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

CREATE TABLE `glpi_plugin_metademands_stepforms_values`
(
    `id`                              int unsigned NOT NULL AUTO_INCREMENT,
    `plugin_metademands_stepforms_id` int unsigned NOT NULL DEFAULT '0',
    `plugin_metademands_fields_id`    int unsigned NOT NULL DEFAULT '0',
    `value`                           text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    `value2`                          text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

CREATE TABLE `glpi_plugin_metademands_steps`
(
    `id`                                int unsigned NOT NULL AUTO_INCREMENT,
    `plugin_metademands_metademands_id` int unsigned NOT NULL DEFAULT '0',
    `block_id`                          int unsigned NOT NULL DEFAULT '0',
    `groups_id`                         int unsigned NOT NULL DEFAULT '0',
    `reminder_delay`                    text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    `message`                           text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
