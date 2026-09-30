<?php

/**
 * -------------------------------------------------------------------------
 * metademands plugin for GLPI
 * Copyright (C) 2018-2026 by the metademands Development Team.
 *
 * https://github.com/InfotelGLPI/metademands
 * -------------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of metademands.
 *
 * metademands is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * metademands is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with metademands. If not, see <http://www.gnu.org/licenses/>.
 * --------------------------------------------------------------------------
 */

namespace GlpiPlugin\Metademands\Fields;

use CommonDBTM;
use Glpi\Application\View\TemplateRenderer;
use Glpi\RichText\RichText;
use Html;
use GlpiPlugin\Metademands\Field;

/**
 * Dateinterval Class
 *
 **/
class Dateinterval extends CommonDBTM
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
        return __('Date interval', 'metademands');
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
            $widget = Html::showDateField($namefield, $opt);
        } else {
            $widget = Html::showDateField($namefield . "[" . $data['id'] . "]", $opt);
        }

        echo TemplateRenderer::getInstance()->render(
            '@metademands/fields/field_span_widget.html.twig',
            ['widget_html' => $widget],
        );
    }


    public static function showFieldCustomValues($params) {}

    public static function showFieldParameters($params): string
    {
        return TemplateRenderer::getInstance()->render(
            '@metademands/fields/field_parameter_interval.html.twig',
            [
                'use_future_date'       => $params['use_future_date'],
                'use_date_now'          => $params['use_date_now'],
                'additional_number_day' => $params['additional_number_day'],
            ],
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
        return Html::convDate($field['value']) . " - " . Html::convDate($field['value2']);
    }

    public static function displayFieldItems(
        &$result,
        $formatAsTable,
        $title_style,
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
        // Start and end each get a label / value pair, the end one on its own row.
        $result[$field['rank']]['content'] .= Field::renderContentCells(
            (bool) $formatAsTable,
            (string) $title_style,
            [
                [
                    'label'   => (string) $label,
                    'value'   => (string) Html::convDate($field['value']),
                    'colspan' => $colspan,
                ],
                [
                    'label'   => (string) $label2,
                    'value'   => (string) Html::convDate($field['value2']),
                    'colspan' => $colspan,
                    'new_row' => true,
                ],
            ],
        );

        return $result;
    }

}
