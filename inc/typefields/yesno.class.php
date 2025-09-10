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
 * PluginMetademandsYesno Class
 *
 **/
class PluginMetademandsYesno extends CommonDBTM
{
    public const CLASSIC_DISPLAY = 0;
    public const SWITCH_DISPLAY = 1;
    /**
     * Return the localized name of the current Type
     * Should be overloaded in each new class
     *
     * @param integer $nb Number of items
     *
     * @return string
     **/
    public static function getTypeName($nb = 0)
    {
        return __('Yes / No', 'metademands');
    }

    public static function showWizardField($data, $namefield, $value, $on_order)
    {
        $options[1] = __('No');
        $options[2] = __('Yes');

        $defaults = "";
        if (isset($data['custom_values'])) {
            $defaults = PluginMetademandsFieldParameter::_unserialize($data['custom_values']);
        }


        if ($value == "") {
            //warning : this is default value
            $value = $data['custom_values'] ?? 0;
        }

        $value = !empty($value) ? $value : $defaults;

        if (is_array($value)) {
            $value = "";
        }

        if ($data["display_type"] == self::CLASSIC_DISPLAY) {
            $field = "";
            $field .= Dropdown::showFromArray(
                $namefield . "[" . $data['id'] . "]",
                $options,
                [
                    'value' => $value,
                    'display_emptychoice' => true,
                    'class' => 'yesno',
                    //                    'noselect2' => true,
                    'width' => '70px',
                    'required' => ($data['is_mandatory'] ? "required" : ""),
                    'id' => $data['id'],
                    'display' => false,
                ]
            );
            echo $field;
        } else {
            self::showSwitchField($data, $namefield, $value);
        }


    }


