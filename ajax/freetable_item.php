<?php

/*
 -------------------------------------------------------------------------
 Metademands plugin for GLPI
 Copyright (C) 2003-2019 by the Metademands Development Team.

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

include('../../../inc/includes.php');

header("Content-Type: application/json; charset=UTF-8");

Html::header_nocache();

Session::checkLoginUser();

$data_by_free = [];

$fields_id = $_POST['fields_id'];
$linebyfield = 'lines'.$fields_id;

if (isset($_POST[$linebyfield])) {
    foreach ($_POST[$linebyfield] as $key => $data) {
        $_SESSION['plugin_metademands'][$_POST['metademands_id']]['freetables'][$fields_id][$data['id']] = $data;
    }
}

if (isset($_POST['remove'])) {
    if (isset($_SESSION['plugin_metademands'][$_POST['metademands_id']]['freetables'][$fields_id])) {
        unset($_SESSION['plugin_metademands'][$_POST['metademands_id']]['freetables'][$fields_id][$_POST['remove']]);
    }
}
