<?php

/**
 * -------------------------------------------------------------------------
 * Metademands plugin for GLPI
 * -------------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of Metademands.
 *
 * Metademands is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Metademands is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Metademands. If not, see <http://www.gnu.org/licenses/>.
 * -------------------------------------------------------------------------
 * @copyright Copyright (C) 2013-2022 by Metademands plugin team.
 * @copyright 2015-2022 Teclib' and contributors.
 * @copyright 2003-2014 by the INDEPNET Development Team.
 * @licence   https://www.gnu.org/licenses/gpl-3.0.html
 * @license   GPLv2 https://www.gnu.org/licenses/gpl-2.0.html
 * @link      https://github.com/pluginsGLPI/Metademands
 * -------------------------------------------------------------------------
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
