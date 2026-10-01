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
use GlpiPlugin\Metademands\FieldOption;
use GlpiPlugin\Metademands\MetademandTask;
use Html;

/**
 * Url Class
 *
 **/
class Url extends CommonDBTM
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
        return __('URL');
    }

    public static function showWizardField($data, $namefield, $value, $on_order)
    {
        if (empty($comment = Field::displayField($data['id'], 'comment'))) {
            $comment = $data['comment'];
        }

        $size = "35";
        if ($data['row_display'] == 1) {
            $size = "70";
        }
        $name = $namefield . "[" . $data['id'] . "]";
        $opt = [
            'id-field' => $name,
            'id' => $name,
            'value' => $value,
            'placeholder' => ($comment !== null) ? RichText::getTextFromHtml($comment) : "",
            'size' => $size,
        ];
        $opt['type'] = "url";

        if ($data['is_mandatory'] == 1) {
            $opt['required'] = "required";
        }
        if (!empty($data['used_by_ticket']) && empty($value) && (int) ($data['link_to_user'] ?? 0) > 0) {
            // Prefilled from the linked "User" field by public/scripts/wizard_form.js
            // (initUserPrefill). Html::input() escapes the attributes.
            $opt['data-md-user-source'] = $namefield . "[" . $data['link_to_user'] . "]";
            $opt['data-md-user-key']    = $data['used_by_ticket'];
            $opt['data-md-user-url']    = PLUGIN_METADEMANDS_WEBDIR . '/ajax/uTextFieldUpdate.php';
        }

        TemplateRenderer::getInstance()->display(
            '@metademands/fields/field_input_widget.html.twig',
            ['input_html' => Html::input($name, $opt)],
        );
    }

    public static function showFieldCustomValues($params) {}

    public static function showFieldParameters($params): string
    {
        return '';
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
                'label'             => __('If field empty', 'metademands'),
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
        //cannot use it
        //        $options[2] = __('Yes');
        \Dropdown::showFromArray("check_value", $options, ['value' => $params['check_value'], 'used' => $already_used]);
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
        if (($check_value == 2 && $value != "")) {
            return false;
        } elseif ($check_value == 1 && $value == "") {
            return false;
        }
        return true;
    }

    public static function showParamsValueToCheck($params): string
    {
        $options[1] = __('No');
        $options[2] = __('Yes');
        return $options[$params['check_value']] ?? "";
    }

    public static function fieldsMandatoryScript($data)
    {
        // Value 1: mandatory when the field is filled, otherwise when it is empty
        $options = [
            'negate' => array_values(array_filter(array_keys($data['options'] ?? []), static fn($idc) => $idc != 1)),
        ];
        if (isset($data['value'])) {
            $options['restore'] = ['val' => (string) $data['value']];
            $options['current'] = [$data['value']];
        }
        FieldOption::displayMandatoryTrigger($data, ['name' => 'field[' . $data['id'] . ']', 'match' => 'prefix'], 'filled', $options);
    }

    public static function taskScript($data)
    {
        MetademandTask::displayTaskTrigger($data, ['name' => 'field[' . $data['id'] . ']', 'match' => 'prefix']);
    }

    public static function fieldsHiddenScript($data)
    {
        // Value 1: shown when the field is filled, otherwise when it is empty
        $options = [
            'negate' => array_values(array_filter(array_keys($data['options'] ?? []), static fn($idc) => $idc != 1)),
        ];
        if (isset($data['value'])) {
            $options['restore'] = ['val' => (string) $data['value']];
            $options['current'] = [$data['value']];
        }
        FieldOption::displayHiddenTrigger($data, ['name' => 'field[' . $data['id'] . ']', 'match' => 'prefix'], 'filled', $options);
    }

    public static function blocksHiddenScript($data)
    {
        // Value 1: blocks shown when the field is filled, otherwise when it is empty
        $options = [
            'negate' => array_values(array_filter(array_keys($data['options'] ?? []), static fn($idc) => $idc != 1)),
        ];
        if (isset($data['value'])) {
            $options['restore'] = ['val' => (string) $data['value']];
        }
        FieldOption::displayBlockTrigger($data, ['name' => 'field[' . $data['id'] . ']', 'match' => 'prefix'], 'filled', $options);
    }

    public static function checkConditions($data, $metaparams)
    {
        Condition::displayTrigger($data, $metaparams, ['name' => 'field[' . $data['id'] . ']', 'match' => 'prefix']);
    }

    public static function getFieldValue($field)
    {
        $field['value'] = RichText::getSafeHtml($field['value']);
        $field['value'] = RichText::getTextFromHtml($field['value']);
        return $field['value'];
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
            // Label and getFieldValue() are plain text: the template escapes both.
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
