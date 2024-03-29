<?php

/*
 -------------------------------------------------------------------------
 Metademands plugin for GLPI
 Copyright (C) 2003-2019 by the Metademands Development Team.

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
 * PluginMetademandsDropdownmeta Class
 *
 **/
class PluginMetademandsDropdownmeta extends CommonDBTM
{

    public static $dropdown_meta_items = [
        '',
        'other',
        'ITILCategory_Metademands',
        'urgency',
        'impact',
        'priority',
        'mydevices'
    ];

    /**
     * Return the localized name of the current Type
     * Should be overloaded in each new class
     *
     * @param integer $nb Number of items
     *
     * @return string
     **/
    static function getTypeName($nb = 0)
    {
        return __('Dropdown', 'metademands');
    }

    static function showWizardField($data, $namefield, $value, $on_order, $itilcategories_id)
    {
        $metademand = new PluginMetademandsMetademand();
        $metademand->getFromDB($data['plugin_metademands_metademands_id']);

        if (empty($comment = PluginMetademandsField::displayField($data['id'], 'comment'))) {
            $comment = $data['comment'];
        }

        switch ($data['item']) {
            case 'other':
                if (!empty($data['custom_values'])) {
                    $custom_values = PluginMetademandsFieldParameter::_unserialize($data['custom_values']);

                    foreach ($custom_values as $k => $val) {
                        if (!empty($ret = PluginMetademandsField::displayField($data["id"], "custom" . $k))) {
                            $custom_values[$k] = $ret;
                        }
                    }

                    $defaults = PluginMetademandsFieldParameter::_unserialize($data['default_values']);

                    $default_values = "";
                    if ($defaults) {
                        foreach ($defaults as $k => $v) {
                            if ($v == 1) {
                                $default_values = $k;
                            }
                        }
                    }
                    $value = !empty($value) ? $value : $default_values;
                    //                     ksort($data['custom_values']);
                    $field = "";
                    $field .= Dropdown::showFromArray(
                        $namefield . "[" . $data['id'] . "]",
                        $custom_values,
                        [
                            'value' => $value,
                            'width' => '100%',
                            'display_emptychoice' => true,
                            'display' => false,
                            'required' => ($data['is_mandatory'] ? "required" : ""),
                        ]
                    );
                }
                break;

            case 'ITILCategory_Metademands':
                if ($on_order == false) {
                    $nameitil = 'field';
                } else {
                    $nameitil = 'basket';
                }
                $values = json_decode($metademand->fields['itilcategories_id']);
                //from Service Catalog
                if ($itilcategories_id > 0) {
                    $value = $itilcategories_id;
                }
                //                  if (!empty($values) && count($values) == 1) {
                //                     foreach ($values as $key => $val)
                //                        $itilcategories_id = $val;
                //                  }
                //                  if ($itilcategories_id > 0) {
                //                     // itilcat from service catalog
                //                     $itilCategory = new ITILCategory();
                //                     $itilCategory->getFromDB($itilcategories_id);
                //                     $field = "<span>" . $itilCategory->getField('name');
                //                     $field .= "<input type='hidden' name='" . $nameitil . "_type' value='" . $metademand->fields['type'] . "' >";
                //                     $field .= "<input type='hidden' name='" . $nameitil . "_plugin_servicecatalog_itilcategories_id' value='" . $itilcategories_id . "' >";
                //                     $field .= "<span>";
                //                  } else {
                $readonly = $data['readonly'];
                $hidden = $data['hidden'];
                if ($hidden == 1 && isset($_SESSION['glpiactiveprofile']['interface'])
                    && $_SESSION['glpiactiveprofile']['interface'] == 'central') {
                    $hidden = 0;
                }

                if ($data['readonly'] == 1 && isset($_SESSION['glpiactiveprofile']['interface'])
                    && $_SESSION['glpiactiveprofile']['interface'] == 'central') {
                    $readonly = 0;
                }
                $opt = [
                    'name' => $nameitil . "_plugin_servicecatalog_itilcategories_id",
                    'right' => 'all',
                    'value' => $value,
                    'condition' => ["id" => $values],
                    'display' => false,
                    'readonly' => $readonly ?? false,
                    'class' => 'form-select itilmeta'
                ];
                if ($data['is_mandatory'] == 1) {
                    $opt['specific_tags'] = ['required' => ($data['is_mandatory'] == 1 ? "required" : "")];
                }
                $field = "";
                if ($hidden == 0) {
                    $field .= ITILCategory::dropdown($opt);
                    $field .= "<input type='hidden' name='" . $nameitil . "_plugin_servicecatalog_itilcategories_id_key' value='" . $data['id'] . "' >";
                }

                if ($readonly == 1 || $hidden == 1) {
                    $field .= Html::hidden($nameitil . "_plugin_servicecatalog_itilcategories_id", ['value' => $value]);
                }
                break;
            case 'mydevices':
                $field = "";
                if ($on_order == false) {
                    // My items
                    //TODO : used_by_ticket -> link with item's ticket
                    $field = "";

                    $_POST['field'] = $namefield . "[" . $data['id'] . "]";
                    //                     $users_id = 0;
                    if ($data['link_to_user'] > 0) {
                        echo "<div id='mydevices_user" . $data['link_to_user'] . "' class=\"input-group\">";
                        $fieldUser = new PluginMetademandsField();
                        $fieldUser->getFromDBByCrit([
                            'id' => $data['link_to_user'],
                            'type' => "dropdown_object",
                            'item' => User::getType()
                        ]);

                        $_POST['value'] = ($fieldUser->fields['default_use_id_requester'] == 0) ? 0 : Session::getLoginUserID(
                        );
                        $_POST['id_fielduser'] = $data['link_to_user'];
                        $_POST['fields_id'] = $data['id'];
                        $_POST['metademands_id'] = $data['plugin_metademands_metademands_id'];
                        include(PLUGIN_METADEMANDS_DIR . "/ajax/umydevicesUpdate.php");
                        echo "</div>";
                    } else {
                        $rand = mt_rand();
                        $p = [
                            'rand' => $rand,
                            'name' => $_POST["field"],
                            'value' => $_SESSION['plugin_metademands'][$data['plugin_metademands_metademands_id']]['fields'][$data['id']] ?? 0
                        ];
                        $field .= PluginMetademandsField::dropdownMyDevices(
                            Session::getLoginUserID(),
                            $_SESSION['glpiactiveentities'],
                            0,
                            0,
                            $p,
                            false
                        );
                    }
                } else {
                    $dbu = new DbUtils();
                    $splitter = explode("_", $value);
                    if (count($splitter) == 2) {
                        $itemtype = $splitter[0];
                        $items_id = $splitter[1];
                    }
                    $field .= "<input type='hidden' name='" . $namefield . "[" . $data['id'] . "]' value='" . $value . "' >";
                    if (isset($itemtype) && isset($items_id)) {
                        $field .= Dropdown::getDropdownName(
                            $dbu->getTableForItemType($itemtype),
                            $items_id
                        );
                    }
                }
                break;
            case 'urgency':
                $field = "";
                $ticket = new Ticket();
                if ($itilcategories_id == 0) {
                    $itilcategories_id_array = json_decode($metademand->fields['itilcategories_id'], true);
                    if (is_array($itilcategories_id_array) && count($itilcategories_id_array) == 1) {
                        foreach ($itilcategories_id_array as $arr) {
                            $itilcategories_id = $arr;
                        }
                    }
                }
                if ($data['default_values']) {
                    $defaults = PluginMetademandsFieldParameter::_unserialize($data['default_values']);
                    foreach ($defaults as $k => $v) {
                        $options['value'] = $v;
                    }
                }

                if ($itilcategories_id > 0) {
                    $meta_tt = $ticket->getITILTemplateToUse(
                        0,
                        $metademand->fields['type'],
                        $itilcategories_id,
                        $metademand->fields['entities_id']
                    );
                    if (isset($meta_tt->predefined['urgency'])) {
                        $options['value'] = $meta_tt->predefined['urgency'];
                        if (isset($_SESSION['plugin_metademands'][$data['plugin_metademands_metademands_id']]['fields'][$data['id']])) {
                            $session_value = $_SESSION['plugin_metademands'][$data['plugin_metademands_metademands_id']]['fields'][$data['id']];
                            if (is_array($session_value)) {
                                foreach ($session_value as $k => $fieldSession) {
                                    if ($fieldSession > 0) {
                                        $options['value'] = $fieldSession;
                                    }
                                }
                            }
                        }
                    }
                }
                $options['name'] = $namefield . "[" . $data['id'] . "]";
                $options['display'] = false;
                $options['required'] = ($data['is_mandatory'] ? "required" : "");
                $field .= Ticket::dropdownUrgency($options);
                break;
            case 'impact':
                $field = "";
                $ticket = new Ticket();
                if ($itilcategories_id == 0) {
                    $itilcategories_id_array = json_decode($metademand->fields['itilcategories_id'], true);
                    if (is_array($itilcategories_id_array) && count($itilcategories_id_array) == 1) {
                        foreach ($itilcategories_id_array as $arr) {
                            $itilcategories_id = $arr;
                        }
                    }
                }
                $defaults = PluginMetademandsFieldParameter::_unserialize($data['default_values']);
                foreach ($defaults as $k => $v) {
                    $options['value'] = $v;
                }
                if ($itilcategories_id > 0) {
                    $meta_tt = $ticket->getITILTemplateToUse(
                        0,
                        $metademand->fields['type'],
                        $itilcategories_id,
                        $metademand->fields['entities_id']
                    );
                    if (isset($meta_tt->predefined['impact'])) {
                        $options['value'] = $meta_tt->predefined['impact'];
                        if (isset($_SESSION['plugin_metademands'][$data['plugin_metademands_metademands_id']]['fields'][$data['id']])) {
                            $session_value = $_SESSION['plugin_metademands'][$data['plugin_metademands_metademands_id']]['fields'][$data['id']];
                            if (is_array($session_value)) {
                                foreach ($session_value as $k => $fieldSession) {
                                    if ($fieldSession > 0) {
                                        $options['value'] = $fieldSession;
                                    }
                                }
                            }
                        }
                    }
                }
                $options['name'] = $namefield . "[" . $data['id'] . "]";
                $options['display'] = false;
                $options['required'] = ($data['is_mandatory'] ? "required" : "");
                $field .= Ticket::dropdownImpact($options);
                break;
            case 'priority':
                $field = "";
                $ticket = new Ticket();
                if ($itilcategories_id == 0) {
                    $itilcategories_id_array = json_decode($metademand->fields['itilcategories_id'], true);
                    if (is_array($itilcategories_id_array) && count($itilcategories_id_array) == 1) {
                        foreach ($itilcategories_id_array as $arr) {
                            $itilcategories_id = $arr;
                        }
                    }
                }
                if ($itilcategories_id > 0) {
                    $meta_tt = $ticket->getITILTemplateToUse(
                        0,
                        $metademand->fields['type'],
                        $itilcategories_id,
                        $metademand->fields['entities_id']
                    );
                    if (isset($meta_tt->predefined['priority'])) {
                        $options['value'] = $meta_tt->predefined['priority'];
                        if (isset($_SESSION['plugin_metademands'][$data['plugin_metademands_metademands_id']]['fields'][$data['id']])) {
                            $session_value = $_SESSION['plugin_metademands'][$data['plugin_metademands_metademands_id']]['fields'][$data['id']];
                            if (is_array($session_value)) {
                                foreach ($session_value as $k => $fieldSession) {
                                    if ($fieldSession > 0) {
                                        $options['value'] = $fieldSession;
                                    }
                                }
                            }
                        }
                    }
                }
                $options['name'] = $namefield . "[" . $data['id'] . "]";
                $options['display'] = false;
                $options['required'] = ($data['is_mandatory'] ? "required" : "");
                $field .= Ticket::dropdownPriority($options);
                break;
            default:
                break;
        }

        echo $field;
    }

