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

use GlpiPlugin\Metademands\Condition;
use GlpiPlugin\Metademands\Field;

header("Content-Type: text/html; charset=UTF-8");
Html::header_nocache();
Session::checkRight("plugin_metademands", UPDATE);

$fields_id = (int) ($_POST['fields_id'] ?? 0);
$rand = (int) ($_POST['rand'] ?? 0);

// The field dropdowns that drive this endpoint carry an empty choice: posting it
// is a legitimate no-op, not an access error.
if ($fields_id <= 0) {
    return;
}

$field = new Field();
// Entity boundary of the parent meta-demand, missing here while the sibling
// endpoints apply it: the condition tree exposes which fields drive this one and on
// which values, that is the business logic of someone else's form.
$field->check($fields_id, READ);

$type = $field->fields['type'];

\Dropdown::showFromArray(
    'show_condition',
    Condition::getEnumShowCondition($type),
    [
        'display_emptychoice' => false,
        'rand' => $rand,
    ],
);

Ajax::updateItemOnSelectEvent(
    "dropdown_show_condition$rand",
    "show_value_to_check_$rand",
    PLUGIN_METADEMANDS_WEBDIR . "/ajax/show_check_value.php",
    [
        'show_condition' => '__VALUE__',
        'fields_id' => $fields_id,
        'rand' => $rand,
    ],
);
