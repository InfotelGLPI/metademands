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

use GlpiPlugin\Metademands\Metademand;

if (strpos($_SERVER['PHP_SELF'], "dropdownITILCategories.php")) {
   header("Content-Type: text/html; charset=UTF-8");
   Html::header_nocache();
} else if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

//$opt = ['entity' => $_POST["entity_restrict"]];
$condition  =[];

if ($_POST["type"]) {
    switch ($_POST['type']) {
        case \Ticket::INCIDENT_TYPE :
            $criteria['is_incident'] = 1;
            break;

        case \Ticket::DEMAND_TYPE:
            $criteria['is_request'] = 1;
            break;
    }
}

//if ($this->fields['object_to_create'] == 'Problem') {
//    $criteria = ['is_problem' => 1];
//} elseif ($this->fields['object_to_create'] == 'Change') {
//    $criteria = ['is_change' => 1];
//}
if(!isset($criteria)) {
    $criteria = [];
}

$criteria += getEntitiesRestrictCriteria(
    \ITILCategory::getTable(),
    'entities_id',
    $_SESSION['glpiactiveentities'],
    true
);

$dbu    = new DbUtils();

$crit["is_deleted"] = 0;
$crit["is_template"] = 0;
$crit['type'] = $_POST['type'];

$cats = $dbu->getAllDataFromTable(Metademand::getTable(), $crit);

$used = [];
foreach ($cats as $item) {
    $tempcats = json_decode($item['itilcategories_id'], true);
    if (is_null($tempcats)) {
        $tempcats = [];
    } else {
        foreach ($tempcats as $tempcat) {
            $used [] = $tempcat;
        }
    }

}

//$ticketcats = $dbu->getAllDataFromTable(TicketTask::getTable());
//foreach ($ticketcats as $item) {
//    if ($item['itilcategories_id'] > 0) {
//        $used []= $item['itilcategories_id'];
//    }
//}
$used = array_unique($used);

$criteria += ['NOT' => [
    'id' => $used
]];
if (count($used) == 0) {
    $result = $dbu->getAllDataFromTable(ITILCategory::getTable());
} else {
    $result = $dbu->getAllDataFromTable(ITILCategory::getTable(), $criteria);
}

$temp   = [];
foreach ($result as $item) {
   $temp[$item['id']] = html_entity_decode($item['completename']);
}

\Dropdown::showFromArray('itilcategories_id', $temp,
                        ['width'    => '100%',
                         'multiple' => true,
                         'entity'   => $_POST["entity_restrict"]]);
