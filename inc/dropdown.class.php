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
class PluginMetademandsDropdown extends CommonDBTM
{

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
        return __('Dropdown');
    }

    static function showWizardField($data, $namefield, $value,  $on_basket, $itilcategories_id) {

        $metademand = new PluginMetademandsMetademand();
        $metademand->getFromDB($data['plugin_metademands_metademands_id']);

        if (empty($comment = PluginMetademandsField::displayField($data['id'], 'comment'))) {
            $comment = $data['comment'];
        }


        switch ($data['item']) {

            case 'User':
                $userrand = mt_rand();
                $field    = "";

                if ($on_basket == false) {
                    $paramstooltip
                        = ['value'          => '__VALUE__',
                        'id_fielduser'   => $data['id'],
                        'metademands_id' => $data['plugin_metademands_metademands_id']];

                    $toupdate[] = ['value_fieldname'
                    => 'value',
                        'id_fielduser' => $data['id'],
                        'to_update'    => "tooltip_user" . $data['id'],
                        'url'          => PLUGIN_METADEMANDS_WEBDIR . "/ajax/utooltipUpdate.php",
                        'moreparams'   => $paramstooltip];

                    $field .= "<script type='text/javascript'>";
                    $field .= "$(function() {";
                    Ajax::updateItemJsCode(
                        "tooltip_user" . $data['id'],
                        PLUGIN_METADEMANDS_WEBDIR . "/ajax/utooltipUpdate.php",
                        $paramstooltip,
                        $namefield . "[" . $data['id'] . "]",
                        false
                    );
                    $field .= "});</script>";
                }
                $paramsloc
                    = ['value'          => '__VALUE__',
                    'id_fielduser'   => $data['id'],
                    'metademands_id' => $data['plugin_metademands_metademands_id']];

                $toupdate[] = ['value_fieldname'
                => 'value',
                    'id_fielduser' => $data['id'],
                    'to_update'    => "location_user" . $data['id'],
                    'url'          => PLUGIN_METADEMANDS_WEBDIR . "/ajax/ulocationUpdate.php",
                    'moreparams'   => $paramsloc];

                $field .= "<script type='text/javascript'>";
                $field .= "$(function() {";
                Ajax::updateItemJsCode(
                    "location_user" . $data['id'],
                    PLUGIN_METADEMANDS_WEBDIR . "/ajax/ulocationUpdate.php",
                    $paramsloc,
                    $namefield . "[" . $data['id'] . "]",
                    false
                );
                $field .= "});</script>";


                $paramsgroup
                    = ['value'          => '__VALUE__',
                    'id_fielduser'   => $data['id'],
                    'metademands_id' => $data['plugin_metademands_metademands_id']];

                $toupdate[] = ['value_fieldname'
                => 'value',
                    'id_fielduser' => $data['id'],
                    'to_update'    => "group_user" . $data['id'],
                    'url'          => PLUGIN_METADEMANDS_WEBDIR . "/ajax/ugroupUpdate.php",
                    'moreparams'   => $paramsgroup];

                $field .= "<script type='text/javascript'>";
                $field .= "$(function() {";
                Ajax::updateItemJsCode(
                    "group_user" . $data['id'],
                    PLUGIN_METADEMANDS_WEBDIR . "/ajax/ugroupUpdate.php",
                    $paramsgroup,
                    $namefield . "[" . $data['id'] . "]",
                    false
                );
                $field .= "});</script>";

                $paramsdev
                    = ['value'          => '__VALUE__',
                    'id_fielduser'   => $data['id'],
                    'metademands_id' => $data['plugin_metademands_metademands_id']];

                $toupdate[] = ['value_fieldname'
                => 'value',
                    'id_fielduser' => $data['id'],
                    'to_update'    => "mydevices_user" . $data['id'],
                    'url'          => PLUGIN_METADEMANDS_WEBDIR . "/ajax/umydevicesUpdate.php",
                    'moreparams'   => $paramsdev];

                $field .= "<script type='text/javascript'>";
                $field .= "$(function() {";
                Ajax::updateItemJsCode(
                    "mydevices_user" . $data['id'],
                    PLUGIN_METADEMANDS_WEBDIR . "/ajax/umydevicesUpdate.php",
                    $paramsdev,
                    $namefield . "[" . $data['id'] . "]",
                    false
                );
                $field .= "});</script>";

                if (empty($value)) {
                    $value = ($data['default_use_id_requester'] == 0) ? 0 : Session::getLoginUserID();
                }

                $right = "all";
                if (!empty($data['custom_values'])) {
                    $options = PluginMetademandsField::_unserialize($data['custom_values']);
                    if (isset($options['user_group']) && $options['user_group'] == 1) {
                        $condition       = getEntitiesRestrictCriteria(Group::getTable(), '', '', true);
                        $group_user_data = Group_User::getUserGroups(Session::getLoginUserID(), $condition);

                        $requester_groups = [];
                        foreach ($group_user_data as $groups) {
                            $requester_groups[] = $groups['id'];
                        }
                        if (count($requester_groups) > 0) {
                            $right = "groups";
                        }
                    }
                }

                $opt = ['name' => $namefield . "[" . $data['id'] . "]",
                    'entity' => $_SESSION['glpiactiveentities'],
                    'right' => $right,
                    'rand' => $userrand,
                    'value' => $value,
                    'display' => false,
                    'toupdate' => $toupdate,
                    'readonly' => $data['readonly'] ?? false,
                ];
                if ($data['is_mandatory'] == 1) {
                    $opt['specific_tags'] = ['required' => ($data['is_mandatory'] == 1 ? "required" : "")];
                }
                if ($data['readonly'] == 1) {
                    $field .= Html::hidden($namefield . "[" . $data['id'] . "]", ['value' => $value]);
                }
                echo User::dropdown($opt);
                break;
            case 'Group':
                $field = "";
                $cond  = [];
                $_POST['field'] = $namefield . "[" . $data['id'] . "]";

                if ($data['link_to_user'] > 0) {
                    echo "<div id='group_user" . $data['link_to_user'] . "' class=\"input-group\">";
                    $_POST['groups_id'] = $value;
                    $fieldUser          = new PluginMetademandsField();
                    $fieldUser->getFromDBByCrit(['id'   => $data['link_to_user'],
                        'type' => "dropdown_object",
                        'item' => User::getType()]);

                    $_POST['value']        = (isset($fieldUser->fields['default_use_id_requester'])
                        && $fieldUser->fields['default_use_id_requester'] == 0) ? 0 : Session::getLoginUserID();
                    $_POST['id_fielduser'] = $data['link_to_user'];
                    $_POST['fields_id']    = $data['id'];
                    $_POST['metademands_id']    = $data['plugin_metademands_metademands_id'];
                    $_POST['is_mandatory'] = $data['is_mandatory'] ?? 0;
                    include(PLUGIN_METADEMANDS_DIR . "/ajax/ugroupUpdate.php");
                    echo "</div>";
                } else {
                    $name = $namefield . "[" . $data['id'] . "]";

                    if (!empty($data['custom_values'])) {
                        $_POST['value']        = (isset($fieldUser->fields['default_use_id_requester'])
                            && $fieldUser->fields['default_use_id_requester'] == 0) ? 0 : Session::getLoginUserID();

                        $condition       = getEntitiesRestrictCriteria(Group::getTable(), '', '', true);
                        $group_user_data = Group_User::getUserGroups($_POST['value'], $condition);

                        $requester_groups = [];
                        foreach ($group_user_data as $groups) {
                            $requester_groups[] = $groups['id'];
                        }
                        $options = PluginMetademandsField::_unserialize($data['custom_values']);

                        foreach ($options as $type_group => $values) {
                            if ($type_group != 'user_group') {
                                $cond[$type_group] = $values;
                            } else {
                                $cond["glpi_groups.id"] = $requester_groups;
                            }
                        }
                        unset($cond['user_group']);
                    }
                    $val_group = (isset($_SESSION['plugin_metademands'][$data['plugin_metademands_metademands_id']]['fields'][$data['id']])
                        && !is_array($_SESSION['plugin_metademands'][$data['plugin_metademands_metademands_id']]['fields'][$data['id']])) ? $_SESSION['plugin_metademands'][$data['plugin_metademands_metademands_id']]['fields'][$data['id']] : 0;

                    $opt = ['name'      => $name,
                        'entity'    => $_SESSION['glpiactiveentities'],
                        'value'     => $val_group,
                        'condition' => $cond,
                        'display'   => false];
                    if ($data['is_mandatory'] == 1) {
                        $opt['specific_tags'] = ['required' => ($data['is_mandatory'] == 1 ? "required" : "")];
                    }

                    $field .= Group::dropdown($opt);
                }


                break;

            default:
                $cond = [];
                $field = "";
                if (!empty($data['custom_values']) && $data['item'] == 'Group') {
                    $options = PluginMetademandsField::_unserialize($data['custom_values']);
                    foreach ($options as $k => $val) {
                        if (!empty($ret = PluginMetademandsField::displayField($data["id"], "custom" . $k))) {
                            $options[$k] = $ret;
                        }
                    }
                    foreach ($options as $type_group => $val) {
                        $cond[$type_group] = $val;
                    }
                }
                $opt = ['value'     => $value,
                    'entity'    => $_SESSION['glpiactiveentities'],
                    'name'      => $namefield . "[" . $data['id'] . "]",
                    //                          'readonly'  => true,
                    'condition' => $cond,
                    'display'   => false];
                if ($data['is_mandatory'] == 1) {
                    $opt['specific_tags'] = ['required' => ($data['is_mandatory'] == 1 ? "required" : "")];
                }
                if (!($item = getItemForItemtype($data['item']))) {
                    break;
                }
                if ($data['item'] == "Location") {
                    if ($data['link_to_user'] > 0) {
                        echo "<div id='location_user" . $data['link_to_user'] . "' class=\"input-group\">";
                        $_POST['field']        = $namefield . "[" . $data['id'] . "]";
                        $_POST['locations_id'] = $value;
                        $fieldUser             = new PluginMetademandsField();
                        $fieldUser->getFromDBByCrit(['id'   => $data['link_to_user'],
                            'type' => "dropdown_object",
                            'item' => User::getType()]);

                        $_POST['value']        = (isset($fieldUser->fields['default_use_id_requester'])
                            && $fieldUser->fields['default_use_id_requester'] == 0) ? 0 : Session::getLoginUserID();
                        $_POST['id_fielduser'] = $data['link_to_user'];
                        $_POST['fields_id']    = $data['id'];
                        $_POST['metademands_id']    = $data['plugin_metademands_metademands_id'];
                        include(PLUGIN_METADEMANDS_DIR . "/ajax/ulocationUpdate.php");
                        echo "</div>";
                    } else {
                        $options['name']    = $namefield . "[" . $data['id'] . "]";
                        $options['display'] = false;
                        if ($data['is_mandatory'] == 1) {
                            $options['specific_tags'] = ['required' => ($data['is_mandatory'] == 1 ? "required" : "")];
                        }
                        //TODO Error if mode basket : $value good value - not $_SESSION['plugin_metademands'][$data['plugin_metademands_metademands_id']]['fields'][$data['id']]
                        $options['value'] = $_SESSION['plugin_metademands'][$data['plugin_metademands_metademands_id']]['fields'][$data['id']] ?? 0;
                        $field            .= Location::dropdown($options);
                    }
                } else {
                    if ($data['item'] == "PluginResourcesResource") {
                        $opt['showHabilitations'] = true;
                    }
                    $container_class = new $data['item']();
                    $field           = "";
                    $field           .= $container_class::dropdown($opt);
                }
                break;
        }

        if ($on_basket == false) {
            echo $field;
        } else {
            return $field;
        }
    }

    static function showFieldCustomValues($values, $key, $params) {

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

        switch ($field['item']) {
            default:
                $dbu = new DbUtils();
                return Dropdown::getDropdownName(
                    $dbu->getTableForItemType($field['item']),
                    $field['value']
                );
        }
    }

    public static function displayFieldItems(&$result, $formatAsTable, $style_title, $label, $field, $return_value, $lang)
    {

        if ($field['value'] != 0) {
            switch ($field['item']) {
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

        return $result;
    }

}
