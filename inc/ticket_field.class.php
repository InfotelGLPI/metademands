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

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

/**
 * Class PluginMetademandsTicket_Field
 */
class PluginMetademandsTicket_Field extends CommonDBTM {

   public $itemtype = 'PluginMetademandsMetademand';

   static $types = ['PluginMetademandsMetademand'];

   static $rightname = 'plugin_metademands';


   /**
    * functions mandatory
    * getTypeName(), canCreate(), canView()
    * */
   static function getTypeName($nb = 0) {
      return __('Wizard creation', 'metademands');
   }

   static function canView() {
      return Session::haveRight(self::$rightname, READ);
   }

   static function canCreate() {
      return Session::haveRightsOr(self::$rightname, [CREATE, UPDATE, DELETE]);
   }

   /**
    * @param $parent_fields
    * @param $values
    * @param $tickets_id
    */
   function setTicketFieldsValues($parent_fields, $values, $tickets_id) {
      if (count($parent_fields)) {
         foreach ($parent_fields as $fields_id => $field) {
            $field['value'] = '';
            if (isset($values[$fields_id])) {
               $field['value'] = $values[$fields_id];
            }
            $this->add(['value'                             => $field['value'],
                             'tickets_id'                        => $tickets_id,
                             'plugin_metademands_fields_id'      => $fields_id]);
         }
      }
   }

   /**
    * @param $tasks_id
    * @param $parent_tickets_id
    *
    * @return bool
    * @throws \GlpitestSQLError
    */
   static function checkTicketCreation($tasks_id, $parent_tickets_id) {
      global $DB;

      $check = [];

      $query = "SELECT `glpi_plugin_metademands_fields`.`check_value`,
                       `glpi_plugin_metademands_fields`.`type`,
                       `glpi_plugin_metademands_tickets_fields`.`value` as field_value
               FROM `glpi_plugin_metademands_tickets_fields`
               LEFT JOIN `glpi_plugin_metademands_fields`
                  ON (`glpi_plugin_metademands_fields`.`id` = `glpi_plugin_metademands_tickets_fields`.`plugin_metademands_fields_id`)
               WHERE `glpi_plugin_metademands_fields`.`plugin_metademands_tasks_id` = ".$tasks_id." 
               AND `glpi_plugin_metademands_tickets_fields`.`tickets_id` = ".$parent_tickets_id;
      $result = $DB->query($query);
      if ($DB->numrows($result)) {
         while ($data = $DB->fetch_assoc($result)) {
            $check[] = self::isCheckValueOK($data['field_value'], $data['check_value'], $data['type']);
         }
      }

      if (in_array(false, $check)) {
         return false;
      }

      return true;
   }

   /**
    * @param $value
    * @param $check_value
    * @param $type
    *
    * @return bool
    */
   static function isCheckValueOK($value, $check_value, $type) {
      switch ($type) {
         case 'yesno':
            if ($check_value != $value) {
               return false;
            }
            break;
         case 'link':
            if ((($check_value == PluginMetademandsField::$not_null || $check_value == 0) && empty($value))) {
               return false;
            }
            break;
         default:
            if ($check_value == PluginMetademandsField::$not_null && empty($value)) {
               return false;
            }
            break;
      }

      return true;
   }

}