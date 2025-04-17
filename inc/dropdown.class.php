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

        $field = "";

        $opt = ['value'     => $value,
            'entity'    => $_SESSION['glpiactiveentities'],
            'name'      => $namefield . "[" . $data['id'] . "]",
            'display'   => false,
//                    'width' => '400px'
        ];
        if (isset($data['is_mandatory']) && $data['is_mandatory'] ==1) {
            $opt['specific_tags'] = ['required' => ($data['is_mandatory'] == 1 ? "required" : "")];
        }
//        if (!($item = getItemForItemtype($data['item']))) {
//            break;
//        }

        switch ($data['item']) {
            case "Location" :
                if ($data['link_to_user'] > 0) {
                    echo "<div id='location_user" . $data['link_to_user'] . "' class=\"input-group\">";
                    $_POST['field']        = $namefield . "[" . $data['id'] . "]";
                    $_POST['locations_id'] = $value;
                    $fieldUser             = new PluginMetademandsField();
                    $fieldUser->getFromDBByCrit(['id'   => $data['link_to_user'],
                        'type' => "dropdown_object",
                        'item' => User::getType()]);

                    $fieldparameter            = new PluginMetademandsFieldParameter();
                    if (isset($fieldUser->fields['id']) && $fieldparameter->getFromDBByCrit(['plugin_metademands_fields_id' => $fieldUser->fields['id']])) {
                        $_POST['value']        = (isset($fieldparameter->fields['default_use_id_requester'])
                            && $fieldparameter->fields['default_use_id_requester'] == 0) ? 0 : Session::getLoginUserID();

                        if (empty($_POST['value'])) {
                            $user = new User();
                            $user->getFromDB(Session::getLoginUserID());
                            $_POST['value'] = ($fieldparameter->fields['default_use_id_requester_supervisor'] == 0) ? 0 : ($user->fields['users_id_supervisor'] ?? 0);
                        }
                    }

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
                    $options['width']    = "400px";
                    $options['display'] = false;
                    if ($data['is_mandatory'] == 1) {
                        $options['specific_tags'] = ['required' => ($data['is_mandatory'] == 1 ? "required" : "")];
                    }
                    //TODO Error if mode basket : $value good value - not $_SESSION['plugin_metademands'][$data['plugin_metademands_metademands_id']]['fields'][$data['id']]
                    $options['value'] = $_SESSION['plugin_metademands'][$data['plugin_metademands_metademands_id']]['fields'][$data['id']] ?? 0;
                    $field            .= Location::dropdown($options);
                }
                break;

            case "UserTitle" :
                if ($data['link_to_user'] > 0) {
                    echo "<div id='title_user" . $data['link_to_user'] . "' class=\"input-group\">";
                    $_POST['field']        = $namefield . "[" . $data['id'] . "]";
                    $_POST['usertitles_id'] = $value;
                    $fieldUser             = new PluginMetademandsField();
                    $fieldUser->getFromDBByCrit(['id'   => $data['link_to_user'],
                        'type' => "dropdown_object",
                        'item' => User::getType()]);

                    $fieldparameter            = new PluginMetademandsFieldParameter();
                    if (isset($fieldUser->fields['id']) && $fieldparameter->getFromDBByCrit(['plugin_metademands_fields_id' => $fieldUser->fields['id']])) {
                        $_POST['value']        = (isset($fieldparameter->fields['default_use_id_requester'])
                            && $fieldparameter->fields['default_use_id_requester'] == 0) ? 0 : Session::getLoginUserID();

                        if (empty($_POST['value'])) {
                            $user = new User();
                            $user->getFromDB(Session::getLoginUserID());
                            $_POST['value'] = ($fieldparameter->fields['default_use_id_requester_supervisor'] == 0) ? 0 : ($user->fields['users_id_supervisor'] ?? 0);
                        }
                    }

                    $_POST['id_fielduser'] = $data['link_to_user'];
                    $_POST['fields_id']    = $data['id'];
                    $_POST['metademands_id']    = $data['plugin_metademands_metademands_id'];
                    if ($data['is_mandatory'] == 1) {
                        $_POST['is_mandatory'] = 1;
                    }
                    include(PLUGIN_METADEMANDS_DIR . "/ajax/utitleUpdate.php");
                    echo "</div>";
                } else {
                    $options['name']    = $namefield . "[" . $data['id'] . "]";
                    $options['width']    = "400px";
                    $options['display'] = false;
                    if ($data['is_mandatory'] == 1) {
                        $options['specific_tags'] = ['required' => ($data['is_mandatory'] == 1 ? "required" : "")];
                    }
                    //TODO Error if mode basket : $value good value - not $_SESSION['plugin_metademands'][$data['plugin_metademands_metademands_id']]['fields'][$data['id']]
                    $options['value'] = $_SESSION['plugin_metademands'][$data['plugin_metademands_metademands_id']]['fields'][$data['id']] ?? 0;
                    $field            .= UserTitle::dropdown($options);
                }
                break;
            case "UserCategory" :
                if ($data['link_to_user'] > 0) {
                    echo "<div id='category_user" . $data['link_to_user'] . "' class=\"input-group\">";
                    $_POST['field']        = $namefield . "[" . $data['id'] . "]";
                    $_POST['usercategories_id'] = $value;
                    $fieldUser             = new PluginMetademandsField();
                    $fieldUser->getFromDBByCrit(['id'   => $data['link_to_user'],
                        'type' => "dropdown_object",
                        'item' => User::getType()]);

                    $fieldparameter            = new PluginMetademandsFieldParameter();
                    if (isset($fieldUser->fields['id']) && $fieldparameter->getFromDBByCrit(['plugin_metademands_fields_id' => $fieldUser->fields['id']])) {
                        $_POST['value']        = (isset($fieldparameter->fields['default_use_id_requester'])
                            && $fieldparameter->fields['default_use_id_requester'] == 0) ? 0 : Session::getLoginUserID();

                        if (empty($_POST['value'])) {
                            $user = new User();
                            $user->getFromDB(Session::getLoginUserID());
                            $_POST['value'] = ($fieldparameter->fields['default_use_id_requester_supervisor'] == 0) ? 0 : ($user->fields['users_id_supervisor'] ?? 0);
                        }
                    }

                    $_POST['id_fielduser'] = $data['link_to_user'];
                    $_POST['fields_id']    = $data['id'];
                    $_POST['metademands_id']    = $data['plugin_metademands_metademands_id'];
                    if ($data['is_mandatory'] == 1) {
                        $_POST['is_mandatory'] = 1;
                    }
                    include(PLUGIN_METADEMANDS_DIR . "/ajax/ucategoryUpdate.php");
                    echo "</div>";
                } else {
                    $options['name']    = $namefield . "[" . $data['id'] . "]";
                    $options['width']    = "400px";
                    $options['display'] = false;
                    if ($data['is_mandatory'] == 1) {
                        $options['specific_tags'] = ['required' => ($data['is_mandatory'] == 1 ? "required" : "")];
                    }
                    //TODO Error if mode basket : $value good value - not $_SESSION['plugin_metademands'][$data['plugin_metademands_metademands_id']]['fields'][$data['id']]
                    $options['value'] = $_SESSION['plugin_metademands'][$data['plugin_metademands_metademands_id']]['fields'][$data['id']] ?? 0;
                    $field            .= UserCategory::dropdown($options);
                }
                break;
            default:
                if ($data['item'] == "PluginResourcesResource") {
                    $opt['showHabilitations'] = true;
                }
                if ($item = getItemForItemtype($data['item'])) {
                    $container_class = new $data['item']();
                    $field = "";
                    $field .= $container_class::dropdown($opt);
                }
                break;
        }

        echo $field;
    }

    static function showFieldCustomValues($params)
    {

    }

    static function showFieldParameters($params) {

        echo "<tr class='tab_bg_1'>";
        if ($params['object_to_create'] == 'Ticket'
            && ($params["item"] == "Location"
                || $params["item"] == "RequestType")) {
            echo "<td>";
            echo __('Use this field for child ticket field', 'metademands');
            echo "</td>";
            echo "<td>";
            Dropdown::showYesNo('used_by_child', $params['used_by_child']);
            echo "</td>";
        } else {
            echo "<td colspan='2'></td>";
        }

        if ($params["item"] == "Location"
        || $params["item"] == "UserTitle"
            || $params["item"] == "UserCategory") {
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

        echo PluginMetademandsFieldOption::showLinkHtml($item->getID(), $params);
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
                            if (!is_array($params['custom_values'])
                                && $params['custom_values'] != null
                                && is_array(json_decode($params['custom_values'], true))) {
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

    static function fieldsMandatoryScript($data) {

        $check_values = $data['options'] ?? [];
        $id = $data["id"];

        $name = "field[" . $data["id"] . "]";
        if ($data["item"] == "ITILCategory_Metademands") {
            $name = "field_plugin_servicecatalog_itilcategories_id";
        }

        $onchange = "";
        $pre_onchange = "";
        $post_onchange = "";
        $debug = (isset($_SESSION['glpi_use_mode'])
        && $_SESSION['glpi_use_mode'] == Session::DEBUG_MODE ? true : false);
        if ($debug) {
            $onchange = "console.log('fieldsHiddenScript-dropdownmeta $id');";
        }

        if (count($check_values) > 0) {

            //Si la valeur est en session
            if (isset($data['value'])) {
                $pre_onchange .= "$('[name=\"field[" . $id . "]\"]').val('" . $data['value'] . "').trigger('change');";
            }


            $onchange .= "$('[name=\"$name\"]').change(function() {";

            $onchange .= "var tohide = {};";

            $display = 0;
            foreach ($check_values as $idc => $check_value) {
                $fields_link = $check_value['fields_link'];
                $onchange .= "if ($fields_link in tohide) {
                        } else {
                            tohide[$fields_link] = true;
                        }
                        if ($(this).val() != 0 && ($(this).val() == $idc || $idc == 0 )) {
                            tohide[$fields_link] = false;
                        }";


                if (isset($data['value']) && $idc == $data['value']) {
                    $display = $fields_link;
                }

                $onchange .= "$.each( tohide, function( key, value ) {
                        if (value == true) {
                            var id = '#metademands_wizard_red'+ key;
                            $(id).html('');
                            sessionStorage.setItem('hiddenlink$name', key);
                            " . PluginMetademandsFieldoption::resetMandatoryFieldsByField($name) . "
                            $('[name =\"field['+ key +']\"]').removeAttr('required');
                        } else {
                             var id = '#metademands_wizard_red'+ key;
                             var fieldid = 'field'+ key;
                             $(id).html('*');
                             $('[name =\"field[' + key + ']\"]').attr('required', 'required');
                             //Special case Upload field
                                  sessionStorage.setItem('mandatoryfile$name', $fields_link);
                                 " . PluginMetademandsFieldoption::checkMandatoryFile($fields_link, $name) . "
                        }
                    });
              ";
            }

            if ($display > 0) {
                $pre_onchange .= PluginMetademandsFieldoption::setMandatoryFieldsByField($id, $display);
            }

            $onchange .= "});";

            echo Html::scriptBlock(
                '$(document).ready(function() {' . $pre_onchange . " " . $onchange . " " . $post_onchange . '});'
            );
        }
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

        if (count($check_values) > 0) {
            //Si la valeur est en session
            if (isset($data['value'])) {
                $script2 .= "$('[name^=\"field[" . $id . "]\"]').val('".$data['value']."').trigger('change');";
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
                if (is_array(PluginMetademandsFieldParameter::_unserialize($data['default']))) {
                    $default_values = PluginMetademandsFieldParameter::_unserialize($data['default']);

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
    }

    static function fieldsHiddenScript($data) {

        $check_values = $data['options'] ?? [];
        $id = $data["id"];

        $name = "field[" . $data["id"] . "]";

        $onchange = "";
        $pre_onchange = "";
        $post_onchange = "";
        $debug = (isset($_SESSION['glpi_use_mode'])
        && $_SESSION['glpi_use_mode'] == Session::DEBUG_MODE ? true : false);
        if ($debug) {
            $onchange = "console.log('fieldsHiddenScript-dropdown $id');";
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

        if (count($check_values) > 0) {
            //default hide of all hidden links
            foreach ($check_values as $idc => $check_value) {
                $hidden_link = $check_value['hidden_link'];
                $pre_onchange .= "$('[id-field =\"field" . $hidden_link . "\"]').hide();";
            }

            //Si la valeur est en session
            if (isset($data['value'])) {
                $pre_onchange .= "$('[name=\"field[" . $id . "]\"]').val('".$data['value']."').trigger('change');";
            }

            $onchange .= "$('[name=\"$name\"]').change(function() {";

            $onchange .= "var tohide = {};";
            $display = 0;
            foreach ($check_values as $idc => $check_value) {
                $hidden_link = $check_value['hidden_link'];
                $onchange .= "if ($hidden_link in tohide) {
                        } else {
                            tohide[$hidden_link] = true;
                        }
                        if ( ($(this).val() == $idc ||  ($(this).val() != 0 && $idc == -1 ))) {
                            tohide[$hidden_link] = false;
                        }";

                if (isset($data['value']) && $idc == $data['value']) {
                    $display = $hidden_link;
                }

                $onchange .= "$.each( tohide, function( key, value ) {
                        if (value == true) {
                            $('[id-field =\"field'+key+'\"]').hide();
                            sessionStorage.setItem('hiddenlink$name', key);
                            $('[name =\"field['+key+']\"]').removeAttr('required');
                            " . PluginMetademandsFieldoption::resetMandatoryFieldsByField($name);

                    if (is_array($childs_by_checkvalue)) {
                        foreach ($childs_by_checkvalue as $k => $childs_blocks) {
                            if ($idc == $k) {
                                foreach ($childs_blocks as $childs) {
                                    $onchange .= "$('[bloc-id =\"bloc" . $childs . "\"]').hide();
                                            $('[bloc-id =\"subbloc" . $childs . "\"]').hide();
                                            if (document.getElementById('ablock" . $childs . "'))
                                                document.getElementById('ablock" . $childs . "').style.display = 'none';";
                                }
                            }
                        }
                    }
                    $onchange .= "} else {
                            $('[id-field =\"field'+key+'\"]').show();
                        }
                    });
              ";
            }

            if ($display > 0) {
                $pre_onchange .= "$('[id-field =\"field" . $display . "\"]').show();";
                $pre_onchange .= PluginMetademandsFieldoption::setMandatoryFieldsByField($id, $display);
            }

            $onchange .= "});";

            echo Html::scriptBlock('$(document).ready(function() {' . $pre_onchange . " " . $onchange. " " . $post_onchange . '});');
        }
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

        if (count($check_values) > 0) {

            //by default - hide all
            $script2 .= PluginMetademandsFieldoption::hideAllblockbyDefault($data);
            if (!isset($data['value'])) {
                $script2 .= PluginMetademandsFieldoption::emptyAllblockbyDefault($check_values);
            }

            //Si la valeur est en session
            if (isset($data['value'])) {
                $script .= "$('[name=\"$name\"]').val(".$data['value'].").trigger('change');";
            }


            $script .= "$('[name=\"$name\"]').change(function() {";

            $script .= "var tohide = {};";

            $display = 0;
            foreach ($check_values as $idc => $check_value) {
                $blocks_idc = [];
                $hidden_block = $check_value['hidden_block'];

                $script .= "if ($(this).val() == $idc || $idc == -1 ) {";

                //specific for radio / dropdowns - one value
                $script .= PluginMetademandsFieldoption::hideAllblockbyDefault($data);

                $script .= "if (document.getElementById('ablock" . $hidden_block . "'))
                document.getElementById('ablock" . $hidden_block . "').style.display = 'block';
                $('[bloc-id =\"bloc'+$hidden_block+'\"]').show();
                $('[bloc-id =\"subbloc'+$hidden_block+'\"]').show();";
                $script .= PluginMetademandsFieldoption::setMandatoryBlockFields($metaid, $hidden_block);

                if (is_array($childs_by_checkvalue)) {
                    foreach ($childs_by_checkvalue as $k => $childs_blocks) {
                        if ($idc == $k) {
                            foreach ($childs_blocks as $childs) {
                                $script .= "if (document.getElementById('ablock" . $childs . "'))
                                            document.getElementById('ablock" . $childs . "').style.display = 'block';
                                            $('[bloc-id =\"bloc" . $childs . "\"]').show();
                                                     " . PluginMetademandsFieldoption::setMandatoryBlockFields(
                                        $metaid,
                                        $childs
                                    );
                            }
                        }
                    }
                }

                if (isset($data['value']) && $idc == $data['value']) {
                    $display = $hidden_block;
                }

                $script .= " }";

                $script .= "if ($(this).val() != $idc) {";
                if (is_array($blocks_idc) && count($blocks_idc) > 0) {
                    foreach ($blocks_idc as $k => $block_idc) {
                        $script .= "if (document.getElementById('ablock" . $block_idc . "'))
                                    document.getElementById('ablock" . $block_idc . "').style.display = 'none';
                                    $('[bloc-id =\"bloc" . $block_idc . "\"]').hide();
                                    $('[bloc-id =\"subbloc" . $block_idc . "\"]').hide();";
                    }
                }
                $script .= " }";

                $script .= "if ($(this).val() == 0 ) {";
                $script .= PluginMetademandsFieldoption::hideAllblockbyDefault($data);
                $script .= " }";
            }

            if ($display > 0) {
                $script2 .= "if (document.getElementById('ablock" . $display . "'))
                document.getElementById('ablock" . $display . "').style.display = 'block';
                $('[bloc-id =\"bloc" . $display . "\"]').show();
                $('[bloc-id =\"subbloc" . $display . "\"]').show();";
            }

            $script .= "fixButtonIndicator();});";

            echo Html::scriptBlock('$(document).ready(function() {' . $script2 . " " . $script . '});');
        }
    }

    public static function getFieldValue($field)
    {
        switch ($field['item']) {
            default:
                $dbu = new DbUtils();
                if (!empty($field['item'])) {
                    return Dropdown::getDropdownName(
                        $dbu->getTableForItemType($field['item']),
                        $field['value']
                    );
                }

        }
    }

    public static function displayFieldItems(&$result, $formatAsTable, $style_title, $label, $field, $return_value, $lang, $is_order = false)
    {

        $colspan = $is_order ? 6 : 1;
        $result[$field['rank']]['display'] = true;
        if ($field['value'] != 0) {
            switch ($field['item']) {
                default:
                    if ($formatAsTable) {
                        $result[$field['rank']]['content'] .= "<td $style_title colspan='$colspan'>";
                    }
                    $result[$field['rank']]['content'] .= $label;
                    if ($formatAsTable) {
                        $result[$field['rank']]['content'] .= "</td><td colspan='$colspan'>";
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
