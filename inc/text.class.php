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
 * PluginMetademandsText Class
 *
 **/
class PluginMetademandsText extends CommonDBTM
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
        return __('Text', 'metademands');
    }

    static function showWizardField($data, $namefield, $value, $on_basket)
    {

        if (empty($comment = PluginMetademandsField::displayField($data['id'], 'comment'))) {
            $comment = $data['comment'];
        }

        $name = $namefield . "[" . $data['id'] . "]";
        $opt  = ['value'       => Html::cleanInputText(Toolbox::stripslashes_deep($value)),
            'placeholder' => (!$comment == null) ? Glpi\RichText\RichText::getTextFromHtml($comment) : "",
            'size'        => 35];
        if ($data['is_mandatory'] == 1) {
            $opt['required'] = "required";
        }
        $field = Html::input($name, $opt);

        echo $field;
    }

    static function showFieldCustomValues($values, $key, $params)
    {

    }

    static function showFieldCustomFields($params)
    {

        echo "<tr><td>";
        echo "<table class='metademands_show_custom_fields'>";
        echo "<tr><td>";
        echo __('Regex', 'metademands');
        //               echo '</br><span class="metademands_wizard_comments">' . __('If the selected field is filled, this field will be displayed', 'metademands') . '</span>';
        echo '</td>';
        echo "<td>";
        echo Html::input('regex', ['value' => $params["regex"], 'size' => 50]);
        echo "</td></tr>";
        echo "</table>";
        echo "</td></tr>";

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

    static function fieldsHiddenScript($data)
    {
        $metaid = $data['plugin_metademands_metademands_id'];
        $check_values = $data['options'] ?? [];
        $id = $data["id"];

        $script = "";
        $script2 = "";
        $debug = (isset($_SESSION['glpi_use_mode'])
        && $_SESSION['glpi_use_mode'] == Session::DEBUG_MODE ? true : false);
        if ($debug) {
            $script = "console.log('fieldsHiddenScript-text $id');";
        }

        //if reload form on loading
        if (isset($_SESSION['plugin_metademands'][$metaid]['fields'][$id])) {
            $session_value = $_SESSION['plugin_metademands'][$metaid]['fields'][$id];
            if ($session_value != "") {
                $script2 .= "$('[name=\"field[" . $id . "]\"]').val('$session_value').trigger('change');";
            }
        }

        $script .= "$('[name^=\"field[" . $data["id"] . "]\"]').change(function() {";

        foreach ($check_values as $idc => $check_value) {
            $hidden_link = $check_value['hidden_link'];

            if (isset($idc) && $idc == 1) {
                $script .= "if ($(this).val().trim().length < 1) {
                                 $('[id-field =\"field" . $hidden_link . "\"]').hide();
                                  " . PluginMetademandsFieldoption::resetMandatoryFieldsByField($hidden_link) . "
                              } else {
                                 $('[id-field =\"field" . $hidden_link . "\"]').show();
                              }
                            ";

                $script2 .= "$('[id-field =\"field" . $hidden_link . "\"]').hide();";

                if (isset($_SESSION['plugin_metademands'][$metaid]['fields'][$id])) {
                    $session_value = $_SESSION['plugin_metademands'][$metaid]['fields'][$id];
                    if ($session_value != "" && $hidden_link > 0) {
                        $script2 .= "$('[id-field =\"field" . $hidden_link . "\"]').show();";
                    }
                }
            } else {
                $script .= "if ($(this).val().trim().length < 1) {
                                $('[id-field =\"field" . $hidden_link . "\"]').show();
                             } else {
                                $('[id-field =\"field" . $hidden_link . "\"]').hide();
                                 " . PluginMetademandsFieldoption::resetMandatoryFieldsByField($hidden_link) . "
                             }";

                $script2 .= "$('[id-field =\"field" . $hidden_link . "\"]').hide();";

                if (isset($_SESSION['plugin_metademands'][$metaid]['fields'][$id])) {
                    $session_value = $_SESSION['plugin_metademands'][$metaid]['fields'][$id];
                    if ($session_value == "" && $hidden_link > 0) {
                        $script2 .= "$('[id-field =\"field" . $hidden_link . "\"]').show();";
                    }
                }
            }
        }
        $script .= "});";
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
                            $childs_by_checkvalue[$idc] = $childs;
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
        $script2 .= PluginMetademandsFieldoption::hideAllblockbyDefault($check_values);

        foreach ($check_values as $idc => $check_value) {
            $blocks_idc = [];
            $hidden_block = $check_value['hidden_block'];

            if (isset($idc) && $idc == 1) {

                $script .= "if ($(this).val().trim().length > 0) {";
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
                    if ($session_value != "" && $hidden_block > 0) {
                        $script2 .= "$('[bloc-id =\"bloc" . $hidden_block . "\"]').show();";
                    }
                }
                $script .= " } else {";

                //specific - one value
                $script .= PluginMetademandsFieldoption::hideAllblockbyDefault($check_values);

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

    public static function displayFieldItems(&$result, $formatAsTable, $style_title, $label, $field, $return_value, $lang)
    {

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

        return $result;
    }

}
