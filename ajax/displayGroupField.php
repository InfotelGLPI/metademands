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
use Glpi\Exception\Http\AccessDeniedHttpException;
use GlpiPlugin\Metademands\MetademandValidation;

header("Content-Type: text/html; charset=UTF-8");
Html::header_nocache();

Session::checkRight("plugin_metademands", UPDATE);

// The "create sub-tickets" branch has nothing to add to the row: only the "create
// tasks" one asks for the group the parent ticket is handed over to.
if (($_POST['create_subticket'] ?? null) !== '0') {
    return;
}

// can() applies both the right on Ticket and checkEntity(): the right checked
// above is the plugin one, which says nothing about this ticket. The assigned group
// read below used to be disclosed for any posted identifier, entity boundary included.
$ticket = new \Ticket();
if (!$ticket->can((int) ($_POST['tickets_id'] ?? 0), READ)) {
    throw new AccessDeniedHttpException();
}

$group = 0;
foreach ($ticket->getGroups(CommonITILActor::ASSIGN) as $d) {
    $group = $d['groups_id'];
}

ob_start();
// Criteria shared with MetademandValidation::validateMeta(), which replays them on
// the value that comes back: the list and the check must not be able to drift apart.
\Group::dropdown([
    'condition' => MetademandValidation::getAssignableGroupCriteria(),
    'name' => 'group_to_assign',
    'value' => $group,
]);
$group_dropdown_html = ob_get_clean();

TemplateRenderer::getInstance()->display('@metademands/ajax/group_to_assign.html.twig', [
    'group_dropdown_html' => $group_dropdown_html,
]);
