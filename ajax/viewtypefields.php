<?php
/*
 * @version $Id: HEADER 15930 2011-10-30 15:47:55Z tsmr $
 -------------------------------------------------------------------------
 Metademands plugin for GLPI
 Copyright (C) 2018-2022 by the Metademands Development Team.

 https://github.com/InfotelGLPI/metademands
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

include('../../../inc/includes.php');
header("Content-Type: text/html; charset=UTF-8");
Html::header_nocache();

Session::checkLoginUser();

if (!isset($_POST['step'])) {
    $_POST['step'] = 'default';
}

switch ($_POST['step']) {
    case 'order':
        $fields = new PluginMetademandsField();
        $fields->showOrderDropdown(
            $_POST['rank'],
            $_POST['fields_id'],
            $_POST['previous_fields_id'],
            $_POST["metademands_id"]
        );
        break;
    case 'object':
        global $CFG_GLPI;
        if ($_POST['type'] === 'text') {
            echo "
                <div class='custom-control custom-checkbox custom-control-inline'>
                    <label>" . __('Link this to a user field', 'metademands') . "</label>
                    <input class='form-check-input' type='checkbox' name='item' value='User'>
                </div>
            ";
        } else {

            $randItem = PluginMetademandsField::dropdownFieldItems(
                $_POST["type"],
                ['value' => $_POST['item'], 'rand' => $_POST["rand"]]
            );
            $paramsItem = [
                'value' => '__VALUE__',
                'item' => '__VALUE__',
                'type' => $_POST['type'],
                'metademands_id' => $_POST["metademands_id"],
            ];

            Ajax::updateItemOnSelectEvent(
                'dropdown_item' . $randItem,
                "show_values",
                PLUGIN_METADEMANDS_WEBDIR .
                "/ajax/viewtypefields.php?id=" . $_POST['metademands_id'],
                $paramsItem
            );
        }
        break;
    case 'listfieldbytype':
        $fields = new PluginMetademandsField();
        $crit = ["type" => $_POST['value']];
        $rand = PluginMetademandsField::dropdown(['name' => "existing_field_id", "condition" => $crit]);
        $params = ['fields_id' => '__VALUE__'];
        Ajax::updateItemOnSelectEvent(
            'dropdown_existing_field_id' . $rand,
            "show_fields_infos",
            PLUGIN_METADEMANDS_WEBDIR .
            "/ajax/viewfieldinfos.php",
            $params
        );
        break;
    default:
        break;
}

Html::ajaxFooter();
