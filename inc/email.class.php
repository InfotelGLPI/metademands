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
 * PluginMetademandsEmail Class
 *
 **/
class PluginMetademandsEmail extends CommonDBTM
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
        return __('Email');
    }

    static function showWizardField($data, $namefield, $value, $on_order)
    {

        if (empty($comment = PluginMetademandsField::displayField($data['id'], 'comment'))) {
            $comment = $data['comment'];
        }

        $size = "35";
        if ($data['row_display'] == 1) {
            $size = "70";
        }
        $name = $namefield . "[" . $data['id'] . "]";
        $opt = ['id-field' => $name,
            'id' => $name,
            'value' => Html::cleanInputText(Toolbox::stripslashes_deep($value)),
            'placeholder' => (!$comment == null) ? Glpi\RichText\RichText::getTextFromHtml($comment) : "",
            'size' => $size
        ];
        $opt['type'] = "email";

        if ($data['is_mandatory'] == 1) {
            $opt['required'] = "required";
        }
        $updateJs = '';
        if (!empty($data['used_by_ticket']) && empty($value)) {
            $updateJs .= "let field{$data['id']} = $(\"[id-field='field{$data['id']}'] input\");
                        field{$data['id']}.val(response[{$data['used_by_ticket']}] ?? '');
                        field{$data['id']}.trigger('input');
                        ";
        }
        $ID = $data['link_to_user'];
        echo "<script type='text/javascript'>
                        $(function() {
                            $(\"[name='field[$ID]']\").ready(function() {
                                 $.ajax({
                                     url: '" . PLUGIN_METADEMANDS_WEBDIR . "/ajax/uTextFieldUpdate.php',
                                     data: { 
                                         id : $(\"[name='field[$ID]']\").val()
                                     },
                                  success: function(response){
                                       response = JSON.parse(response);
                                       $updateJs
                                    },
                                });
                            })
                        })
                    </script>";

        $field = Html::input($name, $opt);

        echo $field;
    }

    static function showFieldCustomValues($params)
    {

    }

    static function showFieldParameters($params)
    {

//        echo "<tr class='tab_bg_1'>";
//        echo "<td>";
//        echo __('Link this to a user field', 'metademands');
//        echo "</td>";
//        echo "<td>";
//
//        $arrayAvailable[0] = Dropdown::EMPTY_VALUE;
//        $field = new PluginMetademandsField();
//        $fields = $field->find([
//            "plugin_metademands_metademands_id" => $params['plugin_metademands_metademands_id'],
//            'type' => "dropdown_object",
//            "item" => User::getType()
//        ]);
//        foreach ($fields as $f) {
//            $arrayAvailable [$f['id']] = $f['rank'] . " - " . urldecode(html_entity_decode($f['name']));
//        }
//        Dropdown::showFromArray('link_to_user', $arrayAvailable, ['value' => $params['link_to_user']]);
//        echo "</td>";
//
//
//        if ($params['link_to_user'] > 0) {
//            echo "<td>" . __('User information to get', 'metademands') . "</td>";
//            $options = [
//                0 => Dropdown::EMPTY_VALUE,
//                6 => _n('Phone', 'Phones', 0),
//                11 => __('Mobile phone'),
//            ];
//            echo "</td>";
//            echo "<td>";
//            Dropdown::showFromArray(
//                'used_by_ticket',
//                $options,
//                ['value' => $params["used_by_ticket"]]
//            );
//            echo "</td>";
//        } else {
//            echo "<td colspan='2'></td>";
//        }
//
//        echo "<tr class='tab_bg_1'>";
//        echo "<td>";
//        echo __('Regex to respect', 'metademands');
//        //               echo '</br><span class="metademands_wizard_comments">' . __('If the selected field is filled, this field will be displayed', 'metademands') . '</span>';
//        echo "</td>";
//        echo "<td>";
//        echo Html::input('regex', ['value' => $params["regex"], 'size' => 50]);
//        echo "</td>";
//        echo "<td colspan='2'></td>";
//        echo "</tr>";

    }

    static function getParamsValueToCheck($fieldoption, $item, $params)
    {
        echo "<tr>";
        echo "<td>";
        echo __('If field empty', 'metademands');
        echo "</td>";
        echo "<td>";
        self::showValueToCheck($fieldoption, $params);
        echo "</td>";

        echo PluginMetademandsFieldOption::showLinkHtml($item->getID(), $params, 1, 0, 1);
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
        $options[1] = __('No');
        //cannot use it
//        $options[2] = __('Yes');
        Dropdown::showFromArray("check_value", $options, ['value' => $params['check_value'], 'used' => $already_used]);
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

    static function isCheckValueOK($value, $check_value)
    {
        if (($check_value == 2 && $value != "")) {
            return false;
        } elseif ($check_value == 1 && $value == "") {
            return false;
        }
    }

    static function showParamsValueToCheck($params)
    {
        $options[1] = __('No');
        $options[2] = __('Yes');
        echo $options[$params['check_value']] ?? "";

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
            $script = "console.log('taskScript-text $id');";
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

        $script .= "$('[name^=\"field[" . $data["id"] . "]\"]').change(function() {";

        foreach ($check_values as $idc => $check_value) {
            $tasks_id = $data['options'][$idc]['plugin_metademands_tasks_id'];

            $script .= "if ($(this).val().trim().length < 1) {
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
//                                if (typeof document.getElementById('nextBtn') !== 'undefined'
//                                && document.getElementById('nextBtn').value){
                                    document.getElementById('nextBtn').innerHTML = '$title'
//                                 }
                                 ";

            $script .= "      } else {
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
                                 
                                 ";
            $script .= "}";

        }
        $script .= "});";

        echo Html::scriptBlock('$(document).ready(function() {' . $script2 . " " . $script . '});');

    }

    static function fieldsHiddenScript($data)
    {
        $metaid = $data['plugin_metademands_metademands_id'];
        $check_values = $data['options'] ?? [];
        $id = $data["id"];

        $onchange = "";
        $pre_onchange = "";
        $post_onchange = "";
        $debug = (isset($_SESSION['glpi_use_mode'])
        && $_SESSION['glpi_use_mode'] == Session::DEBUG_MODE ? true : false);
        if ($debug) {
            $onchange = "console.log('fieldsHiddenScript-text $id');";
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
                    if ($fieldSession != "") {
                        $pre_onchange .= "$('[name=\"field[" . $id . "]\"]').val('$fieldSession').trigger('change');";
                    }
                }
            }
        }

        $onchange .= "$('[name^=\"field[" . $data["id"] . "]\"]').change(function() {";

        foreach ($check_values as $idc => $check_value) {
            $hidden_link = $check_value['hidden_link'];

            if (isset($idc) && $idc == 1) {
                $onchange .= "if ($(this).val().trim().length < 1) {
                                 $('[id-field =\"field" . $hidden_link . "\"]').hide();
                                  " . PluginMetademandsFieldoption::resetMandatoryFieldsByField($hidden_link) . "
                              } else {
                                 $('[id-field =\"field" . $hidden_link . "\"]').show();
                              }
                            ";

                if (isset($_SESSION['plugin_metademands'][$metaid]['fields'][$id])) {
                    $session_value = $_SESSION['plugin_metademands'][$metaid]['fields'][$id];
                    if (is_array($session_value)) {
                        foreach ($session_value as $k => $fieldSession) {
                            if ($fieldSession != "" && $hidden_link > 0) {
                                $pre_onchange .= "$('[id-field =\"field" . $hidden_link . "\"]').show();";
                            }
                        }
                    }
                }
            } else {
                $onchange .= "if ($(this).val().trim().length < 1) {
                                $('[id-field =\"field" . $hidden_link . "\"]').show();
                             } else {
                                $('[id-field =\"field" . $hidden_link . "\"]').hide();
                                 " . PluginMetademandsFieldoption::resetMandatoryFieldsByField($hidden_link) . "
                             }";

                $pre_onchange .= "$('[id-field =\"field" . $hidden_link . "\"]').hide();";

                if (isset($_SESSION['plugin_metademands'][$metaid]['fields'][$id])) {
                    $session_value = $_SESSION['plugin_metademands'][$metaid]['fields'][$id];
                    if (is_array($session_value)) {
                        foreach ($session_value as $k => $fieldSession) {
                            if ($fieldSession == "" && $hidden_link > 0) {
                                $pre_onchange .= "$('[id-field =\"field" . $hidden_link . "\"]').show();";
                            }
                        }
                    }
                }
            }
        }
        $onchange .= "});";

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
            $script = "console.log('blocksHiddenScript-text $id');";
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

            if (isset($idc) && $idc == 1) {

                $script .= "if ($(this).val().trim().length > 0) {";
                $script .= PluginMetademandsFieldoption::hideAllblockbyDefault($data);

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
                            if ($fieldSession != "" && $hidden_block > 0) {
                                $script2 .= "$('[bloc-id =\"bloc" . $hidden_block . "\"]').show();";
                            }
                        }
                    } else {
                        if ($session_value == $idc && $hidden_block > 0) {
                            $script2 .= "$('[bloc-id =\"bloc" . $hidden_block . "\"]').show();";
                        }
                    }
                }
                $script .= " } else {";

                //specific - one value
                $script .= PluginMetademandsFieldoption::hideAllblockbyDefault($data);

                $script .= " }";
//                $script .= " }";
//
//                $script .= "if ($(this).val() != $idc) {";
//                if (is_array($blocks_idc) && count($blocks_idc) > 0) {
//                    foreach ($blocks_idc as $k => $block_idc) {
//                        $script .= "$('[bloc-id =\"bloc" . $block_idc . "\"]').hide();";
//                    }
//                }
//                $script .= " }";
            }
        }
        $script .= "fixButtonIndicator();";
        $script .= "});";


        echo Html::scriptBlock('$(document).ready(function() {' . $script2 . " " . $script . '});');
    }

    public static function getFieldValue($field)
    {
        $field['value'] = Glpi\RichText\RichText::getSafeHtml($field['value']);
        $field['value'] = Glpi\RichText\RichText::getTextFromHtml($field['value']);
        return $field['value'];
    }

    public static function displayFieldItems(&$result, $formatAsTable, $style_title, $label, $field, $return_value, $lang, $is_order = false)
    {
        $colspan = $is_order ? 6 : 1;
        if ($field['value'] != 0) {
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
        }

        return $result;
    }

}
