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
use GlpiPlugin\Metademands\Metademand;
use GlpiPlugin\Metademands\Step;

header("Content-Type: application/json; charset=UTF-8");

Html::header_nocache();

// Page guard. Gate on the same rights as the wizard entry point
// (front/wizard.form.php), the only way in to this route, exactly as the
// neighbouring ajax/condition.php does.
Session::checkSeveralRightsOr([
    'plugin_metademands' => READ,
    'plugin_metademands_createmeta' => READ,
    'plugin_metademands_fillform' => READ,
]);

// This endpoint writes into the plugin session sandbox, so the caller must first be
// entitled to the targeted meta-demand — same correlation as ajax/loadform.php.
$metademands    = new Metademand();
$metademands_id = (int) ($_POST['metademands_id'] ?? 0);
if (
    !$metademands->getFromDB($metademands_id)
    || !Session::haveAccessToEntity(
        $metademands->fields['entities_id'],
        $metademands->fields['is_recursive'],
    )
) {
    throw new AccessDeniedHttpException();
}

$return = Step::showStep();
echo json_encode($return);
