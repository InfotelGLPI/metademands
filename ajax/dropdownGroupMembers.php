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

/**
 * List of a user field of the wizard offering the members of some groups only ("Members of
 * these groups"): the 'url' of its User::dropdown(), in place of ajax/getDropdownUsers.php of
 * the core, whose rights cannot restrict the users to given groups.
 */

use Glpi\Exception\Http\AccessDeniedHttpException;
use GlpiPlugin\Metademands\Field;
use GlpiPlugin\Metademands\FieldParameter;
use GlpiPlugin\Metademands\Fields\Dropdownobject;
use GlpiPlugin\Metademands\Metademand;

header("Content-Type: application/json; charset=UTF-8");
Html::header_nocache();

// Same rights as the wizard, the only page showing this dropdown
Session::checkSeveralRightsOr([
    'plugin_metademands' => READ,
    'plugin_metademands_createmeta' => READ,
    'plugin_metademands_fillform' => READ,
]);

// Only for a dropdown rendered for this session, as the core endpoint checks it
if (!Session::validateIDOR($_POST + ['itemtype' => User::class, 'right' => ($_POST['right'] ?? '')])) {
    throw new AccessDeniedHttpException();
}

// The groups are those of the field, never posted
$field      = new Field();
$metademand = new Metademand();
if (
    !$field->getFromDB((int) ($_GET['fields_id'] ?? 0))
    || $field->fields['item'] !== User::class
    || !$metademand->getFromDB((int) $field->fields['plugin_metademands_metademands_id'])
    || !Session::haveAccessToEntity($metademand->fields['entities_id'], $metademand->fields['is_recursive'])
) {
    throw new AccessDeniedHttpException();
}

$parameter = new FieldParameter();
$custom    = [];
if ($parameter->getFromDBByCrit(['plugin_metademands_fields_id' => $field->getID()])) {
    $custom = FieldParameter::_unserialize($parameter->fields['custom'] ?? '');
}
$groups_ids = FieldParameter::getMemberGroupsIds($custom);
if ($groups_ids === []) {
    // No longer restricted since the form was displayed
    echo Dropdown::getDropdownUsers($_POST, json: true);
    return;
}

$my_groups_ids = (int) (is_array($custom) ? ($custom['user_group'] ?? 0) : 0) === 1
    ? array_map('intval', $_SESSION['glpigroups'] ?? [])
    : null;

echo json_encode(Dropdownobject::getGroupMembersDropdown($groups_ids, $my_groups_ids, $_POST));