    static function showFieldCustomValues($params)
    {

        $custom_values = $params['custom_values'];
        $default_values = $params['default_values'];
        $comment_values = $params['comment_values'];

        echo "<tr class='tab_bg_1'>";
        echo "<td>";
        if (is_array($custom_values) && !empty($custom_values)) {
            echo "<div id='drag'>";
            echo "<table class='tab_cadre_fixe'>";

            foreach ($custom_values as $key => $value) {
                echo "<tr class='tab_bg_1'>";

                echo '<td class="rowhandler control center">';
                echo __('Value') . " " . $key . " ";
                echo '</td>';

                echo '<td class="rowhandler control center">';
                echo "<span id='custom_values$key'>";
                $name = "custom_values[$key]";
                echo Html::input($name, ['value' => $value, 'size' => 50]);
                echo '</span>';
                echo '</td>';

//                echo '<td class="rowhandler control center">';
//                echo "<span id='comment_values$key'>";
//                echo " " . __('Comment') . " ";
//                $value_comment = "";
//                if (isset($comment_values[$key])) {
//                    $value_comment = $comment_values[$key];
//                }
//                $name = "comment_values[" . $key . "]";
//                echo Html::input($name, ['value' => $value_comment, 'size' => 30]);
//                echo '</span>';
//                echo "</td>";

                echo '<td class="rowhandler control center">';
                echo "<span id='default_values$key'>";
                echo _n('Default value', 'Default values', 1, 'metademands');
                $name  = "default_values[" . $key . "]";
                $value = ($default_values[$key] ?? 0);
                Dropdown::showYesNo($name, $value);
                echo '</span>';
                echo "</td>";

                echo '<td class="rowhandler control center">';
                echo "<div class=\"drag row\" style=\"cursor: move;border-width: 0 !important;border-style: none !important; border-color: initial !important;border-image: initial !important;\">";
                echo "<i class=\"fas fa-grip-horizontal grip-rule\"></i>";
                if (isset($params['id'])) {
                    echo PluginMetademandsFieldParameter::showSimpleForm(
                        PluginMetademandsFieldParameter::getFormURL(),
                        'delete_field_custom_values',
                        _x('button', 'Delete permanently'),
                        ['id'                           => $key,
                            'plugin_metademands_fields_id' => $params['id'],
                        ],
                        'fa-times-circle'
                    );
                }
                echo '</div>';
                echo '</td>';
                echo "</tr>";
            }
            if (isset($params['id'])) {
                echo Html::hidden('fields_id', ['value' => $params["id"], 'id' => 'fields_id']);
            }
            echo '</table>';
            echo '</div>';
            echo Html::scriptBlock('$(document).ready(function() {plugin_metademands_redipsInit()});');
            echo '</td>';

            echo "</tr>";
            echo "<tr>";
            echo "<td colspan='4' align='right' id='show_custom_fields'>";
            PluginMetademandsFieldParameter::initCustomValue(max(array_keys($custom_values)), false, true);
            echo "</td>";
            echo "</tr>";
        } else {
            if ($params['item'] != 'urgency' && $params['item'] != 'impact') {
                $key = 1;
                echo "<td>";
                echo __('Value') . " ".$key;
                echo "</td>";

                echo "<td>";
                echo Html::input('custom_values[1]', ['size' => 50]);
                echo "</td>";

                echo "<td>";
                echo "<span id='default_values$key'>";
                echo " " . _n('Default value', 'Default values', 1, 'metademands') . " ";
                $name  = "default_values[" . $key . "]";
                $value = ($default[$key] ?? 0);
                Dropdown::showYesNo($name, $value);
                echo '</span>';
                echo "</td>";

                echo "</tr>";

                echo "<tr>";
                echo "<td colspan='2' align='right' id='show_custom_fields'>";
                PluginMetademandsFieldParameter::initCustomValue($key, false, true);
                echo "</td>";
                echo "</tr>";
            } else {
                if (is_array($default_values) && count($default_values) > 0) {
                    foreach ($default_values as $key => $default_value) {
                        $options['value'] = $default_value;
                    }
                }
                $options['name'] = "default_values[1]";
                if ($params['item'] != 'urgency') {
                    Ticket::dropdownImpact($options);
                } else {
                    Ticket::dropdownUrgency($options);
                }

                echo "</td>";
                echo "</tr>";
            }
        }
        echo "</td>";
        echo "</tr>";
    }

