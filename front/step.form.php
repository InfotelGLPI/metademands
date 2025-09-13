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

use GlpiPlugin\Metademands\Menu;
use GlpiPlugin\Metademands\Metademand;
use GlpiPlugin\Metademands\Step;

if (!isset($_GET["id"])) {
    $_GET["id"] = "";
}
if (!isset($_GET["withtemplate"])) {
    $_GET["withtemplate"] = "";
}

$meta = new Step();

if (isset($_POST["add"])) {

    if (isset($_POST["block_id"])) {
        $meta->check(-1, CREATE, $_POST);
        $blocks = $_POST["block_id"];
        if (count($blocks) > 0) {
            foreach ($blocks as $block) {
                $input['block_id'] = $block;
                $input['groups_id'] = $_POST["groups_id"];
                if ($input['groups_id'] > 0) {
                    $input['only_by_supervisor'] = 0;
                } else {
                    $input['only_by_supervisor'] = $_POST["only_by_supervisor"];
                }
                $input['message'] = $_POST["message"];
                $input['plugin_metademands_metademands_id'] = $_POST["plugin_metademands_metademands_id"];
                $newID = $meta->add($input);
            }
        }
    } else {
        Session::addMessageAfterRedirect(__('Used blocks are mandatory', 'metademands'), true, ERROR);
    }
    Html::back();
} elseif (isset($_POST["delete"])) {
    $meta->check($_POST['id'], DELETE);
    $meta->delete($_POST);
    $meta->redirectToList();
} elseif (isset($_POST["restore"])) {
    $meta->check($_POST['id'], PURGE);
    $meta->restore($_POST);
    $meta->redirectToList();
} elseif (isset($_POST["purge"])) {
    $meta->check($_POST['id'], PURGE);
    $meta->delete($_POST, 1);
    $meta->redirectToList();
} elseif (isset($_POST["update"])) {
    $meta->check($_POST['id'], UPDATE);
    $meta->update($_POST);
    Html::back();
} else {
    $meta->checkGlobal(READ);

    Html::header(Metademand::getTypeName(2), '', "helpdesk", Menu::class);

    $meta->display($_GET);

    Html::footer();
}
