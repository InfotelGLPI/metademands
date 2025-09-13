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

namespace GlpiPlugin\Metademands\Fields;

use CommonDBTM;
use Html;
use GlpiPlugin\Metademands\Field;

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access directly to this file");
}


/**
 * Datetime Class
 *
 **/
class Datetime extends CommonDBTM
{
    /**
     * Return the localized name of the current Type
     * Should be overloaded in each new class
     *
     * @param integer $nb Number of items
     *
     * @return string
     **/
    public static function getTypeName($nb = 0)
    {
        return __('Date & Hour', 'metademands');
    }

    public static function showWizardField($data, $namefield, $value, $on_order)
    {
        if (empty($comment = Field::displayField($data['id'], 'comment'))) {
            $comment = $data['comment'];
        }

        $opt = [
            'value' => $value,
            'display' => false,
            'required' => (bool) $data['is_mandatory'],
            'size' => 40,
        ];

        $use_future_date = $data['use_future_date'];
        if ($value == null && isset($use_future_date) && !empty($use_future_date)) {
            $opt['mindate'] = $_SESSION["glpi_currenttime"];
        }

        if (isset($data["use_date_now"]) && $data["use_date_now"] == true) {
            $addDays = $data['additional_number_day'];
            $startDate = time();
            $value = date('Y-m-d H:i:s', strtotime("+$addDays day", $startDate));
            $opt['value'] = $value;
            $use_future_date = $data['use_future_date'];
            if ($value == null && isset($use_future_date) && !empty($use_future_date)) {
                $opt['mindate'] = $value;
            }
        }

        $field = "<span style='width: 50%!important;display: -webkit-box;'>";
        $field .= Html::showDateTimeField($namefield . "[" . $data['id'] . "]", $opt);
        $field .= "</span>";

        echo $field;
    }

    public static function showFieldCustomValues($params) {}

    public static function showFieldParameters($params)
    {
        echo "<tr class='tab_bg_1'>";
        echo "<td>";
        echo __('Use this field for child ticket field', 'metademands');
        echo "</td>";
        echo "<td>";
        \Dropdown::showYesNo('used_by_child', $params['used_by_child']);
        echo "</td>";

        echo "<td>";
        echo __('Day greater or equal to now', 'metademands');
        echo "</td>";
        echo "<td>";
        \Dropdown::showYesNo('use_future_date', $params['use_future_date']);
        echo "</td>";
        echo "</tr>";

        echo "<tr class='tab_bg_1'>";
        echo "<td>";
        echo __('Define the default date', 'metademands');
        echo "</td>";
        echo "<td>";
        \Dropdown::showYesNo('use_date_now', $params['use_date_now']);
        echo "</td>";
        echo "<td>";
        echo __('Additional number day to the default date', 'metademands');
        echo "</td>";
        echo "<td>";
        $optionNumber = [
            'value' => $params['additional_number_day'],
            'min' => 0,
            'max' => 500,
        ];
        \Dropdown::showNumber('additional_number_day', $optionNumber);
        echo "</td>";
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

    public static function fieldsMandatoryScript($data) {}

    public static function fieldsHiddenScript($data) {}

    public static function blocksHiddenScript($data) {}

    public static function getFieldValue($field)
    {
        return Html::convDateTime($field['value']);
    }

    public static function displayFieldItems(
        &$result,
        $formatAsTable,
        $style_title,
        $label,
        $field,
        $return_value,
        $lang,
        $is_order = false
    ) {
        $colspan = $is_order ? 6 : 1;
        $result[$field['rank']]['display'] = true;
        if ($field['value'] != 0) {
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
