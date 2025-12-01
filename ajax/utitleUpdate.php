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
if (strpos($_SERVER['PHP_SELF'], "utitleUpdate.php")) {
    include('../../../inc/includes.php');
    header("Content-Type: text/html; charset=UTF-8");
    Html::header_nocache();
}

Session::checkLoginUser();
$fieldUser = new PluginMetademandsField();

if (!isset($_POST['fieldname'])) {
    $_POST['fieldname'] = "field";
}

$fields_id = 0;

if (isset($_POST['id_fielduser']) && $_POST["id_fielduser"] > 0) {
    if (!isset($_POST['field'])) {
        if ($fields = $fieldUser->find(['type'         => "dropdown",
                                       'plugin_metademands_metademands_id' => $_POST['metademands_id'],
                                       'item'         => UserTitle::getType()])) {
            foreach ($fields as $f) {
                $fieldparameter = new PluginMetademandsFieldParameter();
                if ($fieldparameter->getFromDBByCrit(
                    ['plugin_metademands_fields_id' => $f['id'],
                      'link_to_user' => $_POST['id_fielduser']]
                )) {
                    $_POST["field"] = $_POST['fieldname'] . "[" . $f['id'] . "]";
                    $name = $_POST['field'];
                    $fields_id = $f['id'];
                }
            }
        }
    } else {
        if (isset($_SESSION['plugin_metademands'][$_POST['metademands_id']]['fields'][$_POST['id_fielduser']])) {
            $_POST['value'] = $_SESSION['plugin_metademands'][$_POST['metademands_id']]['fields'][$_POST['id_fielduser']];
        }
        $name = $_POST['field'];
    }
} else {
    $name = $_POST['field'] ?? "";
}

$usertitles_id = 0;
if (isset($_POST['value']) && $_POST["value"] > 0
    && isset($_POST['id_fielduser']) && $_POST["id_fielduser"] > 0) {
    $user = new User();
    if ($user->getFromDB($_POST["value"])) {
        $usertitles_id = $user->fields['usertitles_id'];
    }
}

if (isset($_POST['fields_id'])
    && isset($_SESSION['plugin_metademands'][$_POST['metademands_id']]['fields'][$_POST['fields_id']])) {
    $usertitles_id = $_SESSION['plugin_metademands'][$_POST['metademands_id']]['fields'][$_POST['fields_id']];
}

$opt = ['name'  => $name,
        'value' => $usertitles_id,
    'width' => '200px'];

if (isset($_POST["is_mandatory"]) && $_POST['is_mandatory'] == 1) {
    $opt['specific_tags'] = ['required' => 'required'];
}

UserTitle::dropdown($opt);

$_POST['name'] = "title_user".$_POST["id_fielduser"];
$_POST['rand'] = "";
Ajax::commonDropdownUpdateItem($_POST);

$_SESSION['plugin_metademands'][$_POST['metademands_id']]['fields'][$fields_id] = $usertitles_id;
