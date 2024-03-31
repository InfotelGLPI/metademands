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
 * Class PluginMetademandsFieldParameter
 */
class PluginMetademandsFieldParameter extends CommonDBTM
{
    public static $itemtype = 'PluginMetademandsField';
    public static $items_id = 'plugin_metademands_fields_id';
    public $dohistory = true;

    static $rightname = 'plugin_metademands';

    public static $allowed_parameters_types = [
        'title',
        'title-block',
        'informations',
        'text',
        'textarea',
        'dropdown_meta',
        'dropdown_object',
        'dropdown',
        'dropdown_multiple',
        'checkbox',
        'yesno',
        'radio',
        'number',
        'basket',
        'date',
        'time',
        'datetime',
        'date_interval',
        'datetime_interval',
        'upload',
        'link',
    ];
    public static $allowed_parameters_items = ['User', 'Group'];

    static function getTypeName($nb = 0)
    {
        return _n('Parameter', 'Parameters', $nb, 'metademands');
    }

    static function getCustomTypeName($nb = 0)
    {
        return _n('Custom value', 'Custom values', $nb, 'metademands');
    }


    static function getIcon()
    {
        return PluginMetademandsMetademand::getIcon();
    }
//
//
//    static function canView()
//    {
//        return Session::haveRight(self::$rightname, READ);
//    }
//
//    /**
//     * @return bool
//     */
//    static function canCreate()
//    {
//        return Session::haveRightsOr(self::$rightname, [CREATE, UPDATE, DELETE]);
//    }



