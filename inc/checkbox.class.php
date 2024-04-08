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
 * PluginMetademandsCheckbox Class
 *
 **/
class PluginMetademandsCheckbox extends CommonDBTM
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
        return __('Checkbox', 'metademands');
    }

    static function showWizardField($data, $namefield, $value, $on_order)
    {

        if (empty($comment = PluginMetademandsField::displayField($data['id'], 'comment'))) {
            $comment = $data['comment'];
        }

        if (!empty($data['custom_values'])) {
            $custom_values = $data['custom_values'];

//            foreach ($custom_values as $k => $val) {
//                if (!empty($ret = PluginMetademandsField::displayField($data["id"], "custom" . $k))) {
//                    $data['custom_values'][$k] = $ret;
//                }
//            }
//            $data['comment_values'] = PluginMetademandsFieldCustomvalue::_unserialize($data['comment_values']);
//            $defaults = PluginMetademandsFieldCustomvalue::_unserialize($data['default_values']);
//            if (!empty($value)) {
//                $value = PluginMetademandsFieldCustomvalue::_unserialize($value);
//            }
            $nbr = 0;
            $inline = "";
            if ($data['row_display'] == 1) {
                $inline = 'custom-control-inline';
            }
            $field = "";

            if (count($custom_values) > 0) {
                foreach ($custom_values as $key => $label) {
                    $field .= "<div class='custom-control custom-checkbox $inline'>";
                    $checked = "";
                    if (isset($value[$key])) {
                        $checked = (isset($label['is_default']) && $label['is_default'] == 1) ? 'checked' : '';
                    } elseif (isset($label['is_default']) && $on_order == false) {
                        $checked = ($label['is_default'] == 1) ? 'checked' : '';
                    }
                    $required = "";
                    if ($data['is_mandatory'] == 1) {
                        $required = "required=required";
                    }
                    $field .= "<input $required class='form-check-input' type='checkbox' check='" . $namefield . "[" . $data['id'] . "]' name='" . $namefield . "[" . $data['id'] . "][" . $key . "]' key='$key' id='" . $namefield . "[" . $data['id'] . "][" . $key . "]' value='$key' $checked>";
                    $nbr++;
                    $field .= "&nbsp;<label class='custom-control-label' for='" . $namefield . "[" . $data['id'] . "][" . $key . "]'>" . $label['name'] . "</label>";
                    if (isset($label['comment']) && !empty($label['comment'])) {
                        $field .= "&nbsp;<span style='vertical-align: bottom;'>";
                        $field .= Html::showToolTip(
                            Glpi\RichText\RichText::getSafeHtml($label['comment']),
                            [
                                'awesome-class' => 'fa-info-circle',
                                'display' => false
                            ]
                        );
                        $field .= "</span>";
                    }
                    $field .= "</div>";

                    $childs_blocks = [];
                    $fieldopt = new PluginMetademandsFieldOption();
                    if ($opts = $fieldopt->find(["plugin_metademands_fields_id" => $data['id'], "check_value" => $key])) {
                        foreach ($opts as $opt) {
                            if (!empty($opt['childs_blocks'])) {
                                $childs_blocks[] = json_decode($opt['childs_blocks'], true);
                            }
                        }
                    }

                    if (isset($childs_blocks[$key])) {
                        $id = $data['id'];
                        $script = "<script type='text/javascript'>";
                        $script .= "$('[id^=\"field[" . $id . "][" . $key . "]\"]').click(function() {";
                        $script .= "if ($('[id^=\"field[" . $id . "][" . $key . "]\"]').not(':checked')) { ";

                        foreach ($childs_blocks[$key] as $customvalue => $childs) {
                            $script .= PluginMetademandsFieldoption::resetMandatoryBlockFields($childs);
                            $script .= "$('div[bloc-id=\"bloc$childs\"]').hide();";
                        }
                        $script .= "}";
                        $script .= "})";
                        $script .= "</script>";

                        $field .= $script;
                    }
                }
            }
        } else {
            $checked = $value ? 'checked' : '';
            $field = "<input class='form-check-input' type='checkbox' name='" . $namefield . "[" . $data['id'] . "]' value='checkbox' $checked>";
        }

        echo $field;
    }

    static function showFieldCustomValues($params)
    {

        $custom_values = $params['custom_values'];

        echo "<tr class='tab_bg_1'>";
        echo "<td>";
        $maxrank = 0;

        if (is_array($custom_values) && !empty($custom_values)) {
            echo "<div id='drag'>";
            echo "<table class='tab_cadre_fixe'>";

            foreach ($custom_values as $key => $value) {

                $target = PluginMetademandsFieldCustomvalue::getFormURL();
                echo "<form method='post' action=\"$target\">";
                echo "<tr class='tab_bg_1'>";

                echo "<td class='rowhandler control center'>";
                echo __('Rank', 'metademands') . " " . $value['rank'] . " ";
                if (isset($params['plugin_metademands_fields_id'])) {
                    echo Html::hidden('fields_id', ['value' => $params["plugin_metademands_fields_id"], 'id' => 'fields_id']);
                }
                echo "</td>";

                echo "<td class='rowhandler control center'>";
                echo "<span id='custom_values$key'>";
                echo Html::input('name['.$key.']', ['value' => $value['name'], 'size' => 30]);
                echo '</span>';
                echo "</td>";

                echo "<td class='rowhandler control center'>";
                echo "<span id='comment_values$key'>";
                echo __('Comment') . " ";
                echo Html::input('comment['.$key.']', ['value' => $value['comment'], 'size' => 30]);
                echo '</span>';
                echo "</td>";

                echo "<td class='rowhandler control center'>";
                echo "<span id='default_values$key'>";
                echo _n('Default value', 'Default values', 1, 'metademands') . " ";
                Dropdown::showYesNo('is_default['.$key.']', $value['is_default']);
                echo '</span>';
                echo "</td>";

                echo "<td class='rowhandler control center'>";
                echo "<div class=\"drag row\" style=\"cursor: move;border-width: 0 !important;border-style: none !important; border-color: initial !important;border-image: initial !important;\">";
                echo "<i class=\"fas fa-grip-horizontal grip-rule\"></i>";
                echo "</div>";
                echo "</td>";

                echo "<td class='rowhandler control center'>";
                echo Html::hidden('id['.$key.']', ['value' => $key]);

                echo Html::submit("", ['name'  => 'update',
                    'class' => 'btn btn-primary',
                    'icon'  => 'fas fa-save']);
                echo "</td>";

                echo "<td class='rowhandler control center'>";
                Html::showSimpleForm(
                    $target,
                    'delete',
                    _x('button', 'Delete permanently'),
                    ['customvalues_id' => $key,
                        'rank' => $value['rank'],
                        'plugin_metademands_fields_id' => $params["plugin_metademands_fields_id"],
                    ],
                    'fa-times-circle',
                    "class='btn btn-primary'"
                );
                echo "</td>";

                echo "</tr>";

                $maxrank = $value['rank'];

                Html::closeForm();
            }

            echo "</table>";
            echo "</div>";
            echo Html::scriptBlock('$(document).ready(function() {plugin_metademands_redipsInit()});');

            echo "<tr class='tab_bg_1'>";
            echo "<td colspan='4' align='left' id='show_custom_fields'>";
            PluginMetademandsFieldCustomvalue::initCustomValue($maxrank, true, true);
            echo "</td>";
            echo "</tr>";

        } else {

            $target = PluginMetademandsFieldCustomvalue::getFormURL();
            echo "<form method='post' action=\"$target\">";
            echo "<tr class='tab_bg_1'>";
            echo "<td align='right'  id='show_custom_fields'>";
            if (isset($params['plugin_metademands_fields_id'])) {
                echo Html::hidden('fields_id', ['value' => $params["plugin_metademands_fields_id"]]);
            }
            PluginMetademandsFieldCustomvalue::initCustomValue(-1, true, true);
            echo "</td>";
            echo "</tr>";
            Html::closeForm();
        }
        echo "</td>";
        echo "</tr>";

    }

    static function getParamsValueToCheck($fieldoption, $item, $params)
    {
        echo "<tr class='tab_bg_1'>";
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
        $elements[-1] = __('Not null value', 'metademands');
        foreach ($params['custom_values'] as $key => $val) {
            $elements[$val['id']] = $val['name'];
        }
        Dropdown::showFromArray(
            "check_value",
            $elements,
            ['value' => $params['check_value'], 'used' => $already_used]
        );
    }

    static function showParamsValueToCheck($params)
    {
        $elements[-1] = __('Not null value', 'metademands');
        foreach ($params['custom_values'] as $key => $val) {
            $elements[$val['id']] = $val['name'];
        }
        echo $elements[$params['check_value']] ?? 0;

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
            && $fields['value'] == null) {
            $msg = $value['name'];
            $checkKo = 1;
        }

        return ['checkKo' => $checkKo, 'msg' => $msg];
    }

    static function isCheckValueOK($value, $check_value)
    {
        if (!empty($value)) {
            $ok = false;
            if ($check_value == -1) {
                $ok = true;
            }
            if (is_array($value)) {
                foreach ($value as $key => $v) {
                    //                     if ($key != 0) {
                    if ($check_value == $key) {
                        $ok = true;
                    }
                    //                     }
                }
            } else if (is_array(json_decode($value, true))) {
                foreach (json_decode($value, true) as $key => $v) {
                    //                     if ($key != 0) {
                    if ($check_value == $key) {
                        $ok = true;
                    }
                    //                     }
                }
            }
            if (!$ok) {
                return false;
            }
        } else {
            return false;
        }
    }

    static function fieldsLinkScript($data, $idc, $rand)
    {

        $fields_link = $data['options'][$idc]['fields_link'];

        $script = "";
        $script .= "var metademandWizard$rand = $(document).metademandWizard();";
        $script .= "metademandWizard$rand.metademand_setMandatoryField(
                                        'metademands_wizard_red" . $fields_link . "',
                                        'field[" . $data['id'] . "][" . $idc . "]',[";
        $script .= $idc;
        $script .= "], '" . $data['item'] . "');";

        return $script;
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
            $script = "console.log('taskScript-checkbox $id');";
        }

        //if reload form on loading
        if (isset($_SESSION['plugin_metademands'][$metaid]['fields'][$id])) {
            $session_value = $_SESSION['plugin_metademands'][$metaid]['fields'][$id];
            if (is_array($session_value)) {
                foreach ($session_value as $k => $fieldSession) {
                    $script2 .= "$('[name=\"field[" . $id . "][" . $fieldSession . "]\"]').prop('checked', true);";
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

        $script .= "$('[name^=\"field[" . $data["id"] . "]\"]').change(function() {";
        $script .= "var tohide = {};";
        foreach ($check_values as $idc => $check_value) {
            $tasks_id = $data['options'][$idc]['plugin_metademands_tasks_id'];

            $script .= " if (this.checked){";
            //                                        foreach ($hidden_link as $key => $fields) {
            $script .= " if ($(this).val() == $idc || $idc == -1) {
                            if ($tasks_id in tohide) {
                            } else {
                                tohide[$tasks_id] = true;
                            }
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
            $script .= "} else {
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
            }
            ";
        }
        $script .= "});";

        foreach ($check_values as $idc => $check_value) {
            $tasks_id = $check_value['plugin_metademands_tasks_id'];
            if (is_array($data['custom_values']) && count($data['custom_values']) > 0) {
                $custom_values = $data['custom_values'];
                foreach ($custom_values as $k => $custom_value) {
                    if ($custom_value['is_default'] == 1) {
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

        $check_values = $data['options'] ?? [];
        $metaid = $data['plugin_metademands_metademands_id'];
        $id = $data["id"];

        $onchange = "";
        $pre_onchange = "";
        $post_onchange = "";
        $debug = (isset($_SESSION['glpi_use_mode'])
        && $_SESSION['glpi_use_mode'] == Session::DEBUG_MODE ? true : false);
        if ($debug) {
            $pre_onchange = "console.log('fieldsHiddenScript-checkbox $id');";
        }

        //Initialize default value - force change after onchange fonction
        if (is_array($data['custom_values']) && count($data['custom_values']) > 0) {
            $custom_values = $data['custom_values'];
            foreach ($custom_values as $k => $custom_value) {
                if ($custom_value['is_default'] == 1) {
                    $post_onchange .= "$('[id=\"field[$id][$k]\"]').prop('checked', true).trigger('change');";
                }
            }
        }

        //default hide of all hidden links
        foreach ($check_values as $idc => $check_value) {
            $hidden_link = $check_value['hidden_link'];
            $pre_onchange .= "$('[id-field =\"field" . $hidden_link . "\"]').hide();";
        }

        //if reload form on loading
        if (isset($_SESSION['plugin_metademands'][$metaid]['fields'][$id])) {
            $session_value = $_SESSION['plugin_metademands'][$metaid]['fields'][$id];
            if (is_array($session_value)) {
                foreach ($session_value as $k => $fieldSession) {
                    $pre_onchange .= "$('[id=\"field[" . $id . "][" . $fieldSession . "]\"]').prop('checked', true).trigger('change');";
                }
            } else {
                $pre_onchange .= "$('[id=\"field[" . $id . "][" . $session_value . "]\"]').prop('checked', true).trigger('change');";
            }
        }

        $onchange .= "$('[name^=\"field[" . $data["id"] . "]\"]').change(function() {";

        $onchange .= "var tohide = {};";

        foreach ($check_values as $idc => $check_value) {
            $hidden_link = $check_value['hidden_link'];

            $onchange .= " if (this.checked){";
            //                                        foreach ($hidden_link as $key => $fields) {
            $onchange .= " if ($(this).val() == $idc || $idc == -1) {
                            if ($hidden_link in tohide) {
                            } else {
                                tohide[$hidden_link] = true;
                            }
                            tohide[$hidden_link] = false;
                        }";

            //if reload form
            if (isset($_SESSION['plugin_metademands'][$metaid]['fields'][$id])) {
                $session_value = $_SESSION['plugin_metademands'][$metaid]['fields'][$id];
                if (is_array($session_value)) {
                    foreach ($session_value as $k => $fieldSession) {
                        if ($fieldSession == $idc) {
                            $pre_onchange .= "$('[name=\"field[" . $id . "][" . $fieldSession . "]\"]').prop('checked', true);";
                        }

                        if ($fieldSession == $idc && $hidden_link > 0) {
                            $pre_onchange .= "$('[id-field =\"field" . $hidden_link . "\"]').show();";
                        }
                    }
                }
            }

            $onchange .= "$.each( tohide, function( key, value ) {                      
                            if (value == true) {
                            $('[id-field =\"field'+key+'\"]').hide();
                                " . PluginMetademandsFieldoption::resetMandatoryFieldsByField($hidden_link) . "
                                $('[name =\"field['+key+']\"]').removeAttr('required');
                            } else {
                                $('[id-field =\"field'+key+'\"]').show();
//                                " .PluginMetademandsFieldoption::setMandatoryFieldsByField($id, $hidden_link)."
                            }
                        });";

            $onchange .= "} else {";
            //not checked
            $onchange .= "if($(this).val() == $idc){
                            if ($hidden_link in tohide) {
                            } else {
                               tohide[$hidden_link] = true;
                            }
                            $.each( $('[name^=\"field[" . $data["id"] . "]\"]:checked'),function( index, value ){
                                if($(value).val() == $idc || $idc == -1 ){
                                    tohide[$hidden_link] = false;
                                }
                            });
                        }";

            $onchange .= "$.each( tohide, function( key, value ) {
                            if (value == true) {
                               $('[id-field =\"field'+key+'\"]').hide();
                               " . PluginMetademandsFieldoption::resetMandatoryFieldsByField($hidden_link) . "
                               $('[name =\"field['+key+']\"]').removeAttr('required');
                            } else {
                               $('[id-field =\"field'+key+'\"]').show();
//                               " .PluginMetademandsFieldoption::setMandatoryFieldsByField($id, $hidden_link)."
                            }
                         });";
            $onchange .= "}";
        }
        $onchange .= "});";
//        if (!isset($_SESSION['plugin_metademands'][$metaid]['fields'][$id])) {
//            foreach ($check_values as $idc => $check_value) {
//
//                $pre_onchange .= "$('[id-field =\"field" . $hidden_link . "\"]').hide();";
//                if (isset($_SESSION['plugin_metademands'][$metaid]['fields'][$id])) {
//                    $session_value = $_SESSION['plugin_metademands'][$metaid]['fields'][$id];
//                    if (is_array($session_value)) {
//                        foreach ($session_value as $k => $fieldSession) {
//                            if ($fieldSession == $idc && $hidden_link > 0) {
//                                $pre_onchange .= "$('[id-field =\"field" . $hidden_link . "\"]').show();";
//                            }
//                        }
//                    }
//                }
//
//                $hidden_link = $check_value['hidden_link'];
//                //Initialize id default value
//                if (is_array($data['custom_values']) && count($data['custom_values']) > 0) {
//                    $custom_values = $data['custom_values'];
//                    foreach ($custom_values as $k => $custom_value) {
//                        if ($k == $idc && $custom_value['is_default'] == 1) {
//                            $onchange .= " $('[id-field =\"field" . $hidden_link . "\"]').show();";
//                        }
//                    }
//                }
//            }
//        }
        echo Html::scriptBlock('$(document).ready(function() {' . $pre_onchange . " " . $onchange. " " . $post_onchange . '});');

    }

    public static function blocksHiddenScript($data)
    {
        $metaid = $data['plugin_metademands_metademands_id'];
        $check_values = $data['options'] ?? [];
        $id = $data["id"];

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
            $script = "console.log('blocksHiddenScript-checkbox $id');";
        }
        $script .= "$('[name^=\"field[" . $data["id"] . "]\"]').change(function() {";

        $script .= "var todisplay = {};tohide = {};";

        //by default - hide all
