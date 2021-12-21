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
 * Class PluginMetademandsTicket_Metademand
 */
class PluginMetademandsTicket_Metademand extends CommonDBTM {

   static $rightname = 'plugin_metademands';
   const RUNNING   = 1;
   const TO_CLOSED = 2;
   const CLOSED    = 3;

   /**
    * functions mandatory
    * getTypeName(), canCreate(), canView()
    *
    * @param int $nb
    *
    * @return string
    */
   static function getTypeName($nb = 0) {
      return __('Task creation', 'metademands');
   }

   /**
    * @return bool|int
    */
   static function canView() {
      return Session::haveRight(self::$rightname, READ);
   }

   /**
    * @return bool
    */
   static function canCreate() {
      return Session::haveRightsOr(self::$rightname, [CREATE, UPDATE, DELETE]);
   }

   /**
    * Display tab for each users
    *
    * @param CommonGLPI $item
    * @param int        $withtemplate
    *
    * @return array|string
    */
   function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {
      global $DB;
      if (!$withtemplate) {
         if ($item->getType() == 'PluginMetademandsMetademand') {
            if ($_SESSION['glpishow_count_on_tabs']) {
               $query = self::countTicketsInTable($item->getID());
               $result  = $DB->query($query);
               $numrows = $DB->numrows($result);

               return self::createTabEntry(__('Linked opened tickets', 'metademands'),
                                           $numrows);
            }
            return __('Linked opened tickets', 'metademands');
         }
      }
      return '';
   }


   /**
    * @param $meta_id
    *
    * @return string
    */
   static function countTicketsInTable($meta_id) {

      $status  = CommonITILObject::INCOMING . ", " . CommonITILObject::PLANNED . ", " .
                 CommonITILObject::ASSIGNED . ", " . CommonITILObject::WAITING;

      $query = "SELECT DISTINCT `glpi_tickets`.`id`
                FROM `glpi_tickets`
                LEFT JOIN `glpi_tickets_users`
                  ON (`glpi_tickets`.`id` = `glpi_tickets_users`.`tickets_id`)
                LEFT JOIN `glpi_plugin_metademands_tickets_metademands`
                  ON (`glpi_tickets`.`id` = `glpi_plugin_metademands_tickets_metademands`.`tickets_id`)
                LEFT JOIN `glpi_groups_tickets`
                  ON (`glpi_tickets`.`id` = `glpi_groups_tickets`.`tickets_id`)";

      $query .= "WHERE `glpi_tickets`.`is_deleted` = 0 
      AND `glpi_plugin_metademands_tickets_metademands`.`plugin_metademands_metademands_id` = $meta_id 
      AND (`glpi_tickets`.`status` IN ($status)) " .
                getEntitiesRestrictRequest("AND", "glpi_tickets");
      $query .= " ORDER BY glpi_tickets.date_mod DESC";

      return $query;

   }

   /**
    * Display content for each users
    *
    * @static
    *
    * @param CommonGLPI $item
    * @param int        $tabnum
    * @param int        $withtemplate
    *
    * @return bool|true
    */
   static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {
      global $DB;

      if (!Session::haveRight("ticket", Ticket::READALL)
          && !Session::haveRight("ticket", Ticket::READASSIGN)
          && !Session::haveRight("ticket", CREATE)) {
         return false;
      }

      $query = self::countTicketsInTable($item->getID());
      $result  = $DB->query($query);
      $numrows = $DB->numrows($result);

      if ($numrows > 0) {

         echo "<table class='tab_cadrehov'>";
         echo "<tr>";
         echo "<th></th>";
         echo "<th>" . __('Requester') . "</th>";
         echo "<th>" . __('Associated element') . "</th>";
         echo "<th>" . __('Description') . "</th>";
         echo "</tr>";
         for ($i = 0; $i < $numrows; $i++) {
            $ID = $DB->result($result, $i, "id");
            Ticket::showVeryShort($ID);
         }
         echo "</table>";
      } else {
         echo "<div class='alert alert-important alert-info center'>".__('No item found')."</div>";
      }
      return true;
   }

   /**
    * @param $field
    * @param $name (default '')
    * @param $values (default '')
    * @param $options   array
    *
    * @return string
    * *@since version 0.84
    *
    */
   static function getSpecificValueToSelect($field, $name = '', $values = '', array $options = []) {

      if (!is_array($values)) {
         $values = [$field => $values];
      }
      $options['display'] = false;

      switch ($field) {
         case 'status' :
            $options['name']  = $name;
            $options['value'] = $values[$field];
            //            $options['withmajor'] = 1;
            return self::dropdownStatus($options);
            break;
      }
      return parent::getSpecificValueToSelect($field, $name, $values, $options);
   }

   /**
    * display a value according to a field
    *
    * @param $field     String         name of the field
    * @param $values    String / Array with the value to display
    * @param $options   Array          of option
    *
    * @return a string
    **@since version 0.83
    *
    */
   static function getSpecificValueToDisplay($field, $values, array $options = []) {

      if (!is_array($values)) {
         $values = [$field => $values];
      }
      switch ($field) {
         case 'status':
            return self::getStatusName($values[$field]);
            break;
      }
      return parent::getSpecificValueToDisplay($field, $values, $options);
   }


   /**
    * @param array $options
    *
    * @return int|string
    */
   static function dropdownStatus(array $options = []) {

      $p['name']     = 'status';
      $p['value']    = 0;
      $p['showtype'] = 'normal';
      $p['display']  = true;

      if (is_array($options) && count($options)) {
         foreach ($options as $key => $val) {
            $p[$key] = $val;
         }
      }

      $values                  = [];
      $values[0]               = static::getStatusName(0);
      $values[self::RUNNING]   = static::getStatusName(self::RUNNING);
      $values[self::TO_CLOSED] = static::getStatusName(self::TO_CLOSED);
      $values[self::CLOSED]    = static::getStatusName(self::CLOSED);

      return Dropdown::showFromArray($p['name'], $values, $p);
   }


   /**
    * @param $value
    *
    * @return string
    */
   static function getStatusName($value) {

      switch ($value) {

         case self::RUNNING :
            return _x('status', 'In progress', 'metademands');

         case self::TO_CLOSED :
            return _x('status', 'To close', 'metademands');

         case self::CLOSED :
            return _x('status', 'Closed', 'metademands');

         default :
            // Return $value if not define
            return Dropdown::EMPTY_VALUE;
      }
   }
}