    static function showFieldParameters($params) {

        echo "<tr>";
        if ($params["item"] == "urgency"
            || $params["item"] == "impact"
            || $params["item"] == "priority") {
            echo "<td>";
            echo __('Use this field for child ticket field', 'metademands');
            echo "</td>";
            echo "<td>";
            Dropdown::showYesNo('used_by_child', $params['used_by_child']);
            echo "</td>";
        } else {
            echo "<td colspan='2'></td>";
        }

        if ($params["item"] == "mydevices") {
            echo "<td>";
            echo __('Link this to a user field', 'metademands');
            echo "</td>";

            echo "<td>";
            $arrayAvailable[0] = Dropdown::EMPTY_VALUE;
            $field = new PluginMetademandsField();
            $fields = $field->find([
                "plugin_metademands_metademands_id" => $params['plugin_metademands_metademands_id'],
                'type' => "dropdown_object",
                "item" => User::getType()
            ]);
            foreach ($fields as $f) {
                $arrayAvailable [$f['id']] = $f['rank'] . " - " . urldecode(html_entity_decode($f['name']));
            }
            Dropdown::showFromArray('link_to_user', $arrayAvailable, ['value' => $params['link_to_user']]);
            echo "</td>";
        }
        echo "</tr>";

        if ($params["id"] > 0 && ($params['type'] == "dropdown_meta"
                && $params["item"] == "ITILCategory_Metademands")) {
            echo "<tr class='tab_bg_1'>";

            echo "<td>";
            echo __('Read-Only', 'metademands');
            echo "</td>";
            echo "<td>";
            Dropdown::showYesNo('readonly', ($params['readonly']));
            echo "</td>";

            echo "<td>";
            echo __('Hidden field', 'metademands');
            echo "</td>";
            echo "<td>";
            Dropdown::showYesNo('hidden', ($params['hidden']));
            echo "</td>";

            echo "</tr>";
        }
    }

