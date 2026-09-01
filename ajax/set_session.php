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

use GlpiPlugin\Metademands\MetademandTask;

header("Content-Type: application/json; charset=UTF-8");

Html::header_nocache();

// This endpoint mutates state (a display flag of the caller's own session), so it must
// not be reachable through GET: reading $_POST restores the core CSRF coverage, which
// only applies to non-GET requests. All callers are jQuery $.ajax with type: 'POST',
// to which the core adds the X-Glpi-Csrf-Token header (js/common.js ajaxSend).
// Gate on the same rights as the wizard entry point (see setup.php).
Session::checkSeveralRightsOr([
    'plugin_metademands' => READ,
    'plugin_metademands_createmeta' => READ,
]);

$KO = false;

$tasks_id = (int) ($_POST["tasks_id"] ?? 0);
if ($tasks_id > 0) {
    $used = MetademandTask::setUsedTask($tasks_id, (int) ($_POST["used"] ?? 0));

    if ($used == 0) {
        $KO = true;
    }
} else {
    $KO = true;
}
if ($KO === false) {
    echo 0;
} else {
    echo $KO;
}
