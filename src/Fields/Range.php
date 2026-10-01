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
 * Range Class
 *
 **/
class Range extends CommonDBTM
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
        return __('Range', 'metademands');
    }

    public static function showWizardField($data, $namefield, $value, $on_order)
    {
        if (empty($comment = Field::displayField($data['id'], 'comment'))) {
            $comment = $data['comment'];
        }

        // The slider only ever carries a number, but the posted value travels through the
        // session and the database before being rendered, so it is normalised here rather
        // than trusted at the attribute and at the JS label that mirrors it.
        if (is_array($value)) {
            $value = 0;
        } else {
            $value = (int) $value;
        }

        $min               = 0;
        $max               = 9999;
        $step              = 1;
        $minimal_mandatory = 0;

        if (isset($data['custom_values'])) {
            $custom_values     = FieldParameter::_unserialize($data['custom_values']);
            $min               = (isset($custom_values[0]) && $custom_values[0] !== "") ? (int) $custom_values[0] : 0;
            $max               = (isset($custom_values[1]) && $custom_values[1] !== "") ? (int) $custom_values[1] : 9999;
            $step              = (isset($custom_values[2]) && $custom_values[2] !== "") ? (int) $custom_values[2] : 1;
            $minimal_mandatory = (isset($custom_values[3]) && $custom_values[3] !== "") ? (int) $custom_values[3] : 0;
        }

        if (empty($value)) {
            $value = $min;
        }

        $name     = $namefield . "[" . $data['id'] . "]";
        // Unique IDs per field to support multiple Range fields on the same form
        $field_id = 'range_' . $data['id'];

        // Cap ticks at 20 to avoid generating thousands of DOM elements
        $tick_count     = $step > 0 ? (int) floor(($max - $min) / $step) + 1 : 0;
        $show_all_ticks = $tick_count <= 20;


        echo TemplateRenderer::getInstance()->render(
            '@metademands/fields/field_range.html.twig',
            [
                'field_id'       => $field_id,
                'name'           => $name,
                'value'          => $value,
                'min'            => $min,
                'max'            => $max,
                'step'           => $step,
                'id'             => $data['id'],
                'is_required'       => isset($data['is_mandatory']) && $data['is_mandatory'] == 1,
                'minimal_mandatory' => $minimal_mandatory,
                'show_all_ticks' => $show_all_ticks,
            ],
        );
    }

    public static function showFieldCustomValues($params)
    {
        $min      = 0;
        $max      = 0;
        $step     = 0;
        $minimal  = 0;

        if (isset($params['custom_values']) && !empty($params['custom_values'])) {
            $min     = $params['custom_values'][0] ?? "";
            $max     = $params['custom_values'][1] ?? "";
            $step    = $params['custom_values'][2] ?? "";
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
                        'widget' => ['type' => 'number', 'name' => 'custom[2]', 'value' => $step, 'options' => ['min' => 1, 'max' => 9999]],
                    ],
                    [
                        'label'  => __("Minimal mandatory", "metademands"),
                        'widget' => ['type' => 'number', 'name' => 'custom[3]', 'value' => $minimal],
                    ],
                ]],
                // The legacy code closes here the form opened by the caller
                'close_form' => true,
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
        $msg     = "";
        $checkKo = 0;

        if ($value['is_mandatory'] && ($fields['value'] === null || $fields['value'] === '')) {
            $msg     = $value['name'];
            $checkKo = 1;
        }

        // Server-side check for minimal_mandatory (mirrors the client-side attribute)
        if (!$checkKo && isset($value['custom_values'])) {
            $custom_values     = FieldParameter::_unserialize($value['custom_values']);
            $minimal_mandatory = (isset($custom_values[3]) && $custom_values[3] !== "") ? (int) $custom_values[3] : 0;
            if ($minimal_mandatory > 0 && (int) $fields['value'] < $minimal_mandatory) {
                $msg     = $value['name'];
                $checkKo = 1;
            }
        }

        return ['checkKo' => $checkKo, 'msg' => $msg];
    }

    public static function fieldsMandatoryScript($data) {}

    public static function fieldsHiddenScript($data) {}

    public static function blocksHiddenScript($data) {}

    public static function getFieldValue($field)
    {
        // Same reason as Fields\Number::getFieldValue(): the range value is posted as-is and
        // is only ever read back as a number.
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
        if ($field['value'] != 0) {
            $result[$field['rank']]['content'] .= Field::renderContentCells(
                (bool) $formatAsTable,
                (string) $title_style,
                [[
                    'label'   => (string) $label,
                    'value'   => (string) self::getFieldValue($field),
                    'colspan' => $colspan,
                ]],
            );
        }

        return $result;
    }

}
