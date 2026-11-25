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
use GlpiPlugin\Metademands\Menu;
use GlpiPlugin\Metademands\Ticket_Field;
use GlpiPlugin\Metademands\TicketField;
use GlpiPlugin\Metademands\Metademand;

if (empty($_GET["id"])) {
    $_GET["id"] = "";
}

$ticketField = new TicketField();

if (isset($_POST["add"])) {
    // Creation right, resolved against the parent metademand carried by the input.
    $ticketField->check(-1, CREATE, $_POST);
    // The value widget names its input after the field ('field'), absent while none is chosen
    $_POST['value'] = isset($_POST['field']) && is_string($_POST['field']) ? ($_POST[$_POST['field']] ?? '') : '';
    $_POST['id'] = $ticketField->add($_POST);

    Html::back();
} elseif (isset($_POST["update"])) {
    // As the add: the value widget is named after the field, absent while none is chosen
    $_POST['value'] = isset($_POST['field']) && is_string($_POST['field']) ? ($_POST[$_POST['field']] ?? '') : '';
    // With -1 the requested right was never evaluated; bind the control to the posted row.
    $_POST['id'] = Metademand::getPostedId($_POST['id'] ?? null);
    $ticketField->check($_POST['id'], UPDATE);
    $ticketField->update($_POST);

    Html::back();
} elseif (isset($_POST["purge"])) {
    // delete($input, 1) is a purge, bound to the posted row.
    $_POST['id'] = Metademand::getPostedId($_POST['id'] ?? null);
    $ticketField->check($_POST['id'], PURGE);
    $ticketField->delete($_POST, 1);
    $ticketField->redirectToList();
} elseif (isset($_POST['template_sync'])) {
    // The fields of that very metademand are rewritten: its own update right, its entity included.
    // A missing id is denied (can() does not evaluate the right of a new item).
    $metademands_id = (int) ($_POST['id'] ?? 0);
    $meta           = new Metademand();
    if ($metademands_id <= 0 || !$meta->can($metademands_id, UPDATE)) {
        throw new AccessDeniedHttpException();
    }
    // With the template chosen in the tab, the one of the metademand by default; only a template
    // the picker offered (TicketField::isOfferedTemplate())
    $templates_id = (int) ($_POST['_templates_id'] ?? 0);
    if ($templates_id > 0 && !TicketField::isOfferedTemplate($meta, $templates_id)) {
        Session::addMessageAfterRedirect(__('This template cannot be used for this metademand', 'metademands'), false, ERROR);
        Html::back();
    }
    TicketField::updateMandatoryTicketFields(['id' => $metademands_id] + $_POST, $templates_id);
    Html::back();
} else {
    $ticketField->checkGlobal(READ);
    Html::header(Ticket_Field::getTypeName(2), '', "helpdesk", Menu::class);
    $ticketField->display(['id' => $_GET["id"]]);
    Html::footer();
}
