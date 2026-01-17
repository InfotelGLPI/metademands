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
if (strpos($_SERVER['PHP_SELF'], "ucategoryUpdate.php")) {
    include('../../../inc/includes.php');
    header("Content-Type: text/html; charset=UTF-8");
    Html::header_nocache();
}

Session::checkLoginUser();

if (!isset($_POST['fieldname'])) {
    $_POST['fieldname'] = "field";
}

$fieldUser = new PluginMetademandsField();
$fieldparameter = new PluginMetademandsFieldParameter();

//Update donc $_POST['field'] doesn't exist
if (isset($_POST['id_fielduser']) && $_POST["id_fielduser"] > 0) {
    if (!isset($_POST['field'])) {
        if ($fields = $fieldUser->find(['type'         => "dropdown",
            'plugin_metademands_metademands_id' => $_POST['metademands_id'],
            'item'         => UserCategory::getType()])) {
            foreach ($fields as $f) {
                if ($fieldparameter->getFromDBByCrit(
                    ['plugin_metademands_fields_id' => $f['id'],
                        'link_to_user' => $_POST['id_fielduser']]
                )) {
                    $id = $f['id'];
                    $_POST["field"] = $_POST['fieldname'] . "[$id]";
                    $_POST["fields_id"] = $id;
                    $_POST["is_mandatory"] = $fieldparameter->fields['is_mandatory'];
                }
            }
        }
    }
}

$val = 0;
if (isset($_POST['value']) && $_POST["value"] > 0) {
    $user = new User();
    if ($user->getFromDB($_POST["value"])) {
        $val = $user->fields['usercategories_id'];
    }
}

if (isset($_POST['fields_id'])
    && isset($_SESSION['plugin_metademands'][$_POST['metademands_id']]['fields'][$_POST['fields_id']])) {
    $val = $_SESSION['plugin_metademands'][$_POST['metademands_id']]['fields'][$_POST['fields_id']];
}

$opt = ['name' => $_POST['field'],
    'value' => $val,
    'width' => '200px'];

if (isset($_POST["is_mandatory"]) && $_POST['is_mandatory'] == 1) {
    $opt['specific_tags'] = ['required' => 'required'];
}

UserCategory::dropdown($opt);

$_POST['name'] = "category_user" . $_POST["id_fielduser"].$_POST['fields_id'];
$_POST['rand'] = "";

Ajax::commonDropdownUpdateItem($_POST);
$metademands = new PluginMetademandsMetademand();
$metademands->getFromDB($_POST['metademands_id']);
$metaconditionsparams = PluginMetademandsWizard::getConditionsParams($metademands);
$data['id'] = $_POST['fields_id'];
$data['item'] = UserCategory::getType();
PluginMetademandsDropdown::checkConditions($data, $metaconditionsparams);
