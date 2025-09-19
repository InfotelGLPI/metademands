<?php
/*
 * @version $Id: HEADER 15930 2011-10-30 15:47:55Z tsmr $
 -------------------------------------------------------------------------
 Metademands plugin for GLPI
 Copyright (C) 2018-2022 by the Metademands Development Team.

 https://github.com/InfotelGLPI/metademands
 -------------------------------------------------------------------------

 LICENSE

 This file is part of Metademands.

 Metademands is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 Metademands is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with Metademands. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 */

use GlpiPlugin\Metademands\Configstep;
use GlpiPlugin\Metademands\Step;

Session::checkLoginUser();

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
        $res = $group->getFromDBByCrit(['id' => $_POST['next_groups_id']]);
        $groupName = $group->fields['name'];
    }
    if (isset($_POST['next_users_id'])
        && $_POST['next_users_id'] != 0) {
        $_SESSION ['plugin_metademands'][$user_id]['users_id_dest'] = $_POST['next_users_id'];
        $userName = getUserName($_POST['next_users_id'], 0, true);
        $msg = sprintf(
            __('The form has been sent to user %s from group %s, you can close the window', 'metademands'),
            $userName,
            $groupName
        );
    } else {
        $msg = sprintf(
            __('The form has been sent to the group %s, you can close the window', 'metademands'),
            $groupName
        );
    }
    $KO = Step::nextUser();

    unset($_SESSION['plugin_metademands']);

    if ($KO === false) {
        $_SESSION['plugin_metademands'][$user_id]['redirect_wizard'] = true;
        Html::popHeader(__('Next recipient', 'metademands'), $_SERVER['PHP_SELF'], true);
        $display = "<div class='alert alert-info alert-info d-flex'>";
        $display .= "$msg";
        $display .= "</div>";
        $display .= Html::popFooter();
        echo $display;
    } else {
        Html::popHeader(__('Next recipient', 'metademands'), $_SERVER['PHP_SELF'], true);
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


