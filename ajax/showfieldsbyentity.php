<?php

/*
 -------------------------------------------------------------------------
 metademands plugin for GLPI
 Copyright (C) 2018-2026 by the metademands Development Team.

 https://github.com/InfotelGLPI/metademands
 -------------------------------------------------------------------------

 LICENSE

 This file is part of metademands.

 metademands is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 3 of the License, or
 (at your option) any later version.

 metademands is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with metademands. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 */

header("Content-Type: text/html; charset=UTF-8");
Html::header_nocache();

Session::checkLoginUser();

use Glpi\Exception\Http\AccessDeniedHttpException;

// This endpoint is reachable by simple requesters, and the 'entity' option below overrides
// the session entity restriction in the dropdowns. Re-validate the requested entity against
// the caller's own perimeter to prevent cross-entity user/group enumeration.
$entities_id = isset($_POST['entities_id']) ? (int) $_POST['entities_id'] : -1;
if ($entities_id < 0 || !Session::haveAccessToEntity($entities_id)) {
    throw new AccessDeniedHttpException();
}

switch ($_POST['action']) {
    case 'showcategories':
        $condition = [];
        switch ($_POST['type']) {
            case \Ticket::DEMAND_TYPE:
                $condition = ['is_request' => 1];
                break;

            default: // \Ticket::INCIDENT_TYPE :
                $condition = ['is_incident' => 1];
        }
        $restrict = getEntitiesRestrictCriteria('glpi_itilcategories', '', $entities_id, false);
        $opt = [
            'value' => $_POST['itilcategories_id'],
            'condition' => $condition + $restrict
        ];

        ITILCategory::dropdown($opt);
        echo Html::hidden('entities_id', ['value' => $entities_id]);
        break;
    case 'users_id_assign':
    case 'users_id_observer':
    case 'users_id_requester':
        // Restrict the actor-right filter to the values the ticket form legitimately emits
        // (see Ticket::getDefaultActorRightSearch); never trust a client-supplied right.
        $allowed_rights = ['all', 'own_ticket', 'id'];
        $right = in_array($_POST['right'] ?? '', $allowed_rights, true) ? $_POST['right'] : 'id';
        User::dropdown(['name' => $_POST['action'],
            'value' => isset($_POST[$_POST['action']]) ? $_POST[$_POST['action']] : 0,
            'entity' =>  $entities_id,
            'right' =>  $right]);
        break;
    case 'groups_id_assign':
    case 'groups_id_observer':
    case 'groups_id_requester':
        // Rebuild the group filter server-side from the action instead of trusting a
        // client-supplied 'condition' array injected as query criteria.
        $group_conditions = [
            'groups_id_requester' => ['is_requester' => 1],
            'groups_id_observer'  => ['is_watcher' => 1],
            'groups_id_assign'    => ['is_assign' => 1],
        ];
        $condition = $group_conditions[$_POST['action']] ?? [];
        $restrict = getEntitiesRestrictCriteria('glpi_groups', '', $entities_id, false);
        \Dropdown::show('Group', ['name' => $_POST['action'],
            'value' => isset($_POST[$_POST['action']]) ? $_POST[$_POST['action']] : 0,
            'entity' => $entities_id,
            'condition' => $condition + $restrict]);
        break;
}

