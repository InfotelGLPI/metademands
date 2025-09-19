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

header("Content-Type: text/html; charset=UTF-8");
Html::header_nocache();

Session::checkLoginUser();

switch ($_POST['action']) {
    case 'showcategories':

        if (isset($_POST["entities_id"])) {

            $restrict = getEntitiesRestrictCriteria('glpi_itilcategories', '', $_POST['entities_id'], false);

            $condition = [];
            switch ($_POST['type']) {
                case \Ticket::DEMAND_TYPE:
                    $condition = ['is_request' => 1];
                    break;

                default: // \Ticket::INCIDENT_TYPE :
                    $condition = ['is_incident' => 1];
            }
            $opt = [
                'value' => $_POST['itilcategories_id'],
                'condition' => $condition + $restrict
            ];

            ITILCategory::dropdown($opt);
            echo Html::hidden('entities_id', ['value' => $_POST['entities_id']]);
        }
        break;
    case 'users_id_assign':
    case 'users_id_observer':
    case 'users_id_requester':
        if (isset($_POST["entities_id"])) {

            $restrict = getEntitiesRestrictCriteria('glpi_groups', '', $_POST['entities_id'], false);
            User::dropdown(['name' => $_POST['action'],
                'value' => isset($_POST[$_POST['action']]) ? $_POST[$_POST['action']] : 0,
                'entity' =>  $_POST["entities_id"],
                'right' =>  $_POST["right"]]);
        }
        break;
    case 'groups_id_assign':
    case 'groups_id_observer':
    case 'groups_id_requester':
        if (isset($_POST["entities_id"])) {

            $restrict = getEntitiesRestrictCriteria('glpi_groups', '', $_POST['entities_id'], false);
            $condition = $_POST['condition'];
            \Dropdown::show('Group', ['name' => $_POST['action'],
                'value' => isset($_POST[$_POST['action']]) ? $_POST[$_POST['action']] : 0,
                'entity' => $_POST["entities_id"],
                'condition' => $condition + $restrict]);
        }
        break;
}

