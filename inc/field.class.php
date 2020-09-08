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

   static $field_types = ['', 'dropdown', 'dropdown_multiple', 'text', 'checkbox', 'textarea', 'datetime',
                          'datetime_interval', 'yesno', 'upload', 'title', 'radio', 'link', 'number', 'parent_field'];
   static $list_items  = ['', 'user', 'usertitle', 'usercategory', 'group', 'location', 'other', 'itilcategory',
                          'PluginMetademandsITILApplication', 'PluginMetademandsITILEnvironment'];

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
    * @param $ID
    * @param $item
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

      if ($ID > 0) {
         $this->check($ID, READ);
      } else {
         // Create item
         $item = $options['item'];
         $metademand = new PluginMetademandsMetademand();
         $canedit    = $metademand->can($item->fields['id'], UPDATE);
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

      $metademand_fields = new PluginMetademandsField();
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
      echo __('Additional label', 'metademands') . "&nbsp;<span style='color:red' id='show_label2' style='display:none'>&nbsp;*&nbsp;</span>";
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
                                                                               "/plugins/metademands/ajax/viewtypefields.php", $paramsRank);
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
                     'fields_display' => $this->fields['fields_display'],
                     'custom_values'  => $this->fields['custom_values'],
                     'comment_values' => $this->fields['comment_values'],
                     'default_values' => $this->fields['default_values'],
                     'check_value'    => $this->fields['check_value'],
                     'metademands_id' => $this->fields["plugin_metademands_metademands_id"],
                     'change_type'    => 1];
      Ajax::updateItemOnSelectEvent('dropdown_type' . $randType, "show_values", $CFG_GLPI["root_doc"] .
                                                                                "/plugins/metademands/ajax/viewtypefields.php", $paramsType);
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
                     'fields_display' => $this->fields['fields_display'],
                     'metademands_id' => $this->fields["plugin_metademands_metademands_id"],
                     'custom_values'  => $this->fields["custom_values"],
                     'comment_values' => $this->fields["comment_values"],
                     'default_values' => $this->fields["default_values"],
                     'check_value'    => $this->fields['check_value']];
      Ajax::updateItemOnSelectEvent('dropdown_item' . $randItem, "show_values", $CFG_GLPI["root_doc"] .
                                                                                "/plugins/metademands/ajax/viewtypefields.php", $paramsItem);
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
      echo "<td>" . __('Display into the basket ?') . "</td>";
      echo "<td>";
      Dropdown::showYesNo("is_basket", $this->fields["is_basket"]);
      echo "</td>";
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
                         'fields_display' => $this->fields['fields_display'],
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
         echo "<th class='center b' colspan='9'>" . __('Form fields', 'metademands') . "</th>";
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
            echo "<td><a href='" . Toolbox::getItemTypeFormURL('PluginMetademandsField') . "?id=" . $id . "'>";
            if (empty(trim($name))) {
               echo __('ID') . " - " . $id;
            } else {
               echo $name;
            }
            echo "</a></td>";
            echo "<td>" . self::getFieldTypesName($value['type']);
            //name of parent field
            if ($value['type'] == 'parent_field') {
               $field = new PluginMetademandsField();
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
               $name = PluginMetademandsMetademandTask::getMetademandTaskName($value['plugin_metademands_tasks_id']);
            }
            echo !empty($name) ? $name : Dropdown::EMPTY_VALUE;
            echo "</td>";
            echo "<td>";
            if (!empty($value['plugin_metademands_tasks_id'])) {
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
            } else {
               echo Dropdown::EMPTY_VALUE;
            }
            echo "</td>";
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

      $dbu = new DbUtils();

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
                        'PluginMetademandsITILApplication', 'PluginMetademandsITILEnvironment'];

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
               array_push($allowed_types, $new_fields);
            }
         }
      }

      if (isset($params['check_value']) && in_array($params['value'], $allowed_types)) {
         $metademands = new PluginMetademandsMetademand();
         $metademands->getFromDB($options['metademands_id']);

         if (isset($params['value'])) {
            echo "<div id='show_type_fields'>";
            echo "<table width='100%' class='metademands_show_values'>";
            echo "<tr><th colspan='2'>" . __('Options', 'metademands') . "</th></tr>";
            echo "<tr><td><table class='metademands_show_custom_fields'>";
            switch ($params['value']) {
               case 'text':
                  // Show field display
                  // Value to check
                  echo "<tr><td>";
                  echo __('Value to check', 'metademands');
                  echo '</td>';
                  echo '<td>';
                  echo __('Not null value', 'metademands');
                  echo '<input type="hidden" name="check_value" value="' . self::$not_null . '">';
                  echo "</td>";
                  echo "</tr>";
                  // Show field display
                  echo "<tr><td>";
                  echo __('Display if this selected field is filled', 'metademands');
                  echo '</br><span class="metademands_wizard_comments">' . __('If the selected field is filled, this field will be displayed', 'metademands') . '</span>';
                  echo '</td>';
                  echo "<td>";
                  self::showFieldsDropdown("fields_display", $metademands->fields["id"], $params['fields_display']);
                  echo "</td></tr>";
                  break;
               case 'textarea':
               case 'upload':
                  // Show field display
                  echo "<tr><td>";
                  echo __('Display if this selected field is filled', 'metademands');
                  echo '</br><span class="metademands_wizard_comments">' . __('If the selected field is filled, this field will be displayed', 'metademands') . '</span>';
                  echo '</td>';
                  echo "<td>";
                  self::showFieldsDropdown("fields_display", $metademands->fields["id"], $params['fields_display']);
                  echo "</td></tr>";
                  break;
               case 'yesno':
                  $data[1] = __('No');
                  $data[2] = __('Yes');

                  // Value to check
                  echo "<tr><td>";
                  echo __('Value to check', 'metademands') . '</td><td>';
                  Dropdown::showFromArray("check_value", $data, ['value' => $params['check_value']]);
                  echo "</td>";
                  echo "</tr><td>";

                  // Show task link
                  echo __('Link a task to the field', 'metademands');
                  echo '</br><span class="metademands_wizard_comments">' . __('If the value selected equals the value to check, the task is created', 'metademands') . '</span>';
                  echo '</td><td>';
                  PluginMetademandsTask::showAllTasksDropdown($metademands->fields["id"], $params['task_link']);
                  echo "</td></tr>";

                  // Show field link
                  echo "<tr><td>";
                  echo __('Link a field to the field', 'metademands');
                  echo '</br><span class="metademands_wizard_comments">' . __('If the value selected equals the value to check, the field becomes mandatory', 'metademands') . '</span>';
                  echo '</td>';
                  echo "<td>";
                  self::showFieldsDropdown("fields_link", $metademands->fields["id"], $params['fields_link']);
                  echo "</td></tr>";

                  // Show field display
                  echo "<tr><td>";
                  echo __('Display if this selected field is filled', 'metademands');
                  echo '</br><span class="metademands_wizard_comments">' . __('If the selected field is filled, this field will be displayed', 'metademands') . '</span>';
                  echo '</td>';
                  echo "<td>";
                  self::showFieldsDropdown("fields_display", $metademands->fields["id"], $params['fields_display']);
                  echo "</td></tr>";
                  break;
               case 'datetime' :
               case 'datetime_interval' :
                  echo "<tr><td>";
                  echo __('Day greater or equal to now', 'metademands');
                  echo "</td><td>";

                  $checked = '';
                  if (isset($params['check_value']) && !empty($params['check_value'])) {
                     $checked = 'checked';
                  }
                  echo "<input type='checkbox' name='check_value' value='1' $checked>";
                  echo "</td></tr>";

                  // Show field display
                  echo "<tr><td>";
                  echo __('Display if this selected field is filled', 'metademands');
                  echo '</br><span class="metademands_wizard_comments">' . __('If the selected field is filled, this field will be displayed', 'metademands') . '</span>';
                  echo '</td>';
                  echo "<td>";
                  self::showFieldsDropdown("fields_display", $metademands->fields["id"], $params['fields_display']);
                  echo "</td></tr>";

                  break;
               case 'number' :
                  $custom_values = [];
                  if (!empty($params['custom_values'])) {
                     $custom_values = self::_unserialize($params['custom_values']);

                  } else {
                     $custom_values['min']  = 0;
                     $custom_values['max']  = 0;
                     $custom_values['step'] = 0;
                  }

                  echo "<tr><td>";
                  echo __('Minimum', 'servicecatalog');
                  echo '</td>';
                  echo "<td>";
                  Dropdown::showNumber('min', ['value' => $custom_values['min'],
                                               'min'   => 1,
                                               'max'   => 360,
                                               'step'  => 1]);
                  echo "</td></tr>";

                  echo "<tr><td>";
                  echo __('Maximum', 'servicecatalog');
                  echo '</td>';
                  echo "<td>";
                  Dropdown::showNumber('max', ['value' => $custom_values['max'],
                                               'min'   => 1,
                                               'max'   => 360,
                                               'step'  => 1]);
                  echo "</td></tr>";

                  echo "<tr><td>";
                  echo __('Step', 'servicecatalog');
                  echo '</td>';
                  echo "<td>";
                  Dropdown::showNumber('step', ['value' => $custom_values['step'],
                                                'min'   => 1,
                                                'max'   => 360,
                                                'step'  => 1]);
                  echo "</td></tr>";

                  // Show field display
                  echo "<tr><td>";
                  echo __('Display if this selected field is filled', 'metademands');
                  echo '</br><span class="metademands_wizard_comments">' . __('If the selected field is filled, this field will be displayed', 'metademands') . '</span>';
                  echo '</td>';
                  echo "<td>";
                  self::showFieldsDropdown("fields_display", $metademands->fields["id"], $params['fields_display']);
                  echo "</td></tr>";

                  break;
               case 'group':
                  $custom_values = [];
                  if (!empty($params['custom_values'])) {
                     $custom_values = self::_unserialize($params['custom_values']);

                  } else {
                     $custom_values['is_assign']    = 0;
                     $custom_values['is_watcher']   = 0;
                     $custom_values['is_requester'] = 0;
                  }

                  // Show task link
                  echo '<tr><td>';
                  echo __('Link a task to the field', 'metademands');
                  echo '</br><span class="metademands_wizard_comments">' . __('If the value selected equals the value to check, the task is created', 'metademands') . '</span>';
                  echo '</td><td>';
                  PluginMetademandsTask::showAllTasksDropdown($metademands->fields["id"], $params['task_link']);
                  echo "</td></tr>";

                  // Show field link
                  echo "<tr><td>";
                  echo __('Link a field to the field', 'metademands');
                  echo '</br><span class="metademands_wizard_comments">' . __('If the value selected equals the value to check, the field becomes mandatory', 'metademands') . '</span>';
                  echo '</td>';
                  echo "<td>";
                  self::showFieldsDropdown("fields_link", $metademands->fields["id"], $params['fields_link']);
                  echo "</td></tr>";

                  // Assigned group
                  echo "<tr><td>";
                  echo __('Assigned');
                  echo '</td>';
                  echo "<td>";
                  Dropdown::showYesNo('is_assign', $custom_values['is_assign']);
                  echo "</td></tr>";

                  // Watcher group
                  echo "<tr><td>";
                  echo __('Watcher');
                  echo '</td>';
                  echo "<td>";
                  Dropdown::showYesNo('is_watcher', $custom_values['is_watcher']);
                  echo "</td></tr>";

                  // Requester group
                  echo "<tr><td>";
                  echo __('Requester');
                  echo '</td>';
                  echo "<td>";
                  Dropdown::showYesNo('is_requester', $custom_values['is_requester']);
                  echo "</td></tr>";

                  // Show field display
                  echo "<tr><td>";
                  echo __('Display if this selected field is filled', 'metademands');
                  echo '</br><span class="metademands_wizard_comments">' . __('If the selected field is filled, this field will be displayed', 'metademands') . '</span>';
                  echo '</td>';
                  echo "<td>";
                  self::showFieldsDropdown("fields_display", $metademands->fields["id"], $params['fields_display']);
                  echo "</td></tr>";

                  break;
               case 'user':
               case 'usertitle':
               case 'usercategory':
               case 'location':
               case 'itilcategory':
                  //               case 'PluginResourcesResource':
               case 'other':
               case 'dropdown':
               case 'dropdown_multiple':
               case 'PluginMetademandsITILApplication':
               case 'PluginMetademandsITILEnvironment':
                  //               case strpos($params['value'], 'PluginLdapfields'):
                  // Value to check
                  echo "<tr><td>";
                  echo __('Value to check', 'metademands');
                  echo '</td>';
                  echo '<td>';
                  echo __('Not null value', 'metademands');
                  echo '<input type="hidden" name="check_value" value="' . self::$not_null . '">';
                  echo "</td>";
                  echo "</tr>";

                  // Show task link
                  echo '<tr><td>';
                  echo __('Link a task to the field', 'metademands');
                  echo '</br><span class="metademands_wizard_comments">' . __('If the value selected equals the value to check, the task is created', 'metademands') . '</span>';
                  echo '</td><td>';
                  PluginMetademandsTask::showAllTasksDropdown($metademands->fields["id"], $params['task_link']);
                  echo "</td></tr>";

                  // Show field link
                  echo "<tr><td>";
                  echo __('Link a field to the field', 'metademands');
                  echo '</br><span class="metademands_wizard_comments">' . __('If the value selected equals the value to check, the field becomes mandatory', 'metademands') . '</span>';
                  echo '</td>';
                  echo "<td>";
                  self::showFieldsDropdown("fields_link", $metademands->fields["id"], $params['fields_link']);
                  echo "</td></tr>";
                  // Show field display
                  echo "<tr><td>";
                  echo __('Display if', 'metademands');
                  echo '</br><span class="metademands_wizard_comments">' . __('If the selected field is filled, this field will be displayed', 'metademands') . '</span>';
                  echo '</td>';
                  echo "<td>";
                  self::showFieldsDropdown("fields_display", $metademands->fields["id"], $params['fields_display']);
                  echo "</td></tr>";
                  break;
               case 'checkbox':
               case 'radio':
                  // Value to check
                  echo "<tr><td>";
                  echo __('Value to check', 'metademands') . '</td>';
                  echo '<td>';
                  echo __('Not null value', 'metademands');
                  echo '<input type="hidden" name="check_value" value="' . self::$not_null . '">';
                  echo "</td>";
                  echo "</tr><td>";

                  // Show task link
                  echo __('Link a task to the field', 'metademands');
                  echo '</br><span class="metademands_wizard_comments">' . __('If the value selected equals the value to check, the task is created', 'metademands') . '</span>';
                  echo '</td><td>';
                  PluginMetademandsTask::showAllTasksDropdown($metademands->fields["id"], $params['task_link']);
                  echo "</td></tr>";

                  // Show field display
                  echo "<tr><td>";
                  echo __('Display if this selected field is filled', 'metademands');
                  echo '</br><span class="metademands_wizard_comments">' . __('If the selected field is filled, this field will be displayed', 'metademands') . '</span>';
                  echo '</td>';
                  echo "<td>";
                  self::showFieldsDropdown("fields_display", $metademands->fields["id"], $params['fields_display']);
                  echo "</td></tr>";
                  break;
               case 'parent_field':
                  echo "<tr><td>";
                  echo __('Field') . '</td>';
                  echo '<td>';
                  //list of fields
                  $fields            = [];
                  $metademand_parent = new PluginMetademandsMetademand();

                  // list of parents
                  $metademands_parent = PluginMetademandsMetademandTask::getAncestorOfMetademandTask($metademands->fields["id"]);

                  foreach ($metademands_parent as $parent_id) {
                     if ($metademand_parent->getFromDB($parent_id)) {
                        $name_metademand = $metademand_parent->getName();
                        $condition       = ['plugin_metademands_metademands_id' => $parent_id,
                                            ['NOT' => ['type' => ['parent_field', 'upload']]]];
                        $datas_fields    = $this->find($condition, ['rank', 'order']);
                        //formatting the name to display (Name of metademand - Father's Field Label - type)
                        foreach ($datas_fields as $data_field) {
                           $fields[$data_field['id']] = $name_metademand . " - " . $data_field['label'] . " - " . self::getFieldTypesName($data_field['type']);
                        }
                     }
                  }
                  Dropdown::showFromArray('parent_field_id', $fields);
                  echo "</td>";
                  echo "</tr>";
                  break;
            }

            echo "</table></td></tr>";
            echo "</table>";
            echo "</div>";
         }
      }
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
                        echo '<input type="text" name="comment_values[' . $key . ']"  value="' . $comment[$key] . '" size="30"/>';
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

         echo '</table>';
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
    * @param $field
    * @param $metademands_id
    * @param $selected_value
    */
   static function showFieldsDropdown($field, $metademands_id, $selected_value) {

      $fields = new self();
      //TODO Add authorized types for fields_display ?
      $fields_data = $fields->find(['plugin_metademands_metademands_id' => $metademands_id]);
      $data        = [Dropdown::EMPTY_VALUE];
      foreach ($fields_data as $id => $value) {
         $name = $value['label'];
         if (empty(trim($name))) {
            $name = 'ID - ' . $id;
         }
         $data[$id] = $name;
         if (!empty($value['label2'])) {
            $data[$id] = ' ' . $value['label2'];
         }
      }

      Dropdown::showFromArray($field, $data, ['value' => $selected_value]);
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
    * @param $rand
    */
   static function dropdownMassiveAction($rand) {

      echo "<input type='hidden' name='itemtype' value='PluginMetademandsField'>";
      echo "<input type='hidden' name='action' value='delete'>";
      echo "&nbsp;<input type='submit' name='massiveaction' class='submit' value='" . _sx('button', 'Delete permanently') . "' >";

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

      $metademands = new self();
      $result      = $metademands->listMetademandsfields($params['metademands_id']);

      return $result;
   }

   /**
    * @param $metademands_id
    *
    * @return array
    */
   function listMetademandsfields($metademands_id) {
      $field                 = new PluginMetademandsField();
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

      //      $data = array_keys($input);
      //
      //      foreach($DB->list_fields($this->getTable()) as $field => $values){
      //         if(!in_array($field, $data)){
      //            $input[$field] = 0;
      //         }
      //      }

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
