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

include('../../../inc/includes.php');

if (empty($_GET["id"])) {
    $_GET["id"] = "";
}

$material = new PluginMetademandsBasketobject();

if (isset($_POST["add"])) {

    $material->check(-1, CREATE, $_POST);
    $newID = $material->add($_POST);
    if ($_SESSION['glpibackcreated']) {
        Html::redirect($material->getFormURL() . "?id=" . $newID);
    }
    Html::back();

} else if (isset($_POST["delete"])) {

    $material->check($_POST['id'], DELETE);
    $material->delete($_POST);
    $material->redirectToList();

} else if (isset($_POST["restore"])) {

    $material->check($_POST['id'], PURGE);
    $material->restore($_POST);
    $material->redirectToList();

} else if (isset($_POST["purge"])) {

    $material->check($_POST['id'], PURGE);
    $material->delete($_POST, 1);
    $material->redirectToList();

} else if (isset($_POST["update"])) {

    $material->check($_POST['id'], UPDATE);
    $material->update($_POST);
    Html::back();

} else {
    Html::header(__('Reference catalog', 'metademands'), '', "management", "pluginmetademandsbasketobject");
    $material->display(['id' => $_GET["id"]]);
    Html::footer();
}
