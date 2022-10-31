<?php
/*
 * @version $Id: HEADER 15930 2011-10-30 15:47:55Z tsmr $
 -------------------------------------------------------------------------
 Metademands plugin for GLPI
 Copyright (C) 2018-2022 by the Metademands Development Team.

 https://github.com/InfotelGLPI/metademands
 -------------------------------------------------------------------------

 LICENSE

 This file is part of Metademands.

 Metademands is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 Metademands is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with Metademands. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 */

/**
 * @return bool
 * @throws \GlpitestSQLError
 */
function plugin_metademands_install() {
    global $DB;

    include_once(PLUGIN_METADEMANDS_DIR . "/inc/profile.class.php");

    if (!$DB->tableExists("glpi_plugin_metademands_fields")) {
        $DB->runFile(PLUGIN_METADEMANDS_DIR . "/install/sql/empty-3.2.8.sql");
        install_notifications_metademands();
    }

    if ($DB->tableExists("glpi_plugin_metademands_profiles")
        && !$DB->fieldExists("glpi_plugin_metademands_profiles", "requester")) {
        $DB->runFile(PLUGIN_METADEMANDS_DIR . "/install/sql/update-2.0.1.sql");
    }

    if (!$DB->tableExists("glpi_plugin_metademands_configs")) {
        $DB->runFile(PLUGIN_METADEMANDS_DIR . "/install/sql/update-2.0.2.sql");
    }

    if ($DB->tableExists("glpi_plugin_metademands_fields")
        && !$DB->fieldExists("glpi_plugin_metademands_fields", "order")) {
        $DB->runFile(PLUGIN_METADEMANDS_DIR . "/install/sql/update-2.0.3.sql");
    }

    if (!$DB->fieldExists("glpi_plugin_metademands_configs", "create_pdf")) {
        $DB->runFile(PLUGIN_METADEMANDS_DIR . "/install/sql/update-2.1.4.sql");
    }

    if ($DB->tableExists("glpi_plugin_metademands_fields")
        && !$DB->fieldExists("glpi_plugin_metademands_fields", "color")) {
        $DB->runFile(PLUGIN_METADEMANDS_DIR . "/install/sql/update-2.3.1.sql");
    }

    //version 2.3.2
    if ($DB->tableExists("glpi_plugin_metademands_fields")
        && !$DB->fieldExists("glpi_plugin_metademands_fields", "parent_field_id")) {
        $DB->runFile(PLUGIN_METADEMANDS_DIR . "/install/sql/update-2.3.2.sql");
    }

    //version 2.4.1
    if ($DB->tableExists("glpi_plugin_metademands_fields")
        && !$DB->fieldExists("glpi_plugin_metademands_fields", "comment_values")) {
        $DB->runFile(PLUGIN_METADEMANDS_DIR . "/install/sql/update-2.4.1.sql");
    }
    //version 2.5.2
    if (!$DB->fieldExists("glpi_plugin_metademands_configs", "childs_parent_content")) {
        $DB->runFile(PLUGIN_METADEMANDS_DIR . "/install/sql/update-2.5.2.sql");
    }

    //version 2.6.2
    if ($DB->tableExists("glpi_plugin_metademands_fields")
        && !$DB->fieldExists("glpi_plugin_metademands_fields", "row_display")) {
        $DB->runFile(PLUGIN_METADEMANDS_DIR . "/install/sql/update-2.6.2.sql");
    }

    //version 2.6.3
    if (!$DB->fieldExists("glpi_plugin_metademands_configs", "display_type")) {
        $DB->runFile(PLUGIN_METADEMANDS_DIR . "/install/sql/update-2.6.3.sql");
    }

    //version 2.7.1
    if ($DB->tableExists("glpi_plugin_metademands_fields")
        && !$DB->fieldExists("glpi_plugin_metademands_fields", "is_basket") &&
        !$DB->fieldExists("glpi_plugin_metademands_metademands", "is_order")) {
        $DB->runFile(PLUGIN_METADEMANDS_DIR . "/install/sql/update-2.7.1.sql");

        include(PLUGIN_METADEMANDS_DIR . "/install/update270_271.php");
        update270_271();

        $field  = new PluginMetademandsField();
        $fields = $field->find();
        foreach ($fields as $f) {
            if (!empty($f["hidden_link"])) {
                $array                 = [];
                $array[]               = $f["hidden_link"];
                $update["id"]          = $f["id"];
                $update["hidden_link"] = json_encode($array);
                $field->update($update);
            }
        }
    }

    //version 2.7.2
    if (!$DB->fieldExists("glpi_plugin_metademands_metademands", "create_one_ticket")) {

        $sql    = "SHOW COLUMNS FROM `glpi_plugin_metademands_metademands`";
        $result = $DB->query($sql);
        while ($data = $DB->fetchArray($result)) {
            if ($data['Field'] == 'itilcategories_id' && $data['Type'] == 'int(1)') {
                include(PLUGIN_METADEMANDS_DIR . "/install/update270_271.php");
                update270_271();
            }
        }

        $DB->runFile(PLUGIN_METADEMANDS_DIR . "/install/sql/update-2.7.2.sql");
    }

    //version 2.7.4
    if (!$DB->fieldExists("glpi_plugin_metademands_fields", "hidden_block")) {
        $DB->runFile(PLUGIN_METADEMANDS_DIR . "/install/sql/update-2.7.4.sql");

        $field  = new PluginMetademandsField();
        $fields = $field->find(['type' => "dropdown", "item" => "user"]);
        foreach ($fields as $f) {
            $f["item"] = "User";
            $f["type"] = "dropdown_object";
            $field->update($f);
        }
        $fields = $field->find(['type' => "dropdown", "item" => "usertitle"]);
        foreach ($fields as $f) {
            $f["item"] = "UserTitle";
            $field->update($f);
        }
        $fields = $field->find(['type' => "dropdown", "item" => "usercategory"]);
        foreach ($fields as $f) {
            $f["item"] = "UserCategory";
            $field->update($f);
        }
        $fields = $field->find(['type' => "dropdown", "item" => "group"]);
        foreach ($fields as $f) {
            $f["item"] = "Group";
            $f["type"] = "dropdown_object";
            $field->update($f);
        }
        $fields = $field->find(['type' => "dropdown", "item" => "location"]);
        foreach ($fields as $f) {
            $f["item"] = "Location";
            $field->update($f);
        }
        $fields = $field->find(['type' => "dropdown", "item" => "appliance"]);
        foreach ($fields as $f) {
            $f["item"] = "Appliance";
            $f["type"] = "dropdown_object";
            $field->update($f);
        }
        $fields = $field->find(['type' => "dropdown", "item" => "itilcategory"]);
        foreach ($fields as $f) {
            $f["item"] = "ITILCategory_Metademands";
            $f["type"] = "dropdown_meta";
            $field->update($f);
        }
        $fields = $field->find(['type' => "dropdown", "item" => "other"]);
        foreach ($fields as $f) {
            $f["item"] = "other";
            $f["type"] = "dropdown_meta";
            $field->update($f);
        }
        $fields = $field->find(['type' => "dropdown", "item" => "PluginResourcesResource"]);
        foreach ($fields as $f) {
            $f["type"] = "dropdown_object";
            $field->update($f);
        }
    }

    //version 2.7.5
    if (!$DB->fieldExists("glpi_plugin_metademands_fields", "display_type")) {
        $DB->runFile(PLUGIN_METADEMANDS_DIR . "/install/sql/update-2.7.5.sql");
        $sql    = "SHOW COLUMNS FROM `glpi_plugin_metademands_fields`";
        $result = $DB->query($sql);
        while ($data = $DB->fetchArray($result)) {
            if ($data['Field'] == 'fields_link') {
                include(PLUGIN_METADEMANDS_DIR . "/install/update274_275.php");
                update274_275();
            }
        }
        include(PLUGIN_METADEMANDS_DIR . "/install/migrateExistingMetaWithNewStatus.php");
        migrateAllExistingMetademandsWithNewStatus();
    }

    PluginMetademandsProfile::createFirstAccess($_SESSION['glpiactiveprofile']['id']);
    PluginMetademandsProfile::initProfile();
    $DB->query("DROP TABLE IF EXISTS `glpi_plugin_metademands_profiles`;");

    //version 2.7.5 ++
    if (!$DB->fieldExists("glpi_plugin_metademands_fields", "link_to_user")) {
        $DB->runFile(PLUGIN_METADEMANDS_DIR . "/install/sql/update-2.7.5b.sql");
    }

    //version 2.7.6
    if (!$DB->fieldExists("glpi_plugin_metademands_fields", "informations_to_display")) {
        $DB->runFile(PLUGIN_METADEMANDS_DIR . "/install/sql/update-2.7.6.sql");
    }
    //version 2.7.8
    if (!$DB->tableExists("glpi_plugin_metademands_pluginfields")) {
        $DB->runFile(PLUGIN_METADEMANDS_DIR . "/install/sql/update-2.7.8.sql");
    }
    //version 2.7.9
    if (!$DB->fieldExists("glpi_plugin_metademands_tasks", "useBlock")) {
        $DB->runFile(PLUGIN_METADEMANDS_DIR . "/install/sql/update-2.7.9.sql");
    }
    //version 2.7.10 - released after 3.0.0
    //version 3.0.0
    if (!$DB->fieldExists("glpi_plugin_metademands_tasks", "formatastable")) {
        $DB->runFile(PLUGIN_METADEMANDS_DIR . "/install/sql/update-3.0.0.sql");
        if (!$DB->fieldExists("glpi_plugin_metademands_fields", "childs_blocks")) {
            $query = "ALTER TABLE `glpi_plugin_metademands_fields` ADD `childs_blocks` VARCHAR (255) NOT NULL DEFAULT '[]';";
            $DB->query($query);
            install_notifications_metademands();
        }
    }

    //version 3.1.0
    if (!$DB->fieldExists("glpi_plugin_metademands_fields", "checkbox_value")) {
        $query = "ALTER TABLE `glpi_plugin_metademands_fields` ADD `checkbox_value` VARCHAR (255) NOT NULL DEFAULT '[]';";
        $DB->query($query);
    }
    if (!$DB->fieldExists("glpi_plugin_metademands_fields", "checkbox_id")) {
        $query = "ALTER TABLE `glpi_plugin_metademands_fields` ADD `checkbox_id` VARCHAR (255) NOT NULL DEFAULT '[]';";
        $DB->query($query);
    }
    if (!$DB->fieldExists("glpi_plugin_metademands_metademands", "can_update")) {
        $query = "ALTER TABLE `glpi_plugin_metademands_metademands` ADD `can_update` tinyint NOT NULL DEFAULT '0';;";
        $DB->query($query);
    }
    if (!$DB->fieldExists("glpi_plugin_metademands_metademands", "can_clone")) {
        $query = "ALTER TABLE `glpi_plugin_metademands_metademands` ADD `can_clone` tinyint NOT NULL DEFAULT '0';;";
        $DB->query($query);
    }
    if (!$DB->fieldExists("glpi_plugin_metademands_configs", "show_form_changes")) {
        $query = "ALTER TABLE `glpi_plugin_metademands_configs` ADD `show_form_changes` tinyint NOT NULL DEFAULT '0';";
        $DB->query($query);
    }

    if (!$DB->fieldExists("glpi_plugin_metademands_forms", "resources_id")) {
        $query = "ALTER TABLE `glpi_plugin_metademands_forms` ADD `resources_id` int unsigned NOT NULL default '0';";
        $DB->query($query);
    }

    if (!$DB->tableExists("glpi_plugin_metademands_interticketfollowups")) {
        $query = "CREATE TABLE `glpi_plugin_metademands_interticketfollowups`
         (
             `id` int unsigned NOT NULL AUTO_INCREMENT,
             `tickets_id` int unsigned NOT NULL DEFAULT '0',
             `targets_id` int unsigned NOT NULL DEFAULT '0',
             `date` timestamp NULL DEFAULT NULL,
             `users_id` int unsigned NOT NULL DEFAULT '0',
             `users_id_editor` int unsigned NOT NULL DEFAULT '0',
             `content` longtext COLLATE utf8mb4_unicode_ci default NULL,
             `is_private` tinyint NOT NULL DEFAULT '0',
             `requesttypes_id` int unsigned NOT NULL DEFAULT '0', -- todo keep it ?
             `date_mod` timestamp NULL DEFAULT NULL,
             `date_creation` timestamp NULL DEFAULT NULL,
             `timeline_position` tinyint NOT NULL DEFAULT '0',
             PRIMARY KEY (`id`)
         ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;";
        $DB->query($query);
    }

    //version 3.2.0
    if (!$DB->fieldExists("glpi_plugin_metademands_metademands", "force_create_tasks")) {
        $DB->runFile(PLUGIN_METADEMANDS_DIR . "/install/sql/update-3.2.0.sql");
    }
    //version 3.2.1
    if ($DB->tableExists("glpi_plugin_metademands_tickets_fields")
        && !$DB->fieldExists("glpi_plugin_metademands_tickets_fields", "value2")) {
        $DB->runFile(PLUGIN_METADEMANDS_DIR . "/install/sql/update-3.2.1.sql");
    }
    //version 3.2.8
    if (!$DB->fieldExists("glpi_plugin_metademands_metademands", "step_by_step_mode")) {
      $DB->runFile(PLUGIN_METADEMANDS_DIR . "/install/sql/update-3.2.8.sql");
    }
    $rep_files_metademands = GLPI_PLUGIN_DOC_DIR . "/metademands";
    if (!is_dir($rep_files_metademands)) {
        mkdir($rep_files_metademands);
    }

    return true;
}

// Uninstall process for plugin : need to return true if succeeded
/**
 * @return bool
 * @throws \GlpitestSQLError
 */
function plugin_metademands_uninstall() {
    global $DB;

    // Plugin tables deletion
    $tables = ["glpi_plugin_metademands_metademands_resources",
               "glpi_plugin_metademands_configs",
               "glpi_plugin_metademands_tickets_itilenvironments",
               "glpi_plugin_metademands_tickets_itilapplications",
               "glpi_plugin_metademands_itilenvironments",
               "glpi_plugin_metademands_itilapplications",
               "glpi_plugin_metademands_groups",
               "glpi_plugin_metademands_metademandtasks",
               "glpi_plugin_metademands_tickets_metademands",
               "glpi_plugin_metademands_tickets_tasks",
               "glpi_plugin_metademands_tickettasks",
               "glpi_plugin_metademands_ticketfields",
               "glpi_plugin_metademands_tickets_fields",
               "glpi_plugin_metademands_fields",
               "glpi_plugin_metademands_tasks",
               "glpi_plugin_metademands_metademands",
               "glpi_plugin_metademands_basketlines",
               "glpi_plugin_metademands_fieldtranslations",
               "glpi_plugin_metademands_metademandtranslations",
               "glpi_plugin_metademands_metademandvalidations",
               "glpi_plugin_metademands_drafts",
               "glpi_plugin_metademands_drafts_values",
               "glpi_plugin_metademands_pluginfields",
               "glpi_plugin_metademands_forms",
               "glpi_plugin_metademands_forms_values",
               "glpi_plugin_metademands_interticketfollowups",
               "glpi_plugin_metademands_stepforms",
               "glpi_plugin_metademands_stepforms_values",
               "glpi_plugin_metademands_steps"];
    foreach ($tables as $table) {
        $DB->query("DROP TABLE IF EXISTS `$table`;");
    }

    $options = ['itemtype' => 'PluginMetademandsInterticketfollowup',
                'event'    => 'add_interticketfollowup',
                'FIELDS'   => 'id'];

    $notif = new Notification();
    foreach ($DB->request('glpi_notifications', $options) as $data) {
        $notif->delete($data);
    }

    //templates
    $template       = new NotificationTemplate();
    $translation    = new NotificationTemplateTranslation();
    $notif_template = new Notification_NotificationTemplate();
    $options        = ['itemtype' => 'PluginMetademandsInterticketfollowup',
                       'FIELDS'   => 'id'];

    foreach ($DB->request('glpi_notificationtemplates', $options) as $data) {
        $options_template = ['notificationtemplates_id' => $data['id'],
                             'FIELDS'                   => 'id'];
        foreach ($DB->request('glpi_notificationtemplatetranslations', $options_template) as $data_template) {
            $translation->delete($data_template);
        }
        $template->delete($data);

        foreach ($DB->request('glpi_notifications_notificationtemplates', $options_template) as $data_template) {
            $notif_template->delete($data_template);
        }
    }
    //for step forms

    $options = ['itemtype' => 'PluginMetademandsStepform',
                'event'    => 'new_step_form',
                'FIELDS'   => 'id'];

    $notif = new Notification();
    foreach ($DB->request('glpi_notifications', $options) as $data) {
        $notif->delete($data);
    }

    $options = ['itemtype' => 'PluginMetademandsStepform',
                'event'    => 'reminder_step_form',
                'FIELDS'   => 'id'];

    $notif = new Notification();
    foreach ($DB->request('glpi_notifications', $options) as $data) {
        $notif->delete($data);
    }

    //templates
    $template       = new NotificationTemplate();
    $translation    = new NotificationTemplateTranslation();
    $notif_template = new Notification_NotificationTemplate();
    $options        = ['itemtype' => 'PluginMetademandsStepform',
                       'FIELDS'   => 'id'];

    foreach ($DB->request('glpi_notificationtemplates', $options) as $data) {
        $options_template = ['notificationtemplates_id' => $data['id'],
                             'FIELDS'                   => 'id'];
        foreach ($DB->request('glpi_notificationtemplatetranslations', $options_template) as $data_template) {
            $translation->delete($data_template);
        }
        $template->delete($data);

        foreach ($DB->request('glpi_notifications_notificationtemplates', $options_template) as $data_template) {
            $notif_template->delete($data_template);
        }
    }

    include_once(PLUGIN_METADEMANDS_DIR . "/inc/profile.class.php");

    PluginMetademandsProfile::removeRightsFromSession();
    PluginMetademandsProfile::removeRightsFromDB();

    return true;
}

// How to display specific actions ?
// options contain at least itemtype and and action
/**
 * @param array $options
 */
//function plugin_metademands_MassiveActionsDisplay($options = []) {
//
//   switch ($options['itemtype']) {
//      case 'PluginMetademandsMetademand':
//         switch ($options['action']) {
//            case "plugin_metademands_duplicate":
//               echo "&nbsp;". Html::submit(_sx('button', 'Post'), ['name' => 'massiveaction', 'class' => 'btn btn-primary']);
//               break;
//         }
//         break;
//   }
//}

// Define dropdown relations
/**
 * @return array|\string[][]
 */
function plugin_metademands_getDatabaseRelations() {

    if (Plugin::isPluginActive("metademands")) {
        return ["glpi_entities" => ["glpi_plugin_metademands_metademands"           => "entities_id",
                                    "glpi_plugin_metademands_fields"                => "entities_id",
                                    "glpi_plugin_metademands_metademands_resources" => "entities_id",
                                    "glpi_plugin_metademands_ticketfields"          => "entities_id",
                                    "glpi_plugin_metademands_tasks"                 => "entities_id"],

                "glpi_plugin_metademands_metademands" => ["glpi_plugin_metademands_fields"                => "plugin_metademands_metademands_id",
                                                          "glpi_plugin_metademands_tickets_metademands"   => "plugin_metademands_metademands_id",
                                                          "glpi_plugin_metademands_metademandtasks"       => "plugin_metademands_metademands_id",
                                                          "glpi_plugin_metademands_ticketfields"          => "plugin_metademands_metademands_id",
                                                          "glpi_plugin_metademands_tasks"                 => "plugin_metademands_metademands_id",
                                                          "glpi_plugin_metademands_groups"                => "plugin_metademands_metademands_id",
                                                          "glpi_plugin_metademands_basketlines"           => "plugin_metademands_metademands_id",
                                                          "glpi_plugin_metademands_metademandvalidations" => "plugin_metademands_metademands_id",
                                                          "glpi_plugin_metademands_metademands_resources" => "plugin_metademands_metademands_id",
                                                          "glpi_plugin_metademands_drafts"                => "plugin_metademands_metademands_id"],

                "glpi_tickets"                   => ["glpi_plugin_metademands_tickets_fields"        => "tickets_id",
                                                     "glpi_plugin_metademands_metademandvalidations" => "tickets_id",
                                                     "glpi_plugin_metademands_tickets_tasks"         => "tickets_id",
                                                     "glpi_plugin_metademands_tickets_metademands"   => "tickets_id",
                ],
                "glpi_users"                     => ["glpi_plugin_metademands_basketlines"           => "users_id",
                                                     "glpi_plugin_metademands_metademandvalidations" => "users_id",
                                                     "glpi_plugin_metademands_tickettasks"           => "users_id_assign",
                                                     "glpi_plugin_metademands_tickettasks"           => "users_id_requester",
                                                     "glpi_plugin_metademands_tickettasks"           => "users_id_observer",
                                                     "glpi_plugin_metademands_drafts"                => "users_id"
                ],
                "glpi_groups"                    => ["glpi_plugin_metademands_groups"      => "groups_id",
                                                     "glpi_plugin_metademands_tickettasks" => "groups_id_assign",
                                                     "glpi_plugin_metademands_tickettasks" => "groups_id_requester",
                                                     "glpi_plugin_metademands_tickettasks" => "groups_id_observer",
                ],
                "glpi_itilcategories"            => ["glpi_plugin_metademands_metademands" => "itilcategories_id",
                                                     "glpi_plugin_metademands_tickettasks" => "itilcategories_id",
                ],
                "glpi_plugin_metademands_fields" => ["glpi_plugin_metademands_tickets_fields" => "plugin_metademands_fields_id",
                                                     "glpi_plugin_metademands_drafts_values"  => "plugin_metademands_fields_id",
                                                     "glpi_plugin_metademands_basketlines"    => "plugin_metademands_fields_id"],

                "glpi_plugin_metademands_tasks" => ["glpi_plugin_metademands_fields"          => "plugin_metademands_tasks_id",
                                                    "glpi_plugin_metademands_tickettasks"     => "plugin_metademands_tasks_id",
                                                    "glpi_plugin_metademands_tickets_tasks"   => "plugin_metademands_tasks_id",
                                                    "glpi_plugin_metademands_metademandtasks" => "plugin_metademands_tasks_id"],

                "glpi_plugin_metademands_drafts" => ["glpi_plugin_metademands_drafts_values" => "plugin_metademands_drafts_id"],
        ];
    } else {
        return [];
    }
}

/**
 * @param $data
 *
 * @return mixed
 */
//function plugin_metademands_MassiveActionsProcess($data) {
//   $metademand = new PluginMetademandsMetademand();
//   $res        = $metademand->doSpecificMassiveActions($data);
//
//   return $res;
//}


//function plugin_metademands_registerMethods() {
//   global $WEBSERVICES_METHOD;
//
//   $WEBSERVICES_METHOD['metademands.addMetademands']
//      = ['PluginMetademandsMetademand', 'methodAddMetademands'];
//   $WEBSERVICES_METHOD['metademands.listMetademands']
//      = ['PluginMetademandsMetademand', 'methodListMetademands'];
//   $WEBSERVICES_METHOD['metademands.listMetademandsfields']
//      = ['PluginMetademandsField', 'methodListMetademandsfields'];
//   $WEBSERVICES_METHOD['metademands.listTasktypes']
//      = ['PluginMetademandsTask', 'methodListTasktypes'];
//   $WEBSERVICES_METHOD['metademands.showMetademands']
//      = ['PluginMetademandsMetademand', 'methodShowMetademands'];
//   $WEBSERVICES_METHOD['metademands.showTicketForm']
//      = ['PluginMetademandsTicket', 'methodShowTicketForm'];
//   $WEBSERVICES_METHOD['metademands.isMandatoryFields']
//      = ['PluginMetademandsTicket', 'methodIsMandatoryFields'];
//
//}


// Define search option for types of the plugins
/**
 * @param $itemtype
 *
 * @return array
 */
function plugin_metademands_getAddSearchOptions($itemtype) {

    $sopt = [];
    if ($itemtype == "Ticket") {
        if (Session::haveRight("plugin_metademands", READ)) {

            $sopt[9499]['table']         = 'glpi_users';
            $sopt[9499]['field']         = 'name';
            $sopt[9499]['linkfield']     = 'users_id';
            $sopt[9499]['name']          = __("Metademand approver", 'metademands');
            $sopt[9499]['datatype']      = "itemlink";
            $sopt[9499]['forcegroupby']  = true;
            $sopt[9499]['joinparams']    = ['beforejoin' => [
                'table'      => 'glpi_plugin_metademands_metademandvalidations',
                'joinparams' => [
                    'jointype' => 'child'
                ]
            ]];
            $sopt[9499]['massiveaction'] = false;

            $sopt[9500]['table']         = 'glpi_plugin_metademands_tickets_metademands';
            $sopt[9500]['field']         = 'status';
            $sopt[9500]['name']          = __('Metademand status', 'metademands');
            $sopt[9500]['datatype']      = "specific";
            $sopt[9500]['searchtype']    = "equals";
            $sopt[9500]['joinparams']    = ['jointype' => 'child'];
            $sopt[9500]['massiveaction'] = false;

            $sopt[9501]['table']         = 'glpi_plugin_metademands_metademandvalidations';
            $sopt[9501]['field']         = 'validate';
            $sopt[9501]['name']          = PluginMetademandsMetademandValidation::getTypeName(1);
            $sopt[9501]['datatype']      = "specific";
            $sopt[9501]['searchtype']    = "equals";
            $sopt[9501]['joinparams']    = ['jointype' => 'child'];
            $sopt[9501]['massiveaction'] = false;

            $sopt[9502]['table']        = 'glpi_plugin_metademands_tickets_tasks';
            $sopt[9502]['field']        = 'id';
            $sopt[9502]['name']         = __("Group child ticket", 'metademands');
            $sopt[9502]['datatype']     = "specific";
            $sopt[9502]['searchtype']   = "equals";
            $sopt[9502]['forcegroupby'] = true;
            //         $sopt[9502]['linkfield']     = 'parent_tickets_id';
            $sopt[9502]['joinparams']    = ['jointype'  => 'child',
                                            'linkfield' => 'parent_tickets_id'];
            $sopt[9502]['massiveaction'] = false;

            $sopt[9503]['table']      = 'glpi_plugin_metademands_tickets_tasks';
            $sopt[9503]['field']      = 'tickets_id';
            $sopt[9503]['name']       = __('Link to metademands', 'metademands');
            $sopt[9503]['datatype']   = "specific";
            $sopt[9503]['searchtype'] = "";
            //         $sopt[9503]['forcegroupby']    = true;
            //         $sopt[9502]['linkfield']     = 'parent_tickets_id';
            $sopt[9503]['joinparams'] = ['jointype'  => 'child',
                                         'linkfield' => 'parent_tickets_id'];
            //         $sopt[9503]['joinparams']    = ['jointype'  => 'child'];
            $sopt[9503]['massiveaction'] = false;

            $sopt[9504]['table']        = 'glpi_plugin_metademands_tickets_tasks';
            $sopt[9504]['field']        = 'plugin_metademands_tasks_id';
            $sopt[9504]['name']         = __("Technician child ticket", 'metademands');
            $sopt[9504]['datatype']     = "specific";
            $sopt[9504]['searchtype']   = "equals";
            $sopt[9504]['forcegroupby'] = true;
            //        $sopt[9502]['linkfield']     = 'parent_tickets_id';
            $sopt[9504]['joinparams']    = ['jointype'  => 'child',
                                            'linkfield' => 'parent_tickets_id'];
            $sopt[9504]['massiveaction'] = false;
        }
    }
    return $sopt;
}


/**
 * @param $link
 * @param $nott
 * @param $type
 * @param $ID
 * @param $val
 * @param $searchtype
 *
 * @return string
 */
function plugin_metademands_addWhere($link, $nott, $type, $ID, $val, $searchtype) {

    $searchopt = &Search::getOptions($type);
    $table     = $searchopt[$ID]["table"];
    $field     = $searchopt[$ID]["field"];

    switch ($table . "." . $field) {
        case "glpi_plugin_metademands_tickets_metademands.status":
            return $link . " `glpi_plugin_metademands_tickets_metademands`.`status` = '$val'";
            break;

        case "glpi_plugin_metademands_metademandvalidations.validate":
            $AND = "";
            if ($val == PluginMetademandsMetademandValidation::TO_VALIDATE
                || $val == PluginMetademandsMetademandValidation::TO_VALIDATE_WITHOUTTASK) {
                $AND = "AND glpi_tickets.status IN ( " . implode(",", Ticket::getNotSolvedStatusArray()) . ")";
            }
            return $link . " `glpi_plugin_metademands_metademandvalidations`.`validate` >= -1
                        AND `glpi_plugin_metademands_metademandvalidations`.`validate` = '$val' $AND";
            break;

        case "glpi_plugin_metademands_tickets_tasks.id":
            switch ($searchtype) {
                case 'equals' :
                    if ($val === '0') {
                        return " ";
                    }
                    if ($val == 'mygroups') {
                        return " $link (`glpi_groups_metademands`.`id` IN ('" . implode("','",
                                                                                        $_SESSION['glpigroups']) . "')) ";
                    } else {
                        return " $link (`glpi_groups_metademands`.`id` IN ('" . $val . "')) ";
                    }
                    break;
                case 'notequals' :
                    return " $link (`glpi_groups_metademands`.`id` NOT IN ('" . implode("','",
                                                                                        $_SESSION['glpigroups']) . "')) ";
                    break;
                case 'contains' :
                    return " ";
                    break;
            }
            break;

        case "glpi_plugin_metademands_tickets_tasks.plugin_metademands_tasks_id":
            switch ($searchtype) {
                case 'equals' :
                    if ($val === '0') {
                        return " ";
                    }
                    return " $link (`glpi_users_metademands`.`id` IN ('" . $val . "')) ";
                    break;
                case 'notequals' :
                    return " $link (`glpi_users_metademands`.`id` NOT IN ('" . $val . "')) ";
                    break;
                case 'contains' :
                    return " ";
                    break;
            }

            break;
        case "glpi_plugin_metademands_tickets_tasks.tickets_id":
            return " ";
            break;
    }
    return "";
}

/**
 * @param $type
 * @param $ref_table
 * @param $new_table
 * @param $linkfield
 * @param $already_link_tables
 *
 * @return \Left|string
 */
function plugin_metademands_addLeftJoin($type, $ref_table, $new_table, $linkfield, &$already_link_tables) {

    // Rename table for meta left join
    $AS = "";
    // Multiple link possibilies case
    if ($new_table == "glpi_plugin_metademands_tickets_tasks") {
        $AS = " AS " . $new_table;
    }

    switch ($new_table) {
        //
        case "glpi_plugin_metademands_tickets_tasks" :
            return "LEFT JOIN `glpi_plugin_metademands_tickets_tasks` $AS ON (`$ref_table`.`id` = `glpi_plugin_metademands_tickets_tasks`.`parent_tickets_id` )
          LEFT JOIN `glpi_groups_tickets` AS glpi_groups_tickets_metademands ON (`$new_table`.`tickets_id` = `glpi_groups_tickets_metademands`.`tickets_id` 
          AND `glpi_groups_tickets_metademands`.`type` = " . CommonITILActor::ASSIGN . " ) 
          LEFT JOIN `glpi_groups` AS glpi_groups_metademands ON (`glpi_groups_tickets_metademands`.`groups_id` = `glpi_groups_metademands`.`id` )
          LEFT JOIN `glpi_tickets` AS glpi_tickets_metademands ON (`$new_table`.`tickets_id` = `glpi_tickets_metademands`.`id` AND `glpi_tickets_metademands`.`is_deleted` = 0)
          LEFT JOIN `glpi_tickets_users` AS glpi_users_tickets_metademands ON (`$new_table`.`tickets_id` = `glpi_users_tickets_metademands`.`tickets_id` 
          AND `glpi_users_tickets_metademands`.`type` = " . CommonITILActor::ASSIGN . " ) 
          LEFT JOIN `glpi_users` AS glpi_users_metademands ON (`glpi_users_tickets_metademands`.`users_id` = `glpi_users_metademands`.`id` )";
            break;

    }
    return "";
}

/**
 * @param $type
 * @param $ID
 * @param $num
 *
 * @return string
 */
function plugin_metademands_addSelect($type, $ID, $num) {
    $searchopt = &Search::getOptions($type);
    $table     = $searchopt[$ID]["table"];
    $field     = $searchopt[$ID]["field"];

    if ($table == "glpi_plugin_metademands_tickets_tasks"
        && $type == "Ticket") {
        //      if($ID == 9502)
        if ($ID == 9504) {
            return " GROUP_CONCAT(DISTINCT CONCAT(IFNULL(`glpi_users_metademands`.`id`, '__NULL__'))
      ORDER BY `glpi_users_metademands`.`id` SEPARATOR '$$##$$') AS `ITEM_$num`, ";
            //         return " GROUP_CONCAT(DISTINCT CONCAT(IFNULL(`glpi_users_metademands`.`name`, '__NULL__'), '$#$',`glpi_users_metademands`.`id`)
            //      ORDER BY `glpi_users_metademands`.`id` SEPARATOR '$$##$$') AS `ITEM_$num`, ";
        }
        return " GROUP_CONCAT(DISTINCT CONCAT(IFNULL(`glpi_groups_metademands`.`completename`, '__NULL__'), '$#$',`glpi_groups_metademands`.`id`)
      ORDER BY `glpi_groups_metademands`.`id` SEPARATOR '$$##$$') AS `ITEM_$num`, ";

        //      return "$table.$field, ";
    } else {
        return "";
    }
}

