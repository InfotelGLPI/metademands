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
 * PluginMetademandsRange Class
 *
 **/
class PluginMetademandsRange extends CommonDBTM
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
        return __('Range', 'metademands');
    }

    static function showWizardField($data, $namefield, $value, $on_order)
    {

        echo Html::css(PLUGIN_METADEMANDS_DIR_NOFULL . "/css/range.css");

        if (empty($comment = PluginMetademandsField::displayField($data['id'], 'comment'))) {
            $comment = $data['comment'];
        }

        if (is_array($value)) {
            $value = 0;
        }
        $opt = [
            'value' => $value,
            'display' => false,
        ];

        if (isset($data['custom_values'])) {
            $custom_values = PluginMetademandsFieldParameter::_unserialize($data['custom_values']);
            $opt = [
                'value' => $value,
                'min' => ((isset($custom_values[0]) && $custom_values[0] != "") ? $custom_values[0] : 0),
                'max' => ((isset($custom_values[1]) && $custom_values[1] != "") ? $custom_values[1] : 999999),
                'step' => ((isset($custom_values[2]) && $custom_values[2] != "") ? $custom_values[2] : 1),
                'display' => false,
            ];
            $minimal_mandatory = ((isset($custom_values[3]) && $custom_values[3] != "") ? $custom_values[3] : 0);
            if (isset($data["is_mandatory"]) && $data['is_mandatory'] == 1) {
                $opt['specific_tags'] = [
                    'required' => 'required',
                    'isnumber' => 'isnumber',
                    'minimal_mandatory' => $minimal_mandatory
                ];
            }
        } else {
            $opt = [
                'value' => '',
                'min' => 0,
                'max' => 0,
                'step' => 1,
            ];
        }
        $name = $namefield . "[" . $data['id'] . "]";
        $required = "";
        $mandatory = "";
        if (isset($data['is_mandatory']) && $data['is_mandatory'] == 1) {
            $required = "required='required'";
        }
        if ($minimal_mandatory > 0) {
            $mandatory = "minimal_mandatory='$minimal_mandatory'";
        }
        $field = "<div class='range'>";
        $field .= "<div class='range-slider'>";

        if (!isset($opt['value']) || empty($opt['value'])) {
            $opt['value'] = 0;
        }
        $field .= "<input type='range' id='range' $required $mandatory isnumber='isnumber' name='$name' value='".$opt['value']."' min='".$opt['min']."' max='".$opt['max']."' step='".$opt['step']."'>";
        $field .= "<div class='sliderticks'>";

        $min  = $opt['min'];
        $max = $opt['max'];
        $step = $opt['step'];
        for ($i = $min; $i <= $max; $i += $step) {
            $field .= "<span>".$i."</span>";
        }
        $field .= "</div>";
        $field .= "</div>";

        $field .= "<div class='rangevalue'>0</div>";

        $field .= "</div>";

        $js = 'const sliderEl = document.querySelector("#range")
                                        const sliderValue = document.querySelector(".rangevalue")
                                        
                                        sliderEl.addEventListener("input", (event) => {
                                          const tempSliderValue = event.target.value; 
                                          
                                          sliderValue.textContent = tempSliderValue;
                                          
                                          const progress = (tempSliderValue / sliderEl.max) * 100;
                                         
                                          sliderEl.style.background = `linear-gradient(to right, #f50 ${progress}%, #ccc ${progress}%)`;
                                        })';
        echo Html::scriptBlock('$(document).ready(function() {'.$js.'});');

        echo Html::scriptBlock('');
        echo $field;
    }

    static function showFieldCustomValues($params)
    {
        $target = PluginMetademandsFieldCustomvalue::getFormURL();
        echo "<form method='post' action=\"$target\">";
        echo "<tr class='tab_bg_1'>";
        echo "<td>";
        $min = 0;
        $max  = 0;
        $step  = 0;
        $minimal  = 0;

        if (isset($params['custom_values']) && !empty($params['custom_values'])) {
            $min = $params['custom_values'][0] ?? "";
            $max = $params['custom_values'][1] ?? "";
            $step = $params['custom_values'][2] ?? "";
            $minimal = $params['custom_values'][3] ?? "";
        }
        echo '<label>' . __("Minimal count") . '</label>&nbsp;';
        $opt                   = ['value'         => $min];
        Dropdown::showNumber("custom[0]", $opt);
        echo "</td>";

        echo "<td>";
        echo '<label>' . __("Maximal count") . '</label>&nbsp;';
        $opt                   = ['value'         => $max, 'max' => 999999];
        Dropdown::showNumber("custom[1]", $opt);
        echo "</td>";

        echo "<td>";
        echo '<label>' . __("Step for number", "metademands") . '</label>&nbsp;';
        $opt                   = ['value'         => $step, 'min' => 1, 'max' => 999999];
        Dropdown::showNumber("custom[2]", $opt);
        echo "</td>";

        echo "<td>";
        echo '<label>' . __("Minimal mandatory", "metademands") . '</label>&nbsp;';
        $opt                   = ['value'         => $minimal];
        Dropdown::showNumber("custom[3]", $opt);
        echo "</td>";
        echo "</tr>";

        echo "<tr class='tab_bg_1'>";
        echo "<td>";
        echo Html::submit("", ['name'  => 'update',
            'class' => 'btn btn-primary',
            'icon'  => 'fas fa-save']);
        echo "</td>";
        echo "</tr>";
        Html::closeForm();
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

        if ($field['value'] != 0) {
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
        }

        return $result;
    }

}