    static function getParamsValueToCheck($fieldoption, $item, $params)
    {
        echo "<tr>";
        echo "<td>";
        echo __('Value to check', 'metademands');
//        echo " ( " . Dropdown::EMPTY_VALUE . " = " . __('Not null value', 'metademands') . ")";
        echo "</td>";
        echo "<td>";
        self::showValueToCheck($fieldoption, $params);
        echo "</td>";

        echo PluginMetademandsFieldOption::showLinkHtml($item->getID(), $params, 1, 1, 1);
    }

    static function showValueToCheck($item, $params)
    {
        $field = new PluginMetademandsFieldOption();
        $existing_options = $field->find(["plugin_metademands_fields_id" => $params["plugin_metademands_fields_id"]]);
        $already_used = [];
        if ($item->getID() == 0) {
            foreach ($existing_options as $existing_option) {
                $already_used[$existing_option["check_value"]] = $existing_option["check_value"];
            }
        }
        switch ($params["item"]) {
            case 'ITILCategory_Metademands':
                $metademand = new PluginMetademandsMetademand();
                $metademand->getFromDB($params["plugin_metademands_metademands_id"]);
                $values = json_decode($metademand->fields['itilcategories_id']);

                $name = "check_value";
                $opt = [
                    'name' => $name,
                    'right' => 'all',
                    'value' => $params['check_value'],
                    'condition' => ["id" => $values],
                    'display' => true,
                    'used' => $already_used
                ];
                ITILCategory::dropdown($opt);

                break;
            default:
                $dbu = new DbUtils();
                if ($item = $dbu->getItemForItemtype($params["item"])
                    && $params['type'] != "dropdown_multiple") {
                    //               if ($params['value'] == 'group') {
                    //                  $name = "check_value";// TODO : HS POUR LES GROUPES CAR rajout un RAND dans le dropdownname
                    //               } else {
                    $name = "check_value";
                    //               }
                    $params['item']::Dropdown([
                        "name" => $name,
                        "value" => $params['check_value'],
                        'used' => $already_used
                    ]);
                } else {
                    if ($params["item"] != "other" && $params["type"] == "dropdown_multiple") {
                        $elements[-1] = __('Not null value', 'metademands');
                        if (is_array(json_decode($params['custom_values'], true))) {
                            $elements += json_decode($params['custom_values'], true);
                        }
                        foreach ($elements as $key => $val) {
                            if ($key != 0) {
                                $elements[$key] = $params["item"]::getFriendlyNameById($key);
                            }
                        }
                    } else {
                        $elements[-1] = __('Not null value', 'metademands');
                        if (is_array(json_decode($params['custom_values'], true))) {
                            $elements += json_decode($params['custom_values'], true);
                        }
                        foreach ($elements as $key => $val) {
                            $elements[$key] = urldecode($val);
                        }
                    }
                    Dropdown::showFromArray(
                        "check_value",
                        $elements,
                        ['value' => $params['check_value'], 'used' => $already_used]
                    );
                }
                break;
        }
    }


