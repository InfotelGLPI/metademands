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

use Glpi\Application\View\TemplateRenderer;
use Glpi\Exception\Http\BadRequestHttpException;
use Glpi\Exception\Http\NotFoundHttpException;
use GlpiPlugin\Metademands\Metademand;
use GlpiPlugin\Metademands\TicketField;

header("Content-Type: text/html; charset=UTF-8");
Html::header_nocache();

global $CFG_GLPI, $DB;

// The widget of the value of a field of the parent ticket (TicketField::showForm()): the screen
// of a designer, on the types a meta-demand creates. Any account able to update one itemtype
// (a self-service user creating tickets) had the name of any user or object displayed by id.
Session::checkCentralAccess();
Session::checkRightsOr(Metademand::$rightname, [CREATE, UPDATE]);

$dbu = new DbUtils();
if (
    !isset($_POST["itemtype"])
    || !in_array($_POST["itemtype"], Metademand::getTypes(true), true)
    || !($item = $dbu->getItemForItemtype($_POST['itemtype']))
) {
    throw new NotFoundHttpException();
}

if (in_array($_POST["itemtype"], $CFG_GLPI["infocom_types"])) {
    Session::checkSeveralRightsOr([$_POST["itemtype"] => UPDATE,
        "infocom"          => UPDATE]);
} else {
    $item->checkGlobal(UPDATE);
}

if (isset($_POST["itemtype"]) && isset($_POST["id_field"]) && $_POST["id_field"]) {
    $search = Search::getOptions($_POST["itemtype"]);
    if (!isset($search[$_POST["id_field"]])) {
        throw new NotFoundHttpException();
    }
    $search = $search[$_POST["id_field"]];

    // Only the itemtypes that can actually be linked to a ticket are offered for the linked
    // item: the value comes from the browser and would otherwise let any dropdown be listed.
    // Checked here so that the widget, printed by the template, never has to throw.
    if (
        $search["table"] == $dbu->getTableForItemType($_POST["itemtype"])
        && $search["table"] . "." . $search["linkfield"] === "glpi_tickets.items_id"
        && !empty($_POST['itemtype_used'])
    ) {
        if (!in_array($_POST['itemtype_used'], $CFG_GLPI['ticket_types'], true)) {
            throw new BadRequestHttpException();
        }
        if (!($linked_item = $dbu->getItemForItemtype($_POST['itemtype_used']))) {
            throw new BadRequestHttpException();
        }
        $linked_item->checkGlobal(READ);
    }

    // The preselected value of a field pointing to another table is displayed by its name: an
    // object of the active entities only
    $value_table = (string) ($search['table'] ?? '');
    if (
        $value_table !== ''
        && $value_table !== $dbu->getTableForItemType($_POST['itemtype'])
        && isset($_POST['value'])
        && is_scalar($_POST['value'])
        && ctype_digit((string) $_POST['value'])
        && (int) $_POST['value'] > 0
    ) {
        $value_id = (int) $_POST['value'];
        if ($value_table === User::getTable()) {
            $visible = array_intersect(
                Profile_User::getUserEntities($value_id, true),
                (array) ($_SESSION['glpiactiveentities'] ?? []),
            ) !== [];
        } elseif ($DB->tableExists($value_table) && $DB->fieldExists($value_table, 'entities_id')) {
            $visible = countElementsInTable(
                $value_table,
                ['id' => $value_id] + getEntitiesRestrictCriteria($value_table, '', '', true),
            ) > 0;
        } else {
            $visible = true;
        }
        if (!$visible) {
            $_POST['value'] = 0;
        }
    }

    // $FIELDNAME_PRINTED was never set: no branch of the
    // switch printed the field name, so the hidden input is always emitted.
    TemplateRenderer::getInstance()->display('@metademands/ajax/massiveaction_field.html.twig', [
        'use_table'   => TicketField::massiveActionFieldUsesTable($_POST['itemtype'], $search, $_POST),
        'itemtype'    => $_POST['itemtype'],
        'search'      => $search,
        'input'       => $_POST,
        'field_name'  => empty($search["linkfield"]) ? $search["field"] : $search["linkfield"],
    ]);
}
