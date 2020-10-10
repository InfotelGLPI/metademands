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
 * Class PluginMetademandsField
 */
class PluginMetademandsField extends CommonDBChild {

   static public $itemtype = 'PluginMetademandsMetademand';
   static public $items_id = 'plugin_metademands_metademands_id';

   static $types = ['PluginMetademandsMetademand'];

   static $field_types = ['', 'dropdown', 'dropdown_multiple', 'text', 'checkbox', 'textarea', 'datetime', 'informations',
                          'datetime_interval', 'yesno', 'upload', 'title', 'radio', 'link', 'number', 'parent_field'];
   static $list_items  = ['', 'user', 'usertitle', 'usercategory', 'group', 'location', 'other', 'itilcategory',
                          'PluginMetademandsITILApplication', 'PluginMetademandsITILEnvironment', 'appliance'];

   static $not_null = 'NOT_NULL';

   static $rightname = 'plugin_metademands';

   /**
    * functions mandatory
    * getTypeName(), canCreate(), canView()
    *
    * @param int $nb
    *
    * @return string
    */
   static function getTypeName($nb = 0) {
      return __('Wizard creation', 'metademands');
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

      if (!$withtemplate) {
         if ($item->getType() == 'PluginMetademandsMetademand') {
            if ($_SESSION['glpishow_count_on_tabs']) {
               $dbu = new DbUtils();
               return self::createTabEntry(self::getTypeName(),
                                           $dbu->countElementsInTable($this->getTable(),
                                                                      ["plugin_metademands_metademands_id" => $item->getID()]));
            }
            return self::getTypeName();
         }
      }
      return '';
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
      $field = new self();

      if (in_array($item->getType(), self::getTypes(true))) {
         $field->showForm(0, ["item" => $item]);
      }
      return true;
   }


   /**
    * @param       $ID
    * @param array $options
    *
    * @return bool
    * @throws \GlpitestSQLError
    */
   function showForm($ID, $options = []) {
      global $CFG_GLPI;

      if (!$this->canview()) {
         return false;
      }
      if (!$this->cancreate()) {
         return false;
      }

      $metademand = new PluginMetademandsMetademand();

      if ($ID > 0) {
         $this->check($ID, READ);
         $metademand->getFromDB($this->fields['plugin_metademands_metademands_id']);
      } else {
         // Create item
         $item    = $options['item'];
         $canedit = $metademand->can($item->fields['id'], UPDATE);
         $this->getEmpty();
         $this->fields["plugin_metademands_metademands_id"] = $item->fields['id'];
         $this->fields['color']                             = '#000';
      }

      // Data saved in session
      if (isset($_SESSION['glpi_plugin_metademands_fields'])) {
         foreach ($_SESSION['glpi_plugin_metademands_fields'] as $key => $value) {
            $this->fields[$key] = $value;
         }
         unset($_SESSION['glpi_plugin_metademands_fields']);
      }


      if ($ID > 0) {
         $this->showFormHeader(['colspan' => 2]);
      } else {
         echo "<div class='center first-bloc'>";
         echo "<form name='field_form' method='post' action='" . Toolbox::getItemTypeFormURL(__CLASS__) . "'>";
         echo "<table class='tab_cadre_fixe'>";
         echo "<tr class='tab_bg_1'>";
         echo "<th colspan='6'>" . __('Add a field', 'metademands') . "</th>";
         echo "</tr>";
      }

      $metademand_fields = new self();
      $metademand_fields->getFromDBByCrit(['plugin_metademands_metademands_id' => $this->fields['plugin_metademands_metademands_id'],
                                           'item'                              => 'itilcategory']);
      $categories = [];
      if (isset($metademand->fields['itilcategories_id'])) {
         if (is_array(json_decode($metademand->fields['itilcategories_id'], true))) {
            $categories = json_decode($metademand->fields['itilcategories_id'], true);
         }
      }

      if (count($metademand_fields->fields) < 1 && count($categories) > 1) {
         echo "<tr style='margin-bottom: 5px;' class='tab_bg_1'>";
         echo "<td align='center' colspan='4'>";
         echo "<span style='color:darkred;font-size: 14px'>";
         echo "<i class='fas fa-exclamation-triangle'></i> " . __('Please add a type category field', 'metademands');
         echo "</span>";
         echo "</td>";
         echo "</tr>";
      }

      echo "<tr class='tab_bg_1'>";

      // LABEL
      echo "<td>" . __('Label') . "<span style='color:red'>&nbsp;*&nbsp;</span></td>";
      echo "<td>";
      Html::autocompletionTextField($this, "label", ['value' => stripslashes($this->fields["label"])]);
      if ($ID > 0) {
         echo "<input type='hidden' name='entities_id' value='" . $this->fields["entities_id"] . "'>";
         echo "<input type='hidden' name='is_recursive' value='" . $this->fields["is_recursive"] . "'>";
      } else {
         echo "<input type='hidden' name='entities_id' value='" . $item->fields["entities_id"] . "'>";
         echo "<input type='hidden' name='is_recursive' value='" . $item->fields["is_recursive"] . "'>";
      }
      echo "</td>";

      // MANDATORY
      echo "<td>" . __('Mandatory field') . "</td>";
      echo "<td>";
      Dropdown::showYesNo("is_mandatory", $this->fields["is_mandatory"]);
      echo "</td>";
      echo "</tr>";

      // LABEL 2
      echo "<tr class='tab_bg_1'>";
      echo "<td>";
      echo __('Additional label', 'metademands') . "&nbsp;<span id='show_label2' style='color:red;display:none;'>&nbsp;*&nbsp;</span>";
      echo "</td>";
      echo "<td>";
      Html::autocompletionTextField($this, "label2", ['value' => stripslashes($this->fields["label2"])]);
      echo "</td>";

      echo "<td>";
      echo __('Takes the whole row', 'metademands');
      echo "</td>";
      echo "<td>";
      Dropdown::showYesNo('row_display', ($this->fields['row_display']));
      echo "</td>";
      echo "</tr>";

      // COMMENT
      echo "<tr class='tab_bg_1'>";
      echo "<td>" . __('Comments') . "</td>";
      echo "<td>";
      echo Html::autocompletionTextField($this, "comment", ['value' => stripslashes($this->fields["comment"])]);
      echo "</td>";

      // RANK
      echo "<td>" . __('Block', 'metademands') . "</td>";
      echo "<td>";
      $randRank   = Dropdown::showNumber('rank', ['value' => $this->fields["rank"],
                                                  'min'   => 1,
                                                  'max'   => 10]);
      $paramsRank = ['rank'               => '__VALUE__',
                     'step'               => 'order',
                     'fields_id'          => $this->fields['id'],
                     'metademands_id'     => $this->fields['plugin_metademands_metademands_id'],
                     'previous_fields_id' => $this->fields['plugin_metademands_fields_id']];
      Ajax::updateItemOnSelectEvent('dropdown_rank' . $randRank, "show_order", $CFG_GLPI["root_doc"] .
                                                                               "/plugins/metademands/ajax/viewtypefields.php?id=" . $this->fields['id'], $paramsRank);
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      // TYPE
      echo "<td>" . __('Type') . "<span style='color:red'>&nbsp;*&nbsp;</span></td>";
      echo "<td>";
      $randType   = self::dropdownFieldTypes("type", ['value'          => $this->fields["type"],
                                                      'metademands_id' => $this->fields["plugin_metademands_metademands_id"]]);
      $paramsType = ['value'          => '__VALUE__',
                     'type'           => '__VALUE__',
                     'item'           => $this->fields['item'],
                     'task_link'      => $this->fields['plugin_metademands_tasks_id'],
                     'fields_link'    => $this->fields['fields_link'],
                     'max_upload'     => $this->fields['max_upload'],
                     'regex'          => $this->fields['regex'],
                     //                     'fields_display' => $this->fields['fields_display'],
                     'hidden_link'    => $this->fields['hidden_link'],
                     'custom_values'  => $this->fields['custom_values'],
                     'comment_values' => $this->fields['comment_values'],
                     'default_values' => $this->fields['default_values'],
                     'check_value'    => $this->fields['check_value'],
                     'metademands_id' => $this->fields["plugin_metademands_metademands_id"],
                     'change_type'    => 1];
      Ajax::updateItemOnSelectEvent('dropdown_type' . $randType, "show_values", $CFG_GLPI["root_doc"] .
                                                                                "/plugins/metademands/ajax/viewtypefields.php?id=" . $this->fields['id'], $paramsType);
      echo "</td>";

      // ORDER
      echo "<td>" . __('Display field after', 'metademands') . "</td>";
      echo "<td>";
      echo "<span id='show_order'>";
      $this->showOrderDropdown($this->fields['rank'],
                               $this->fields['id'],
                               $this->fields['plugin_metademands_fields_id'],
                               $this->fields["plugin_metademands_metademands_id"]);
      echo "</span>";
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      // ITEM
      //Display for dropdown list
      echo "<td>";
      echo "<span id='show_item_object' style='display:none'>";
      echo __('Object', 'metademands') . "<span style='color:red'>&nbsp;*&nbsp;</span>";
      echo "</span>";

      //Display to add a title
      echo "<span id='show_item_label_title' style='display:none'>";
      echo __('Color') . "<span style='color:red'>&nbsp;*&nbsp;</span>";
      echo "</span>";
      echo "</td>";
      echo "<td>";
      echo "<span id='show_item' style='display:none'>";
      $randItem = self::dropdownFieldItems("item", ['value' => $this->fields["item"]]);
      echo "</span>";

      echo "<span id='show_item_title' style='display:none'>";
      //      echo Html::script('/lib/jqueryplugins/spectrum-colorpicker/spectrum.js');
      //      echo Html::css('lib/jqueryplugins/spectrum-colorpicker/spectrum.min.css');
      //      Html::requireJs('colorpicker');
      $rand = mt_rand();
      Html::showColorField('color', ['value' => $this->fields["color"], 'rand' => $rand]);
      echo "</span>";

      $paramsItem = ['value'          => '__VALUE__',
                     'item'           => '__VALUE__',
                     'type'           => $this->fields['type'],
                     'task_link'      => $this->fields['plugin_metademands_tasks_id'],
                     'fields_link'    => $this->fields['fields_link'],
                     'max_upload'     => $this->fields['max_upload'],
                     'regex'          => $this->fields['regex'],
                     //                     'fields_display' => $this->fields['fields_display'],
                     'hidden_link'    => $this->fields['hidden_link'],
                     'metademands_id' => $this->fields["plugin_metademands_metademands_id"],
                     'custom_values'  => $this->fields["custom_values"],
                     'comment_values' => $this->fields["comment_values"],
                     'default_values' => $this->fields["default_values"],
                     'check_value'    => $this->fields['check_value']];
      Ajax::updateItemOnSelectEvent('dropdown_item' . $randItem, "show_values", $CFG_GLPI["root_doc"] .
                                                                                "/plugins/metademands/ajax/viewtypefields.php?id=" . $this->fields['id'], $paramsItem);
      echo "<input type='hidden' name='plugin_metademands_metademands_id' value='" . $this->fields["plugin_metademands_metademands_id"] . "'/>";
      $params = ['id'                 => 'dropdown_type' . $randType,
                 'to_change'          => 'dropdown_item' . $randItem,
                 'value'              => 'dropdown',
                 'current_item'       => $this->fields['item'],
                 'current_type'       => $this->fields['type'],
                 'titleDisplay'       => 'show_item_object',
                 'valueDisplay'       => 'show_item',
                 'titleDisplay_title' => 'show_item_label_title',
                 'valueDisplay_title' => 'show_item_title',
                 'value_title'        => 'title',
      ];

      $script = "var metademandWizard = $(document).metademandWizard(" . json_encode(['root_doc' => $CFG_GLPI['root_doc']]) . ");";
      $script .= "metademandWizard.metademands_show_field_onchange(" . json_encode($params) . ");";
      $script .= "metademandWizard.metademands_show_field(" . json_encode($params) . ");";
      echo Html::scriptBlock('$(document).ready(function() {' . $script . '});');

      echo "</td>";
      // Is_Basket Fields
      if ($metademand->fields['is_order'] == 1) {
         echo "<td>" . __('Display into the basket', 'metademands') . "</td>";
         echo "<td>";
         Dropdown::showYesNo("is_basket", $this->fields["is_basket"]);
         echo "</td>";
      } else {
         echo "<td colspan='2'></td>";
      }
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      // SHOW SPECIFIC VALUES
      echo "<td colspan='4'>";
      echo "<div id='show_values'>";
      if ($this->fields['type'] == 'dropdown') {
         $this->fields['type'] = $this->fields['item'];
      }
      $paramTypeField = ['value'          => $this->fields['type'],
                         'custom_values'  => $this->fields['custom_values'],
                         'comment_values' => $this->fields['comment_values'],
                         'default_values' => $this->fields['default_values'],
                         'task_link'      => $this->fields['plugin_metademands_tasks_id'],
                         'fields_link'    => $this->fields['fields_link'],
                         'max_upload'     => $this->fields['max_upload'],
                         'regex'          => $this->fields['regex'],
                         'hidden_link'    => $this->fields['hidden_link'],
                         //                         'fields_display' => $this->fields['fields_display'],
                         'item'           => $this->fields['item'],
                         'type'           => $this->fields['type'],
                         'check_value'    => $this->fields['check_value'],
                         'metademands_id' => $this->fields["plugin_metademands_metademands_id"]];

      $this->getEditValue(self::_unserialize($this->fields['custom_values']),
                          self::_unserialize($this->fields['comment_values']),
                          self::_unserialize($this->fields['default_values']),
                          $paramTypeField);
      $this->viewTypeField($paramTypeField);
      echo "</div>";
      echo "</td>";

      echo "</tr>";

      if ($ID > 0) {
         $this->showFormButtons(['colspan' => 2]);

      } else {
         if ($canedit) {
            echo "<tr class='tab_bg_1'>";
            echo "<td class='tab_bg_2 center' colspan='6'>";
            echo "<input type='hidden' class='submit' name='plugin_metademands_metademands_id' value='" . $item->fields['id'] . "'>";
            echo "<input type='submit' class='submit' name='add' value='" . _sx('button', 'Add') . "'>";
            echo "</td>";
            echo "</tr>";
         }

         echo "</table>";
         Html::closeForm();
         echo "</div>";

         // Show fields
         $this->listFields($item->fields['id'], $canedit);

         // Show wizard demo
         $wizard = new PluginMetademandsWizard();
         echo "<table class='tab_cadre_fixe'>";
         echo "<tr><th class='tab_bg_1'>" . PluginMetademandsWizard::getTypeName() . "</th></tr>";
         echo "<tr><td>";
         $options = ['step' => 2, 'metademands_id' => $item->getID(), 'preview' => true];
         $wizard->showWizard($options);
         echo "</td></tr>";
         echo "</table>";
      }
      return true;
   }