    static function showParamsValueToCheck($params)
    {
        if ($params['check_value'] == -1) {
            echo __('Not null value', 'metademands');
        } else {
            switch ($params["item"]) {
                case 'ITILCategory_Metademands':
                    echo Dropdown::getDropdownName('glpi_itilcategories', $params['check_value']);
                    break;
                default:
                    $dbu = new DbUtils();
                    if ($item = $dbu->getItemForItemtype($params["item"])
                        && $params['type'] != "dropdown_multiple") {
                        echo Dropdown::getDropdownName(getTableForItemType($params["item"]), $params['check_value']);
                    } else {
                        if ($params["item"] != "other" && $params["type"] == "dropdown_multiple") {
                            $elements = [];
                            if (is_array(json_decode($params['custom_values'], true))) {
                                $elements += json_decode($params['custom_values'], true);
                            }
                            foreach ($elements as $key => $val) {
                                if ($key != 0) {
                                    $elements[$key] = $params["item"]::getFriendlyNameById($key);
                                }
                            }
                            echo $elements[$params['check_value']];
                        } else {
                            $elements = [];
                            if (is_array(json_decode($params['custom_values'], true))) {
                                $elements += json_decode($params['custom_values'], true);
                            }
                            foreach ($elements as $key => $val) {
                                $elements[$key] = urldecode($val);
                            }
                            echo $elements[$params['check_value']] ?? "";
                        }
                    }
                    break;
            }
        }
    }

    static function isCheckValueOK($value, $check_value)
    {
        if (($check_value == PluginMetademandsField::$not_null || $check_value == 0) && empty($value)) {
            return false;
        } elseif ($check_value != $value
            && ($check_value != PluginMetademandsField::$not_null && $check_value != 0)) {
            return false;
        }
    }

    /**
     * @param array $value
     * @param array $fields
     * @return bool
     */
    public static function checkMandatoryFields($value = [], $fields = [])
    {
        $msg = "";
        $checkKo = 0;
        // Check fields empty
        if ($value['is_mandatory']
            && empty($fields['value'])) {
            $msg = $value['name'];
            $checkKo = 1;
        }

        return ['checkKo' => $checkKo, 'msg' => $msg];
    }

    static function fieldsLinkScript($data, $idc, $rand)
    {
    }

