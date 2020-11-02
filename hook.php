<?php
/*
 * @version $Id: HEADER 15930 2011-10-30 15:47:55Z tsmr $
 -------------------------------------------------------------------------
 Metademands plugin for GLPI
 Copyright (C) 2018-2019 by the Metademands Development Team.

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

   include_once(GLPI_ROOT . "/plugins/metademands/inc/profile.class.php");

   if (!$DB->tableExists("glpi_plugin_metademands_metademands")) {
      $DB->runFile(GLPI_ROOT . "/plugins/metademands/install/sql/empty-2.7.4.sql");
   }

   if (!$DB->tableExists("glpi_plugin_metademands_itilapplications") || !$DB->tableExists("glpi_plugin_metademands_itilenvironments")) {
      $DB->runFile(GLPI_ROOT . "/plugins/metademands/install/sql/update-2.0.0.sql");
   }

   if ($DB->tableExists("glpi_plugin_metademands_profiles") && !$DB->fieldExists("glpi_plugin_metademands_profiles", "requester")) {
      $DB->runFile(GLPI_ROOT . "/plugins/metademands/install/sql/update-2.0.1.sql");
   }

   if (!$DB->tableExists("glpi_plugin_metademands_configs")) {
      $DB->runFile(GLPI_ROOT . "/plugins/metademands/install/sql/update-2.0.2.sql");
   }

   if (!$DB->fieldExists("glpi_plugin_metademands_fields", "order")) {
      $DB->runFile(GLPI_ROOT . "/plugins/metademands/install/sql/update-2.0.3.sql");
   }

   if (!$DB->fieldExists("glpi_plugin_metademands_configs", "create_pdf")) {
      $DB->runFile(GLPI_ROOT . "/plugins/metademands/install/sql/update-2.1.4.sql");
   }

   if (!$DB->fieldExists("glpi_plugin_metademands_itilenvironments", "is_outproduction")) {
      include(GLPI_ROOT . "/plugins/metademands/install/update214_225.php");
      update214to225();
   }

   if (!$DB->fieldExists("glpi_plugin_metademands_fields", "color")) {
      $DB->runFile(GLPI_ROOT . "/plugins/metademands/install/sql/update-2.3.1.sql");
   }

   //version 2.3.2
   if (!$DB->fieldExists("glpi_plugin_metademands_fields", "parent_field_id")) {
      $DB->runFile(GLPI_ROOT . "/plugins/metademands/install/sql/update-2.3.2.sql");
   }

   //version 2.4.1
   if (!$DB->fieldExists("glpi_plugin_metademands_fields", "comment_values")) {
      $DB->runFile(GLPI_ROOT . "/plugins/metademands/install/sql/update-2.4.1.sql");
   }
   //version 2.5.2
   if (!$DB->fieldExists("glpi_plugin_metademands_configs", "childs_parent_content")) {
      $DB->runFile(GLPI_ROOT . "/plugins/metademands/install/sql/update-2.5.2.sql");
   }

   //version 2.6.2
   if (!$DB->fieldExists("glpi_plugin_metademands_fields", "row_display")) {
      $DB->runFile(GLPI_ROOT . "/plugins/metademands/install/sql/update-2.6.2.sql");
   }

   //version 2.6.3
   if (!$DB->fieldExists("glpi_plugin_metademands_configs", "display_type")) {
      $DB->runFile(GLPI_ROOT . "/plugins/metademands/install/sql/update-2.6.3.sql");
   }

   //version 2.7.1
   if (!$DB->fieldExists("glpi_plugin_metademands_fields", "is_basket") &&
       !$DB->fieldExists("glpi_plugin_metademands_metademands", "is_order")) {
      $DB->runFile(GLPI_ROOT . "/plugins/metademands/install/sql/update-2.7.1.sql");

      include(GLPI_ROOT . "/plugins/metademands/install/update270_271.php");
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
            include(GLPI_ROOT . "/plugins/metademands/install/update270_271.php");
            update270_271();
         }
      }

      $DB->runFile(GLPI_ROOT . "/plugins/metademands/install/sql/update-2.7.2.sql");
   }

   //version 2.7.4
   if (!$DB->fieldExists("glpi_plugin_metademands_fields", "hidden_block")) {
      $DB->runFile(GLPI_ROOT . "/plugins/metademands/install/sql/update-2.7.4.sql");


      $field = new PluginMetademandsField();
      $fields = $field->find(['type'=>"dropdown","item"=>"user"]);
      foreach ($fields as $f){
         $f["item"] = "User";
         $field->update($f);
      }
      $fields = $field->find(['type'=>"dropdown","item"=>"usertitle"]);
      foreach ($fields as $f){
         $f["item"] = "UserTitle";
         $field->update($f);
      }
      $fields = $field->find(['type'=>"dropdown","item"=>"usercategory"]);
      foreach ($fields as $f){
         $f["item"] = "UserCategory";
         $field->update($f);
      }
      $fields = $field->find(['type'=>"dropdown","item"=>"group"]);
      foreach ($fields as $f){
         $f["item"] = "Group";
         $field->update($f);
      }
      $fields = $field->find(['type'=>"dropdown","item"=>"location"]);
      foreach ($fields as $f){
         $f["item"] = "Location";
         $field->update($f);
      }

   }


   PluginMetademandsProfile::createFirstAccess($_SESSION['glpiactiveprofile']['id']);
   PluginMetademandsProfile::initProfile();
   $DB->query("DROP TABLE IF EXISTS `glpi_plugin_metademands_profiles`;");

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
              "glpi_plugin_metademands_metademandtranslations"];
   foreach ($tables as $table) {
      $DB->query("DROP TABLE IF EXISTS `$table`;");
   }

   if (class_exists('PluginDatainjectionModel')) {
      PluginDatainjectionModel::clean(['itemtype' => 'PluginMetademandsITILApplication']);
   }

   include_once(GLPI_ROOT . "/plugins/metademands/inc/profile.class.php");

   PluginMetademandsProfile::removeRightsFromSession();
   PluginMetademandsProfile::removeRightsFromDB();

   return true;
}

/**
 * @param $itemtype
 *
 * @return array
 */
