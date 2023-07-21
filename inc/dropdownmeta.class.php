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

    public static $dropdown_meta_items     = ['', 'other', 'ITILCategory_Metademands', 'urgency', 'impact', 'priority',
        'mydevices'];

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

    static function showWizardField($data, $namefield, $value,  $on_basket, $itilcategories_id) {

        $metademand = new PluginMetademandsMetademand();
        $metademand->getFromDB($data['plugin_metademands_metademands_id']);

        if (empty($comment = PluginMetademandsField::displayField($data['id'], 'comment'))) {
            $comment = $data['comment'];
        }

        switch ($data['item']) {
            case 'other':
                if (!empty($data['custom_values'])) {
                    $data['custom_values'] = array_merge([0 => Dropdown::EMPTY_VALUE], PluginMetademandsField::_unserialize($data['custom_values']));
                    foreach ($data['custom_values'] as $k => $val) {
                        if (!empty($ret = PluginMetademandsField::displayField($data["id"], "custom" . $k))) {
                            $data['custom_values'][$k] = $ret;
                        }
                    }

                    $defaults = PluginMetademandsField::_unserialize($data['default_values']);

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
                        $data['custom_values'],
                        ['value'    => $value,
                            'width'    => '100%',
                            'display'  => false,
                            'required' => ($data['is_mandatory'] ? "required" : ""),
                        ]
                    );
                }
                break;

            case 'ITILCategory_Metademands':
                if ($on_basket == false) {
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
                if ($data['readonly'] == 1 && isset($_SESSION['glpiactiveprofile']['interface'])
                    && $_SESSION['glpiactiveprofile']['interface'] == 'central') {
                    $readonly = 0;
                }
                $opt = ['name' => $nameitil . "_plugin_servicecatalog_itilcategories_id",
                    'right' => 'all',
                    'value' => $value,
                    'condition' => ["id" => $values],
                    'display' => false,
                    'readonly' => $readonly ?? false,
                    'class' => 'form-select itilmeta'];
                if ($data['is_mandatory'] == 1) {
                    $opt['specific_tags'] = ['required' => ($data['is_mandatory'] == 1 ? "required" : "")];
                }
                $field = "";
                $field .= ITILCategory::dropdown($opt);
                $field .= "<input type='hidden' name='" . $nameitil . "_plugin_servicecatalog_itilcategories_id_key' value='" . $data['id'] . "' >";
                if ($readonly == 1) {
                    $field .= Html::hidden($nameitil . "_plugin_servicecatalog_itilcategories_id", ['value' => $value]);
                }
                break;
            case 'mydevices':
                $field  = "";
                if ($on_basket == false) {
                    // My items
                    //TODO : used_by_ticket -> link with item's ticket
                    $field = "";

                    $_POST['field'] = $namefield . "[" . $data['id'] . "]";
                    //                     $users_id = 0;
                    if ($data['link_to_user'] > 0) {
                        echo "<div id='mydevices_user" . $data['link_to_user'] . "' class=\"input-group\">";
                        $fieldUser = new PluginMetademandsField();
                        $fieldUser->getFromDBByCrit(['id'   => $data['link_to_user'],
                            'type' => "dropdown_object",
                            'item' => User::getType()]);

                        $_POST['value']        = ($fieldUser->fields['default_use_id_requester'] == 0) ? 0 : Session::getLoginUserID();
                        $_POST['id_fielduser'] = $data['link_to_user'];
                        $_POST['fields_id']    = $data['id'];
                        $_POST['metademands_id']    = $data['plugin_metademands_metademands_id'];
                        include(PLUGIN_METADEMANDS_DIR . "/ajax/umydevicesUpdate.php");
                        echo "</div>";
                    } else {
                        $rand  = mt_rand();
                        $p     = ['rand'  => $rand,
                            'name'  => $_POST["field"],
                            'value' => $_SESSION['plugin_metademands'][$data['plugin_metademands_metademands_id']]['fields'][$data['id']] ?? 0];
                        $field .= PluginMetademandsField::dropdownMyDevices(Session::getLoginUserID(), $_SESSION['glpiactiveentities'], 0, 0, $p, false);
                    }
                } else {
                    $dbu      = new DbUtils();
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
                $field  = "";
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
                    $meta_tt = $ticket->getITILTemplateToUse(0, $metademand->fields['type'], $itilcategories_id, $metademand->fields['entities_id']);
                    if (isset($meta_tt->predefined['urgency'])) {
                        $default_value    = $meta_tt->predefined['urgency'];
                        $options['value'] = $_SESSION['plugin_metademands'][$data['plugin_metademands_metademands_id']]['fields'][$data['id']] ?? $default_value;
                    }
                }
                $options['name']     = $namefield . "[" . $data['id'] . "]";
                $options['display']  = false;
                $options['required'] = ($data['is_mandatory'] ? "required" : "");
                $field               .= Ticket::dropdownUrgency($options);
                break;
            case 'impact':
                $field  = "";
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
                    $meta_tt = $ticket->getITILTemplateToUse(0, $metademand->fields['type'], $itilcategories_id, $metademand->fields['entities_id']);
                    if (isset($meta_tt->predefined['impact'])) {
                        $default_value    = $meta_tt->predefined['impact'];
                        $options['value'] = $_SESSION['plugin_metademands'][$data['plugin_metademands_metademands_id']]['fields'][$data['id']] ?? $default_value;
                    }
                }
                $options['name']     = $namefield . "[" . $data['id'] . "]";
                $options['display']  = false;
                $options['required'] = ($data['is_mandatory'] ? "required" : "");
                $field               .= Ticket::dropdownImpact($options);
                break;
            case 'priority':
                $field  = "";
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
                    $meta_tt = $ticket->getITILTemplateToUse(0, $metademand->fields['type'], $itilcategories_id, $metademand->fields['entities_id']);
                    if (isset($meta_tt->predefined['priority'])) {
                        $default_value    = $meta_tt->predefined['priority'];
                        $options['value'] = $_SESSION['plugin_metademands'][$data['plugin_metademands_metademands_id']]['fields'][$data['id']] ?? $default_value;
                    }
                }
                $options['name']     = $namefield . "[" . $data['id'] . "]";
                $options['display']  = false;
                $options['required'] = ($data['is_mandatory'] ? "required" : "");
                $field               .= Ticket::dropdownPriority($options);
                break;
            default:
                break;
        }

        if ($on_basket == false) {
            echo $field;
        } else {
            return $field;
        }
    }

    static function showFieldCustomValues($values, $key, $params) {

        echo "<tr>";
        echo "<td>";
        if (is_array($values) && !empty($values)) {
            echo "<div id='drag'>";
            echo "<table class='tab_cadre_fixe'>";
            foreach ($values as $key => $value) {
                echo "<tr>";

                echo '<td class="rowhandler control center">';
                echo "<div class=\"drag row\" style=\"cursor: move;border-width: 0 !important;border-style: none !important; border-color: initial !important;border-image: initial !important;\">";
                echo "<p id='custom_values$key'>";
                echo __('Value') . " " . $key . " ";
                $name = "custom_values[$key]";
                echo Html::input($name, ['value' => $value, 'size' => 50]);
                echo '</p>';
                echo '</div>';
                echo '</td>';

                echo '<td class="rowhandler control center">';
                echo "<div class=\"drag row\" style=\"cursor: move;border-width: 0 !important;border-style: none !important; border-color: initial !important;border-image: initial !important;\">";
                //                     echo "<p id='default_values$key'>";
                $display_default = false;
                //                     if ($params['value'] == 'dropdown_multiple') {
                $display_default = true;
                //                        echo " " . _n('Default value', 'Default values', 1, 'metademands') . " ";
                $checked = "";
                //                        if (isset($default[$key])
                //                            && $default[$key] == 1) {
                //                           $checked = "checked";
                //                        }
                //                        echo "<input type='checkbox' name='default_values[" . $key . "]'  value='1' $checked />";
                echo "<p id='default_values$key'>";
                echo " " . _n('Default value', 'Default values', 1, 'metademands') . " ";
                $name  = "default_values[" . $key . "]";
                $value = ($default[$key] ?? 0);
                Dropdown::showYesNo($name, $value);
                echo '</p>';
                //                     }
                //                     echo '</p>';
                echo '</div>';
                echo '</td>';

                echo '<td class="rowhandler control center">';
                echo "<div class=\"drag row\" style=\"cursor: move;border-width: 0 !important;border-style: none !important; border-color: initial !important;border-image: initial !important;\">";
                echo "<i class=\"fas fa-grip-horizontal grip-rule\"></i>";
                if (isset($params['id'])) {
                    echo PluginMetademandsField::showSimpleForm(
                        PluginMetademandsField::getFormURL(),
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
            PluginMetademandsField::initCustomValue(max(array_keys($values)), false, $display_default);
            echo "</td>";
            echo "</tr>";
        } else {
            //                  echo "<tr>";
            //                  echo "<td>";
            echo __('Value') . " 1 ";
            echo Html::input('custom_values[1]', ['size' => 50]);
            echo "</td>";
            echo "<td>";
            $display_default = false;
            //                  if ($params['value'] == 'dropdown_multiple') {
            $display_default = true;
            //                     echo " " . _n('Default value', 'Default values', 1, 'metademands') . " ";
            //                     echo '<input type="checkbox" name="default_values[1]"  value="1"/>';
            echo "<p id='default_values1'>";
            echo " " . _n('Default value', 'Default values', 1, 'metademands') . " ";
            $name  = "default_values[1]";
            $value = 1;
            Dropdown::showYesNo($name, $value);
            echo '</p>';
            echo "</td>";
            //                  }
            echo "</tr>";

            echo "<tr>";
            echo "<td colspan='2' align='right' id='show_custom_fields'>";
            PluginMetademandsField::initCustomValue(1, false, $display_default);
            echo "</td>";
            echo "</tr>";
        }

    }

    static function getParamsValueToCheck($fieldoption, $item, $params)
    {
        echo "<tr>";
        echo "<td>";
        echo __('Value to check', 'metademands');
        echo " ( " . Dropdown::EMPTY_VALUE . " = " . __('Not null value', 'metademands') . ")";
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
                $opt = ['name' => $name,
                    'right' => 'all',
                    'value' => $params['check_value'],
                    'condition' => ["id" => $values],
                    'display' => true,
                    'used' => $already_used];
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
                    $params['item']::Dropdown(["name" => $name,
                        "value" => $params['check_value'], 'used' => $already_used]);
                } else {
                    if ($params["item"] != "other" && $params["type"] == "dropdown_multiple") {
                        $elements[0] = Dropdown::EMPTY_VALUE;
                        if (is_array(json_decode($params['custom_values'], true))) {
                            $elements += json_decode($params['custom_values'], true);
                        }
                        foreach ($elements as $key => $val) {
                            if ($key != 0) {
                                $elements[$key] = $params["item"]::getFriendlyNameById($key);
                            }
                        }
                    } else {
                        $elements[0] = Dropdown::EMPTY_VALUE;
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

    static function isCheckValueOK($value, $check_value)
    {
        if (($check_value == PluginMetademandsField::$not_null || $check_value == 0) && empty($value)) {
            return false;
        } else if ($check_value != $value
            && ($check_value != PluginMetademandsField::$not_null && $check_value != 0)) {
            return false;
        }
    }

    static function fieldsLinkScript($data, $idc, $rand) {

    }

    static function fieldsHiddenScript($data) {

        $check_values = $data['options'];
        $id = $data["id"];

        $name = "field[" . $data["id"] . "]";
        $script = "console.log('fieldsHiddenScript-dropdown $id');
                $('[name=\"$name\"]').change(function() {";


        $script2 = "";
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

            if (isset($_SESSION['plugin_metademands'][$data["plugin_metademands_metademands_id"]]['fields'][$data["id"]])
                && ($_SESSION['plugin_metademands'][$data["plugin_metademands_metademands_id"]]['fields'][$data["id"]] == $idc
                    || ($_SESSION['plugin_metademands'][$data["plugin_metademands_metademands_id"]]['fields'][$data["id"]] != 0 && $idc == 0))) {
                $script2 .= "$('[id-field =\"field" . $hidden_link . "\"]').show();";
            } else {
                if ($data['type'] == "dropdown_object" && $data['item'] == 'User') {
                    if (Session::getLoginUserID() == $idc) {
                        $script2 .= "$('[id-field =\"field" . $hidden_link . "\"]').show();";
                    }
                }
            }
        }
        $script .= "$.each( tohide, function( key, value ) {           
                        if(value == true){
                            $('[id-field =\"field'+key+'\"]').hide();
                            " .PluginMetademandsFieldoption::resetMandatoryFieldsByField($hidden_link)."
                            $('[name =\"field['+key+']\"]').removeAttr('required');
                        }else{
                            $('[id-field =\"field'+key+'\"]').show();
                        }
                    });
              });";
        //Initialize id default value
        foreach ($check_values as $idc => $check_value) {
            $hidden_link = $check_value['hidden_link'];
            if (is_array(PluginMetademandsField::_unserialize($data['default_values']))) {
                $default_values = PluginMetademandsField::_unserialize($data['default_values']);

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
        $check_values = $data['options'];
        $id = $data["id"];

        $name = "field[" . $data["id"] . "]";

        $script = "console.log('blocksHiddenScript-dropdown $id');
                    $('[name=\"$name\"]').change(function() { ";
        $script2 = "";
        $script .= "var tohide = {};";
        foreach ($check_values as $idc => $check_value) {
            $hidden_block = $check_value['hidden_block'];

            $script .= "if ($hidden_block in tohide) {
                        } else {
                            tohide[$hidden_block] = true;
                        }
                        if ($(this).val() == $idc || ($(this).val() != 0 &&  $idc == 0 )) {
                            tohide[$hidden_block] = false;
                        }";

            $script2 .= "$('[bloc-id =\"bloc" . $hidden_block . "\"]').hide();
                            " . PluginMetademandsFieldoption::setMandatoryBlockFields($metaid, $hidden_block);

            if (isset($_SESSION['plugin_metademands'][$data["plugin_metademands_metademands_id"]]['fields'][$data["id"]])
                && ($_SESSION['plugin_metademands'][$data["plugin_metademands_metademands_id"]]['fields'][$data["id"]] == $idc
                    || ($_SESSION['plugin_metademands'][$data["plugin_metademands_metademands_id"]]['fields'][$data["id"]] != 0 && $idc == 0))) {
                $script2 .= "$('[bloc-id =\"bloc" . $hidden_block . "\"]').show();";
            } else {
                if ($data['type'] == "dropdown_object" && $data['item'] == 'User') {
                    if (Session::getLoginUserID() == $idc) {
                        $script2 .= "$('[bloc-id =\"bloc" . $hidden_block . "\"]').show();";
                    }
                }
            }

            if (isset($check_value['childs_blocks']) && $check_value['childs_blocks'] != null) {
                $childs_blocks = json_decode($check_value['childs_blocks'], true);
                if (isset($childs_blocks)
                    && is_array($childs_blocks)
                    && count($childs_blocks) > 0) {
                    foreach ($childs_blocks as $childs) {
                        if (is_array($childs)) {
                            foreach ($childs as $childs_block) {
                                $script .= "$('[bloc-id =\"bloc" . $childs_block . "\"]').hide();
                                                            " .PluginMetademandsFieldoption::resetMandatoryBlockFields($childs_block);
                            }
                        }
                    }
                }
            }
        }
        $script .= "$.each( tohide, function( key, value ) {
                        if(value == true){
                            $('[bloc-id =\"bloc'+key+'\"]').hide();
                            " .PluginMetademandsFieldoption::resetMandatoryBlockFields($hidden_block)."
                        } else {
                            $('[bloc-id =\"bloc'+key+'\"]').show();
                            " . PluginMetademandsFieldoption::setMandatoryBlockFields($metaid, $hidden_block)."
                        }
                    });";

        foreach ($check_values as $idc => $check_value) {
            $hidden_block = $check_value['hidden_block'];
            if ($hidden_block > 0) {
                $script2 .= PluginMetademandsFieldoption::resetMandatoryBlockFields($hidden_block);
            }
            $childs_blocks = [];
            if (isset($data['options'])) {
                $opts = $data['options'];
                foreach ($opts as $optid => $opt) {
                    if ($optid == $idc) {
                        if (!empty($opt['childs_blocks'])) {
                            $childs_blocks[] = json_decode($opt['childs_blocks'], true);
                        }
                    }
                }
            }

            if (is_array($childs_blocks) && count($childs_blocks) > 0) {
                if (isset($idc)) {
                    $script .= "if ((($(this).val() != $idc && $idc != 0 ) ||  ($(this).val() == 0 &&  $idc == 0 ) )) {";
                    foreach ($childs_blocks as $childs) {
                        if (is_array($childs)) {
                            foreach ($childs as $k => $v) {
                                if (!is_array($v)) {
                                    $script .= PluginMetademandsFieldoption::resetMandatoryBlockFields($v);
                                }
                            }
                        }
                    }

                    $script .= "}";

                    foreach ($childs_blocks as $childs) {
                        if (is_array($childs)) {
                            foreach ($childs as $k => $v) {
                                if ($v > 0) {
                                    $hiddenblocks[] = $v;
                                    $_SESSION['plugin_metademands']['hidden_blocks'] = $hiddenblocks;
                                }
                            }
                        }
                    }
                }
            }
            //Initialize id default value
            if (is_array(PluginMetademandsField::_unserialize($data['default_values']))) {
                $default_values = PluginMetademandsField::_unserialize($data['default_values']);

                foreach ($default_values as $k => $v) {
                    if ($v == 1) {
                        if ($idc == $k) {
                            $script2 .= "$('[bloc-id =\"bloc" . $hidden_block . "\"]').show();
                            " . PluginMetademandsFieldoption::setMandatoryBlockFields($metaid, $hidden_block);
                        }
                    }
                }
            }
        }
        $script .= "fixButtonIndicator();});";

        echo Html::scriptBlock('$(document).ready(function() {' . $script2 . " " . $script . '});');
    }


    public static function getFieldValue($field)
    {

        $dbu = new DbUtils();
        if (!empty($field['custom_values'])
            && $field['item'] == 'other') {
            return "";
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
                            $field['value']);
                }
            }
        }
    }

    public static function displayFieldItems(&$result, $formatAsTable, $style_title, $label, $field, $return_value, $lang)
    {

        if (!empty($field['custom_values'])
            && $field['item'] == 'other') {
            $custom_values = array_merge([0 => Dropdown::EMPTY_VALUE], PluginMetademandsField::_unserialize($field['custom_values']));
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
                            $result[$field['rank']]['content'] .= self::getFieldValue($field);
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
                        $result[$field['rank']]['content'] .= self::getFieldValue($field);
                        if ($formatAsTable) {
                            $result[$field['rank']]['content'] .= "</td>";
                        }
                        break;
                    default:
                        $result[$field['rank']]['display'] = true;
                        if ($formatAsTable) {
                            $result[$field['rank']]['content'] .= "<td $style_title>";
                        }
                        $result[$field['rank']]['content'] .= $label;
                        if ($formatAsTable) {
                            $result[$field['rank']]['content'] .= "</td><td>";
                        }
                        $result[$field['rank']]['content'] .= self::getFieldValue($field);
                        if ($formatAsTable) {
                            $result[$field['rank']]['content'] .= "</td>";
                        }
                        break;
                }
            }
        }

        return $result;
    }
}