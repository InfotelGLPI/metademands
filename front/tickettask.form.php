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
Session::checkLoginUser();

if (empty($_GET["id"])) {
    $_GET["id"] = "";
}

$tickettask = new PluginMetademandsTicketTask();
$task       = new PluginMetademandsTask();


if (isset($_POST["update"])) {
    // Check update rights for clients
    $tickettask->check(-1, UPDATE, $_POST);

    if ($tickettask->isMandatoryField($_POST) && $tickettask->update($_POST)) {
        $tasks_id    = $_POST['plugin_metademands_tasks_id'];
        $parent_task = $_POST['parent_tasks_id'] ?? 0;

        if (!isset($_POST['block_use']) || $_POST['block_use'] == '') {
            $input['block_use'] = json_encode([]);
        } else {
            $input['block_use'] = json_encode($_POST['block_use']);
        }

        if ($parent_task > 0) {
            $parenttask = new PluginMetademandsTask();
            $parenttask->getFromDB($parent_task);
            $input['level'] = $parenttask->fields['level'] + 1;
        } else {
            $input['plugin_metademands_tasks_id'] = 0;
            $input['level'] = 1;
        }
//        $input['type']  = $_POST['type'];
        $input['id'] = $tasks_id;
        $input['plugin_metademands_tasks_id'] = $parent_task;

        $task->update($input);
    }
    //   PluginMetademandsMetademand::addLog($_POST, PluginMetademandsMetademand::LOG_UPDATE);
    Html::back();
} elseif (isset($_POST["purge"])) {
    // Check update rights for clients
    $tickettask->check(-1, UPDATE, $_POST);
    if ($tickettask->delete($_POST)) {
        $_POST['id'] = $_POST['plugin_metademands_tasks_id'];
        $task->delete($_POST);
    }
//   PluginMetademandsMetademand::addLog($_POST, PluginMetademandsMetademand::LOG_DELETE);
//   echo html::scriptBlock("window.parent.$('div[id^=\"metademandTicketTask\"]').dialog('close');window.parent.location.reload();");
} elseif (isset($_GET['_in_modal'])) {
    Html::popHeader(PluginMetademandsTask::getTypeName(2), $_SERVER['PHP_SELF']);
    $_SESSION["metademandsHelpdeskSaved"] = $_POST;
    $tickettask->showForm($_GET["id"]);
    Html::popFooter();
} else {
    $tickettask->checkGlobal(READ);
    Html::header(PluginMetademandsTask::getTypeName(2), '', "helpdesk", "pluginmetademandsmenu");
    $_SESSION["metademandsHelpdeskSaved"] = $_POST;
    $tickettask->display(['id' => $_GET["id"]]);
    Html::footer();
}
