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
if (strpos($_SERVER['PHP_SELF'], "dropdownNextUser.php")) {
    include('../../../inc/includes.php');
    header("Content-Type: text/html; charset=UTF-8");
    Html::header_nocache();
} else if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access this file directly");
}
//header("Content-Type: text/html; charset=UTF-8");

Session::checkCentralAccess();
$return = "";
$groupUser = new Group_User();
$user = new User();
$users = [];
$rand = $_GET['rand'];
if (isset($_POST['next_groups_id']) && $_POST['next_groups_id'] > 0) {
    $groupUsers = $groupUser->find([
        'groups_id' => $_POST['next_groups_id']
    ]);
    if (count($groupUsers) > 0) {
        foreach ($groupUsers as $grpUsr) {
            $res = $user->getFromDBByCrit(['id' => $grpUsr['users_id']]);
            if ($res) {
                $users[$grpUsr['users_id']] = $user->fields['name'];
            }
        }
        Dropdown::showFromArray(
            'next_users_id',
            $users,
            [
                'display' => true,
                'display_emptychoice' => true,
            ]);
        Ajax::updateItem(

        );

    }

}

