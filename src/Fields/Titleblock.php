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
use Glpi\RichText\RichText;
use Html;
use GlpiPlugin\Metademands\Field;
use GlpiPlugin\Metademands\Wizard;
use Session;

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access directly to this file");
}


/**
 * Titleblock Class
 *
 **/
class Titleblock extends CommonDBTM
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
        return __('Block title', 'metademands');
    }

    public static function showWizardField($data, $namefield, $value, $on_order, $preview, $config_link)
    {
        $debug = isset($_SESSION['glpi_use_mode'])
        && $_SESSION['glpi_use_mode'] == Session::DEBUG_MODE;
        $color = Wizard::hex2rgba($data['color'], "0.03");
        $rank = $data['rank'];
        $style_background = "style='background-color: $color!important;border-color: " . $data['color'] . "!important;border-radius: 0;margin-bottom: 10px;'";

        if ($preview || $debug) {
            echo "<div class=\"card-header preview-md preview-md-$rank\" $style_background data-title='" . $rank . "' >";
        } else {
            echo "<div class='card-header' $style_background>";
        }

        echo "<h2 class=\"card-title\"><span style='color:" . $data['color'] . ";font-weight: normal;'>";
        $icon = $data['icon'];
        if (!empty($icon)) {
            if (str_contains($icon, 'fa-')) {
                echo "<i class='fa-2x fas $icon' style=\"font-family:'Font Awesome 6 Free', 'Font Awesome 6 Brands';\"></i>&nbsp;";
            } else {
                echo "<i class='ti $icon' style=\"font-size:2em;\"></i>&nbsp;";
            }
        }
        if (empty($label = Field::displayField($data['id'], 'name'))) {
            $label = $data['name'];
        }

        echo $label;
        if ($debug) {
            echo " (ID:" . $data['id'] . ")";
        }
        echo $config_link;
        if (isset($data['label2']) && !empty($data['label2'])) {
            echo "&nbsp;";
            if (empty($label2 = Field::displayField($data['id'], 'label2'))) {
                $label2 = $data['label2'];
            }
            Html::showToolTip(
                RichText::getSafeHtml($label2),
                ['awesome-class' => 'ti ti-info-circle']
            );
        }
        echo "<i id='up" . $rank . "' class='ti ti-chevron-up pointer' style='right:40px;position: absolute;color:" . $data['color'] . ";'></i>";
        $rand = mt_rand();
        echo Html::scriptBlock(
            "
                     var myelement$rand = '#up" . $rank . "';
                     var bloc$rand = 'bloc" . $rank . "';
                     $(myelement$rand).click(function() {
                         if($('[bloc-hideid =' + bloc$rand + ']:visible').length) {
                             $('[bloc-hideid =' + bloc$rand + ']').hide();
                             $(myelement$rand).toggleClass('ti ti-chevron-up ti ti-chevron-down');
                         } else {
                             $('[bloc-hideid =' + bloc$rand + ']').show();
                             $(myelement$rand).toggleClass('ti ti-chevron-down ti ti-chevron-up');
                         }
                     });"
        );
        echo "</span></h2>";
        echo "</div>";
        if (!empty($data['comment'])) {
            if (empty($comment = Field::displayField($data['id'], 'comment'))) {
                $comment = $data['comment'];
            }
            $comment = htmlspecialchars_decode(stripslashes($comment));
            echo "<div class='card-body'><i>" . $comment . "</i></div>";
        }
    }

    public static function showFieldCustomValues($params)
    {
    }

    public static function showFieldParameters($params)
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
    }

    public static function fieldsMandatoryScript($data)
    {
    }

    public static function fieldsHiddenScript($data)
    {
    }

    public static function blocksHiddenScript($data)
    {
    }

    public static function displayFieldItems(&$result, $formatAsTable, $style_title, $label, $field, $return_value, $lang, $is_order = false)
    {
        //to true automatickly if another field on the block is loaded
        $result[$field['rank']]['display'] = false;
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
