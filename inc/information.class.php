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
        $class = "class='alert alert-warning alert-dismissible fade show informations'";
        $field .= "<div $class>";

        $todisplay = "";
        if ($data['hide_title'] == 0) {
            if (empty($todisplay = PluginMetademandsField::displayField($data['id'], 'name'))) {
                $todisplay = $data['name'];
            }
        }

        if (empty($todisplay) && empty($todisplay = PluginMetademandsField::displayField($data['id'], 'comment'))) {
            $todisplay = $data['comment'];
        }

        if (empty($todisplay) && !empty($data['label2'])) {
            $todisplay = $data['label2'];
            if (empty($todisplay = PluginMetademandsField::displayField($data['id'], 'label2'))) {
                $todisplay = htmlspecialchars_decode(stripslashes($data['label2']));
            }
        }

        if ($on_order == false && !empty($todisplay)) {
            $icon = $data['icon'];
            $color = $data['color'];
            if ($icon) {
                $field = "<i class='fas fa-2x $icon' style='color: $color;'></i>&nbsp;";
            }
            $field .= "<label class='col-form-label' style='color: $color;'>" . htmlspecialchars_decode(stripslashes($todisplay)) . "</label>";
        }
        if ($preview) {
            $field .= $config_link;
        }
        $field .= "</div>";

        echo $field;
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

}
