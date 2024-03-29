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
 * PluginMetademandsTitleblock Class
 *
 **/
class PluginMetademandsTitleblock extends CommonDBTM
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
        return __('Block title', 'metademands');
    }

    static function showWizardField($data, $namefield, $value, $on_order, $preview, $config_link)
    {
        $color = PluginMetademandsWizard::hex2rgba($data['color'], "0.03");
        $rank = $data['rank'];
        $style_background = "style='background-color: $color!important;border-color: " . $data['color'] . "!important;border-radius: 0;margin-bottom: 10px;'";

        if ($preview) {
            echo "<div class=\"card-header preview-md preview-md-$rank\" $style_background data-title='" . $rank . "' >";
        } else {
            echo "<div class='card-header' $style_background>";
        }

        echo "<h2 class=\"card-title\"><span style='color:" . $data['color'] . ";font-weight: normal;'>";
        $icon = $data['icon'];
        if (!empty($icon)) {
            echo "<i class='fa-2x fas $icon' style=\"font-family:'Font Awesome 5 Free', 'Font Awesome 5 Brands';\"></i>&nbsp;";
        }
        if (empty($label = PluginMetademandsField::displayField($data['id'], 'name'))) {
            $label = $data['name'];
        }

        echo $label;
        echo $config_link;
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
        echo "<i id='up" . $rank . "' class='fa-1x fas fa-chevron-up pointer' style='right:40px;position: absolute;color:" . $data['color'] . ";'></i>";
        $rand = mt_rand();
        echo Html::scriptBlock("
                     var myelement$rand = '#up" . $rank . "';
                     var bloc$rand = 'bloc" . $rank . "';
                     $(myelement$rand).click(function() {     
                         if($('[bloc-hideid =' + bloc$rand + ']:visible').length) {
                             $('[bloc-hideid =' + bloc$rand + ']').hide();
                             $(myelement$rand).toggleClass('fa-chevron-up fa-chevron-down');
                         } else {
                             $('[bloc-hideid =' + bloc$rand + ']').show();
                             $(myelement$rand).toggleClass('fa-chevron-down fa-chevron-up');
                         }
                     });");
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

    static function showFieldCustomValues($values, $key, $params)
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

    public static function displayFieldItems(&$result, $formatAsTable, $style_title, $label, $field, $return_value, $lang)
    {

        if ($formatAsTable) {
            $result[$field['rank']]['content'] .= "<th colspan='10'>";
        }
        $result[$field['rank']]['content'] .= $label;
        if ($formatAsTable) {
            $result[$field['rank']]['content'] .= "</th>";
        }

        return $result;
    }

}
