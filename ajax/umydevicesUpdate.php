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

$AJAX_INCLUDE = 1;
if (strpos($_SERVER['PHP_SELF'], "umydevicesUpdate.php")) {
    include('../../../inc/includes.php');
    header("Content-Type: text/html; charset=UTF-8");
    Html::header_nocache();
}

Session::checkLoginUser();

$fieldUser = new PluginMetademandsField();
$fieldparameter = new PluginMetademandsFieldParameter();

if (isset($_POST["fields_id"])
    && $fieldparameter->getFromDBByCrit(['plugin_metademands_fields_id' => $_POST["fields_id"]])) {
    $_POST['display_type'] = $fieldparameter->fields['display_type'];
}


if (isset($_POST['id_fielduser']) && $_POST["id_fielduser"] > 0) {

    $fieldparameter = new PluginMetademandsFieldParameter();
    if ($fieldparameter->getFromDBByCrit(['plugin_metademands_fields_id' => $_POST["id_fielduser"]])) {
        if ($_POST['value'] == $_POST['id_fielduser']) {
            $_POST['value'] = (isset($fieldparameter->fields['default_use_id_requester'])
                && $fieldparameter->fields['default_use_id_requester'] == 0) ? 0 : Session::getLoginUserID();
        }

    }

    if (!isset($_POST['field'])) {
        if ($fields = $fieldUser->find([
            'type' => "dropdown_meta",
            'plugin_metademands_metademands_id' => $_POST['metademands_id'],
            'item' => "mydevices",
        ])) {
            foreach ($fields as $field) {
                if ($fieldparameter->getFromDBByCrit([
                    'plugin_metademands_fields_id' => $field['id'],
                    'link_to_user' => $_POST['id_fielduser'],
                ])) {
                    $id = $field['id'];
                    $_POST["field"] = "field[$id]";
                    $_POST["is_mandatory"] = $fieldparameter->fields['is_mandatory'];
                    $_POST['limit'] = $fieldparameter->fields['default'];
                    $_POST['display_type'] = $fieldparameter->fields['display_type'];
                }
            }
        }
    } else {
        if (isset($_SESSION['plugin_metademands'][$_POST['metademands_id']]['fields'][$_POST['id_fielduser']])) {
            $_POST['value'] = $_SESSION['plugin_metademands'][$_POST['metademands_id']]['fields'][$_POST['id_fielduser']];
        }
    }
}

$users_id = 0;

$user = new User();
if (isset($_POST['value']) && $_POST["value"] > 0) {
    if ($user->getFromDB($_POST["value"])) {
        $users_id = $_POST['value'];
    }
}

$limit = [];

if (isset($_POST['limit'])) {
    $limit = json_decode($_POST['limit'], true);
}

$val = 0;
if (isset($_POST['fields_id'])
    && isset($_SESSION['plugin_metademands'][$_POST['metademands_id']]['fields'][$_POST['fields_id']])) {
    $val = $_SESSION['plugin_metademands'][$_POST['metademands_id']]['fields'][$_POST['fields_id']];
}

$rand = mt_rand();

$p = [
    'rand' => $rand,
    'name' => $_POST["field"],
    'value' => $val,
    'users_id' => $users_id,
];



if ($_POST['display_type'] == PluginMetademandsDropdownmeta::ICON_DISPLAY) {

    $p['selected_items_id'] = $_POST['selected_items_id'] ?? 0;
    $p['selected_itemtype'] = $_POST['selected_itemtype'] ?? "";
    $p['is_mandatory'] = $_POST['is_mandatory'] ?? 0;
    $p['limit'] = $_POST['limit'] ? $limit : [];
    if ((isset($_POST['value']) && ($_POST["value"] > 0))) {
        $p['users_id'] = $_POST['value'] ?? Session::getLoginUserID();
    }
    $users_id = $p['users_id'];

    PluginMetademandsDropdownmeta::getItemsForUser($p);
    $_POST['name'] = "mydevices_user$users_id";

} else {

    PluginMetademandsField::dropdownMyDevices($users_id, $_SESSION['glpiactiveentities'], 0, 0, $p, $limit);
    $_POST['name'] = "mydevices_user";

}

$_POST['rand'] = "";
Ajax::commonDropdownUpdateItem($_POST);
