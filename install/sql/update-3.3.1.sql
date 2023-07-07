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
    `visibility`                         int NOT NULL DEFAULT '0',
    `plugin_metademands_metademands_id` int unsigned NOT NULL DEFAULT '0',
    PRIMARY KEY (`id`),
    KEY (`plugin_metademands_metademands_id`),
    KEY `entities_id` (`entities_id`)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_ci
  ROW_FORMAT = DYNAMIC;