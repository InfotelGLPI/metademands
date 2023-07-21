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
        return __('Yes / No', 'metademands');
    }

    static function showWizardField($data, $namefield, $value,  $on_basket) {

        $options[1] = __('No');
        $options[2] = __('Yes');

        $defaults = PluginMetademandsField::_unserialize($data['custom_values']);

        if ($value == "") {
            //warning : this is default value
            $value = $data['custom_values'];
        }

        $value = !empty($value) ? $value : $defaults;

        if (is_array($value)) {
            $value = "";
        }

        $field = "";
        $field .= Dropdown::showFromArray($namefield . "[" . $data['id'] . "]", $options, ['value' => $value,
                'display_emptychoice' => false,
                'class' => '',
//                    'noselect2' => true,
                'width' => '70px',
                'required' => ($data['is_mandatory'] ? "required" : ""),
                'id' => $data['id'],
                'display' => false
            ]
        );

        if ($on_basket == false) {
            echo $field;
        } else {
            return $field;
        }
    }

    static function showFieldCustomValues($values, $key, $params) {


        // Show yes/no default value
        echo "<tr><td id='show_custom_fields'>";
        echo _n('Default value', 'Default values', 1, 'metademands') . "&nbsp;";
        $p= [];
        if (isset($params['custom_values'])) {
            $p['value'] = $params['custom_values'];
        }
        $data[1] = __('No');
        $data[2] = __('Yes');

        Dropdown::showFromArray("custom_values", $data, $p);
        echo "</td></tr>";

    }

    static function getParamsValueToCheck($fieldoption, $item, $params)
    {
        $data[1] = __('No');
        $data[2] = __('Yes');

        // Value to check
        echo "<tr>";
        echo "<td>";
        echo __('Value to check', 'metademands');
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
        $options[1] = __('No');
        $options[2] = __('Yes');
        Dropdown::showFromArray("check_value", $options, ['value' => $params['check_value'], 'used' => $already_used]);
    }

    static function showParamsValueToCheck($params)
    {
        $options[1] = __('No');
        $options[2] = __('Yes');
        echo $options[$params['check_value']] ?? "";

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
        $script2 = "";
        $script = "console.log('fieldsHiddenScript-yesno $id');
                $('[name^=\"field[" . $data["id"] . "]\"]').change(function() {";
        foreach ($check_values as $idc => $check_value) {
            $hidden_link = $data['options'][$idc]['hidden_link'];
            $val = Toolbox::addslashes_deep($idc);
            $script .= "if ($(this).val() == $val) {
                             $('[id-field =\"field" . $hidden_link . "\"]').show();
                           } else {
                            $('[id-field =\"field" . $hidden_link . "\"]').hide();
                            " . PluginMetademandsFieldoption::resetMandatoryFieldsByField($hidden_link) . "
                           }";

            if ($idc == $data["custom_values"]) {
                $script2 .= "$('[id-field =\"field" . $hidden_link . "\"]').show();";
                if (isset($_SESSION['plugin_metademands'][$data["plugin_metademands_metademands_id"]]['fields'][$data["id"]])
                    && $_SESSION['plugin_metademands'][$data["plugin_metademands_metademands_id"]]['fields'][$data["id"]] != $idc) {
                    $script2 .= "$('[id-field =\"field" . $hidden_link . "\"]').hide();";
                }
            } else {
                $script2 .= "$('[id-field =\"field" . $hidden_link . "\"]').hide();";
                if (isset($_SESSION['plugin_metademands'][$data["plugin_metademands_metademands_id"]]['fields'][$data["id"]])
                    && $_SESSION['plugin_metademands'][$data["plugin_metademands_metademands_id"]]['fields'][$data["id"]] == $idc) {
                    $script2 .= "$('[id-field =\"field" . $hidden_link . "\"]').show();";
                }
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

                if (is_array($childs_blocks)) {
                    if (count($childs_blocks) > 0) {
                        $script .= "
                                            if($(this).val() != $idc){";
                        foreach ($childs_blocks as $childs) {
                            if (is_array($childs)) {
                                foreach ($childs as $k => $v) {
                                    if (!is_array($v)) {
                                        $script .= PluginMetademandsFieldoption::resetMandatoryBlockFields($v);
                                        $script .= "$('div[bloc-id=\"bloc$v\"]').hide();";
                                    }
                                }
                            }
                        }
                        $script .= " }";

                        foreach ($childs_blocks as $childs) {
                            if (is_array($childs)) {
                                foreach ($childs as $k => $v) {
                                    if ($v > 0) {
                                        $hiddenblocks[] = $v;
                                        $_SESSION['plugin_metademands'][$data["plugin_metademands_metademands_id"]]['hidden_blocks'] = $hiddenblocks;
                                    }
                                }
                            }
                        }
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
        $check_values = $data['options'];
        $id = $data["id"];

        $script2 = "";
        $script = "console.log('blocksHiddenScript-yesno $id');
                $('[name^=\"field[" . $data["id"] . "]\"]').change(function() {";

        foreach ($check_values as $idc => $check_value) {
            $hidden_block = $data['options'][$idc]['hidden_block'];

            $script .= "if ($(this).val() == $idc) {
                          $('[bloc-id =\"bloc" . $hidden_block . "\"]').show();
                        } else {
                         $('[bloc-id =\"bloc" . $hidden_block . "\"]').hide();
            " . PluginMetademandsFieldoption::resetMandatoryBlockFields($hidden_block)."
                        }";

            if ($idc == $data["custom_values"]) {
                $script2 .= "$('[bloc-id =\"bloc" . $hidden_block . "\"]').show();";
                if (isset($_SESSION['plugin_metademands'][$data["plugin_metademands_metademands_id"]]['fields'][$data["id"]])
                    && $_SESSION['plugin_metademands'][$data["plugin_metademands_metademands_id"]]['fields'][$data["id"]] != $idc) {
                    $script2 .= "$('[bloc-id =\"bloc" . $hidden_block . "\"]').hide();";
                }
            } else {
                $script2 .= "$('[bloc-id =\"bloc" . $hidden_block . "\"]').hide();";
                if (isset($_SESSION['plugin_metademands'][$data["plugin_metademands_metademands_id"]]['fields'][$data["id"]])
                    && $_SESSION['plugin_metademands'][$data["plugin_metademands_metademands_id"]]['fields'][$data["id"]] == $idc) {
                    $script2 .= "$('[bloc-id =\"bloc" . $hidden_block . "\"]').show();";
                }
            }
        }
        $script .= "});";
        $script .= "fixButtonIndicator();";
        //Initialize id default value
        foreach ($check_values as $idc => $check_value) {

            //include child blocks
            if (isset($check_value['childs_blocks']) && $check_value['childs_blocks'] != null) {
                $childs_blocks = json_decode($check_value['childs_blocks'], true);
                if (isset($childs_blocks)
                    && is_array($childs_blocks)
                    && count($childs_blocks) > 0) {
                    foreach ($childs_blocks as $childs) {
                        if (is_array($childs)) {
                            foreach ($childs as $childs_block) {
                                $script2 .= " . PluginMetademandsFieldoption::resetMandatoryBlockFields($childs_block) . ";
                                $hiddenblocks[] = $childs_block;
                                $_SESSION['plugin_metademands']['hidden_blocks'] = $hiddenblocks;
                            }
                        }
                    }
                }

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
        }

        echo Html::scriptBlock('$(document).ready(function() {' . $script2 . " " . $script . '});');
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
