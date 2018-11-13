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
 * Class PluginMetademandsITILEnvironment
 */
class PluginMetademandsITILEnvironment extends CommonDropdown {

   const NO_OBJECT = 1;

   static function getTypeName($nb = 0) {

      return _n('Environment', 'Environments', $nb, 'metademands');
   }

   /**
    * @return bool
    */
   function pre_deleteItem() {
      if ($this->input['id'] == self::NO_OBJECT) {
         return false;
      }

      return true;
   }

   /**
    * @param $application_name
    * @param $environment_name
    */
   static function getDisplayScript($application_name, $environment_name) {
      echo 'var itilenvironments = document.getElementsByName("'.$environment_name.'")[0];
            var itilapplications = document.getElementsByName("'.$application_name.'")[0];
            if(itilapplications.value == 0 || itilapplications.value == 1){
               itilenvironments.value = 1;
               var op = itilenvironments.getElementsByTagName("option");
               for (var i = 0; i < op.length; i++) {
                  (op[i].value != 1)?op[i].disabled = true:op[i].disabled = false;
               }
            } else {
               if(itilenvironments.value == 1) itilenvironments.value = 0;
               var op = itilenvironments.getElementsByTagName("option");
               for (var i = 0; i < op.length; i++) {
                  (op[i].value == 1)?op[i].disabled = true:op[i].disabled = false;
               }
            }
         ';
   }

   // Get last productTypeTicket

   /**
    * @param       $tickets_id
    * @param array $options
    *
    * @throws \GlpitestSQLError
    */
   function getITILEnvironmentForTicket($tickets_id, $options = []) {
      global $DB;

      $query  = "SELECT `glpi_plugin_metademands_itilenvironments`.*,
                       `glpi_plugin_metademands_tickets_itilenvironments`.`plugin_metademands_itilenvironments_id`,
                       `glpi_plugin_metademands_tickets_itilenvironments`.`id` as link_id
                  FROM `glpi_plugin_metademands_itilenvironments`
                  LEFT JOIN `glpi_plugin_metademands_tickets_itilenvironments`
                    ON (`glpi_plugin_metademands_tickets_itilenvironments`.`plugin_metademands_itilenvironments_id` = `glpi_plugin_metademands_itilenvironments`.`id`)
                  WHERE `glpi_plugin_metademands_tickets_itilenvironments`.`tickets_id` = '".$tickets_id."'";
      $result = $DB->query($query);

      if ($DB->numrows($result)) {
         $this->fields = $DB->fetch_assoc($result);
      } else {
         $this->getEmpty();
         $this->fields['plugin_metademands_itilenvironments_id'] = 0;
      }
   }

   // UPDATE ITILENVIRONMENT

   /**
    * @param array $ticket_values
    */
   function updateITILEnvironmentForTicket($ticket_values = []) {
      if (isset($ticket_values['plugin_metademands_itilenvironments_id'])) {
         $ticket_itilenvironment = new PluginMetademandsTicket_ITILEnvironment();
         $this->getITILEnvironmentForTicket($ticket_values['id']);
         if (!isset($this->fields['link_id'])) {
            $ticket_itilenvironment->add(['tickets_id'                             => $ticket_values['id'],
               'plugin_metademands_itilenvironments_id' => $ticket_values['plugin_metademands_itilenvironments_id']]);
         } else {
            $ticket_itilenvironment->update(['id'                                     => $this->fields['link_id'],
               'plugin_metademands_itilenvironments_id' => $ticket_values['plugin_metademands_itilenvironments_id']]);
         }
      }
   }

   // ADD ITILENVIRONMENT

   /**
    * @param array $ticket_values
    */
   function addITILEnvironmentForTicket($ticket_values = []) {
      if (isset($ticket_values['plugin_metademands_itilenvironments_id'])) {
         $ticket_itilenvironment = new PluginMetademandsTicket_ITILEnvironment();
         $ticket_itilenvironment->add(['tickets_id'                             => $ticket_values['id'],
            'plugin_metademands_itilenvironments_id' => $ticket_values['plugin_metademands_itilenvironments_id']]);
      }
   }

   function getAdditionalFields() {

      $tab = [['name'  => 'is_outproduction',
            'label' => __('Is out of production', 'metademands'),
            'type'  => 'bool',
            'list'  => false],
      ];

      return $tab;
   }

   /**
    * Add search options for an item
    *
    * @return array
    */
   function getAddSearchOptions() {

      $tab[9050]['table']      = $this->getTable();
      $tab[9050]['field']      = 'name';
      $tab[9050]['field_name'] = 'plugin_metademands_itilenvironments_id';
      $tab[9050]['name']       = self::getTypeName();
      $tab[9050]['datatype']   = 'dropdown';

      return $tab;
   }

   public function rawSearchOptions() {
      $tab = parent::rawSearchOptions();

      $tab[] = [
         'id'       => '20',
         'table'    => $this->getTable(),
         'field'    => 'is_outproduction',
         'name'     => __('Is out of production', 'metademands'),
         'datatype' => 'bool',
         'searchtype' => 'equals'
      ];

      return $tab;
   }

}
