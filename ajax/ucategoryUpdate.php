<?php

/**
 * -------------------------------------------------------------------------
 * metademands plugin for GLPI
 * Copyright (C) 2018-2026 by the metademands Development Team.
 *
 * https://github.com/InfotelGLPI/metademands
 * -------------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of metademands.
 *
 * metademands is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * metademands is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with metademands. If not, see <http://www.gnu.org/licenses/>.
 * --------------------------------------------------------------------------
 */

use GlpiPlugin\Metademands\Config;
use GlpiPlugin\Metademands\Field;
use GlpiPlugin\Metademands\FieldParameter;
use GlpiPlugin\Metademands\Fields\Dropdown;
use GlpiPlugin\Metademands\Metademand;
use GlpiPlugin\Metademands\Wizard;

$AJAX_INCLUDE = 1;
if (strpos($_SERVER['PHP_SELF'], "ucategoryUpdate.php")) {
    header("Content-Type: text/html; charset=UTF-8");
    Html::header_nocache();
}

if (!isset($_POST['fieldname'])) {
    $_POST['fieldname'] = "field";
}

$fieldUser = new Field();
$fieldparameter = new FieldParameter();

//Update donc $_POST['field'] doesn't exist
if (isset($_POST['id_fielduser']) && $_POST["id_fielduser"] > 0) {
    if (!isset($_POST['field'])) {
        if ($fields = $fieldUser->find(['type'         => "dropdown",
            'plugin_metademands_metademands_id' => $_POST['metademands_id'],
            'item'         => UserCategory::getType()])) {
            foreach ($fields as $f) {
                if ($fieldparameter->getFromDBByCrit(
                    ['plugin_metademands_fields_id' => $f['id'],
                        'link_to_user' => $_POST['id_fielduser']],
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
if (isset($_POST['users_id']) && $_POST["users_id"] > 0) {
    $users_id = (int) $_POST["users_id"];
    $user = new User();
    // Only expose another user's profile attribute when the caller may read that user
    // — prevents PII enumeration by id. Single rule for the whole u*Update.php family.
    if (
        $user->getFromDB($users_id)
        && Config::canCurrentUserViewRequester($users_id)
    ) {
        $val = $user->fields['usercategories_id'];
    }
}

if (isset($_POST['fields_id'])
    && isset($_SESSION['plugin_metademands'][$_POST['metademands_id']]['fields'][$_POST['fields_id']])) {
    $val = $_SESSION['plugin_metademands'][$_POST['metademands_id']]['fields'][$_POST['fields_id']];
}

// [S3] Normalize the reflected form field name: it is echoed back as the name attribute
// of the dropdown rendered below (shared rule for the whole u*Update.php family).
$_POST['field'] = Config::normalizePostedFieldName($_POST['field'] ?? '');

$opt = ['name' => $_POST['field'],
    'value' => $val,
    'width' => '200px'];

if (isset($_POST["is_mandatory"]) && $_POST['is_mandatory'] == 1) {
    $opt['specific_tags'] = ['required' => 'required'];
}

UserCategory::dropdown($opt);

$_POST['name'] = "category_user" . $_POST["id_fielduser"] . $_POST['fields_id'];
$_POST['rand'] = "";
Ajax::commonDropdownUpdateItem($_POST);

$metademands = new Metademand();
$metademands->getFromDB($_POST['metademands_id']);
$metaconditionsparams = Wizard::getConditionsParams($metademands);
$data['id'] = $_POST['fields_id'];
$data['item'] = UserCategory::getType();
$data['plugin_metademands_metademands_id'] = $_POST['metademands_id'];
Dropdown::checkConditions($data, $metaconditionsparams);
