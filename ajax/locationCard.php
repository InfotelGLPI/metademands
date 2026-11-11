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

use GlpiPlugin\Metademands\Fields\Dropdown;

$AJAX_INCLUDE = 1;

if (strpos($_SERVER['PHP_SELF'], "locationCard.php")) {
    header("Content-Type: text/html; charset=UTF-8");
    Html::header_nocache();
}

// Page guard. Gate on the same rights as the wizard entry point
// (front/wizard.form.php), the only way in to this route, as the u*Update.php
// family does. The field and location checks below are the per-request boundary.
Session::checkSeveralRightsOr([
    'plugin_metademands' => READ,
    'plugin_metademands_createmeta' => READ,
    'plugin_metademands_fillform' => READ,
]);

// Identity card of the location chosen in a dropdown / Location field of the wizard
// (public/scripts/wizard_form.js). Nothing is printed unless that field shows a card,
// in a meta-demand the caller may fill in, and its list offers the location: see
// Dropdown::getOfferedLocation(). The Location READ right is not required, the users
// of the simplified interface rarely have it.
echo Dropdown::renderLocationCard(
    Dropdown::getOfferedLocation(
        (int) ($_GET['fields_id'] ?? 0),
        (int) ($_GET['locations_id'] ?? 0),
    ),
);
