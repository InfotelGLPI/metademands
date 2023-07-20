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
 * PluginMetademandsDateinterval Class
 *
 **/
class PluginMetademandsDateinterval extends CommonDBTM
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
        return __('Date interval', 'metademands');
    }

    static function showWizardField($data, $namefield, $value, $on_basket)
    {

        if (empty($comment = PluginMetademandsField::displayField($data['id'], 'comment'))) {
            $comment = $data['comment'];
        }

        $field = "<span style='width: 50%!important;display: -webkit-box;'>";
        $field .= Html::showDateField($namefield . "[" . $data['id'] . "]", ['value'    => $value,
            'display'  => false,
            'required' => ($data['is_mandatory'] ? "required" : ""),
            'size'     => 40
        ]);
        $field .= "</span>";

        if ($on_basket == false) {
            echo $field;
        } else {
            return $field;
        }
    }

    static function showFieldCustomValues($values, $key, $params)
    {

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

    public static function getFieldValue($field)
    {
        return Html::convDate($field['value']) . " - " . Html::convDate($field['value2']);
    }

    public static function displayFieldItems(&$result, $formatAsTable, $style_title, $label, $field, $return_value, $lang)
    {

        if (empty($label2 = PluginMetademandsField::displayField($field['id'], 'label2', $lang))) {
            $label2 = Toolbox::stripslashes_deep($field['label2']);
            if ($field['label2'] != NULL) {
                $label2 = Glpi\RichText\RichText::getTextFromHtml($field['label2']);
            }
        }

        $result[$field['rank']]['display'] = true;
        if ($formatAsTable) {
            $result[$field['rank']]['content'] .= "<td $style_title>";
        }
        $result[$field['rank']]['content'] .= $label;
        if ($formatAsTable) {
            $result[$field['rank']]['content'] .= "</td><td>";
        }
        $result[$field['rank']]['content'] .= Html::convDate($field['value']);
        if ($formatAsTable) {
            $result[$field['rank']]['content'] .= "</td></tr>";
            $result[$field['rank']]['content'] .= "<tr class='odd'><td $style_title>";
        }
        $result[$field['rank']]['content'] .= $label2;
        if ($formatAsTable) {
            $result[$field['rank']]['content'] .= "</td><td>";
        }
        $result[$field['rank']]['content'] .= Html::convDate($field['value2']);
        if ($formatAsTable) {
            $result[$field['rank']]['content'] .= "</td>";
        }

        return $result;
    }

}