    /**
     * @param CommonGLPI $item
     * @param int        $withtemplate
     *
     * @return string
     */
    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        $nb = self::getNumberOfParametersForItem($item);
        return self::createTabEntry(self::getTypeName(Session::getPluralNumber()), $nb);
    }



    /**
     * Return the number of parameters for an item
     *
     * @param item
     *
     * @return int number of parameters for this item
     */
    static function getNumberOfParametersForItem($item)
    {
        $dbu = new DbUtils();
        return $dbu->countElementsInTable(
            $dbu->getTableForItemType(__CLASS__),
            ["plugin_metademands_fields_id" => $item->getID()]
        );
    }

    /**
     *
     * @static
     *
     * @param CommonGLPI $item
     * @param int $tabnum
     * @param int $withtemplate
     *
     * @return bool|true
     */
    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        $field_parameter = new PluginMetademandsFieldParameter();
        if ($field_parameter->getFromDBByCrit(["plugin_metademands_fields_id" => $item->getID()])) {
            $field_parameter->showParameterForm($field_parameter->getID(), ['parent' => $item]);
        } else {
            $field_parameter->showParameterForm(-1, ['parent' => $item]);
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
    public function showParameterForm($ID = -1, $options = [])
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
        $metademand_fields = new PluginMetademandsField();
        $metademand_custom = new PluginMetademandsFieldCustomvalue();
        $item = $options['parent'];

        if ($ID > 0) {
            $this->check($ID, UPDATE);
            $metademand_fields->getFromDB($item->getID());
            $metademand->getFromDB($metademand_fields->fields['plugin_metademands_metademands_id']);
        } else {
            $metademand_fields->getFromDB($item->getID());
            $metademand->getFromDB($metademand_fields->fields['plugin_metademands_metademands_id']);
            // Create item
            $options['itemtype'] = get_class($item);
            $options['items_id'] = $item->getID();
//            $this->check(-1, CREATE, $options);
        }

        $this->showFormHeader($options);


        echo Html::hidden('plugin_metademands_fields_id', ['value' => $item->getID()]);
        echo Html::hidden('type', ['value' => $metademand_fields->fields['type']]);
        echo Html::hidden('item', ['value' => $metademand_fields->fields['item']]);

        $params = PluginMetademandsField::getAllParamsFromField($metademand_fields);

        self::showFieldParameters($params);

        $this->showFormButtons(['colspan' => 2, 'candel' => false,]);

        if ($ID > 0) {
            echo "<table class='tab_cadre' width='100%'>";
            echo "<tr class='tab_bg_1'>";
            echo "<th colspan='2'>" . __('Field informations', 'metademands') . "</th>";
            echo "</tr>";

            echo "<tr class='tab_bg_1'>";
            echo "<td>" . __('Type') . "</td>";
            echo "<td>";
            echo PluginMetademandsField::getFieldTypesName($params["type"]);
            echo "</td>";
            echo "</tr>";

            echo "<tr class='tab_bg_1'>";
            echo "<td>" . __('Example', 'metademands') . "</td>";
            echo "<td>";
            echo PluginMetademandsField::getFieldInput([], $params, false, 0, 0, false, "");
            echo "</td>";
            echo "</tr>";

            echo "</table>";
        }


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
    static public function showFieldParameters($options)
    {
        global $PLUGIN_HOOKS;

        $params['value'] = 0;

        foreach ($options as $key => $value) {
            $params[$key] = $value;
        }

        $allowed_parameters_types = self::$allowed_parameters_types;
        $allowed_parameters_items = self::$allowed_parameters_items;
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

        if (in_array($params['type'], $allowed_parameters_types)
            || in_array($params['item'], $allowed_parameters_items)) {
//            if (is_array($new_fields) && in_array($params['type'], $new_fields)) {
//                $params['value'] = $params['type'];
//            }
//            if ($params["type"] === "dropdown") {
//                $params['value'] = $params['type'];
//            }

            self::showGlobalParameters($params);

//            echo "<div id='show_type_fields'>";
//            echo "<table width='100%' class='metademands_show_values'>";

            switch ($params["type"]) {
                case 'title':
                    echo PluginMetademandsTitle::showFieldParameters($params);
                    break;
                case 'title-block':
                    echo PluginMetademandsTitleblock::showFieldParameters($params);
                    break;
                case 'informations':
                    echo PluginMetademandsInformation::showFieldParameters($params);
                    break;
                case 'text':
                    echo PluginMetademandsText::showFieldParameters($params);
                    break;
                case 'textarea':
                    echo PluginMetademandsTextarea::showFieldParameters($params);
                    break;
                case 'dropdown_meta':
                    echo PluginMetademandsDropdownmeta::showFieldParameters($params);
                    break;
                case 'dropdown_object':
                    echo PluginMetademandsDropdownobject::showFieldParameters($params);
                    break;
                case 'dropdown':
                    echo PluginMetademandsDropdown::showFieldParameters($params);
                    break;
                case 'dropdown_multiple':
                    echo PluginMetademandsDropdownmultiple::showFieldParameters($params);
                    break;
                case 'checkbox':
                    break;
                case 'radio':
                    break;
                case 'yesno':
                    break;
                case 'number':
                    break;
                case 'basket':
                    break;
                case 'date':
                    echo PluginMetademandsDate::showFieldParameters($params);
                    break;
                case 'time':
                    echo PluginMetademandsTime::showFieldParameters($params);
                    break;
                case 'datetime':
                    echo PluginMetademandsDatetime::showFieldParameters($params);
                    break;
                case 'date_interval':
                    echo PluginMetademandsDateinterval::showFieldParameters($params);
                    break;
                case 'datetime_interval':
                    echo PluginMetademandsDatetimeInterval::showFieldParameters($params);
                    break;
                case 'upload':
                    echo PluginMetademandsUpload::showFieldParameters($params);
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
//            echo "</table>";
//            echo "</div>";
        }
    }


    static function showGlobalParameters($params)
    {

        // MANDATORY
        if ($params['type'] != "title"
            && $params['type'] != "title-block"
            && $params['type'] != "informations") {

            echo "<tr class='tab_bg_1'>";
            if ($params['type'] != "link") {
                echo "<td>" . __('Mandatory field') . "</td>";
                echo "<td>";
                Dropdown::showYesNo("is_mandatory", $params["is_mandatory"]);
                echo "</td>";
            }
            echo "<td>";
            echo __('Hide title', 'metademands');
            echo "</td>";
            echo "<td>";
            Dropdown::showYesNo('hide_title', ($params['hide_title']));
            echo "</td>";
            echo "</tr>";
        }
        if ($params['type'] != "title"
            && $params['type'] != "title-block"
            && $params['type'] != "informations") {
            echo "<tr class='tab_bg_1'>";
            echo "<td>";
            echo __('Takes the whole row', 'metademands');
            echo "</td>";
            echo "<td>";
            Dropdown::showYesNo('row_display', ($params['row_display']));
            echo "</td>";

            // Is_Basket Fields
            if ($params['is_order'] == 1) {
                echo "<td>" . __('Display into the basket', 'metademands') . "</td>";
                echo "<td>";
                if ($params['id'] > 0) {
                    $value = $params["is_basket"];
                } else {
                    $value = 1;
                }
                Dropdown::showYesNo("is_basket", $value);
                echo "</td>";
            } else {
                echo "<td colspan='2'></td>";
            }
            echo " </tr>";
        }

        echo "<tr class='tab_bg_1'>";
        //TODO permit linked items_id / itemtype
        if ($params['type'] != "title"
            && $params['type'] != "title-block"
            && $params['type'] != "informations"
            && $params['type'] != 'text'
            && $params['type'] != 'textarea'
            && $params['type'] != 'checkbox'
            && $params['type'] != 'yesno'
            && $params['type'] != 'radio'
            && $params['type'] != 'number'
            && $params['type'] != 'basket'
            && $params['type'] != 'link') {

            echo "<td>";
            echo __('Use this field as object field', 'metademands');
            echo "</td>";
            echo "<td>";
            $ticket_fields[0] = Dropdown::EMPTY_VALUE;
            $objectclass = $params['object_to_create'];
            $searchOption = Search::getOptions($objectclass);

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
            if (($params['type'] == "dropdown_object"
                    && $params["item"] == "User")
                || ($params['type'] == "dropdown_multiple"
                    && $params["item"] == "User")) {
                //Valideur
                $allowed_fields[59] = __('Approver');
                $granted_fields = [
                    4,
                    66,
                    59
                ];
            }
            if ($params['type'] == "dropdown_object"
                && $params["item"] == "Group") {
                $granted_fields = [
                    71,
                    65,
                ];
            }

            if ($params['type'] == "dropdown_object"
                && $params["item"] == "Entity") {
                $allowed_fields[80] = 'entities_id';
                $granted_fields = [
                    80,
                ];
            }

            if ($params['type'] == "dropdown"
                && $params["item"] == "Location") {
                $granted_fields = [
                    'locations_id',
                ];
            }

            if ($params['type'] == "dropdown"
                && $params["item"] == "RequestType") {
                $granted_fields = [
                    'requesttypes_id',
                ];
            }

            if ($params['type'] == "dropdown_meta"
                && ($params["item"] == "urgency"
                    || $params["item"] == "impact"
                    || $params["item"] == "priority")) {
                $granted_fields = [
                    $params["item"]
                ];
            }

            if ($params['type'] == "dropdown_meta"
                && ($params["item"] == "ITILCategory_Metademands")) {
                $granted_fields = [
                    'itilcategories_id'
                ];
            }

            if ($params['type'] == "date"
                || $params["type"] == "datetime") {
                $granted_fields = [
                    'time_to_resolve'
                ];
            }

            if (($params['type'] == "dropdown_meta"
                    && $params["item"] == "mydevices")
                || ($params['type'] == "dropdown_multiple"
                    && $params["item"] == "Appliance")
                || ($params['type'] == "dropdown_object"
                    && Ticket::isPossibleToAssignType($params["item"]))) {
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
                ['value' => $params["used_by_ticket"]]
            );
            echo "</td>";
        }

        if ($params['type'] != "title"
            && $params['type'] != "title-block"
            && $params['type'] != "informations"
            && $params['type'] != 'link') {
            if (Plugin::isPluginActive('fields')) {
                echo "<td>";
                echo __('Link this to a plugin "fields" field', 'metademands');
                echo "</td>";
                echo "<td>";

                $arrayAvailableContainer = [];
                $fieldsContainer = new PluginFieldsContainer();
                $fieldsContainers = $fieldsContainer->find();

                foreach ($fieldsContainers as $container) {
                    $typesContainer = json_decode($container['itemtypes']);
                    if (is_array($typesContainer) && in_array($params["object_to_create"], $typesContainer)) {
                        $arrayAvailableContainer[] = $container['id'];
                    }
                }

                $pluginfield = new PluginMetademandsPluginfields();
                $opt = ['display_emptychoice' => true];
                if ($pluginfield->getFromDBByCrit(['plugin_metademands_fields_id' => $params["id"]])) {
                    $opt["value"] = $pluginfield->fields["plugin_fields_fields_id"];
                }
                $condition = [];
                if (count($arrayAvailableContainer) > 0) {
                    $condition = ['plugin_fields_containers_id' => $arrayAvailableContainer];
                }

                $field = new PluginFieldsField();
                $fields_values = $field->find($condition);
                $datas = [];
                foreach ($fields_values as $fields_value) {
                    $datas[$fields_value['id']] = $fields_value['label'];
                }

                Dropdown::showFromArray('plugin_fields_fields_id', $datas, $opt);

                echo "</td>";
            }
        }
        echo "</tr>";
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
                $item = $dbu->getItemForItemtype($pluginclass);
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
                $item = $dbu->getItemForItemtype($pluginclass);
                if ($item && is_callable([$item, 'addFieldItems'])) {
                    return $item->addFieldItems();
                }
            }
        }
    }


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

        return $input;
    }


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

        return $input;
    }

    /**
     * @param $input
     *
     * @return bool
     */
    public function checkMandatoryFields($input)
    {
        $msg = [];
        $checkKo = false;

        $id = isset($input['id']) ? $input['id'] : 0;
        foreach ($input as $key => $value) {
            if ($key === 'informations_to_display' && (in_array($input['type'], ['dropdown_multiple', 'dropdown_object']
                    ) && $input['item'] === 'User')) {
                $temp = json_decode($value);
                if (empty($temp)) {
                    $msg[] = __("Informations to display in ticket and PDF", "metademands");
                    $checkKo = true;
                }
            }

            $_SESSION['glpi_plugin_metademands_fields'][$id][$key] = $value;
        }

        if ($checkKo) {
            Session::addMessageAfterRedirect(
                sprintf(__("Mandatory fields are not filled. Please correct: %s"), implode(', ', $msg)),
                false,
                ERROR
            );
            return false;
        }
        return true;
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
                        $clean = Html::cleanPostForTextArea($value);
                        if ($clean != null) {
                            $value = urlencode($clean);
                        }
                    }
                }

                return json_encode($input);
            }
        }
    }

    public static function _serializeArray($input)
    {
        if ($input != null || $input == []) {
            $data_temp = [];
            if (is_array($input)) {
                foreach ($input as $k => $v) {
                    $data_temp[urlencode($k)] = self::_serializeArray($v);
                }
                return $data_temp;
            } else {
                return urlencode($input);
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
                    if ($value != null) {
                        $value = urldecode($value);
                    }
                }
            }
        }

        return $input;
    }

    public static function _unserializeArray($input)
    {
        if (!empty($input)) {
            $data_temp = [];
            if (is_array($input)) {
                foreach ($input as $k => $v) {
                    $data_temp[json_decode($k, true)] = self::_unserializeArray($v);
                }
                return $data_temp;
            } else {
                return json_decode($input, true);
            }
        }

        return $input;
    }


