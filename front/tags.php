<?php

/*
 -------------------------------------------------------------------------
 metademands plugin for GLPI
 Copyright (C) 2018-2026 by the metademands Development Team.

 https://github.com/InfotelGLPI/metademands
 -------------------------------------------------------------------------

 LICENSE

 This file is part of metademands.

 metademands is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 3 of the License, or
 (at your option) any later version.

 metademands is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with metademands. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 */

use Glpi\Exception\Http\AccessDeniedHttpException;
use Glpi\Exception\Http\BadRequestHttpException;
use GlpiPlugin\Metademands\Group;
use GlpiPlugin\Metademands\Metademand;
use GlpiPlugin\Metademands\TicketField;

if (!isset($_GET["metademands_id"])) {
    throw new BadRequestHttpException();
}

Session::checkCentralAccess();

// checkCentralAccess() only proves access to the central interface: without a
// business-right + entity check any technician could enumerate other entities'
// form structures (field names, exposed user fields) by iterating
// metademands_id (IDOR). Enforce the plugin READ right and the entity scope on
// the targeted meta-demand, mirroring the plugin's other rendering endpoints
// (previewMetademand.php / createmetademands.php).
$meta = new Metademand();
if (!$meta->getFromDB((int) $_GET["metademands_id"])
    || !Session::haveAccessToEntity($meta->fields['entities_id'], $meta->fields['is_recursive'])
    || !($meta->canView() || Group::isUserHaveRight($meta->getID()))) {
    throw new AccessDeniedHttpException();
}

Html::popHeader(__('List of available tags'), '');

TicketField::showAvailableTags($_GET["metademands_id"]);

Html::popFooter();
