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
 * Class PluginMetademandsITILApplication
 */
class PluginMetademandsITILApplication extends CommonDropdown {

   const NO_APPLICATION     = 1;
   // Critical criticity
   const CRITICAL_CRITICITY = 1;
   // Major criticity
   const MAJOR_CRITICITY    = 2;
   // Minor criticity
   const MINOR_CRITICITY    = 3;

   /**
    * @param int $nb
    *
    * @return string
    */
   static function getTypeName($nb = 0) {

      return _n('Application', 'Applications', $nb, 'metademands');
   }

   /**
    * @return bool
    */
   function pre_deleteItem() {
      if ($this->input['id'] == self::NO_APPLICATION) {
         return false;
      }

      return true;
   }

   // Get last productTypeTicket

   /**
    * @param       $tickets_id
    * @param array $options
    *
    * @throws \GlpitestSQLError
    */
   function getITILApplicationForTicket($tickets_id, $options = []) {
      global $DB;

      $query  = "SELECT `glpi_plugin_metademands_itilapplications`.*,
                 `glpi_plugin_metademands_tickets_itilapplications`.`plugin_metademands_itilapplications_id`,
                 `glpi_plugin_metademands_tickets_itilapplications`.`id` as link_id
            FROM `glpi_plugin_metademands_itilapplications`
            LEFT JOIN `glpi_plugin_metademands_tickets_itilapplications`
              ON (`glpi_plugin_metademands_tickets_itilapplications`.`plugin_metademands_itilapplications_id` = `glpi_plugin_metademands_itilapplications`.`id`)
            WHERE `glpi_plugin_metademands_tickets_itilapplications`.`tickets_id` = '".$tickets_id."'";
      $result = $DB->query($query);

      if ($DB->numrows($result)) {
         $this->fields = $DB->fetchAssoc($result);
      } else {
         $this->getEmpty();
         $this->fields['plugin_metademands_itilapplications_id'] = 0;
      }
   }

   // UPDATE ITILAPPLICATION

   /**
    * @param array $ticket_values
    *
    * @throws \GlpitestSQLError
    * @throws \GlpitestSQLError
    */
   function updateITILApplicationForTicket($ticket_values = []) {
      if (isset($ticket_values['plugin_metademands_itilapplications_id'])) {
         $ticket_itilapplication = new PluginMetademandsTicket_ITILApplication();
         $this->getITILApplicationForTicket($ticket_values['id']);

         if (!isset($this->fields['link_id'])) {
            $ticket_itilapplication->add(['tickets_id'                             => $ticket_values['id'],
               'plugin_metademands_itilapplications_id' => $ticket_values['plugin_metademands_itilapplications_id']]);
         } else {
            $ticket_itilapplication->update(['id'                                     => $this->fields['link_id'],
               'plugin_metademands_itilapplications_id' => $ticket_values['plugin_metademands_itilapplications_id']]);
         }
      }
   }

   // ADD ITILAPPLICATION

   /**
    * @param array $ticket_values
    */
   function addITILApplicationForTicket($ticket_values = []) {
      if (isset($ticket_values['plugin_metademands_itilapplications_id'])) {
         $ticket_itilapplication = new PluginMetademandsTicket_ITILApplication();
         $ticket_itilapplication->add(['tickets_id'                             => $ticket_values['id'],
            'plugin_metademands_itilapplications_id' => $ticket_values['plugin_metademands_itilapplications_id']]);
      }
   }

   /**
    * @return array|array[]
    */
   function getAdditionalFields() {
      $tab = [['name'  => 'is_critical',
            'label' => __('Criticity', 'metademands'),
            'type'  => 'specific',
            'list'  => false],
      ];

      return $tab;
   }

   /**
    * @param       $ID
    * @param array $field
    */
   function displaySpecificTypeField($ID, $field = []) {
      switch ($field['name']) {
         case 'is_critical':
            Dropdown::showFromArray($field['name'], self::getCriticity(), ['value' => $this->fields[$field['name']]]);
            break;
      }
   }

   /**
    * @since version 0.84
    *
    * @param $field
    * @param $name            (default '')
    * @param $values          (default '')
    * @param $options   array
    *
    * @return string
    * */
   static function getSpecificValueToSelect($field, $name = '', $values = '', array $options = []) {

      if (!is_array($values)) {
         $values = [$field => $values];
      }
      $options['display'] = false;
      switch ($field) {
         case 'is_critical':
            return Dropdown::showFromArray($name, self::getCriticity(), ['value' => $values[$field], 'display' => false]);
            break;
      }
      return parent::getSpecificValueToSelect($field, $name, $values, $options);
   }

   /**
    * @param $field
    * @param $values
    * @param $options   array
    *
    * @return mixed|string
    * @return mixed|string
    * @since version 0.84
    *
    */
   static function getSpecificValueToDisplay($field, $values, array $options = []) {

      if (!is_array($values)) {
         $values = [$field => $values];
      }
      switch ($field) {
         case 'is_critical' :
            $criticity = self::getCriticity();
            return $criticity[$values[$field]];
      }
      return parent::getSpecificValueToDisplay($field, $values, $options);
   }

   /**
    * Get application criticity
    *
    * @return array of criticity
    * */
   static function getCriticity() {
      $options[0]                        = Dropdown::EMPTY_VALUE;
      $options[self::CRITICAL_CRITICITY] = __('Critical', 'metademands');
      $options[self::MAJOR_CRITICITY]    = __('Major', 'metademands');
      $options[self::MINOR_CRITICITY]    = __('Minor', 'metademands');

      return $options;
   }

   /**
    * Get application criticity Name
    *
    * @param $value criticity ID
    *
    * @return mixed
    * @return mixed
    */
   static function getApplicationCriticityName($value) {
      $criticity = self::getCriticity();
      if (isset($criticity[$value])) {
         return $criticity[$value];
      }
   }

   /**
    * Add search options for an item
    *
    * @return array
    */
   function getAddSearchOptions() {

      $tab[8050]['table']      = $this->getTable();
      $tab[8050]['field']      = 'name';
      $tab[8050]['field_name'] = 'plugin_metademands_itilapplications_id';
      $tab[8050]['name']       = self::getTypeName();
      $tab[8050]['datatype']   = 'dropdown';

      return $tab;
   }

   /**
    * @return array
    */
   public function rawSearchOptions() {

      $tab = parent::rawSearchOptions();

      $tab[] = [
         'id'       => '20',
         'table'    => $this->getTable(),
         'field'    => 'is_critical',
         'name'     => __('Criticity', 'metademands'),
         'datatype' => 'specific',
         'searchtype' => 'equals'
      ];

      return $tab;
   }

}
