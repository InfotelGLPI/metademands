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

use GlpiPlugin\Metademands\Configstep;
use GlpiPlugin\Metademands\Step;

// This entry point mutates the workflow state of a step form and echoes a confirmation,
// so it carries the same gate as front/stepform.php, the entry point of that surface.
// The step form itself is then bound to the current user by Step::canActOnStepform(),
// called from Step::nextUser(). It replaces a Session::checkLoginUser() that was dead
// code on a routed GLPI 11 entry point, authentication being enforced by the framework.
Session::checkSeveralRightsOr([
    'plugin_metademands' => READ,
    'plugin_metademands_fillform' => READ,
]);

// We manage the display of the drop-down lists of the groups of the next
// and/or the display of the drop-down lists of the users linked to the group
global $CFG_GLPI;

$conf = new Configstep();
$step = new Step();
$group = new Group();
$groupUser = new Group_User();
$user_id = Session::getLoginUserID();
$user = new User();
$nextGroups = [];
$rand = mt_rand();
$groupName = "";
$userName = "";

if (isset($_POST['action']) && $_POST['action'] == 'nextUser') {

    if (isset($_POST['update_stepform'])) {
        $_SESSION ['plugin_metademands'][$user_id]['update_stepform'] = $_POST['update_stepform'];
    }
    if (isset($_POST['next_groups_id'])) {
        $_SESSION ['plugin_metademands'][$user_id]['groups_id_dest'] = $_POST['next_groups_id'];
        $res = $group->getFromDBByCrit(['id' => (int) $_POST['next_groups_id']]);
        // Group names are stored unescaped (GLPI 10+/11); this legacy echo path
        // is not Twig-autoescaped, so neutralize the value before it is embedded
        // in the confirmation alert to prevent stored XSS. A miss leaves the name
        // empty rather than reading the fields of an unloaded object.
        if ($res) {
            $groupName = htmlspecialchars((string) $group->fields['name'], ENT_QUOTES, 'UTF-8');
        }
    }
    if (isset($_POST['next_users_id'])
        && $_POST['next_users_id'] != 0) {
        $_SESSION ['plugin_metademands'][$user_id]['users_id_dest'] = $_POST['next_users_id'];
        // Same treatment as the group name above: user names are stored unescaped and this
        // legacy echo path is not Twig-autoescaped, so neutralize the value before it is
        // interpolated into the confirmation alert.
        $userName = htmlspecialchars((string) getUserName((int) $_POST['next_users_id']), ENT_QUOTES, 'UTF-8');
        $msg = sprintf(
            __('The form has been sent to user %s from group %s, you can close the window', 'metademands'),
            $userName,
            $groupName,
        );
    } else {
        $msg = sprintf(
            __('The form has been sent to the group %s, you can close the window', 'metademands'),
            $groupName,
        );
    }
    // The destination posted above is staged in the session because nextUser() reads
    // it back from there to validate it. Drop that staging whatever happens, so a
    // destination rejected by nextUser() cannot outlive the request it came with.
    try {
        $KO = Step::nextUser();
    } finally {
        unset($_SESSION['plugin_metademands']);
    }

    if ($KO === false) {
        $_SESSION['plugin_metademands'][$user_id]['redirect_wizard'] = true;
        Html::popHeader(__('Next recipient', 'metademands'), '', true);
        $display = "<div class='alert alert-info alert-info d-flex'>";
        $display .= "$msg";
        $display .= "</div>";
        $display .= Html::popFooter();
        echo $display;
    } else {
        Html::popHeader(__('Next recipient', 'metademands'), '', true);
        $msg = __('A problem occurred, the form was not sent', 'metademands');
        $display = "<div class='alert alert-info alert-info d-flex'>";
        $display .= "$msg";
        $display .= "</div>";
        $display .= Html::popFooter();
        echo $KO;
    }

} else {
    Html::popHeader(__('Next recipient', 'metademands'));
    Step::showModalForm();
    Html::popFooter();
}
