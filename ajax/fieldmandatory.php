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

use GlpiPlugin\Metademands\Field;
use GlpiPlugin\Metademands\FieldParameter;
use GlpiPlugin\Metademands\Metademand;

// "Mandatory / optional" switch of the "Wizard creation" tab (public/scripts/field_list.js):
// sets the is_mandatory parameter of a field, as its form does.
header("Content-Type: application/json; charset=UTF-8");
Html::header_nocache();

Session::checkRight(Metademand::$rightname, UPDATE);

$field = new Field();
// Bound to the field: checks the right and the entity of its metademand
$field->check((int) ($_POST['id'] ?? 0), UPDATE);

$parameter = new FieldParameter();
if (
    in_array($field->fields['type'], FieldParameter::NOT_MANDATORY_TYPES, true)
    || !$parameter->getFromDBByCrit(['plugin_metademands_fields_id' => $field->getID()])
) {
    echo json_encode(['success' => false]);
    return;
}
$parameter->check($parameter->getID(), UPDATE);

$is_mandatory = (int) (($_POST['is_mandatory'] ?? 0) == 1);
echo json_encode([
    'success'      => $parameter->update(['id' => $parameter->getID(), 'is_mandatory' => $is_mandatory]),
    'is_mandatory' => $is_mandatory,
]);
