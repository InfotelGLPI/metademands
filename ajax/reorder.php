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

use Glpi\Exception\Http\AccessDeniedHttpException;
use GlpiPlugin\Metademands\Field;
use GlpiPlugin\Metademands\FieldCustomvalue;
use GlpiPlugin\Metademands\Freetablefield;
use GlpiPlugin\Metademands\Metademand;

header("Content-Type: text/html; charset=UTF-8");
Html::header_nocache();

Session::checkRight("plugin_metademands", UPDATE);

// reorder() is handed $_POST directly, so CommonDBChild::can() never runs and the only
// remaining check is a right bit that is global rather than per entity. Derive the parent
// meta-demand from the field read in database -- not from a posted identifier -- and
// require access to its entity before renumbering anything.
$parent_field = new Field();
if (!$parent_field->getFromDB((int) ($_POST['field_id'] ?? 0))) {
    throw new AccessDeniedHttpException();
}
Metademand::assertCanAccessEntity($parent_field->fields['plugin_metademands_metademands_id']);

if ($_POST['type'] == "freetable") {
    $field = new Freetablefield();
} else {
    $field = new FieldCustomvalue();
}

$field->reorder($_POST);
