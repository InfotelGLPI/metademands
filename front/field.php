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

// Same right as the other field administration screens (ajax/reorderfields.php,
// ajax/show_conditions.php): this controller writes the search criteria of the field
// list into the session, it is not reachable from the helpdesk interface.
Session::checkRight('plugin_metademands', UPDATE);

if (isset($_POST["search"])) {
    $metademands_id = (int) ($_POST["plugin_metademands_metademands_id"] ?? 0);

    // Only keep the three criteria Field::listFields() actually reads back, instead of
    // persisting the whole $_POST under a client-controlled key.
    $_SESSION['plugin_metademands_searchresults'][$metademands_id] = [
        'block' => $_POST['block'] ?? 0,
        'type'  => $_POST['type'] ?? 0,
        'item'  => $_POST['item'] ?? 0,
    ];
}

Html::back();
