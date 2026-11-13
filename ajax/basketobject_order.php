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

use GlpiPlugin\Metademands\Basketobject;

/**
 * Display order of the references of a type, posted by public/scripts/basket_order.js
 * from the options of a basket field: the references in their new order.
 */

header("Content-Type: application/json; charset=UTF-8");
Html::header_nocache();

Session::checkRight(Basketobject::$rightname, UPDATE);

$ids = $_POST['basketobjects_id'] ?? [];
if (!is_array($ids)) {
    $ids = [];
}

// Basketobject::reorder() checks the right on each reference and its type
echo json_encode([
    'success' => Basketobject::reorder((int) ($_POST['types_id'] ?? 0), array_map('intval', $ids)),
]);
