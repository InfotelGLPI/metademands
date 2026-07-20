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
use GlpiPlugin\Metademands\Metademand;
use GlpiPlugin\Metademands\Stepform;
use GlpiPlugin\Metademands\Stepform_Value;
use GlpiPlugin\Metademands\Wizard;

header("Content-Type: application/json; charset=UTF-8");

Html::header_nocache();

Session::checkLoginUser();

$metademands = new Metademand();
$wizard      = new Wizard();
$form        = new Stepform();

// A step-form may only be loaded by its owner, its direct destinee, or a member
// of the destination group (when not assigned to a specific user). Mirrors the
// access model of Stepform::getWaitingForms() and prevents IDOR by id enumeration.
$form_id  = (int) ($_POST['plugin_metademands_stepforms_id'] ?? 0);
$users_id = Session::getLoginUserID();

$can_load = false;
if ($form->getFromDB($form_id)) {
    if (
        (int) $form->fields['users_id'] === $users_id
        || (int) $form->fields['users_id_dest'] === $users_id
    ) {
        $can_load = true;
    } elseif (
        (int) $form->fields['users_id_dest'] === 0
        && (int) $form->fields['groups_id_dest'] > 0
    ) {
        $group_user = new Group_User();
        $can_load = (bool) $group_user->getFromDBByCrit([
            'users_id'  => $users_id,
            'groups_id' => (int) $form->fields['groups_id_dest'],
        ]);
    }
}
if (!$can_load) {
    throw new AccessDeniedHttpException();
}

    unset($_SESSION['plugin_metademands']);
    $metademands->getFromDB($_POST['metademands_id']);
    Stepform_Value::loadFormValues($_POST['metademands_id'], $_POST['plugin_metademands_stepforms_id']);
    $form_name = $form->getField('name');

    $_SESSION['plugin_metademands'][$_POST['metademands_id']]['fields']['_users_id_requester'] = $_POST['_users_id_requester'];
    // Case of simple ticket convertion

    // Resources id
    if (isset($_POST['resources_id'])) {
        $_SESSION['plugin_metademands'][$_POST['metademands_id']]['fields']['resources_id'] = $_POST['resources_id'];
        // Resources step
        $_SESSION['plugin_metademands'][$_POST['metademands_id']]['fields']['resources_step'] = $_POST['resources_step'];
    }

    //Category id if have category field
    $_SESSION['plugin_metademands'][$_POST['metademands_id']]['field_plugin_servicecatalog_itilcategories_id'] = $_POST['field_plugin_servicecatalog_itilcategories_id'] ?? 0;
    $_SESSION['plugin_metademands'][$_POST['metademands_id']]['field_plugin_servicecatalog_itilcategories_id'] =
        (isset($_POST['basket_plugin_servicecatalog_itilcategories_id'])
            && $_SESSION['plugin_metademands'][$_POST['metademands_id']]['field_plugin_servicecatalog_itilcategories_id'] == 0) ? $_POST['basket_plugin_servicecatalog_itilcategories_id'] : 0;
//    $_SESSION['plugin_metademands'][$_POST['metademands_id']]['field_type']                                    = $metademands->fields['type'];
    $_SESSION['plugin_metademands'][$_POST['metademands_id']]['plugin_metademands_stepforms_id']               = $_POST['plugin_metademands_stepforms_id'];
    $_SESSION['plugin_metademands'][$_POST['metademands_id']]['block_id']                                      = $form->fields['block_id'];

echo 0;