function plugin_metademands_getAddSearchOptions($itemtype) {
   if ($itemtype == 'Ticket') {
      $config = new PluginMetademandsConfig();
      $data   = $config->getConfigFromDB();
      if ($data['enable_application_environment']) {
         $itilapplication = new PluginMetademandsITILApplication();
         $tab1            = $itilapplication->getAddSearchOptions();

         $itilenvironment = new PluginMetademandsITILEnvironment();
         $tab2            = $itilenvironment->getAddSearchOptions();

         return array_replace($tab1, $tab2);
      }
   }
}

/**
 * @param $itemtype
 * @param $ref_table
 * @param $new_table
 * @param $linkfield
 * @param $already_link_tables
 *
 * @return string
 */
function plugin_metademands_addLeftJoin($itemtype, $ref_table, $new_table, $linkfield, $already_link_tables) {
   if ($itemtype == 'Ticket' && $new_table == 'glpi_plugin_metademands_itilapplications') {
      return " LEFT JOIN `glpi_plugin_metademands_tickets_itilapplications` "
             . "   ON (`glpi_plugin_metademands_tickets_itilapplications`.`tickets_id` = `glpi_tickets`.`id`)"
             . " LEFT JOIN `glpi_plugin_metademands_itilapplications` "
             . "   ON (`glpi_plugin_metademands_itilapplications`.`id` = `glpi_plugin_metademands_tickets_itilapplications`.`plugin_metademands_itilapplications_id`)";
   } else if ($itemtype == 'Ticket' && $new_table == 'glpi_plugin_metademands_itilenvironments') {
      return " LEFT JOIN `glpi_plugin_metademands_tickets_itilenvironments` "
             . "   ON (`glpi_plugin_metademands_tickets_itilenvironments`.`tickets_id` = `glpi_tickets`.`id`)"
             . " LEFT JOIN `glpi_plugin_metademands_itilenvironments` "
             . "   ON (`glpi_plugin_metademands_itilenvironments`.`id` = `glpi_plugin_metademands_tickets_itilenvironments`.`plugin_metademands_itilenvironments_id`)";
   }
}

// Define Dropdown tables to be manage in GLPI
/**
 * @return array
 */
function plugin_metademands_getDropdown() {

   $plugin = new Plugin();

   if ($plugin->isActivated("metademands")) {
      return ['PluginMetademandsMetademand'      => PluginMetademandsMetademand::getTypeName(2),
              'PluginMetademandsITILApplication' => PluginMetademandsITILApplication::getTypeName(2),
              'PluginMetademandsITILEnvironment' => PluginMetademandsITILEnvironment::getTypeName(2)];
   } else {
      return [];
   }
}

// Hook done on purge item case
/**
 * @param $item
 */
