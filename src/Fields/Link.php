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
use Toolbox;

/**
 * Link Class
 *
 **/
class Link extends CommonDBTM
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
        return __('Link');
    }

    public static function showWizardField($data, $namefield, $value, $on_order)
    {
        if (empty($label2 = Field::displayField($data['id'], 'label2'))) {
            $label2 = $data['label2'];
        }

        $mode = '';
        $url = '';
        $label = '';
        $input_name = '';
        $input_value = '';

        if (!empty($data['custom_values'])) {
            $custom_values = FieldParameter::_unserialize($data['custom_values']);
            foreach ($custom_values as $k => $val) {
                if (!empty($ret = Field::displayField($data["id"], "custom" . $k))) {
                    $custom_values[$k] = $ret;
                }
            }

            // The URL is typed by the form designer and echoed back to every
            // requester. Normalise the scheme here, exactly as getFieldValue()
            // does on the read side, so that neither a javascript: nor a data:
            // payload can ever reach the rendered href.
            $url = self::normalizeUrl($custom_values[1] ?? '');

            switch ($custom_values[0]) {
                case 'button':
                    $mode = 'button';
                    $label = Toolbox::stripTags(!empty($label2) ? $label2 : __('Link'));
                    break;
                case 'link_a':
                    $mode = 'link_a';
                    $label = $url;
                    break;
            }

            $input_name = $namefield . "[" . $data['id'] . "]";
            $input_value = $custom_values[1] ?? '';
        }

        // A dedicated template replaces the former onclick="window.open('...')"
        // handler: the anchor carries the URL in an autoescaped attribute, so
        // there is no JS string context left for a quote to break out of.
        echo TemplateRenderer::getInstance()->render(
            '@metademands/fields/field_link.html.twig',
            [
                'mode' => $mode,
                'url' => $url,
                'label' => $label,
                'input_name' => $input_name,
                'input_value' => $input_value,
            ],
        );
    }

    public static function showFieldCustomValues($params)
    {
        $linkType = 0;
        $linkVal = '';
        if (isset($params['custom_values'])
            && !empty($params['custom_values'])) {
            $custom_values = $params['custom_values'];
            $linkType = $custom_values[0] ?? "";
            $linkVal = $custom_values[1] ?? "";
        }

        echo TemplateRenderer::getInstance()->render(
            '@metademands/fields/field_customvalue_fixed.html.twig',
            [
                'rows' => [[
                    [
                        'label' => __("Link"),
                        'widget' => ['type' => 'text', 'name' => 'custom[1]', 'value' => $linkVal, 'size' => 30],
                    ],
                    [
                        'label' => __("Button Type", "metademands"),
                        'widget' => [
                            'type'     => 'array',
                            'name'     => 'custom[0]',
                            'value'    => $linkType,
                            'elements' => [
                                'button' => __('button', "metademands"),
                                'link_a' => __('Web link'),
                            ],
                        ],
                        'hint'  => __('*use field "Additional label" for the button title', 'metademands'),
                    ],
                ]],
            ],
        );
    }

    public static function isCheckValueOK($value, $check_value)
    {
        if ((($check_value == Field::$not_null || $check_value == 0) && empty($value))) {
            return false;
        }
        return true;
    }

    public static function fieldsMandatoryScript($data) {}

    public static function fieldsHiddenScript($data) {}

    public static function blocksHiddenScript($data) {}

    /**
     * Force an http(s) scheme on a stored link.
     *
     * Prefixing rather than filtering neutralises javascript:, data: and
     * vbscript: by construction: anything that is not already an explicit
     * http(s) URL becomes a relative-looking host, never an executable scheme.
     *
     * @param mixed $url raw value as stored in custom_values
     *
     * @return string
     */
    public static function normalizeUrl($url): string
    {
        $url = (string) $url;

        if (!str_starts_with($url, 'http://') && !str_starts_with($url, 'https://')) {
            $url = "http://" . $url;
        }

        return $url;
    }

    public static function getFieldValue($field)
    {
        $field['value'] = self::normalizeUrl($field['value']);
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
            // normalizeUrl() keeps the scheme filtering; the template escapes the URL
            // in the href and in the anchor text.
            $result[$field['rank']]['content'] .= Field::renderContentCells(
                (bool) $formatAsTable,
                (string) $title_style,
                [[
                    'label'   => (string) $label,
                    'value'   => (string) self::getFieldValue($field),
                    'colspan' => $colspan,
                    'link'    => true,
                ]],
            );
        }

        return $result;
    }

}
