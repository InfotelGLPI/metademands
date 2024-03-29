CREATE TABLE `glpi_plugin_metademands_fieldparameters`
(
    `id`                                  int unsigned NOT NULL AUTO_INCREMENT,
    `plugin_metademands_fields_id`        int unsigned NOT NULL DEFAULT '0',
    `custom_values`                       text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    `default_values`                      text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    `comment_values`                      text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
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
