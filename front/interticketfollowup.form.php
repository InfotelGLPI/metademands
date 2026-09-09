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

use Glpi\Event;
use Glpi\Exception\Http\AccessDeniedHttpException;
use Glpi\Exception\Http\BadRequestHttpException;
use GlpiPlugin\Metademands\Interticketfollowup;
use GlpiPlugin\Metademands\Ticket_Metademand;
use GlpiPlugin\Metademands\Ticket;

// This controller had no page guard at all: the only control was carried by check(-1, CREATE),
// which boils down to the global right bit anyway.
Session::checkRight('plugin_metademands_followup', CREATE);

$fup = new Interticketfollowup();

// class_exists() accepts any autoloadable class of the core, of another plugin or of vendor/, and
// the posted itemtype decides which table is read and written downstream: control the value, not
// its existence. Only the ITIL objects the timeline of which can carry this form are legitimate.
if (!isset($_POST['itemtype'])
    || !in_array($_POST['itemtype'], [\Ticket::class, \Change::class, \Problem::class], true)) {
    throw new BadRequestHttpException();
}
$track = new $_POST['itemtype']();

// The followup is attached to this object and notifies its actors, so the posted identifier has to
// be confronted with the session - neither the entity nor the actor list was checked before.
if (!$track->can((int) ($_POST["tickets_id"] ?? 0), READ)) {
    throw new AccessDeniedHttpException();
}

if (isset($_POST["add"])) {
    if (isset($_POST["targets_id"])) {
        if ($_POST["targets_id"] == 0) {

            $tickets_found = [];
            $ticket_metademand = new Ticket_Metademand();
            $ticket_metademand_datas = $ticket_metademand->find(['tickets_id' => $_POST["tickets_id"]]);

            // If ticket is Parent : Check if all sons ticket are closed
            if (count($ticket_metademand_datas)) {
                $ticket_metademand_datas = reset($ticket_metademand_datas);
                $tickets_found = Ticket::getSonTickets(
                    $_POST["tickets_id"],
                    $ticket_metademand_datas['plugin_metademands_metademands_id'],
                    [],
                    true,
                    true,
                );
            }

            foreach ($tickets_found as $ticket) {
                // getSonTickets() is scoped by the metademand, not by the perimeter of the session:
                // a linked ticket may well sit in an entity the author cannot reach, so the control
                // is replayed here rather than only on the root identifier. A dedicated instance is
                // used so that $track keeps pointing at the object of the redirection below.
                $son = new $_POST['itemtype']();
                if (!$son->can((int) $ticket["tickets_id"], READ)) {
                    continue;
                }

                $_POST["targets_id"] = $ticket["tickets_id"];
                $fup->check(-1, CREATE, $_POST);
                $fup->add($_POST);

                Event::log(
                    $fup->getField('tickets_id'),
                    strtolower($_POST['itemtype']),
                    4,
                    "tracking",
                    //TRANS: %s is the user login
                    sprintf(__('%s adds a followup'), $_SESSION["glpiname"]),
                );
            }
        } else {
            // Replay the criteria of the dropdown builder at the sink: only the tickets getTargets()
            // offers - the sons of the same metademand, still open - are legitimate targets.
            if (!array_key_exists(
                (int) $_POST["targets_id"],
                Interticketfollowup::getTargets((int) $_POST["tickets_id"]),
            )) {
                throw new AccessDeniedHttpException();
            }

            $fup->check(-1, CREATE, $_POST);
            $fup->add($_POST);

            Event::log(
                $fup->getField('tickets_id'),
                strtolower($_POST['itemtype']),
                4,
                "tracking",
                //TRANS: %s is the user login
                sprintf(__('%s adds a followup'), $_SESSION["glpiname"]),
            );
        }
    }
    Html::redirect($track->getFormURLWithID($_POST["tickets_id"]));
}
//else if (isset($_POST['add_close'])
//           ||isset($_POST['add_reopen'])) {
//   if ($track->getFromDB($_POST['items_id']) && (method_exists($track, 'canApprove') && $track->canApprove())) {
//      $fup->add($_POST);
//
//      Event::log($fup->getField('items_id'), strtolower($_POST['itemtype']), 4, "tracking",
//         //TRANS: %s is the user login
//                 sprintf(__('%s approves or refuses a solution'), $_SESSION["glpiname"]));
//      Html::back();
//   }
//
//} else if (isset($_POST["update"])) {
//   $fup->check($_POST['id'], UPDATE);
//   $fup->update($_POST);
//
//   Event::log($fup->getField('tickets_id'), strtolower($_POST['itemtype']), 4, "tracking",
//      //TRANS: %s is the user login
//              sprintf(__('%s updates a followup'), $_SESSION["glpiname"]));
//   Html::redirect($track->getFormURLWithID($fup->getField('tickets_id')));
//
//} else if (isset($_POST["purge"])) {
//   $fup->check($_POST['id'], PURGE);
//   $fup->delete($_POST, 1);
//
//   Event::log($fup->getField('tickets_id'), strtolower($_POST['itemtype']), 4, "tracking",
//      //TRANS: %s is the user login
//              sprintf(__('%s purges a followup'), $_SESSION["glpiname"]));
//   Html::redirect($track->getFormURLWithID($fup->getField('tickets_id')));
//}

throw new BadRequestHttpException();
