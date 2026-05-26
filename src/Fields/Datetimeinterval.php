<?php

/*
 -------------------------------------------------------------------------
 metademands plugin for GLPI
 Copyright (C) 2018-2026 by the metademands Development Team.

 https://github.com/InfotelGLPI/metademands
 -------------------------------------------------------------------------

 LICENSE

 This file is part of metademands.

 metademands is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 metademands is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with metademands. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 */

namespace GlpiPlugin\Metademands\Fields;

use CommonDBTM;
use Glpi\Application\View\TemplateRenderer;
use Glpi\RichText\RichText;
use Html;
use GlpiPlugin\Metademands\Field;

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access directly to this file");
}


/**
 * Datetimeinterval Class
 *
 **/
class Datetimeinterval extends CommonDBTM
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
        return __('Date & Hour interval', 'metademands');
    }

    public static function showWizardField($data, $namefield, $value, $end)
    {
        $opt = [
            'value' => $value,
            'display' => false,
            'required' => (bool) $data['is_mandatory'],
            'size' => 40,
        ];

        $use_future_date = $data['use_future_date'];
        if ($value == null && isset($use_future_date) && !empty($use_future_date)) {
            $opt['mindate'] = date("Y-m-d");
        }

        if (isset($data["use_date_now"]) && $data["use_date_now"] == true) {
            $addDays = $data['additional_number_day'];
            $startDate = time();
            $opt['value'] = date('Y-m-d H:i:s', strtotime("+$addDays day", $startDate));
            $mindate = date('Y-m-d', strtotime("+$addDays day", $startDate));
            $use_future_date = $data['use_future_date'];
            if ($value == null && isset($use_future_date) && !empty($use_future_date)) {
                $opt['mindate'] = $mindate;
            }
        }

        if ($end == true) {
            $field = "<span style='width: 50%!important;display: -webkit-box;'>";
            $field .= Html::showDateTimeField($namefield, $opt);
            $field .= "</span>";
        } else {
            $field = "<span style='width: 50%!important;display: -webkit-box;'>";
            $field .= Html::showDateTimeField($namefield . "[" . $data['id'] . "]", $opt);
            $field .= "</span>";
        }

        echo $field;
    }

    public static function showFieldCustomValues($params) {}

    public static function showFieldParameters($params): string
    {
        ob_start();
        \Dropdown::showYesNo('use_future_date', $params['use_future_date']);
        $use_future_date_html = ob_get_clean();

        ob_start();
        \Dropdown::showYesNo('use_date_now', $params['use_date_now']);
        $use_date_now_html = ob_get_clean();

        ob_start();
        \Dropdown::showNumber('additional_number_day', [
            'value' => $params['additional_number_day'],
            'min'   => 0,
            'max'   => 500,
        ]);
        $additional_number_day_html = ob_get_clean();

        return TemplateRenderer::getInstance()->render(
            '@metademands/fields/field_parameter_interval.html.twig',
            [
                'use_future_date_html'       => $use_future_date_html,
                'use_date_now_html'          => $use_date_now_html,
                'additional_number_day_html' => $additional_number_day_html,
            ]
        );
    }

    public static function checkMandatoryFields($value = [], $fields = [])
    {
        $msg = "";
        $checkKo = 0;
        // Check fields empty
        if ($value['is_mandatory']
            && ($fields['value'] === null || $fields['value'] === '' || $fields['value'] === 'NULL')) {
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
        return Html::convDateTime($field['value']) . " - " . Html::convDateTime($field['value2']);
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
        $colspan = $is_order ? 3 : 1;
        if (empty($label2 = Field::displayField($field['id'], 'label2', $lang))) {
            $label2 = $field['label2'];
            if ($field['label2'] != null) {
                $label2 = RichText::getTextFromHtml($field['label2']);
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
        $result[$field['rank']]['content'] .= Html::convDateTime($field['value']);
        if ($formatAsTable) {
            $result[$field['rank']]['content'] .= "</td></tr>";
            $result[$field['rank']]['content'] .= "<tr class='odd'><td $style_title colspan='$colspan'>";
        }
        $result[$field['rank']]['content'] .= $label2;
        if ($formatAsTable) {
            $result[$field['rank']]['content'] .= "</td><td colspan='$colspan'>";
        }
        $result[$field['rank']]['content'] .= Html::convDateTime($field['value2']);
        if ($formatAsTable) {
            $result[$field['rank']]['content'] .= "</td>";
        }

        return $result;
    }
}