    /**
     * @param $name
     * @param $value
     */
    public static function showSwitchField($data, $namefield, $value)
    {

        $name = $namefield . "[" . $data['id'] . "]";
        $required = ($data['is_mandatory'] && $value == 0)? "required" : "";
        $id = $name . "-toggle";

        echo "<label class='ios-switch-sm'>";

        $checked = "";
        if ($value == 2) {
            $checked = "checked='checked'";
        }
        if ($value == 0) {
            $value = 1;
        }
        echo "<input type='checkbox' id='$id' name='$name' $checked isswitch='isswitch' >";
        echo "<span class='slider'>";
        echo "<span class='checkmark-on'><i class='ti ti-check'></i></span>";
        echo "<span class='checkmark-off'></span>";
        echo "<span class='checkmark-empty'></span>";
        echo "</span>";
        echo "</label>";

        echo Html::hidden($name, ['id' => $name, 'value' => $value]);

        echo Html::scriptBlock("(function(){
        const toggle = document.getElementById('$id');
        const hidden = document.getElementById('$name');

        toggle.addEventListener('change', function () {
            hidden.value = this.checked ? '2' : '1';
            if (this.checked) {
                toggle.setAttribute('checked', 'checked');
//                toggle.setAttribute('required', '');
//                toggle.setAttribute('invalid', '');
            } else {
                toggle.setAttribute('checked', '');
            }
        });
    })();
    ");
    }

    public static function showFieldCustomValues($params)
    {
        // Show yes/no default value
        echo "<tr class='tab_bg_1'>";
        echo "<td>";
        echo _n('Default value', 'Default values', 1, 'metademands') . "&nbsp;";
        $p = [];

        if (isset($params['custom_values']) && !is_array($params['custom_values'])) {
            $p['value'] = $params['custom_values'];
        }
        $data[1] = __('No');
        $data[2] = __('Yes');

        if ($params["display_type"] == self::CLASSIC_DISPLAY) {
            $p['display_emptychoice'] = true;
        }
        Dropdown::showFromArray("custom", $data, $p);
        echo "</td>";
        echo "</tr>";

        echo "<tr class='tab_bg_1'>";
        echo "<td>";
        echo Html::submit("", [
            'name' => 'update',
            'class' => 'btn btn-primary',
            'icon' => 'ti ti-device-floppy',
        ]);
        echo "</td>";
        echo "</tr>";
    }

    public static function showFieldParameters($params)
    {

        $disp = [];
        $disp[self::CLASSIC_DISPLAY] = __("Classic display", "metademands");
        $disp[self::SWITCH_DISPLAY] = __("Switch display", "metademands");
        echo "<tr class='tab_bg_1'>";
        echo "<td>";
        echo __('Display type of the field', 'metademands');
        echo "</td>";
        echo "<td>";

        echo Dropdown::showFromArray(
            "display_type",
            $disp,
            ['value' => $params['display_type'], 'display' => false]
        );
        echo "</td>";
        echo "</tr>";
    }

    public static function getParamsValueToCheck($fieldoption, $item, $params)
    {
        $data[1] = __('No');
        $data[2] = __('Yes');

        // Value to check
        echo "<tr>";
        echo "<td>";
        echo __('Value to check', 'metademands');
        echo "</td>";
        echo "<td class = 'dropdown-valuetocheck'>";
        self::showValueToCheck($fieldoption, $params);
        echo "</td>";

        echo "<script type = \"text/javascript\">
                 $('td.dropdown-valuetocheck select').on('change', function() {
                 let formOption = [
                     " . $params['ID'] . ",
                         $(this).val(),
                         $('select[name=\"plugin_metademands_tasks_id\"]').val(),
                         $('select[name=\"fields_link\"]').val(),
                         $('select[name=\"hidden_link\"]').val(),
                         $('select[name=\"hidden_block\"]').val(),
                         JSON.stringify($('select[name=\"childs_blocks[][]\"]').val()),
                         $('select[name=\"users_id_validate\"]').val(),
                         $('select[name=\"checkbox_id\"]').val()
                  ];

                     reloadviewOption(formOption);
                 });";


        echo " </script>";
        if ($params['check_value'] == '') {
            $params['check_value'] = 1;
        }

        echo PluginMetademandsFieldOption::showLinkHtml($item->getID(), $params);
    }

    public static function showValueToCheck($item, $params)
    {
        $field = new PluginMetademandsFieldOption();
        $existing_options = $field->find(["plugin_metademands_fields_id" => $params["plugin_metademands_fields_id"]]);
        $already_used = [];

        $options[1] = __('No');
        $options[2] = __('Yes');
        Dropdown::showFromArray("check_value", $options, ['value' => $params['check_value'], 'used' => $already_used]);
    }

    public static function showParamsValueToCheck($params)
    {
        $options[1] = __('No');
        $options[2] = __('Yes');
        echo $options[$params['check_value']] ?? "";
    }

    public static function isCheckValueOK($value, $check_value)
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

    public static function fieldsMandatoryScript($data)
    {
        $check_values = $data['options'] ?? [];
        $id = $data["id"];
        $name = "field[" . $data["id"] . "]";

        $onchange = "";
        $pre_onchange = "";
        $post_onchange = "";
        $debug = (isset($_SESSION['glpi_use_mode'])
        && $_SESSION['glpi_use_mode'] == Session::DEBUG_MODE ? true : false);
        if ($debug) {
            $onchange = "console.log('fieldsMandatoryScript-yesno $id');";
        }

        if (count($check_values) > 0) {
            //Si la valeur est en session
            if (isset($data['value'])) {
                if ($data["display_type"] == self::CLASSIC_DISPLAY) {
                    $pre_onchange .= "$('[name=\"field[" . $id . "]\"]').val('" . $data['value'] . "').trigger('change');";
                } else {
                    if ($data['value'] == 2) {
                        $pre_onchange .= "$('[name=\"field[" . $id . "]\"]').prop('checked', true).trigger('change');";
                    }
                }
            }

            $onchange .= "$('[name^=\"field[" . $data["id"] . "]\"]').change(function() {";
            $display = 0;
            foreach ($check_values as $idc => $check_value) {
                foreach ($check_value['fields_link'] as $fields_link) {
                    $val = $idc;

                    if ($data["display_type"] == self::CLASSIC_DISPLAY) {
                        $onchange .= "if ($(this).val() == $val) {";
                    } else {
                        $onchange .= " if (this.checked) {";
                    }

                    $onchange .= "$('#metademands_wizard_red" . $fields_link . "').html('*');
                                     $('[name =\"field[' + $fields_link + ']\"]').attr('required', 'required');
                                     //Special case Upload field
                                      sessionStorage.setItem('mandatoryfile$name', $fields_link);
                                     " . PluginMetademandsFieldoption::checkMandatoryFile($fields_link, $name) . "
                                   } else {
                                      $('#metademands_wizard_red" . $fields_link . "').html('');
                                      sessionStorage.setItem('hiddenlink$name', $fields_link);
                                    " . PluginMetademandsFieldoption::resetMandatoryFieldsByField($name) . "
                                   }";

                    if (isset($data['value']) && $idc == $data['value']) {
                        $display = $fields_link;
                    }
                }
            }
            $onchange .= "});";

            if ($display > 0) {
                $pre_onchange .= PluginMetademandsFieldoption::setMandatoryFieldsByField($id, $display);
            }

            echo Html::scriptBlock(
                '$(document).ready(function() {' . $pre_onchange . " " . $onchange . " " . $post_onchange . '});'
            );
        }
    }

    public static function taskScript($data)
    {
        $check_values = $data['options'] ?? [];
        $id = $data["id"];

        $script = "";
        $script2 = "";
        $debug = (isset($_SESSION['glpi_use_mode'])
        && $_SESSION['glpi_use_mode'] == Session::DEBUG_MODE ? true : false);
        if ($debug) {
            $script = "console.log('taskScript-yesno $id');";
        }

        if (count($check_values) > 0) {
            //Si la valeur est en session
            //specific
            if (isset($data['value']) && is_array($data['value'])) {
                $values = $data['value'];
                foreach ($values as $value) {
                    $script2 .= "$('[name^=\"field[" . $id . "]\"]').val('" . $data['value'] . "').trigger('change');";
                }
            }

            $title = "<i class=\"ti ti-device-floppy\"></i>&nbsp;" . _sx('button', 'Save & Post', 'metademands');
            $nextsteptitle = __(
                'Next',
                'metademands'
            ) . "&nbsp;<i class=\"ti ti-chevron-right\"></i>";


            foreach ($check_values as $idc => $check_value) {
                foreach ($data['options'][$idc]['plugin_metademands_tasks_id'] as $tasks_id) {
                    if ($tasks_id) {
                        if (PluginMetademandsMetademandTask::setUsedTask($tasks_id, 0)) {
                            $script .= "$('[name^=\"field[" . $data["id"] . "]\"]').ready(function() {";
                            $script .= "document.getElementById('nextBtn').innerHTML = '$title'";
                            $script .= "});";
                        }
                    }
                }
            }

            $script .= "$('[name^=\"field[" . $data["id"] . "]\"]').change(function() {";

            foreach ($check_values as $idc => $check_value) {
                foreach ($data['options'][$idc]['plugin_metademands_tasks_id'] as $tasks_id) {
                    $val = $idc;

                    if ($data["display_type"] == self::CLASSIC_DISPLAY) {
                        $script .= "if ($(this).val() == $val) {";
                    } else {
                        $script .= " if (this.checked) {";
                    }
                    $script .= "$.ajax({
                                     url: '" . PLUGIN_METADEMANDS_WEBDIR . "/ajax/set_session.php',
                                     data: { tasks_id: $tasks_id,
                                  used: 1 },
                                  success: function(response){
                                       if (response != 1) {
                                           document.getElementById('nextBtn').innerHTML = '$nextsteptitle'
                                       }
                                    },
                                });
                                 ";

                    $script .= "      } else {
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
                                 ";
                    $script .= "}";

                    //            if ($idc == $data["custom_values"]) {
                    //                $script2 .= "console.log('custom $tasks_id ');";
                    //
                    //                //if reload form
                    //                if (isset($_SESSION['plugin_metademands'][$metaid]['fields'][$id])) {
                    //                    $session_value = $_SESSION['plugin_metademands'][$metaid]['fields'][$id];
                    //                    if (is_array($session_value)) {
                    //                        foreach ($session_value as $k => $fieldSession) {
                    //                            if ($fieldSession != $idc && $tasks_id > 0) {
                    //                                $script2 .= "console.log('notused');";
                    //                            }
                    //                        }
                    //                    }
                    //                }
                    //
                    //            } else {
                    ////                $script2 .= "$('[id-field =\"field" . $task_id . "\"]').hide();";
                    ////
                    ////                //if reload form
                    ////                if (isset($_SESSION['plugin_metademands'][$metaid]['fields'][$id])) {
                    ////                    $session_value = $_SESSION['plugin_metademands'][$metaid]['fields'][$id];
                    ////                    if (is_array($session_value)) {
                    ////                        foreach ($session_value as $k => $fieldSession) {
                    ////                            if ($fieldSession == $idc && $task_id > 0) {
                    ////                                $script2 .= "$('[id-field =\"field" . $task_id . "\"]').show();";
                    ////                            }
                    ////                        }
                    ////                    }
                    ////                }
                    //            }
                }
            }
            $script .= "});";

            //Initialize id default value
            foreach ($check_values as $idc => $check_value) {
                foreach ($check_value['plugin_metademands_tasks_id'] as $tasks_id) {
                    if (isset($data['custom'])) {
                        $custom_values = PluginMetademandsFieldParameter::_unserialize($data['custom']);

                        if ($idc == $custom_values) {
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
            echo Html::scriptBlock('$(document).ready(function() {' . $script2 . " " . $script . '});');
        }
    }


    public static function fieldsHiddenScript($data)
    {
        $check_values = $data['options'] ?? [];
        $id = $data["id"];
        $name = "field[" . $data["id"] . "]";

        $onchange = "";
        $pre_onchange = "";
        $post_onchange = "";
        $debug = (isset($_SESSION['glpi_use_mode'])
        && $_SESSION['glpi_use_mode'] == Session::DEBUG_MODE ? true : false);
        if ($debug) {
            $onchange = "console.log('fieldsHiddenScript-yesno $id');";
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
            //Initialize id default value
            foreach ($check_values as $idc => $check_value) {
                foreach ($check_value['hidden_link'] as $hidden_link) {
                    if (isset($data['custom'])) {
                        $custom_values = PluginMetademandsFieldParameter::_unserialize($data['custom']);
                        if ($idc == $custom_values) {
                            $post_onchange .= "$('[name^=\"field[$id]\"]').prop('checked', true).trigger('change');";
                        }
                    }
                }
            }

            //default hide of all hidden links
            foreach ($check_values as $idc => $check_value) {
                foreach ($check_value['hidden_link'] as $hidden_link) {
                    $pre_onchange .= "$('[id-field =\"field" . $hidden_link . "\"]').hide();";
                }
            }

            //Si la valeur est en session
            if (isset($data['value'])) {
                if ($data["display_type"] == self::CLASSIC_DISPLAY) {
                    $pre_onchange .= "$('[name=\"field[" . $id . "]\"]').val('" . $data['value'] . "').trigger('change');";
                } else {
                    if ($data['value'] == 2) {
                        $pre_onchange .= "$('[name=\"field[" . $id . "]\"]').prop('checked', true).trigger('change');";
                    }
                }
            }

            $onchange .= "$('[name^=\"field[" . $id . "]\"]').change(function() {";
            $display = 0;
            foreach ($check_values as $idc => $check_value) {
                foreach ($data['options'][$idc]['hidden_link'] as $hidden_link) {
                    $val = $idc;
                    if ($data["display_type"] == self::CLASSIC_DISPLAY) {
                        $onchange .= "if ($(this).val() == $val) {";
                    } else {
                        $onchange .= " if (this.checked) {";
                    }
                    $onchange .= "$('[id-field =\"field" . $hidden_link . "\"]').show();

                           } else {
                            $('[id-field =\"field" . $hidden_link . "\"]').hide();
                            sessionStorage.setItem('hiddenlink$name', $hidden_link);
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
                    $onchange .= "}";

                    if (isset($data['value']) && $idc == $data['value']) {
                        $display = $hidden_link;
                    }
                    //            }
                }
            }
            $onchange .= "});";

            if ($display > 0) {
                $pre_onchange .= "$('[id-field =\"field" . $display . "\"]').show();";
                $pre_onchange .= PluginMetademandsFieldoption::setMandatoryFieldsByField($id, $display);
            }

            echo Html::scriptBlock(
                '$(document).ready(function() {' . $pre_onchange . " " . $onchange . " " . $post_onchange . '});'
            );
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

        $onchange = "";
        $pre_onchange = "";
        $post_onchange = "";
        $debug = isset($_SESSION['glpi_use_mode'])
        && $_SESSION['glpi_use_mode'] == Session::DEBUG_MODE;
        if ($debug) {
            $script = "console.log('blocksHiddenScript-yesno $id');";
        }


        if (count($check_values) > 0) {
            //by default - hide all
            $pre_onchange .= PluginMetademandsFieldoption::hideAllblockbyDefault($data);
            if (!isset($data['value'])) {
                $pre_onchange .= PluginMetademandsFieldoption::emptyAllblockbyDefault($check_values);
            }

            //Si la valeur est en session
            if (isset($data['value'])) {
                if ($data["display_type"] == self::CLASSIC_DISPLAY) {
                    $pre_onchange .= "$('[name=\"$name\"]').val(" . $data['value'] . ").trigger('change');";
                } else {
                    if ($data['value'] == 2) {
                        $pre_onchange .= "$('[name=\"$name\"]').prop('checked', true).trigger('change');";
                    }
                }
            }

            $onchange .= "$('[name=\"$name\"]').change(function() {";

            $onchange .= "var tohide = {};";
            $display = 0;

            foreach ($check_values as $idc => $check_value) {
                foreach ($data['options'][$idc]['hidden_block'] as $hidden_block) {
                    $blocks_idc = [];

                    //Default values
                    //Warning : not use default_values
                    if (isset($data['custom'])) {
                        $custom_values = PluginMetademandsFieldParameter::_unserialize($data['custom']);

                        if ($idc == $custom_values) {
                            $post_onchange .= "$('[bloc-id =\"bloc" . $hidden_block . "\"]').show();
                        if (document.getElementById('ablock" . $hidden_block . "'))
                        document.getElementById('ablock" . $hidden_block . "').style.display = 'block';
                                " . PluginMetademandsFieldoption::setMandatoryBlockFields($metaid, $hidden_block);

                            if (is_array($childs_by_checkvalue)) {
                                foreach ($childs_by_checkvalue as $k => $childs_blocks) {
                                    if ($idc == $k) {
                                        foreach ($childs_blocks as $childs) {
                                            $options = getAllDataFromTable(
                                                'glpi_plugin_metademands_fieldoptions',
                                                ['hidden_block' => $childs]
                                            );
                                            if (count($options) == 0) {
                                                $post_onchange .= "$('[bloc-id =\"bloc" . $childs . "\"]').show();
                                        if (document.getElementById('ablock" . $childs . "'))
                                        document.getElementById('ablock" . $childs . "').style.display = 'block';
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

                    if ($data["display_type"] == self::CLASSIC_DISPLAY) {
                        $onchange .= "if ($(this).val() == $idc || $idc == -1 ) {";
                    } else {
                        $onchange .= " if (this.checked) {";
                    }
                    //specific for radio / dropdowns - one value
                    //            $script .= PluginMetademandsFieldoption::hideAllblockbyDefault($data);

                    //Prepare subblocks
                    $onchange .= "$('[bloc-id =\"bloc'+$hidden_block+'\"]').show();
                $('[bloc-id =\"subbloc" . $hidden_block . "\"]').show();
                if (document.getElementById('ablock" . $hidden_block . "'))
                document.getElementById('ablock" . $hidden_block . "').style.display = 'block';";
                    $onchange .= PluginMetademandsFieldoption::setMandatoryBlockFields($metaid, $hidden_block);

                    if (is_array($childs_by_checkvalue)) {
                        foreach ($childs_by_checkvalue as $k => $childs_blocks) {
                            if ($idc == $k) {
                                foreach ($childs_blocks as $childs) {
                                    $options = getAllDataFromTable(
                                        'glpi_plugin_metademands_fieldoptions',
                                        ['hidden_block' => $childs]
                                    );
                                    if (count($options) == 0) {
                                        $onchange .= "$('[bloc-id =\"bloc" . $childs . "\"]').show();
                                if (document.getElementById('ablock" . $childs . "'))
                                document.getElementById('ablock" . $childs . "').style.display = 'block';
                                                     " . PluginMetademandsFieldoption::setMandatoryBlockFields(
                                            $metaid,
                                            $childs
                                        );
                                    }
                                }
                            }
                        }
                    }

                    if (isset($data['value']) && $idc == $data['value']) {
                        $display = $hidden_block;
                    }

                    $onchange .= " } else {

                sessionStorage.setItem('hiddenbloc$name', $hidden_block);";

                    //specific - one value
                    $onchange .= PluginMetademandsFieldoption::setEmptyBlockFields($name);
                    $onchange .= "$('[bloc-id =\"bloc" . $hidden_block . "\"]').hide();
                $('[bloc-id =\"subbloc" . $hidden_block . "\"]').hide();
                if (document.getElementById('ablock" . $hidden_block . "'))
                document.getElementById('ablock" . $hidden_block . "').style.display = 'none';";

                    if (is_array($childs_by_checkvalue)) {
                        foreach ($childs_by_checkvalue as $k => $childs_blocks) {
                            if ($idc == $k) {
                                foreach ($childs_blocks as $childs) {
                                    $onchange .= "$('[bloc-id =\"bloc" . $childs . "\"]').hide();
                                if (document.getElementById('ablock" . $childs . "'))
                                document.getElementById('ablock" . $childs . "').style.display = 'none';";
                                }
                            }
                        }
                    }
                    $onchange .= " }";
                }
            }
            //Prepare subblocks

            if ($display > 0) {
                $pre_onchange .= "if (document.getElementById('ablock" . $display . "'))
                        document.getElementById('ablock" . $display . "').style.display = 'block';
                        $('[bloc-id =\"bloc" . $display . "\"]').show();
                        $('[bloc-id =\"subbloc" . $display . "\"]').show();";
            }

            $onchange .= "});";

            echo Html::scriptBlock(
                '$(document).ready(function() {' . $pre_onchange . " " . $onchange . " " . $post_onchange . '});'
            );
        }
    }

    public static function checkConditions($data, $metaparams)
    {
        foreach ($metaparams as $key => $val) {
            if (isset($metaparams[$key])) {
                $$key = $metaparams[$key];
            }
        }

        $root_doc = PLUGIN_METADEMANDS_WEBDIR;
        $onchange = "window.metademandconditionsparams = {};
                        metademandconditionsparams.submittitle = '$submittitle';
                        metademandconditionsparams.nextsteptitle = '$nextsteptitle';
                        metademandconditionsparams.use_condition = '$use_condition';
                        metademandconditionsparams.show_rule = '$show_rule';
                        metademandconditionsparams.show_button = '$show_button';
                        metademandconditionsparams.use_richtext = '$use_richtext';
                        metademandconditionsparams.richtext_ids = {$richtext_id};
                        metademandconditionsparams.root_doc = '$root_doc';";
        $name = "field[" . $data["id"] . "]";
        $onchange .= "$('[name=\"$name\"]').change(function() {";
        $onchange .= "plugin_metademands_wizard_checkConditions(metademandconditionsparams);";
        $onchange .= "});";

        echo Html::scriptBlock(
            '$(document).ready(function() {' . $onchange . '});'
        );
    }

    public static function getFieldValue($field)
    {
        if ($field['value'] == 2) {
            $val = __('Yes');
        } else {
            $val = __('No');
        }
        return $val;
    }

    public static function displayFieldItems(
        &$result,
        $formatAsTable,
        $style_title,
        $label,
        $field,
        $return_value,
        $lang,
        $is_order = false
    ) {
        $colspan = $is_order ? 6 : 1;
        $result[$field['rank']]['display'] = true;
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

        return $result;
    }
}
