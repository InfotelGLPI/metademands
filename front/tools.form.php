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
use GlpiPlugin\Metademands\FieldOption;
use GlpiPlugin\Metademands\Ticket_Metademand;

Session::checkRight("plugin_metademands", UPDATE);

if (empty($_GET["id"])) {
    $_GET["id"] = "";
}

if (isset($_POST["purge_emptyoptions"])) {
    $itil = $_POST["id"];
    $field = new FieldOption();
    // Same defect as the controllers listed by the audit: check(-1, ...) never evaluated the
    // requested right, and delete($input, 1) is a purge.
    $field->check((int) $_POST["id"], PURGE);
    $field->delete($_POST, 1);
    Session::addMessageAfterRedirect(__('Empty option has been deleted', 'metademands'));
    Html::back();
} elseif (isset($_POST["change_global_status"])) {

    $ticket_metademand = new Ticket_Metademand();
    if ($notclosedmetademands = $ticket_metademand->find(['NOT' => ['status' => Ticket_Metademand::CLOSED]])) {
        foreach ($notclosedmetademands as $notclosedmetademand) {
            $ticket = new Ticket();
            if ($ticket->getFromDB($notclosedmetademand['parent_tickets_id'])) {
                if ($ticket->fields['status'] != Ticket::CLOSED) {
                    Ticket_Metademand::changeMetademandGlobalStatus($ticket);
                }
            }
        }
    }
    Session::addMessageAfterRedirect(__('Metademands statuses updated', 'metademands'));
    Html::back();
    // The "fix_emptycustomvalues" action used to sit here. It rewrote a `custom_values` column
    // that no longer exists on glpi_plugin_metademands_fieldparameters (renamed to `custom`, and
    // the options themselves moved to their own table), so it could only ever have produced a
    // failing UPDATE. The Tools diagnostic that triggered it has been removed along with it.
} else {
    throw new AccessDeniedHttpException();
}
