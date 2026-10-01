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
use GlpiPlugin\Metademands\Condition;
use GlpiPlugin\Metademands\Field;
use GlpiPlugin\Metademands\FieldCustomvalue;
use GlpiPlugin\Metademands\FieldOption;
use GlpiPlugin\Metademands\MetademandTask;

/**
 * Checkbox Class
 *
 **/
class Checkbox extends CommonDBTM
{
    public const CLASSIC_DISPLAY = 0;
    public const BLOCK_DISPLAY = 1;

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
        return __('Checkbox', 'metademands');
    }

    public static function showWizardField($data, $namefield, $value, $on_order)
    {
        if (empty($comment = Field::displayField($data['id'], 'comment'))) {
            $comment = $data['comment'];
        }

        // No custom values: render a single plain checkbox.
        if (empty($data['custom_values'])) {
            echo TemplateRenderer::getInstance()->render('@metademands/fields/field_checkbox.html.twig', [
                'is_simple'      => true,
                'simple_checked' => (bool) $value,
                'namefield'      => $namefield,
                'id'             => $data['id'],
                'options'        => [],
            ]);
            return;
        }

        $custom_values = $data['custom_values'];

        $inline = "";
        if ($data['row_display'] == 1) {
            $inline = 'form-check-inline';
        }

        $is_block = ($data["display_type"] == self::BLOCK_DISPLAY);

        // Build the option list with raw text (Twig auto-escapes name/icon) and pre-sanitized
        // rich HTML for comments.
        $options = [];
        if (count($custom_values) > 0) {
            foreach ($custom_values as $key => $label) {
                $is_checked = false;
                if (isset($value[$key]) && $value[$key] == $key) {
                    $is_checked = true;
                } elseif (isset($label['is_default']) && $on_order == false) {
                    $is_checked = ($label['is_default'] == 1);
                }

                if (empty($name = Field::displayCustomvaluesField($data['id'], $key))) {
                    $name = $label['name'];
                }

                $icon = $label['icon'];
                if (empty($label['icon'])) {
                    $icon = $data['icon'];
                }

                $has_comment = isset($label['comment']) && !empty($label['comment']);
                $comment_html = "";
                if ($has_comment) {
                    if (empty(
                        $comment = Field::displayCustomvaluesField(
                            $data['id'],
                            $key,
                            "comment",
                        )
                    )) {
                        $comment = $label['comment'];
                    }
                    // Sanitized here, escaped or shown by |safe_html by the template
                    $comment_html = RichText::getSafeHtml($comment);
                }

                $childs_blocks = [];
                $fieldopt = new FieldOption();
                if ($opts = $fieldopt->find(
                    ["plugin_metademands_fields_id" => $data['id'], "check_value" => $key],
                )) {
                    foreach ($opts as $opt) {
                        if (!empty($opt['childs_blocks'])) {
                            $childs_blocks[] = json_decode($opt['childs_blocks'], true);
                        }
                    }
                }

                // Blocks hidden (and their fields made optional) on each click on the
                // option, by public/scripts/wizard_form.js. The legacy script targeted
                // the ids of the "field" namespace only, hence the guard.
                $hide_blocks = [];
                if ($namefield === 'field' && isset($childs_blocks[$key])) {
                    $hide_blocks = array_values(array_map('intval', (array) $childs_blocks[$key]));
                }

                $options[] = [
                    'key'                  => $key,
                    'name'                 => $name,
                    'is_checked'           => $is_checked,
                    'has_comment'          => $has_comment,
                    'comment_html'         => $comment_html,
                    'has_icon'             => !empty($icon),
                    'icon'                 => (string) $icon,
                    'icon_is_fa'           => str_contains((string) $icon, 'fa-'),
                    'hide_blocks'          => $hide_blocks,
                ];
            }
        }

        echo TemplateRenderer::getInstance()->render('@metademands/fields/field_checkbox.html.twig', [
            'is_simple'      => false,
            'simple_checked' => false,
            'is_required'    => $data['is_mandatory'] == 1,
            'is_block'       => $is_block,
            'inline'         => $inline,
            'namefield'      => $namefield,
            'id'             => $data['id'],
            'options'        => $options,
        ]);
    }

    public static function showFieldCustomValues($params)
    {
        FieldCustomvalue::showList(FieldCustomvalue::getListContext($params, true, true, true));
    }

    public static function showFieldParameters($params): string
    {
        $disp = [];
        $disp[self::CLASSIC_DISPLAY] = __("Classic display", "metademands");
        $disp[self::BLOCK_DISPLAY] = __("Block display", "metademands");
        return TemplateRenderer::getInstance()->render(
            '@metademands/fields/field_parameter_checkbox_radio.html.twig',
            [
                'display_type'  => $params['display_type'],
                'display_types' => $disp,
            ],
        );
    }

    public static function getParamsValueToCheck($fieldoption, $item, $params)
    {
        ob_start();
        self::showValueToCheck($fieldoption, $params);
        $cell_content = ob_get_clean();

        // Value cell, included by the row template; its parameters are read by
        // public/scripts/fieldoption_valuetocheck.js from data-* attributes.
        $valuetocheck = [
            'option_id'       => $params['ID'],
            'with_check_type' => false,
            'with_tech_group' => true,
            'content'         => $cell_content,
        ];

        $link_html = FieldOption::showLinkHtml($item->getID(), $params);

        echo TemplateRenderer::getInstance()->render(
            '@metademands/fields/field_params_value_to_check.html.twig',
            [
                'row_class'         => 'tab_bg_1',
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
        $already_used = [];
        $elements[-1] = __('Not null value', 'metademands');
        foreach ($params['custom_values'] as $key => $val) {
            $elements[$val['id']] = $val['name'];
        }
        \Dropdown::showFromArray(
            "check_value",
            $elements,
            ['value' => $params['check_value'], 'used' => $already_used],
        );
    }

    public static function showParamsValueToCheck($params): string
    {
        $elements[-1] = __('Not null value', 'metademands');
        foreach ($params['custom_values'] as $key => $val) {
            $elements[$val['id']] = $val['name'];
        }
        return $elements[$params['check_value']] ?? "";
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
            && $fields['value'] === null) {
            $msg = $value['name'];
            $checkKo = 1;
        }

        return ['checkKo' => $checkKo, 'msg' => $msg];
    }

    public static function isCheckValueOK($value, $check_value)
    {
        if (!empty($value)) {
            $ok = false;
            if ($check_value == -1) {
                $ok = true;
            }
            if (is_array($value)) {
                foreach ($value as $key => $v) {
                    //                     if ($key != 0) {
                    if ($check_value == $key) {
                        $ok = true;
                    }
                    //                     }
                }
            } elseif (is_array(json_decode($value, true))) {
                foreach (json_decode($value, true) as $key => $v) {
                    //                     if ($key != 0) {
                    if ($check_value == $key) {
                        $ok = true;
                    }
                    //                     }
                }
            }
            if (!$ok) {
                return false;
            }
        } else {
            return false;
        }
        return true;
    }

    public static function fieldsMandatoryScript($data)
    {
        $values = $data['value'] ?? [];
        $values = is_array($values) ? $values : [$values];
        $options = [
            'any'     => [-1],
            'current' => $values,
        ];
        if (count($values) > 0) {
            $options['restore'] = ['check' => array_map('strval', array_values($values))];
        }
        // Every checkbox of a linked checkbox field
        $options['target_match'] = 'contains';
        FieldOption::displayMandatoryTrigger($data, ['name' => 'field[' . $data['id'] . ']', 'match' => 'prefix'], 'checked', $options);
    }

    public static function taskScript($data)
    {
        $options = [
            'any'      => [-1],
            'defaults' => MetademandTask::getDefaultCustomValues($data['custom_values'] ?? []),
        ];
        if (isset($data['value']) && is_array($data['value'])) {
            $options['restore'] = ['check' => array_map('strval', array_values($data['value']))];
        }
        MetademandTask::displayTaskTrigger($data, ['name' => 'field[' . $data['id'] . ']', 'match' => 'prefix'], 'checked', $options);
    }

    public static function fieldsHiddenScript($data)
    {
        $values = $data['value'] ?? [];
        $values = is_array($values) ? $values : [$values];
        $options = [
            'any'     => [-1],
            'current' => $values,
        ];
        if (count($values) > 0) {
            $options['restore'] = ['check' => array_map('strval', array_values($values))];
        }
        FieldOption::displayHiddenTrigger($data, ['name' => 'field[' . $data['id'] . ']', 'match' => 'prefix'], 'checked', $options);
    }

    public static function blocksHiddenScript($data)
    {
        $values = $data['value'] ?? [];
        $values = is_array($values) ? $values : [$values];
        $options = [
            'any' => [-1],
        ];
        if (count($values) > 0) {
            $options['restore'] = ['check' => array_map('strval', array_values($values))];
        }
        FieldOption::displayBlockTrigger($data, ['name' => 'field[' . $data['id'] . ']', 'match' => 'prefix'], 'checked', $options);
    }

    public static function checkConditions($data, $metaparams)
    {
        Condition::displayTrigger($data, $metaparams, ['name' => 'field[' . $data['id'] . ']', 'match' => 'prefix']);
    }

    public static function getFieldValue($field, $lang)
    {
        if (isset($field['custom_values']) && !empty($field['custom_values'])) {
            $custom_values = [];
            foreach ($field['custom_values'] as $key => $val) {
                $custom_values[$val['id']] = $val['name'];
            }
            foreach ($custom_values as $k => $val) {
                if (!empty($ret = Field::displayField($field["id"], "custom" . $k, $lang))) {
                    $custom_values[$k] = $ret;
                }
            }
            if (!empty($field['value'])) {
                if (is_string($field['value'])) {
                    $field['value'] = FieldCustomvalue::_unserialize($field['value']);
                } else {
                    $field['value'] = json_decode(json_encode($field['value']), true);
                }
            }
            $custom_checkbox = [];

            foreach ($custom_values as $key => $val) {
                $checked = isset($field['value'][$key]) ? 1 : 0;
                if ($checked) {
                    $custom_checkbox[] = $val;
                }
            }
            return implode(',', $custom_checkbox);
        } else {
            if ($field['value']) {
                return $field['value'];
            }
            return '';
        }
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
        if (is_string($field['value'])) {
            $field['value'] = FieldCustomvalue::_unserialize($field['value']);
        } else {
            $field['value'] = json_decode(json_encode($field['value']), true);
        }

        $result[$field['rank']]['display'] = true;
        if (!empty($field['custom_values']) && !empty($field['value'])) {
            $result[$field['rank']]['content'] .= Field::renderContentCells(
                (bool) $formatAsTable,
                (string) $title_style,
                [[
                    'label'   => (string) $label,
                    'value'   => (string) self::getFieldValue($field, $lang),
                    'colspan' => $colspan,
                ]],
            );
        } else {
            if ($field['value']) {
                $result[$field['rank']]['content'] .= Field::renderContentCells(
                    (bool) $formatAsTable,
                    (string) $title_style,
                    [[
                        'value'      => (string) self::getFieldValue($field, $lang),
                        'colspan'    => $colspan,
                        'value_only' => true,
                    ]],
                );
            }
        }

        return $result;
    }
}
