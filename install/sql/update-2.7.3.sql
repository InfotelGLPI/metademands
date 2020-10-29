DROP TABLE IF EXISTS `glpi_plugin_metademands_fieldtranslations`;
CREATE TABLE `glpi_plugin_metademands_fieldtranslations`
(
    `id`       int(11) NOT NULL AUTO_INCREMENT,
    `items_id` int(11) NOT NULL                     DEFAULT '0',
    `itemtype` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
    `language` varchar(5) COLLATE utf8_unicode_ci   DEFAULT NULL,
    `field`    varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
    `value`    text COLLATE utf8_unicode_ci         DEFAULT NULL,
    PRIMARY KEY (`id`)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8
  COLLATE = utf8_unicode_ci
  AUTO_INCREMENT = 1; 