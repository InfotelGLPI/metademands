<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2021 Teclib' and contributors.
 *
 * http://glpi-project.org
 *
 * based on GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2003-2014 by the INDEPNET Development Team.
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * GLPI is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * GLPI is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 * ---------------------------------------------------------------------
 */

use Glpi\Event;
use Glpi\Exception\Http\BadRequestHttpException;
use GlpiPlugin\Metademands\Interticketfollowup;

Session::checkLoginUser();

$fup = new Interticketfollowup();

if (!isset($_POST['itemtype']) || !class_exists($_POST['itemtype'])) {
    throw new BadRequestHttpException();
}
$track = new $_POST['itemtype'];


if (isset($_POST["add"])) {
   $fup->check(-1, CREATE, $_POST);
   $fup->add($_POST);

   Event::log($fup->getField('tickets_id'), strtolower($_POST['itemtype']), 4, "tracking",
      //TRANS: %s is the user login
              sprintf(__('%s adds a followup'), $_SESSION["glpiname"]));
   Html::redirect($track->getFormURLWithID($fup->getField('tickets_id')));

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
