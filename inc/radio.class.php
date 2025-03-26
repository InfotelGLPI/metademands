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
 * PluginMetademandsRadio Class
 *
 **/
class PluginMetademandsRadio extends CommonDBTM
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
        return __('Radio button', 'metademands');
    }

    static function showWizardField($data, $namefield, $value,  $on_order) {

        if (empty($comment = PluginMetademandsField::displayField($data['id'], 'comment'))) {
            $comment = $data['comment'];
        }

        $field = "";
        if (!empty($data['custom_values'])) {
            $custom_values = $data['custom_values'];
//            $data['custom_values'] = PluginMetademandsFieldParameter::_unserialize($data['custom_values']);
//            foreach ($data['custom_values'] as $k => $val) {
//                if (!empty($ret = PluginMetademandsField::displayField($data["id"], "custom" . $k))) {
//                    $data['custom_values'][$k] = $ret;
//                }
//            }
//            $data['comment_values'] = PluginMetademandsFieldParameter::_unserialize($data['comment_values']);
//            $defaults               = PluginMetademandsFieldParameter::_unserialize($data['default_values']);
//            if ($value != null) {
//                $value = PluginMetademandsFieldParameter::_unserialize($value);
//            }
            $nbr = 0;
            $inline = "";
            if ($data['row_display'] == 1) {
                $inline = 'custom-control-inline';
            }

            if (count($custom_values) > 0) {
                foreach ($custom_values as $key => $label) {
                    $field .= "<div class='custom-control custom-radio $inline'>";
                    $checked = "";

                    if (empty($value) && isset($label['is_default']) && $on_order == false) {
                        $checked = ($label['is_default'] == 1) ? 'checked' : '';
                    }
                    if (isset($value) && $value == $key) {
                        $checked = 'checked';
                    }
                    $required = "";
                    if ($data['is_mandatory'] == 1) {
                        $required = "required=required";
                    }
                    $field .= "<input $required class='form-check-input' type='radio' name='" . $namefield . "[" . $data['id'] . "]' id='" . $namefield . "[" . $data['id'] . "][" . $key . "]' value='$key' $checked>";

                    if (empty($name = PluginMetademandsField::displayCustomvaluesField($data['id'], $key))) {
                        $name = $label['name'];
                    }
                    $field .= "&nbsp;<label class='custom-control-label' for='" . $namefield . "[" . $data['id'] . "][" . $key . "]'>" . $name . "</label>";
                    if (isset($label['comment']) && !empty($label['comment'])) {
                        $field .= "&nbsp;<span style='vertical-align: bottom;'>";
                        if (empty($comment = PluginMetademandsField::displayCustomvaluesField($data['id'], $key, "comment"))) {
                            $comment = $label['comment'];
                        }
                        $field .= Html::showToolTip(
                            Glpi\RichText\RichText::getSafeHtml($comment),
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
                        $script .= "if ($('[id^=\"field[" . $id . "][" . $key . "]\"]').is(':checked')) { ";

                        foreach ($childs_blocks as $customvalue => $childs) {
                            if ($customvalue != $key) {
                                foreach ($childs as $k => $v) {
                                    $script .= "sessionStorage.setItem('hiddenbloc$childs', $childs);";
                                    $script .= PluginMetademandsFieldoption::resetMandatoryBlockFields($namefield);
                                    $script .= "$('div[bloc-id=\"bloc$v\"]').hide();";
                                }
                            }
                        }
                        $script .= "}";
                        $script .= "})";
                        $script .= "</script>";

                        $field .= $script;
                    }
                }
            }
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
                    echo Html::hidden('type', ['value' => $params["type"], 'id' => 'type']);
                }
                echo "</td>";

                echo "<td class='rowhandler control center'>";
                echo "<span id='custom_values$key'>";
                echo Html::input('name['.$key.']', ['value' => $value['name'], 'size' => 30]);
                echo "</span>";
                echo "</td>";

                echo "<td class='rowhandler control center'>";
                echo "<span id='comment_values$key'>";
                echo __('Comment') . " ";
                echo Html::input('comment['.$key.']', ['value' => $value['comment'], 'size' => 30]);
                echo "</span>";
                echo "</td>";

                echo "<td class='rowhandler control center'>";
                echo "<span id='default_values$key'>";
                echo _n('Default value', 'Default values', 1, 'metademands') . " ";
                Dropdown::showYesNo('is_default['.$key.']', $value['is_default']);
                echo "</span>";
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
            PluginMetademandsFieldCustomvalue::initCustomValue($maxrank, true, true, $params["plugin_metademands_fields_id"]);
            echo "</td>";
            echo "</tr>";
            PluginMetademandsFieldCustomvalue::importCustomValue($params);

        } else {
            $target = PluginMetademandsFieldCustomvalue::getFormURL();
            echo "<form method='post' action=\"$target\">";
            echo "<tr class='tab_bg_1'>";
            echo "<td align='right'  id='show_custom_fields'>";
            if (isset($params['plugin_metademands_fields_id'])) {
                echo Html::hidden('fields_id', ['value' => $params["plugin_metademands_fields_id"]]);
            }
            PluginMetademandsFieldCustomvalue::initCustomValue(-1, true, true, $params["plugin_metademands_fields_id"]);
            echo "</td>";
            echo "</tr>";
            Html::closeForm();
            PluginMetademandsFieldCustomvalue::importCustomValue($params);
        }
        echo "</td>";
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
        if (empty($value) && $value != 0) {
            return false;
        } else if ($check_value != $value) {
            return false;
        }
    }

    static function fieldsLinkScript($data, $idc, $rand) {


        $fields_link = $data['options'][$idc]['fields_link'];

        $script = "";
        $script .= "var metademandWizard$rand = $(document).metademandWizard();";
        $script .= "metademandWizard$rand.metademand_setMandatoryField(
                                        'metademands_wizard_red" . $fields_link . "',
                                        'field[" . $data['id'] . "][".$idc."]',[";
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
            $script = "console.log('taskScript-radio $id');";
        }

        if (count($check_values) > 0) {

            //Si la valeur est en session
            //specific
            if (isset($data['value'])) {
                $script2 .= "$('[id=\"field[" . $id . "][".$data['value']."]\"]').prop('checked', true);";
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
                $script .= "if ($tasks_id in tohide) {
                        } else {
                            tohide[$tasks_id] = true;
                        }
                        if (parseInt($(this).val()) == $idc || $idc == -1) {
                            tohide[$tasks_id] = false;
                        }";


                $script .= "$.each( tohide, function( key, value ) {           
                        if (value == true) {
                           $.ajax({
                                url: '" . PLUGIN_METADEMANDS_WEBDIR . "/ajax/set_session.php',
                                    type: 'POST',
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
                                type: 'POST',
                                dataType: 'text',
                                data: { tasks_id: $tasks_id,
                                  used: 1 },
                                success: function(response){
                                   if (response != 1) {
                                       document.getElementById('nextBtn').innerHTML = '$nextsteptitle'
                                   }
                                },
                             });
                        }
                    });";
            }
            $script .= "});";

            foreach ($check_values as $idc => $check_value) {
                $tasks_id = $check_value['plugin_metademands_tasks_id'];

                if (isset($data['custom_values'])
                    && is_array($data['custom_values'])
                    && count($data['custom_values']) > 0) {
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
    }

    static function fieldsHiddenScript($data) {


        $check_values = $data['options'] ?? [];
        $id = $data["id"];

        $onchange = "";
        $pre_onchange = "";
        $post_onchange = "";
        $debug = (isset($_SESSION['glpi_use_mode'])
        && $_SESSION['glpi_use_mode'] == Session::DEBUG_MODE ? true : false);
        if ($debug) {
            $onchange = "console.log('fieldsHiddenScript-radio $id');";
        }

        if (count($check_values) > 0) {
            //Initialize default value - force change after onchange fonction
            if (isset($data['custom_values'])
                && is_array($data['custom_values'])
                && count($data['custom_values']) > 0) {
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

            //Si la valeur est en session
            //specific
            if (isset($data['value'])) {
                $pre_onchange .= "$('[id=\"field[" . $id . "][" . $data['value'] . "]\"]').prop('checked', true).trigger('change');";
            }

            $onchange .= "$('[name^=\"field[" . $data["id"] . "]\"]').change(function() {";

            $onchange .= "var tohide = {};";
            $display = 0;
            foreach ($check_values as $idc => $check_value) {
                $hidden_link = $check_value['hidden_link'];

                $onchange .= "if ($hidden_link in tohide) {
                        } else {
                            tohide[$hidden_link] = true;
                        }
                        if (parseInt($(this).val()) == $idc || $idc == -1) {
                            tohide[$hidden_link] = false;
                        }";

                if (isset($data['value']) && $idc == $data['value']) {
                    $display = $hidden_link;
                }

                $onchange .= "$.each( tohide, function( key, value ) {
                        if (value == true) {
                            $('[id-field =\"field'+key+'\"]').hide();
                            " . PluginMetademandsFieldoption::resetMandatoryFieldsByField($hidden_link) . "
                            $('[name =\"field['+key+']\"]').removeAttr('required');
                        } else {
                            $('[id-field =\"field'+key+'\"]').show();
//                            " . PluginMetademandsFieldoption::setMandatoryFieldsByField($id, $hidden_link) . "
                        }
                    });";
            }

            if ($display > 0) {
                $pre_onchange .= "$('[id-field =\"field" . $display . "\"]').show();";
                $pre_onchange .= PluginMetademandsFieldoption::setMandatoryFieldsByField($id, $display);
            }

            $onchange .= "});";

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

        //hidden_blocks by idc
        $hiddenblocks_by_checkvalue = [];
        foreach ($check_values as $idc => $check_value) {
            if (isset($check_value['hidden_block'])) {
                $hiddenblocks_by_checkvalue[$idc] = $check_value['hidden_block'];
            }
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

        $onchange = "";
        $pre_onchange = "";
        $post_onchange = "";
        $debug = (isset($_SESSION['glpi_use_mode'])
        && $_SESSION['glpi_use_mode'] == Session::DEBUG_MODE ? true : false);
        if ($debug) {
            $onchange = "console.log('blocksHiddenScript-radio $id');";
        }

        if (count($check_values) > 0) {

            //Initialize default value - force change after onchange fonction
            foreach ($check_values as $idc => $check_value) {
                //Default values
                if (isset($data['custom_values'])
                    && is_array($data['custom_values'])
                    && count($data['custom_values']) > 0) {
                    $custom_values = $data['custom_values'];
                    foreach ($custom_values as $k => $custom_value) {
                        if ($k == $idc && $custom_value['is_default'] == 1) {
                            $post_onchange .= "$('[name=\"$name\"]').prop('checked', true).trigger('change');";
                        }
                        if (is_array($childs_by_checkvalue)) {
                            foreach ($childs_by_checkvalue as $k => $childs_blocks) {
                                if ($idc == $k) {
                                    foreach ($childs_blocks as $childs) {
                                        $post_onchange .= "$('[bloc-id =\"bloc" . $childs . "\"]').show();
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


            //by default - hide all
            $pre_onchange .= PluginMetademandsFieldoption::hideAllblockbyDefault($data);
            if (!isset($data['value'])) {
                $pre_onchange .= PluginMetademandsFieldoption::emptyAllblockbyDefault($check_values);
            }


            //Si la valeur est en session
            //specific
            if (isset($data['value'])) {
                if (isset($check_values[$data['value']]['hidden_block'])) {
                    $pre_onchange .= "$('[bloc-id =\"bloc" . $check_values[$data['value']]['hidden_block'] . "\"]').show();";
                }
            }


            $onchange .= "$('[name^=\"field[" . $data["id"] . "]\"]').change(function() {";

            $onchange .= "var tohide = {};";

            $display = 0;
            foreach ($check_values as $idc => $check_value) {
                $blocks_idc = [];
                $hidden_block = $check_value['hidden_block'];

                $onchange .= "if ($(this).val() == $idc || $idc == -1 ) {";

                foreach ($check_values as $idc => $check_value) {
                    $hidden_block = $check_value['hidden_block'];

                    $onchange .= "if ($hidden_block in tohide) {
                      } else {
                        tohide[$hidden_block] = true;
                      }
                    if ($(this).val() != 0 && ($(this).val() == $idc || $idc == 0 )) {
                        tohide[$hidden_block] = false;
                    }";


                    $onchange .= "$.each( tohide, function( key, value ) {
                    if (value == true) {
                        $('[bloc-id =\"bloc'+ key +'\"]').hide();
                        sessionStorage.setItem('hiddenbloc$name', key);
                        " . PluginMetademandsFieldoption::setEmptyBlockFields($name) . "";
                    $hidden = PluginMetademandsFieldoption::resetMandatoryBlockFields($name);
                    $onchange .= "$hidden";
                    if (is_array($childs_by_checkvalue)) {
                        foreach ($childs_by_checkvalue as $k => $childs_blocks) {
                            if ($idc == $k) {
                                foreach ($childs_blocks as $childs) {
                                    $onchange .= "$('[bloc-id =\"bloc" . $childs . "\"]').hide();";
                                }
                            }
                        }
                    }
                    $onchange .= "} else {

                        $('[bloc-id =\"bloc'+ key +'\"]').show(); 
                        ";

                    $hidden = PluginMetademandsFieldoption::setMandatoryBlockFields($metaid, $hidden_block);

                    $onchange .= "$hidden";
                    $onchange .= "}
                });
          ";

                    if (isset($data['value']) && $idc == $data['value']) {
                        $display = $hidden_block;
                    }

                    if ($data["item"] == "ITILCategory_Metademands") {
                        if (isset($_GET['itilcategories_id']) && $idc == $_GET['itilcategories_id']) {
                            $pre_onchange .= "$('[bloc-id =\"bloc" . $hidden_block . "\"]').show();
                          " . PluginMetademandsFieldoption::setMandatoryBlockFields($metaid, $hidden_block);
                        }
                    }
                }

                if (isset($data['value']) && $idc == $data['value']) {
                    $display = $hidden_block;
                }


                $onchange .= " }";

            }

            if ($display > 0) {
                $pre_onchange .= "$('[bloc-id =\"bloc" . $display . "\"]').show();";
            }

            $onchange .= "fixButtonIndicator();
        });";

            echo Html::scriptBlock(
                '$(document).ready(function() {' . $pre_onchange . " " . $onchange . " " . $post_onchange . '});'
            );
        }
    }

    public static function getFieldValue($field, $label, $lang)
    {
        if (!empty($field['custom_values'])) {
            $custom_values = [];
            foreach ($field['custom_values'] as $key => $val) {
                $custom_values[$val['id']] = $val['name'];
            }
            foreach ($custom_values as $k => $val) {
                if (!empty($ret = PluginMetademandsField::displayField($field["id"], "custom" . $k, $lang))) {
                    $custom_values[$k] = $ret;
                }
            }
            //TODO MIGRATE
            if ($field['value'] != "") {
                $field['value'] = PluginMetademandsFieldParameter::_unserialize($field['value']);
            }

            $custom_radio = "";
            foreach ($custom_values as $key => $val) {
                if ($field['value'] == $key && $field['value'] !== "") {
                    $custom_radio = $val;
                }
            }
            return $custom_radio;
        } else {
            if ($field['value']) {
                return $label;
            }
        }
    }

    public static function displayFieldItems(&$result, $formatAsTable, $style_title, $label, $field, $return_value, $lang, $is_order = false)
    {
        $colspan = $is_order ? 6 : 1;
        $result[$field['rank']]['display'] = true;
        if (!empty($field['custom_values']) && $field['value'] > 0) {
            if ($formatAsTable) {
                $result[$field['rank']]['content'] .= "<td $style_title colspan='$colspan'>";
            }
            $result[$field['rank']]['content'] .= $label;
            if ($formatAsTable) {
                $result[$field['rank']]['content'] .= "</td>";
            }
            if ($formatAsTable) {
                $result[$field['rank']]['content'] .= "<td colspan='$colspan'>";
            }
            $result[$field['rank']]['content'] .= self::getFieldValue($field, $label, $lang);
            if ($formatAsTable) {
                $result[$field['rank']]['content'] .= "</td>";
            }
        } else {
            if ($field['value']) {
                if ($formatAsTable) {
                    $result[$field['rank']]['content'] .= "<td colspan='$colspan'>";
                }
                $result[$field['rank']]['content'] .= $label;
                if ($formatAsTable) {
                    $result[$field['rank']]['content'] .= "</td>";
                }
            }
        }

        return $result;
    }
}
