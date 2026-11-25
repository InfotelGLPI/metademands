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
use GlpiPlugin\Metademands\Metademand;

// This controller had no page guard at all: the only control was carried by check(-1, CREATE),
// which boils down to the global right bit anyway.
Session::checkRight('plugin_metademands_followup', CREATE);

$fup = new Interticketfollowup();

// class_exists() accepts any autoloadable class of the core, of another plugin or of vendor/, and
// the posted itemtype decides which table is read and written downstream: control the value, not
// its existence. Only the ITIL objects the timeline of which can carry this form are legitimate.
// The followups link the tickets of a meta-demand: a Change or a Problem was checked under its id
// while the followup was written on the Ticket of the same id
if (!isset($_POST['itemtype']) || $_POST['itemtype'] !== \Ticket::class) {
    throw new BadRequestHttpException();
}
$track = new \Ticket();

// The followup is attached to this object and notifies its actors, so the posted identifier has to
// be confronted with the session - neither the entity nor the actor list was checked before. The
// id checked is the id written.
$_POST['tickets_id'] = Metademand::getPostedId($_POST['tickets_id'] ?? null);
if (!$track->can($_POST['tickets_id'], READ)) {
    throw new AccessDeniedHttpException();
}

if (isset($_POST["add"])) {
    if (isset($_POST["targets_id"])) {
        // Replay the criteria of the dropdown builder at the sink: only what getTargets() offers is
        // a legitimate target -- the other tickets of the same metademand still open, or 0 ("all
        // tickets"), which it only offers when the ticket belongs to a metademand.
        if (!array_key_exists(
            (int) $_POST["targets_id"],
            Interticketfollowup::getTargets((int) $_POST["tickets_id"]),
        )) {
            throw new AccessDeniedHttpException();
        }

        // "All tickets" is a single followup with targets_id = 0, not one copy per ticket: the
        // timelines and the notifications spread it over the whole metademand
        // (Interticketfollowup::getRelatedTickets()), so the ticket it is written from no longer
        // shows it once per son.
        // The inputs of the form only: an "id" made add() clone that followup (Clonable, no right
        // checked on it) onto this ticket, whatever its entity, and the author, the date and the
        // notifications were taken from the request
        $input = [
            'itemtype'   => \Ticket::class,
            'tickets_id' => $_POST['tickets_id'],
            'targets_id' => (int) $_POST['targets_id'],
            'content'    => $_POST['content'] ?? '',
        ] + array_intersect_key($_POST, array_flip(['_content', '_prefix_content', '_tag_content']));
        $fup->check(-1, CREATE, $input);
        $fup->add($input);

        Event::log(
            $fup->getField('tickets_id'),
            strtolower($_POST['itemtype']),
            4,
            "tracking",
            //TRANS: %s is the user login
            sprintf(__('%s adds a followup'), $_SESSION["glpiname"]),
        );
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
