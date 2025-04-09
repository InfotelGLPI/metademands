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
 * PluginMetademandsDateinterval Class
 *
 **/
class PluginMetademandsDateinterval extends CommonDBTM
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
        return __('Date interval', 'metademands');
    }

    static function showWizardField($data, $namefield, $value, $end)
    {

        if (empty($comment = PluginMetademandsField::displayField($data['id'], 'comment'))) {
            $comment = $data['comment'];
        }

        $opt = ['value'    => $value,
            'display'  => false,
            'required' => (bool)$data['is_mandatory'],
            'size'     => 40
        ];

        $use_future_date = $data['use_future_date'];
        $date = date("Y-m-d");

        if (isset($use_future_date) && !empty($use_future_date)) {
            $opt['min'] = $date;
        }

        if (isset($data["use_date_now"]) && $data["use_date_now"] == true) {
            $addDays = $data['additional_number_day'];
            $value = date('Y-m-d', strtotime($date . " + $addDays days"));
            $use_future_date = $data['use_future_date'];
            $opt['value'] = $value;
            if (isset($use_future_date) && !empty($use_future_date)) {
                $opt['min'] = $value;
            }
        }

        if ($end == true) {
            $field = "<span style='width: 50%!important;display: -webkit-box;'>";
            $field .= Html::showDateField($namefield, $opt);
            $field .= "</span>";
        } else {
            $field = "<span style='width: 50%!important;display: -webkit-box;'>";
            $field .= Html::showDateField($namefield . "[" . $data['id'] . "]", $opt);
            $field .= "</span>";
        }



        echo $field;
    }


    static function showFieldCustomValues($params)
    {

    }

    static function showFieldParameters($params)
    {

        echo "<tr class='tab_bg_1'>";
        echo "<td>";
        echo __('Day greater or equal to now', 'metademands');
        echo "</td>";
        echo "<td>";
        $use_future_date = $params['use_future_date'];
        $checked = '';
        if (isset($use_future_date) && !empty($use_future_date)) {
            $checked = 'checked';
        }
        echo "<input type='checkbox' name='use_future_date' value='1' $checked>";
        echo "</td>";

        echo "<td>";
        echo __('Define the default date', 'metademands');
        echo "</td>";
        echo "<td>";
        Dropdown::showYesNo('use_date_now', $params['use_date_now']);
        echo "</td>";

        echo "</tr>";

        echo "<tr class='tab_bg_1'>";
        echo "<td>";
        echo __('Additional number day to the default date', 'metademands');
        echo "</td>";
        echo "<td>";
        $optionNumber = [
            'value' => $params['additional_number_day'],
            'min'   => 0,
            'max'   => 500,
        ];
        Dropdown::showNumber('additional_number_day', $optionNumber);
        echo "</td>";
        echo "<td colspan='2'></td>";
        echo "</tr>";

    }

    public static function checkMandatoryFields($value = [], $fields = [])
    {

        $msg = "";
        $checkKo = 0;
        // Check fields empty
        if ($value['is_mandatory']
            && ($fields['value'] == 'NULL' || empty($fields['value']))) {
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
        return Html::convDate($field['value']) . " - " . Html::convDate($field['value2']);
    }

    public static function displayFieldItems(&$result, $formatAsTable, $style_title, $label, $field, $return_value, $lang, $is_order = false)
    {
        $colspan = $is_order ? 3 : 1;
        if (empty($label2 = PluginMetademandsField::displayField($field['id'], 'label2', $lang))) {
            $label2 = Toolbox::stripslashes_deep($field['label2']);
            if ($field['label2'] != NULL) {
                $label2 = Glpi\RichText\RichText::getTextFromHtml($field['label2']);
            }
        }

        $result[$field['rank']]['display'] = true;
        if ($formatAsTable) {
            $result[$field['rank']]['content'] .= "<td $style_title colspan='$colspan'>";
        }
        $result[$field['rank']]['content'] .= $label;
        if ($formatAsTable) {
            $result[$field['rank']]['content'] .= "</td><td colspan='$colspan'>";
        }
        $result[$field['rank']]['content'] .= Html::convDate($field['value']);
        if ($formatAsTable) {
            $result[$field['rank']]['content'] .= "</td></tr>";
            $result[$field['rank']]['content'] .= "<tr class='odd'><td $style_title colspan='$colspan'>";
        }
        $result[$field['rank']]['content'] .= $label2;
        if ($formatAsTable) {
            $result[$field['rank']]['content'] .= "</td><td colspan='$colspan'>";
        }
        $result[$field['rank']]['content'] .= Html::convDate($field['value2']);
        if ($formatAsTable) {
            $result[$field['rank']]['content'] .= "</td>";
        }

        return $result;
    }

}