    static function taskScript($data)
    {
        $check_values = $data['options'] ?? [];
        $metaid = $data['plugin_metademands_metademands_id'];
        $id = $data["id"];

        $script = "";
        $script2 = "";
        $debug = (isset($_SESSION['glpi_use_mode'])
        && $_SESSION['glpi_use_mode'] == Session::DEBUG_MODE ? true : false);
        if ($debug) {
            $script = "console.log('taskScript-dropdownmeta $id');";
        }

        //if reload form on loading
        if (isset($_SESSION['plugin_metademands'][$metaid]['fields'][$id])) {
            $session_value = $_SESSION['plugin_metademands'][$metaid]['fields'][$id];
            if (is_array($session_value)) {
                foreach ($session_value as $k => $fieldSession) {
                    if ($fieldSession > 0) {
                        $script2 .= "$('[name^=\"field[" . $id . "]\"]').val('$fieldSession').trigger('change');";
                    }
                }
            }
        }

        $title = "<i class=\"fas fa-save\"></i>&nbsp;" . _sx('button', 'Save & Post', 'metademands');
        $nextsteptitle = "<i class=\"fas fa-save\"></i>&nbsp;" . __(
                'Next',
                'metademands'
            ) . "&nbsp;<i class=\"ti ti-chevron-right\"></i>";


        foreach ($check_values as $idc => $check_value) {
            $tasks_id = $data['options'][$idc]['plugin_metademands_tasks_id'];
            if ($tasks_id) {
                if (PluginMetademandsMetademandTask::setUsedTask($tasks_id, 0)) {
                    $script .= "$('[name^=\"field[" . $data["id"] . "]\"]').ready(function() {";
                    $script .= "document.getElementById('nextBtn').innerHTML = '$title'";
                    $script .= "});";
                }
            }
        }

        $name = "field[" . $data["id"] . "]";
        $script .= "$('[name=\"$name\"]').change(function() {";
        $script .= "var tohide = {};";
        foreach ($check_values as $idc => $check_value) {
            $tasks_id = $data['options'][$idc]['plugin_metademands_tasks_id'];


            $script .= "if ($tasks_id in tohide) {
                        } else {
                            tohide[$tasks_id] = true;
                        }
                        if ($(this).val() != 0 && ($(this).val() == $idc || $idc == 0 )) {
                            tohide[$tasks_id] = false;
                        }";

//            $script2 .= "$('[id-field =\"field" . $tasks_id . "\"]').hide();";
//
//            if (isset($_SESSION['plugin_metademands'][$metaid]['fields'][$id])) {
//                $session_value = $_SESSION['plugin_metademands'][$metaid]['fields'][$id];
//                if (is_array($session_value)) {
//                    foreach ($session_value as $k => $fieldSession) {
//                        if ($fieldSession == $idc && $tasks_id > 0) {
//                            $script2 .= "$('[id-field =\"field" . $tasks_id . "\"]').show();";
//                        }
//                    }
//                }
//            }

            $script .= "$.each( tohide, function( key, value ) {           
                        if (value == true) {
                            $.ajax({
                                     url: '" . PLUGIN_METADEMANDS_WEBDIR . "/ajax/set_session.php',
                                     data: { tasks_id: $tasks_id,
                                  used: 0 },
                                  success: function(response){
                                       if (response != 1) {
                                           document.getElementById('nextBtn').innerHTML = '$title'
                                       }
                                    },
                                });

                        } else {
                             $.ajax({
                                     url: '" . PLUGIN_METADEMANDS_WEBDIR . "/ajax/set_session.php',
                                     data: { tasks_id: $tasks_id,
                                  used: 1 },
                                  success: function(response){
                                       if (response != 1) {
                                           document.getElementById('nextBtn').innerHTML = '$nextsteptitle'
                                       }
                                    },
                                });

                        }
                    });
              ";
        }
        $script .= "});";

        foreach ($check_values as $idc => $check_value) {
            $tasks_id = $check_value['plugin_metademands_tasks_id'];
            if (is_array(PluginMetademandsFieldParameter::_unserialize($data['default_values']))) {
                $default_values = PluginMetademandsFieldParameter::_unserialize($data['default_values']);

                foreach ($default_values as $k => $v) {
                    if ($v == 1) {
                        if ($idc == $k) {
                            if (PluginMetademandsMetademandTask::setUsedTask($tasks_id, 1)) {
                                $script .= "$('[name^=\"field[" . $data["id"] . "]\"]').ready(function() {";
                                $script .= "document.getElementById('nextBtn').innerHTML = '$nextsteptitle'";
                                $script .= "});";
                            }
                        } else {
                            if (PluginMetademandsMetademandTask::setUsedTask($tasks_id, 0)) {
                                $script .= "$('[name^=\"field[" . $data["id"] . "]\"]').ready(function() {";
                                $script .= "document.getElementById('nextBtn').innerHTML = '$title'";
                                $script .= "});";
                            }
                        }
                    }
                }
            }
        }

        echo Html::scriptBlock('$(document).ready(function() {' . $script2 . " " . $script . '});');
    }

    static function fieldsHiddenScript($data)
    {
        $metaid = $data['plugin_metademands_metademands_id'];
        $check_values = $data['options'] ?? [];
        $id = $data["id"];

        $name = "field[" . $data["id"] . "]";

        $script = "";
        $script2 = "";
        $debug = (isset($_SESSION['glpi_use_mode'])
        && $_SESSION['glpi_use_mode'] == Session::DEBUG_MODE ? true : false);
        if ($debug) {
            $script = "console.log('fieldsHiddenScript-dropdownmeta $id');";
        }

        //if reload form on loading
        if (isset($_SESSION['plugin_metademands'][$metaid]['fields'][$id])) {
            $session_value = $_SESSION['plugin_metademands'][$metaid]['fields'][$id];
            if (is_array($session_value)) {
                foreach ($session_value as $k => $fieldSession) {
                    if ($fieldSession > 0) {
                        $script2 .= "$('[name=\"field[" . $id . "]\"]').val('$fieldSession').trigger('change');";
                    }
                }
            }
        }

        $script .= "$('[name=\"$name\"]').change(function() {";

        $script .= "var tohide = {};";

        foreach ($check_values as $idc => $check_value) {
            $hidden_link = $check_value['hidden_link'];
            $script .= "if ($hidden_link in tohide) {
                        } else {
                            tohide[$hidden_link] = true;
                        }
                        if ($(this).val() != 0 && ($(this).val() == $idc || $idc == 0 )) {
                            tohide[$hidden_link] = false;
                        }";

            $script2 .= "$('[id-field =\"field" . $hidden_link . "\"]').hide();";

            if (isset($_SESSION['plugin_metademands'][$metaid]['fields'][$id])) {
                $session_value = $_SESSION['plugin_metademands'][$metaid]['fields'][$id];
                if (is_array($session_value)) {
                    foreach ($session_value as $k => $fieldSession) {
                        if ($fieldSession == $idc && $hidden_link > 0) {
                            $script2 .= "$('[id-field =\"field" . $hidden_link . "\"]').show();";
                        }
                    }
                }
            }

//            if (isset($_SESSION['plugin_metademands'][$data["plugin_metademands_metademands_id"]]['fields'][$data["id"]])
//                && ($_SESSION['plugin_metademands'][$data["plugin_metademands_metademands_id"]]['fields'][$data["id"]] == $idc
//                    || ($_SESSION['plugin_metademands'][$data["plugin_metademands_metademands_id"]]['fields'][$data["id"]] != 0 && $idc == 0))) {
//                $script2 .= "$('[id-field =\"field" . $hidden_link . "\"]').show();";
//            }

            $script .= "$.each( tohide, function( key, value ) {           
                        if (value == true) {
                            $('[id-field =\"field'+key+'\"]').hide();
                            " . PluginMetademandsFieldoption::resetMandatoryFieldsByField($hidden_link) . "
                            $('[name =\"field['+key+']\"]').removeAttr('required');
                        } else {
                            $('[id-field =\"field'+key+'\"]').show();
                            " . PluginMetademandsFieldoption::setMandatoryFieldsByField($id, $hidden_link) . "
                        }
                    });
              ";
        }
        $script .= "});";
        //Initialize id default value
        foreach ($check_values as $idc => $check_value) {
            $hidden_link = $check_value['hidden_link'];
            if (is_array(PluginMetademandsFieldParameter::_unserialize($data['default_values']))) {
                $default_values = PluginMetademandsFieldParameter::_unserialize($data['default_values']);

                foreach ($default_values as $k => $v) {
                    if ($v == 1) {
                        if ($idc == $k) {
                            $script .= " $('[id-field =\"field" . $hidden_link . "\"]').show();";
                        }
                    }
                }
            }
        }
        echo Html::scriptBlock('$(document).ready(function() {' . $script2 . " " . $script . '});');
    }

    public static function blocksHiddenScript($data)
    {
        $metaid = $data['plugin_metademands_metademands_id'];
        $check_values = $data['options'] ?? [];
        $id = $data["id"];

        $name = "field[" . $data["id"] . "]";
        if ($data["item"] == "ITILCategory_Metademands") {
            $name = "field_plugin_servicecatalog_itilcategories_id";
        }

        //add childs by idc
        $childs_by_checkvalue = [];
        foreach ($check_values as $idc => $check_value) {
            if (isset($check_value['childs_blocks']) && $check_value['childs_blocks'] != null) {
                $childs_blocks = json_decode($check_value['childs_blocks'], true);
                if (isset($childs_blocks)
                    && is_array($childs_blocks)
                    && count($childs_blocks) > 0) {
                    foreach ($childs_blocks as $childs) {
                        if (is_array($childs)) {
                            foreach ($childs as $child) {
                                $childs_by_checkvalue[$idc][] = $child;
                            }
                        }
                    }
                }
            }
        }

        $script = "";
        $script2 = "";
        $debug = (isset($_SESSION['glpi_use_mode'])
        && $_SESSION['glpi_use_mode'] == Session::DEBUG_MODE ? true : false);
        if ($debug) {
            $script = "console.log('blocksHiddenScript-dropdownmeta $id');";
        }
        $script .= "$('[name=\"$name\"]').change(function() {";

        $script .= "tohide = {};";

        //by default - hide all
        $script2 .= PluginMetademandsFieldoption::hideAllblockbyDefault($data);
        if (!isset($_SESSION['plugin_metademands'][$metaid]['fields'][$id])) {
            $script2 .= PluginMetademandsFieldoption::emptyAllblockbyDefault($check_values);
        }
        foreach ($check_values as $idc => $check_value) {
            $blocks_idc = [];
            $hidden_block = $check_value['hidden_block'];

            //Default values
            if (is_array(PluginMetademandsFieldParameter::_unserialize($data['default_values']))) {
                $default_values = PluginMetademandsFieldParameter::_unserialize($data['default_values']);
                foreach ($default_values as $k => $v) {
                    if ($v == 1) {
                        if ($idc == $k) {
                            $script2 .= "$('[bloc-id =\"bloc" . $hidden_block . "\"]').show();
                                " . PluginMetademandsFieldoption::setMandatoryBlockFields($metaid, $hidden_block);

                            if (is_array($childs_by_checkvalue)) {
                                foreach ($childs_by_checkvalue as $k => $childs_blocks) {
                                    if ($idc == $k) {
                                        foreach ($childs_blocks as $childs) {
                                            $script2 .= "$('[bloc-id =\"bloc" . $childs . "\"]').show();
                                                 " . PluginMetademandsFieldoption::setMandatoryBlockFields(
                                                    $metaid,
                                                    $childs
                                                );
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }

            $script .= "if ($(this).val() == $idc || $idc == -1 ) {";

            //specific for radio / dropdowns - one value
            $script .= PluginMetademandsFieldoption::hideAllblockbyDefault($data);
            if (!isset($_SESSION['plugin_metademands'][$metaid]['fields'][$id])) {
                $script .= PluginMetademandsFieldoption::emptyAllblockbyDefault($check_values);
            }
            $script .= "$('[bloc-id =\"bloc'+$hidden_block+'\"]').show();";
            $script .= PluginMetademandsFieldoption::setMandatoryBlockFields($metaid, $hidden_block);

            $blocks_idc[] = $hidden_block;
            if (is_array($childs_by_checkvalue)) {
                foreach ($childs_by_checkvalue as $k => $childs_blocks) {
                    if ($idc == $k) {
                        foreach ($childs_blocks as $childs) {
                            $blocks_idc[] = $childs;
                            $script .= "$('[bloc-id =\"bloc" . $childs . "\"]').show();
                                                     " . PluginMetademandsFieldoption::setMandatoryBlockFields(
                                    $metaid,
                                    $childs
                                );
                        }
                    }
                }
            }

            if (isset($_SESSION['plugin_metademands'][$metaid]['fields'][$id])) {
                $session_value = $_SESSION['plugin_metademands'][$metaid]['fields'][$id];
                if (is_array($session_value)) {
                    foreach ($session_value as $k => $fieldSession) {
                        if ($fieldSession == $idc && $hidden_block > 0) {
                            $script2 .= "$('[bloc-id =\"bloc" . $hidden_block . "\"]').show();";
                        }
                    }
                } else {
                    if ($session_value == $idc && $hidden_block > 0) {
                        $script2 .= "$('[bloc-id =\"bloc" . $hidden_block . "\"]').show();";
                    }
                }
            }

//            if (isset($_SESSION['plugin_metademands'][$data["plugin_metademands_metademands_id"]]['fields'][$data["id"]])
//                && ($_SESSION['plugin_metademands'][$data["plugin_metademands_metademands_id"]]['fields'][$data["id"]] == $idc || $idc == -1)) {
//                $script2 .= "$('[bloc-id =\"bloc" . $hidden_block . "\"]').show();";
//            }

            $script .= " } else {";
//            $script .= "if ($(this).val() != $idc) {";
////            if (is_array($blocks_idc) && count($blocks_idc) > 0) {
////                foreach ($blocks_idc as $k => $block_idc) {
////                    $script .= "$('[bloc-id =\"bloc" . $block_idc . "\"]').hide();";
////                }
////            }
//
//            $script .= " }";
//
//            $script .= "if ($(this).val() == 0) {";
//            $script .= PluginMetademandsFieldoption::hideAllblockbyDefault($data);
//            $script .= " }";
            $script .= " }";
            if ($data["item"] == "ITILCategory_Metademands") {
                if (isset($_GET['itilcategories_id']) && $idc == $_GET['itilcategories_id']) {
                    $script2 .= "$('[bloc-id =\"bloc" . $hidden_block . "\"]').show();
                              " . PluginMetademandsFieldoption::setMandatoryBlockFields($metaid, $hidden_block);
                }
            }
        }

        $script .= "fixButtonIndicator();});";

        echo Html::scriptBlock('$(document).ready(function() {' . $script2 . " " . $script . '});');
    }


    public static function getFieldValue($field, $lang)
    {
        $dbu = new DbUtils();
        if (!empty($field['custom_values'])
            && $field['item'] == 'other') {
            $custom_values = PluginMetademandsFieldParameter::_unserialize($field['custom_values']);
            $custom_values[0] = Dropdown::EMPTY_VALUE;
            foreach ($custom_values as $k => $val) {
                if (!empty($ret = PluginMetademandsField::displayField($field["id"], "custom" . $k, $lang))) {
                    $custom_values[$k] = $ret;
                }
                if (isset($custom_values[$field['value']])) {
                    return $custom_values[$field['value']];
                }
            }
        } else {
            if ($field['value'] != 0) {
                switch ($field['item']) {
                    case 'ITILCategory_Metademands':
                        return Dropdown::getDropdownName(
                            $dbu->getTableForItemType('ITILCategory'),
                            $field['value']
                        );
                    case 'mydevices':
                        $splitter = explode("_", $field['value']);
                        if (count($splitter) == 2) {
                            $itemtype = $splitter[0];
                            $items_id = $splitter[1];
                        }
                        if (isset($items_id)) {
                            return Dropdown::getDropdownName(
                                $dbu->getTableForItemType($itemtype),
                                $items_id
                            );
                        } else {
                            return "";
                        }
                    case 'urgency':
                        return Ticket::getUrgencyName($field['value']);
                    case 'impact':
                        return Ticket::getImpactName($field['value']);
                    case 'priority':
                        return Ticket::getPriorityName($field['value']);
                    default:
                        return Dropdown::getDropdownName(
                            $dbu->getTableForItemType($field['item']),
                            $field['value']
                        );
                }
            }
        }
    }

    public static function displayFieldItems(
        &$result,
        $formatAsTable,
        $style_title,
        $label,
        $field,
        $return_value,
        $lang
    ) {
        if (!empty($field['custom_values'])
            && $field['item'] == 'other' && $field['value'] > 0) {
            $custom_values = PluginMetademandsFieldParameter::_unserialize($field['custom_values']);
            $custom_values[0] = Dropdown::EMPTY_VALUE;

            foreach ($custom_values as $k => $val) {
                if (!empty($ret = PluginMetademandsField::displayField($field["id"], "custom" . $k, $lang))) {
                    $custom_values[$k] = $ret;
                }
            }
            if (isset($custom_values[$field['value']])) {
                if ($formatAsTable) {
                    $result[$field['rank']]['content'] .= "<td $style_title>";
                }
                $result[$field['rank']]['content'] .= $label;
                if ($formatAsTable) {
                    $result[$field['rank']]['content'] .= "</td><td>";
                }
                $result[$field['rank']]['content'] .= $custom_values[$field['value']];
                if ($formatAsTable) {
                    $result[$field['rank']]['content'] .= "</td>";
                }

                $result[$field['rank']]['display'] = true;
            }
        } else {
            if ($field['value'] != 0) {
                switch ($field['item']) {
                    case 'mydevices':
                        $result[$field['rank']]['display'] = true;
                        if ($formatAsTable) {
                            $result[$field['rank']]['content'] .= "<td $style_title>";
                        }
                        $result[$field['rank']]['content'] .= $label;
                        if ($formatAsTable) {
                            $result[$field['rank']]['content'] .= "</td><td>";
                        }

                        $splitter = explode("_", $field['value']);
                        if (count($splitter) == 2) {
                            $itemtype = $splitter[0];
                            $items_id = $splitter[1];
                        }
                        if ($itemtype && $items_id) {
                            $result[$field['rank']]['content'] .= self::getFieldValue($field, $lang);
                        }
                        if ($formatAsTable) {
                            $result[$field['rank']]['content'] .= "</td>";
                        }
                        break;
                    case 'priority':
                    case 'impact':
                    case 'urgency':
                        $result[$field['rank']]['display'] = true;
                        if ($formatAsTable) {
                            $result[$field['rank']]['content'] .= "<td $style_title>";
                        }
                        $result[$field['rank']]['content'] .= $label;
                        if ($formatAsTable) {
                            $result[$field['rank']]['content'] .= "</td>";
                            $result[$field['rank']]['content'] .= "<td>";
                        }
                        $result[$field['rank']]['content'] .= self::getFieldValue($field, $lang);
                        if ($formatAsTable) {
                            $result[$field['rank']]['content'] .= "</td>";
                        }
                        break;
                    default:
                        $hidden = $field['hidden'];
                        if ($hidden == 0) {
                            $result[$field['rank']]['display'] = true;
                            if ($formatAsTable) {
                                $result[$field['rank']]['content'] .= "<td $style_title>";
                            }
                            $result[$field['rank']]['content'] .= $label;
                            if ($formatAsTable) {
                                $result[$field['rank']]['content'] .= "</td><td>";
                            }
                            $result[$field['rank']]['content'] .= self::getFieldValue($field, $lang);
                            if ($formatAsTable) {
                                $result[$field['rank']]['content'] .= "</td>";
                            }
                        }
                        break;
                }
            }
        }

        return $result;
    }
}
