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

use GlpiPlugin\Metademands\Menu;
use GlpiPlugin\Metademands\Ticket_Field;
use GlpiPlugin\Metademands\TicketField;

if (empty($_GET["id"])) {
    $_GET["id"] = "";
}

$ticketField = new TicketField();

if (isset($_POST["add"])) {
    // Creation right, resolved against the parent metademand carried by the input.
    $ticketField->check(-1, CREATE, $_POST);
    $_POST['value'] = $_POST[$_POST['field']];
    $_POST['id'] = $ticketField->add($_POST);

    Html::back();
} elseif (isset($_POST["update"])) {
    $_POST['value'] = $_POST[$_POST['field']];
    // With -1 the requested right was never evaluated; bind the control to the posted row.
    $ticketField->check((int) $_POST['id'], UPDATE);
    $ticketField->update($_POST);

    Html::back();
} elseif (isset($_POST["purge"])) {
    // delete($input, 1) is a purge, bound to the posted row.
    $ticketField->check((int) $_POST['id'], PURGE);
    $ticketField->delete($_POST, 1);
    $ticketField->redirectToList();
} elseif (isset($_POST['template_sync'])) {
    // Syncing mandatory template fields is a configuration change: require the plugin update right.
    Session::checkRight('plugin_metademands', UPDATE);
    TicketField::updateMandatoryTicketFields($_POST);
    Html::back();
} else {
    $ticketField->checkGlobal(READ);
    Html::header(Ticket_Field::getTypeName(2), '', "helpdesk", Menu::class);
    $ticketField->display(['id' => $_GET["id"]]);
    Html::footer();
}
