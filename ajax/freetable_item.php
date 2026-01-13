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

header("Content-Type: application/json; charset=UTF-8");

Html::header_nocache();

Session::checkLoginUser();

$data_by_free = [];

$fields_id = $_POST['datas']['fields_id'];
$metademands_id = $_POST['datas']['metademands_id'];


if (isset($_POST['datas']['add'])) {
    $datas[$_POST['datas']['add']['id']] = $_POST['datas']['add'];
    foreach ($datas as $key => $data) {
        $_SESSION['plugin_metademands'][$metademands_id]['freetables'][$fields_id][$key] = $data;
    }
} else if (isset($_POST['datas']['update'])) {
    $datas[$_POST['datas']['update']['id']] = $_POST['datas']['update'];
    foreach ($datas as $key => $data) {
        $_SESSION['plugin_metademands'][$metademands_id]['freetables'][$fields_id][$key] = $data;
    }

} else if (isset($_POST['type']) && $_POST['type'] == 'remove') {
    if (isset($_SESSION['plugin_metademands'][$metademands_id]['freetables'][$fields_id][$_POST['datas']['remove']])) {
        unset($_SESSION['plugin_metademands'][$metademands_id]['freetables'][$fields_id][$_POST['datas']['remove']]);
    }
    if (isset($_SESSION['plugin_metademands'][$metademands_id]['freetables'][$fields_id])) {
        foreach (($_SESSION['plugin_metademands'][$metademands_id]['freetables'][$fields_id]) as $key => $value) {
            if ($value['id'] == $_POST['datas']['remove']) {
                unset($_SESSION['plugin_metademands'][$metademands_id]['freetables'][$fields_id][$key]);
            }
        }
    }
    if (isset($_SESSION['plugin_metademands'][$metademands_id]['fields'][$fields_id])) {
        foreach (($_SESSION['plugin_metademands'][$metademands_id]['fields'][$fields_id]) as $key => $value) {
            if ($value['id'] == $_POST['datas']['remove']) {
                unset($_SESSION['plugin_metademands'][$metademands_id]['fields'][$fields_id][$key]);
            }
        }
    }
}