//        if (!isset($_SESSION['plugin_metademands'][$metaid]['fields'][$id])) {
        $script2 .= PluginMetademandsFieldoption::hideAllblockbyDefault($data);
        if (!isset($_SESSION['plugin_metademands'][$metaid]['fields'][$id])) {
         $script2 .= PluginMetademandsFieldoption::emptyAllblockbyDefault($check_values);
        }
        //checkbox : one value at each time
        foreach ($check_values as $idc => $check_value) {

            $hidden_block = $check_value['hidden_block'];

            //Default values
            if (is_array($data['custom_values']) && count($data['custom_values']) > 0) {
                $custom_values = $data['custom_values'];
                foreach ($custom_values as $k => $custom_value) {
                    if ($custom_value['is_default'] == 1) {
                        if ($idc == $k) {
                            $script2 .= "$('[bloc-id =\"bloc" . $hidden_block . "\"]').show();
                                " . PluginMetademandsFieldoption::setMandatoryBlockFields($metaid, $hidden_block);

                            if (is_array($childs_by_checkvalue)) {
                                foreach ($childs_by_checkvalue as $k => $childs_blocks) {
                                    if ($idc == $k) {
                                        foreach ($childs_blocks as $childs) {
                                            $script2 .= "$('[bloc-id =\"bloc" . $childs . "\"]').show();
                                                 " . PluginMetademandsFieldoption::setMandatoryBlockFields($metaid, $childs);
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
            //end Default values

            $script .= " if (this.checked) {";

                $script .= "if ($(this).val() == $idc || $idc == -1 ) {";
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

//                if (isset($_SESSION['plugin_metademands'][$data["plugin_metademands_metademands_id"]]['fields'][$data["id"]])
//                    && is_array($_SESSION['plugin_metademands'][$data["plugin_metademands_metademands_id"]]['fields'][$data["id"]])) {
//                    foreach ($_SESSION['plugin_metademands'][$data["plugin_metademands_metademands_id"]]['fields'][$data["id"]] as $fieldSession) {
//                        if ($fieldSession == $idc || $idc == -1) {
//                            $script2 .= "$('[bloc-id =\"bloc" . $hidden_block . "\"]').show();";
//                        }
//                    }
//                }

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

                $script .= " }";

            $script .= " } else { ";

            //if reload form


                $script .= "if($(this).val() == $idc){
                            $('[bloc-id =\"bloc'+$hidden_block+'\"]').hide();";
                $script .= PluginMetademandsFieldoption::resetMandatoryBlockFields($hidden_block)
                    . PluginMetademandsFieldoption::setEmptyBlockFields($hidden_block);

                if (is_array($childs_by_checkvalue)) {
                    foreach ($childs_by_checkvalue as $k => $childs_blocks) {
                        if ($idc == $k) {
                            foreach ($childs_blocks as $childs) {
                                $script .= "$('[bloc-id =\"bloc" . $childs . "\"]').hide();
                                                         " . PluginMetademandsFieldoption::setEmptyBlockFields($childs)
                                                            . PluginMetademandsFieldoption::resetMandatoryBlockFields($childs);
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
                        if (is_array($childs_by_checkvalue)) {
                            foreach ($childs_by_checkvalue as $k => $childs_blocks) {
                                if ($idc == $k) {
                                    foreach ($childs_blocks as $childs) {
                                        $script2 .= "$('[bloc-id =\"bloc" . $childs . "\"]').show();
                                                     " . PluginMetademandsFieldoption::setMandatoryBlockFields($metaid, $childs);
                                    }
                                }
                            }
                        }
                    }
                }
            }

//                if (isset($_SESSION['plugin_metademands'][$data["plugin_metademands_metademands_id"]]['fields'][$data["id"]])
//                    && is_array($_SESSION['plugin_metademands'][$data["plugin_metademands_metademands_id"]]['fields'][$data["id"]])) {
//                    foreach ($_SESSION['plugin_metademands'][$data["plugin_metademands_metademands_id"]]['fields'][$data["id"]] as $fieldSession) {
//                        if ($fieldSession == $idc || $idc == -1) {
//                            $script2 .= "$('[bloc-id =\"bloc" . $hidden_block . "\"]').hide();";
//                        }
//                    }
//                }

//                if (isset($_SESSION['plugin_metademands'][$metaid]['fields'][$id])) {
//                    $session_value = $_SESSION['plugin_metademands'][$metaid]['fields'][$id];
//                    if (is_array($session_value)) {
//                        foreach ($session_value as $k => $fieldSession) {
//                            if ($fieldSession == $idc && $hidden_block > 0) {
//                                $script2 .= "$('[bloc-id =\"bloc" . $hidden_block . "\"]').hide();";
//                            }
//                        }
//                    }
//                }

                $script .= "}";

            $script .= " }";
        }


        $script .= "fixButtonIndicator();
        });";

        echo Html::scriptBlock('$(document).ready(function() {' . $script2 . " " . $script . '});');
    }

    public static function getFieldValue($field, $lang)
    {
        if (isset($field['custom_values']) && !empty($field['custom_values'])) {
            $custom_values = [];
            foreach ($field['custom_values'] as $key => $val) {
                $custom_values[$val['id']] = $val['name'];
            }
            foreach ($custom_values as $k => $val) {
                if (!empty($ret = PluginMetademandsField::displayField($field["id"], "custom" . $k, $lang))) {
                    $custom_values[$k] = $ret;
                }
            }
            if (!empty($field['value'])) {
                if (is_string($field['value'])) {
                    $field['value'] = PluginMetademandsFieldCustomvalue::_unserialize($field['value']);
                } else {
                    $field['value'] = json_decode(json_encode($field['value']), true);
                }
            }
            $custom_checkbox = [];

            foreach ($custom_values as $key => $val) {
                $checked = isset($field['value'][$key]) ? 1 : 0;
                if ($checked) {
                    $custom_checkbox[] .= $val;
                }
            }
            return implode(',', $custom_checkbox);
        } else {
            if ($field['value']) {
                return $field['value'];
            }
        }
    }

    public static function displayFieldItems(&$result, $formatAsTable, $style_title, $label, $field, $return_value, $lang)
    {
        if (is_string($field['value'])) {
            $field['value'] = PluginMetademandsFieldCustomvalue::_unserialize($field['value']);
        } else {
            $field['value'] = json_decode(json_encode($field['value']), true);
        }

        if (!empty($field['custom_values']) && $field['value'] > 0) {
            $result[$field['rank']]['display'] = true;
            if ($formatAsTable) {
                $result[$field['rank']]['content'] .= "<td $style_title>";
            }
            $result[$field['rank']]['content'] .= $label;
            if ($formatAsTable) {
                $result[$field['rank']]['content'] .= "</td>";
            }
            if ($formatAsTable) {
                $result[$field['rank']]['content'] .= "<td>";
            }
            $result[$field['rank']]['content'] .= self::getFieldValue($field, $lang);
            if ($formatAsTable) {
                $result[$field['rank']]['content'] .= "</td>";
            }
        } else {
            if ($field['value']) {
                $result[$field['rank']]['display'] = true;
                if ($formatAsTable) {
                    $result[$field['rank']]['content'] .= "<td>";
                }
                $result[$field['rank']]['content'] .= self::getFieldValue($field, $lang);
                if ($formatAsTable) {
                    $result[$field['rank']]['content'] .= "</td>";
                }
            }
        }

        return $result;
    }

}