   /**
    * @param $plugin_metademands_metademands_id
    * @param $canedit
    *
    * @throws \GlpitestSQLError
    */
   private function listFields($plugin_metademands_metademands_id, $canedit) {
      $data = $this->find(['plugin_metademands_metademands_id' => $plugin_metademands_metademands_id],
                          ['rank', 'order']);
      $rand = mt_rand();

      if (count($data)) {
         echo "<div class='center first-bloc'>";
         if ($canedit) {
            Html::openMassiveActionsForm('mass' . __CLASS__ . $rand);
            $massiveactionparams = ['item' => __CLASS__, 'container' => 'mass' . __CLASS__ . $rand];
            Html::showMassiveActions($massiveactionparams);
         }
         echo "<table class='tab_cadre_fixe'>";
         echo "<tr class='tab_bg_2'>";
         echo "<th class='center b' colspan='10'>" . __('Form fields', 'metademands') . "</th>";
         echo "</tr>";
         echo "<tr class='tab_bg_2'>";
         echo "<th width='10'>";
         if ($canedit) {
            echo Html::getCheckAllAsCheckbox('mass' . __CLASS__ . $rand);
         }
         echo "</th>";
         echo "<th class='center b'>" . __('Label') . "</th>";
         echo "<th class='center b'>" . __('Type') . "</th>";
         echo "<th class='center b'>" . __('Object', 'metademands') . "</th>";
         echo "<th class='center b'>" . __('Mandatory field') . "</th>";
         echo "<th class='center b'>" . __('Link a task to the field', 'metademands') . "</th>";
         echo "<th class='center b'>" . __('Value to check', 'metademands') . "</th>";
         $meta = new PluginMetademandsMetademand();
         if ($meta->getFromDB($plugin_metademands_metademands_id) && $meta->fields['is_order'] == 1) {
            echo "<th class='center b'>" . __('Display into the basket', 'metademands') . "</th>";
         }
         echo "<th class='center b'>" . __('Block', 'metademands') . "</th>";
         echo "<th class='center b'>" . __('Order', 'metademands') . "</th>";
         echo "</tr>";
         // Init navigation list for field items
         Session::initNavigateListItems($this->getType(), self::getTypeName(1));

         foreach ($data as $id => $value) {
            Session::addToNavigateListItems($this->getType(), $id);
            echo "<tr class='tab_bg_1'>";
            echo "<td width='10'>";
            if ($canedit) {
               Html::showMassiveActionCheckBox(__CLASS__, $id);
            }
            echo "</td>";
            $name = $value['label'] . (!empty($value['label2']) ? '&nbsp;-&nbsp;' . $value['label2'] : '');
            echo "<td><a href='" . Toolbox::getItemTypeFormURL(__CLASS__) . "?id=" . $id . "'>";
            if (empty(trim($name))) {
               echo __('ID') . " - " . $id;
            } else {
               echo $name;
            }
            echo "</a></td>";
            echo "<td>" . self::getFieldTypesName($value['type']);
            //name of parent field
            if ($value['type'] == 'parent_field') {
               $field = new self();
               $field->getFromDB($value['parent_field_id']);
               if (empty(trim($field->fields['label']))) {
                  echo " ( ID - " . $value['parent_field_id'] . ")";
               } else {
                  echo " (" . $field->fields['label'] . ")";
               }
            }
            echo "</td>";
            echo "<td>" . self::getFieldItemsName($value['item']) . "</td>";
            echo "<td>" . Dropdown::getYesNo($value['is_mandatory']) . "</td>";
            echo "<td>";
            $name = Dropdown::getDropdownName('glpi_plugin_metademands_tasks', $value['plugin_metademands_tasks_id']);
            if ($name == '&nbsp;') {
               if (!is_array(self::_unserialize($value['check_value']))) {
                  if (!empty($value['check_value'])) {
                     $name = PluginMetademandsMetademandTask::getMetademandTaskName($value['plugin_metademands_tasks_id']);
                  } else {
                     $name = '-----';
                  }
               } else {
                  $name = __('Multiples', 'metademands');
               }
            }
            echo !empty($name) ? $name : Dropdown::EMPTY_VALUE;
            echo "</td>";
            echo "<td>";
            if (!empty($value['plugin_metademands_tasks_id'])) {
               if (is_array(self::_unserialize($value['check_value']))) {
                  echo __('Multiples', 'metademands');
               } else {
                  switch ($value['type']) {
                     case 'yesno':
                        echo Dropdown::getYesNo($value['check_value'] - 1);
                        break;
                     case 'dropdown':
                     case'checkbox':
                     case 'radio':
                        echo __('Not null value', 'metademands');
                        break;
                     default:
                        echo Dropdown::EMPTY_VALUE;
                        break;
                  }
               }
            } else {
               echo Dropdown::EMPTY_VALUE;
            }
            echo "</td>";
            if ($meta->fields['is_order'] == 1) {
               echo "<td>" . Dropdown::getYesNo($value['is_basket']) . "</td>";
            }

            echo "<td class='center' style='color:white;background-color: #" . self::setColor($value['rank']) . "'>" . $value['rank'] . "</td>";
            echo "<td class='center' style='color:white;background-color: #" . self::setColor($value['rank']) . "'>";
            echo empty($value['order']) ? __('None') : $value['order'];
            echo "</td>";
            echo "</tr>";
         }
         echo "</table>";

         if ($canedit && count($data)) {
            $massiveactionparams['ontop'] = false;
            Html::showMassiveActions($massiveactionparams);
            Html::closeForm();
         }
         echo "</div>";
      } else {
         echo "<div class='center first-bloc'>";
         echo "<table class='tab_cadre_fixe'>";
         echo "<tr  class='tab_bg_1'><td class='center'>" . __('No item to display') . "</td></tr>";
         echo "</table>";
         echo "</div>";
      }
   }

   /**
    * Show field types dropdown
    *
    * @param type  $name
    * @param array $param
    *
    * @return dropdown of types
    * @throws \GlpitestSQLError
    */
   static function dropdownFieldTypes($name, $param = []) {
      global $PLUGIN_HOOKS;

      $p = [];
      foreach ($param as $key => $val) {
         $p[$key] = $val;
      }

      $type_fields = self::$field_types;

      $plugin = new Plugin();

      if (isset($PLUGIN_HOOKS['metademands'])) {
         foreach ($PLUGIN_HOOKS['metademands'] as $plug => $method) {
            $new_fields = self::addPluginTextFieldItems($plug);
            if ($plugin->isActivated($plug) && is_array($new_fields)) {
               $type_fields = array_merge($type_fields, $new_fields);
            }
         }
      }

      foreach ($type_fields as $key => $types) {
         //delete type parent_field if no parent metademand & not field
         if ($types == 'parent_field') {
            $metademands_parent = PluginMetademandsMetademandTask::getAncestorOfMetademandTask($p['metademands_id']);
            $list_fields        = [];
            $field              = new self();
            foreach ($metademands_parent as $parent_id) {
               $condition    = ['plugin_metademands_metademands_id' => $parent_id,
                                ['NOT' => ['type' => ['parent_field', 'upload']]]];
               $datas_fields = $field->find($condition, ['rank', 'order']);
               foreach ($datas_fields as $data_field) {
                  $list_fields[$data_field['id']] = $data_field['label'];
               }
            }

            if (count($metademands_parent) == 0) {
               continue;
            } else if (count($list_fields) == 0) {
               continue;
            }
         }
         if (empty($types)) {
            $options[$key] = self::getFieldTypesName($types);
         } else {
            $options[$types] = self::getFieldTypesName($types);
         }
      }

      return Dropdown::showFromArray($name, $options, $p);
   }

