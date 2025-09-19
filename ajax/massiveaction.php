<?php
/*
 * @version $Id: HEADER 15930 2011-10-30 15:47:55Z tsmr $
 -------------------------------------------------------------------------
 Metademands plugin for GLPI
 Copyright (C) 2018-2022 by the Metademands Development Team.

 https://github.com/InfotelGLPI/metademands
 -------------------------------------------------------------------------

 LICENSE

 This file is part of Metademands.

 Metademands is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 Metademands is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with Metademands. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 */

header("Content-Type: text/html; charset=UTF-8");
Html::header_nocache();

Session::checkLoginUser();

use Glpi\Exception\Http\AccessDeniedHttpException;
use GlpiPlugin\Metademands\Metademand;
use GlpiPlugin\Metademands\TicketField;
use GlpiPlugin\Metademands\Group;

Html::header(Metademand::getTypeName(2), $_SERVER['PHP_SELF'], "plugins", Metademand::class);

if (isset($_POST["action"]) && isset($_POST["item"]) && count($_POST["item"]) && isset($_POST["itemtype"])) {

   switch ($_POST["itemtype"]) {
       case Field::class :
         $field = new Field();
         switch ($_POST["action"]) {
            case "delete":
               foreach ($_POST["item"] as $key => $val) {
                  if ($val == 1) {
                     if ($field->can($key, UPDATE)) {
                        $field->delete(['id' => $key]);
                     }
                  }
               }
               Html::back();
               break;
         }
      break;
      case TicketField::class :
         $ticketField = new TicketField();
         switch ($_POST["action"]) {
            case "delete":
               foreach ($_POST["item"] as $key => $val) {
                  if ($val == 1) {
                     if ($ticketField->can($key, UPDATE)) {
                        $ticketField->delete(['id' => $key]);
                     }
                  }
               }
               Html::back();
               break;
         }
      break;
      case Group::class :
         $group = new \Group();
         switch ($_POST["action"]) {
            case "delete":
               foreach ($_POST["item"] as $key => $val) {
                  if ($val == 1) {
                     if ($group->can($key, UPDATE)) {
                        $group->delete(['id' => $key]);
                     }
                  }
               }
               Html::back();
               break;
         }
      break;
   }
} else {

    throw new AccessDeniedHttpException();
}

Html::footer();
