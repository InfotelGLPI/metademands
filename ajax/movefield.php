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
use GlpiPlugin\Metademands\Metademand;

// Drag and drop of the "Wizard creation" tab (public/scripts/field_list.js): moves a field
// right after another one, or at the top of a block, in the same block or another one.
header("Content-Type: application/json; charset=UTF-8");
Html::header_nocache();

Session::checkRight(Metademand::$rightname, UPDATE);

$field = new Field();
// Bound to the moved row: checks the right and the entity of its metademand
$field->check((int) ($_POST['id'] ?? 0), UPDATE);

echo json_encode([
    'success' => $field->moveTo(
        (int) ($_POST['rank'] ?? 0),
        (int) ($_POST['previous_fields_id'] ?? 0),
    ),
]);
