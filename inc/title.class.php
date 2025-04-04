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
 * PluginMetademandsTitle Class
 *
 **/
class PluginMetademandsTitle extends CommonDBTM
{

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
        return __('Title');
    }

    static function showWizardField($data, $namefield, $value, $on_order, $preview, $config_link)
    {
        $debug = (isset($_SESSION['glpi_use_mode'])
        && $_SESSION['glpi_use_mode'] == Session::DEBUG_MODE ? true : false);

        if ($data['hide_title'] == 0) {
            $color = PluginMetademandsWizard::hex2rgba($data['color'], "0.03");
            $style_background = "style='background-color: $color!important;border-color:" . $data['color'] . "!important;border-radius: 0;margin-bottom: 10px;'";

            echo "<div id-field='field" . $data["id"] . "' class='card-header' $style_background>";
            echo "<br><h2 class='card-title'><span style='color:" . $data['color'] . ";font-weight: normal;'>";
            $icon = $data['icon'];
            if (!empty($icon)) {
                echo "<i class='fa-2x fas $icon' style=\"font-family:'Font Awesome 5 Free', 'Font Awesome 5 Brands';\"></i>&nbsp;";
            }
            if (empty($label = PluginMetademandsField::displayField($data['id'], 'name'))) {
                $label = "";
                if (isset($data['name'])) {
                    $label = $data['name'];
                }
            }

            echo $label;
            if ($debug) {
                echo " (ID:". $data['id'].")";
            }

            if (isset($data['label2']) && !empty($data['label2'])) {
                echo "&nbsp;";
                if (empty($label2 = PluginMetademandsField::displayField($data['id'], 'label2'))) {
                    $label2 = $data['label2'];
                }
                Html::showToolTip(
                    Glpi\RichText\RichText::getSafeHtml($label2),
                    ['awesome-class' => 'fa-info-circle']
                );
            }
            if ($preview) {
                echo $config_link;
            }
            echo "</span></h2>";
            echo "</div>";
            if (!empty($data['comment'])) {
                if (empty($comment = PluginMetademandsField::displayField($data['id'], 'comment'))) {
                    $comment = $data['comment'];
                }
                $comment = htmlspecialchars_decode(stripslashes($comment));
                echo "<div class='card-body'><i>" . $comment . "</i></div>";
            }
        }
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
        echo "&nbsp;<input type='checkbox' name='_blank_picture'>&nbsp;" . __('Clear');
        echo "</td>";
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
        //to true automatickly if another field on the block is loaded
//        $result[$field['rank']]['display'] = false;

        if ($formatAsTable) {
            $colspan = $is_order ? 12 : 2;
            $result[$field['rank']]['content'] .= "<th colspan='$colspan'>";
        }
        $result[$field['rank']]['content'] .= $label;
        if ($formatAsTable) {
            $result[$field['rank']]['content'] .= "</th>";
        }

        return $result;
    }

}
