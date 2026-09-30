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
use GlpiPlugin\Metademands\Field;
use GlpiPlugin\Metademands\FieldParameter;

/**
 * Number Class
 *
 **/
class Number extends CommonDBTM
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
        return __('Number', 'metademands');
    }

    public static function showWizardField($data, $namefield, $value, $on_order)
    {
        if (is_array($value)) {
            $value = 0;
        }

        if (isset($data['custom_values'])) {
            $custom_values = FieldParameter::_unserialize($data['custom_values']);
            $opt = [
                'value' => $value === null ? 0 : $value,
                'min' => ((isset($custom_values[0]) && $custom_values[0] != "") ? $custom_values[0] : 0),
                'max' => ((isset($custom_values[1]) && $custom_values[1] != "") ? $custom_values[1] : 9999),
                'step' => ((isset($custom_values[2]) && $custom_values[2] != "") ? $custom_values[2] : 1),
                'display' => false,
            ];
            $minimal_mandatory = ((isset($custom_values[3]) && $custom_values[3] != "") ? $custom_values[3] : 0);
            if (isset($data["is_mandatory"]) && $data['is_mandatory'] == 1) {
                $opt['specific_tags'] = [
                    'required' => 'required',
                    'isnumber' => 'isnumber',
                    'minimal_mandatory' => $minimal_mandatory,
                ];
            }
        } else {
            $opt = [
                'value' => $value === null ? 0 : $value,
                'display' => false,
            ];
        }
        echo TemplateRenderer::getInstance()->render(
            '@metademands/fields/field_widget.html.twig',
            ['widget_html' => \Dropdown::showNumber($namefield . "[" . $data['id'] . "]", $opt)],
        );
    }

    public static function showFieldCustomValues($params)
    {
        $min = 0;
        $max = 0;
        $step = 0;
        $minimal = 0;

        if (isset($params['custom_values']) && !empty($params['custom_values'])) {
            $min = $params['custom_values'][0] ?? "";
            $max = $params['custom_values'][1] ?? "";
            $step = $params['custom_values'][2] ?? "";
            $minimal = $params['custom_values'][3] ?? "";
        }

        echo TemplateRenderer::getInstance()->render(
            '@metademands/fields/field_customvalue_fixed.html.twig',
            [
                'rows' => [[
                    [
                        'label'  => __("Minimal count"),
                        'widget' => ['type' => 'number', 'name' => 'custom[0]', 'value' => $min],
                    ],
                    [
                        'label'  => __("Maximal count"),
                        'widget' => ['type' => 'number', 'name' => 'custom[1]', 'value' => $max, 'options' => ['max' => 9999]],
                    ],
                    [
                        'label'  => __("Step for number", "metademands"),
                        'widget' => ['type' => 'number', 'name' => 'custom[2]', 'value' => $step, 'options' => ['min' => 1]],
                    ],
                    [
                        'label'  => __("Minimal mandatory", "metademands"),
                        'widget' => ['type' => 'number', 'name' => 'custom[3]', 'value' => $minimal],
                    ],
                ]],
            ],
        );
    }

    /**
     * @param array $value
     * @param array $fields
     * @return array
     */
    public static function checkMandatoryFields($value = [], $fields = [])
    {
        $msg = "";
        $checkKo = 0;
        // Check fields empty
        if ($value['is_mandatory']
            && ($fields['value'] === null || $fields['value'] === '')) {
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
        // The value reaches this method straight from the field[<id>] entry of the submitted
        // form, so a forged request can store anything in a column the readers render as a
        // number. Anything non-numeric is dropped rather than echoed back.
        return is_numeric($field['value'] ?? null) ? (string) ($field['value'] + 0) : '';
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
        $colspan = $is_order ? 6 : 1;
        $result[$field['rank']]['display'] = true;
        $result[$field['rank']]['content'] .= Field::renderContentCells(
            (bool) $formatAsTable,
            (string) $title_style,
            [[
                'label'   => (string) $label,
                'value'   => (string) self::getFieldValue($field),
                'colspan' => $colspan,
            ]],
        );

        return $result;
    }

}
