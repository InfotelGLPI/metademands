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

class PluginMetademandsBasketline extends CommonDBTM {

   static $rightname = 'plugin_metademands';

   /**
    * @param $values
    */
   public static function retrieveDatasByType($idline, $values, $fields) {

      echo "<tr class='basket-data'>";

      foreach ($fields as $k => $v) {

         foreach ($values as $key => $value) {

            if ($v['id'] == $value['plugin_metademands_fields_id']) {

               switch ($v['type']) {

                  case 'id' :
                  case 'line' :
                  case 'plugin_metademands_metademands_id':
                  case 'plugin_metademands_fields_id':
                  case 'link':
                     break;

                  case 'text':
                  case 'textarea':
                  case 'number':
                     $display = $value['value'];
                     echo "<td>" . $display . "</td>";
                     break;

                  case 'upload':
                     $arrayFiles = json_decode($value['value'], true);
                     echo "<td>";
                     if ($arrayFiles != "") {
                        foreach ($arrayFiles as $file) {
                           echo str_replace($file['_prefix_filename'], "", $file['_filename']) . "<br />";
                        }
                     }
                     echo "</td>";
                     break;

                  case 'dropdown':
                     $display = "";
                     switch ($v['item']) {
                        case 'user':
                           $display = getUserName($value['value']);
                           break;
                        case 'other':
                           if (!empty($v['custom_values']) && isset ($v['custom_values'])) {
                              $v['custom_values'] = PluginMetademandsField::_unserialize($v['custom_values']);
                              $display            = ($value['value'] != 0) ? $v['custom_values'][$value['value']] : ' ';
                           }
                           break;
                        //others
                        default:
                           $display = Dropdown::getDropdownName(getTableForItemType($v['item']), $value['value']);
                           $display = ($display == '&nbsp;') ? ' ' : $display;
                           break;
                     }
                     echo "<td>" . $display . "</td>";
                     break;

                  case 'yesno':
                     $display = "";
                     if ($value['value'] == 1) {
                        $display = __('No');
                     } else if ($value['value'] == 2) {
                        $display = __('Yes');
                     }
                     echo "<td>" . $display . "</td>";
                     break;

                  case 'dropdown_multiple':
                     $display = " ";
                     if (!empty($v['custom_values'])) {
                        $custom_values = PluginMetademandsField::_unserialize($v['custom_values']);
                        $values_fields = PluginMetademandsField::_unserialize($value['value']);
                        $parseValue    = [];
                        if (is_array($values_fields)
                            && count($values_fields) > 0) {
                           foreach ($values_fields as $key => $val) {
                              array_push($parseValue, $custom_values[$val]);
                           }
                        }
                        $display = implode(', ', $parseValue);
                     }
                     echo "<td>" . $display . "</td>";
                     break;

                  case 'checkbox':
                     $display = " ";
                     if (!empty($v['custom_values'])) {
                        $custom_values   = PluginMetademandsField::_unserialize($v['custom_values']);
                        $values_fields   = PluginMetademandsField::_unserialize($value['value']);
                        $custom_checkbox = [];
                        foreach ($custom_values as $key => $val) {
                           $checked = isset($values_fields[$key]) ? 1 : 0;
                           if ($checked) {
                              $custom_checkbox[] .= $val;
                           }
                        }
                        $display = implode(', ', $custom_checkbox);
                     }
                     echo "<td>" . $display . "</td>";
                     break;

                  case 'radio' :
                     $display = " ";
                     if (!empty($v['custom_values'])) {
                        $custom_values = PluginMetademandsField::_unserialize($v['custom_values']);
                        $values_fields = PluginMetademandsField::_unserialize($value['value']);
                        //specific for radio
                        if ($value['value'] != "") {
                           foreach ($custom_values as $key => $val) {
                              if ($values_fields == $key) {
                                 $display = $custom_values[$key];
                              }
                           }
                        }
                     }
                     echo "<td>" . $display . "</td>";
                     break;

                  case 'datetime':
                     echo "<td>" . Html::convDate($value['value']) . "</td>";
                     break;

                  case 'datetime_interval':
                     $interval = " ";
                     $display  = Html::convDate($value['value']);
                     $display2 = Html::convDate($value['value2']);
                     if (!empty($value['value'])) {
                        $interval = $display . " / " . $display2;
                     }
                     echo "<td>" . $interval . "</td>";
                     break;
                  default :
                     echo "<td>" . $value['value'] . "</td>";
                     break;
               }
            }
         }
      }
      echo "<td>";
      echo "<button type='submit' class='btn btn-default' name='deletebasketline' value='$idline' class='delete-line-basket'>";
      echo "<i class='fas fa-trash' data-hasqtip='0' aria-hidden='true'></i>";
      echo "</button>";
      echo "</td>";
      echo "</tr>";
   }

   /**
    * @param $content
    */
   function addToBasket($content, $plugin_metademands_metademands_id) {
      global $DB;

      $query  = "SELECT MAX(`line`)
                FROM `" . $this->getTable() . "`
                WHERE `plugin_metademands_metademands_id` = $plugin_metademands_metademands_id 
                AND `users_id` = ".Session::getLoginUserID()."";
      $result = $DB->query($query);

      $line = $DB->result($result, 0, 0) + 1;

      foreach ($content as $values) {
         //TODO drop if empty datas ??
         $name = $values['item'];
         if ($values['type'] == "dropdown_multiple") {
            $name = $values['type'];
         }
         $this->add(['name'                              => $name,
                     'value'                             => $values['value'],
                     'value2'                            => $values['value2'],
                     'line'                              => $line,
                     'plugin_metademands_fields_id'      => $values['plugin_metademands_fields_id'],
                     'plugin_metademands_metademands_id' => $plugin_metademands_metademands_id,
                     'users_id'                          => Session::getLoginUserID()]);

      }
   }

   /**
    * @param $input
    */
   function deleteFromBasket($input) {

      $this->deleteByCriteria(['line' => $input['deletebasketline'],
                               'users_id' => Session::getLoginUserID()]);
      Session::addMessageAfterRedirect(__("The line has been deleted", "metademands"), false, INFO);
   }
}