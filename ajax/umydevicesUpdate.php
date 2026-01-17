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

use GlpiPlugin\Metademands\Field;
use GlpiPlugin\Metademands\FieldParameter;
use GlpiPlugin\Metademands\Fields\Dropdownmeta;
use GlpiPlugin\Metademands\Metademand;
use GlpiPlugin\Metademands\Wizard;

$AJAX_INCLUDE = 1;
if (strpos($_SERVER['PHP_SELF'], "umydevicesUpdate.php")) {
    header("Content-Type: text/html; charset=UTF-8");
    Html::header_nocache();
}

Session::checkLoginUser();

if (!isset($_POST['fieldname'])) {
    $_POST['fieldname'] = "field";
}

$fieldUser = new Field();
$fieldparameter = new FieldParameter();

//Update donc $_POST['field'] doesn't exist
if (isset($_POST['id_fielduser']) && $_POST["id_fielduser"] > 0) {
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
                    $_POST["field"] = $_POST['fieldname'] . "[$id]";
                    $_POST["fields_id"] = $id;
                    $_POST["is_mandatory"] = $fieldparameter->fields['is_mandatory'];
                    $_POST['limit'] = $fieldparameter->fields['default'];
                    $_POST['display_type'] = $fieldparameter->fields['display_type'];
                }
            }
        }
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
    'rand' => "",
    'name' => $_POST['field'],
    'value' => $val,
    'users_id' => $_POST['users_id'],
];

if ($_POST['display_type'] == Dropdownmeta::ICON_DISPLAY) {
    $p['selected_items_id'] = $_POST['selected_items_id'] ?? 0;
    $p['selected_itemtype'] = $_POST['selected_itemtype'] ?? "";
    $p['is_mandatory'] = $_POST['is_mandatory'] ?? 0;
    $p['limit'] = $_POST['limit'] ? $limit : [];

    Dropdownmeta::getItemsForUser($p);

} else {
    Field::dropdownMyDevices($p['users_id'], $_SESSION['glpiactiveentities'], 0, 0, $p, $limit);
}

$_POST['name'] = "mydevices_user".$_POST["id_fielduser"].$_POST['fields_id'];
$_POST['rand'] = "";

Ajax::commonDropdownUpdateItem($_POST);

$metademands = new Metademand();
$metademands->getFromDB($_POST['metademands_id']);
$metaconditionsparams = Wizard::getConditionsParams($metademands);
$data['id'] = $_POST['fields_id'];
$data['item'] = "mydevices";
Dropdownmeta::checkConditions($data, $metaconditionsparams);
