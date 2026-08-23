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

use GlpiPlugin\Metademands\MetademandValidation;

// Require the dedicated business right: this endpoint both discloses (GET) and
// performs (POST) the validation of a metademand. It is gated for the button in
// MetademandValidation::showActionsForm() but must also be enforced server-side.
Session::checkRight('plugin_metademands_validatemeta', READ);

$metavalidation = new MetademandValidation();

// The target ticket id is user-controlled in both branches. Validate it and
// enforce entity access + ticket visibility before loading/validating anything,
// otherwise a validator could act on a ticket from another entity (IDOR).
$tickets_id = (int) ($_REQUEST['tickets_id'] ?? 0);
if ($tickets_id <= 0) {
    throw new \Glpi\Exception\Http\BadRequestHttpException();
}

$ticket = new Ticket();
if (!$ticket->can($tickets_id, READ)) {
    throw new \Glpi\Exception\Http\AccessDeniedHttpException();
}

Html::popHeader(Ticket::getTypeName(Session::getPluralNumber()));

if (isset($_POST['action'])) {
    $metavalidation->validateMeta($_REQUEST);

} else {

    $params['tickets_id'] = $tickets_id;
    $metavalidation->viewValidation($params);
}

Html::popFooter();
