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
 * Class PluginMetademandsBasketline
 */
class PluginMetademandsBasketline extends CommonDBTM {

   static $rightname = 'plugin_metademands';

   /**
    * @param array $line
    * @param bool  $preview
    * @param       $metademands_id
    */
   static function constructBasket($metademands_id, $line = [], $preview = false) {

      if (count($line) > 0) {
         $metademands = new PluginMetademandsMetademand();
         $metademands->getFromDB($metademands_id);

         if (!$preview && countElementsInTable("glpi_plugin_metademands_basketlines",
                                  ["plugin_metademands_metademands_id" => $metademands->fields['id'],
                                   "users_id"                          => Session::getLoginUserID()])) {
            echo "<div style='text-align: center; margin-top: 20px; margin-bottom : 20px;' class=\"bt-feature col-md-12\">";
            echo "<input type='submit' class='submit' id='add_to_basket' name='add_to_basket' value='"
                 . _sx('button', 'Add to basket', 'metademands') . "'>";
            echo "</div>";
         }
         $basketline = new self();
         if ($basketlinesFind = $basketline->find(['plugin_metademands_metademands_id' => $metademands_id,
                                                   'users_id'                          => Session::getLoginUserID()])) {

            echo "<table class='table-basket'>";
            echo "<caption class='basket-title'>" . __('Your basket', 'metademands') . "</caption> ";
            echo "<tr class='basket-label'>";
            foreach ($line as $key => $data) {
               if ($data['item'] == 'informations') {
                  continue;
               }
               if ($data['is_basket'] == 1) {

                  echo "<th class='basket-th'>";
                  echo $data['label'];
                  echo "<span class='metademands_wizard_red' id='metademands_wizard_red" . $data['id'] . "'>";
                  if ($data['is_mandatory'] && $data['type'] != 'parent_field') {
                     echo "*";
                  }
                  echo "</span>";
                  echo "</th>";
               }
            }
            $class = "basket-th";
            //            if ($preview == false) {
            //               $class = "basket-delete-th";
            //            }
            echo "<th class='$class'></th>";
            echo "</tr>";

            $basketLines = [];
            if ($preview == false) {
               foreach ($basketlinesFind as $basketLine) {
                  $basketLines[$basketLine['line']][] = $basketLine;
               }
               foreach ($basketLines as $idline => $fieldlines) {
                  self::retrieveDatasByType($idline, $fieldlines, $line);
               }
            }
            echo "</table>";
         }
      }
   }

   /**
    * @param $idline
    * @param $values
    * @param $fields
    */
   public static function retrieveDatasByType($idline, $values, $fields) {

      echo "<tr class='basket-data'>";

      foreach ($fields as $k => $v) {

         foreach ($values as $key => $value) {

            if ($v['id'] == $value['plugin_metademands_fields_id']) {

               $v['value'] = '';
               if (isset($value['value'])) {
                  $v['value'] = $value['value'];
               }

               //TODO $metademands_data ?
               //TODO $itilcategories_id ?
               echo "<td>" . PluginMetademandsField::getFieldInput([], $v, true, 0, $idline) . "</td>";
            }
         }
      }
      echo "<td width='100px'>";
      echo "<button type='submit' class='btn update-line-basket' name='update_basket_line' value='$idline'>";
      echo "<i class='fas fa-save' data-hasqtip='0' aria-hidden='true'></i>&nbsp;";
      echo "<button type='submit' class='btn delete-line-basket' name='delete_basket_line' value='$idline'>";
      echo "<i class='fas fa-trash' data-hasqtip='0' aria-hidden='true'></i>";
      echo "</button>";
      echo "</td>";
      echo "</tr>";
   }