/**
 * @param        $type
 * @param        $field
 * @param        $data
 * @param        $num
 * @param string $linkfield
 *
 * @return string
 * @throws \GlpitestSQLError
 */
function plugin_metademands_giveItem($type, $field, $data, $num, $linkfield = "") {
    global $CFG_GLPI;
    switch ($field) {
        case 9499 :
            $out = getUserName($data['raw']["ITEM_" . $num]);
            return $out;
            break;
        case 9500 :
            $out = PluginMetademandsTicket_Metademand::getStatusName($data['raw']["ITEM_" . $num]);
            return $out;
            break;
        case 9501 :
            if ($data['raw']["ITEM_" . $num] > -1) {
                $style = "style='background-color: " . PluginMetademandsMetademandValidation::getStatusColor($data['raw']["ITEM_" . $num]) . ";'";
                $out   = "<div class='center' $style>";
                $out   .= PluginMetademandsMetademandValidation::getStatusName($data['raw']["ITEM_" . $num]);
                $out   .= "</div>";
            } else {
                $out = "";
            }
            return $out;
            break;
        //      case 9502 :
        //         $out   = PluginMetademandsTicket_Metademand::getStatusName($data['raw']["ITEM_" . $num]);
        //         return $out;
        //         break;
        case 9503:
            $out                                  = $data['id'];
            $options['criteria'][0]['field']      = 50; // metademand status
            $options['criteria'][0]['searchtype'] = 'equals';
            $options['criteria'][0]['value']      = $data['id'];
            $options['criteria'][0]['link']       = 'AND';

            $options['criteria'][1]['field']      = 8; // groups_id
            $options['criteria'][1]['searchtype'] = 'equals';
            $options['criteria'][1]['value']      = 'mygroups';
            $options['criteria'][1]['link']       = 'AND';

            $options['criteria'][2]['field']      = 12; // status
            $options['criteria'][2]['searchtype'] = 'equals';
            $options['criteria'][2]['value']      = 'notold';
            $options['criteria'][2]['link']       = 'AND';


            $metademands = new PluginMetademandsTicket_Metademand();

            if ($metademands->getFromDBByCrit(['tickets_id' => $data['id']])) {
                $DB                               = DBConnection::getReadConnection();
                $dbu                              = new DbUtils();
                $get_running_parents_tickets_meta =
                    "SELECT  COUNT( DISTINCT `glpi_plugin_metademands_tickets_metademands`.`id`) as 'total_running' FROM `glpi_tickets`
                        LEFT JOIN `glpi_plugin_metademands_tickets_metademands` ON `glpi_tickets`.`id` =  `glpi_plugin_metademands_tickets_metademands`.`tickets_id`
                         LEFT JOIN `glpi_plugin_metademands_tickets_tasks`  ON (`glpi_tickets`.`id` = `glpi_plugin_metademands_tickets_tasks`.`parent_tickets_id` )
                         LEFT JOIN `glpi_groups_tickets` AS glpi_groups_tickets_metademands ON (`glpi_plugin_metademands_tickets_tasks`.`tickets_id` = `glpi_groups_tickets_metademands`.`tickets_id` ) 
                         LEFT JOIN `glpi_groups` AS glpi_groups_metademands ON (`glpi_groups_tickets_metademands`.`groups_id` = `glpi_groups_metademands`.`id` ) WHERE
                            `glpi_tickets`.`is_deleted` = 0 AND `glpi_plugin_metademands_tickets_metademands`.`status` =  
                                    " . PluginMetademandsTicket_Metademand::RUNNING . " AND (`glpi_groups_metademands`.`id` IN ('" . implode("','",
                                                                                                                                             $_SESSION['glpigroups']) . "')) AND  `glpi_tickets`.`id` =  " . $data['id'] . " " .
                    $dbu->getEntitiesRestrictRequest('AND', 'glpi_tickets');


                $total_running_parents_meta = $DB->query($get_running_parents_tickets_meta);

                $total_running = 0;
                while ($row = $DB->fetchArray($total_running_parents_meta)) {
                    $total_running = $row['total_running'];
                }
                if ($total_running > 0) {
                    $out = "<a href=\"" . $CFG_GLPI["root_doc"] . "/front/ticket.php?" .
                           Toolbox::append_params($options, '&amp;') . "\"><i class='center fas fa-share-alt fa-2x'></i></a>";
                    return $out;
                } else {
                    return " ";
                }
            }
            return " ";
            break;
        case 9504 :
            $result = "";
            if (isset($data["Ticket_9504"]) && !is_null($data["Ticket_9504"])) {
                if (isset($data["Ticket_9504"]["count"])) {
                    $count = $data["Ticket_9504"]["count"];
                    $i     = 0;
                    for ($i; $i < $count; $i++) {
                        if ($i != 0) {
                            $result .= "\n";
                        }
                        $result .= getUserName($data["Ticket_9504"][$i]["name"]);


                    }
                }
            }
            return $result;
            break;

    }

    return "";
}

function install_notifications_metademands() {

    global $DB;

    $migration = new Migration(1.0);

    // Notification
    // Request
    $query_id = "INSERT INTO `glpi_notificationtemplates`(`name`, `itemtype`, `date_mod`) VALUES ('New inter ticket Followup','PluginMetademandsInterticketfollowup', NOW());";
    $DB->query($query_id) or die($DB->error());

    $query_id = "SELECT `id` FROM `glpi_notificationtemplates` WHERE `itemtype`='PluginMetademandsInterticketfollowup' AND `name` = 'New inter ticket Followup'";
    $result = $DB->query($query_id) or die($DB->error());
    $templates_id = $DB->result($result, 0, 'id');

    $query = "INSERT INTO `glpi_notificationtemplatetranslations` (`notificationtemplates_id`, `subject`, `content_text`, `content_html`)
VALUES('" . $templates_id . "',
'',
'##ticket.action##Ticket : ##ticket.title## (##ticket.id##)
##IFticket.storestatus=6## ##lang.ticket.closedate## ##ticket.closedate## 
##ENDIFticket.storestatus## ##lang.ticket.creationdate## : ##ticket.creationdate####IFticket.authors##
##lang.ticket.authors## : ##ticket.authors## ##ENDIFticket.authors## 
##IFticket.assigntogroups####lang.ticket.assigntogroups## : ##ticket.assigntogroups## ##ENDIFticket.assigntogroups## 
##IFticket.assigntousers####lang.ticket.assigntousers## : ##ticket.assigntousers## ##ENDIFticket.assigntousers##
<!-- Suivis 
##ticket.action## -->
##FOREACH LAST 1 followups_intern##
##lang.followup_intern.author## : ##followup_intern.author## - ##followup_intern.date####followup_intern.description##
##ENDFOREACHfollowups_intern##
##lang.ticket.numberoffollowups## : ##ticket.numberoffollowups##
##lang.ticket.description##
##ticket.description##
##lang.ticket.category## :
##ticket.category##
##lang.ticket.urgency## :
##ticket.urgency##
##lang.ticket.location## :
##ticket.location####FOREACHitems##
##lang.ticket.item.name## :##ENDFOREACHitems####FOREACHitems##
##ticket.item.name####ENDFOREACHitems####FOREACHdocuments##
Documents :##ENDFOREACHdocuments####FOREACHdocuments##
##document.filename####ENDFOREACHdocuments##
Ticket ###ticket.id##
','');";
    $DB->query($query);

    $query = "INSERT INTO `glpi_notifications` (`name`, `entities_id`, `itemtype`, `event`, `is_recursive`)
              VALUES ('New inter ticket Followup', 0, 'PluginMetademandsInterticketfollowup', 'add_interticketfollowup', 1);";
    $DB->query($query);

    //retrieve notification id
    $query_id = "SELECT `id` FROM `glpi_notifications`
               WHERE `name` = 'New inter ticket Followup' AND `itemtype` = 'PluginMetademandsInterticketfollowup' AND `event` = 'add_interticketfollowup'";
    $result = $DB->query($query_id) or die ($DB->error());
    $notification = $DB->result($result, 0, 'id');

    $query = "INSERT INTO `glpi_notifications_notificationtemplates` (`notifications_id`, `mode`, `notificationtemplates_id`) 
               VALUES (" . $notification . ", 'mailing', " . $templates_id . ");";
    $DB->query($query);


    //for step forms
    //TODO

    $migration->executeMigration();
    return true;


}