function plugin_pre_item_purge_metademands($item) {
   switch (get_class($item)) {
      case 'PluginMetademandsMetademand' :
         $temp = new PluginMetademandsTask();
         $temp->deleteByCriteria(['plugin_metademands_metademands_id' => $item->getField('id')], 1);

         $temp = new PluginMetademandsGroup();
         $temp->deleteByCriteria(['plugin_metademands_metademands_id' => $item->getField('id')], 1);

         $temp = new PluginMetademandsField();
         $temp->deleteByCriteria(['plugin_metademands_metademands_id' => $item->getField('id')], 1);

         $temp = new PluginMetademandsTicketField();
         $temp->deleteByCriteria(['plugin_metademands_metademands_id' => $item->getField('id')], 1);
         break;

      case 'PluginMetademandsTask' :
         $temp = new PluginMetademandsTicketTask();
         $temp->deleteByCriteria(['plugin_metademands_tasks_id' => $item->getField('id')], 1);

         $temp = new PluginMetademandsMetademandTask();
         $temp->deleteByCriteria(['plugin_metademands_tasks_id' => $item->getField('id')], 1);
         break;

      case 'PluginMetademandsField' :
         $temp = new PluginMetademandsTicket_Field();
         $temp->deleteByCriteria(['plugin_metademands_fields_id' => $item->getField('id')], 1);
         break;

      case 'Ticket' :
         $temp = new PluginMetademandsTicket_Task();
         $temp->deleteByCriteria(['tickets_id' => $item->getField('id')], 1);

         $temp = new PluginMetademandsTicket_Metademand();
         $temp->deleteByCriteria(['tickets_id' => $item->getField('id')], 1);

         $temp = new PluginMetademandsTicket_Field();
         $temp->deleteByCriteria(['tickets_id' => $item->getField('id')], 1);

         $temp = new PluginMetademandsTicket_ITILApplication();
         $temp->deleteByCriteria(['tickets_id' => $item->getField('id')], 1);

         $temp = new PluginMetademandsTicket_ITILEnvironment();
         $temp->deleteByCriteria(['tickets_id' => $item->getField('id')], 1);
         break;

      case 'Group' :
         $temp = new PluginMetademandsGroup();
         $temp->deleteByCriteria(['groups_id' => $item->getField('id')], 1);
         break;

      case 'PluginResourcesContractType' :
         $temp = new PluginMetademandsMetademand_Resource();
         $temp->deleteByCriteria(['plugin_resources_contracttypes_id' => $item->getField('id')], 1);
         break;
   }
}

// How to display specific actions ?
// options contain at least itemtype and and action
/**
 * @param array $options
 */
function plugin_metademands_MassiveActionsDisplay($options = []) {

   switch ($options['itemtype']) {
      case 'PluginMetademandsMetademand':
         switch ($options['action']) {
            case "plugin_metademands_duplicate":
               echo "&nbsp;<input type=\"submit\" name=\"massiveaction\" 
                     class=\"submit\" value=\"" . _sx('button', 'Post') . "\" >";
               break;
         }
         break;
   }
}

// Define dropdown relations
/**
 * @return array|\string[][]
 */