//
//    public function post_addItem()
//    {
//        $pluginField = new PluginMetademandsPluginfields();
//        $input = [];
//        if (isset($this->input['plugin_fields_fields_id'])) {
//            $input['plugin_fields_fields_id'] = $this->input['plugin_fields_fields_id'];
//            $input['plugin_metademands_fields_id'] = $this->fields['id'];
//            $input['plugin_metademands_metademands_id'] = $this->fields['plugin_metademands_metademands_id'];
//            $pluginField->add($input);
//        }
//    }
//
//    public function post_updateItem($history = 1)
//    {
//        $pluginField = new PluginMetademandsPluginfields();
//        if (isset($this->input['plugin_fields_fields_id'])) {
//            if ($pluginField->getFromDBByCrit(['plugin_metademands_fields_id' => $this->fields['id']])) {
//                $input = [];
//                $input['plugin_fields_fields_id'] = $this->input['plugin_fields_fields_id'];
//                $input['plugin_metademands_fields_id'] = $this->fields['id'];
//                $input['id'] = $pluginField->fields['id'];
//                $pluginField->update($input);
//            } else {
//                $input = [];
//                $input['plugin_fields_fields_id'] = $this->input['plugin_fields_fields_id'];
//                $input['plugin_metademands_fields_id'] = $this->fields['id'];
//                $input['plugin_metademands_metademands_id'] = $this->fields['plugin_metademands_metademands_id'];
//                $pluginField->add($input);
//            }
//        }
//    }
}
