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

        if (!empty($data['custom_values'])) {
            $data['custom_values'] = PluginMetademandsField::_unserialize($data['custom_values']);
            foreach ($data['custom_values'] as $k => $val) {
                if (!empty($ret = PluginMetademandsField::displayField($data["id"], "custom" . $k))) {
                    $data['custom_values'][$k] = $ret;
                }
            }
            $data['comment_values'] = PluginMetademandsField::_unserialize($data['comment_values']);
            $defaults               = PluginMetademandsField::_unserialize($data['default_values']);
            if ($value != null) {
                $value = PluginMetademandsField::_unserialize($value);
            }
            $nbr    = 0;
            $inline = "";
            if ($data['row_display'] == 1) {
                $inline = 'custom-control-inline';
            }
            $field = "";

            foreach ($data['custom_values'] as $key => $label) {
                $field .= "<div class='custom-control custom-radio $inline'>";

                $checked = "";
                if ($value != null && $value == $key) {
                    $checked = 'checked';
                } elseif ($value == null && isset($defaults[$key]) && $on_order == false) {
                    $checked = ($defaults[$key] == 1) ? 'checked' : '';
                }
                $required = "";
                if ($data['is_mandatory'] == 1) {
                    $required = "required=required";
                }
                $field .= "<input $required class='form-check-input' type='radio' name='" . $namefield . "[" . $data['id'] . "]' id='" . $namefield . "[" . $data['id'] . "][" . $key . "]' value='$key' $checked>";

                $field .= "&nbsp;<label class='custom-control-label' for='" . $namefield . "[" . $data['id'] . "][" . $key . "]'>$label</label>";
                if (isset($data['comment_values'][$key]) && !empty($data['comment_values'][$key])) {
                    $field .= "&nbsp;<span style='vertical-align: bottom;'>";
                    $field .= Html::showToolTip(
                        Glpi\RichText\RichText::getSafeHtml($data['comment_values'][$key]),
                        ['awesome-class' => 'fa-info-circle',
                            'display'       => false]
                    );
                    $field .= "</span>";
                }

                $field .= "</div>";
                $childs_blocks = [];
                $fieldopt = new PluginMetademandsFieldOption();
                if($opts = $fieldopt->find(["plugin_metademands_fields_id" => $data['id'], "check_value" => $key])) {
                    foreach ($opts as $opt) {
                        if (!empty($opt['childs_blocks'])) {
                            $childs_blocks[] = json_decode($opt['childs_blocks'], true);
                        }
                    }
                }
                if (isset($childs_blocks[$key])) {
                    $id     = $data['id'];
                    $script = "<script type='text/javascript'>";
                    $script .= "$('[id^=\"field[" . $id . "][" . $key . "]\"]').click(function() {";
                    $script .= "if ($('[id^=\"field[" . $id . "][" . $key . "]\"]').is(':checked')) { ";

                    foreach ($childs_blocks as $customvalue => $childs) {
                        if ($customvalue != $key) {
                            foreach ($childs as $k => $v) {
                                $script .= PluginMetademandsFieldoption::resetMandatoryBlockFields($v);
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

        echo $field;
    }

    static function showFieldCustomValues($values, $key, $params) {

        $default_values = PluginMetademandsField::_unserialize($params['default_values']);
        $comment_values = PluginMetademandsField::_unserialize($params['comment_values']);

        echo "<tr>";
        echo "<td>";
        if (is_array($values) && !empty($values)) {
            echo "<div id='drag'>";
            echo "<table class='tab_cadre_fixe'>";
            foreach ($values as $key => $value) {
                echo "<tr>";

                echo '<td class="rowhandler control center">';
                echo "<p id='custom_values$key'>";
                echo __('Value') . " " . $key . " ";
                $name = "custom_values[$key]";
                echo Html::input($name, ['value' => $value, 'size' => 30]);
                echo '</p>';
                echo "</td>";

                echo '<td class="rowhandler control center">';
                echo "<p id='comment_values$key'>";
                echo " " . __('Comment') . " ";
                $value_comment = "";
                if (isset($comment_values[$key])) {
                    $value_comment = $comment_values[$key];
                }
                $name = "comment_values[" . $key . "]";
                echo Html::input($name, ['value' => $value_comment, 'size' => 30]);
                echo '</p>';
                echo "</td>";

                echo "<td>";
                echo "<p id='default_values$key'>";
                echo " " . _n('Default value', 'Default values', 1, 'metademands') . " ";
                $name  = "default_values[" . $key . "]";
                $value = ($default_values[$key] ?? 0);
                Dropdown::showYesNo($name, $value);
                echo '</p>';
                echo "</td>";

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

            echo "<tr>";
            echo "<td colspan='4' align='right' id='show_custom_fields'>";
            PluginMetademandsField::initCustomValue(max(array_keys($values)), true, true);
            echo "</td>";
            echo "</tr>";
        } else {
            echo __('Value') . " 1 ";
            echo Html::input('custom_values[1]', ['size' => 30]);
            echo "</td>";
            echo "<td>";
            echo " " . __('Comment') . " ";
            echo Html::input('comment_values[1]', ['size' => 30]);
            echo "</td>";
            echo "<td>";
            //                  echo " " . _n('Default value', 'Default values', 1, 'metademands') . " ";
            //                  echo '<input type="checkbox" name="default_values[1]"  value="1"/>';
            echo "<p id='default_values$key'>";
            echo " " . _n('Default value', 'Default values', 1, 'metademands') . " ";
            $name  = "default_values[" . $key . "]";
            $value = ($default[$key] ?? 0);
            Dropdown::showYesNo($name, $value);
            echo '</p>';
            echo "</td>";
            echo "</tr>";

            echo "<tr>";
            echo "<td colspan='3' align='right'  id='show_custom_fields'>";
            PluginMetademandsField::initCustomValue(0, true, true);
            echo "</td>";
            echo "</tr>";
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
        if (is_array(json_decode($params['custom_values'], true))) {
            $elements += json_decode($params['custom_values'], true);
        }
        foreach ($elements as $key => $val) {
            $elements[$key] = urldecode($val);
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
        if (is_array(json_decode($params['custom_values'], true))) {
            $elements += json_decode($params['custom_values'], true);
        }
        foreach ($elements as $key => $val) {
            $elements[$key] = urldecode($val);
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

        return  $script;
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

        //if reload form on loading
        if (isset($_SESSION['plugin_metademands'][$metaid]['fields'][$id])) {
            $session_value = $_SESSION['plugin_metademands'][$metaid]['fields'][$id];
            if (is_array($session_value)) {
                foreach ($session_value as $k => $fieldSession) {
                    if ($fieldSession > 0) {
                        $script2 .= "$('[id=\"field[" . $id . "][" . $fieldSession . "]\"]').prop('checked', true);";
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

        $metaid = $data['plugin_metademands_metademands_id'];
        $check_values = $data['options'] ?? [];
        $id = $data["id"];

        $script = "";
        $script2 = "";
        $debug = (isset($_SESSION['glpi_use_mode'])
        && $_SESSION['glpi_use_mode'] == Session::DEBUG_MODE ? true : false);
        if ($debug) {
            $script = "console.log('fieldsHiddenScript-radio $id');";
        }

        //if reload form on loading
        if (isset($_SESSION['plugin_metademands'][$metaid]['fields'][$id])) {
            $session_value = $_SESSION['plugin_metademands'][$metaid]['fields'][$id];

            if (is_array($session_value)) {
                foreach ($session_value as $k => $fieldSession) {
                    $script2 .= "$('[id=\"field[" . $id . "][" . $fieldSession . "]\"]').prop('checked', true);";
                }
            } else {
                $script2 .= "$('[id=\"field[" . $id . "][" . $session_value . "]\"]').prop('checked', true);";
            }
        }

        $script .= "$('[name^=\"field[" . $data["id"] . "]\"]').change(function() {";

        $script .= "var tohide = {};";

        foreach ($check_values as $idc => $check_value) {
            $hidden_link = $check_value['hidden_link'];
            $script .= "if ($hidden_link in tohide) {
                        } else {
                            tohide[$hidden_link] = true;
                        }
                        if (parseInt($(this).val()) == $idc || $idc == -1) {
                            tohide[$hidden_link] = false;
                        }";

            $script2 .= "$('[id-field =\"field" . $hidden_link . "\"]').hide();";

            if (isset($_SESSION['plugin_metademands'][$metaid]['fields'][$id])) {
                $session_value = $_SESSION['plugin_metademands'][$metaid]['fields'][$id];
                if ($session_value == $idc && $hidden_link > 0) {
                    $script2 .= "$('[id-field =\"field" . $hidden_link . "\"]').show();";
                }
            }

//            if (isset($_SESSION['plugin_metademands'][$data["plugin_metademands_metademands_id"]]['fields'][$data["id"]])
//                && ($_SESSION['plugin_metademands'][$data["plugin_metademands_metademands_id"]]['fields'][$data["id"]] == $idc || $idc == -1)) {
//                $script2 .= "$('[id-field =\"field" . $hidden_link . "\"]').show();";
//            }

            //Initialize id default value
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

            $script .= "$.each( tohide, function( key, value ) {
                        if (value == true) {
                            $('[id-field =\"field'+key+'\"]').hide();
                            " . PluginMetademandsFieldoption::resetMandatoryFieldsByField($hidden_link) . "
                            $('[name =\"field['+key+']\"]').removeAttr('required');
                        } else {
                            $('[id-field =\"field'+key+'\"]').show();
//                            " .PluginMetademandsFieldoption::setMandatoryFieldsByField($id, $hidden_link)."
                        }
                    });";
        }
        $script .= "});";


        echo Html::scriptBlock('$(document).ready(function() {' . $script2 . " " . $script . '});');

    }

    public static function blocksHiddenScript($data)
    {
        $metaid = $data['plugin_metademands_metademands_id'];
        $check_values = $data['options'] ?? [];
        $id = $data["id"];


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

        $script = "";
        $script2 = "";

        $debug = (isset($_SESSION['glpi_use_mode'])
        && $_SESSION['glpi_use_mode'] == Session::DEBUG_MODE ? true : false);
        if ($debug) {
            $script = "console.log('blocksHiddenScript-radio $id');";
        }

        if (isset($_SESSION['plugin_metademands'][$metaid]['fields'][$id])) {
            $session_value = $_SESSION['plugin_metademands'][$metaid]['fields'][$id];
            if (is_array($session_value)) {
                foreach ($session_value as $k => $fieldSession) {
                    if (isset($check_values[$fieldSession]['hidden_block'])
                        && $check_values[$fieldSession]['hidden_block'] > 0) {
                        $script .= "$('[bloc-id =\"bloc" . $check_values[$fieldSession]['hidden_block'] . "\"]').show();";
                    }
                }
            }
        }

        $script .= "$('[name^=\"field[" . $data["id"] . "]\"]').change(function() {";

        $script .= "var tohide = {};";

        //by default - hide all
        $script2 .= PluginMetademandsFieldoption::hideAllblockbyDefault($data);
        if (!isset($_SESSION['plugin_metademands'][$metaid]['fields'][$id])) {
            $script2 .= PluginMetademandsFieldoption::emptyAllblockbyDefault($check_values);
        }
        foreach ($check_values as $idc => $check_value) {
            $blocks_idc = [];
            $hidden_block = $check_value['hidden_block'];

            //Default values
            if (is_array(PluginMetademandsField::_unserialize($data['default_values']))) {
                $default_values = PluginMetademandsField::_unserialize($data['default_values']);
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
                                                 " . PluginMetademandsFieldoption::setMandatoryBlockFields($metaid, $childs);
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }

            $script .= "if ($(this).val() == $idc || $idc == -1 ) {";

//            if (!isset($_SESSION['plugin_metademands'][$metaid]['fields'][$id])) {
                foreach ($hiddenblocks_by_checkvalue as $id => $hideblock) {
                    if ($id != $idc) {
                        //specific for radio / dropdowns - one value
                        $script .= "$('[bloc-id=\"bloc'+" . $hideblock . "+'\"]').hide();
                    $('div[bloc-id =\"bloc'+" . $hideblock . "+'\"]').find(':input').each(function() {
                                                 switch(this.type) {
                                                    case 'password':
                                                    case 'text':
                                                    case 'textarea':
                                                    case 'file':
                                                    case 'date':
                                                    case 'number':
                                                    case 'tel':
                                                    case 'email':
                                                        jQuery(this).val('');
                                                        if (typeof tinymce !== 'undefined' && tinymce.get(this.id)) {
                                                            tinymce.get(this.id).setContent('');
                                                        }
                                                        break;
                                                    case 'select-one':
                                                    case 'select-multiple':
                                                        jQuery(this).val('0').trigger('change');
                                                        jQuery(this).val('0');
                                                        break;
                                                    case 'checkbox':
                                                    case 'radio':
                                                         this.checked = false;
                                                         var checkname = this.name;
                                                         $(\"[name^='\"+checkname+\"']\").removeAttr('required');
                                                }
                                                jQuery(this).removeAttr('required');
                                                regex = /multiselectfield.*_to/g;
                                                totest = this.id;
                                                found = totest.match(regex);
                                                if(found !== null) {
                                                  regex = /multiselectfield[0-9]*/;
                                                   found = totest.match(regex);
                                                   $('#'+found[0]+'_leftAll').click();
                                                }
                                            });";

                    }
                }
                foreach ($childs_by_checkvalue as $id => $hidechild) {
                    if ($idc != $id) {
                        foreach ($hidechild as $childs) {
                            //specific for radio / dropdowns - one value
                            $script .= "$('[bloc-id=\"bloc'+" . $childs . "+'\"]').hide();
                    $('div[bloc-id =\"bloc'+" . $childs . "+'\"]').find(':input').each(function() {
                                                 switch(this.type) {
                                                    case 'password':
                                                    case 'text':
                                                    case 'textarea':
                                                    case 'file':
                                                    case 'date':
                                                    case 'number':
                                                    case 'tel':
                                                    case 'email':
                                                        jQuery(this).val('');
                                                        if (typeof tinymce !== 'undefined' && tinymce.get(this.id)) {
                                                            tinymce.get(this.id).setContent('');
                                                        }
                                                        break;
                                                    case 'select-one':
                                                    case 'select-multiple':
                                                        jQuery(this).val('0').trigger('change');
                                                        jQuery(this).val('0');
                                                        break;
                                                    case 'checkbox':
                                                    case 'radio':
                                                         this.checked = false;
                                                         var checkname = this.name;
                                                         $(\"[name^='\"+checkname+\"']\").removeAttr('required');
                                                }
                                                jQuery(this).removeAttr('required');
                                                regex = /multiselectfield.*_to/g;
                                                totest = this.id;
                                                found = totest.match(regex);
                                                if(found !== null) {
                                                  regex = /multiselectfield[0-9]*/;
                                                   found = totest.match(regex);
                                                   $('#'+found[0]+'_leftAll').click();
                                                }
                                            });";
                        }
                    }
                }
//            }

            $script .= "$('[bloc-id =\"bloc'+$hidden_block+'\"]').show();";
            $script .= PluginMetademandsFieldoption::setMandatoryBlockFields($metaid, $hidden_block);

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

                if (is_array($childs_by_checkvalue)) {
                    foreach ($childs_by_checkvalue as $k => $childs_blocks) {
                        if (is_array($session_value)) {
                            foreach ($session_value as $k => $fieldSession) {
                                if ($idc == $fieldSession) {
                                    foreach ($childs_blocks as $childs) {
                                        $script .= "$('[bloc-id =\"bloc" . $childs . "\"]').show();
                                                     " . PluginMetademandsFieldoption::setMandatoryBlockFields($metaid, $childs);
                                    }
                                }
                            }
                        }
                    }
                }
            }

//            if (isset($_SESSION['plugin_metademands'][$data["plugin_metademands_metademands_id"]]['fields'][$data["id"]])
//                && ($_SESSION['plugin_metademands'][$data["plugin_metademands_metademands_id"]]['fields'][$data["id"]] == $idc || $idc == -1)) {
//                $script2 .= "$('[bloc-id =\"bloc" . $hidden_block . "\"]').show();";
//            }

            $script .= " } else {";

            //specific for radio - one value
//            $script .= PluginMetademandsFieldoption::hideAllblockbyDefault($data);

            $script .= " }";

//            $script .= " }";
//
//            $script .= "if ($(this).val() != $idc) {";
//            if (is_array($blocks_idc) && count($blocks_idc) > 0) {
//                foreach ($blocks_idc as $k => $block_idc) {
//                    $script .= "$('[bloc-id =\"bloc" . $block_idc . "\"]').hide();";
//                }
//            }
//            $script .= " }";

        }


        $script .= "fixButtonIndicator();
        });";

        echo Html::scriptBlock('$(document).ready(function() {' . $script2 . " " . $script . '});');
    }

    public static function getFieldValue($field, $label, $lang)
    {
        if (!empty($field['custom_values'])) {
            $custom_values = PluginMetademandsField::_unserialize($field['custom_values']);
            foreach ($custom_values as $k => $val) {
                if (!empty($ret = PluginMetademandsField::displayField($field["id"], "custom" . $k, $lang))) {
                    $custom_values[$k] = $ret;
                }
            }
            if ($field['value'] != "") {
                $field['value'] = PluginMetademandsField::_unserialize($field['value']);
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

    public static function displayFieldItems(&$result, $formatAsTable, $style_title, $label, $field, $return_value, $lang)
    {
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
            $result[$field['rank']]['content'] .= self::getFieldValue($field, $label, $lang);
            if ($formatAsTable) {
                $result[$field['rank']]['content'] .= "</td>";
            }
        } else {
            if ($field['value']) {
                $result[$field['rank']]['display'] = true;
                if ($formatAsTable) {
                    $result[$field['rank']]['content'] .= "<td>";
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
