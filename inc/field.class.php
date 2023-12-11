<?php
/*
 * @version $Id: HEADER 15930 2011-10-30 15:47:55Z tsmr $
 -------------------------------------------------------------------------
 Metademands plugin for GLPI
 Copyright (C) 2018-2022 by the Metademands Development Team.

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
class PluginMetademandsField extends CommonDBChild
{
    public static $rightname = 'plugin_metademands';

    public static $itemtype = 'PluginMetademandsMetademand';
    public static $items_id = 'plugin_metademands_metademands_id';

    // Request type
    const MAX_FIELDS = 40;


    public static $field_types = ['', 'dropdown', 'dropdown_object', 'dropdown_meta', 'dropdown_multiple',
                                  'title', 'title-block', 'informations', 'text', 'textarea', 'yesno',
                                  'checkbox', 'radio', 'number', 'basket', 'date', 'datetime', 'date_interval',
                                  'datetime_interval','upload', 'link',
                                   'parent_field'];

    public static $allowed_options_types = ['upload', 'text', 'date', 'datetime', 'date_interval', 'datetime_interval',
        'dropdown_multiple', 'dropdown_object', 'basket'];
    public static $allowed_options_items = ['User'];

    public static $allowed_custom_types = ['checkbox', 'yesno', 'radio', 'link', 'dropdown_multiple', 'number', 'basket'];
    public static $allowed_custom_items = ['other'];

    public static $not_null = 'NOT_NULL';


    public static function getIcon()
    {
        return PluginMetademandsMetademand::getIcon();
    }


    public function canCreateItem()
    {

        return true;

    }

   /**
    * functions mandatory
    * getTypeName(), canCreate(), canView()
    *
    * @param int $nb
    *
    * @return string
    */
    public static function getTypeName($nb = 0)
    {
        return __('Wizard creation', 'metademands');
    }

   /**
    * @return bool|int
    */
    public static function canView()
    {
        return Session::haveRight(self::$rightname, READ);
    }

   /**
    * @return bool
    */
    public static function canCreate()
    {
        return Session::haveRightsOr(self::$rightname, [CREATE, UPDATE, DELETE]);
    }


    /**
     * Get request criteria to search for an item
     *
     * @since 9.4
     *
     * @param string  $itemtype Item type
     * @param integer $items_id Item ID
     *
     * @return array|null
     **/
    public static function getSQLCriteriaToSearchForItem($itemtype, $items_id)
    {
        $table = static::getTable();

        $criteria = [
            'SELECT' => [
                static::getIndexName(),
                'plugin_metademands_metademands_id AS items_id'
            ],
            'FROM'   => $table,
            'WHERE'  => [
                $table . '.' . 'plugin_metademands_metademands_id' => $items_id
            ]
        ];

        // Check item 1 type
        $request = false;
        if (preg_match('/^itemtype/', static::$itemtype)) {
            $criteria['SELECT'][] = static::$itemtype . ' AS itemtype';
            $criteria['WHERE'][$table . '.' . static::$itemtype] = $itemtype;
            $request = true;
        } else {
            $criteria['SELECT'][] = new \QueryExpression("'" . static::$itemtype . "' AS itemtype");
            if (
                ($itemtype ==  static::$itemtype)
                || is_subclass_of($itemtype, static::$itemtype)
            ) {
                $request = true;
            }
        }
        if ($request === true) {
            return $criteria;
        }
        return null;
    }


    /**
    *
    * @param CommonGLPI $item
    * @param int        $withtemplate
    *
    * @return array|string
    */
    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        if ($item->getType() == 'PluginMetademandsMetademand') {
            if ($_SESSION['glpishow_count_on_tabs']) {
                $dbu = new DbUtils();
                return self::createTabEntry(
                    self::getTypeName(),
                    $dbu->countElementsInTable(
                        $this->getTable(),
                        ["plugin_metademands_metademands_id" => $item->getID()]
                    )
                );
            }
            return self::getTypeName();
        }
        return '';
    }

   /**
    *
    * @static
    *
    * @param CommonGLPI $item
    * @param int        $tabnum
    * @param int        $withtemplate
    *
    * @return bool|true
    */
    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        self::listFields($item);

        return true;
    }

   /**
    * @param array $options
    *
    * @return array
    * @see CommonGLPI::defineTabs()
    */
    public function defineTabs($options = [])
    {
        $ong = [];
        $this->addDefaultFormTab($ong);
        $this->addStandardTab('PluginMetademandsFieldOption', $ong, $options);
        $this->addStandardTab('PluginMetademandsFieldTranslation', $ong, $options);
        return $ong;
    }


   /**
    * @param       $ID
    * @param array $options
    *
    * @return bool
    * @throws \GlpitestSQLError
    */
    public function showForm($ID, $options = [])
    {
        global $PLUGIN_HOOKS;

        if (!$this->canview()) {
            return false;
        }
        if (!$this->cancreate()) {
            return false;
        }
        Html::requireJs('tinymce');

        $metademand = new PluginMetademandsMetademand();

        if (isset($options['parent']) && !empty($options['parent'])) {
            $item = $options['parent'];
        }

        if ($ID > 0) {
            $this->check($ID, READ);
            $metademand->getFromDB($this->fields['plugin_metademands_metademands_id']);
        } else {
            // Create item
            if (!isset($item)) {
               return false;
            }
            $options['itemtype'] = get_class($item);
            $options['items_id'] = $item->getID();
            $metademand->getFromDB($item->getID());
            // Create item
            $this->check(-1, CREATE, $options);
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

        $this->showFormHeader($options);

        $metademand_fields = new self();
        $metademand_fields->getFromDBByCrit(['plugin_metademands_metademands_id' => $this->fields['plugin_metademands_metademands_id'],
                                           'item'                              => 'ITILCategory_Metademands']);
        $categories = [];
        if (isset($metademand->fields['itilcategories_id'])) {
            if (is_array(json_decode($metademand->fields['itilcategories_id'], true))) {
                $categories = json_decode($metademand->fields['itilcategories_id'], true);
            }
        }

        if (count($metademand_fields->fields) < 1 && count($categories) > 1) {
            echo "<div class='alert alert-important alert-warning d-flex'>";
            echo "<i class='fas fa-exclamation-triangle fa-3x'></i>&nbsp;" . __('Please add a type category field', 'metademands');
            echo "</div>";
        }

        echo "<tr class='tab_bg_1'>";

        // LABEL
        echo "<td>" . __('Label') . "<span style='color:red'>&nbsp;*&nbsp;</span></td>";
        echo "<td>";
        echo Html::input('name', ['value' => stripslashes($this->fields["name"]), 'size' => 40]);
        if ($ID > 0) {
            echo Html::hidden('entities_id', ['value' => $this->fields["entities_id"]]);
            echo Html::hidden('is_recursive', ['value' => $this->fields["is_recursive"]]);
        } else {
            echo Html::hidden('entities_id', ['value' => $item->fields["entities_id"]]);
            echo Html::hidden('is_recursive', ['value' => $item->fields["is_recursive"]]);
        }
        echo "</td>";

        // MANDATORY
        echo "<td>" . __('Mandatory field') . "</td>";
        echo "<td>";
        Dropdown::showYesNo("is_mandatory", $this->fields["is_mandatory"]);
        echo "</td>";
        echo "</tr>";

        echo "<tr class='tab_bg_1'>";
        echo "<td>";
        echo __('Hide title', 'metademands');
        echo "</td>";
        echo "<td>";
        Dropdown::showYesNo('hide_title', ($this->fields['hide_title']));
        echo "</td>";
        echo "<td colspan='2'> </td>";
        echo "</tr>";

        // LABEL 2
        echo "<tr class='tab_bg_1'>";
        echo "<td>";
        echo __('Additional label', 'metademands') ;
        echo "&nbsp;<span id='show_label2' style='color:red;display:none;'>&nbsp;*&nbsp;</span>";
        echo "</td>";
        echo "<td>";
        $label2 = Html::cleanPostForTextArea($this->fields['label2']);
        Html::textarea(['name'              => 'label2',
                      'value'             => $label2,
                      'enable_richtext'   => true,
                      'enable_fileupload' => false,
                      'enable_images'     => true,
                      'cols'              => 50,
                      'rows'              => 3]);
       //      Html::autocompletionTextField($this, "label2", ['value' => stripslashes($this->fields["label2"])]);
        echo "</td>";

        // COMMENT
        echo "<td>" . __('Comments') . "</td>";
        echo "<td>";
        $comment = Html::cleanPostForTextArea($this->fields['comment']);
        Html::textarea(['name'              => 'comment',
                      'value'             => $comment,
                      'enable_richtext'   => true,
                      'enable_fileupload' => false,
                      'enable_images'     => false,
                      'cols'              => 50,
                      'rows'              => 3]);

        echo "</td>";
        echo "</tr>";

        echo "<tr class='tab_bg_1'>";
        echo "<td>";
        echo __('Takes the whole row', 'metademands');
        echo "</td>";
        echo "<td>";
        Dropdown::showYesNo('row_display', ($this->fields['row_display']));
        echo "</td>";


        // BLOCK
        echo "<td>" . __('Block', 'metademands') . "</td>";
        echo "<td>";
        $randRank   = Dropdown::showNumber('rank', ['value' => $this->fields["rank"],
                                                  'min'   => 1,
                                                  'max'   => self::MAX_FIELDS]);
        $paramsRank = ['rank'               => '__VALUE__',
                     'step'               => 'order',
                     'fields_id'          => $this->fields['id'],
                     'metademands_id'     => $this->fields['plugin_metademands_metademands_id'],
                     'previous_fields_id' => $this->fields['plugin_metademands_fields_id']];
        Ajax::updateItemOnSelectEvent('dropdown_rank' . $randRank, "show_order", PLUGIN_METADEMANDS_WEBDIR .
                                                                               "/ajax/viewtypefields.php?id=" . $this->fields['id'], $paramsRank);
        echo "</td>";
        echo "</tr>";

        echo "<tr class='tab_bg_1'>";
        // TYPE
        echo "<td>" . __('Type') . "<span style='color:red'>&nbsp;*&nbsp;</span></td>";
        echo "<td>";

        $randType   = self::dropdownFieldTypes("type", ['value'          => $this->fields["type"],
                                                      'metademands_id' => $this->fields["plugin_metademands_metademands_id"]]);
        $paramsType = ['value' => '__VALUE__',
            'type' => '__VALUE__',
            'item' => $this->fields['item'],
            'max_upload' => $this->fields['max_upload'],
            'regex' => $this->fields['regex'],
            'use_future_date'            => $this->fields['use_future_date'],
            'use_date_now' => $this->fields['use_date_now'],
            'additional_number_day' => $this->fields['additional_number_day'],
            'display_type' => $this->fields['display_type'],
            'informations_to_display' => $this->fields['informations_to_display'],
            'custom_values' => $this->fields['custom_values'],
            'comment_values' => $this->fields['comment_values'],
            'default_values' => $this->fields['default_values'],
            'metademands_id' => $this->fields["plugin_metademands_metademands_id"],
            'link_to_user' => $this->fields["link_to_user"],
            'readonly' => $this->fields["readonly"],
            'hidden' => $this->fields["hidden"],
            'change_type' => 1];
        Ajax::updateItemOnSelectEvent('dropdown_type' . $randType, "show_values", PLUGIN_METADEMANDS_WEBDIR .
                                                                                "/ajax/viewtypefields.php?id=" . $this->fields['id'], $paramsType);

        if ($metademand->fields['is_basket'] == 0
            && ($this->fields['type'] == 'basket' || $this->fields['type'] == 'free_input')) {
            echo "<span class='alert alert-warning d-flex'>";
            echo __('Remember to activate basket mode on your metademand !', 'metademands');
            echo "</span>";
        }
        echo "</td>";

        // ORDER
        if ($this->fields['type'] != "title-block") {
            echo "<td>" . __('Display field after', 'metademands') . "</td>";
            echo "<td>";
            echo "<span id='show_order'>";
            $this->showOrderDropdown(
                $this->fields['rank'],
                $this->fields['id'],
                $this->fields['plugin_metademands_fields_id'],
                $this->fields["plugin_metademands_metademands_id"]
            );
            echo "</span>";
            echo "</td>";
        } else {
            echo "<td colspan='2'></td>";
        }
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

        echo "</span>";
        echo "</td>";
        echo "<td>";
        echo "<span id='show_item' >";
        $randItem = self::dropdownFieldItems("item", $this->fields["type"], ['value' => $this->fields["item"]]);
        echo "</span>";
        $paramsType = ['value'                   => '__VALUE__',
                     'type'                    => '__VALUE__',
                     'item'                    => $this->fields['item'],
                     'max_upload'              => $this->fields['max_upload'],
                     'regex'                   => $this->fields['regex'],
                     'use_date_now'            => $this->fields['use_date_now'],
                     'additional_number_day'   => $this->fields['additional_number_day'],
                     'display_type'            => $this->fields['display_type'],
                     'informations_to_display' => $this->fields['informations_to_display'],
                     'custom_values'           => $this->fields['custom_values'],
                     'comment_values'          => $this->fields['comment_values'],
                     'default_values'          => $this->fields['default_values'],
                     'step'                    => 'object',
                     'rand'                    => $randItem,
                     'metademands_id'          => $this->fields["plugin_metademands_metademands_id"],
                     'link_to_user'            => $this->fields["link_to_user"],
                     'readonly' => $this->fields["readonly"],
                     'hidden' => $this->fields["hidden"],
                     'change_type'             => 1];
        Ajax::updateItemOnSelectEvent('dropdown_type' . $randType, "show_item", PLUGIN_METADEMANDS_WEBDIR .
                                                                              "/ajax/viewtypefields.php?id=" . $this->fields['id'], $paramsType);
        echo "<span id='show_item_title' style='display:none'>";

        echo __('Color') . "<span style='color:red'>&nbsp;*&nbsp;</span>";

        Html::showColorField('color', ['value' => $this->fields["color"]]);

        echo "<br><br>";

        echo __('Icon'). "&nbsp;";

        $icon_selector_id = 'icon_' . mt_rand();
        echo Html::select(
            'icon',
            [$this->fields['icon'] => $this->fields['icon']],
            [
                'id' => $icon_selector_id,
                'selected' => $this->fields['icon'],
                'style' => 'width:175px;'
            ]
        );

        echo Html::script('js/Forms/FaIconSelector.js');
        echo Html::scriptBlock(
            <<<JAVASCRIPT
         $(
            function() {
               var icon_selector = new GLPI.Forms.FaIconSelector(document.getElementById('{$icon_selector_id}'));
               icon_selector.init();
            }
         );
JAVASCRIPT
        );
        echo "</span>";

        $paramsItem = ['value'                   => '__VALUE__',
                     'item'                    => '__VALUE__',
                     'type'                    => $this->fields['type'],
                     'max_upload'              => $this->fields['max_upload'],
                     'regex'                   => $this->fields['regex'],
                     'use_date_now'            => $this->fields['use_date_now'],
                     'additional_number_day'   => $this->fields['additional_number_day'],
                     'display_type'            => $this->fields['display_type'],
                     'informations_to_display' => $this->fields['informations_to_display'],
                     'metademands_id'          => $this->fields["plugin_metademands_metademands_id"],
                     'custom_values'           => $this->fields["custom_values"],
                     'comment_values'          => $this->fields["comment_values"],
                     'default_values'          => $this->fields["default_values"],
                     'link_to_user'            => $this->fields["link_to_user"],
                    'readonly' => $this->fields["readonly"],
                    'hidden' => $this->fields["hidden"]
        ];
        Ajax::updateItemOnSelectEvent('dropdown_item' . $randItem, "show_values", PLUGIN_METADEMANDS_WEBDIR .
                                                                                "/ajax/viewtypefields.php?id=" . $this->fields['id'], $paramsItem);


        echo Html::hidden('plugin_metademands_metademands_id', ['value' => $this->fields["plugin_metademands_metademands_id"]]);

        $params = ['id'                 => 'dropdown_type' . $randType,
                 'to_change'          => 'dropdown_item' . $randItem,
                 'value'              => 'dropdown',
                 'value2'             => 'dropdown_object',
                 'value3'             => 'dropdown_meta',
                 'value4'             => 'dropdown_multiple',
                 'value5'             => 'basket',
                 'current_item'       => $this->fields['item'],
                 'current_type'       => $this->fields['type'],
                 'titleDisplay'       => 'show_item_object',
                 'valueDisplay'       => 'show_item',
                 'titleDisplay_title' => 'show_item_label_title',
                 'valueDisplay_title' => 'show_item_title',
                 'value_title'        => 'title',
                 'value_informations' => 'informations',
                 'value_title_block'  => 'title-block',
        ];
        if (isset($PLUGIN_HOOKS['metademands'])) {
            foreach ($PLUGIN_HOOKS['metademands'] as $plug => $method) {
                $params['value_plugin'] = self::addPluginFieldTypeValue($plug);
            }
        }

        $script = "var metademandWizard = $(document).metademandWizard(" . json_encode(['root_doc' => PLUGIN_METADEMANDS_WEBDIR]) . ");";
        $script .= "metademandWizard.metademands_show_field_onchange(" . json_encode($params) . ");";
        $script .= "metademandWizard.metademands_show_field(" . json_encode($params) . ");";
        echo Html::scriptBlock('$(document).ready(function() {' . $script . '});');

        echo "</td>";
        // Is_Basket Fields
        if ($metademand->fields['is_order'] == 1) {
            echo "<td>" . __('Display into the basket', 'metademands') . "</td>";
            echo "<td>";
            if ($ID > 0) {
                $value = $this->fields["is_basket"];
            } else {
                $value = 1;
            }
            Dropdown::showYesNo("is_basket", $value);
            echo "</td>";
        } else {
            echo "<td colspan='2'></td>";
        }

        echo "</tr>";

        echo "<tr class='tab_bg_1'>";
        if ($this->fields['type'] == "dropdown_object"
          && $this->fields["item"] == "Group") {
            $custom_values = self::_unserialize($this->fields['custom_values']);
            $is_assign     = $custom_values['is_assign'] ?? 0;
            $is_watcher    = $custom_values['is_watcher'] ?? 0;
            $is_requester  = $custom_values['is_requester'] ?? 0;
            $user_group  = $custom_values['user_group'] ?? 0;
            echo "<td></td>";

            echo "<td>";
            echo __('Requester');
            echo "&nbsp;";
            // Assigned group
            Dropdown::showYesNo('is_requester', $is_requester);
            echo "<br>";
            echo __('Watcher');
            echo "&nbsp;";
            // Watcher group
            Dropdown::showYesNo('is_watcher', $is_watcher);
            echo "<br>";
            echo __('Assigned');
            echo "&nbsp;";
            // Requester group
            Dropdown::showYesNo('is_assign', $is_assign);
            echo "<br>";
            echo __('My groups');
            echo "&nbsp;";
            // user_group
            Dropdown::showYesNo('user_group', $user_group);
            echo "</td>";
        } elseif (($this->fields['type'] == "dropdown_object"
                 && $this->fields["item"] == "User")
        || ($this->fields['type'] == "dropdown_multiple"
                && $this->fields["item"] == "User")) {
            $custom_values = self::_unserialize($this->fields['custom_values']);
            $user_group  = $custom_values['user_group'] ?? 0;
            echo "<td></td>";

            echo "<td>";
            echo __('Only users of my groups', 'metademands');
            echo "&nbsp;";
            // user_group
            Dropdown::showYesNo('user_group', $user_group);
            echo "</td>";
        } else {
            echo "<td colspan='2'></td>";
        }

        //TODO permit linked items_id / itemtype
        if ($ID > 0
          && $this->fields['type'] != "title"
          && $this->fields['type'] != "title-block"
          && $this->fields['type'] != "informations") {
            echo "<td>";
            echo __('Use this field as object field', 'metademands');
            echo "</td>";
            echo "<td>";
            $ticket_fields[0] = Dropdown::EMPTY_VALUE;
            $objectclass      = $metademand->fields['object_to_create'];
            $searchOption     = Search::getOptions($objectclass);

            if ($objectclass == 'Ticket') {
                $tt = new TicketTemplate();
            } elseif ($objectclass == 'Problem') {
                $tt = new ProblemTemplate();
            } elseif ($objectclass == 'Change') {
                $tt = new ChangeTemplate();
            }
            $allowed_fields = $tt->getAllowedFields(true, true);

            unset($allowed_fields[-2]);

           //      Array ( [1] => name [21] => content [12] => status [10] => urgency [11] => impact [3] => priority
           //      [15] => date [4] => _users_id_requester [71] => _groups_id_requester [5] => _users_id_assign
           //      [8] => _groups_id_assign [6] => _suppliers_id_assign [66] => _users_id_observer [65] => _groups_id_observer
           //      [7] => itilcategories_id [131] => itemtype [13] => items_id [142] => _documents_id [175] => _tasktemplates_id [9] => requesttypes_id
           //      [83] => locations_id [37] => slas_id_tto [30] => slas_id_ttr [190] => olas_id_tto [191] => olas_id_ttr [18] => time_to_resolve
           //      [155] => time_to_own [180] => internal_time_to_resolve [185] => internal_time_to_own [45] => actiontime [52] => global_validation [14] => type )
           //         $granted_fields = [
           //            4,
           //            71,
           //            66,
           //            65,
           //            'urgency',
           //            'impact',
           //            'priority',
           //            'locations_id',
           //            'requesttypes_id',
           //            'itemtype',
           //            'items_id',
           //            'time_to_resolve',
           //         ];
            $granted_fields = [];
            if (($this->fields['type'] == "dropdown_object"
             && $this->fields["item"] == "User")
                || ($this->fields['type'] == "dropdown_multiple"
                    && $this->fields["item"] == "User")) {
                //Valideur
                $allowed_fields[59] = __('Approver');
                $granted_fields     = [
                4,
                66,
                59
                ];
            }
            if ($this->fields['type'] == "dropdown_object"
             && $this->fields["item"] == "Group") {
                $granted_fields = [
                71,
                65,
                ];
            }

            if ($this->fields['type'] == "dropdown_object"
                && $this->fields["item"] == "Entity") {
                $allowed_fields[80] = 'entities_id';
                $granted_fields = [
                    80,
                ];
            }

            if ($this->fields['type'] == "dropdown"
             && $this->fields["item"] == "Location") {
                $granted_fields = [
                'locations_id',
                ];
            }

            if ($this->fields['type'] == "dropdown"
             && $this->fields["item"] == "RequestType") {
                $granted_fields = [
                'requesttypes_id',
                ];
            }

            if ($this->fields['type'] == "dropdown_meta"
             && ($this->fields["item"] == "urgency" || $this->fields["item"] == "impact" || $this->fields["item"] == "priority")) {
                $granted_fields = [
                $this->fields["item"]
                ];
            }

            if ($this->fields['type'] == "dropdown_meta"
              && ($this->fields["item"] == "ITILCategory_Metademands")) {
                $granted_fields = [
                  'itilcategories_id'
                ];
            }

            if ($this->fields['type'] == "date"
             || $this->fields["type"] == "datetime") {
                $granted_fields = [
                'time_to_resolve'
                ];
            }

            if (($this->fields['type'] == "dropdown_meta"
              && $this->fields["item"] == "mydevices")
                || ($this->fields['type'] == "dropdown_multiple"
                    && $this->fields["item"] == "Appliance")
             || ($this->fields['type'] == "dropdown_object"
                 && Ticket::isPossibleToAssignType($this->fields["item"]))) {
                $granted_fields = [
                13
                ];
            }

            foreach ($allowed_fields as $id => $value) {
                if (in_array($searchOption[$id]['linkfield'], $granted_fields) || in_array($id, $granted_fields)) {
                    $ticket_fields[$id] = $searchOption[$id]['name'];
                }
            }
            Dropdown::showFromArray(
                'used_by_ticket',
                $ticket_fields,
                ['value' => $this->fields["used_by_ticket"]]
            );
            echo "</td>";
        } else {
            echo "<td colspan='2'></td>";
        }
        echo "</tr>";

        if ($ID > 0 && $this->fields['type'] == "textarea") {
            echo "<tr class='tab_bg_1'>";
            echo "<td>";
            echo __('Use richt text', 'metademands');
            echo "</td>";
            echo "<td>";
            Dropdown::showYesNo('use_richtext', ($this->fields['use_richtext']));
            echo "</td>";
            echo "<td colspan='2'></td>";
            echo "</tr>";
        }

        if ($ID > 0 && (
            ($this->fields['type'] == "dropdown_object"
                       && ($this->fields["item"] == "User"
                           || $this->fields["item"] == "Group"))
                      || ($this->fields['type'] == "dropdown"
                          && ($this->fields["item"] == "Location"
                              || $this->fields["item"] == "RequestType"))
                      || ($this->fields['type'] == "dropdown_meta"
                          && ($this->fields["item"] == "urgency"
                              || $this->fields["item"] == "impact"
                              || $this->fields["item"] == "priority"))
                      || $this->fields['type'] == "date"
                      || $this->fields["type"] == "datetime"
        )
        ) {
            echo "<tr class='tab_bg_1'>";
            echo "<td colspan='2'>";
            echo "</td>";
            if ($metademand->fields['object_to_create'] == 'Ticket') {
                echo "<td>";
                echo __('Use this field for child ticket field', 'metademands');
                echo "</td>";
                echo "<td>";
                Dropdown::showYesNo('used_by_child', $this->fields['used_by_child']);
            } else {
                echo "<td colspan='2'>";
            }
            echo "</td>";
            echo "</tr>";
        } else {
            Html::hidden('used_by_child', ['value' => 0]);
        }

        if ($ID > 0 && ($this->fields['type'] == "dropdown_object"
                      && $this->fields["item"] == "User")) {
            echo "<tr class='tab_bg_1'>";
            echo "<td colspan='2'>";
            echo "</td>";
            echo "<td>";
            echo __('Use id of requester by default', 'metademands');
            echo "</td>";
            echo "<td>";
            Dropdown::showYesNo('default_use_id_requester', $this->fields['default_use_id_requester']);
            echo "</td>";
            echo "</tr>";
            echo "<tr class='tab_bg_1'>";
            echo "<td colspan='2'>";
            echo "</td>";
            echo "<td>";
            echo __('Read-Only', 'metademands');
            echo "</td>";
            echo "<td>";
            Dropdown::showYesNo('readonly', ($this->fields['readonly']));
            echo "</td>";
            echo "</tr>";
        }

        if ($ID > 0 && ($this->fields['type'] == "dropdown_meta"
                && $this->fields["item"] == "ITILCategory_Metademands")) {
            echo "<tr class='tab_bg_1'>";

            echo "<td>";
            echo __('Read-Only', 'metademands');
            echo "</td>";
            echo "<td>";
            Dropdown::showYesNo('readonly', ($this->fields['readonly']));
            echo "</td>";

            echo "<td>";
            echo __('Hidden field', 'metademands');
            echo "</td>";
            echo "<td>";
            Dropdown::showYesNo('hidden', ($this->fields['hidden']));
            echo "</td>";

            echo "</tr>";
        }

        if ($ID > 0 && (
            ($this->fields['type'] == "dropdown_object"
                       && $this->fields["item"] == "Group")
                      || ($this->fields['type'] == "dropdown"
                          && $this->fields["item"] == "Location")
                      || ($this->fields['type'] == "dropdown_meta"
                          && $this->fields["item"] == "mydevices")
        )
        ) {
            echo "<tr class='tab_bg_1'>";
            echo "<td colspan='2'>";
            echo "</td>";
            echo "<td>";
            echo __('Link this to a user field', 'metademands');
            echo "</td>";
            echo "<td>";
            $arrayAvailable    = [];
            $arrayAvailable[0] = Dropdown::EMPTY_VALUE;
            $field             = new self();
            $fields            = $field->find(["plugin_metademands_metademands_id" => $this->fields['plugin_metademands_metademands_id'],
                                            'type'                              => "dropdown_object",
                                            "item"                              => User::getType()]);
            foreach ($fields as $f) {
                $arrayAvailable [$f['id']] = $f['rank'] . " - " . urldecode(html_entity_decode($f['name']));
            }
            Dropdown::showFromArray('link_to_user', $arrayAvailable, ['value' => $this->fields['link_to_user']]);
            echo "</td>";
            echo "</tr>";
        } else {
            Html::hidden('link_to_field', ['value' => 0]);
        }
        if (Plugin::isPluginActive('fields')) {
            echo "<tr class='tab_bg_1'>";
            echo "<td>";
            echo __('Link this to a plugin "fields" field', 'metademands');
            echo "</td>";
            echo "<td>";

            $arrayAvailableContainer = [];
            $fieldsContainer         = new PluginFieldsContainer();
            $fieldsContainers        = $fieldsContainer->find();

            $meta = new PluginMetademandsMetademand();
            $meta->getFromDB($this->fields["plugin_metademands_metademands_id"]);
            foreach ($fieldsContainers as $container) {
                $typesContainer = json_decode($container['itemtypes']);
                if (is_array($typesContainer) && in_array($meta->fields["object_to_create"], $typesContainer)) {
                    $arrayAvailableContainer[] = $container['id'];
                }
            }

            $pluginfield = new PluginMetademandsPluginfields();
            $opt = ['display_emptychoice' => true];
            if ($pluginfield->getFromDBByCrit(['plugin_metademands_fields_id' => $ID])) {
                $opt["value"] = $pluginfield->fields["plugin_fields_fields_id"];
            }
            $condition  = [];
            if (count($arrayAvailableContainer) > 0) {
                $condition  = ['plugin_fields_containers_id' => $arrayAvailableContainer];
            }

            $field = new PluginFieldsField();
            $fields_values = $field->find($condition);
            $datas = [];
            foreach ($fields_values as $fields_value) {
                $datas[$fields_value['id']] = $fields_value['label'];
            }

            Dropdown::showFromArray('plugin_fields_fields_id', $datas, $opt);

            echo "</td>";
            echo "<td colspan='2'>";
            echo "</td>";
            echo "</tr>";
        }


        echo "<tr class='tab_bg_1'>";
        // SHOW SPECIFIC VALUES
        echo "<td colspan='4'>";
        echo "<div id='show_values'>";
        $this->fields["dropdown"] = false;

        $paramTypeField = ['id' => $this->fields['id'],
            'value' => $this->fields['type'],
            'custom_values' => $this->fields['custom_values'],
            'comment_values' => $this->fields['comment_values'],
            'default_values' => $this->fields['default_values'],
            'max_upload' => $this->fields['max_upload'],
            'regex' => $this->fields['regex'],
            'use_future_date' => $this->fields['use_future_date'],
            'use_date_now' => $this->fields['use_date_now'],
            'additional_number_day' => $this->fields['additional_number_day'],
            'display_type' => $this->fields['display_type'],
            'informations_to_display' => $this->fields['informations_to_display'],
            'item' => $this->fields['item'],
            'type' => $this->fields['type'],
            'drop' => $this->fields["dropdown"],
            'link_to_user' => $this->fields["link_to_user"],
            'readonly' => $this->fields["readonly"],
            'hidden' => $this->fields["hidden"],
            'metademands_id' => $this->fields["plugin_metademands_metademands_id"],
        ];

        $this->getEditValue(
            self::_unserialize($this->fields['custom_values']),
            self::_unserialize($this->fields['comment_values']),
            self::_unserialize($this->fields['default_values']),
            $paramTypeField
        );
        $this->viewTypeField($paramTypeField);

        echo "</div>";
        echo "</td>";
        echo "</tr>";

        $this->showFormButtons(['colspan' => 2]);
        return true;
    }


    /**
     * View options for items or types
     *
     * @param array $options
     *
     * @return void
     * @throws \GlpitestSQLError
     */
    public function viewTypeField($options)
    {
        global $PLUGIN_HOOKS;

        $params['value']       = 0;

        foreach ($options as $key => $value) {
            $params[$key] = $value;
        }

        $allowed_options_types = self::$allowed_options_types;
        $allowed_options_items = self::$allowed_options_items;
        $new_fields = [];

//        if (Plugin::isPluginActive('ldapfields')) {
//            $ldapfields_containers = new PluginLdapfieldsContainer();
//            $ldapfields = $ldapfields_containers->find(['type' => 'dropdown', 'is_active' => true]);
//            if (count($ldapfields) > 0) {
//                foreach ($ldapfields as $ldapfield) {
//                    array_push($allowed_options_types, $ldapfield['name']);
//                }
//            }
//        }

        if (isset($PLUGIN_HOOKS['metademands'])) {
            foreach ($PLUGIN_HOOKS['metademands'] as $plug => $method) {
                if (Plugin::isPluginActive($plug)) {
                    $new_fields = self::addPluginFieldItems($plug);
                    if (is_array($new_fields) && count($new_fields) > 0) {
                        $allowed_options_types = array_merge($allowed_options_types, $new_fields);
                    }
                }
            }
        }

        if (in_array($params['type'], $allowed_options_types)
            || in_array($params['item'], $allowed_options_items)) {

            $metademands = new PluginMetademandsMetademand();
            $metademands->getFromDB($options['metademands_id']);

            if (is_array($new_fields) && in_array($params['value'], $new_fields)) {
                $params['value'] = $params['type'];
            }
            if ($params["type"] === "dropdown") {
                $params['value'] = $params['type'];
            }


            echo "<div id='show_type_fields'>";
            echo "<table width='100%' class='metademands_show_values'>";

            switch ($params["value"]) {
                case 'title':
                    break;
                case 'title-block':
                    break;
                case 'informations':
                    break;
                case 'text':
                    echo PluginMetademandsText::showFieldCustomFields($params);
                    break;
                case 'textarea':
                    break;
                case 'dropdown_meta':
                    break;
                case 'dropdown_object':
                    echo PluginMetademandsDropdownobject::showFieldCustomFields($params);
                    break;
                case 'dropdown':
                    break;
                case 'dropdown_multiple':
                    echo PluginMetademandsDropdownmultiple::showFieldCustomFields($params);
                    break;
                case 'checkbox':
                    break;
                case 'radio':
                    break;
                case 'yesno':
                    break;
                case 'number':
                    break;
                case 'date':
                    echo PluginMetademandsDate::showFieldCustomFields($params);
                    break;
                case 'datetime':
                    echo PluginMetademandsDatetime::showFieldCustomFields($params);
                    break;
                case 'date_interval':
                    echo PluginMetademandsDateinterval::showFieldCustomFields($params);
                    break;
                case 'datetime_interval':
                    echo PluginMetademandsDatetimeInterval::showFieldCustomFields($params);
                    break;
                case 'upload':
                    echo PluginMetademandsUpload::showFieldCustomFields($params);
                    break;
                case 'link':
                    break;
                case 'parent_field':
                    break;
                default:
                    if (isset($PLUGIN_HOOKS['metademands'])) {

                        foreach ($PLUGIN_HOOKS['metademands'] as $plug => $method) {
                            if (Plugin::isPluginActive($plug)) {
                                echo self::showPluginCustomvalues($plug, $params);
                            }
                        }
                    }
                    break;
            }
            echo "</table>";
            echo "</div>";

        }
    }

   /**
    * @param $plugin_metademands_metademands_id
    * @param $canedit
    *
    * @throws \GlpitestSQLError
    */
    private static function listFields($item)
    {
        global $CFG_GLPI;

        $rand = mt_rand();
        $canedit = $item->can($item->getID(), UPDATE);

        if ($canedit) {
            echo "<div id='viewfield" . $item->getType() . $item->getID() . "$rand'></div>\n";

            echo "<script type='text/javascript' >\n";
            echo "function addField" . $item->getType() . $item->getID() . "$rand() {\n";
            $params = ['type' => __CLASS__,
                'parenttype' => get_class($item),
                $item->getForeignKeyField() => $item->getID(),
                'id' => -1];
            Ajax::updateItemJsCode("viewfield" . $item->getType() . $item->getID() . "$rand",
                $CFG_GLPI["root_doc"] . "/ajax/viewsubitem.php",
                $params);
            echo "};";
            echo "</script>\n";
            echo "<div class='center'>" .
                "<a class='submit btn btn-primary' href='javascript:addField" .
                $item->getType() . $item->getID() . "$rand();'>" . __('Add a new field', 'metademands') .
                "</a></div><br>";
        }

        $self = new self();

        $data = $self->find(
            ['plugin_metademands_metademands_id' => $item->getID()],
            ['rank', 'order']
        );

        if (is_array($data) && count($data) > 0) {

            if ($canedit) {
                Html::openMassiveActionsForm('mass' . __CLASS__ . $rand);
                $massiveactionparams = ['item'      => __CLASS__,
                                    'container' => 'mass' . __CLASS__ . $rand];
                Html::showMassiveActions($massiveactionparams);
            }
            echo "<div class='left'>";
            echo "<table class='tab_cadre_fixehov'><tr class='tab_bg_2'>";
            echo "<th class='center b' colspan='12'>" . __('Form fields', 'metademands') . "</th>";
            echo "</tr><tr>";
            if ($canedit) {
                echo "<th width='10'>";
                echo Html::getCheckAllAsCheckbox('mass' . __CLASS__ . $rand);
                echo "</th>";
            }
            echo "<th class='center b'>" . __('ID') . "</th>";
            echo "<th class='center b'>" . __('Label') . "</th>";
            echo "<th class='center b'>" . __('Type') . "</th>";
            echo "<th class='center b'>" . __('Object', 'metademands') . "</th>";
            echo "<th class='center b'>" . __('Mandatory field') . "</th>";
            echo "<th class='center b'>" . __('Link a task to the field', 'metademands') . "</th>";
            echo "<th class='center b'>" . __('Value to check', 'metademands') . "</th>";
            if ($item->fields['is_order'] == 1) {
                echo "<th class='center b'>" . __('Display into the basket', 'metademands') . "</th>";
            }
            echo "<th class='center b'>" . __('Use this field as a ticket field', 'metademands') . "</th>";
            echo "<th class='center b'>" . __('Block', 'metademands') . "</th>";
            echo "<th class='center b'>" . __('Order', 'metademands') . "</th>";
            echo "</tr>";
            // Init navigation list for field items
            Session::initNavigateListItems($self->getType(), self::getTypeName(1));

            foreach ($data as $value) {
                Session::addToNavigateListItems($self->getType(), $value['id']);

                echo "<tr class='tab_bg_1'>";
                if ($canedit) {
                    echo "<td class='center'>";
                    Html::showMassiveActionCheckBox(__CLASS__, $value["id"]);
                    echo "</td>";
                }
                echo "<td>";
//                if ($canedit) {
//                    echo "\n<script type='text/javascript' >\n";
//                    echo "function viewEditField" . $item->getType() . $value['id'] . "$rand() {\n";
//                    $params = ['type' => __CLASS__,
//                        'parenttype' => get_class($item),
//                        $item->getForeignKeyField() => $item->getID(),
//                        'id' => $value["id"]];
//                    Ajax::updateItemJsCode("viewfield" . $item->getType() . $item->getID() . "$rand",
//                        $CFG_GLPI["root_doc"] . "/ajax/viewsubitem.php",
//                        $params);
//                    echo "};";
//                    echo "</script>\n";
//                }
                echo $value['id'];
                echo "</td>";
                $name = $value['name'];
                echo "<td>";
                echo " <a href='" . Toolbox::getItemTypeFormURL(__CLASS__) . "?id=" . $value['id'] . "'>";
                if (empty(trim($name))) {
                    echo __('ID') . " - " . $value['id'];
                } else {
                    echo $name;
                }
                echo "</a>";
                echo "</td>";
                echo "<td>" . self::getFieldTypesName($value['type']);
                //name of parent field
                if ($value['type'] == 'parent_field') {
                    $fieldopt = new PluginMetademandsFieldOption();
                    if($fieldopt->getFromDBByCrit(["plugin_metademands_fields_id" => $value['id']])) {
                        $field = new self();
                        if($field->getFromDB($fieldopt->fields['parent_field_id'])) {
                            if (empty(trim($field->fields['name']))) {
                                echo " ( ID - " . $value['parent_field_id'] . ")";
                            } else {
                                echo " (" . $field->fields['name'] . ")";
                            }
                        }
                    }
                }
                echo "</td>";
                echo "<td>" . self::getFieldItemsName($value['type'], $value['item']) . "</td>";
                echo "<td>";
                if ($value['is_mandatory'] == 1) {
                    echo "<span class='red'>";
                }
                echo Dropdown::getYesNo($value['is_mandatory']);
                if ($value['is_mandatory'] == 1) {
                    echo "</span>";
                }
                echo "</td>";

                echo "<td>";

                $fieldopt = new PluginMetademandsFieldOption();
                if($opts = $fieldopt->find(["plugin_metademands_fields_id" => $value['id']])) {
                    foreach ($opts as $opt) {
                        $tasks = [];
                        if (!empty($opt['plugin_metademands_tasks_id'])) {
                            $tasks[] = $opt['plugin_metademands_tasks_id'];
                        }
                        if (is_array($tasks)) {
                            foreach ($tasks as $k => $task) {
                                echo Dropdown::getDropdownName('glpi_plugin_metademands_tasks', $task);
                                echo "<br>";
                            }
                        }
                    }
                } else {
                    echo Dropdown::EMPTY_VALUE;
                }
                echo "</td>";

                echo "<td>";
                $fieldopt = new PluginMetademandsFieldOption();
                if($opts = $fieldopt->find(["plugin_metademands_fields_id" => $value['id']])) {
                    $nbopts = count($opts);
                    if ($nbopts > 1) {
                        echo __('Multiples', 'metademands');
                    } else {
                        foreach ($opts as $opt) {
                            $data['item'] = $value['item'];
                            $data['type'] = $value['type'];
                            $data['custom_values'] = $value['custom_values'];
                            $data['check_value'] = $opt['check_value'];
                            $data['parent_field_id'] = $opt['parent_field_id'];
                            echo PluginMetademandsFieldOption::getValueToCheck($data);
                        }
                    }

                } else {
                    echo Dropdown::EMPTY_VALUE;
                }
                echo "</td>";
                if ($item->fields['is_order'] == 1) {
                    echo "<td>" . Dropdown::getYesNo($value['is_basket']) . "</td>";
                }
                echo "<td>";

                $searchOption = Search::getOptions('Ticket');
                if (isset($searchOption[$value['used_by_ticket']]['name'])) {
                    echo $searchOption[$value['used_by_ticket']]['name'];
                }
                echo "</td>";

                echo "<td class='center' style='color:white;background-color: #" . self::setColor($value['rank']) . "!important'>";
                echo $value['rank'] . "</td>";
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
    public static function dropdownFieldTypes($name, $param = [])
    {
        global $PLUGIN_HOOKS;

        $p = [];
        foreach ($param as $key => $val) {
            $p[$key] = $val;
        }

        $type_fields = self::$field_types;

        if (isset($PLUGIN_HOOKS['metademands'])) {
            foreach ($PLUGIN_HOOKS['metademands'] as $plug => $method) {
                $new_fields = self::addPluginTextFieldItems($plug);
                if (Plugin::isPluginActive($plug) && is_array($new_fields)) {
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
                        $list_fields[$data_field['id']] = $data_field['name'];
                    }
                }

                if (count($metademands_parent) == 0) {
                    continue;
                } elseif (count($list_fields) == 0) {
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
    public static function getFieldTypesName($value = '')
    {
        global $PLUGIN_HOOKS;

        switch ($value) {
            case 'title':
                return PluginMetademandsTitle::getTypeName();
            case 'title-block':
                return PluginMetademandsTitleblock::getTypeName();
            case 'informations':
                return PluginMetademandsInformation::getTypeName();
            case 'text':
                return PluginMetademandsText::getTypeName();
            case 'textarea':
                return PluginMetademandsTextarea::getTypeName();
            case 'dropdown_meta':
                return PluginMetademandsDropdownmeta::getTypeName();
            case 'dropdown_object':
                return PluginMetademandsDropdownobject::getTypeName();
            case 'dropdown':
                return PluginMetademandsDropdown::getTypeName();
            case 'dropdown_multiple':
                return PluginMetademandsDropdownmultiple::getTypeName();
            case 'checkbox':
                return PluginMetademandsCheckbox::getTypeName();
            case 'radio':
                return PluginMetademandsRadio::getTypeName();
            case 'yesno':
                return PluginMetademandsYesno::getTypeName();
            case 'number':
                return PluginMetademandsNumber::getTypeName();
            case 'date':
                return PluginMetademandsDate::getTypeName();
            case 'datetime':
                return PluginMetademandsDatetime::getTypeName();
            case 'date_interval':
                return PluginMetademandsDateinterval::getTypeName();
            case 'datetime_interval':
                return PluginMetademandsDatetimeInterval::getTypeName();
            case 'upload':
                return PluginMetademandsUpload::getTypeName();
            case 'link':
                return PluginMetademandsLink::getTypeName();
            case 'basket':
                return PluginMetademandsBasket::getTypeName();
            case 'parent_field':
                return __('Father\'s field', 'metademands');
            default:
                if (isset($PLUGIN_HOOKS['metademands'])) {
                    foreach ($PLUGIN_HOOKS['metademands'] as $plug => $method) {
                        $new_fields = self::getPluginFieldTypesName($plug);

                        if (Plugin::isPluginActive($plug)
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
    public static function addPluginCaseCustomFields($plug, $name, $p)
    {
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
                if ($item && is_callable([$item, 'addCaseCustomFields'])) {
                    return $item->addCaseCustomFields($name, $p);
                }
            }
        }
    }

    /**
     * Load fields from plugins
     *
     * @param $plug
     */
    public static function showPluginCustomvalues($plug, $params)
    {
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
                if ($item && is_callable([$item, 'showCustomvalues'])) {
                     $item->showCustomvalues($params);
                }
            }
        }
    }

    /**
     * Load fields from plugins
     *
     * @param $plug
     */
    public static function showPluginFieldCase($plug, $metademands_data, $data, $on_order = false, $itilcategories_id = 0, $idline = 0)
    {
        global $PLUGIN_HOOKS;

        $dbu = new DbUtils();

        if (isset($PLUGIN_HOOKS['metademands'][$plug])) {
            $pluginclasses = $PLUGIN_HOOKS['metademands'][$plug];

            foreach ($pluginclasses as $pluginclass) {
                if (!class_exists($pluginclass)) {
                    continue;
                }

                $item               = $dbu->getItemForItemtype($pluginclass);
                if ($item && is_callable([$item, 'showFieldCase'])) {

                    $item->showFieldCase($metademands_data, $data, $on_order = false, $itilcategories_id = 0, $idline = 0);
                }
            }
        }
    }


   /**
    * Load fields from plugins
    *
    * @param $plug
    */
    public static function addPluginFieldItems($plug)
    {
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
    public static function addPluginDropdownFieldItems($plug)
    {
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
    public static function addPluginDropdownMultipleFieldItems($plug)
    {
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
                if ($item && is_callable([$item, 'addDropdownMultipleFieldItems'])) {
                    return $item->addDropdownMultipleFieldItems();
                }
            }
        }
    }



    /**
     * Load fields from plugins
     *
     * @param $plug
     *
     * @return void
     */
    public static function addPluginFieldTypeValue($plug)
    {
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
                if ($item && is_callable([$item, 'addFieldTypeValue'])) {
                    return $item->addFieldTypeValue();
                }
            }
        }
    }

    /**
     * Load fields from plugins
     *
     * @param $plug
     *
     * @return void
     */
    public static function addPluginTextFieldItems($plug)
    {
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
    public static function getPluginFieldTypesName($plug)
    {
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
                if ($item && is_callable([$item, 'getFieldTypesName'])) {
                    return $item->getFieldTypesName();
                }
            }
        }
    }

    /**
     * Load fields from plugins
     *
     * @param $plug
     */
    public static function getPluginFieldItemsName($plug)
    {
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
    * saves data fields option from plugins
    *
    * @param $plug
    */
    public static function getPluginSaveOptions($plug, $params)
    {
        global $PLUGIN_HOOKS;

        $dbu = new DbUtils();
        if (isset($PLUGIN_HOOKS['metademands'][$plug])) {
            if (Plugin::isPluginActive($plug)) {
                $pluginclasses = $PLUGIN_HOOKS['metademands'][$plug];

                foreach ($pluginclasses as $pluginclass) {
                    if (!class_exists($pluginclass)) {
                        continue;
                    }
                    $form[$pluginclass] = [];
                    $item               = $dbu->getItemForItemtype($pluginclass);
                    if ($item && is_callable([$item, 'saveOptions'])) {
                        return $item->saveOptions($params);
                    }
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
    public static function dropdownFieldItems($name, $typefield, $param = [])
    {
        global $PLUGIN_HOOKS;

        $p = [];
        foreach ($param as $key => $val) {
            $p[$key] = $val;
        }

        $type_fields          = PluginMetademandsDropdownmeta::$dropdown_meta_items;
        $type_fields_multiple = PluginMetademandsDropdownmultiple::$dropdown_multiple_items;

        switch ($typefield) {
            case "dropdown_multiple":
                foreach ($type_fields_multiple as $key => $items) {
                    if (empty($items)) {
                        $options[$key] = self::getFieldItemsName("dropdown_multiple", $items);
                    } else {
                        $options[$items] = self::getFieldItemsName("dropdown_multiple",$items);
                    }
                }
                if (isset($PLUGIN_HOOKS['metademands'])) {
                    foreach ($PLUGIN_HOOKS['metademands'] as $plug => $method) {
                        $new_fields = self::addPluginDropdownMultipleFieldItems($plug);
                        if (Plugin::isPluginActive($plug) && is_array($new_fields)) {
                            $options = array_merge_recursive($options, $new_fields);
                        }
                    }
                }
                return Dropdown::showFromArray($name, $options, $p);
                break;
            case "dropdown":
                $options = Dropdown::getStandardDropdownItemTypes();
                return Dropdown::showFromArray($name, $options, $p);
                break;
            case "dropdown_meta":
                foreach ($type_fields as $key => $items) {
                    if (empty($items)) {
                        $options[$key] = self::getFieldItemsName("dropdown_meta", $items);
                    } else {
                        $options[$items] = self::getFieldItemsName("dropdown_meta", $items);
                    }
                }
                if (isset($PLUGIN_HOOKS['metademands'])) {
                    foreach ($PLUGIN_HOOKS['metademands'] as $plug => $method) {
                        $new_fields = self::addPluginDropdownFieldItems($plug);
                        if (Plugin::isPluginActive($plug) && is_array($new_fields)) {
                            $options = array_merge_recursive($options, $new_fields);
                        }
                    }
                }
                return Dropdown::showFromArray($name, $options, $p);
                break;
            case "dropdown_object":
                $options = self::getGlpiObject();
                return Dropdown::showFromArray($name, $options, $p);
                break;
            case "basket":
                $options = new PluginMetademandsBasketobjecttype();
                return $options->Dropdown(["name" => $name, 'value' => $p['value']]);
                break;
            default :

                if (isset($PLUGIN_HOOKS['metademands'])) {
                    foreach ($PLUGIN_HOOKS['metademands'] as $plug => $method) {
                        $newcase = self::addPluginCaseCustomFields($plug, $name, $p);
                    }
                    return $newcase;
                }
                break;
        }
    }

   /**
    * get field items name
    *
    * @param string $value
    *
    * @return string item
    */
    public static function getFieldItemsName($type = '', $value = '')
    {
        global $PLUGIN_HOOKS;

        switch ($type) {
            case 'basket':
                $basketobject = new PluginMetademandsBasketobjecttype();
                $name = Dropdown::EMPTY_VALUE;
                if ($basketobject->getFromDB($value)) {
                    $name = $basketobject->getName();
                }
                return $name;
        }

        switch ($value) {
            case 'other':
                return __('Other');
            case 'ITILCategory_Metademands':
                return __('Category of the metademand', 'metademands');
            case 'mydevices':
                return __('My devices');
            case 'urgency':
                return __('Urgency');
            case 'impact':
                return __('Impact');
            case 'priority':
                return __('Priority');
            case 'user':
                return __('User');
            case 'appliance':
                return __('Appliance');
            default:
                if (isset($PLUGIN_HOOKS['metademands'])) {
                    foreach ($PLUGIN_HOOKS['metademands'] as $plug => $method) {
                        $new_fields = self::getPluginFieldItemsName($plug);
                        if (Plugin::isPluginActive($plug)
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
                $dbu = new DbUtils();
                if (!is_numeric($value)) {
                    if ($item = $dbu->getItemForItemtype($value)) {
                        if (is_callable([$item, 'getTypeName'])) {
                            return $item::getTypeName();
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
    public static function getPluginFieldItemsType($plug)
    {
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
    public static function displayFieldByType($metademands_data, $data, $preview = false, $config_link = "", $itilcategories_id = 0)
    {
        global $PLUGIN_HOOKS;

        $required = "";
//        if ($data['is_mandatory'] == 1 && $data['type'] != 'parent_field') {
//            $required = "required=required style='color:red'";
//        }

        $upload = "";
        if ($data['type'] == "upload") {
            $max = "";
            if ($data["max_upload"] > 0) {
                $max = "( " . sprintf(__("Maximum number of documents : %s ", "metademands"), $data["max_upload"]) . ")";
            }

            $upload = "$max (" . Document::getMaxUploadSize() . ")";
        }
//        if ($data['is_mandatory'] == 1) {
//            $required = "style='color:red'";
//        }

        if (empty($label = self::displayField($data['id'], 'name'))) {
            $label = $data['name'];
        }
        if ($data["use_date_now"] == true) {
            if ($data["type"] == 'date' ||
             $data["type"] == 'date_interval'
            ) {
                $date          = date("Y-m-d");
                $addDays       = $data['additional_number_day'];
                $data['value'] = date('Y-m-d', strtotime($date . " + $addDays days"));
            }
            if ($data["type"] == 'datetime' ||
             $data["type"] == 'datetime_interval'
            ) {
                $addDays       = $data['additional_number_day'];
                $startDate     = time();
                $data['value'] = date('Y-m-d H:i:s', strtotime("+$addDays day", $startDate));
            }
        }
        $hidden = $data['hidden'];
        if ($hidden == 1 && isset($_SESSION['glpiactiveprofile']['interface'])
            && $_SESSION['glpiactiveprofile']['interface'] == 'central') {
            $hidden = 0;
        }

        if ($data['hide_title'] == 0) {

            if ($hidden == 0) {
                echo "<span $required class='col-form-label metademand-label'>";
                echo $label . " $upload";
                if ($preview) {
                    echo $config_link;
                }
                echo "</span>";

                if (empty($comment = self::displayField($data['id'], 'comment'))) {
                    $comment = $data['comment'];
                }
                if ($data['type'] != "title"
                    && $data['type'] != "informations"
                    && $data['type'] != "title-block"
                    && $data['type'] != "text"
                    && !empty($comment)) {
                    $display = true;
                    if ($data['use_richtext'] == 0) {
                        $display = false;
                    }
                    if ($display) {
                        echo "&nbsp;";
                        echo Html::showToolTip(Glpi\RichText\RichText::getSafeHtml($comment), ['display' => false]);
                    }
                }
                echo "<span class='metademands_wizard_red' id='metademands_wizard_red" . $data['id'] . "'>";
                if ($data['is_mandatory'] == 1
                    && $data['type'] != 'parent_field') {
                    echo "*";
                }
                echo "</span>";

                echo "&nbsp;";

                //use plugin fields types
                if (isset($PLUGIN_HOOKS['metademands'])) {
                    foreach ($PLUGIN_HOOKS['metademands'] as $plug => $method) {
                        $new_fields = self::getPluginFieldItemsType($plug);
                        if (Plugin::isPluginActive($plug) && is_array($new_fields)) {
                            if (in_array($data['type'], array_keys($new_fields))) {
                                $data['type'] = $new_fields[$data['type']];
                            }
                        }
                    }
                }

                // Input
                if ($data['type'] != 'link') {
                    echo "<br>";
                }
            }
        } else {
            echo "<div style='margin-top: 10px;'>";
            if ($preview) {
                echo $config_link;
            }
        }
        echo self::getFieldInput($metademands_data, $data, false, $itilcategories_id, 0);
        if ($data['hide_title'] == 1) {
            echo "</div>";
        }
    }


   /**
    * @param      $metademands_data
    * @param      $data
    * @param bool $on_order
    * @param int  $itilcategories_id
    *
    * @param int  $idline
    *
    * @return int|mixed|String
    */
    public static function getFieldInput($metademands_data, $data, $on_order = false, $itilcategories_id = 0, $idline = 0)
    {
        global $PLUGIN_HOOKS;

        $metademand = new PluginMetademandsMetademand();
        $metademand->getFromDB($data['plugin_metademands_metademands_id']);

        $field = '';
        $value = '';
        if (isset($data['value'])) {
            $value = $data['value'];
        }

        if ($on_order == false) {
            $namefield = 'field';
        } else {
            $namefield = 'field_basket_' . $idline;
        }

        switch ($data['type']) {

            case 'title':
                break;
            case 'title-block':
                break;
            case 'informations':
                PluginMetademandsInformation::showWizardField($data, $namefield, $value, $on_order);
                break;
            case 'text':
                PluginMetademandsText::showWizardField($data, $namefield, $value, $on_order);
                break;
            case 'textarea':
                PluginMetademandsTextarea::showWizardField($data, $namefield, $value, $on_order);
                break;
            case 'dropdown_meta':
                PluginMetademandsDropdownmeta::showWizardField($data, $namefield, $value, $on_order, $itilcategories_id);
                break;
            case 'dropdown_object':
                PluginMetademandsDropdownobject::showWizardField($data, $namefield, $value, $on_order, $itilcategories_id);
                break;
            case 'dropdown':
                PluginMetademandsDropdown::showWizardField($data, $namefield, $value, $on_order, $itilcategories_id);
                break;
            case 'dropdown_multiple':
                PluginMetademandsDropdownmultiple::showWizardField($data, $namefield, $value, $on_order);
                break;
            case 'checkbox':
                PluginMetademandsCheckbox::showWizardField($data, $namefield, $value, $on_order);
                break;
            case 'radio':
                PluginMetademandsRadio::showWizardField($data, $namefield, $value, $on_order);
                break;
            case 'yesno':
                PluginMetademandsYesno::showWizardField($data, $namefield, $value, $on_order);
                break;
            case 'number':
                PluginMetademandsNumber::showWizardField($data, $namefield, $value, $on_order);
                break;
            case 'date':
                PluginMetademandsDate::showWizardField($data, $namefield, $value, $on_order);
                break;
            case 'date_interval':
                PluginMetademandsDateinterval::showWizardField($data, $namefield, $value, $on_order);
                break;
            case 'datetime':
                PluginMetademandsDatetime::showWizardField($data, $namefield, $value, $on_order);
                break;
            case 'datetime_interval':
                PluginMetademandsDatetimeinterval::showWizardField($data, $namefield, $value, $on_order);
                break;
            case 'upload':
                PluginMetademandsUpload::showWizardField($data, $namefield, $value, $on_order, $idline);
                break;
            case 'link':
                PluginMetademandsLink::showWizardField($data, $namefield, $value, $on_order);
                break;
            case 'basket':
                PluginMetademandsBasket::showWizardField($data, $on_order, $itilcategories_id, $idline);
                break;
            case 'parent_field':
                foreach ($metademands_data as $metademands_data_steps) {
                    foreach ($metademands_data_steps as $line_data) {
                        foreach ($line_data['form'] as $field_id => $field_value) {

                            if (isset($data['options'])) {
                                $opts = $data['options'];

                                    if (isset($opts[0]['parent_field_id'])) {

                                        $value_parent_field = '';
                                        $parent_field_id = 0;
                                        if (isset($opts[0]['parent_field_id'])) {
                                            $parent_field_id = $opts[0]['parent_field_id'];
                                        }

                                    if (isset($line_data['form'][$parent_field_id]['type'])
                                        && isset($_SESSION['plugin_metademands'][$metademand->getID()]['fields'][$parent_field_id])) {
                                            if (isset($_SESSION['plugin_metademands'][$metademand->getID()]['fields'][$parent_field_id])) {
                                                $value = $_SESSION['plugin_metademands'][$metademand->getID()]['fields'][$parent_field_id];
                                            } else {
                                                $value = 0;
                                            }

                                            switch ($line_data['form'][$parent_field_id]['type']) {
                                                case 'dropdown_multiple':
                                                    if (!empty($line_data['form'][$parent_field_id]['custom_values'])) {
                                                        $value_parent_field = $line_data['form'][$parent_field_id]['custom_values'][$parent_field_id];
                                                    }
                                                    break;
                                                case 'dropdown':
                                                case 'dropdown_object':
                                                case 'dropdown_meta':
                                                    if (!empty($line_data['form'][$value_parent_field]['custom_values'])
                                                        && $line_data['form'][$value_parent_field]['item'] == 'other') {
                                                        $value_parent_field = $line_data['form'][$parent_field_id]['custom_values'][$parent_field_id];
                                                    } else {
                                                        switch ($line_data['form'][$parent_field_id]['item']) {
                                                            case 'User':
                                                                $value_parent_field = "<input type='hidden' name='" . $namefield . "[" . $data['id'] . "]' value='" . $value . "'>";
                                                                $user = new User();
                                                                $user->getFromDB($value);
                                                                $value_parent_field .= $user->getName();
                                                                break;
                                                            default:
                                                                $dbu = new DbUtils();
                                                                $value_parent_field = "<input type='hidden' name='" . $namefield . "[" . $data['id'] . "]' value='" . $value . "'>";
                                                                $value_parent_field .= Dropdown::getDropdownName(
                                                                    $dbu->getTableForItemType($line_data['form'][$parent_field_id]['item']),
                                                                    $value
                                                                );
                                                                break;
                                                        }
                                                    }
                                                    break;
                                                case 'checkbox':
                                                    if (!empty($line_data['form'][$parent_field_id]['custom_values'])) {
                                                        $line_data['form'][$parent_field_id]['custom_values'] = self::_unserialize($line_data['form'][$parent_field_id]['custom_values']);
                                                        foreach ($line_data['form'][$parent_field_id]['custom_values'] as $k => $val) {
                                                            if (!empty($ret = self::displayField($line_data['form'][$parent_field_id]["id"], "custom" . $k))) {
                                                                $line_data['form'][$parent_field_id]['custom_values'][$k] = $ret;
                                                            }
                                                        }
                                                        $checkboxes = self::_unserialize($value);

                                                        $custom_checkbox = [];
                                                        $value_parent_field = "";
                                                        foreach ($line_data['form'][$parent_field_id]['custom_values'] as $key => $label) {
                                                            $checked = isset($checkboxes[$key]) ? 1 : 0;
                                                            if ($checked) {
                                                                $custom_checkbox[] .= $label;
                                                                $value_parent_field .= "<input type='hidden' name='" . $namefield . "[" . $data['id'] . "][" . $key . "]' value='checkbox'>";
                                                            }
                                                        }
                                                        $value_parent_field .= implode('<br>', $custom_checkbox);
                                                    }
                                                    break;

                                                case 'radio':
                                                    if (!empty($line_data['form'][$parent_field_id]['custom_values'])) {
                                                        $line_data['form'][$parent_field_id]['custom_values'] = self::_unserialize($line_data['form'][$parent_field_id]['custom_values']);
                                                        foreach ($line_data['form'][$parent_field_id]['custom_values'] as $k => $val) {
                                                            if (!empty($ret = self::displayField($line_data['form'][$parent_field_id]["id"], "custom" . $k))) {
                                                                $line_data['form'][$parent_field_id]['custom_values'][$k] = $ret;
                                                            }
                                                        }
                                                        foreach ($line_data['form'][$parent_field_id]['custom_values'] as $key => $label) {
                                                            if ($value == $key) {
                                                                $value_parent_field = "<input type='hidden' name='" . $namefield . "[" . $data['id'] . "]' value='$key' >";
                                                                $value_parent_field .= $label;
                                                                break;
                                                            }
                                                        }
                                                    }
                                                    break;

                                                case 'date':
                                                    $value_parent_field = "<input type='hidden' name='" . $namefield . "[" . $data['id'] . "]' value='" . $value . "'>";
                                                    $value_parent_field .= Html::convDate($value);
                                                    break;

                                                case 'datetime':
                                                    $value_parent_field = "<input type='hidden' name='" . $namefield . "[" . $data['id'] . "]' value='" . $value . "'>";
                                                    $value_parent_field .= Html::convDateTime($value);
                                                    break;

                                                case 'date_interval':
                                                    $value_parent_field = "<input type='hidden' name='" . $namefield . "[" . $data['id'] . "]' value='" . $value . "'>";
                                                    if (isset($_SESSION['plugin_metademands'][$metademand->getID()]['fields'][$data['parent_field_id'] . "-2"])) {
                                                        $value2 = $_SESSION['plugin_metademands'][$metademand->getID()]['fields'][$parent_field_id . "-2"];
                                                        $value_parent_field .= "<input type='hidden' name='" . $namefield . "[" . $data['id'] . "-2]' value='" . $value2 . "'>";
                                                    } else {
                                                        $value2 = 0;
                                                    }
                                                    $value_parent_field .= Html::convDate($value) . " - " . Html::convDate($value2);
                                                    break;

                                                case 'datetime_interval':
                                                    $value_parent_field = "<input type='hidden' name='" . $namefield . "[" . $data['id'] . "]' value='" . $value . "'>";
                                                    if (isset($_SESSION['plugin_metademands'][$metademand->getID()]['fields'][$data['parent_field_id'] . "-2"])) {
                                                        $value2 = $_SESSION['plugin_metademands'][$metademand->getID()]['fields'][$parent_field_id . "-2"];
                                                        $value_parent_field .= "<input type='hidden' name='" . $namefield . "[" . $data['id'] . "-2]' value='" . $value2 . "'>";
                                                    } else {
                                                        $value2 = 0;
                                                    }
                                                    $value_parent_field .= Html::convDateTime($value) . " - " . Html::convDateTime($value2);
                                                    break;
                                                case 'yesno':
                                                    $value_parent_field = "<input type='hidden' name='" . $namefield . "[" . $data['id'] . "]' value='" . $value . "'>";
                                                    $value_parent_field .= Dropdown::getYesNo($value);
                                                    break;
                                                case 'basket':

                                                    break;
                                                default:
                                                    $value_parent_field = "<input type='hidden' name='" . $namefield . "[" . $data['id'] . "]' value='" . $value . "'>";
                                            }
                                        }
                                        $field .= $value_parent_field;
                                        break;
                                    }
//                                }
                            }
                        }
                    }
                }
                break;
            default:
                //plugin case
                if (isset($PLUGIN_HOOKS['metademands'])) {
                    $hooks_plugins = $PLUGIN_HOOKS['metademands'];
                    foreach ($hooks_plugins as $plug => $pluginclass) {
                        if (Plugin::isPluginActive($plug)) {
                            echo self::showPluginFieldCase($plug, $metademands_data, $data, $on_order = false, $itilcategories_id = 0, $idline = 0);
                        }
                    }
                }
                break;
        }
//        if ($on_order == false) {
            echo $field;
//        } else {
//            return $field;
//        }
    }


   /**
    * @param        $entity
    * @param        $userid
    * @param string $filter
    * @param bool   $first
    *
    * @return array|int|mixed
    */
    public static function getUserGroup($entity, $userid, $cond = '', $first = true)
    {
        global $DB;

        $dbu = new DbUtils();

        $where = [];
        if ($cond) {
            $where = $cond;
        }

        $query = ['FIELDS'     => ['glpi_groups' => ['id']],
                'FROM'       => 'glpi_groups_users',
                'INNER JOIN' => ['glpi_groups' => ['FKEY' => ['glpi_groups'       => 'id',
                                                              'glpi_groups_users' => 'groups_id']]],
                'WHERE'      => ['users_id' => $userid,
                                 $dbu->getEntitiesRestrictCriteria('glpi_groups', '', $entity, true),
                                ] + $where];

        $rep = [];
        foreach ($DB->request($query) as $data) {
            if ($first) {
                return $data['id'];
            }
            $rep[] = $data['id'];
        }
        return ($first ? 0 : $rep);
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
    public static function showSimpleForm(
        $action,
        $btname,
        $btlabel,
        array $fields = [],
        $btimage = '',
        $btoption = '',
        $confirm = ''
    ) {
        return Html::getSimpleForm($action, $btname, $btlabel, $fields, $btimage, $btoption, $confirm);
    }



   /**
    * @param $url
    */
//    public function addNewOpt($url)
//    {
//        $res = "<script type='text/javascript'>
//
//      let root_metademands_doc = '" . PLUGIN_METADEMANDS_WEBDIR . "';
//
//                $('#addNewOpt').click(function() {
//                    let nb = document.getElementById('nbOptions').valueOf().value;
//                    nb++;
//                    parent.parent.window.location.replace(root_metademands_doc + '/front/" . $url . "&nbOpt='+nb);
//                });
//                </script>";
//        echo $res;
//    }



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
    public function getEditValue($values = [], $comment = [], $default = [], $options = [])
    {
        $params['value'] = 0;
        $params['item']  = '';
        $params['type']  = '';

        foreach ($options as $key => $value) {
            $params[$key] = $value;
        }

        $allowed_custom_types = self::$allowed_custom_types;
        $allowed_custom_items = self::$allowed_custom_items;

        if (in_array($params['value'], $allowed_custom_types)
          || in_array($params['item'], $allowed_custom_items)) {
            echo "<table width='100%' class='metademands_show_values'>";
            if ($params['value'] != "dropdown_multiple"
                && $params['item'] != 'User') {
                echo "<tr><th colspan='4'>" . __('Custom values', 'metademands') . "</th></tr>";
            }

            echo "<tr><td>";
            echo '<table width=\'100%\' class="tab_cadre">';
            if ($params["type"] == "dropdown_multiple" && empty($params["item"])) {
                $params["item"] = "other";
            }

            if ($params["type"] == "radio") {
                $params["item"] = "radio";
            }
            if ($params["type"] == "checkbox") {
                $params["item"] = "checkbox";
            }

            if ($params["type"] != "dropdown_multiple") {
                switch ($params['item']) {
                    case 'other':
                        PluginMetademandsDropdownmeta::showFieldCustomValues($values, $key, $params);
                        break;
                    default:
                        break;
                }
            }

            switch ($params['type']) {
                case 'title':
                    break;
                case 'title-block':
                    break;
                case 'informations':
                    break;
                case 'text':
                    break;
                case 'textarea':
                    break;
                case 'dropdown_meta':
                    break;
                case 'dropdown_object':
                    break;
                case 'dropdown':
                    break;
                case 'dropdown_multiple':
                    PluginMetademandsDropdownmultiple::showFieldCustomValues($values, $key, $params);
                    break;
                case 'checkbox':
                    PluginMetademandsCheckbox::showFieldCustomValues($values, $key, $params);
                    break;
                case 'radio':
                    PluginMetademandsRadio::showFieldCustomValues($values, $key, $params);
                    break;
                case 'yesno':
                    PluginMetademandsYesno::showFieldCustomValues($values, $key, $params);
                    break;
                case 'number':
                    PluginMetademandsNumber::showFieldCustomValues($values, $key, $params);
                    break;
                case 'date':
                    break;
                case 'date_interval':
                    break;
                case 'datetime':
                    break;
                case 'datetime_interval':
                    break;
                case 'upload':
                    break;
                case 'link':
                    PluginMetademandsLink::showFieldCustomValues($values, $key, $params);
                    break;
                case 'basket':
                    PluginMetademandsBasket::showFieldCustomValues($values, $key, $params);
                    break;
                case 'parent_field':
                    break;
            }

            echo '</table>';
            echo "</td></tr></table>";
        }
    }


    public function reorderArray($targetArray, $indexFrom, $indexTo)
    {
        $targetElement  = $targetArray[$indexFrom];
        $magicIncrement = ($indexTo - $indexFrom) / abs($indexTo - $indexFrom);

        for ($Element = $indexFrom; $Element != $indexTo; $Element += $magicIncrement) {
            $targetArray[$Element] = $targetArray[$Element + $magicIncrement];
        }

        $targetArray[$indexTo] = $targetElement;

        return $targetArray;
    }

   /**
    * @param array $params
    */
    public function reorder(array $params)
    {
        $crit = [
         'id' => $params['field_id'],
        ];

        $itemMove = new self();
        $itemMove->getFromDBByCrit($crit);

        $custom_values = self::_unserialize($itemMove->fields["custom_values"]);
        $default_values = self::_unserialize($itemMove->fields["default_values"]);
        $comment_values = self::_unserialize($itemMove->fields["comment_values"]);

        if (isset($params['old_order']) && isset($params['new_order'])) {
            $old_order = $params['old_order'];
            $new_order = $params['new_order'];

            $old_order = $old_order + 1;
            $new_order = $new_order + 1;

            $new_custom_values = $this->reorderArray($custom_values, $old_order, $new_order);
            $new_default_values = $this->reorderArray($default_values, $old_order, $new_order);
            $new_comment_values = $this->reorderArray($comment_values, $old_order, $new_order);

            $itemMove->update([
                              'id'            => $params['field_id'],
                              'custom_values' => self::_serialize($new_custom_values),
                                'default_values' => self::_serialize($new_default_values),
                                'comment_values' => self::_serialize($new_comment_values)
                           ]);
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
    public static function initCustomValue($count, $display_comment = false, $display_default = false)
    {
        Html::requireJs("metademands");
        $script = "var metademandWizard = $(document).metademandWizard(" . json_encode(['root_doc' => PLUGIN_METADEMANDS_WEBDIR]) . ");";

        echo Html::hidden('display_comment', ['id' => 'display_comment', 'value' => $display_comment]);
        echo Html::hidden('count_custom_values', ['id' => 'count_custom_values', 'value' => $count]);
        echo Html::hidden('display_default', ['id' => 'display_default', 'value' => $display_default]);

        echo "&nbsp;<i class='fa-2x fas fa-plus-square' style='cursor:pointer' 
            onclick='$script metademandWizard.metademands_add_custom_values(\"show_custom_fields\");' 
            title='" . _sx("button", "Add") . "'/></i>&nbsp;";
    }

   /**
    * @param $valueId
    * @param $display_comment
    * @param $display_default
    */
    public static function addNewValue($valueId, $display_comment, $display_default)
    {
        $valueId = $valueId + 1;
        echo '<table width=\'100%\' class="tab_cadre">';
        echo "<tr>";

        echo "<td id='show_custom_fields'>";
        echo '<p id=\'custom_values' . $valueId . '\'>';
        echo __('Value') . ' ' . $valueId . ' ';
        $name = "custom_values[$valueId]";
        echo Html::input($name, ['size' => 50]);
        echo "</td>";
        echo '</p>';

        echo "<td id='show_custom_fields'>";
        echo '<p id=\'comment_values' . $valueId . '\'>';
        if ($display_comment) {
            echo " " . __('Comment') . " ";
            $name = "comment_values[$valueId]";
            echo Html::input($name, ['size' => 30]);
        }
        echo '</p>';
        echo "</td>";

        echo "<td id='show_custom_fields'>";
        echo '<p id=\'default_values' . $valueId . '\'>';
        if ($display_default) {
            echo " " . _n('Default value', 'Default values', 1, 'metademands') . " ";
           //         echo '<input type="checkbox" name="default_values[' . $valueId . ']"  value="1"/>';
            $name  = "default_values[$valueId]";
            $value = 0;
            Dropdown::showYesNo($name, $value);
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
    public static function _serialize($input)
    {
        if ($input != null || $input == []) {
            if (is_array($input)) {
                foreach ($input as &$value) {
                    if ($value != null) {
                        $value = urlencode(Html::cleanPostForTextArea($value));
                    }
                }

                return json_encode($input);
            }
        }
    }

   /**
    * @param $input
    *
    * @return mixed
    */
    public static function _unserialize($input)
    {
        if (!empty($input)) {
            if (!is_array($input)) {
                $input = json_decode($input, true);
            }
            if (is_array($input) && !empty($input)) {
                foreach ($input as &$value) {
                    $value = urldecode($value);
                }
            }
        }

        return $input;
    }

   /**
    * @param $metademands_id
    *
    * @return array
    */
    public function listMetademandsfields($metademands_id)
    {
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
    public function prepareInputForAdd($input)
    {
        if (!$this->checkMandatoryFields($input)) {
            return false;
        }

       //      $meta = new PluginMetademandsMetademand();

       //      if ($meta->getFromDB($input['plugin_metademands_metademands_id'])
       //          && $meta->fields['is_order'] == 1) {
       //         $input['is_basket'] = 1;
       //      }

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
    public function prepareInputForUpdate($input)
    {
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

    public function cleanDBonPurge()
    {

        $temp = new PluginMetademandsTicket_Field();
        $temp->deleteByCriteria(['plugin_metademands_fields_id' => $this->fields['id']]);

        $temp = new PluginMetademandsBasketline();
        $temp->deleteByCriteria(['plugin_metademands_fields_id' => $this->fields['id']]);

        $temp = new PluginMetademandsFieldOption();
        $temp->deleteByCriteria(['plugin_metademands_fields_id' => $this->fields['id']]);

        $temp = new PluginMetademandsFieldOption();
        $temp->deleteByCriteria(['parent_field_id' => $this->fields['id']]);
    }

   /**
    * @param $value
    *
    * @return bool|string
    */
    public static function setColor($value)
    {
        return substr(substr(dechex(($value * 298)), 0, 2) .
                    substr(dechex(($value * 7777)), 0, 3) .
                    substr(dechex(($value * 1)), 0, 1) .
                    substr(dechex(($value * 64)), 0, 1) .
                    substr(dechex(($value * 13)), 0, 1) .
                    substr(dechex(($value * 1)), 0, 1), 0, 6);
    }

   /**
    * @param $input
    *
    * @return bool
    */
    public function checkMandatoryFields($input)
    {
        $msg     = [];
        $checkKo = false;

        $mandatory_fields = ['name'   => __('Label'),
                           'label2' => __('Additional label', 'metademands'),
                           'type'   => __('Type'),
                           'item'   => __('Object', 'metademands')];

        foreach ($input as $key => $value) {
            if (array_key_exists($key, $mandatory_fields)) {
                if (empty($value)) {
                    if (($key == 'item' && ($input['type'] == 'dropdown'
                                       || $input['type'] == 'dropdown_object'
                                       || $input['type'] == 'dropdown_meta'))
                    || ($key == 'label2' && ($input['type'] == 'date_interval' || $input['type'] == 'datetime_interval'))) {
                        $msg[]   = $mandatory_fields[$key];
                        $checkKo = true;
                    } elseif ($key != 'item' && $key != 'label2') {
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
    public function rawSearchOptions()
    {
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
         'id'       => '817',
         'table'    => $this->getTable(),
         'field'    => 'label2',
         'name'     => __('Additional label', 'metademands'),
         'datatype' => 'text'
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
    public static function getSpecificValueToSelect($field, $name = '', $values = '', array $options = [])
    {
        if (!is_array($values)) {
            $values = [$field => $values];
        }
        $options['display'] = false;
        switch ($field) {
            case 'rank':
                $options['min'] = 1;
                $options['max'] = self::MAX_FIELDS;

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
    public function showOrderDropdown($rank, $fields_id, $previous_fields_id, $metademands_id)
    {
        if (empty($rank)) {
            $rank = 1;
        }
        $restrict = ['rank' => $rank, 'plugin_metademands_metademands_id' => $metademands_id];
       //      $restrict += ['NOT' => ['type' => 'title-block']];
        if (!empty($fields_id)) {
            $restrict += ['NOT' => ['id' => $fields_id]];
        }

        $order = [Dropdown::EMPTY_VALUE];

        foreach ($this->find($restrict, ['order']) as $id => $values) {
            $order[$id] = $values['name'];
            //if (!empty($values['label2'])) {
            //   $order[$id] .= ' - ' . $values['label2'];
            //}
            if (empty(trim($order[$id]))) {
                $order[$id] = __('ID') . " - " . $id;
            }
        }
        Dropdown::showFromArray('plugin_metademands_fields_id', $order, ['value' => $previous_fields_id]);
    }

   /**
    * @param $input
    */
    public function recalculateOrder($input)
    {
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
        foreach ($this->find(
            ['rank'                              => $input['rank'],
            'plugin_metademands_metademands_id' => $input["plugin_metademands_metademands_id"]],
            ['order']
        ) as $fields_id => $values) {
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


   /**
    * Returns the translation of the field
    *
    * @param type  $item
    * @param type  $field
    *
    * @return type
    * @global type $DB
    *
    */
    public static function displayField($id, $field, $lang = '')
    {
        global $DB;

        $res = "";
        // Make new database object and fill variables
        $iterator = $DB->request([
                                  'FROM'  => 'glpi_plugin_metademands_fieldtranslations',
                                  'WHERE' => [
                                     'itemtype' => self::getType(),
                                     'items_id' => $id,
                                     'field'    => $field,
                                     'language' => $_SESSION['glpilanguage']
                                  ]]);
        if ($lang != $_SESSION['glpilanguage'] && $lang != '') {
            $iterator2 = $DB->request([
                                      'FROM'  => 'glpi_plugin_metademands_fieldtranslations',
                                      'WHERE' => [
                                         'itemtype' => self::getType(),
                                         'items_id' => $id,
                                         'field'    => $field,
                                         'language' => $lang
                                      ]]);
        }


        if (count($iterator)) {
            foreach ($iterator as $data) {
                $res = $data['value'];
            }
        }
        if ($lang != $_SESSION['glpilanguage'] && $lang != '') {
            if (count($iterator2)) {
                foreach ($iterator2 as $data2) {
                    $res .= ' / ' . $data2['value'];
                    $iterator2->next();
                }
            }
        }
        return $res;
    }


   /**
    * @return array
    */
   /**
    * @return array
    */
    public function getForbiddenStandardMassiveAction()
    {
        $forbidden = parent::getForbiddenStandardMassiveAction();

        $forbidden[] = 'merge';
        $forbidden[] = 'add_transfer_list';
        $forbidden[] = 'amend_comment';

        return $forbidden;
    }

   /**
    * @return array[]
    */
    public static function getGlpiObject()
    {
        $optgroup = [
         __("Assets")         => [
            Computer::class         => Computer::getTypeName(2),
            Monitor::class          => Monitor::getTypeName(2),
            Software::class         => Software::getTypeName(2),
            Networkequipment::class => Networkequipment::getTypeName(2),
            Peripheral::class       => Peripheral::getTypeName(2),
            Printer::class          => Printer::getTypeName(2),
            CartridgeItem::class    => CartridgeItem::getTypeName(2),
            ConsumableItem::class   => ConsumableItem::getTypeName(2),
            Phone::class            => Phone::getTypeName(2),
            Line::class             => Line::getTypeName(2)],
         __("Assistance")     => [
            Ticket::class          => Ticket::getTypeName(2),
            Problem::class         => Problem::getTypeName(2),
            TicketRecurrent::class => TicketRecurrent::getTypeName(2)],
         __("Management")     => [
            Budget::class    => Budget::getTypeName(2),
            Supplier::class  => Supplier::getTypeName(2),
            Contact::class   => Contact::getTypeName(2),
            Contract::class  => Contract::getTypeName(2),
            Document::class  => Document::getTypeName(2),
            Project::class   => Project::getTypeName(2),
            Appliance::class => Appliance::getTypeName(2)],
         __("Tools")          => [
            Reminder::class => __("Notes"),
            RSSFeed::class  => __("RSS feed")],
         __("Administration") => [
            User::class    => User::getTypeName(2),
            Group::class   => Group::getTypeName(2),
            Entity::class  => Entity::getTypeName(2),
            Profile::class => Profile::getTypeName(2)],
        ];
        if (class_exists(PassiveDCEquipment::class)) {
            // Does not exists in GLPI 9.4
            $optgroup[__("Assets")][PassiveDCEquipment::class] = PassiveDCEquipment::getTypeName(2);
        }

        return $optgroup;
    }

    public static function getDeviceName($value)
    {
        global $DB, $CFG_GLPI;
        $userID = Session::getLoginUserID();
        $entity_restrict =  $_SESSION['glpiactiveentities'];

        if ($_SESSION["glpiactiveprofile"]["helpdesk_hardware"] & pow(2, Ticket::HELPDESK_MY_HARDWARE)) {
            $my_devices = ['' => Dropdown::EMPTY_VALUE];
            $devices = [];

            // My items
            foreach ($CFG_GLPI["linkuser_types"] as $itemtype) {
                if (($item = getItemForItemtype($itemtype))
                    && Ticket::isPossibleToAssignType($itemtype)) {
                    $itemtable = getTableForItemType($itemtype);

                    $criteria = [
                        'FROM' => $itemtable,
                        'WHERE' => [
                                'users_id' => $userID
                            ] + getEntitiesRestrictCriteria($itemtable, '', $entity_restrict, $item->maybeRecursive()),
                        'ORDER' => $item->getNameField()
                    ];

                    if ($item->maybeDeleted()) {
                        $criteria['WHERE']['is_deleted'] = 0;
                    }
                    if ($item->maybeTemplate()) {
                        $criteria['WHERE']['is_template'] = 0;
                    }
                    if (in_array($itemtype, $CFG_GLPI["helpdesk_visible_types"])) {
                        $criteria['WHERE']['is_helpdesk_visible'] = 1;
                    }

                    $iterator = $DB->request($criteria);
                    $nb = count($iterator);
                    if ($nb > 0) {
                        $type_name = $item->getTypeName($nb);

                        foreach ($iterator as $data) {
                            if (!isset($already_add[$itemtype]) || !in_array($data["id"], $already_add[$itemtype])) {
                                $output = $data[$item->getNameField()];
                                if (empty($output) || $_SESSION["glpiis_ids_visible"]) {
                                    $output = sprintf(__('%1$s (%2$s)'), $output, $data['id']);
                                }
                                $output = sprintf(__('%1$s - %2$s'), $type_name, $output);
                                if ($itemtype != 'Software') {
                                    if (!empty($data['serial'])) {
                                        $output = sprintf(__('%1$s - %2$s'), $output, $data['serial']);
                                    }
                                    if (!empty($data['otherserial'])) {
                                        $output = sprintf(__('%1$s - %2$s'), $output, $data['otherserial']);
                                    }
                                }
                                $devices[$itemtype . "_" . $data["id"]] = $output;

                                $already_add[$itemtype][] = $data["id"];
                            }
                        }
                    }
                }
            }

            if (count($devices)) {
                $my_devices[__('My devices')] = $devices;
            }
            // My group items
            if (Session::haveRight("show_group_hardware", "1")) {
                $iterator = $DB->request([
                    'SELECT' => [
                        'glpi_groups_users.groups_id',
                        'glpi_groups.name'
                    ],
                    'FROM' => 'glpi_groups_users',
                    'LEFT JOIN' => [
                        'glpi_groups' => [
                            'ON' => [
                                'glpi_groups_users' => 'groups_id',
                                'glpi_groups' => 'id'
                            ]
                        ]
                    ],
                    'WHERE' => [
                            'glpi_groups_users.users_id' => $userID
                        ] + getEntitiesRestrictCriteria('glpi_groups', '', $entity_restrict, true)
                ]);

                $devices = [];
                $groups = [];
                if (count($iterator)) {
                    foreach ($iterator as $data) {
                        $a_groups = getAncestorsOf("glpi_groups", $data["groups_id"]);
                        $a_groups[$data["groups_id"]] = $data["groups_id"];
                        $groups = array_merge($groups, $a_groups);
                    }

                    foreach ($CFG_GLPI["linkgroup_types"] as $itemtype) {
                        if (($item = getItemForItemtype($itemtype))
                            && Ticket::isPossibleToAssignType($itemtype)) {
                            $itemtable = getTableForItemType($itemtype);
                            $criteria = [
                                'FROM' => $itemtable,
                                'WHERE' => [
                                        'groups_id' => $groups
                                    ] + getEntitiesRestrictCriteria($itemtable, '', $entity_restrict, $item->maybeRecursive()),
                                'ORDER' => 'name'
                            ];

                            if ($item->maybeDeleted()) {
                                $criteria['WHERE']['is_deleted'] = 0;
                            }
                            if ($item->maybeTemplate()) {
                                $criteria['WHERE']['is_template'] = 0;
                            }

                            $iterator = $DB->request($criteria);
                            if (count($iterator)) {
                                $type_name = $item->getTypeName();
                                if (!isset($already_add[$itemtype])) {
                                    $already_add[$itemtype] = [];
                                }
                                foreach ($iterator as $data) {
                                    if (!in_array($data["id"], $already_add[$itemtype])) {
                                        $output = '';
                                        if (isset($data["name"])) {
                                            $output = $data["name"];
                                        }
                                        if (empty($output) || $_SESSION["glpiis_ids_visible"]) {
                                            $output = sprintf(__('%1$s (%2$s)'), $output, $data['id']);
                                        }
                                        $output = sprintf(__('%1$s - %2$s'), $type_name, $output);
                                        if (isset($data['serial'])) {
                                            $output = sprintf(__('%1$s - %2$s'), $output, $data['serial']);
                                        }
                                        if (isset($data['otherserial'])) {
                                            $output = sprintf(__('%1$s - %2$s'), $output, $data['otherserial']);
                                        }
                                        $devices[$itemtype . "_" . $data["id"]] = $output;

                                        $already_add[$itemtype][] = $data["id"];
                                    }
                                }
                            }
                        }
                    }
                    if (count($devices)) {
                        $my_devices[__('Devices own by my groups')] = $devices;
                    }
                }
            }
            // Get software linked to all owned items
            if (in_array('Software', $_SESSION["glpiactiveprofile"]["helpdesk_item_type"])) {
                $software_helpdesk_types = array_intersect($CFG_GLPI['software_types'], $_SESSION["glpiactiveprofile"]["helpdesk_item_type"]);
                foreach ($software_helpdesk_types as $itemtype) {
                    if (isset($already_add[$itemtype]) && count($already_add[$itemtype])) {
                        $iterator = $DB->request([
                            'SELECT' => [
                                'glpi_softwareversions.name AS version',
                                'glpi_softwares.name AS name',
                                'glpi_softwares.id'
                            ],
                            'DISTINCT' => true,
                            'FROM' => 'glpi_items_softwareversions',
                            'LEFT JOIN' => [
                                'glpi_softwareversions' => [
                                    'ON' => [
                                        'glpi_items_softwareversions' => 'softwareversions_id',
                                        'glpi_softwareversions' => 'id'
                                    ]
                                ],
                                'glpi_softwares' => [
                                    'ON' => [
                                        'glpi_softwareversions' => 'softwares_id',
                                        'glpi_softwares' => 'id'
                                    ]
                                ]
                            ],
                            'WHERE' => [
                                    'glpi_items_softwareversions.items_id' => $already_add[$itemtype],
                                    'glpi_items_softwareversions.itemtype' => $itemtype,
                                    'glpi_softwares.is_helpdesk_visible' => 1
                                ] + getEntitiesRestrictCriteria('glpi_softwares', '', $entity_restrict),
                            'ORDERBY' => 'glpi_softwares.name'
                        ]);

                        $devices = [];
                        if (count($iterator)) {
                            $item = new Software();
                            $type_name = $item->getTypeName();
                            if (!isset($already_add['Software'])) {
                                $already_add['Software'] = [];
                            }
                            foreach ($iterator as $data) {
                                if (!in_array($data["id"], $already_add['Software'])) {
                                    $output = sprintf(__('%1$s - %2$s'), $type_name, $data["name"]);
                                    $output = sprintf(
                                        __('%1$s (%2$s)'),
                                        $output,
                                        sprintf(
                                            __('%1$s: %2$s'),
                                            __('version'),
                                            $data["version"]
                                        )
                                    );
                                    if ($_SESSION["glpiis_ids_visible"]) {
                                        $output = sprintf(__('%1$s (%2$s)'), $output, $data["id"]);
                                    }
                                    $devices["Software_" . $data["id"]] = $output;

                                    $already_add['Software'][] = $data["id"];
                                }
                            }
                            if (count($devices)) {
                                $my_devices[__('Installed software')] = $devices;
                            }
                        }
                    }
                }
            }
            // Get linked items to computers
            if (isset($already_add['Computer']) && count($already_add['Computer'])) {
                $devices = [];

                // Direct Connection
                $types = ['Monitor', 'Peripheral', 'Phone', 'Printer'];
                foreach ($types as $itemtype) {
                    if (in_array($itemtype, $_SESSION["glpiactiveprofile"]["helpdesk_item_type"])
                        && ($item = getItemForItemtype($itemtype))) {
                        $itemtable = getTableForItemType($itemtype);
                        if (!isset($already_add[$itemtype])) {
                            $already_add[$itemtype] = [];
                        }
                        $criteria = [
                            'SELECT' => "$itemtable.*",
                            'DISTINCT' => true,
                            'FROM' => 'glpi_computers_items',
                            'LEFT JOIN' => [
                                $itemtable => [
                                    'ON' => [
                                        'glpi_computers_items' => 'items_id',
                                        $itemtable => 'id'
                                    ]
                                ]
                            ],
                            'WHERE' => [
                                    'glpi_computers_items.itemtype' => $itemtype,
                                    'glpi_computers_items.computers_id' => $already_add['Computer']
                                ] + getEntitiesRestrictCriteria($itemtable, '', $entity_restrict),
                            'ORDERBY' => "$itemtable.name"
                        ];

                        if ($item->maybeDeleted()) {
                            $criteria['WHERE']["$itemtable.is_deleted"] = 0;
                        }
                        if ($item->maybeTemplate()) {
                            $criteria['WHERE']["$itemtable.is_template"] = 0;
                        }

                        $iterator = $DB->request($criteria);
                        if (count($iterator)) {
                            $type_name = $item->getTypeName();
                            foreach ($iterator as $data) {
                                if (!in_array($data["id"], $already_add[$itemtype])) {
                                    $output = $data["name"];
                                    if (empty($output) || $_SESSION["glpiis_ids_visible"]) {
                                        $output = sprintf(__('%1$s (%2$s)'), $output, $data['id']);
                                    }
                                    $output = sprintf(__('%1$s - %2$s'), $type_name, $output);
                                    if ($itemtype != 'Software') {
                                        $output = sprintf(__('%1$s - %2$s'), $output, $data['otherserial']);
                                    }
                                    $devices[$itemtype . "_" . $data["id"]] = $output;

                                    $already_add[$itemtype][] = $data["id"];
                                }
                            }
                        }
                    }
                }
                if (count($devices)) {
                    $my_devices[__('Connected devices')] = $devices;
                }
            }

        }
        $array = explode('_',$value);
        $itemType = $array[0];
        $item_id = $array[1];

        $item = new $itemType();
        $item->getFromDB($item_id);
        $return =  $itemType . " - " . $item->fields['name'] . " (" . $item_id . ")";
        return $return;
    }

   /**
    * Make a select box for Ticket my devices
    *
    * @param integer $userID User ID for my device section (default 0)
    * @param integer $entity_restrict restrict to a specific entity (default -1)
    * @param int     $itemtype of selected item (default 0)
    * @param integer $items_id of selected item (default 0)
    * @param array   $options array of possible options:
    *    - used     : ID of the requester user
    *    - multiple : allow multiple choice
    *
    * @return void
    */
    public static function dropdownMyDevices($userID = 0, $entity_restrict = -1, $itemtype = 0, $items_id = 0, $options = [], $display = true)
    {
        global $DB, $CFG_GLPI;

        $params = ['tickets_id' => 0,
                 'used'       => [],
                 'multiple'   => false,
                 'name'       => 'my_items',
                 'value'      => 0,
                 'rand'       => mt_rand()];

        foreach ($options as $key => $val) {
            $params[$key] = $val;
        }
       //
       //      if ($userID == 0) {
       //         $userID = Session::getLoginUserID();
       //      }

        $rand        = $params['rand'];
        $already_add = $params['used'];

        if ($_SESSION["glpiactiveprofile"]["helpdesk_hardware"] & pow(2, Ticket::HELPDESK_MY_HARDWARE)) {
            $my_devices = ['' => Dropdown::EMPTY_VALUE];
            $devices    = [];

            // My items
            foreach ($CFG_GLPI["linkuser_types"] as $itemtype) {
                if (($item = getItemForItemtype($itemtype))
                && Ticket::isPossibleToAssignType($itemtype)) {
                    $itemtable = getTableForItemType($itemtype);

                    $criteria = [
                    'FROM'  => $itemtable,
                    'WHERE' => [
                                'users_id' => $userID
                             ] + getEntitiesRestrictCriteria($itemtable, '', $entity_restrict, $item->maybeRecursive()),
                    'ORDER' => $item->getNameField()
                    ];

                    if ($item->maybeDeleted()) {
                        $criteria['WHERE']['is_deleted'] = 0;
                    }
                    if ($item->maybeTemplate()) {
                        $criteria['WHERE']['is_template'] = 0;
                    }
                    if (in_array($itemtype, $CFG_GLPI["helpdesk_visible_types"])) {
                        $criteria['WHERE']['is_helpdesk_visible'] = 1;
                    }

                    $iterator = $DB->request($criteria);
                    $nb       = count($iterator);
                    if ($nb > 0) {
                        $type_name = $item->getTypeName($nb);

                        foreach ($iterator as $data) {
                            if (!isset($already_add[$itemtype]) || !in_array($data["id"], $already_add[$itemtype])) {
                                $output = $data[$item->getNameField()];
                                if (empty($output) || $_SESSION["glpiis_ids_visible"]) {
                                    $output = sprintf(__('%1$s (%2$s)'), $output, $data['id']);
                                }
                                $output = sprintf(__('%1$s - %2$s'), $type_name, $output);
                                if ($itemtype != 'Software') {
                                    if (!empty($data['serial'])) {
                                        $output = sprintf(__('%1$s - %2$s'), $output, $data['serial']);
                                    }
                                    if (!empty($data['otherserial'])) {
                                        $output = sprintf(__('%1$s - %2$s'), $output, $data['otherserial']);
                                    }
                                }
                                $devices[$itemtype . "_" . $data["id"]] = $output;

                                $already_add[$itemtype][] = $data["id"];
                            }
                        }
                    }
                }
            }

            if (count($devices)) {
                $my_devices[__('My devices')] = $devices;
            }
            // My group items
            if (Session::haveRight("show_group_hardware", "1")) {
                $iterator = $DB->request([
                                        'SELECT'    => [
                                           'glpi_groups_users.groups_id',
                                           'glpi_groups.name'
                                        ],
                                        'FROM'      => 'glpi_groups_users',
                                        'LEFT JOIN' => [
                                           'glpi_groups' => [
                                              'ON' => [
                                                 'glpi_groups_users' => 'groups_id',
                                                 'glpi_groups'       => 'id'
                                              ]
                                           ]
                                        ],
                                        'WHERE'     => [
                                                          'glpi_groups_users.users_id' => $userID
                                                       ] + getEntitiesRestrictCriteria('glpi_groups', '', $entity_restrict, true)
                                     ]);

                $devices = [];
                $groups  = [];
                if (count($iterator)) {
                    foreach ($iterator as $data) {
                        $a_groups                     = getAncestorsOf("glpi_groups", $data["groups_id"]);
                        $a_groups[$data["groups_id"]] = $data["groups_id"];
                        $groups                       = array_merge($groups, $a_groups);
                    }

                    foreach ($CFG_GLPI["linkgroup_types"] as $itemtype) {
                        if (($item = getItemForItemtype($itemtype))
                        && Ticket::isPossibleToAssignType($itemtype)) {
                            $itemtable = getTableForItemType($itemtype);
                            $criteria  = [
                            'FROM'  => $itemtable,
                            'WHERE' => [
                                      'groups_id' => $groups
                                   ] + getEntitiesRestrictCriteria($itemtable, '', $entity_restrict, $item->maybeRecursive()),
                            'ORDER' => 'name'
                            ];

                            if ($item->maybeDeleted()) {
                                $criteria['WHERE']['is_deleted'] = 0;
                            }
                            if ($item->maybeTemplate()) {
                                $criteria['WHERE']['is_template'] = 0;
                            }

                            $iterator = $DB->request($criteria);
                            if (count($iterator)) {
                                $type_name = $item->getTypeName();
                                if (!isset($already_add[$itemtype])) {
                                    $already_add[$itemtype] = [];
                                }
                                foreach ($iterator as $data) {
                                    if (!in_array($data["id"], $already_add[$itemtype])) {
                                        $output = '';
                                        if (isset($data["name"])) {
                                            $output = $data["name"];
                                        }
                                        if (empty($output) || $_SESSION["glpiis_ids_visible"]) {
                                            $output = sprintf(__('%1$s (%2$s)'), $output, $data['id']);
                                        }
                                        $output = sprintf(__('%1$s - %2$s'), $type_name, $output);
                                        if (isset($data['serial'])) {
                                            $output = sprintf(__('%1$s - %2$s'), $output, $data['serial']);
                                        }
                                        if (isset($data['otherserial'])) {
                                            $output = sprintf(__('%1$s - %2$s'), $output, $data['otherserial']);
                                        }
                                        $devices[$itemtype . "_" . $data["id"]] = $output;

                                        $already_add[$itemtype][] = $data["id"];
                                    }
                                }
                            }
                        }
                    }
                    if (count($devices)) {
                        $my_devices[__('Devices own by my groups')] = $devices;
                    }
                }
            }
            // Get software linked to all owned items
            if (in_array('Software', $_SESSION["glpiactiveprofile"]["helpdesk_item_type"])) {
                $software_helpdesk_types = array_intersect($CFG_GLPI['software_types'], $_SESSION["glpiactiveprofile"]["helpdesk_item_type"]);
                foreach ($software_helpdesk_types as $itemtype) {
                    if (isset($already_add[$itemtype]) && count($already_add[$itemtype])) {
                        $iterator = $DB->request([
                                              'SELECT'    => [
                                                 'glpi_softwareversions.name AS version',
                                                 'glpi_softwares.name AS name',
                                                 'glpi_softwares.id'
                                              ],
                                              'DISTINCT'  => true,
                                              'FROM'      => 'glpi_items_softwareversions',
                                              'LEFT JOIN' => [
                                                 'glpi_softwareversions' => [
                                                    'ON' => [
                                                       'glpi_items_softwareversions' => 'softwareversions_id',
                                                       'glpi_softwareversions'       => 'id'
                                                    ]
                                                 ],
                                                 'glpi_softwares'        => [
                                                    'ON' => [
                                                       'glpi_softwareversions' => 'softwares_id',
                                                       'glpi_softwares'        => 'id'
                                                    ]
                                                 ]
                                              ],
                                              'WHERE'     => [
                                                                'glpi_items_softwareversions.items_id' => $already_add[$itemtype],
                                                                'glpi_items_softwareversions.itemtype' => $itemtype,
                                                                'glpi_softwares.is_helpdesk_visible'   => 1
                                                             ] + getEntitiesRestrictCriteria('glpi_softwares', '', $entity_restrict),
                                              'ORDERBY'   => 'glpi_softwares.name'
                                           ]);

                        $devices = [];
                        if (count($iterator)) {
                            $item      = new Software();
                            $type_name = $item->getTypeName();
                            if (!isset($already_add['Software'])) {
                                $already_add['Software'] = [];
                            }
                            foreach ($iterator as $data) {
                                if (!in_array($data["id"], $already_add['Software'])) {
                                    $output = sprintf(__('%1$s - %2$s'), $type_name, $data["name"]);
                                    $output = sprintf(
                                        __('%1$s (%2$s)'),
                                        $output,
                                        sprintf(
                                            __('%1$s: %2$s'),
                                            __('version'),
                                            $data["version"]
                                        )
                                    );
                                    if ($_SESSION["glpiis_ids_visible"]) {
                                        $output = sprintf(__('%1$s (%2$s)'), $output, $data["id"]);
                                    }
                                    $devices["Software_" . $data["id"]] = $output;

                                    $already_add['Software'][] = $data["id"];
                                }
                            }
                            if (count($devices)) {
                                $my_devices[__('Installed software')] = $devices;
                            }
                        }
                    }
                }
            }
            // Get linked items to computers
            if (isset($already_add['Computer']) && count($already_add['Computer'])) {
                $devices = [];

                // Direct Connection
                $types = ['Monitor', 'Peripheral', 'Phone', 'Printer'];
                foreach ($types as $itemtype) {
                    if (in_array($itemtype, $_SESSION["glpiactiveprofile"]["helpdesk_item_type"])
                    && ($item = getItemForItemtype($itemtype))) {
                        $itemtable = getTableForItemType($itemtype);
                        if (!isset($already_add[$itemtype])) {
                            $already_add[$itemtype] = [];
                        }
                        $criteria = [
                        'SELECT'    => "$itemtable.*",
                        'DISTINCT'  => true,
                        'FROM'      => 'glpi_computers_items',
                        'LEFT JOIN' => [
                        $itemtable => [
                           'ON' => [
                              'glpi_computers_items' => 'items_id',
                              $itemtable             => 'id'
                           ]
                        ]
                        ],
                        'WHERE'     => [
                                       'glpi_computers_items.itemtype'     => $itemtype,
                                       'glpi_computers_items.computers_id' => $already_add['Computer']
                                    ] + getEntitiesRestrictCriteria($itemtable, '', $entity_restrict),
                        'ORDERBY'   => "$itemtable.name"
                        ];

                        if ($item->maybeDeleted()) {
                            $criteria['WHERE']["$itemtable.is_deleted"] = 0;
                        }
                        if ($item->maybeTemplate()) {
                            $criteria['WHERE']["$itemtable.is_template"] = 0;
                        }

                        $iterator = $DB->request($criteria);
                        if (count($iterator)) {
                            $type_name = $item->getTypeName();
                            foreach ($iterator as $data) {
                                if (!in_array($data["id"], $already_add[$itemtype])) {
                                    $output = $data["name"];
                                    if (empty($output) || $_SESSION["glpiis_ids_visible"]) {
                                        $output = sprintf(__('%1$s (%2$s)'), $output, $data['id']);
                                    }
                                    $output = sprintf(__('%1$s - %2$s'), $type_name, $output);
                                    if ($itemtype != 'Software') {
                                        $output = sprintf(__('%1$s - %2$s'), $output, $data['otherserial']);
                                    }
                                    $devices[$itemtype . "_" . $data["id"]] = $output;

                                    $already_add[$itemtype][] = $data["id"];
                                }
                            }
                        }
                    }
                }
                if (count($devices)) {
                    $my_devices[__('Connected devices')] = $devices;
                }
            }

            $return = "<span id='show_items_id_requester'>";
            $return .= Dropdown::showFromArray($params['name'], $my_devices, ['rand' => $rand, 'display' => false, 'value' => $params['value']]);
            $return .= "</span>";

            if ($display) {
                echo $return;
            } else {
                return $return;
            }
            // Auto update summary of active or just solved tickets
           //         $params = ['my_items' => '__VALUE__'];
           //
           //         Ajax::updateItemOnSelectEvent("dropdown_my_items$rand", "item_ticket_selection_information$rand",
           //                                       $CFG_GLPI["root_doc"] . "/ajax/ticketiteminformation.php",
           //                                       $params);
        }
    }

    public function getProfileJoinCriteria()
    {
        return [
         'INNER JOIN' => [
            Profile_User::getTable() => [
               'ON' => [
                  Profile_User::getTable() => 'users_id',
                  User::getTable()         => 'id'
               ]
            ]
         ],
         'WHERE'      => getEntitiesRestrictCriteria(
             Profile_User::getTable(),
             'entities_id',
             $_SESSION['glpiactiveentities'],
             true
         )
        ];
    }

   /**
    * Get request criteria to select uniques users
    *
    * @return array
    * @since 9.4
    *
    */
    final public function getDistinctUserCriteria()
    {
        return [
         'FIELDS'   => [
            User::getTable() . '.id AS users_id',
            User::getTable() . '.language AS language'
         ],
         'DISTINCT' => true,
        ];
    }


    public function post_addItem()
    {
        $pluginField = new PluginMetademandsPluginfields();
        $input       = [];
        if (isset($this->input['plugin_fields_fields_id'])) {
            $input['plugin_fields_fields_id']           = $this->input['plugin_fields_fields_id'];
            $input['plugin_metademands_fields_id']      = $this->fields['id'];
            $input['plugin_metademands_metademands_id'] = $this->fields['plugin_metademands_metademands_id'];
            $pluginField->add($input);
        }
    }

    public function post_updateItem($history = 1)
    {
        $pluginField = new PluginMetademandsPluginfields();
        if (isset($this->input['plugin_fields_fields_id'])) {
            if ($pluginField->getFromDBByCrit(['plugin_metademands_fields_id' => $this->fields['id']])) {
                $input                                 = [];
                $input['plugin_fields_fields_id']      = $this->input['plugin_fields_fields_id'];
                $input['plugin_metademands_fields_id'] = $this->fields['id'];
                $input['id']                           = $pluginField->fields['id'];
                $pluginField->update($input);
            } else {
                $input                                      = [];
                $input['plugin_fields_fields_id']           = $this->input['plugin_fields_fields_id'];
                $input['plugin_metademands_fields_id']      = $this->fields['id'];
                $input['plugin_metademands_metademands_id'] = $this->fields['plugin_metademands_metademands_id'];
                $pluginField->add($input);
            }
        }
    }
}
