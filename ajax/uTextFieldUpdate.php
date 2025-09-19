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

$AJAX_INCLUDE = 1;

if (strpos($_SERVER['PHP_SELF'], "uTextFieldUpdate.php")) {
    header("Content-Type: text/html; charset=UTF-8");
    Html::header_nocache();
}

Session::checkLoginUser();
$user = new User();
$data = [];
if (isset($_GET['id']) && $_GET["id"] > 0) {
    $user->getFromDB($_GET['id']);
    // see field.class.php used_by_ticket dropdown definition
    $data = [
        5 => $user->getDefaultEmail(),
        6 => $user->getField('phone'),
        11 => $user->getField('mobile'),
        22 => $user->getField('registration_number'),
    ];
}
echo json_encode($data);


