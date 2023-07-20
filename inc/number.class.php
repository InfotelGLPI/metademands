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
 * PluginMetademandsNumber Class
 *
 **/
class PluginMetademandsNumber extends CommonDBTM
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
        return __('Number', 'metademands');
    }

    static function showWizardField($data, $namefield, $value, $on_basket)
    {

        if (empty($comment = PluginMetademandsField::displayField($data['id'], 'comment'))) {
            $comment = $data['comment'];
        }

        $data['custom_values'] = PluginMetademandsField::_unserialize($data['custom_values']);
        $opt                   = ['value'         => $value,
            'min'           => ((isset($data['custom_values'][0]) && $data['custom_values'][0] != "") ? $data['custom_values'][0] : 0),
            'max'           => ((isset($data['custom_values'][1]) && $data['custom_values'][1] != "") ? $data['custom_values'][1] : 999999),
            'step'          => ((isset($data['custom_values'][2]) && $data['custom_values'][2] != "") ? $data['custom_values'][2] : 1),
            'display'       => false,
        ];
        if (isset($data["is_mandatory"]) && $data['is_mandatory'] == 1) {
            $opt['specific_tags'] = ['required' => 'required', 'isnumber' => 'isnumber'];
        }
        $field = Dropdown::showNumber($namefield . "[" . $data['id'] . "]", $opt);

        if ($on_basket == false) {
            echo $field;
        } else {
            return $field;
        }
    }

    static function showFieldCustomValues($values, $key, $params)
    {
        // Show number custom value
        echo "<tr><td id='show_custom_fields'>";
        $min = 0;
        $max  = 0;
        $step  = 0;
        if (isset($params['custom_values']) && !empty($params['custom_values'])) {
            $params['custom_values'] = PluginMetademandsField::_unserialize($params['custom_values']);
            $min                = $params['custom_values'][0] ?? "";
            $max                 = $params['custom_values'][1] ?? "";
            $step                 = $params['custom_values'][2] ?? "";
        }
        echo '<label>' . __("Minimal count") . '</label>&nbsp;';
        $opt                   = ['value'         => $min];
        Dropdown::showNumber("custom_values[0]", $opt);
        echo "</td>";

        echo "<td>";
        echo '<label>' . __("Maximal count") . '</label>&nbsp;';
        $opt                   = ['value'         => $max];
        Dropdown::showNumber("custom_values[1]", $opt);
        echo "</td>";

        echo "<td>";
        echo '<label>' . __("Step for number", "metademands") . '</label>&nbsp;';
        $opt                   = ['value'         => $step];
        Dropdown::showNumber("custom_values[2]", $opt);
        echo "</td>";
        echo "</tr>";
    }

    static function fieldsLinkScript($data, $idc, $rand)
    {

    }

    static function fieldsHiddenScript($data)
    {

    }

    public static function blocksHiddenScript($data)
    {
        
    }

    public static function getFieldValue($field)
    {
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