   /**
    * @param $content
    * @param $plugin_metademands_metademands_id
    *
    * @throws \GlpitestSQLError
    */
   function addToBasket($content, $plugin_metademands_metademands_id) {
      global $DB;

      $query  = "SELECT MAX(`line`)
                FROM `" . $this->getTable() . "`
                WHERE `plugin_metademands_metademands_id` = $plugin_metademands_metademands_id 
                AND `users_id` = " . Session::getLoginUserID() . "";
      $result = $DB->query($query);

      $line = $DB->result($result, 0, 0) + 1;

      foreach ($content as $values) {

         if ($values['item'] == "informations") {
            continue;
         }
         //TODO drop if empty datas ??
         $name = $values['item'];
         if ($values['type'] == "dropdown_multiple") {
            $name = $values['type'];
         }

         $this->add(['name'                              => $name,
                     'value'                             => isset($values['value']) ? $values['value'] : NULL,
                     'value2'                            => $values['value2'],
                     'line'                              => $line,
                     'plugin_metademands_fields_id'      => $values['plugin_metademands_fields_id'],
                     'plugin_metademands_metademands_id' => $plugin_metademands_metademands_id,
                     'users_id'                          => Session::getLoginUserID()]);

      }
   }

   /**
    * @param $input
    * @param $line
    */
   function updateFromBasket($input, $line) {

      $new_files = [];
      if (isset($input['_filename']) && !empty($input['_filename'])) {
         foreach ($input['_filename'] as $key => $filename) {
            $new_files[$key]['_prefix_filename'] = $input['_prefix_filename'][$key];
            $new_files[$key]['_tag_filename']    = $input['_tag_filename'][$key];
            $new_files[$key]['_filename']        = $input['_filename'][$key];
         }
      }
      foreach ($input['field_basket_' . $line] as $fields_id => $value) {

         //get id from form_metademands_id & $id
         $this->getFromDBByCrit(["plugin_metademands_metademands_id" => $input['form_metademands_id'],
                                 'plugin_metademands_fields_id'      => $fields_id,
                                 'line'                              => $input['update_basket_line']]);

         if ($this->fields['name'] != "itilcategory") {
            if ($this->fields['name'] == "upload") {

               $old_files = [];
               if (isset($this->fields['value']) && !empty($this->fields['value'])) {
                  $old_files = json_decode($this->fields['value'], 1);
               }
               if (is_array($new_files) && count($new_files) > 0
               && is_array($old_files) && count($old_files) > 0) {
                  $files = array_merge($old_files, $new_files);
                  $value = json_encode($files);
               } else {
                  $value = json_encode($new_files);
               }

            } else {
               $value = is_array($value) ? PluginMetademandsField::_serialize($value) : $value;
            }

            $this->update(['plugin_metademands_fields_id' => $fields_id,
                           'value'                        => $value,
                           'id'                           => $this->fields['id']]);
         }
      }
      if (isset($input['basket_plugin_servicecatalog_itilcategories_id'])) {

         $this->getFromDBByCrit(["plugin_metademands_metademands_id" => $input['form_metademands_id'],
                                 'name'                              => "itilcategory",
                                 'line'                              => $input['update_basket_line']]);

         $this->update(['value' => $input['basket_plugin_servicecatalog_itilcategories_id'],
                        'id'    => $this->fields['id']]);
      }


      Session::addMessageAfterRedirect(__("The line has been updated", "metademands"), false, INFO);
   }

   /**
    * @param $input
    */
   function deleteFromBasket($input) {

      $this->deleteByCriteria(['line'     => $input['delete_basket_line'],
                               'users_id' => Session::getLoginUserID()]);
      Session::addMessageAfterRedirect(__("The line has been deleted", "metademands"), false, INFO);
   }

   /**
    * @param $input
    */
   function deleteFileFromBasket($input) {

      $this->getFromDBByCrit(["plugin_metademands_metademands_id" => $input['metademands_id'],
                              'plugin_metademands_fields_id'      => $input['plugin_metademands_fields_id'],
                              'line'                              => $input['idline']]);

      $files = json_decode($this->fields['value'], 1);
      unset($files[$input['id']]);
      $files = json_encode($files);
      $this->update(['plugin_metademands_fields_id' => $input['plugin_metademands_fields_id'],
                     'value'                        => $files,
                     'id'                           => $this->fields['id']]);
   }
}