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
use Html;
use GlpiPlugin\Metademands\Field;
use GlpiPlugin\Metademands\FieldParameter;

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access directly to this file");
}


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
        $css_html = Html::css(PLUGIN_METADEMANDS_WEBDIR . "/css/range.css");

        if (empty($comment = Field::displayField($data['id'], 'comment'))) {
            $comment = $data['comment'];
        }

        if (is_array($value)) {
            $value = 0;
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

        $required       = (isset($data['is_mandatory']) && $data['is_mandatory'] == 1) ? "required='required'" : "";
        $mandatory_attr = $minimal_mandatory > 0 ? "minimal_mandatory='$minimal_mandatory'" : "";

        // Cap ticks at 20 to avoid generating thousands of DOM elements
        $tick_count     = $step > 0 ? (int) floor(($max - $min) / $step) + 1 : 0;
        $show_all_ticks = $tick_count <= 20;

        // IIFE to scope variables, supporting multiple Range fields per page
        $js = "(function() {
            const sliderEl    = document.querySelector('#$field_id');
            const sliderValue = document.querySelector('#rangevalue_{$data['id']}');
            const updateSlider = (val) => {
                sliderValue.textContent = val;
                // Account for min offset in progress calculation
                const range    = sliderEl.max - sliderEl.min;
                const progress = range > 0 ? (val - sliderEl.min) / range * 100 : 0;
                sliderEl.style.background = `linear-gradient(to right, #f50 \${progress}%, #ccc \${progress}%)`;
            };
            updateSlider(sliderEl.value);
            sliderEl.addEventListener('input', (event) => updateSlider(event.target.value));
        })();";

        echo TemplateRenderer::getInstance()->render(
            '@metademands/fields/field_range.html.twig',
            [
                'css_html'       => $css_html,
                'script_html'    => Html::scriptBlock('$(document).ready(function() {' . $js . '});'),
                'field_id'       => $field_id,
                'name'           => $name,
                'value'          => $value,
                'min'            => $min,
                'max'            => $max,
                'step'           => $step,
                'id'             => $data['id'],
                'required'       => $required,
                'mandatory_attr' => $mandatory_attr,
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

        ob_start();
        echo '<label>' . __("Minimal count") . '</label>&nbsp;';
        $opt = ['value' => $min];
        \Dropdown::showNumber("custom[0]", $opt);
        $min_cell = ob_get_clean();

        ob_start();
        echo '<label>' . __("Maximal count") . '</label>&nbsp;';
        $opt = ['value' => $max, 'max' => 9999];
        \Dropdown::showNumber("custom[1]", $opt);
        $max_cell = ob_get_clean();

        ob_start();
        echo '<label>' . __("Step for number", "metademands") . '</label>&nbsp;';
        $opt = ['value' => $step, 'min' => 1, 'max' => 9999];
        \Dropdown::showNumber("custom[2]", $opt);
        $step_cell = ob_get_clean();

        ob_start();
        echo '<label>' . __("Minimal mandatory", "metademands") . '</label>&nbsp;';
        $opt = ['value' => $minimal];
        \Dropdown::showNumber("custom[3]", $opt);
        $minimal_cell = ob_get_clean();

        ob_start();
        echo Html::submit("", ['name'  => 'update',
            'class' => 'btn btn-primary',
            'icon'  => 'ti ti-device-floppy']);
        $submit_html = ob_get_clean();

        ob_start();
        Html::closeForm();
        $after_html = ob_get_clean();

        echo TemplateRenderer::getInstance()->render(
            '@metademands/fields/field_customvalue_fixed.html.twig',
            [
                'rows' => [[
                    ['html' => $min_cell],
                    ['html' => $max_cell],
                    ['html' => $step_cell],
                    ['html' => $minimal_cell],
                ]],
                'submit_html' => $submit_html,
                'after_html'  => $after_html,
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
        return $field['value'];
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
