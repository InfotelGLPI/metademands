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

header("Content-Type: text/html; charset=UTF-8");
Html::header_nocache();

use Glpi\Exception\Http\AccessDeniedHttpException;
use GlpiPlugin\Metademands\Field;
use GlpiPlugin\Metademands\Metademand;
use GlpiPlugin\Metademands\TicketField;
use GlpiPlugin\Metademands\Group;

// The only action handled here is a deletion, so the page guard must express that and
// not the modification bit: plugin_metademands offers both DELETE (trashbin, the items
// that carry is_deleted) and PURGE (definitive), since Metademand::maybeDeleted() is true.
Session::checkRightsOr("plugin_metademands", [DELETE, PURGE]);

// Do not forward the client-controllable PHP_SELF/PATH_INFO to Html::header(); the
// $url param is unused in GLPI 11, pass an empty string (as elsewhere in the plugin).
Html::header(Metademand::getTypeName(2), '', "plugins", Metademand::class);

if (isset($_POST["action"]) && isset($_POST["item"]) && count($_POST["item"]) && isset($_POST["itemtype"])) {

    switch ($_POST["itemtype"]) {
        case Field::class:
            $field = new Field();
            switch ($_POST["action"]) {
                case "delete":
                    foreach ($_POST["item"] as $key => $val) {
                        if ($val == 1) {
                            // Field carries is_deleted, so delete() sends it to the trashbin:
                            // the matching bit is DELETE, not the modification bit.
                            if ($field->can($key, DELETE)) {
                                $field->delete(['id' => $key]);
                            }
                        }
                    }
                    Html::back();
                    break;
            }
            break;
        case TicketField::class:
            $ticketField = new TicketField();
            switch ($_POST["action"]) {
                case "delete":
                    foreach ($_POST["item"] as $key => $val) {
                        if ($val == 1) {
                            // No is_deleted on this table: delete() removes the row for good,
                            // so the operation requires PURGE.
                            if ($ticketField->can($key, PURGE)) {
                                $ticketField->delete(['id' => $key]);
                            }
                        }
                    }
                    Html::back();
                    break;
            }
            break;
        case Group::class:
            // Posted ids are plugin group-right rows (glpi_plugin_metademands_groups),
            // not core groups: instantiate the plugin CommonDBChild, whose can()/delete()
            // gate on plugin_metademands UPDATE + the parent metademand's entity.
            $group = new Group();
            switch ($_POST["action"]) {
                case "delete":
                    foreach ($_POST["item"] as $key => $val) {
                        if ($val == 1) {
                            // No is_deleted either: definitive removal, hence PURGE.
                            if ($group->can($key, PURGE)) {
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
