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

use Glpi\Exception\Http\AccessDeniedHttpException;
use GlpiPlugin\Metademands\Step;

header("Content-Type: text/html; charset=UTF-8");
Html::header_nocache();

$groupUser = new Group_User();

$step = new Step();

$next_groups_id = isset($_POST['next_groups_id']) ? (int) $_POST['next_groups_id'] : 0;

if ($next_groups_id > 0) {
    // This endpoint returns the full member list of the requested group. Without an entity
    // check any authenticated requester could enumerate the membership of arbitrary groups
    // (including groups in entities they cannot access). Validate the group against the
    // caller's own perimeter, mirroring ajax/showfieldsbyentity.php.
    $group = new Group();
    if (!$group->getFromDB($next_groups_id)
        || !Session::haveAccessToEntity($group->fields['entities_id'], $group->fields['is_recursive'])) {
        throw new AccessDeniedHttpException();
    }

    $groupUsers = $groupUser->find([
        'groups_id' => $next_groups_id
    ]);
    if (count($groupUsers) > 0) {
        $step->displayNextUser($groupUsers);
    }
}

