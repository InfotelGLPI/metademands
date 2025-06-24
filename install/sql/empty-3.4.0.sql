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
    `id`                               int unsigned NOT NULL AUTO_INCREMENT,
    `name`                             varchar(255)                            DEFAULT NULL,
    `entities_id`                      int unsigned NOT NULL                   DEFAULT '0',
    `is_recursive`                     int          NOT NULL                   DEFAULT '0',
    `is_template`                      tinyint      NOT NULL                   DEFAULT '0',
    `template_name`                    varchar(255)                            DEFAULT NULL,
    `is_active`                        tinyint      NOT NULL                   DEFAULT '1',
    `maintenance_mode`                 tinyint      NOT NULL                   DEFAULT '0',
    `can_update`                       tinyint      NOT NULL                   DEFAULT '0',
    `can_clone`                        tinyint      NOT NULL                   DEFAULT '0',
    `comment`                          text COLLATE utf8mb4_unicode_ci         DEFAULT NULL,
    `object_to_create`                 varchar(255) collate utf8mb4_unicode_ci DEFAULT NULL,
    `type`                             int          NOT NULL                   DEFAULT '0',
    `itilcategories_id`                text COLLATE utf8mb4_unicode_ci         NOT NULL,
    `icon`                             varchar(255)                            DEFAULT NULL,
    `is_order`                         tinyint                                 DEFAULT 0,
    `create_one_ticket`                tinyint      NOT NULL                   DEFAULT '0',
    `force_create_tasks`               tinyint      NOT NULL                   DEFAULT '0',
    `date_creation`                    timestamp    NULL                       DEFAULT NULL,
    `date_mod`                         timestamp    NULL                       DEFAULT NULL,
    `validation_subticket`             tinyint      NOT NULL                   DEFAULT '0',
    `is_deleted`                       tinyint      NOT NULL                   DEFAULT '0',
    `hide_no_field`                    tinyint                                 DEFAULT '0',
    `hide_title`                       tinyint                                 DEFAULT '0',
    `title_color`                      varchar(255)                            DEFAULT '#000000',
    `background_color`                 varchar(255)                            DEFAULT '#FFFFFF',
    `step_by_step_mode`                tinyint      NOT NULL                   DEFAULT '0',
    `show_rule`                        tinyint      NOT NULL                   DEFAULT '1',
    `initial_requester_childs_tickets` tinyint      NOT NULL                   DEFAULT '1',
    `is_basket`                        tinyint                                 DEFAULT 0,
    `use_confirm`                      tinyint      NOT NULL                   DEFAULT '0',
    PRIMARY KEY (`id`),
    KEY `name` (`name`),
    KEY `entities_id` (`entities_id`),
    KEY `is_recursive` (`is_recursive`),
    KEY `is_template` (`is_template`),
    KEY `is_deleted` (`is_deleted`)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_ci
  ROW_FORMAT = DYNAMIC;


