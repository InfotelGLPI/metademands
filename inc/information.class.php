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

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access directly to this file");
}


/**
 * PluginMetademandsInformation Class
 *
 **/
class PluginMetademandsInformation extends CommonDBTM
{

    const INFO = 1;
    const WARNING = 2;
    const ALERT = 3;
    /**
     * Return the localized name of the current Type
     * Should be overloaded in each new class
     *
     * @param integer $nb Number of items
     *
     * @return string
     **/
    static function getTypeName($nb = 0)
    {
        return __('Informations', 'metademands');
    }

    static function showWizardField($data, $namefield, $value, $on_order, $preview, $config_link)
    {

        $field = '';

        $display = "alert-info";
        if ($data["display_type"] == 2) {
            $display = "alert-warning";
        }
        if ($data["display_type"] == 3) {
            $display = "alert-danger";
        }
        $class = "class='alert $display alert-dismissible fade show informations'";
        $field .= "<div $class>";

        $todisplay = "";
        if ($data['hide_title'] == 0) {
            if (empty($todisplay = PluginMetademandsField::displayField($data['id'], 'name'))) {
                $todisplay = $data['name'];
            }
        }

        if (!empty($data['comment'])) {
            if (empty(PluginMetademandsField::displayField($data['id'], 'comment'))) {
                $todisplay .= htmlspecialchars_decode(stripslashes($data['comment']));
            } else {
                $todisplay .=PluginMetademandsField::displayField($data['id'], 'comment');
            }
        }

        if (!empty($data['label2'])) {
            if (empty(PluginMetademandsField::displayField($data['id'], 'label2'))) {
                $todisplay .= htmlspecialchars_decode(stripslashes($data['label2']));
            } else {
                $todisplay .=PluginMetademandsField::displayField($data['id'], 'label2');
            }
        }

        if ($on_order == false && !empty($todisplay)) {
            $icon = $data['icon'];
            $color = $data['color'];
            if ($icon) {
                $field .= "<i class='fas fa-2x $icon' style='color: $color;'></i>&nbsp;";
            }
            $field .= "<label style='color: $color;'>" . htmlspecialchars_decode(stripslashes($todisplay)) . "</label>";
        }
        if ($preview) {
            $field .= $config_link;
        }
        $field .= "</div>";

        echo $field;
    }

    static function showFieldCustomValues($params)
    {

    }


    static function showFieldParameters($params)
    {
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
                'style' => 'width:175px;'
            ]
        );

        echo Html::script('js/Forms/FaIconSelector.js');
        echo Html::scriptBlock(
            <<<JAVASCRIPT
         $(
            function() {
               var icon_selector = new GLPI.Forms.FaIconSelector(document.getElementById('{$icon_selector_id}'));
               icon_selector.init();
            }
         );
JAVASCRIPT
        );
        echo "</td>";
        echo "</tr>";

        echo "<tr class='tab_bg_1'>";

        echo "<td>";
        echo __('Type', 'metademands');
        echo '</td>';

        echo "<td>";
        $values[self::INFO] = __('Information', 'metademands');
        $values[self::WARNING] = __('Warning', 'metademands');
        $values[self::ALERT] = __('Alert', 'metademands');

        Dropdown::showFromArray("display_type", $values, ['value' => $params['display_type']]);
        echo "</td>";
        echo "<td colspan='2'></td>";
        echo "</tr>";

    }

    static function fieldsLinkScript($data, $idc, $rand)
    {

    }

    static function fieldsHiddenScript($data)
    {

    }

    public static function blocksHiddenScript($data)
    {

    }

    public static function displayFieldItems(&$result, $formatAsTable, $style_title, $label, $field, $return_value, $lang, $is_order = false)
    {
        $result[$field['rank']]['display'] = false;
        return $result;
    }
}
