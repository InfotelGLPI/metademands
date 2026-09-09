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
use GlpiPlugin\Metademands\FieldCustomvalue;

header("Content-Type: text/html; charset=UTF-8");

Html::header_nocache();

// This endpoint had no control at all and listed the custom values of any field of the instance.
// Field is a CommonDBChild, so check() resolves the parent metademand and its entity boundary.
Session::checkRight('plugin_metademands', READ);

$KO          = false;
$fields = new Field();
$fields->check((int) ($_POST['checkbox_id_val'] ?? 0), READ);

$arrayValues = [];
$field_custom = new FieldCustomvalue();
if ($customs = $field_custom->find(["plugin_metademands_fields_id" => $fields->getID()], "rank")) {
    if (count($customs) > 0) {
        foreach ($customs as $custom) {
            $arrayValues[$custom['id']] = $custom['name'];
        }
    }
}

\Dropdown::showFromArray('checkbox_value', $arrayValues, ['display_emptychoice' => false]);
