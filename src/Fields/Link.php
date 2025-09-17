<?php

/*
 -------------------------------------------------------------------------
 Metademands plugin for GLPI
 Copyright (C) 2003-2019 by the Metademands Development Team.

 -------------------------------------------------------------------------

 LICENSE

 This file is part of Metademands.

 Metademands is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 Metademands is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with Metademands. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 */

namespace GlpiPlugin\Metademands\Fields;

use CommonDBTM;
use Html;
use GlpiPlugin\Metademands\Field;
use GlpiPlugin\Metademands\FieldParameter;

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access directly to this file");
}


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
        if (empty($comment = Field::displayField($data['id'], 'comment'))) {
            $comment = $data['comment'];
        }

        if (empty($label2 = Field::displayField($data['id'], 'label2'))) {
            $label2 = $data['label2'];
        }
        $field = "";

        if (!empty($data['custom_values'])) {
            $custom_values = FieldParameter::_unserialize($data['custom_values']);
            foreach ($custom_values as $k => $val) {
                if (!empty($ret = Field::displayField($data["id"], "custom" . $k))) {
                    $custom_values[$k] = $ret;
                }
            }
            switch ($custom_values[0]) {
                case 'button':
                    $btnLabel = __('Link');
                    if (!empty($label2)) {
                        $btnLabel = $label2;
                    }

                    $field = "<input type='submit' class='submit btn btn-primary' style='margin-top: 5px;' value ='" . Toolbox::stripTags(
                        $btnLabel
                    ) . "'
                     target='_blank' onclick=\"window.open('" . $custom_values[1] . "','_blank');return false\">";

                    break;
                case 'link_a':
                    $field = Html::link($custom_values[1], $custom_values[1], ['target' => '_blank']);
                    break;
            }
            $title = $namefield . "[" . $data['id'] . "]";
            $field .= Html::hidden($title, ['value' => $custom_values[1]]);
        }

        echo $field;
    }

    public static function showFieldCustomValues($params)
    {
        echo "<tr class='tab_bg_1'>";
        echo "<td>";
        $linkType = 0;
        $linkVal = '';
        if (isset($params['custom_values'])
            && !empty($params['custom_values'])) {
            $custom_values = $params['custom_values'];
            $linkType = $custom_values[0] ?? "";
            $linkVal = $custom_values[1] ?? "";
        }
        echo '<label>' . __("Link") . '</label>';
        echo Html::input('custom[1]', ['value' => $linkVal, 'size' => 30]);
        echo "</td>";
        echo "<td>";

        echo '<label>' . __("Button Type", "metademands") . '</label>&nbsp;';
        \Dropdown::showFromArray(
            "custom[0]",
            [
                'button' => __('button', "metademands"),
                'link_a' => __('Web link'),
            ],
            ['value' => $linkType]
        );
        echo "<br /><i>" . __("*use field \"Additional label\" for the button title", "metademands") . "</i>";
        echo "</td>";
        echo "</tr>";
        echo "<tr class='tab_bg_1'>";
        echo "<td>";
        echo Html::submit("", [
            'class' => 'btn btn-primary',
            'icon'  => 'ti ti-device-floppy']);
        echo "</td>";
        echo "</tr>";
    }

    public static function isCheckValueOK($value, $check_value)
    {
        if ((($check_value == Field::$not_null || $check_value == 0) && empty($value))) {
            return false;
        }
    }

    public static function fieldsMandatoryScript($data) {}

    public static function fieldsHiddenScript($data) {}

    public static function blocksHiddenScript($data) {}

    public static function getFieldValue($field)
    {
        if (!str_starts_with($field['value'], 'http://') && !str_starts_with($field['value'], 'https://')) {
            $field['value'] = "http://" . $field['value'];
        }
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
            if (!str_starts_with($field['value'], 'http://') && !str_starts_with($field['value'], 'https://')) {
                $field['value'] = "http://" . $field['value'];
            }
            if ($formatAsTable) {
                $result[$field['rank']]['content'] .= "<td $style_title colspan='$colspan'>";
            }
            $result[$field['rank']]['content'] .= $label;
            if ($formatAsTable) {
                $result[$field['rank']]['content'] .= "</td><td colspan='$colspan'>";
            }
            $result[$field['rank']]['content'] .= '<a href="' . $field['value'] . '" data-mce-href="' . $field['value'] . '" > ' . self::getFieldValue(
                $field
            ) . '</a>';
            if ($formatAsTable) {
                $result[$field['rank']]['content'] .= '</td>';
            }
        }

        return $result;
    }

}