function plugin_metademands_getDatabaseRelations() {

   $plugin = new Plugin();
   if ($plugin->isActivated("metademands")) {
      return ["glpi_entities" => ["glpi_plugin_metademands_metademands"           => "entities_id",
                                  "glpi_plugin_metademands_fields"                => "entities_id",
                                  "glpi_plugin_metademands_itilapplications"      => "entities_id",
                                  "glpi_plugin_metademands_itilenvironments"      => "entities_id",
                                  "glpi_plugin_metademands_metademands_resources" => "entities_id",
                                  "glpi_plugin_metademands_ticketfields"          => "entities_id",
                                  "glpi_plugin_metademands_tasks"                 => "entities_id"],

              "glpi_plugin_metademands_metademands" => ["glpi_plugin_metademands_fields"                => "plugin_metademands_metademands_id",
                                                        "glpi_plugin_metademands_tickets_metademands"   => "plugin_metademands_metademands_id",
                                                        "glpi_plugin_metademands_metademandtasks"       => "plugin_metademands_metademands_id",
                                                        "glpi_plugin_metademands_ticketfields"          => "plugin_metademands_metademands_id",
                                                        "glpi_plugin_metademands_tasks"                 => "plugin_metademands_metademands_id",
                                                        "glpi_plugin_metademands_metademands_resources" => "plugin_metademands_metademands_id"],

              "glpi_tickets" => ["glpi_plugin_metademands_tickets_fields"           => "tickets_id",
                                 "glpi_plugin_metademands_tickets_tasks"            => "tickets_id",
                                 "glpi_plugin_metademands_tickets_metademands"      => "tickets_id",
                                 "glpi_plugin_metademands_tickets_itilapplications" => "tickets_id",
                                 "glpi_plugin_metademands_tickets_itilenvironments" => "tickets_id"],

              "glpi_plugin_metademands_fields" => ["glpi_plugin_metademands_tickets_fields" => "plugin_metademands_fields_id"],

              "glpi_plugin_metademands_tasks" => ["glpi_plugin_metademands_fields"          => "plugin_metademands_tasks_id",
                                                  "glpi_plugin_metademands_tickettasks"     => "plugin_metademands_tasks_id",
                                                  "glpi_plugin_metademands_tickets_tasks"   => "plugin_metademands_tasks_id",
                                                  "glpi_plugin_metademands_metademandtasks" => "plugin_metademands_tasks_id"],

              "glpi_plugin_metademands_itilapplications" => ["glpi_plugin_metademands_tickets_itilapplications" => "plugin_metademands_itilapplications_id"],

              "glpi_plugin_metademands_itilenvironments" => ["glpi_plugin_metademands_tickets_itilenvironments" => "plugin_metademands_itilenvironments_id"],
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
/**
 * @param $data
 *
 * @return mixed
 */
function plugin_metademands_MassiveActionsProcess($data) {
   $metademand = new PluginMetademandsMetademand();
   $res        = $metademand->doSpecificMassiveActions($data);

   return $res;
}

/**
 * @param $options
 *
 * @return array
 */
/**
 * @param $options
 *
 * @return array
 */
function plugin_metademands_getRuleActions($options) {
   if ($options['rule_itemtype'] == 'RuleTicket') {
      $ticket = new PluginMetademandsTicket();
      return $ticket->addRuleFields();
   }
}

/**
 * @param $options
 *
 * @return array
 */
/**
 * @param $options
 *
 * @return array
 */
function plugin_metademands_getRuleCriteria($options) {
   if ($options['rule_itemtype'] == 'RuleTicket') {
      $ticket = new PluginMetademandsTicket();
      return $ticket->addRuleFields();
   }
}

function plugin_metademands_registerMethods() {
   global $WEBSERVICES_METHOD;

   $WEBSERVICES_METHOD['metademands.addMetademands']
      = ['PluginMetademandsMetademand', 'methodAddMetademands'];
   $WEBSERVICES_METHOD['metademands.listMetademands']
      = ['PluginMetademandsMetademand', 'methodListMetademands'];
   $WEBSERVICES_METHOD['metademands.listMetademandsfields']
      = ['PluginMetademandsField', 'methodListMetademandsfields'];
   $WEBSERVICES_METHOD['metademands.listTasktypes']
      = ['PluginMetademandsTask', 'methodListTasktypes'];
   $WEBSERVICES_METHOD['metademands.showMetademands']
      = ['PluginMetademandsMetademand', 'methodShowMetademands'];
   $WEBSERVICES_METHOD['metademands.showTicketForm']
      = ['PluginMetademandsTicket', 'methodShowTicketForm'];
   $WEBSERVICES_METHOD['metademands.isMandatoryFields']
      = ['PluginMetademandsTicket', 'methodIsMandatoryFields'];

}

function plugin_datainjection_populate_metademands() {
   global $INJECTABLE_TYPES;

   $INJECTABLE_TYPES['PluginMetademandsITILApplicationInjection'] = 'metademands';
}

/**
 * @return bool
 * @throws \GlpitestSQLError
 */
/**
 * @return bool
 * @throws \GlpitestSQLError
 */
function dbMyISAM() {
   global $DB;

   $query  = "SELECT TABLE_NAME,ENGINE FROM information_schema.TABLES
             WHERE TABLE_SCHEMA = '$DB->dbdefault' 
             AND ENGINE='MyISAM'";
   $myISAM = false;
   if ($result = $DB->query($query)) {
      if ($DB->numrows($result) > 0) {
         while ($data = $DB->fetchAssoc($result)) {
            if ($data['TABLE_NAME'] == "glpi_itilcategories" ||
                $data['TABLE_NAME'] == "glpi_tickets" ||
                $data['TABLE_NAME'] == "glpi_groups") {
               $myISAM = true;
            }
         }
      }
   }
   return $myISAM;
}
