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

use GlpiPlugin\Metademands\Metademand;

$AJAX_INCLUDE = 1;

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$type   = $_POST['type'] ?? $_GET['type'] ?? '';

// The search box markup is HTML; the list of the forms is JSON, never served as HTML: it
// carries the names and comments of the forms and the route answers a plain GET
header(
    $action === 'getHtml'
        ? "Content-Type: text/html; charset=UTF-8"
        : "Content-Type: application/json; charset=UTF-8",
);
Html::header_nocache();

// Gate on the same rights as the wizard entry point (see front/wizard.form.php), whose
// catalogue this box searches; Metademand::fuzzySearch() then replays the per-group
// visibility rule of the listing. It replaces a Session::checkLoginUser() that was dead
// code on a routed GLPI 11 entry point, authentication being enforced by the framework.
Session::checkSeveralRightsOr([
    'plugin_metademands' => READ,
    'plugin_metademands_createmeta' => READ,
]);

echo Metademand::fuzzySearch($action, $type);
