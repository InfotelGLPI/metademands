<?php
/*
 * @version $Id: HEADER 15930 2011-10-30 15:47:55Z tsmr $
 -------------------------------------------------------------------------
 Metademands plugin for GLPI
 Copyright (C) 2018-2019 by the Metademands Development Team.

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

if (!isset($_GET["id"])) {
   $_GET["id"] = "";
}
if (!isset($_GET["withtemplate"])) {
   $_GET["withtemplate"] = "";
}

$meta      = new PluginMetademandsMetademand();

if (isset($_POST["add"])) {

   $meta->check(-1, CREATE, $_POST);
   $newID = $meta->add($_POST);
   if ($_SESSION['glpibackcreated']) {
      Html::redirect($meta->getFormURL() . "?id=" . $newID);
   }
   Html::back();

} else if (isset($_POST["delete"])) {

   $meta->check($_POST['id'], DELETE);
   $meta->delete($_POST);
   $meta->redirectToList();

} else if (isset($_POST["restore"])) {

   $meta->check($_POST['id'], PURGE);
   $meta->restore($_POST);
   $meta->redirectToList();

} else if (isset($_POST["purge"])) {

   $meta->check($_POST['id'], PURGE);
   $meta->delete($_POST, 1);
   $meta->redirectToList();

} else if (isset($_POST["update"])) {

   $meta->check($_POST['id'], UPDATE);
   $meta->update($_POST);
   Html::back();

} else {

   $meta->checkGlobal(READ);

   Html::header(PluginMetademandsMetademand::getTypeName(2), '', "helpdesk", "pluginmetademandsmetademand");

   $meta->display($_GET);

   Html::footer();
}
