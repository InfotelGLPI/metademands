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
use GlpiPlugin\Metademands\Wizard;

header("Content-Type: text/html; charset=UTF-8");
Html::header_nocache();

Session::checkRight("plugin_metademands", UPDATE);

$metademands_id = (int) ($_REQUEST['metademands_id'] ?? 0);

// plugin_metademands UPDATE is a global right bit, not scoped per entity, so
// revalidate the entity perimeter on the loaded meta-demand before rendering
// its full structure. Prevents cross-entity disclosure via id enumeration.
$metademand = new Metademand();
if (!$metademand->getFromDB($metademands_id)
    || !Session::haveAccessToEntity(
        $metademand->fields['entities_id'],
        $metademand->fields['is_recursive'],
    )) {
    throw new AccessDeniedHttpException();
}

$step = Metademand::STEP_SHOW;
$current_ticket = 0;
$meta_validated = 0;
$preview = 1;
$options = [];
$seeform = 0;

Wizard::showMetademands(
    $metademands_id,
    $step,
    $current_ticket,
    $meta_validated,
    $preview,
    $options,
    $seeform,
    $_REQUEST['block'],
);
