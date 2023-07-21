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

        if ($on_basket == false) {
            echo $field;
        } else {
            return $field;
        }
    }

    static function showFieldCustomValues($values, $key, $params)
    {

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
        $options[2] = __('Yes');
        Dropdown::showFromArray("check_value", $options, ['value' => $params['check_value'], 'used' => $already_used]);
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

        $check_values = $data['options'];
        $id = $data["id"];

        $script = "console.log('fieldsHiddenScript-text $id');
                    $('[name^=\"field[" . $data["id"] . "]\"]').change(function() {";
        $script2 = "";

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

                if (isset($_SESSION['plugin_metademands'][$data["plugin_metademands_metademands_id"]]['fields'][$data["id"]])
                    && $_SESSION['plugin_metademands'][$data["plugin_metademands_metademands_id"]]['fields'][$data["id"]] != "") {
                    $script2 .= "$('[id-field =\"field" . $hidden_link . "\"]').show();";
                }
            } else {
                $script .= "if ($(this).val().trim().length < 1) {
                                $('[id-field =\"field" . $hidden_link . "\"]').show();
                             } else {
                                $('[id-field =\"field" . $hidden_link . "\"]').hide();
                                 " . PluginMetademandsFieldoption::resetMandatoryFieldsByField($hidden_link) . "
                             }";

                $script2 .= "$('[id-field =\"field" . $hidden_link . "\"]').hide();";

                if (isset($_SESSION['plugin_metademands'][$data["plugin_metademands_metademands_id"]]['fields'][$data["id"]])
                    && $_SESSION['plugin_metademands'][$data["plugin_metademands_metademands_id"]]['fields'][$data["id"]] == "") {
                    $script2 .= "$('[id-field =\"field" . $hidden_link . "\"]').show();";
                }
            }
            $script .= "});";
        }
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
        $check_values = $data['options'];
        $id = $data["id"];

        $script = "console.log('blocksHiddenScript-text $id');
                  $('[name^=\"field[" . $data["id"] . "]\"]').change(function() {";
        $script2 = "";

        foreach ($check_values as $idc => $check_value) {
            $hidden_block = $check_value['hidden_block'];
            if (isset($idc) && $idc == 1) {
                $script .= "if ($(this).val().trim().length < 1) {
                               $('[bloc-id =\"bloc" . $hidden_block . "\"]').hide();";
                $script .= PluginMetademandsFieldoption::resetMandatoryBlockFields($hidden_block);
                $script .= "} else {
                              $('[bloc-id =\"bloc" . $hidden_block . "\"]').show();
                           }";

                $script2 .= "$('[bloc-id =\"bloc" . $hidden_block . "\"]').hide();";

                if (isset($_SESSION['plugin_metademands'][$data["plugin_metademands_metademands_id"]]['fields'][$data["id"]])
                    && $_SESSION['plugin_metademands'][$data["plugin_metademands_metademands_id"]]['fields'][$data["id"]] != "") {
                    $script2 .= "$('[bloc-id =\"bloc" . $hidden_block . "\"]').show();";
                }

            } else {
                $script .= "if ($(this).val().trim().length < 1) {
                               $('[bloc-id =\"bloc" . $hidden_block . "\"]').show();
                            } else {
                               $('[bloc-id =\"bloc" . $hidden_block . "\"]').hide();";
                $script .= PluginMetademandsFieldoption::resetMandatoryBlockFields($hidden_block);
                $script .= " }";

                $script2 .= "$('[bloc-id =\"bloc" . $hidden_block . "\"]').hide();";

                if (isset($_SESSION['plugin_metademands'][$data["plugin_metademands_metademands_id"]]['fields'][$data["id"]])
                    && $_SESSION['plugin_metademands'][$data["plugin_metademands_metademands_id"]]['fields'][$data["id"]] == "") {
                    $script2 .= "$('[bloc-id =\"bloc" . $hidden_block . "\"]').show();";
                }
            }

            if (isset($data['options'])) {
                $childs_blocks = [];

                $opts = $data['options'];
                foreach ($opts as $optid => $opt) {
                    if ($optid == $idc) {
                        if (!empty($opt['childs_blocks'])) {
                            $childs_blocks[] = json_decode($opt['childs_blocks'], true);
                        }
                    }
                }

                if (is_array($childs_blocks) && count($childs_blocks) > 0) {
                    if (isset($idc) && $idc == 1) {
                        $script .= " if ($(this).val().trim().length < 1) {";
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
                    } else {
                        $script .= " if ($(this).val().trim().length >= 1) {";
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
                    }

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
                            $script2 .= "$('[bloc-id =\"bloc" . $hidden_block . "\"]').show();";
                        }
                    }
                }
            }
        }
        $script .= "fixButtonIndicator();";
        $script .= "});";


        echo Html::scriptBlock('$(document).ready(function() {' . $script2 . " " . $script . '});');
    }

    public static function getFieldValue($field)
    {
        $field['value'] = Glpi\RichText\RichText::getSafeHtml($field['value']);
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