   /**
    * get field types name
    *
    * @param string $value
    *
    * @return string types
    */
   static function getFieldTypesName($value = '') {
      global $PLUGIN_HOOKS;

      switch ($value) {
         case 'dropdown':
            return __('Dropdown', 'metademands');
         case 'dropdown_multiple':
            return __('Dropdown multiple', 'metademands');
         case 'text':
            return __('Text', 'metademands');
         case 'checkbox':
            return __('Checkbox', 'metademands');
         case 'textarea':
            return __('Textarea', 'metademands');
         case 'datetime':
            return __('Date', 'metademands');
         case 'datetime_interval':
            return __('Date interval', 'metademands');
         case 'yesno'   :
            return __('Yes / No', 'metademands');
         case 'upload'  :
            return __('Add a document');
         case 'title'   :
            return __('Add a title', 'metademands');
         case 'radio'   :
            return __('Radio button', 'metademands');
         case 'parent_field' :
            return __('Father\'s field', 'metademands');
         case 'link' :
            return __('Link');
         case 'number':
            return __('Number', 'metademands');
         case 'informations':
            return __('Informations', 'metademands');
         default:
            if (isset($PLUGIN_HOOKS['metademands'])) {
               $plugin = new Plugin();
               foreach ($PLUGIN_HOOKS['metademands'] as $plug => $method) {
                  $new_fields = self::getPluginFieldItemsName($plug);
                  if ($plugin->isActivated($plug)
                      && is_array($new_fields)) {
                     if (isset($new_fields[$value])) {
                        return $new_fields[$value];
                     } else {
                        continue;
                     }
                     return Dropdown::EMPTY_VALUE;
                  }
               }
            }
            return Dropdown::EMPTY_VALUE;
      }
   }


   /**
    * Load fields from plugins
    *
    * @param $plug
    */
   static function addPluginFieldItems($plug) {
      global $PLUGIN_HOOKS;

      $dbu = new DbUtils();
      if (isset($PLUGIN_HOOKS['metademands'][$plug])) {
         $pluginclasses = $PLUGIN_HOOKS['metademands'][$plug];

         foreach ($pluginclasses as $pluginclass) {
            if (!class_exists($pluginclass)) {
               continue;
            }
            $form[$pluginclass] = [];
            $item               = $dbu->getItemForItemtype($pluginclass);
            if ($item && is_callable([$item, 'addFieldItems'])) {
               return $item->addFieldItems();
            }
         }
      }
   }

   /**
    * Load fields from plugins
    *
    * @param $plug
    */
   static function addPluginDropdownFieldItems($plug) {
      global $PLUGIN_HOOKS;

      $dbu = new DbUtils();
      if (isset($PLUGIN_HOOKS['metademands'][$plug])) {
         $pluginclasses = $PLUGIN_HOOKS['metademands'][$plug];

         foreach ($pluginclasses as $pluginclass) {
            if (!class_exists($pluginclass)) {
               continue;
            }
            $form[$pluginclass] = [];
            $item               = $dbu->getItemForItemtype($pluginclass);
            if ($item && is_callable([$item, 'addDropdownFieldItems'])) {
               return $item->addDropdownFieldItems();
            }
         }
      }
   }

   /**
    * Load fields from plugins
    *
    * @param $plug
    */
   static function addPluginTextFieldItems($plug) {
      global $PLUGIN_HOOKS;

      $dbu = new DbUtils();
      if (isset($PLUGIN_HOOKS['metademands'][$plug])) {
         $pluginclasses = $PLUGIN_HOOKS['metademands'][$plug];

         foreach ($pluginclasses as $pluginclass) {
            if (!class_exists($pluginclass)) {
               continue;
            }
            $form[$pluginclass] = [];
            $item               = $dbu->getItemForItemtype($pluginclass);
            if ($item && is_callable([$item, 'addTextFieldItems'])) {
               return $item->addTextFieldItems();
            }
         }
      }
   }

   /**
    * Load fields from plugins
    *
    * @param $plug
    */
   static function getPluginFieldItemsName($plug) {
      global $PLUGIN_HOOKS;

      $dbu = new DbUtils();
      if (isset($PLUGIN_HOOKS['metademands'][$plug])) {
         $pluginclasses = $PLUGIN_HOOKS['metademands'][$plug];

         foreach ($pluginclasses as $pluginclass) {
            if (!class_exists($pluginclass)) {
               continue;
            }
            $form[$pluginclass] = [];
            $item               = $dbu->getItemForItemtype($pluginclass);
            if ($item && is_callable([$item, 'getFieldItemsName'])) {
               return $item->getFieldItemsName();
            }
         }
      }
   }


   /**
    * Show field item dropdown
    *
    * @param type  $name
    * @param array $param
    *
    * @return dropdown of items
    */
   static function dropdownFieldItems($name, $param = []) {
      global $PLUGIN_HOOKS;

      $p = [];
      foreach ($param as $key => $val) {
         $p[$key] = $val;
      }
      $config = new PluginMetademandsConfig();
      $data   = $config->getConfigFromDB();
      $plugin = new Plugin();

      $type_fields = self::$list_items;

      if (isset($PLUGIN_HOOKS['metademands'])) {
         foreach ($PLUGIN_HOOKS['metademands'] as $plug => $method) {
            $new_fields = self::addPluginDropdownFieldItems($plug);
            if ($plugin->isActivated($plug) && is_array($new_fields)) {
               $type_fields = array_merge($type_fields, $new_fields);
            }
         }
      }

      if ($data['enable_application_environment'] == 0) {
         if (($key = array_search('PluginMetademandsITILApplication', $type_fields)) !== false) {
            unset($type_fields[$key]);
         }
         if (($key = array_search('PluginMetademandsITILEnvironment', $type_fields)) !== false) {
            unset($type_fields[$key]);
         }
      }
      foreach ($type_fields as $key => $items) {
         if (empty($items)) {
            $options[$key] = self::getFieldItemsName($items);
         } elseif ($plugin->isActivated('ldapfields') && $items == 'PluginLdapfields') {
            $ldapfields_containers = new PluginLdapfieldsContainer();
            $ldapfields            = $ldapfields_containers->find(['type' => 'dropdown', 'is_active' => true]);
            if (count($ldapfields) > 0) {
               foreach ($ldapfields as $ldapfield) {
                  $ldapattribute = new PluginLdapfieldsAuthLDAP();
                  $ldapattribute->getFromDB($ldapfield['plugin_ldapfields_authldaps_id']);
                  $label                       = PluginLdapfieldsLabelTranslation::getLabelFor($ldapattribute);
                  $options[$ldapfield['name']] = $label;
               }
            }
         } else {
            $options[$items] = self::getFieldItemsName($items);
         }
      }
      return Dropdown::showFromArray($name, $options, $p);

   }

   /**
    * get field items name
    *
    * @param string $value
    *
    * @return string item
    */
   static function getFieldItemsName($value = '') {
      global $PLUGIN_HOOKS;

      switch ($value) {
         case 'user':
            return __('User');
         case 'usertitle':
            return __('User') . ' - ' . _x('person', 'Title');
         case 'usercategory':
            return __('User') . ' - ' . __('Category');
         case 'group':
            return __('Group');
         case 'location':
            return __('Location');
         case 'other':
            return __('Other');
         case 'itilcategory':
            return __('Category of the metademand', 'metademands');
         case 'PluginMetademandsITILApplication' :
            return PluginMetademandsITILApplication::getTypeName();
         case 'PluginMetademandsITILEnvironment' :
            return PluginMetademandsITILEnvironment::getTypeName();
         case 'appliance' :
            return __('Appliance');
         default:
            if (isset($PLUGIN_HOOKS['metademands'])) {
               $plugin = new Plugin();
               foreach ($PLUGIN_HOOKS['metademands'] as $plug => $method) {
                  $new_fields = self::getPluginFieldItemsName($plug);
                  if ($plugin->isActivated($plug)
                      && is_array($new_fields)) {
                     if (isset($new_fields[$value])) {
                        return $new_fields[$value];
                     } else {
                        continue;
                     }
                     return Dropdown::EMPTY_VALUE;
                  }
               }
            }
            return Dropdown::EMPTY_VALUE;
      }
   }


   /**
    * Load fields from plugins
    *
    * @param $plug
    */
   static function getPluginFieldItemsType($plug) {
      global $PLUGIN_HOOKS;

      $dbu = new DbUtils();
      if (isset($PLUGIN_HOOKS['metademands'][$plug])) {
         $pluginclasses = $PLUGIN_HOOKS['metademands'][$plug];

         foreach ($pluginclasses as $pluginclass) {
            if (!class_exists($pluginclass)) {
               continue;
            }
            $form[$pluginclass] = [];
            $item               = $dbu->getItemForItemtype($pluginclass);
            if ($item && is_callable([$item, 'getFieldItemsType'])) {
               return $item->getFieldItemsType();
            }
         }
      }
   }


   /**
    * @param        $data
    * @param        $metademands_data
    * @param bool   $preview
    * @param string $config_link
    * @param int    $itilcategories_id
    */
   static function getFieldType($data, $metademands_data, $preview = false, $config_link = "", $itilcategories_id = 0) {
      global $PLUGIN_HOOKS;

      $required = "";
      if ($data['is_mandatory'] && $data['type'] != 'parent_field') {
         $required = "required";
      }

      $upload = "";
      if ($data['type'] == "upload") {
         $max = "";
         if ($data["max_upload"] > 0) {
            $max = "( " . sprintf(__("Maximum number of documents : %s ", "metademands"), $data["max_upload"]) . ")";
         }

         $upload = "$max (" . Document::getMaxUploadSize() . ")";
      }
      if ($data['is_mandatory']) {
         $required = "style='color:red'";
      }

      echo "<label for='field[" . $data['id'] . "]' $required class='col-form-label col-form-label-sm'>";
      echo $data['label'] . " $upload";
      if ($preview) {
         echo $config_link;
      }
      echo "</label>";
      echo "<span class='metademands_wizard_red' id='metademands_wizard_red" . $data['id'] . "'>";
      if ($data['is_mandatory'] && $data['type'] != 'parent_field') {
         echo "*";
      }
      echo "</span>";

      echo "&nbsp;";

      $plugin = new Plugin();
      //use plugin fields types
      if (isset($PLUGIN_HOOKS['metademands'])) {
         foreach ($PLUGIN_HOOKS['metademands'] as $plug => $method) {
            $new_fields = self::getPluginFieldItemsType($plug);
            if ($plugin->isActivated($plug) && is_array($new_fields)) {
               if (in_array($data['type'], array_keys($new_fields))) {
                  $data['type'] = $new_fields[$data['type']];
               }
            }
         }
      }

      // Input
      echo "<br>";
      echo self::getFieldInput($metademands_data, $data, false, $itilcategories_id, 0);

   }


