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
use GlpiPlugin\Metademands\Field;
use Html;

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access directly to this file");
}


/**
 * Information Class
 *
 **/
class Information extends CommonDBTM
{
    public const INFO = 1;
    public const WARNING = 2;
    public const ALERT = 3;
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
        return __('Informations', 'metademands');
    }

    public static function showWizardField($data, $namefield, $value, $on_order, $preview, $config_link)
    {

        $field = '';
        $iconcolor = "info-alert-info";
        $display = "alert-info";
        if ($data["display_type"] == 2) {
            $display = "alert-warning";
            $iconcolor = "info-alert-warning";
        }
        if ($data["display_type"] == 3) {
            $display = "alert-danger";
            $iconcolor = "info-alert-danger";
        }
        $class = "class='alert $display alert-dismissible fade show informations'";
        $field .= "<div $class style='display:flex;align-items: center;'>";

        $todisplay = "";
        if ($data['hide_title'] == 0) {
            if (empty($todisplay = Field::displayField($data['id'], 'name'))) {
                $todisplay = $data['name'];
            }
        }

        if (!empty($data['comment'])) {
            if (empty(Field::displayField($data['id'], 'comment'))) {
                $todisplay .= htmlspecialchars_decode(stripslashes($data['comment']));
            } else {
                $todisplay .= Field::displayField($data['id'], 'comment');
            }
        }

        if (!empty($data['label2'])) {
            if (empty(Field::displayField($data['id'], 'label2'))) {
                $todisplay .= htmlspecialchars_decode(stripslashes($data['label2']));
            } else {
                $todisplay .= Field::displayField($data['id'], 'label2');
            }
        }

        if ($on_order == false && !empty($todisplay)) {
            $icon = $data['icon'];
            $color = $data['color'];
            if ($icon) {
                if (str_contains($icon, 'fa-')) {
                    $field .= "<i class='fas fa-2x $icon' style='color: $color;'></i>&nbsp;";
                } else {
                    $field .= "<i class='ti $icon' style='font-size:2em;color: $color;'></i>&nbsp;";
                }
            }
            $field .= "<div style='color: $color;'>" . htmlspecialchars_decode(stripslashes($todisplay)) . "</div>";
        }
        if ($preview) {
            $field .= $config_link;
        }
        $field .= "</div>";

        echo $field;
    }

    public static function showFieldCustomValues($params) {}


    public static function showFieldParameters($params)
    {

        $values[self::INFO] = __('Information', 'metademands');
        $values[self::WARNING] = __('Warning', 'metademands');
        $values[self::ALERT] = __('Alert', 'metademands');

        echo "<tr class='tab_bg_1'>";

        echo "<td>";
        echo __('Color');
        echo "</td>";

        echo "<td>";
        Html::showColorField('color', ['value' => $params["color"]]);
        echo "</td>";

        echo "<td>";
        echo __('Icon') . "&nbsp;";
        echo "</td>";
        echo "<td>";
        $icon_selector_id = 'icon_' . mt_rand();
        echo Html::select(
            'icon',
            [$params['icon'] => $params['icon']],
            [
                'id' => $icon_selector_id,
                'selected' => $params['icon'],
                'style' => 'width:175px;',
            ]
        );

        echo Html::script('js/modules/Form/WebIconSelector.js');
        echo Html::scriptBlock(
            <<<JAVASCRIPT
         $(
            function() {
            import('/js/modules/Form/WebIconSelector.js').then((m) => {
               var icon_selector = new m.default(document.getElementById('{$icon_selector_id}'));
               icon_selector.init();
               });
            }
         );
        JAVASCRIPT
        );

        echo "&nbsp;<input type='checkbox' name='_blank_picture'>&nbsp;" . __('Clear');
        echo "</td>";
        echo "</tr>";

        echo "<tr class='tab_bg_1'>";

        echo "<td>";
        echo __('Type', 'metademands');
        echo '</td>';

        echo "<td>";

        \Dropdown::showFromArray("display_type", $values, ['value' => $params['display_type']]);
        echo "</td>";
        echo "<td colspan='2'></td>";
        echo "</tr>";

    }

    public static function fieldsMandatoryScript($data) {}

    public static function fieldsHiddenScript($data) {}

    public static function blocksHiddenScript($data) {}

    public static function displayFieldItems(&$result, $formatAsTable, $style_title, $label, $field, $return_value, $lang, $is_order = false)
    {
        $result[$field['rank']]['display'] = false;
        return $result;
    }
}
