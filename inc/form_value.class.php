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
 * Class PluginMetademandsDraft
 */
class PluginMetademandsForm_Value extends CommonDBTM {

   static $rightname = 'plugin_metademands';

   /**
    * @param $parent_fields
    * @param $values
    * @param $tickets_id
    */
   static function setFormValues($parent_fields, $values, $form_id) {

      if (count($parent_fields)) {
         foreach ($parent_fields as $fields_id => $field) {
            $field['value'] = '';
            if (isset($values[$fields_id]) && !is_array($values[$fields_id])) {
               $field['value'] = $values[$fields_id];
            } else if (isset($values[$fields_id]) && is_array($values[$fields_id])) {
               $field['value'] = json_encode($values[$fields_id]);
            }
            $field['value2'] = '';
            if (isset($values[$fields_id . "-2"]) && !is_array($values[$fields_id . "-2"])) {
               $field['value2'] = $values[$fields_id . "-2"];
            } else if (isset($values[$fields_id . "-2"]) && is_array($values[$fields_id . "-2"])) {
               $field['value2'] = json_encode($values[$fields_id . "-2"]);
            }
            $form_value = new self();
            //TODO CHANGE
            $form_value->add([
                                 'value'                        => $field['value'],
                                 'value2'                       => $field['value2'],
                                 'plugin_metademands_forms_id' => $form_id,
                                 'plugin_metademands_fields_id' => $fields_id]);
         }
      }
   }

   /**
    * @param $plugin_metademands_drafts_id
    * @param $users_id
    */
   static function loadFormValues($plugin_metademands_forms_id) {
      $form_value   = new self();
      $forms_values = $form_value->find(['plugin_metademands_forms_id' => $plugin_metademands_forms_id]);
      foreach ($forms_values as $values) {
         if (isset($_SESSION['plugin_metademands']['fields'][$values['plugin_metademands_fields_id']])) {
            unset($_SESSION['plugin_metademands']['fields'][$values['plugin_metademands_fields_id']]);
         }
         if (isset($_SESSION['plugin_metademands']['fields'][$values['plugin_metademands_fields_id'] . "-2"])) {
            unset($_SESSION['plugin_metademands']['fields'][$values['plugin_metademands_fields_id'] . "-2"]);
         }
         $_SESSION['plugin_metademands']['fields'][$values['plugin_metademands_fields_id']] = Toolbox::addslashes_deep(json_decode($values['value'], true)) ?? Toolbox::addslashes_deep($values['value']);
         if (!empty($values['value2'])) {
            $_SESSION['plugin_metademands']['fields'][$values['plugin_metademands_fields_id'] . "-2"] = Toolbox::addslashes_deep(json_decode($values['value2'], true)) ?? Toolbox::addslashes_deep($values['value2']);
         }
      }
   }
}