-- ----------------------------------------------------------
--
-- Structure de la table 'glpi_plugin_metademands_tasks'
--
-- ----------------------------------------------------------
DROP TABLE IF EXISTS `glpi_plugin_metademands_tasks`;
CREATE TABLE `glpi_plugin_metademands_tasks`
(
    `id`                                int unsigned NOT NULL AUTO_INCREMENT,
    `name`                              varchar(255)                    DEFAULT NULL,
    `completename`                      varchar(255)                    DEFAULT NULL,
    `comment`                           text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    `entities_id`                       int unsigned NOT NULL           DEFAULT '0',
    `is_recursive`                      int          NOT NULL           DEFAULT '0',
    `level`                             int          NOT NULL           DEFAULT '0',
    `type`                              int          NOT NULL           DEFAULT '0',
    `ancestors_cache`                   text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    `sons_cache`                        text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    `plugin_metademands_tasks_id`       int unsigned NOT NULL           DEFAULT '0',
    `plugin_metademands_metademands_id` int unsigned NOT NULL           DEFAULT '0',
    `block_use`                         varchar(255) NOT NULL           DEFAULT '[]',
    `useBlock`                          tinyint      NOT NULL           DEFAULT '1',
    `formatastable`                     tinyint      NOT NULL           DEFAULT '1',
    `block_parent_ticket_resolution`    tinyint      NOT NULL           DEFAULT '1',
    PRIMARY KEY (`id`),
    KEY `plugin_metademands_metademands_id` (`plugin_metademands_metademands_id`),
    KEY `plugin_metademands_tasks_id` (`plugin_metademands_tasks_id`),
    KEY `entities_id` (`entities_id`),
    KEY `type` (`type`)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_ci
  ROW_FORMAT = DYNAMIC;


-- ------------------------------------------------------------
--
-- Structure de la table 'glpi_plugin_metademands_fields'
--
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `glpi_plugin_metademands_fields`;
CREATE TABLE `glpi_plugin_metademands_fields`
(
    `id`                                  int unsigned NOT NULL AUTO_INCREMENT,
    `entities_id`                         int unsigned NOT NULL DEFAULT '0',
    `is_recursive`                        int          NOT NULL           DEFAULT '0',
    `comment`                             text COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
    `rank`                                int          NOT NULL           DEFAULT '0',
    `order`                               int          NOT NULL           DEFAULT '0',
    `name`                                varchar(255)                    DEFAULT NULL,
    `label2`                              text COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
    `type`                                varchar(255)                    DEFAULT NULL,
    `item`                                varchar(255)                    DEFAULT NULL,
    `plugin_metademands_fields_id`        int unsigned NOT NULL DEFAULT '0',
    `plugin_metademands_metademands_id`   int unsigned NOT NULL DEFAULT '0',
    `date_creation`                       timestamp NULL DEFAULT NULL,
    `date_mod`                            timestamp NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY                                   `plugin_metademands_fields_id` (`plugin_metademands_fields_id`),
    KEY                                   `plugin_metademands_metademands_id` (`plugin_metademands_metademands_id`)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_ci
  ROW_FORMAT = DYNAMIC;


-- ------------------------------------------------------------
--
-- Structure de la table 'glpi_plugin_metademands_fields'
--
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `glpi_plugin_metademands_fieldparameters`;
CREATE TABLE `glpi_plugin_metademands_fieldparameters`
(
    `id`                                  int unsigned NOT NULL AUTO_INCREMENT,
    `plugin_metademands_fields_id`        int unsigned NOT NULL DEFAULT '0',
    `custom`                              text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    `default`                             text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    `hide_title`                          tinyint      NOT NULL           DEFAULT '0',
    `is_mandatory`                        int          NOT NULL           DEFAULT '0',
    `max_upload`                          int          NOT NULL           DEFAULT 0,
    `regex`                               VARCHAR(255) NOT NULL           DEFAULT '',
    `color`                               varchar(255)                    DEFAULT NULL,
    `row_display`                         tinyint                         DEFAULT 0,
    `is_basket`                           tinyint                         DEFAULT 0,
    `display_type`                        int                             DEFAULT 0,
    `used_by_ticket`                      int          NOT NULL           DEFAULT '0',
    `used_by_child`                       tinyint                         DEFAULT 0,
    `link_to_user`                        int                             DEFAULT 0,
    `default_use_id_requester`            int unsigned DEFAULT 0,
    `default_use_id_requester_supervisor` int unsigned DEFAULT 0,
    `use_future_date`                     tinyint                         DEFAULT 0,
    `use_date_now`                        tinyint                         DEFAULT 0,
    `additional_number_day`               int                             DEFAULT 0,
    `informations_to_display`             varchar(255) NOT NULL           DEFAULT '[]',
    `use_richtext`                        tinyint      NOT NULL           DEFAULT '1',
    `icon`                                varchar(255)                    DEFAULT NULL,
    `readonly`                            tinyint                         DEFAULT 0,
    `hidden`                              tinyint                         DEFAULT 0,
    PRIMARY KEY (`id`),
    KEY                                   `plugin_metademands_fields_id` (`plugin_metademands_fields_id`)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_ci
  ROW_FORMAT = DYNAMIC;

DROP TABLE IF EXISTS `glpi_plugin_metademands_fieldcustomvalues`;
CREATE TABLE `glpi_plugin_metademands_fieldcustomvalues`
(
    `id`                           int unsigned NOT NULL AUTO_INCREMENT,
    `plugin_metademands_fields_id` int unsigned NOT NULL           DEFAULT '0',
    `name`                         VARCHAR(255) NOT NULL           DEFAULT '0',
    `is_default`                   int          NOT NULL           DEFAULT '0',
    `comment`                      text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    `rank`                         int          NOT NULL           DEFAULT '0',
    `icon`                         VARCHAR(255)                    DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `plugin_metademands_fields_id` (`plugin_metademands_fields_id`)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_ci
  ROW_FORMAT = DYNAMIC;


DROP TABLE IF EXISTS `glpi_plugin_metademands_freetablefields`;
CREATE TABLE `glpi_plugin_metademands_freetablefields`
(
    `id`                           int unsigned NOT NULL AUTO_INCREMENT,
    `plugin_metademands_fields_id` int unsigned NOT NULL           DEFAULT '0',
    `internal_name`                VARCHAR(255) NOT NULL           DEFAULT '0',
    `type`                         VARCHAR(255)                    DEFAULT NULL,
    `name`                         VARCHAR(255) NOT NULL           DEFAULT '0',
    `dropdown_values`              text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    `is_mandatory`                 int          NOT NULL           DEFAULT '0',
    `comment`                      text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    `rank`                         int          NOT NULL           DEFAULT '0',
    PRIMARY KEY (`id`),
    KEY `plugin_metademands_fields_id` (`plugin_metademands_fields_id`)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_ci
  ROW_FORMAT = DYNAMIC;

-- ------------------------------------------------------------
--
-- Structure de la table 'glpi_plugin_metademands_fieldoptions'
--
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `glpi_plugin_metademands_fieldoptions`;
CREATE TABLE `glpi_plugin_metademands_fieldoptions`
(
    `id`                           int unsigned NOT NULL AUTO_INCREMENT,
    `plugin_metademands_fields_id` int unsigned NOT NULL DEFAULT '0',
    `check_value`                  int          NOT NULL DEFAULT '0',
    `plugin_metademands_tasks_id`  int unsigned NOT NULL DEFAULT '0',
    `fields_link`                  int unsigned NOT NULL DEFAULT '0',
    `hidden_link`                  int unsigned NOT NULL DEFAULT '0',
    `hidden_block`                 int unsigned NOT NULL DEFAULT '0',
    `hidden_block_same_block`      tinyint      NOT NULL DEFAULT '0',
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

-- ------------------------------------------------------------
--
-- Structure de la table 'glpi_plugin_metademands_tickets_fields'
--
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `glpi_plugin_metademands_tickets_fields`;
CREATE TABLE `glpi_plugin_metademands_tickets_fields`
(
    `id`                           int unsigned NOT NULL AUTO_INCREMENT,
    `value`                        text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    `value2`                       text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    `tickets_id`                   int unsigned NOT NULL           DEFAULT '0',
    `plugin_metademands_fields_id` int unsigned NOT NULL           DEFAULT '0',
    PRIMARY KEY (`id`),
    KEY `plugin_metademands_fields_id` (`plugin_metademands_fields_id`),
    KEY `tickets_id` (`tickets_id`)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_ci
  ROW_FORMAT = DYNAMIC;

-- ------------------------------------------------------------
--
-- Structure de la table 'glpi_plugin_metademands_ticketfields'
--
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `glpi_plugin_metademands_ticketfields`;
CREATE TABLE `glpi_plugin_metademands_ticketfields`
(
    `id`                                int unsigned NOT NULL AUTO_INCREMENT,
    `num`                               int                             DEFAULT NULL,
    `value`                             text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    `entities_id`                       int unsigned NOT NULL           DEFAULT '0',
    `is_recursive`                      int          NOT NULL           DEFAULT '0',
    `is_mandatory`                      int          NOT NULL           DEFAULT '0',
    `is_deletable`                      int          NOT NULL           DEFAULT '1',
    `plugin_metademands_metademands_id` int unsigned NOT NULL           DEFAULT '0',
    PRIMARY KEY (`id`),
    KEY `entities_id` (`entities_id`),
    KEY `plugin_metademands_metademands_id` (`plugin_metademands_metademands_id`)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_ci
  ROW_FORMAT = DYNAMIC;

-- ------------------------------------------------------------
--
-- Structure de la table 'glpi_plugin_metademands_tickettasks'
--
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `glpi_plugin_metademands_tickettasks`;
CREATE TABLE `glpi_plugin_metademands_tickettasks`
(
    `id`                          int unsigned NOT NULL AUTO_INCREMENT,
    `entities_id`                 int unsigned NOT NULL           DEFAULT '0',
    `content`                     text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    `itilcategories_id`           int unsigned                    DEFAULT '0',
    `type`                        int          NOT NULL           DEFAULT '0',
    `status`                      varchar(255)                    DEFAULT NULL,
    `actiontime`                  int          NOT NULL           DEFAULT '0',
    `requesttypes_id`             int unsigned NOT NULL           DEFAULT '0',
    `groups_id_assign`            int unsigned NOT NULL           DEFAULT '0',
    `users_id_assign`             int unsigned NOT NULL           DEFAULT '0',
    `groups_id_requester`         int unsigned NOT NULL           DEFAULT '0',
    `users_id_requester`          int unsigned NOT NULL           DEFAULT '0',
    `groups_id_observer`          int unsigned NOT NULL           DEFAULT '0',
    `users_id_observer`           int unsigned NOT NULL           DEFAULT '0',
    `plugin_metademands_tasks_id` int unsigned NOT NULL           DEFAULT '0',
    PRIMARY KEY (`id`),
    KEY `plugin_metademands_tasks_id` (`plugin_metademands_tasks_id`),
    KEY `itilcategories_id` (`itilcategories_id`),
    KEY `groups_id_assign` (`groups_id_assign`),
    KEY `users_id_assign` (`users_id_assign`),
    KEY `groups_id_requester` (`groups_id_requester`),
    KEY `users_id_requester` (`users_id_requester`),
    KEY `groups_id_observer` (`groups_id_observer`),
    KEY `users_id_observer` (`users_id_observer`)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_ci
  ROW_FORMAT = DYNAMIC;

-- ----------------------------------------------------------
--
-- Structure de la table 'glpi_plugin_metademands_tickets_tasks'
--
-- ----------------------------------------------------------
DROP TABLE IF EXISTS `glpi_plugin_metademands_tickets_tasks`;
CREATE TABLE `glpi_plugin_metademands_tickets_tasks`
(
    `id`                          int unsigned NOT NULL AUTO_INCREMENT,
    `plugin_metademands_tasks_id` int unsigned NOT NULL DEFAULT '0',
    `level`                       int          NOT NULL DEFAULT '0',
    `tickets_id`                  int unsigned NOT NULL DEFAULT '0',
    `parent_tickets_id`           int unsigned NOT NULL DEFAULT '0',
    PRIMARY KEY (`id`),
    KEY `plugin_metademands_tasks_id` (`plugin_metademands_tasks_id`),
    KEY `tickets_id` (`tickets_id`),
    KEY `parent_tickets_id` (`parent_tickets_id`)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_ci
  ROW_FORMAT = DYNAMIC;

-- ----------------------------------------------------------
--
-- Structure de la table 'glpi_plugin_metademands_tickets_metademands'
--
-- ----------------------------------------------------------
DROP TABLE IF EXISTS `glpi_plugin_metademands_tickets_metademands`;
CREATE TABLE `glpi_plugin_metademands_tickets_metademands`
(
    `id`                                int unsigned NOT NULL AUTO_INCREMENT,
    `plugin_metademands_metademands_id` int unsigned NOT NULL DEFAULT '0',
    `tickets_id`                        int unsigned NOT NULL DEFAULT '0',
    `parent_tickets_id`                 int unsigned NOT NULL DEFAULT '0',
    `tickettemplates_id`                int unsigned NOT NULL DEFAULT '0',
    `status`                            tinyint      NOT NULL DEFAULT '1',
    PRIMARY KEY (`id`),
    KEY `plugin_metademands_metademands_id` (`plugin_metademands_metademands_id`),
    KEY `tickets_id` (`tickets_id`),
    KEY `parent_tickets_id` (`parent_tickets_id`),
    KEY `tickettemplates_id` (`tickettemplates_id`)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_ci
  ROW_FORMAT = DYNAMIC;

-- ----------------------------------------------------------
--
-- Structure de la table 'glpi_plugin_metademands_metademandtasks'
--
-- ----------------------------------------------------------
DROP TABLE IF EXISTS `glpi_plugin_metademands_metademandtasks`;
CREATE TABLE `glpi_plugin_metademands_metademandtasks`
(
    `id`                                int unsigned NOT NULL AUTO_INCREMENT,
    `entities_id`                       int unsigned NOT NULL DEFAULT '0',
    `plugin_metademands_metademands_id` int unsigned NOT NULL DEFAULT '0',
    `plugin_metademands_tasks_id`       int unsigned NOT NULL DEFAULT '0',
    PRIMARY KEY (`id`),
    KEY `plugin_metademands_metademands_id` (`plugin_metademands_metademands_id`),
    KEY `entities_id` (`entities_id`),
    KEY `plugin_metademands_tasks_id` (`plugin_metademands_tasks_id`)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_ci
  ROW_FORMAT = DYNAMIC;

-- ----------------------------------------------------------
--
-- Structure de la table 'glpi_plugin_metademands_groups'
--
-- ----------------------------------------------------------
DROP TABLE IF EXISTS `glpi_plugin_metademands_groups`;
CREATE TABLE `glpi_plugin_metademands_groups`
(
    `id`                                int unsigned NOT NULL AUTO_INCREMENT,
    `entities_id`                       int unsigned NOT NULL DEFAULT '0',
    `groups_id`                         int unsigned NOT NULL DEFAULT '0',
    `plugin_metademands_metademands_id` int unsigned NOT NULL DEFAULT '0',
    PRIMARY KEY (`id`),
    KEY (`plugin_metademands_metademands_id`),
    KEY `entities_id` (`entities_id`),
    KEY (`groups_id`)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_ci
  ROW_FORMAT = DYNAMIC;

-- ----------------------------------------------------------
--
-- Structure de la table 'glpi_plugin_metademands_groupconfigs'
--
-- ----------------------------------------------------------
DROP TABLE IF EXISTS `glpi_plugin_metademands_groupconfigs`;
CREATE TABLE `glpi_plugin_metademands_groupconfigs`
(
    `id`                                int unsigned NOT NULL AUTO_INCREMENT,
    `entities_id`                       int unsigned NOT NULL DEFAULT '0',
    `visibility`                        int          NOT NULL DEFAULT '0',
    `plugin_metademands_metademands_id` int unsigned NOT NULL DEFAULT '0',
    PRIMARY KEY (`id`),
    KEY (`plugin_metademands_metademands_id`),
    KEY `entities_id` (`entities_id`)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_ci
  ROW_FORMAT = DYNAMIC;


-- --------------------------------------------------------
--
-- Structure de la table 'glpi_plugin_metademands_metademands_resources'
--
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `glpi_plugin_metademands_metademands_resources`;
CREATE TABLE `glpi_plugin_metademands_metademands_resources`
(
    `id`                                int unsigned NOT NULL AUTO_INCREMENT,
    `entities_id`                       int unsigned NOT NULL DEFAULT '0',
    `plugin_resources_contracttypes_id` int unsigned NOT NULL DEFAULT '0',
    `plugin_metademands_metademands_id` int unsigned NOT NULL DEFAULT '0',
    PRIMARY KEY (`id`),
    KEY `entities_id` (`entities_id`),
    KEY `plugin_resources_contracttypes_id` (`plugin_resources_contracttypes_id`),
    KEY `plugin_metademands_metademands_id` (`plugin_metademands_metademands_id`)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_ci
  ROW_FORMAT = DYNAMIC;

-- --------------------------------------------------------
--
-- Structure de la table 'glpi_plugin_metademands_configs'
--
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `glpi_plugin_metademands_configs`;
CREATE TABLE `glpi_plugin_metademands_configs`
(
    `id`                                int unsigned NOT NULL auto_increment,
    `simpleticket_to_metademand`        tinyint               DEFAULT '0',
    `parent_ticket_tag`                 varchar(255)          DEFAULT NULL,
    `son_ticket_tag`                    varchar(255)          DEFAULT NULL,
    `create_pdf`                        tinyint               DEFAULT '0',
    `show_requester_informations`       tinyint               DEFAULT 0,
    `childs_parent_content`             tinyint               DEFAULT 0,
    `display_type`                      tinyint               DEFAULT 1,
    `display_buttonlist_servicecatalog` tinyint               DEFAULT 1,
    `title_servicecatalog`              varchar(255)          DEFAULT NULL,
    `comment_servicecatalog`            text                  DEFAULT NULL,
    `fa_servicecatalog`                 varchar(100) NOT NULL DEFAULT 'fas fa-share-alt',
    `languageTech`                      varchar(100)          DEFAULT NULL,
    `use_draft`                         tinyint               DEFAULT 0,
    `show_form_changes`                 tinyint      NOT NULL DEFAULT '0',
    `add_groups_with_regex`             tinyint      NOT NULL DEFAULT '0',
    `icon_request`                      varchar(255)          DEFAULT NULL,
    `icon_incident`                     varchar(255)          DEFAULT NULL,
    `icon_problem`                      varchar(255)          DEFAULT NULL,
    `icon_change`                       varchar(255)          DEFAULT NULL,
    `see_top`                           tinyint      NOT NULL DEFAULT '1',
    PRIMARY KEY (`id`)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_ci
  ROW_FORMAT = DYNAMIC;

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
    `users_id`                          int unsigned NOT NULL                   DEFAULT '0',
    `plugin_metademands_metademands_id` int unsigned NOT NULL                   DEFAULT '0',
    `plugin_metademands_fields_id`      int unsigned NOT NULL                   DEFAULT '0',
    `line`                              int          NOT NULL                   DEFAULT '0',
    `name`                              varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    `value`                             text COLLATE utf8mb4_unicode_ci,
    `value2`                            text COLLATE utf8mb4_unicode_ci,
    PRIMARY KEY (`id`),
    UNIQUE KEY `unicity` (`plugin_metademands_metademands_id`, `plugin_metademands_fields_id`, `line`, `name`,
                          `users_id`),
    KEY `users_id` (`users_id`),
    KEY `plugin_metademands_metademands_id` (`plugin_metademands_metademands_id`),
    KEY `plugin_metademands_fields_id` (`plugin_metademands_fields_id`)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_ci
  ROW_FORMAT = DYNAMIC;

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

DROP TABLE IF EXISTS `glpi_plugin_metademands_metademandvalidations`;
CREATE TABLE `glpi_plugin_metademands_metademandvalidations`
(
    `id`                                int unsigned NOT NULL AUTO_INCREMENT,
    `tickets_id`                        int unsigned NOT NULL DEFAULT '0',
    `plugin_metademands_metademands_id` int unsigned NOT NULL DEFAULT '0',
    `users_id`                          int unsigned NOT NULL DEFAULT '0',
    `validate`                          tinyint      NOT NULL DEFAULT '0',
    `date`                              timestamp    NOT NULL,
    `tickets_to_create`                 text         NOT NULL,
    PRIMARY KEY (`id`),
    KEY `users_id` (`users_id`),
    KEY `tickets_id` (`tickets_id`),
    KEY `plugin_metademands_metademands_id` (`plugin_metademands_metademands_id`)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_ci
  ROW_FORMAT = DYNAMIC;

DROP TABLE IF EXISTS `glpi_plugin_metademands_drafts_values`;
CREATE TABLE `glpi_plugin_metademands_drafts_values`
(
    `id`                           int unsigned NOT NULL AUTO_INCREMENT,
    `plugin_metademands_drafts_id` int unsigned NOT NULL DEFAULT '0',
    `plugin_metademands_fields_id` int unsigned NOT NULL DEFAULT '0',
    `value`                        text         NOT NULL,
    `value2`                       text         NOT NULL,
    PRIMARY KEY (`id`),
    KEY `plugin_metademands_drafts_id` (`plugin_metademands_drafts_id`),
    KEY `plugin_metademands_fields_id` (`plugin_metademands_fields_id`)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_ci
  ROW_FORMAT = DYNAMIC;

DROP TABLE IF EXISTS `glpi_plugin_metademands_drafts`;
CREATE TABLE `glpi_plugin_metademands_drafts`
(
    `id`                                int unsigned NOT NULL AUTO_INCREMENT,
    `name`                              VARCHAR(255) NOT NULL DEFAULT '0',
    `plugin_metademands_metademands_id` int unsigned NOT NULL DEFAULT '0',
    `users_id`                          int unsigned NOT NULL DEFAULT '0',
    `date`                              timestamp    NOT NULL,
    PRIMARY KEY (`id`),
    KEY `plugin_metademands_metademands_id` (`plugin_metademands_metademands_id`)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_ci
  ROW_FORMAT = DYNAMIC;

DROP TABLE IF EXISTS `glpi_plugin_metademands_pluginfields`;
CREATE TABLE `glpi_plugin_metademands_pluginfields`
(
    `id`                                int unsigned NOT NULL AUTO_INCREMENT,
    `plugin_fields_fields_id`           int unsigned NOT NULL DEFAULT '0',
    `plugin_metademands_fields_id`      int unsigned NOT NULL DEFAULT '0',
    `plugin_metademands_metademands_id` int unsigned NOT NULL DEFAULT '0',
    PRIMARY KEY (`id`),
    KEY `plugin_fields_fields_id` (`plugin_fields_fields_id`),
    KEY `plugin_metademands_fields_id` (`plugin_metademands_fields_id`),
    KEY `plugin_metademands_metademands_id` (`plugin_metademands_metademands_id`)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_ci
  ROW_FORMAT = DYNAMIC;

DROP TABLE IF EXISTS `glpi_plugin_metademands_forms`;
CREATE TABLE `glpi_plugin_metademands_forms`
(
    `id`                                int unsigned NOT NULL AUTO_INCREMENT,
    `name`                              VARCHAR(255) NOT NULL                   DEFAULT '0',
    `plugin_metademands_metademands_id` int unsigned NOT NULL                   DEFAULT '0',
    `items_id`                          int unsigned NOT NULL                   DEFAULT '0',
    `itemtype`                          varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    `users_id`                          int unsigned NOT NULL                   DEFAULT '0',
    `date`                              timestamp    NOT NULL,
    `is_model`                          tinyint      NOT NULL                   DEFAULT '0',
    `resources_id`                      int unsigned NOT NULL                   DEFAULT '0',
    PRIMARY KEY (`id`),
    KEY `plugin_metademands_metademands_id` (`plugin_metademands_metademands_id`)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_ci
  ROW_FORMAT = DYNAMIC;

DROP TABLE IF EXISTS `glpi_plugin_metademands_forms_values`;
CREATE TABLE `glpi_plugin_metademands_forms_values`
(
    `id`                           int unsigned NOT NULL AUTO_INCREMENT,
    `plugin_metademands_forms_id`  int unsigned NOT NULL DEFAULT '0',
    `plugin_metademands_fields_id` int unsigned NOT NULL DEFAULT '0',
    `value`                        text         NOT NULL,
    `value2`                       text         NOT NULL,
    PRIMARY KEY (`id`),
    KEY `plugin_metademands_forms_id` (`plugin_metademands_forms_id`),
    KEY `plugin_metademands_fields_id` (`plugin_metademands_fields_id`)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_ci
  ROW_FORMAT = DYNAMIC;

CREATE TABLE `glpi_plugin_metademands_interticketfollowups`
(
    `id`                int unsigned NOT NULL AUTO_INCREMENT,
    `tickets_id`        int unsigned NOT NULL DEFAULT '0',
    `targets_id`        int unsigned NOT NULL DEFAULT '0',
    `date`              timestamp    NULL     DEFAULT NULL,
    `users_id`          int unsigned NOT NULL DEFAULT '0',
    `users_id_editor`   int unsigned NOT NULL DEFAULT '0',
    `content`           longtext COLLATE utf8mb4_unicode_ci,
    `is_private`        tinyint      NOT NULL DEFAULT '0',
    `requesttypes_id`   int unsigned NOT NULL DEFAULT '0',
    `date_mod`          timestamp    NULL     DEFAULT NULL,
    `date_creation`     timestamp    NULL     DEFAULT NULL,
    `timeline_position` tinyint      NOT NULL DEFAULT '0',
    PRIMARY KEY (`id`)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_ci
  ROW_FORMAT = DYNAMIC;

CREATE TABLE `glpi_plugin_metademands_stepforms`
(
    `id`                                int unsigned NOT NULL AUTO_INCREMENT,
    `name`                              VARCHAR(255) NOT NULL DEFAULT '0',
    `plugin_metademands_metademands_id` int unsigned NOT NULL DEFAULT '0',
    `items_id`                          int unsigned NOT NULL DEFAULT '0',
    `users_id`                          int unsigned NOT NULL DEFAULT '0',
    `groups_id_dest`                    int unsigned NOT NULL DEFAULT '0',
    `users_id_dest`                     int unsigned NOT NULL DEFAULT '0',
    `date`                              timestamp    NULL     DEFAULT NULL,
    `reminder_date`                     timestamp    NULL     DEFAULT NULL,
    `block_id`                          int unsigned NOT NULL DEFAULT '0',
    PRIMARY KEY (`id`),
    KEY `plugin_metademands_metademands_id` (`plugin_metademands_metademands_id`)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_ci
  ROW_FORMAT = DYNAMIC;

CREATE TABLE `glpi_plugin_metademands_stepforms_values`
(
    `id`                              int unsigned NOT NULL AUTO_INCREMENT,
    `plugin_metademands_stepforms_id` int unsigned NOT NULL           DEFAULT '0',
    `plugin_metademands_fields_id`    int unsigned NOT NULL           DEFAULT '0',
    `value`                           text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    `value2`                          text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `plugin_metademands_stepforms_id` (`plugin_metademands_stepforms_id`),
    KEY `plugin_metademands_fields_id` (`plugin_metademands_fields_id`)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_ci
  ROW_FORMAT = DYNAMIC;

CREATE TABLE `glpi_plugin_metademands_steps`
(
    `id`                                int unsigned NOT NULL AUTO_INCREMENT,
    `plugin_metademands_metademands_id` int unsigned NOT NULL           DEFAULT '0',
    `block_id`                          int unsigned NOT NULL           DEFAULT '0',
    `groups_id`                         int unsigned NOT NULL           DEFAULT '0',
    `only_by_supervisor`                tinyint      NOT NULL           DEFAULT '0',
    `reminder_delay`                    text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    `message`                           text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `plugin_metademands_metademands_id` (`plugin_metademands_metademands_id`)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_ci
  ROW_FORMAT = DYNAMIC;

CREATE TABLE `glpi_plugin_metademands_configsteps`
(
    `id`                                int unsigned NOT NULL AUTO_INCREMENT,
    `plugin_metademands_metademands_id` int unsigned NOT NULL DEFAULT '0',
    `see_blocks_as_tab`                 tinyint      NOT NULL DEFAULT '0',
    `link_user_block`                   tinyint      NOT NULL DEFAULT '0',
    `multiple_link_groups_blocks`       tinyint      NOT NULL DEFAULT '0',
    `add_user_as_requester`             tinyint      NOT NULL DEFAULT '0',
    `supervisor_validation`             tinyint      NOT NULL DEFAULT '0',
    `step_by_step_interface`            tinyint      NOT NULL DEFAULT '0',
    PRIMARY KEY (`id`),
    KEY `plugin_metademands_metademands_id` (`plugin_metademands_metademands_id`)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_ci
  ROW_FORMAT = DYNAMIC;

CREATE TABLE `glpi_plugin_metademands_stepforms_actors`
(
    `id`                              int unsigned NOT NULL AUTO_INCREMENT,
    `plugin_metademands_stepforms_id` int unsigned NOT NULL DEFAULT '0',
    `users_id`                        int unsigned NOT NULL DEFAULT '0',
    PRIMARY KEY (`id`),
    KEY `plugin_metademands_stepforms_id` (`plugin_metademands_stepforms_id`),
    KEY `users_id` (`users_id`)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_ci
  ROW_FORMAT = DYNAMIC;

CREATE TABLE `glpi_plugin_metademands_conditions`
(
    `id`                                int unsigned NOT NULL AUTO_INCREMENT,
    `plugin_metademands_fields_id`      int unsigned NOT NULL DEFAULT '0',
    `plugin_metademands_metademands_id` int unsigned NOT NULL DEFAULT '0',
    `items_id`                          int unsigned NOT NULL DEFAULT '0',
    `item`                              varchar(255)          DEFAULT NULL,
    `check_value`                       varchar(255) NULL     DEFAULT NULL,
    `show_logic`                        int(11)      NOT NULL DEFAULT '1',
    `show_condition`                    int(11)      NOT NULL DEFAULT '0',
    `order`                             int(11)      NOT NULL DEFAULT '0',
    `type`                              varchar(255)          DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `plugin_metademands_fields_id` (`plugin_metademands_fields_id`),
    KEY `plugin_metademands_metademands_id` (`plugin_metademands_metademands_id`)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_ci
  ROW_FORMAT = DYNAMIC;

CREATE TABLE `glpi_plugin_metademands_basketobjecttypes`
(
    `id`           int unsigned NOT NULL auto_increment,
    `name`         varchar(255) collate utf8mb4_unicode_ci default NULL,
    PRIMARY KEY (`id`)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_ci
  ROW_FORMAT = DYNAMIC;

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
    `itilcategories_id`                 int unsigned NOT NULL DEFAULT '0',
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
