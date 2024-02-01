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
 * PluginMetademandsDropdown Class 
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

    static function showWizardField($data, $namefield, $value,  $on_order, $itilcategories_id) {

        $metademand = new PluginMetademandsMetademand();
        $metademand->getFromDB($data['plugin_metademands_metademands_id']);

        if (empty($comment = PluginMetademandsField::displayField($data['id'], 'comment'))) {
            $comment = $data['comment'];
        }

        switch ($data['item']) {

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
                        if ($data['is_mandatory'] == 1) {
                            $_POST['is_mandatory'] = 1;
                        }
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

        echo $field;
    }

    static function showFieldCustomValues($values, $key, $params) {

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
            default:
                $dbu = new DbUtils();
                if ($item = $dbu->getItemForItemtype($params["item"])) {
                    //               if ($params['value'] == 'group') {
                    //                  $name = "check_value";// TODO : HS POUR LES GROUPES CAR rajout un RAND dans le dropdownname
                    //               } else {
                    $name = "check_value";
                    //               }

                    $params['item']::Dropdown(["name" => $name,
                        "value" => $params['check_value'],
                        'used' => $already_used,
                        'display_emptychoice' => false,
                        'toadd' => [-1 => __('Not null value', 'metademands')]]);
                }
//                else {
//                    if ($params["item"] != "other" && $params["type"] == "dropdown_multiple") {
//                        $elements[-1] = __('Not null value', 'metademands');
//                        if (is_array(json_decode($params['custom_values'], true))) {
//                            $elements += json_decode($params['custom_values'], true);
//                        }
//                        foreach ($elements as $key => $val) {
//                            if ($key != 0) {
//                                $elements[$key] = $params["item"]::getFriendlyNameById($key);
//                            }
//                        }
//                    } else {
//                        $elements[-1] = __('Not null value', 'metademands');
//                        if (is_array(json_decode($params['custom_values'], true))) {
//                            $elements += json_decode($params['custom_values'], true);
//                        }
//                        foreach ($elements as $key => $val) {
//                            $elements[$key] = urldecode($val);
//                        }
//                    }
//                    Dropdown::showFromArray(
//                        "check_value",
//                        $elements,
//                        ['value' => $params['check_value'], 'used' => $already_used]
//                    );
//                }
                break;
        }
    }


    static function showParamsValueToCheck($params)
    {
        if ($params['check_value'] == -1) {
            echo __('Not null value', 'metademands');
        } else {
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

    static function fieldsLinkScript($data, $idc, $rand) {

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
            $script = "console.log('taskScript-dropdown $id');";
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
        $nextsteptitle = "<i class=\"fas fa-save\"></i>&nbsp;" . __('Next', 'metademands') . "&nbsp;<i class=\"ti ti-chevron-right\"></i>";


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
            if (is_array(PluginMetademandsField::_unserialize($data['default_values']))) {
                $default_values = PluginMetademandsField::_unserialize($data['default_values']);

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

    static function fieldsHiddenScript($data) {

        $check_values = $data['options'] ?? [];
        $metaid = $data['plugin_metademands_metademands_id'];
        $id = $data["id"];

        $name = "field[" . $data["id"] . "]";

        $script = "";
        $script2 = "";
        $debug = (isset($_SESSION['glpi_use_mode'])
        && $_SESSION['glpi_use_mode'] == Session::DEBUG_MODE ? true : false);
        if ($debug) {
            $script = "console.log('fieldsHiddenScript-dropdown $id');";
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

            //if reload form
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
                            " .PluginMetademandsFieldoption::resetMandatoryFieldsByField($hidden_link)."
                            $('[name =\"field['+key+']\"]').removeAttr('required');
                        } else {
                            $('[id-field =\"field'+key+'\"]').show();
                            " .PluginMetademandsFieldoption::setMandatoryFieldsByField($id, $hidden_link)."
                        }
                    });
              ";
        }
        $script .= "});";
        //Initialize id default value
        // No default value for dropdown
//        foreach ($check_values as $idc => $check_value) {
//            $hidden_link = $check_value['hidden_link'];
//            if (is_array(PluginMetademandsField::_unserialize($data['default_values']))) {
//                $default_values = PluginMetademandsField::_unserialize($data['default_values']);
//
//                foreach ($default_values as $k => $v) {
//                    if ($v == 1) {
//                        if ($idc == $k) {
//                            $script .= " $('[id-field =\"field" . $hidden_link . "\"]').show();";
//                        }
//                    }
//                }
//            }
//        }
        echo Html::scriptBlock('$(document).ready(function() {' . $script2 . " " . $script . '});');

    }

    public static function blocksHiddenScript($data)
    {
        $metaid = $data['plugin_metademands_metademands_id'];
        $check_values = $data['options'] ?? [];
        $id = $data["id"];

        $name = "field[" . $data["id"] . "]";

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
            $script = "console.log('blocksHiddenScript-dropdown $id');";
        }
        $script .= "$('[name=\"$name\"]').change(function() {";

        $script .= "var tohide = {};";

        //by default - hide all
        $script2 .= PluginMetademandsFieldoption::hideAllblockbyDefault($check_values);
        if (!isset($_SESSION['plugin_metademands'][$metaid]['fields'][$id])) {
            $script2 .= PluginMetademandsFieldoption::emptyAllblockbyDefault($check_values);
        }
        foreach ($check_values as $idc => $check_value) {
            $blocks_idc = [];
            $hidden_block = $check_value['hidden_block'];

            $script .= "if ($(this).val() == $idc || $idc == -1 ) {";

            //specific for radio / dropdowns - one value
            $script .= PluginMetademandsFieldoption::hideAllblockbyDefault($check_values);

            $script .= "$('[bloc-id =\"bloc'+$hidden_block+'\"]').show();";
            $script .= PluginMetademandsFieldoption::setMandatoryBlockFields($metaid, $hidden_block);

            if (is_array($childs_by_checkvalue)) {
                foreach ($childs_by_checkvalue as $k => $childs_blocks) {
                    if ($idc == $k) {
                        foreach ($childs_blocks as $childs) {
                            $script .= "$('[bloc-id =\"bloc" . $childs . "\"]').show();
                                                     " . PluginMetademandsFieldoption::setMandatoryBlockFields($metaid, $childs);
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
//                && ($_SESSION['plugin_metademands'][$data["plugin_metademands_metademands_id"]]['fields'][$data["id"]] == $idc
//                    || ($_SESSION['plugin_metademands'][$data["plugin_metademands_metademands_id"]]['fields'][$data["id"]] != 0 && $idc == 0))) {
//                $script2 .= "$('[bloc-id =\"bloc" . $hidden_block . "\"]').show();";
//            }

            $script .= " }";

            $script .= "if ($(this).val() != $idc) {";
            if (is_array($blocks_idc) && count($blocks_idc) > 0) {
                foreach ($blocks_idc as $k => $block_idc) {
                    $script .= "$('[bloc-id =\"bloc" . $block_idc . "\"]').hide();";
                }
            }
            $script .= " }";

            $script .= "if ($(this).val() == 0 ) {";
            $script .= PluginMetademandsFieldoption::hideAllblockbyDefault($check_values);
            $script .= " }";

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
