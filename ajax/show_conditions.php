<?php

/*
 -------------------------------------------------------------------------
 metademands plugin for GLPI
 Copyright (C) 2018-2026 by the metademands Development Team.

 https://github.com/InfotelGLPI/metademands
 -------------------------------------------------------------------------

 LICENSE

 This file is part of metademands.

 metademands is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 metademands is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with metademands. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 */

use GlpiPlugin\Metademands\Condition;
use GlpiPlugin\Metademands\Field;

header("Content-Type: text/html; charset=UTF-8");
Html::header_nocache();
Session::checkLoginUser();

if (isset($_POST['fields_id'])) {
    $fields_id = $_POST['fields_id'];
}
if (isset($_POST['rand'])) {
    $rand = $_POST['rand'];
}
$field = new Field();
if ($field->getFromDB($fields_id)) {
    $item = $field->fields['item'];
    $type = $field->fields['type'];
    $options = [
        'display_emptychoice' => false,
        'rand' => $rand,
    ];

    \Dropdown::showFromArray(
        'show_condition',
        Condition::getEnumShowCondition($type),
        $options
    );

    Ajax::updateItemOnSelectEvent(
        "dropdown_show_condition$rand",
        "show_value_to_check_$rand",
        PLUGIN_METADEMANDS_WEBDIR . "/ajax/show_check_value.php",
        [
            'show_condition' => '__VALUE__',
            'fields_id' => $fields_id,
            'rand' => $rand
        ]
    );
}
