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
use GlpiPlugin\Metademands\Condition;
use GlpiPlugin\Metademands\Field;
use GlpiPlugin\Metademands\FieldOption;
use GlpiPlugin\Metademands\FieldParameter;
use GlpiPlugin\Metademands\MetademandTask;

/**
 * Yesno Class
 *
 **/
class Yesno extends CommonDBTM
{
    public const CLASSIC_DISPLAY = 0;
    public const SWITCH_DISPLAY = 1;
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
        return __('Yes / No', 'metademands');
    }

    public static function showWizardField($data, $namefield, $value, $on_order)
    {
        $options[1] = __('No');
        $options[2] = __('Yes');

        $defaults = "";
        if (isset($data['custom_values'])) {
            $defaults = FieldParameter::_unserialize($data['custom_values']);
        }


        if ($value == "") {
            //warning : this is default value
            $value = $data['custom_values'] ?? 0;
        }

        $value = !empty($value) ? $value : $defaults;

        if (is_array($value)) {
            $value = "";
        }

        if ($data["display_type"] == self::CLASSIC_DISPLAY) {
            $field = "";
            $field .= \Dropdown::showFromArray(
                $namefield . "[" . $data['id'] . "]",
                $options,
                [
                    'value' => $value,
                    'display_emptychoice' => true,
                    'class' => 'yesno',
                    //                    'noselect2' => true,
                    'width' => '70px',
                    'required' => ($data['is_mandatory'] ? "required" : ""),
                    'id' => $data['id'],
                    'display' => false,
                ],
            );
            echo TemplateRenderer::getInstance()->render(
                '@metademands/fields/field_widget.html.twig',
                ['widget_html' => $field],
            );
        } else {
            self::showSwitchField($data, $namefield, $value);
        }


    }


    /**
     * @param $name
     * @param $value
     */
    public static function showSwitchField($data, $namefield, $value)
    {

        $name = $namefield . "[" . $data['id'] . "]";
        $required = ($data['is_mandatory'] && $value == 0) ? "required" : "";
        $id = $name . "-toggle";

        $is_checked = $value == 2;
        if ($value == 0) {
            $value = 1;
        }


        echo TemplateRenderer::getInstance()->render(
            '@metademands/fields/field_switch.html.twig',
            [
                'id'          => $id,
                'name'        => $name,
                'is_checked'  => $is_checked,
                'value'       => $value,
            ],
        );
    }

    public static function showFieldCustomValues($params)
    {
        // Yes / no default value
        $value = '';
        if (isset($params['custom_values']) && !is_array($params['custom_values'])) {
            $value = $params['custom_values'];
        }

        echo TemplateRenderer::getInstance()->render(
            '@metademands/fields/field_customvalue_fixed.html.twig',
            [
                'rows' => [[[
                    'text'   => _n('Default value', 'Default values', 1, 'metademands') . "\u{00A0}",
                    'widget' => [
                        'type'                => 'array',
                        'name'                => 'custom',
                        'value'               => $value,
                        'elements'            => [1 => __('No'), 2 => __('Yes')],
                        'display_emptychoice' => $params["display_type"] == self::CLASSIC_DISPLAY,
                    ],
                ]]],
            ],
        );
    }

    public static function showFieldParameters($params): string
    {
        $disp = [];
        $disp[self::CLASSIC_DISPLAY] = __("Classic display", "metademands");
        $disp[self::SWITCH_DISPLAY] = __("Switch display", "metademands");
        return TemplateRenderer::getInstance()->render(
            '@metademands/fields/field_parameter_yesno.html.twig',
            [
                'display_type'        => $params['display_type'],
                'display_types'       => $disp,
                'show_switch_warning' => $params["display_type"] == self::SWITCH_DISPLAY,
            ],
        );
    }

    public static function getParamsValueToCheck($fieldoption, $item, $params)
    {
        $data[1] = __('No');
        $data[2] = __('Yes');

        // Value to check
        ob_start();
        self::showValueToCheck($fieldoption, $params);
        $cell_content = ob_get_clean();

        // Value cell, included by the row template; its parameters are read by
        // public/scripts/fieldoption_valuetocheck.js from data-* attributes.
        $valuetocheck = [
            'option_id'       => $params['ID'],
            'with_check_type' => false,
            'with_tech_group' => false,
            'content'         => $cell_content,
        ];

        if ($params['check_value'] == '') {
            $params['check_value'] = 1;
        }

        $link_html = FieldOption::showLinkHtml($item->getID(), $params);

        echo TemplateRenderer::getInstance()->render(
            '@metademands/fields/field_params_value_to_check.html.twig',
            [
                'row_class'         => '',
                'label'             => __('Value to check', 'metademands'),
                'label_colspan'     => 2,
                'regex_html'        => '',
                'valuetocheck'      => $valuetocheck,
                'link_html'         => $link_html,
            ],
        );
    }

    public static function showValueToCheck($item, $params)
    {
        $field = new FieldOption();
        $existing_options = $field->find(["plugin_metademands_fields_id" => $params["plugin_metademands_fields_id"]]);
        $already_used = [];

        $options[1] = __('No');
        $options[2] = __('Yes');
        \Dropdown::showFromArray("check_value", $options, ['value' => $params['check_value'], 'used' => $already_used]);
    }

    public static function showParamsValueToCheck($params): string
    {
        $options[1] = __('No');
        $options[2] = __('Yes');
        return $options[$params['check_value']] ?? "";
    }

    public static function isCheckValueOK($value, $check_value)
    {
        if (($check_value == Field::$not_null || $check_value == 0) && empty($value)) {
            return false;
        } elseif ($check_value != $value
            && ($check_value != Field::$not_null && $check_value != 0)) {
            return false;
        }
        return true;
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


    public static function fieldsMandatoryScript($data)
    {
        $options = ['show_current' => true];
        if (isset($data['value']) && !is_array($data['value'])) {
            $options['current'] = [$data['value']];
        }
        if ($data['display_type'] == self::CLASSIC_DISPLAY) {
            // The value kept in session, otherwise the default one
            $value = isset($data['value']) && $data['value'] > 0
                ? $data['value']
                : (isset($data['custom']) ? FieldParameter::_unserialize($data['custom']) : null);
            if (is_scalar($value) && array_key_exists($value, $data['options'] ?? [])) {
                $options['restore'] = ['val' => (string) $value];
            }
            FieldOption::displayMandatoryTrigger($data, ['name' => 'field[' . $data['id'] . ']', 'match' => 'prefix'], 'select', $options);
            return;
        }
        // The switch is rendered in its state. Value 2 (yes): mandatory when it is
        // on, value 1 (no): when it is off
        $options['negate']  = [1];
        $options['restore'] = ['refresh' => true];
        FieldOption::displayMandatoryTrigger($data, ['name' => 'field[' . $data['id'] . ']', 'match' => 'prefix'], 'switch', $options);
    }

    public static function taskScript($data)
    {
        $options = [];
        if (isset($data['value']) && is_array($data['value']) && count($data['value']) > 0) {
            // The switch is rendered in its state: only flag its tasks
            $options['restore'] = $data['display_type'] == self::CLASSIC_DISPLAY
                ? ['val' => (string) end($data['value'])]
                : ['refresh' => true];
        }
        if (isset($data['custom'])) {
            $options['defaults'] = [FieldParameter::_unserialize($data['custom'])];
        }
        MetademandTask::displayTaskTrigger(
            $data,
            ['name' => 'field[' . $data['id'] . ']', 'match' => 'prefix'],
            $data['display_type'] == self::CLASSIC_DISPLAY ? 'select' : 'switch',
            $options,
        );
    }


    public static function fieldsHiddenScript($data)
    {
        $options = [];
        if (isset($data['value']) && !is_array($data['value'])) {
            $options['current'] = [$data['value']];
        }
        if ($data['display_type'] == self::CLASSIC_DISPLAY) {
            // The value kept in session, otherwise the default one
            $value = isset($data['value']) && $data['value'] > 0
                ? $data['value']
                : (isset($data['custom']) ? FieldParameter::_unserialize($data['custom']) : null);
            if (is_scalar($value) && array_key_exists($value, $data['options'] ?? [])) {
                $options['restore'] = ['val' => (string) $value];
            }
            FieldOption::displayHiddenTrigger($data, ['name' => 'field[' . $data['id'] . ']', 'match' => 'prefix'], 'select', $options);
            return;
        }
        // The switch is rendered in its state. Value 2 (yes): shown when it is on,
        // value 1 (no): when it is off
        $options['negate'] = [1];
        FieldOption::displayHiddenTrigger($data, ['name' => 'field[' . $data['id'] . ']', 'match' => 'prefix'], 'switch', $options);
    }

    public static function blocksHiddenScript($data)
    {
        $options = [];
        if ($data['display_type'] == self::CLASSIC_DISPLAY) {
            // The value kept in session, otherwise the default one
            $value = isset($data['value']) && $data['value'] > 0
                ? $data['value']
                : (isset($data['custom']) ? FieldParameter::_unserialize($data['custom']) : null);
            if (is_scalar($value) && array_key_exists($value, $data['options'] ?? [])) {
                $options['restore'] = ['val' => (string) $value];
            }
            FieldOption::displayBlockTrigger($data, ['name' => 'field[' . $data['id'] . ']', 'match' => 'prefix'], 'select', $options);
            return;
        }
        // The switch is rendered in its state. Value 2 (yes): shown when it is on,
        // value 1 (no): when it is off
        $options['negate'] = [1];
        FieldOption::displayBlockTrigger($data, ['name' => 'field[' . $data['id'] . ']', 'match' => 'prefix'], 'switch', $options);
    }

    public static function checkConditions($data, $metaparams)
    {
        Condition::displayTrigger($data, $metaparams, ['name' => 'field[' . $data['id'] . ']', 'match' => 'exact']);
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
