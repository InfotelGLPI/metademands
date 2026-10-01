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
use GlpiPlugin\Metademands\FieldCustomvalue;
use GlpiPlugin\Metademands\Field;
use GlpiPlugin\Metademands\FieldOption;
use GlpiPlugin\Metademands\FieldParameter;
use GlpiPlugin\Metademands\MetademandTask;

/**
 * Radio Class
 *
 **/
class Radio extends CommonDBTM
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
        return __('Radio button', 'metademands');
    }

    public static function showWizardField($data, $namefield, $value, $on_order)
    {
        if (empty($comment = Field::displayField($data['id'], 'comment'))) {
            $comment = $data['comment'];
        }

        if (empty($data['custom_values'])) {
            return;
        }

        $custom_values = $data['custom_values'];
        $inline = "";
        if ($data['row_display'] == 1) {
            $inline = 'form-check-inline';
        }
        $is_block = ($data["display_type"] == self::BLOCK_DISPLAY);

        $options = [];

        if (count($custom_values) > 0) {
            foreach ($custom_values as $key => $label) {
                $is_checked = false;

                if (empty($value) && isset($label['is_default']) && $on_order == false) {
                    $is_checked = ($label['is_default'] == 1);
                }
                if (isset($value) && $value == $key) {
                    $is_checked = true;
                }

                // Option label / comment / icon come from user-supplied custom-value data stored
                // raw (GLPI 10+): passed raw to the template so {{ }} auto-escapes them (label/icon),
                // the comment is sanitized here (getSafeHtml) and escaped or shown by |safe_html.
                if (empty($name = Field::displayCustomvaluesField($data['id'], $key))) {
                    $name = $label['name'];
                }

                $has_comment = isset($label['comment']) && !empty($label['comment']);
                $comment_html = '';
                $has_icon = false;
                $icon = '';
                $icon_is_fa = false;

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
                    $comment_html = RichText::getSafeHtml($comment);
                }

                if ($is_block) {
                    $icon = $label['icon'];
                    if (empty($label['icon'])) {
                        $icon = $data['icon'];
                    }
                    $has_icon = !empty($icon);
                    $icon_is_fa = str_contains((string) $icon, 'fa-');
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

                // Blocks hidden (and their fields made optional) when the option is
                // checked, by public/scripts/wizard_form.js. The legacy script targeted the
                // ids of the "field" namespace only, hence the guard; it skipped the entry
                // whose position equals the option key.
                $hide_blocks = [];
                if ($namefield === 'field') {
                    foreach ($childs_blocks as $position => $childs) {
                        if ($position == $key) {
                            continue;
                        }
                        // Either a flat list of blocks or a list of lists
                        foreach ((array) $childs as $entry) {
                            foreach ((array) $entry as $block) {
                                $hide_blocks[] = (int) $block;
                            }
                        }
                    }
                }

                $options[] = [
                    'key'                  => $key,
                    'name'                 => $name,
                    'is_checked'           => $is_checked,
                    'has_comment'          => $has_comment,
                    'comment_html'         => $comment_html,
                    'has_icon'             => $has_icon,
                    'icon'                 => (string) $icon,
                    'icon_is_fa'           => $icon_is_fa,
                    'hide_blocks'          => array_values(array_unique($hide_blocks)),
                ];
            }
        }

        echo TemplateRenderer::getInstance()->render('@metademands/fields/field_radio.html.twig', [
            'is_block'    => $is_block,
            'is_required' => $data['is_mandatory'] == 1,
            'inline'      => $inline,
            'namefield'   => $namefield,
            'id'          => $data['id'],
            'options'     => $options,
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
            && ($fields['value'] === null || $fields['value'] === '')) {
            $msg = $value['name'];
            $checkKo = 1;
        }

        return ['checkKo' => $checkKo, 'msg' => $msg];
    }

    public static function isCheckValueOK($value, $check_value)
    {
        if (empty($value) && $value != 0) {
            return false;
        } elseif ($check_value != $value) {
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
        FieldOption::displayMandatoryTrigger($data, ['name' => 'field[' . $data['id'] . ']', 'match' => 'prefix'], 'checked', $options);
    }

    public static function taskScript($data)
    {
        $options = [
            'any'      => [-1],
            'defaults' => MetademandTask::getDefaultCustomValues($data['custom_values'] ?? []),
        ];
        if (isset($data['value']) && $data['value'] !== '') {
            $options['restore'] = ['check' => array_map('strval', array_values((array) $data['value']))];
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
            'any' => [0, -1],
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

    public static function getFieldValue($field, $label, $lang)
    {
        if (!empty($field['custom_values'])) {
            $custom_values = [];
            foreach ($field['custom_values'] as $key => $val) {
                $custom_values[$val['id']] = $val['name'];
            }
            foreach ($custom_values as $k => $val) {
                if (!empty($ret = Field::displayField($field["id"], "custom" . $k, $lang))) {
                    $custom_values[$k] = $ret;
                }
            }
            //TODO MIGRATE
            if ($field['value'] != "") {
                $field['value'] = FieldParameter::_unserialize($field['value']);
            }

            $custom_radio = "";
            foreach ($custom_values as $key => $val) {
                if ($field['value'] == $key && $field['value'] !== "") {
                    $custom_radio = $val;
                }
            }
            return $custom_radio;
        } else {
            if ($field['value']) {
                return $label;
            }
        }
        return "";
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
        if (!empty($field['custom_values']) && $field['value'] > 0) {
            $result[$field['rank']]['content'] .= Field::renderContentCells(
                (bool) $formatAsTable,
                (string) $title_style,
                [[
                    'label'   => (string) $label,
                    'value'   => (string) self::getFieldValue($field, $label, $lang),
                    'colspan' => $colspan,
                ]],
            );
        } else {
            if ($field['value']) {
                $result[$field['rank']]['content'] .= Field::renderContentCells(
                    (bool) $formatAsTable,
                    (string) $title_style,
                    [[
                        'value'      => (string) $label,
                        'colspan'    => $colspan,
                        'value_only' => true,
                    ]],
                );
            }
        }

        return $result;
    }
}