   /**
    * @param      $metademands_data
    * @param      $data
    * @param bool $on_basket
    * @param int  $itilcategories_id
    *
    * @param int  $idline
    *
    * @return int|mixed|String
    */
   static function getFieldInput($metademands_data, $data, $on_basket = false, $itilcategories_id = 0, $idline = 0) {

      $field = '';
      $value = '';
      if (isset($data['value'])) {
         $value = $data['value'];
      }

      if ($on_basket == false) {
         $namefield = 'field';
      } else {
         $namefield = 'field_basket_' . $idline;
      }

      switch ($data['type']) {
         case 'dropdown_multiple' :
            if (!empty($data['custom_values'])) {
               $data['custom_values'] = self::_unserialize($data['custom_values']);
               $defaults              = self::_unserialize($data['default_values']);
               $default_values        = [];
               if ($defaults) {
                  foreach ($defaults as $k => $v) {
                     if ($v != null) {
                        $default_values[] = $k;
                     }
                  }
               }
               ksort($data['custom_values']);
               $value = is_array($value) ? $value : $default_values;
               $field = Dropdown::showFromArray($namefield . "[" . $data['id'] . "]", $data['custom_values'],
                                                ['values'   => $value,
                                                 'width'    => '250px',
                                                 'multiple' => true,
                                                 'display'  => false
                                                ]);
            }
            break;
         case 'dropdown':
            switch ($data['item']) {
               case 'other' :
                  if (!empty($data['custom_values'])) {
                     $data['custom_values']    = self::_unserialize($data['custom_values']);
                     $data['custom_values'][0] = Dropdown::EMPTY_VALUE;
                     ksort($data['custom_values']);
                     $field = "";
                     $field .= Dropdown::showFromArray($namefield . "[" . $data['id'] . "]",
                                                       $data['custom_values'],
                                                       ['value'   => $value,
                                                        'width'   => '200px',
                                                        'display' => false
                                                       ]);
                  }
                  break;
               case 'user':
                  $userrand = mt_rand();
                  $field    = "";

                  $value = !empty($value) ? $value : 0;
                  $field .= User::dropdown(['name'    => $namefield . "[" . $data['id'] . "]",
                                            'entity'  => $_SESSION['glpiactiveentities'],
                                            'right'   => 'all',
                                            'rand'    => $userrand,
                                            'value'   => $value,
                                            'display' => false
                                           ]);
                  break;
               case 'itilcategory':
                  if ($on_basket == false) {
                     $nameitil = 'field';
                  } else {
                     $nameitil = 'basket';
                  }
                  $metademand = new PluginMetademandsMetademand();
                  $metademand->getFromDB($data['plugin_metademands_metademands_id']);
                  $values = json_decode($metademand->fields['itilcategories_id']);
                  if (count($values) == 1) {
                     foreach ($values as $key => $val)
                        $itilcategories_id = $val;
                  }
                  if ($itilcategories_id > 0) {
                     // itilcat from service catalog
                     $itilCategory = new ITILCategory();
                     $itilCategory->getFromDB($itilcategories_id);
                     $field = "<span>" . $itilCategory->getField('name');
                     $field .= "<input type='hidden' name='" . $nameitil . "_plugin_servicecatalog_itilcategories_id' value='" . $itilcategories_id . "' >";
                     $field .= "<span>";
                  } else {
                     $opt = ['name'      => $nameitil . "_plugin_servicecatalog_itilcategories_id",
                             'right'     => 'all',
                             'value'     => $value,
                             'condition' => ["id" => $values],
                             'display'   => false];

                     $field = "";
                     $field .= ITILCategory::dropdown($opt);
                  }
                  break;
               case 'usertitle':
                  $titlerand = mt_rand();
                  $field     = "";
                  $field     .= UserTitle::dropdown(['name'    => $namefield . "[" . $data['id'] . "]",
                                                     'rand'    => $titlerand,
                                                     'value'   => $value,
                                                     'display' => false]);
                  break;
               case 'usercategory':
                  $catrand = mt_rand();
                  $field   = "";
                  $field   .= UserCategory::dropdown(['name'    => $namefield . "[" . $data['id'] . "]",
                                                      'rand'    => $catrand,
                                                      'value'   => $value,
                                                      'display' => false]);
                  break;
               case 'PluginMetademandsITILApplication' :
                  $opt   = ['value'  => $value,
                            'entity' => $_SESSION['glpiactiveentities'],
                            'name'   => $namefield . "[" . $data['id'] . "],
                                        'display' => false"];
                  $field = "";
                  $field .= PluginMetademandsITILApplication::dropdown($opt);
                  break;
               case 'PluginMetademandsITILEnvironment' :
                  $opt   = ['value'  => $value,
                            'entity' => $_SESSION['glpiactiveentities'],
                            'name'   => $namefield . "[" . $data['id'] . "],
                                        'display' => false"];
                  $field = "";
                  $field .= PluginMetademandsITILEnvironment::dropdown($opt);
                  break;
               default:
                  $cond = [];
                  if (!empty($data['custom_values']) && $data['item'] == 'group') {
                     $options = self::_unserialize($data['custom_values']);
                     foreach ($options as $type_group => $val) {
                        $cond[$type_group] = $val;
                     }
                  }
                  $opt = ['value'     => $value,
                          'entity'    => $_SESSION['glpiactiveentities'],
                          'display'   => true,
                          'name'      => $namefield . "[" . $data['id'] . "]",
                          'readonly'  => true,
                          'condition' => $cond,
                          'display'   => false];
                  if (!($item = getItemForItemtype($data['item']))) {
                     break;
                  }
                  $container_class = new $data['item']();
                  $field           = "";
                  $field           .= $container_class::dropdown($opt);
                  break;
            }
            break;
         case 'text':
            $field = "<input type='text' name='" . $namefield . "[" . $data['id'] . "]' value='" . $value . "' class='form-control form-control-sm' id='" . $namefield . "[" . $data['id'] . "]' placeholder=\"" . $data['comment'] . "\">";
            break;
         case 'informations':
            if ($on_basket == false) {
               $field = nl2br($data['comment']);
            }
            break;
         case 'link':
            if (!empty($data['custom_values'])) {
               $data['custom_values'] = self::_unserialize($data['custom_values']);
               switch ($data['custom_values'][0]) {
                  case 'button' :
                     $btnLabel = __('Link');
                     if (!empty($data['label2'])) {
                        $btnLabel = $data['label2'];
                     }
                     $field = "<input type='submit' class='submit' value ='$btnLabel' target='_blank' onclick=\"window.open('" . $data['custom_values'][1] . "','_blank');return false\">";

                     break;
                  case 'link_a' :
                     $field = "<a target='_blank' href ='" . $data['custom_values'][1] . "'>" . $data['custom_values'][1] . "</a>";
                     break;
               }
               $field .= "<input type='hidden' name=" . $namefield . "[" . $data['id'] . "]' value='" . $data['custom_values'][1] . "' >";
            }
            //            echo "<input type='hidden' name=''.$namefield.'[" . $data['id'] . "]' value='" . $data['custom_values'] . "'>";

            break;
         case 'checkbox':
            if (!empty($data['custom_values'])) {
               $data['custom_values']  = self::_unserialize($data['custom_values']);
               $data['comment_values'] = self::_unserialize($data['comment_values']);
               $defaults               = self::_unserialize($data['default_values']);
               if (!empty($value)) {
                  $value = self::_unserialize($value);
               }
               $nbr    = 0;
               $inline = "";
               if ($data['row_display'] == 1) {
                  $inline = 'custom-control-inline';
               }
               $field = "";
               foreach ($data['custom_values'] as $key => $label) {
                  $field   .= "<div class='custom-control custom-checkbox $inline'>";
                  $checked = "";
                  if (isset($value[$key])) {
                     $checked = isset($value[$key]) ? 'checked' : '';
                  } elseif (isset($defaults[$key]) && $on_basket == false) {
                     $checked = ($defaults[$key] == 1) ? 'checked' : '';
                  }
                  $field .= "<input class='custom-control-input' type='checkbox' name='" . $namefield . "[" . $data['id'] . "][" . $key . "]' key='$key' id='" . $namefield . "[" . $data['id'] . "][" . $key . "]' value='$key' $checked>";
                  $nbr++;
                  $field .= "&nbsp;<label class='custom-control-label' for='" . $namefield . "[" . $data['id'] . "][" . $key . "]'>$label</label>";
                  if (isset($data['comment_values'][$key]) && !empty($data['comment_values'][$key])) {
                     $field .= "&nbsp;<span style='vertical-align: bottom;'>";
                     $field .= Html::showToolTip($data['comment_values'][$key],
                                                 ['awesome-class' => 'fa-info-circle',
                                                  'display'       => false]);
                     $field .= "</span>";
                  }
                  $field .= "</div>";
               }
            } else {
               $checked = $value ? 'checked' : '';
               $field   = "<input class='custom-control-input' type='checkbox' name='" . $namefield . "[" . $data['id'] . "]' value='checkbox' $checked>";
            }
            break;

         case 'radio':
            if (!empty($data['custom_values'])) {
               $data['custom_values']  = self::_unserialize($data['custom_values']);
               $data['comment_values'] = self::_unserialize($data['comment_values']);
               $defaults               = self::_unserialize($data['default_values']);
               if ($value != NULL) {
                  $value = self::_unserialize($value);
               }
               $nbr    = 0;
               $inline = "";
               if ($data['row_display'] == 1) {
                  $inline = 'custom-control-inline';
               }
               $field = "";
               foreach ($data['custom_values'] as $key => $label) {
                  $field .= "<div class='custom-control custom-radio $inline'>";

                  $checked = "";
                  if ($value != NULL && $value == $key) {
                     $checked = $value == $key ? 'checked' : '';
                  } elseif ($value == NULL && isset($defaults[$key]) && $on_basket == false) {
                     $checked = ($defaults[$key] == 1) ? 'checked' : '';
                  }
                  $field .= "<input class='custom-control-input' type='radio' name='" . $namefield . "[" . $data['id'] . "]' id='" . $namefield . "[" . $data['id'] . "][" . $key . "]' value='$key' $checked>";
                  $nbr++;
                  $field .= "&nbsp;<label class='custom-control-label' for='" . $namefield . "[" . $data['id'] . "][" . $key . "]'>$label</label>";
                  if (isset($data['comment_values'][$key]) && !empty($data['comment_values'][$key])) {
                     $field .= "&nbsp;<span style='vertical-align: bottom;'>";
                     $field .= Html::showToolTip($data['comment_values'][$key],
                                                 ['awesome-class' => 'fa-info-circle',
                                                  'display'       => false]);
                     $field .= "</span>";
                  }
                  $field .= "</div>";
               }
            }
            break;
         case 'textarea':
            $value = Html::cleanPostForTextArea($value);
            $field = "<textarea class='form-control' rows='3' placeholder=\"" . $data['comment'] . "\" name='" . $namefield . "[" . $data['id'] . "]' id='" . $namefield . "[" . $data['id'] . "]'>" . $value . "</textarea>";
            break;
         case 'datetime_interval':
         case 'datetime':
            $field = Html::showDateField($namefield . "[" . $data['id'] . "]", ['value'   => $value,
                                                                                'display' => false
            ]);
            break;
         case 'number':
            $data['custom_values'] = self::_unserialize($data['custom_values']);
            $field                 = Dropdown::showNumber($namefield . "[" . $data['id'] . "]", ['value'   => $value,
                                                                                                 'min'     => ((isset($data['custom_values']['min']) && $data['custom_values']['min'] != "") ? $data['custom_values']['min'] : 0),
                                                                                                 'max'     => ((isset($data['custom_values']['max']) && $data['custom_values']['max'] != "") ? $data['custom_values']['max'] : 360),
                                                                                                 'step'    => ((isset($data['custom_values']['step']) && $data['custom_values']['step'] != "") ? $data['custom_values']['step'] : 1),
                                                                                                 'display' => false
                                                                                                 //                                                   'toadd' => [0 => __('Infinite')]
            ]);
            break;
         case 'yesno':
            $option[1] = __('No');
            $option[2] = __('Yes');
            $field     = "";
            $field     .= Dropdown::showFromArray($namefield . "[" . $data['id'] . "]", $option, ['value'   => $value,
                                                                                                  'display' => false]);
            break;
         case 'upload':
            $arrayFiles = json_decode($value, true);
            $field      = "";
            $nb         = 0;
            if ($arrayFiles != "") {
               foreach ($arrayFiles as $k => $file) {
                  $field .= str_replace($file['_prefix_filename'], "", $file['_filename']);
                  $wiz   = new PluginMetademandsWizard();
                  $field .= "&nbsp;";
                  //own showSimpleForm for return (not echo)
                  $field .= self::showSimpleForm($wiz->getFormURL(), 'delete_basket_file',
                                                 _x('button', 'Delete permanently'),
                                                 ['id'                           => $k,
                                                  'metademands_id'               => $data['plugin_metademands_metademands_id'],
                                                  'plugin_metademands_fields_id' => $data['id'],
                                                  'idline'                       => $idline
                                                 ],
                                                 'fa-times-circle');
                  $field .= "<br>";
                  $nb++;
               }
               if ($data["max_upload"] > $nb) {
                  if ($data["max_upload"] > 1) {
                     $field .= Html::file(['filecontainer' => 'fileupload_info_ticket',
                                           'editor_id'     => '',
                                           'showtitle'     => false,
                                           'multiple'      => true,
                                           'display'       => false]);
                  } else {
                     $field .= Html::file(['filecontainer' => 'fileupload_info_ticket',
                                           'editor_id'     => '',
                                           'showtitle'     => false,
                                           'display'       => false
                                          ]);
                  }
               }
            } else {
               if ($data["max_upload"] > 1) {
                  $field .= Html::file(['filecontainer' => 'fileupload_info_ticket',
                                        'editor_id'     => '',
                                        'showtitle'     => false,
                                        'multiple'      => true,
                                        'display'       => false]);
               } else {
                  $field .= Html::file(['filecontainer' => 'fileupload_info_ticket',
                                        'editor_id'     => '',
                                        'showtitle'     => false,
                                        'display'       => false
                                       ]);
               }
            }

            $field .= "<input type='hidden' name='" . $namefield . "[" . $data['id'] . "]' value='$value'>";
            break;

         case 'parent_field':
            foreach ($metademands_data as $metademands_data_steps) {
               foreach ($metademands_data_steps as $line_data) {
                  foreach ($line_data['form'] as $field_id => $field_value) {
                     if ($field_id == $data['parent_field_id']) {

                        $value_parent_field = '';
                        if (isset($_SESSION['plugin_metademands']['fields'][$data['parent_field_id']])) {
                           $value_parent_field = $_SESSION['plugin_metademands']['fields'][$data['parent_field_id']];
                        }

                        switch ($field_value['type']) {
                           case 'dropdown_multiple':
                              if (!empty($field_value['custom_values'])) {
                                 $value_parent_field = $field_value['custom_values'][$value_parent_field];
                              }
                              break;
                           case 'dropdown':
                              if (!empty($field_value['custom_values'])
                                  && $field_value['item'] == 'other') {
                                 $value_parent_field = $field_value['custom_values'][$value_parent_field];
                              } else {
                                 switch ($field_value['item']) {
                                    case 'user':
                                       $value_parent_field = "<input type='hidden' name='" . $namefield . "[" . $data['id'] . "]' value='" . $value_parent_field . "'>";
                                       $user               = new User();
                                       $user->getFromDB($value_parent_field);
                                       $value_parent_field .= $user->getName();
                                       break;
                                    default:
                                       $dbu                = new DbUtils();
                                       $value_parent_field = "<input type='hidden' name='" . $namefield . "[" . $data['id'] . "]' value='" . $value_parent_field . "'>";
                                       $value_parent_field .= Dropdown::getDropdownName($dbu->getTableForItemType($field_value['item']),
                                                                                        $value_parent_field);
                                       break;
                                 }
                              }
                              break;
                           case 'checkbox':
                              if (!empty($field_value['custom_values'])) {
                                 $field_value['custom_values'] = self::_unserialize($field_value['custom_values']);
                                 $checkboxes                   = self::_unserialize($value_parent_field);

                                 $custom_checkbox    = [];
                                 $value_parent_field = "";
                                 foreach ($field_value['custom_values'] as $key => $label) {
                                    $checked = isset($checkboxes[$key]) ? 1 : 0;
                                    if ($checked) {
                                       $custom_checkbox[]  .= $label;
                                       $value_parent_field .= "<input type='hidden' name='" . $namefield . "[" . $data['id'] . "][" . $key . "]' value='checkbox'>";

                                    }
                                 }
                                 $value_parent_field .= implode('<br>', $custom_checkbox);
                              }
                              break;

                           case 'radio' :
                              if (!empty($field_value['custom_values'])) {
                                 $field_value['custom_values'] = self::_unserialize($field_value['custom_values']);
                                 foreach ($field_value['custom_values'] as $key => $label) {
                                    if ($value_parent_field == $key) {
                                       $value_parent_field = "<input type='hidden' name='" . $namefield . "[" . $data['id'] . "]' value='$key' >";
                                       $value_parent_field .= $label;
                                       break;
                                    }

                                 }
                              }
                              break;

                           case 'datetime':
                              $value_parent_field = "<input type='hidden' name='" . $namefield . "[" . $data['id'] . "]' value='" . $value_parent_field . "'>";
                              $value_parent_field .= Html::convDate($value_parent_field);

                              break;

                           case 'datetime_interval':
                              $value_parent_field = "<input type='hidden' name='" . $namefield . "[" . $data['id'] . "]' value='" . $value_parent_field . "'>";
                              if (isset($_SESSION['plugin_metademands']['fields'][$data['parent_field_id'] . "-2"])) {
                                 $value_parent_field2 = $_SESSION['plugin_metademands']['fields'][$data['parent_field_id'] . "-2"];
                                 $value_parent_field  .= "<input type='hidden' name='" . $namefield . "[" . $data['id'] . "-2]' value='" . $value_parent_field2 . "'>";
                              } else {
                                 $value_parent_field2 = 0;
                              }
                              $value_parent_field .= Html::convDate($value_parent_field) . " - " . Html::convDate($value_parent_field2);
                              break;
                           case 'yesno' :
                              $value_parent_field = "<input type='hidden' name='" . $namefield . "[" . $data['id'] . "]' value='" . $value_parent_field . "'>";
                              $value_parent_field .= Dropdown::getYesNo($value_parent_field);
                              break;

                           default :
                              $value_parent_field = "<input type='hidden' name='" . $namefield . "[" . $data['id'] . "]' value='" . $value_parent_field . "'>";

                        }
                        $field = $value_parent_field;
                        break;
                     }
                  }
               }
            }
            break;
      }
      if ($on_basket == false) {
         echo $field;
      } else {
         return $field;
      }
   }

   /**
    * @param        $action
    * @param        $btname
    * @param        $btlabel
    * @param array  $fields
    * @param string $btimage
    * @param string $btoption
    * @param string $confirm
    *
    * @return string
    */
   static function showSimpleForm($action, $btname, $btlabel, array $fields = [], $btimage = '',
                                  $btoption = '', $confirm = '') {

      return Html::getSimpleForm($action, $btname, $btlabel, $fields, $btimage, $btoption, $confirm);
   }

   /**
    * View options for items or types
    *
    * @param array $options
    *
    * @return void
    * @throws \GlpitestSQLError
    */
   function viewTypeField($options) {
      global $PLUGIN_HOOKS;

      $params['value']       = 0;
      $params['check_value'] = 0;


      foreach ($options as $key => $value) {
         $params[$key] = $value;
      }

      $allowed_types = ['yesno', 'datetime', 'datetime_interval', 'user', 'usertitle', 'usercategory', 'group',
                        'location', 'other', 'checkbox', 'radio', 'dropdown_multiple',
                        'parent_field', 'number', 'text', 'textarea', 'upload', 'itilcategory',
                        'PluginMetademandsITILApplication', 'PluginMetademandsITILEnvironment', 'appliance'];
      $new_fields    = [];

      $plugin = new Plugin();
      //      if ($plugin->isActivated('ldapfields')) {
      //         $ldapfields_containers = new PluginLdapfieldsContainer();
      //         $ldapfields            = $ldapfields_containers->find(['type' => 'dropdown', 'is_active' => true]);
      //         if (count($ldapfields) > 0) {
      //            foreach ($ldapfields as $ldapfield) {
      //               array_push($allowed_types, $ldapfield['name']);
      //            }
      //         }
      //      }

      if (isset($PLUGIN_HOOKS['metademands'])) {
         foreach ($PLUGIN_HOOKS['metademands'] as $plug => $method) {
            $new_fields = self::addPluginFieldItems($plug);
            if ($plugin->isActivated($plug) && is_array($new_fields)) {
               $allowed_types = array_merge($allowed_types, $new_fields);
            }
         }
      }

      if ((isset($params['check_value']) || $params['value'] == 'upload' || $params['value'] == 'text') && in_array($params['value'], $allowed_types)) {
         $metademands = new PluginMetademandsMetademand();
         $metademands->getFromDB($options['metademands_id']);
         if (in_array($params['value'], $new_fields)) {
            $params['value'] = $params['type'];
         }
         if (isset($params['value'])) {
            if (strpos($_SERVER['HTTP_REFERER'], 'field.form.php') > 0) {
               echo "<div id='show_type_fields'>";
               echo "<table width='100%' class='metademands_show_values'>";
               echo "<tr><th colspan='2'>" . __('Options', 'metademands') . "</th></tr>";
               echo "<i class='fa fa-plus' id='addNewOpt' ></i>";
               echo "</th></tr></thead><tbody>";

               //               echo "<tr>";
               $nb  = 0;
               $url = 'field.form.php?id=' . $_GET['id'];
               // Multi criterias

               if (strpos($_SERVER['HTTP_REFERER'], 'nbOpt=') > 0) {
                  $nb = substr($_SERVER['HTTP_REFERER'], strpos($_SERVER['HTTP_REFERER'], 'nbOpt=') + 6);
               } else if (is_array(self::_unserialize($this->getField('check_value')))) {
                  foreach (self::_unserialize($this->getField('check_value')) as $elem) {
                     $nb++;
                  }
               }

               if ($params["value"] == 'upload') {

                  echo "<tr><td>";
                  echo "<table class='metademands_show_custom_fields' style='border-bottom: 1px dashed black'>";
                  echo "<tr><td>";
                  echo __('Number of documents allowed', 'metademands');
                  //               echo '</br><span class="metademands_wizard_comments">' . __('If the selected field is filled, this field will be displayed', 'metademands') . '</span>';
                  echo '</td>';
                  echo "<td>";
                  $data[0] = Dropdown::EMPTY_VALUE;
                  for ($i = 1; $i <= 50; $i++) {
                     $data[$i] = $i;
                  }


                  echo Dropdown::showFromArray("max_upload", $data, array('value' => $params['max_upload'], 'display' => false));
                  //            self::showFieldsDropdown("fields_display", $metademands->fields["id"], $params['fields_display']);
                  //            $html .= $this->showLinkHtml($metademands->fields["id"], $params, $nbOpt, 0,0,0);
                  echo "</td></tr>";
                  echo "</table>";
                  echo "</td></tr>";
               }
               if ($params["value"] == 'text') {

                  echo "<tr><td>";
                  echo "<table class='metademands_show_custom_fields' style='border-bottom: 1px dashed black'>";
                  echo "<tr><td>";
                  echo __('Regex', 'metademands');
                  //               echo '</br><span class="metademands_wizard_comments">' . __('If the selected field is filled, this field will be displayed', 'metademands') . '</span>';
                  echo '</td>';
                  echo "<td>";


                  //                  echo Dropdown::showFromArray("max_upload", $data, array('value' => $params['max_upload'], 'display' => false));
                  echo '<input type="text" name="regex"  value="' . ($params["regex"]) . '" size="50"/>';
                  //            self::showFieldsDropdown("fields_display", $metademands->fields["id"], $params['fields_display']);
                  //            $html .= $this->showLinkHtml($metademands->fields["id"], $params, $nbOpt, 0,0,0);
                  echo "</td></tr>";
                  echo "</table>";
                  echo "</td></tr>";
               }
               if ($nb == 0) {
                  echo $this->addNewOpt($url);
               } else {
                  echo "<tr><td>";
                  for ($i = 0; $i < $nb; $i++) {
                     echo "<table class='metademands_show_custom_fields' style='border-bottom: 1px dashed black'>";
                     echo $this->showOptions($metademands->getField('id'), $params, $i);
                     echo "</table>";
                     echo $this->addNewOpt($url);
                  }
                  echo "</td></tr>";
               }

               echo "<input type='hidden' id='nbOptions' value='" . $nb . "' />";
               echo "</tbody></table>";
               echo "</div>";
            }
         }
      }
   }

   /**
    * @param $url
    */
   function addNewOpt($url) {
      global $CFG_GLPI;
      $res = "<script type='text/javascript'>

      var rootDoc = '" . $CFG_GLPI['root_doc'] . "';
                $('#addNewOpt').click(function(){
                    var nb = document.getElementById('nbOptions').valueOf().value;
                    nb++;
                    parent.parent.window.location.replace(rootDoc + '/plugins/metademands/front/" . $url . "&nbOpt='+nb);
                });
                </script>";
      echo $res;
   }

   /**
    * @param $metademands_id
    * @param $params
    * @param $nbOpt
    *
    * @return string
    * @throws \GlpitestSQLError
    */
   function showOptions($metademands_id, $params, $nbOpt) {
      $metademands = new PluginMetademandsMetademand();
      $metademands->getFromDB($metademands_id);

      $display = false;
      $html    = "";

      $params['check_value'] = self::_unserialize($params['check_value']);
      if (!isset($params['check_value'][$nbOpt])) {
         $params['check_value'] = "";
      } else {
         $params['check_value'] = $params['check_value'][$nbOpt];
      }

      $params['task_link'] = self::_unserialize($params['task_link']);
      if (!isset($params['task_link'][$nbOpt])) {
         $params['task_link'] = "";
      } else {
         $params['task_link'] = $params['task_link'][$nbOpt];
      }

      $params['fields_link'] = self::_unserialize($params['fields_link']);
      if (!isset($params['fields_link'][$nbOpt])) {
         $params['fields_link'] = "";
      } else {
         $params['fields_link'] = $params['fields_link'][$nbOpt];
      }
      $params['hidden_link'] = self::_unserialize($params['hidden_link']);
      if (!isset($params['hidden_link'][$nbOpt])) {
         $params['hidden_link'] = "";
      } else {
         $params['hidden_link'] = $params['hidden_link'][$nbOpt];
      }


      switch ($params['value']) {
         case 'yesno':
            $data[1] = __('No');
            $data[2] = __('Yes');
            // Value to check
            $html .= "<tr><td>";
            $html .= __('Value to check', 'metademands') . '</td><td>';
            $html .= Dropdown::showFromArray("check_value[]", $data, array('value' => $params['check_value'], 'display' => $display));
            //            $html .= Dropdown::showYesNo("check_value[]", $params['check_value'],-1,['display' => $display]);
            $html .= "</td>";
            $html .= "</tr><td>";

            $html .= $this->showLinkHtml($metademands->fields["id"], $params, 1, 1, 1);

            //            // Show field link
            //            $html .= "<tr><td>";
            //            $html .= __('Link a field to the field', 'metademands');
            //            $html .= '</br><span class="metademands_wizard_comments">'.__('If the value selected equals the value to check, the field becomes mandatory', 'metademands').'</span>';
            //            $html .= '</td>';
            //            $html .= "<td>";
            //            $html .= self::showFieldsDropdown($metademands->fields["id"], $params['fields_link'],false);
            //            $html .= "</td></tr>";
            break;
         case 'datetime' :
         case 'datetime_interval' :
            $html .= "<tr><td>";
            $html .= __('Day greater or equal to now', 'metademands');
            $html .= "</td><td>";

            $checked = '';
            if (isset($params['check_value']) && !empty($params['check_value'])) {
               $checked = 'checked';
            }
            $html .= "<input type='checkbox' name='check_value' value='1' $checked>";
            $html .= "</td></tr>";
            break;
         case 'user':
         case 'usertitle':
         case 'usercategory':
         case 'group':
         case 'location':
         case 'PluginResourcesResource':
         case 'PluginMetademandsITILApplication':
         case 'PluginMetademandsITILEnvironment':
            // Value to check
            $html .= "<tr><td>";
            $html .= __('Value to check', 'metademands');
            $html .= " ( " . Dropdown::EMPTY_VALUE . " = " . __('Not null value', 'metademands') . ")";
            $html .= '</td>';
            $html .= '<td>';
            if (class_exists($params['value'])) {
               //               if($params['value'] == 'group' || $params['value'] == 'usertitle'|| $params['value'] == 'usercategory'){
               //                  $name = "check_value";// TODO : HS POUR LES GROUPES CAR rajout un RAND dans le dropdownname
               //               } else{
               $name = "check_value[]";
               //               }
               $html .= $params['value']::Dropdown(["name"    => $name,
                                                    "value"   => $params['check_value'],
                                                    "display" => $display,
                                                    "addicon" => false]);
            } else {
               $elements[0] = Dropdown::EMPTY_VALUE;
               if (is_array(json_decode($params['custom_values'], true))) {
                  $elements += json_decode($params['custom_values'], true);
                  //                  $elements = html_entity_decode();
               }
               foreach ($elements as $key => $val) {
                  $elements[$key] = urldecode($val);
               }
               $html .= Dropdown::showFromArray("check_value[]",
                                                $elements,
                                                ['value'   => $params['check_value'],
                                                 'display' => $display]);
            }

            $html .= "</td>";
            $html .= "</tr>";

            $html .= $this->showLinkHtml($metademands->fields["id"], $params, 1, 1, 1);

            break;
         case 'other':
         case 'dropdown':
         case 'dropdown_multiple':
            $html .= "<tr><td>";
            $html .= __('Value to check', 'metademands');
            $html .= " ( " . Dropdown::EMPTY_VALUE . " = " . __('Not null value', 'metademands') . ")";
            $html .= '</td>';
            $html .= '<td>';
            if (class_exists($params['value'])) {
               if ($params['value'] == 'group') {
                  $name = "check_value";// TODO : HS POUR LES GROUPES CAR rajout un RAND dans le dropdownname
               } else {
                  $name = "check_value[]";
               }
               $html .= $params['value']::Dropdown(["name" => $name,
                                                    "value" => $params['check_value'],
                                                    "display" => $display]);
            } else {
               $elements[0] = Dropdown::EMPTY_VALUE;
               if (is_array(json_decode($params['custom_values'], true))) {
                  $elements += json_decode($params['custom_values'], true);
               }
               foreach ($elements as $key => $val) {
                  $elements[$key] = urldecode($val);
               }
               $html .= Dropdown::showFromArray("check_value[]",
                                                $elements,
                                                ['value'   => $params['check_value'],
                                                 'display' => $display]);
            }

            $html .= "</td>";
            $html .= "</tr>";

            $html .= $this->showLinkHtml($metademands->fields["id"], $params, 1, 1, 1);

            break;
         case 'checkbox':
         case 'radio':
            // Value to check
            $html         .= "<tr><td>";
            $html         .= __('Value to check', 'metademands');
            $html         .= " ( " . Dropdown::EMPTY_VALUE . " = " . __('Not null value', 'metademands') . ")" . '</td>';
            $html         .= '<td>';
            $elements[-1] = Dropdown::EMPTY_VALUE;
            if (is_array(json_decode($params['custom_values'], true))) {
               $elements += json_decode($params['custom_values'], true);
            }
            foreach ($elements as $key => $val) {
               $elements[$key] = urldecode($val);
            }
            $html .= Dropdown::showFromArray("check_value[]",
                                             $elements,
                                             ['value'   => $params['check_value'],
                                              'display' => $display]);

            $html .= "</td>";
            $html .= "</tr><td>";

            $html .= $this->showLinkHtml($metademands->fields["id"], $params, 1, 0, 1);

            break;
         case 'parent_field':
            $html .= "<tr><td>";
            $html .= __('Field') . '</td>';
            $html .= '<td>';
            //list of fields
            $fields            = array();
            $metademand_parent = new PluginMetademandsMetademand();

            // list of parents
            $metademands_parent = PluginMetademandsMetademandTask::getAncestorOfMetademandTask($metademands->fields["id"]);

            foreach ($metademands_parent as $parent_id) {
               if ($metademand_parent->getFromDB($parent_id)) {
                  $name_metademand = $metademand_parent->getName();

                  $condition    = ['plugin_metademands_metademands_id' => $parent_id,
                                   ['NOT' => ['type' => ['parent_field', 'upload']]]];
                  $datas_fields = $this->find($condition, ['rank', 'order']);
                  //formatting the name to display (Name of metademand - Father's Field Label - type)
                  foreach ($datas_fields as $data_field) {
                     $fields[$data_field['id']] = $name_metademand . " - " . $data_field['label'] . " - " . self::getFieldTypesName($data_field['type']);
                  }
               }
            }
            $html .= Dropdown::showFromArray('parent_field_id[]', $fields, ['display' => $display]);
            $html .= "</td></tr>";
            break;
         case 'text':
         case 'textarea':
            $data[1] = __('No');
            $data[2] = __('Yes');
            // Value to check
            $html .= "<tr><td>";
            $html .= __('If field empty', 'metademands') . '</td><td>';
            $html .= Dropdown::showFromArray("check_value[]", $data, array('value' => $params['check_value'], 'display' => $display));
            //            $html .= Dropdown::showYesNo("check_value[]", $params['check_value'],-1,['display' => $display]);
            $html .= "</td>";
            $html .= "</tr><td>";

            $html .= $this->showLinkHtml($metademands->fields["id"], $params, 1, 0, 1);


            break;

         case 'upload':
            // Show field display
            //            if($nbOpt == 0){
            //               $html .= "<tr><td>";
            //               $html .= __('Multiple document ', 'metademands');
            ////               echo '</br><span class="metademands_wizard_comments">' . __('If the selected field is filled, this field will be displayed', 'metademands') . '</span>';
            //               $html .= '</td>';
            //               $html .= "<td>";
            //               $data[0] = Dropdown::EMPTY_VALUE;
            //               for($i =1;$i<=50;$i++){
            //                  $data[$i] = $i;
            //               }
            //
            //
            //               $html .= Dropdown::showFromArray("max_upload", $data, array('value' => $params['max_upload'], 'display' => $display));
            //               //            self::showFieldsDropdown("fields_display", $metademands->fields["id"], $params['fields_display']);
            //               //            $html .= $this->showLinkHtml($metademands->fields["id"], $params,  0,0,0);
            //               $html .= "</td></tr>";
            //            }

            break;
      }

      return $html;
   }

   /**
    * @param     $metademands_id
    * @param     $params
    *
    * @param int $task
    * @param int $field
    * @param int $hidden
    *
    * @return string
    * @throws \GlpitestSQLError
    */
   function showLinkHtml($metademands_id, $params, $task = 1, $field = 1, $hidden = 0) {

      $res = "";

      // Show task link
      if ($task) {
         $res = '<tr><td>';
         $res .= __('Link a task to the field', 'metademands');
         $res .= '</br><span class="metademands_wizard_comments">' . __('If the value selected equals the value to check, the task is created', 'metademands') . '</span>';
         $res .= '</td><td>';
         $res .= PluginMetademandsTask::showAllTasksDropdown($metademands_id, $params['task_link'], false);
         $res .= "</td></tr>";
      }

      // Show field link
      if ($field) {
         $res .= "<tr><td>";
         $res .= __('Link a field to the field', 'metademands');
         $res .= '</br><span class="metademands_wizard_comments">' . __('If the value selected equals the value to check, the field becomes mandatory', 'metademands') . '</span>';
         $res .= '</td>';
         $res .= "<td>";
         $res .= self::showFieldsDropdown($metademands_id, $params['fields_link'], $this->getID(), false);
         $res .= "</td></tr>";
      }
      if ($hidden) {
         $res .= "<tr><td>";
         $res .= __('Link a hidden field', 'metademands');
         $res .= '</br><span class="metademands_wizard_comments">' . __('If the value selected equals the value to check, the field becomes visible', 'metademands') . '</span>';
         $res .= '</td>';
         $res .= "<td>";
         $res .= self::showHiddenDropdown($metademands_id, $params['hidden_link'], $this->getID(), false);
         $res .= "</td></tr>";
      }

      return $res;
   }


   /**
    * @param      $metademands_id
    * @param      $selected_value
    * @param      $idF
    * @param bool $display
    *
    * @return int|string
    */
   static function showFieldsDropdown($metademands_id, $selected_value, $idF, $display = true) {

      $fields      = new self();
      $fields_data = $fields->find(['plugin_metademands_metademands_id' => $metademands_id]);
      $data        = [Dropdown::EMPTY_VALUE];
      foreach ($fields_data as $id => $value) {
         if ($value['item'] != "itilcategory"
             && $value['item'] != "informations"
             && $idF != $id) {
            $data[$id] = urldecode(html_entity_decode($value['label']));
            //            if (!empty($value['label2'])) {
            //               $data[$id] .= ' - ' . $value['label2'];
            //            }
         }
      }

      return Dropdown::showFromArray('fields_link[]', $data, ['value' => $selected_value, 'display' => $display]);
   }

   /**
    * @param      $metademands_id
    * @param      $selected_value
    * @param bool $display
    * @param      $idF
    *
    * @return int|string
    */
   static function showHiddenDropdown($metademands_id, $selected_value, $idF, $display = true) {


      $fields      = new self();
      $fields_data = $fields->find(['plugin_metademands_metademands_id' => $metademands_id]);
      $data        = [Dropdown::EMPTY_VALUE];
      foreach ($fields_data as $id => $value) {
         if ($value['item'] != "itilcategory"
             && $value['item'] != "informations"
             && $idF != $id) {
            $data[$id] = urldecode(html_entity_decode($value['label']));
            //            if (!empty($value['label2'])) {
            //               $data[$id] .= ' - ' . urldecode(html_entity_decode($value['label2']));
            //            }
         }

      }

      return Dropdown::showFromArray('hidden_link[]', $data, ['value' => $selected_value, 'display' => $display]);
   }


   /**
    * View custom values for items or types
    *
    * @param array $values
    * @param array $comment
    * @param array $default
    * @param array $options
    *
    * @return void
    */
   function getEditValue($values = [], $comment = [], $default = [], $options = []) {

      $params['value'] = 0;
      $params['item']  = '';
      $params['type']  = '';

      foreach ($options as $key => $value) {
         $params[$key] = $value;
      }

      $allowed_types = ['other', 'checkbox', 'yesno', 'radio', 'link', 'dropdown_multiple'];

      if (in_array($params['value'], $allowed_types)) {
         echo "<table width='100%' class='metademands_show_values'>";
         echo "<tr><th colspan='4'>" . __('Custom values', 'metademands') . "</th></tr>";
         echo "<tr><td>";
         echo '<table width=\'100%\' class="tab_cadre">';

         switch ($params['value']) {
            case 'other':
            case 'dropdown_multiple':
            case 'dropdown':
               echo "<tr>";
               if (is_array($values) && !empty($values)) {
                  foreach ($values as $key => $value) {
                     echo "<tr>";

                     echo "<td>";
                     echo "<p id='custom_values$key'>";
                     echo __('Value') . " " . $key . " ";
                     echo '<input type="text" name="custom_values[' . $key . ']"  value="' . $value . '" size="30"/>';
                     echo '</p>';
                     echo "</td>";

                     echo "<td>";
                     echo "<p id='default_values$key'>";
                     $display_default = false;
                     if ($params['value'] == 'dropdown_multiple') {
                        $display_default = true;
                        echo " " . _n('Default value', 'Default values', 1, 'metademands') . " ";
                        $checked = "";
                        if (isset($default[$key])
                            && $default[$key] == 1) {
                           $checked = "checked";
                        }
                        echo "<input type='checkbox' name='default_values[" . $key . "]'  value='1' $checked />";
                     }
                     echo '</p>';
                     echo "</td>";

                     echo "</tr>";
                  }
                  echo "<tr>";
                  echo "<td colspan='4' align='right' id='show_custom_fields'>";
                  self::initCustomValue(max(array_keys($values)), false, $display_default);
                  echo "</td>";
                  echo "</tr>";
               } else {
                  echo "<tr>";

                  echo "<td>";
                  echo __('Value') . " 1 ";
                  echo '<input type="text" name="custom_values[1]"  value="" size="30"/>';
                  echo "</td>";
                  echo "<td>";
                  $display_default = false;
                  if ($params['value'] == 'dropdown_multiple') {
                     $display_default = true;
                     echo " " . _n('Default value', 'Default values', 1, 'metademands') . " ";
                     echo '<input type="checkbox" name="default_values[1]"  value="1"/>';
                     echo "</td>";
                  }
                  echo "</tr>";

                  echo "<tr>";
                  echo "<td colspan='2' align='right' id='show_custom_fields'>";
                  self::initCustomValue(1, false, $display_default);
                  echo "</td>";
                  echo "</tr>";
               }
               break;
            case 'checkbox':
            case 'radio':
               if (is_array($values) && !empty($values)) {
                  foreach ($values as $key => $value) {
                     echo "<tr>";

                     echo "<td>";
                     echo "<p id='custom_values$key'>";
                     echo __('Value') . " " . $key . " ";
                     echo '<input type="text" name="custom_values[' . $key . ']"  value="' . $value . '" size="30"/>';
                     echo '</p>';
                     echo "</td>";

                     echo "<td>";
                     echo "<p id='comment_values$key'>";
                     if ($params['value'] == 'checkbox' || $params['value'] == 'radio') {
                        echo " " . __('Comment') . " ";
                        $value_comment = "";
                        if (isset($comment[$key])) {
                           $value_comment = $comment[$key];
                        }
                        echo '<input type="text" name="comment_values[' . $key . ']"  value="' . $value_comment . '" size="30"/>';
                     }
                     echo '</p>';
                     echo "</td>";

                     echo "<td>";
                     echo "<p id='default_values$key'>";
                     echo " " . _n('Default value', 'Default values', 1, 'metademands') . " ";
                     $name  = "default_values[" . $key . "]";
                     $value = (isset($default[$key]) ? $default[$key] : 0);
                     Dropdown::showYesNo($name, $value);
                     echo '</p>';
                     echo "</td>";

                     echo "</tr>";
                  }
                  echo "<tr>";
                  echo "<td colspan='4' align='right' id='show_custom_fields'>";
                  self::initCustomValue(max(array_keys($values)), true, true);
                  echo "</td>";
                  echo "</tr>";
               } else {
                  echo "<tr>";

                  echo "<td>";
                  echo __('Value') . " 0 ";
                  echo '<input type="text" name="custom_values[0]"  value="" size="30"/>';
                  echo "</td>";
                  echo "<td>";
                  echo " " . __('Comment') . " ";
                  echo '<input type="text" name="comment_values[0]"  value="" size="30"/>';
                  echo "</td>";
                  echo "<td>";
                  echo " " . _n('Default value', 'Default values', 1, 'metademands') . " ";
                  echo '<input type="checkbox" name="default_values[1]"  value="1"/>';

                  echo "</td>";
                  echo "</tr>";

                  echo "<tr>";
                  echo "<td colspan='3' align='right'  id='show_custom_fields'>";
                  self::initCustomValue(0, true, true);
                  echo "</td>";
                  echo "</tr>";
               }
               echo "</tr>";
               break;
            case 'yesno': // Show yes/no default value
               echo "<tr><td id='show_custom_fields'>";
               echo _n('Default value', 'Default values', 1, 'metademands') . "&nbsp;";
               if (isset($params['custom_values'])) {
                  $p['value'] = $params['custom_values'];
               }
               $data[1] = __('No');
               $data[2] = __('Yes');

               Dropdown::showFromArray("custom_values", $data, $p);
               echo "</td></tr>";
               break;
            case 'link': // Show yes/no default value
               echo "<tr><td id='show_custom_fields'>";
               $linkType = 0;
               $linkVal  = '';
               if (isset($params['custom_values']) && !empty($params['custom_values'])) {
                  $params['custom_values'] = self::_unserialize($params['custom_values']);
                  $linkType                = $params['custom_values'][0];
                  $linkVal                 = $params['custom_values'][1];
               }
               echo '<label>' . __("Link") . '</label>';
               echo '<input type="text" name="custom_values[1]" value="' . $linkVal . '" size="30"/>';

               echo "</td>";
               echo "<td>";

               echo '<label>' . __("Button Type", "metademands") . '</label>';
               Dropdown::showFromArray("custom_values[0]",
                                       [
                                          'button' => __('button'),
                                          'link_a' => __('Web link')
                                       ],
                                       ['value' => $linkType]);
               echo "<br /><i>" . __("*use field \"Additional label\" for the button title", "metademands") . "</i>";
               echo "</td></tr>";
               break;
         }

         echo '</td></tr></table>';
         echo "</td></tr></table>";
      }
   }

   /**
    * @param      $count
    * @param bool $display_comment
    */
   /**
    * @param      $count
    * @param bool $display_comment
    * @param bool $display_default
    */
   static function initCustomValue($count, $display_comment = false, $display_default = false) {
      global $CFG_GLPI;

      Html::requireJs("metademands");
      $script = "var metademandWizard = $(document).metademandWizard(" . json_encode(['root_doc' => $CFG_GLPI['root_doc']]) . ");";

      echo "<input type='hidden' id='display_comment' value='$display_comment' />";
      echo '<input type="hidden" id="count_custom_values" value="' . $count . '"/>';
      echo "<input type='hidden' id='display_default' value='$display_default' />";

      echo "&nbsp;<i class='fa-2x fas fa-plus-square' style='cursor:pointer' 
            onclick='$script metademandWizard.metademands_add_custom_values(\"show_custom_fields\");' 
            title='" . _sx("button", "Add") . "'/></i>&nbsp;";

      echo "&nbsp;<i class='fa-2x fas fa-trash-alt' style='cursor:pointer'
            onclick='$script metademandWizard.metademands_delete_custom_values(\"custom_values\");'
            title='" . _sx('button', 'Delete permanently') . "'/></i>";

   }

   /**
    * @param $valueId
    * @param $display_comment
    * @param $display_default
    */
   static function addNewValue($valueId, $display_comment, $display_default) {

      echo '<table width=\'100%\' class="tab_cadre">';
      echo "<tr>";

      echo "<td id='show_custom_fields'>";
      echo '<p id=\'custom_values' . $valueId . '\'>';
      echo __('Value') . ' ' . $valueId . ' ';
      echo '<input type="text" name="custom_values[' . $valueId . ']"value="" size="30"/>';
      echo "</td>";
      echo '</p>';

      echo "<td id='show_custom_fields'>";
      echo '<p id=\'comment_values' . $valueId . '\'>';
      if ($display_comment) {
         echo " " . __('Comment') . " ";
         echo '<input type="text" name="comment_values[' . $valueId . ']"  value="" size="30"/>';
      }
      echo '</p>';
      echo "</td>";

      echo "<td id='show_custom_fields'>";
      echo '<p id=\'default_values' . $valueId . '\'>';
      if ($display_default) {
         echo " " . _n('Default value', 'Default values', 1, 'metademands') . " ";
         echo '<input type="checkbox" name="default_values[' . $valueId . ']"  value="1"/>';
      }
      echo '</p>';
      echo "</td>";

      echo "</tr>";
      echo '</td></tr></table>';
   }

   /**
    * @param $input
    *
    * @return string
    */
   static function _serialize($input) {
      if ($input != null) {
         foreach ($input as &$value) {
            $value = urlencode(Html::cleanPostForTextArea($value));
         }

         return json_encode($input);
      }
   }

   /**
    * @param $input
    *
    * @return mixed
    */
   static function _unserialize($input) {
      if (!empty($input)) {
         if (!is_array($input)) {
            $input = json_decode($input, true);
         }
         if (is_array($input)) {
            foreach ($input as &$value) {
               $value = urldecode($value);
            }
         }
      }

      return $input;
   }

   /**
    * Type that could be linked to a metademand
    *
    * @param $all boolean, all type, or only allowed ones
    *
    * @return array of types
    * */
   static function getTypes($all = false) {

      if ($all) {
         return self::$types;
      }

      // Only allowed types
      $types = self::$types;
      $dbu   = new DbUtils();
      foreach ($types as $key => $type) {
         if (!($item = $dbu->getItemForItemtype($type))) {
            continue;
         }

         if (!$item->canView()) {
            unset($types[$key]);
         }
      }
      return $types;
   }

   /**
    * @param $params
    * @param $protocol
    *
    * @return array
    */
   static function methodListMetademandsfields($params, $protocol) {

      if (isset ($params['help'])) {
         return ['help'           => 'bool,optional',
                 'metademands_id' => 'bool,mandatory'];
      }

      if (!Session::getLoginUserID()) {
         return PluginWebservicesMethodCommon::Error($protocol, WEBSERVICES_ERROR_NOTAUTHENTICATED);
      }

      if (!isset($params['metademands_id'])) {
         return PluginWebservicesMethodCommon::Error($protocol, WEBSERVICES_ERROR_MISSINGPARAMETER);
      }

      $field  = new self();
      $result = $field->find(['plugin_metademands_metademands_id' => $params['metademands_id']]);

      return $result;
   }

   /**
    * @param $metademands_id
    *
    * @return array
    */
   function listMetademandsfields($metademands_id) {
      $field                 = new self();
      $listMetademandsFields = $field->find(['plugin_metademands_metademands_id' => $metademands_id]);

      return $listMetademandsFields;
   }

   /**
    * @param array $input
    *
    * @return array|bool
    */
   /**
    * @param array $input
    *
    * @return array|bool
    */
   function prepareInputForAdd($input) {

      if (!$this->checkMandatoryFields($input)) {
         return false;
      }

      $meta = new PluginMetademandsMetademand();

      if ($meta->getFromDB($input['plugin_metademands_metademands_id'])
          && $meta->fields['is_order'] == 1) {
         $input['is_basket'] = 1;
      }

      if (isset($input["type"]) && $input["type"] == "checkbox") {
         $input["item"] = "checkbox";
      }
      if (isset($input["type"]) && $input["type"] == "radio") {
         $input["item"] = "radio";
      }

      return $input;
   }

   /**
    * @param array $input
    *
    * @return array|bool
    */
   /**
    * @param array $input
    *
    * @return array|bool
    */
   function prepareInputForUpdate($input) {


      if (!$this->checkMandatoryFields($input)) {
         return false;
      }
      if (isset($input["type"]) && $input["type"] == "checkbox") {
         $input["item"] = "checkbox";
      }
      if (isset($input["type"]) && $input["type"] == "radio") {
         $input["item"] = "radio";
      }

      return $input;
   }

   function cleanDBonPurge() {

      $field = new self();
      $field->deleteByCriteria(['parent_field_id' => $this->getID(),
                                'type'            => 'parent_field']);
   }

   /**
    * @param $value
    *
    * @return bool|string
    */
   static function setColor($value) {
      return substr(substr(dechex(($value * 298.45345)), 0, 2) .
                    substr(dechex(($value * 7777.2354)), 0, 3) .
                    substr(dechex(($value * 1.5455)), 0, 1) .
                    substr(dechex(($value * 64)), 0, 1) .
                    substr(dechex(($value * 13.8645)), 0, 1) .
                    substr(dechex(($value * 1.545)), 0, 1), 0, 6);

   }

   /**
    * @param $input
    *
    * @return bool
    */
   function checkMandatoryFields($input) {
      $msg     = [];
      $checkKo = false;

      $mandatory_fields = ['label'  => __('Label'),
                           'label2' => __('Additional label', 'metademands'),
                           'type'   => __('Type'),
                           'item'   => __('Object', 'metademands')];

      foreach ($input as $key => $value) {
         if (array_key_exists($key, $mandatory_fields)) {
            if (empty($value)) {
               if (($key == 'item' && $input['type'] == 'dropdown')
                   || ($key == 'label2' && $input['type'] == 'datetime_interval')) {
                  $msg[]   = $mandatory_fields[$key];
                  $checkKo = true;
               } else if ($key != 'item' && $key != 'label2') {
                  $msg[]   = $mandatory_fields[$key];
                  $checkKo = true;
               }
            }
         }
         $_SESSION['glpi_plugin_metademands_fields'][$key] = $value;
      }

      if ($checkKo) {
         Session::addMessageAfterRedirect(sprintf(__("Mandatory fields are not filled. Please correct: %s"), implode(', ', $msg)), false, ERROR);
         return false;
      }
      return true;
   }

   /**
    * @return array
    */
   /**
    * @return array
    */
   function rawSearchOptions() {

      $tab = [];

      $tab[] = [
         'id'   => 'common',
         'name' => self::getTypeName(1)
      ];

      $tab[] = [
         'id'            => '1',
         'table'         => $this->getTable(),
         'field'         => 'name',
         'name'          => __('Name'),
         'datatype'      => 'itemlink',
         'itemlink_type' => $this->getType()
      ];

      $tab[] = [
         'id'       => '30',
         'table'    => $this->getTable(),
         'field'    => 'id',
         'name'     => __('ID'),
         'datatype' => 'number'
      ];

      $tab[] = [
         'id'            => '814',
         'table'         => $this->getTable(),
         'field'         => 'rank',
         'name'          => __('Block', 'metademands'),
         'datatype'      => 'specific',
         'massiveaction' => true
      ];

      $tab[] = [
         'id'            => '815',
         'table'         => $this->getTable(),
         'field'         => 'order',
         'name'          => __('Order', 'metademands'),
         'datatype'      => 'specific',
         'massiveaction' => false
      ];

      $tab[] = [
         'id'    => '816',
         'table' => $this->getTable(),
         'field' => 'label',
         'name'  => __('Label'),
      ];

      $tab[] = [
         'id'    => '817',
         'table' => $this->getTable(),
         'field' => 'label2',
         'name'  => __('Additional label', 'metademands'),
      ];

      $tab[] = [
         'id'       => '818',
         'table'    => $this->getTable(),
         'field'    => 'comment',
         'name'     => __('Comments'),
         'datatype' => 'text'
      ];

      $tab[] = [
         'id'       => '819',
         'table'    => $this->getTable(),
         'field'    => 'is_mandatory',
         'name'     => __('Mandatory field'),
         'datatype' => 'bool'
      ];

      $tab[] = [
         'id'       => '820',
         'table'    => $this->getTable(),
         'field'    => 'is_basket',
         'name'     => __('Display into the basket', 'metademands'),
         'datatype' => 'bool'
      ];

      $tab[] = [
         'id'       => '880',
         'table'    => 'glpi_entities',
         'field'    => 'completename',
         'name'     => __('Entity'),
         'datatype' => 'dropdown'
      ];

      $tab[] = [
         'id'       => '886',
         'table'    => $this->getTable(),
         'field'    => 'is_recursive',
         'name'     => __('Child entities'),
         'datatype' => 'bool'
      ];

      return $tab;
   }

   /**
    * @param $field
    * @param $name (default '')
    * @param $values (default '')
    * @param $options   array
    *
    * @return string
    **@since version 0.84
    *
    */
   static function getSpecificValueToSelect($field, $name = '', $values = '', array $options = []) {

      if (!is_array($values)) {
         $values = [$field => $values];
      }
      $options['display'] = false;
      switch ($field) {
         case 'rank':
            $options['min'] = 1;
            $options['max'] = 10;

            return Dropdown::showNumber($name, $options);
            break;
         case 'order':
            return Dropdown::showNumber($name, $options);
            break;
      }

      return parent::getSpecificValueToSelect($field, $name, $values, $options);
   }

   /**
    * @param $rank
    * @param $fields_id
    * @param $previous_fields_id
    * @param $metademands_id
    */
   function showOrderDropdown($rank, $fields_id, $previous_fields_id, $metademands_id) {

      if (empty($rank)) {
         $rank = 1;
      }
      $restrict = ['rank' => $rank, 'plugin_metademands_metademands_id' => $metademands_id];
      if (!empty($fields_id)) {
         $restrict += ['NOT' => ['id' => $fields_id]];
      }

      $order = [Dropdown::EMPTY_VALUE];

      foreach ($this->find($restrict, ['order']) as $id => $values) {
         $order[$id] = $values['label'];
         if (!empty($values['label2'])) {
            $order[$id] .= ' - ' . $values['label2'];
         }
         if (empty(trim($order[$id]))) {
            $order[$id] = __('ID') . " - " . $id;
         }
      }
      Dropdown::showFromArray('plugin_metademands_fields_id', $order, ['value' => $previous_fields_id]);
   }

   /**
    * @param $input
    */
   function recalculateOrder($input) {
      $previousfield = new self();
      $new_order     = [];

      // Set current field after selected field
      if (!empty($input['plugin_metademands_fields_id'])) {
         $previousfield->getFromDB($input['plugin_metademands_fields_id']);
         $input['order'] = $previousfield->fields['order'] + 1;
      } else {
         $input['order'] = 1;
      }

      // Calculate order
      foreach ($this->find(['rank'                              => $input['rank'],
                            'plugin_metademands_metademands_id' => $input["plugin_metademands_metademands_id"]],
                           ['order']) as $fields_id => $values) {
         if ($fields_id == $input['id']) {
            $values['order'] = $input['order'];
         }
         if ($values['order'] >= $input['order'] && $values['id'] != $input['id']) {
            $new_order[$fields_id] = $values['order'] + 1;
         } else {
            $new_order[$fields_id] = $values['order'];
         }
      }
      asort($new_order);// sort by value

      // Update the new order on each fields of the rank
      $count    = 1;// reinit orders with a counter
      $previous = [];
      foreach ($new_order as $fields_id => $order) {
         $previous[$count] = $fields_id;
         $myfield          = new self();
         $myfield->getFromDB($fields_id);
         // Update order
         $myfield->fields['order'] = $count;
         // Update previous fields_id
         if (isset($previous[$count - 1])) {
            $myfield->fields['plugin_metademands_fields_id'] = $previous[$count - 1];
         } else {
            $myfield->fields['plugin_metademands_fields_id'] = 0;
         }
         $myfield->updateInDB(['order', 'plugin_metademands_fields_id']);
         $count++;
      }
   }
}
