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

$task           = new PluginMetademandsTask();
$tickettask     = new PluginMetademandsTicketTask();
$metademandtask = new PluginMetademandsMetademandTask();

if (isset($_POST["add"])) {

   if (isset($_POST['taskType'])) {
      // Check update rights for clients
      $task->check(-1, UPDATE, $_POST);
      $_POST['plugin_metademands_tasks_id'] = isset($_POST['parent_tasks_id']) ? $_POST['parent_tasks_id'] : 0;

      if (!isset($_POST['block_use']) || $_POST['block_use'] == '') {
         $_POST['block_use'] = json_encode([]);
      } else {
         $_POST['block_use'] = json_encode($_POST['block_use']);
      }
      $_POST['type']  = $_POST['taskType'];
      $_POST['level'] = 1;

      if (isset($_POST['plugin_metademands_tasks_id']) && $_POST['plugin_metademands_tasks_id'] > 0) {
         $parenttask = new PluginMetademandsTask();
         $parenttask->getFromDB($_POST['plugin_metademands_tasks_id']);
         $_POST['level'] = $parenttask->fields['level'] + 1;
      }

      if ($tickettask->isMandatoryField($_POST) && $tasks_id = $task->add($_POST)) {
         if ($_POST['taskType'] == PluginMetademandsTask::TICKET_TYPE
             || $_POST['taskType'] == PluginMetademandsTask::TASK_TYPE) {
            $_POST['plugin_metademands_tasks_id'] = $tasks_id;
            $_POST['type']                        = Ticket::DEMAND_TYPE;
            $tickettask->add($_POST);
         } else {
            if ($_POST['link_metademands_id']) {
               $metademandtask->add(['plugin_metademands_tasks_id'       => $tasks_id,
                                     'plugin_metademands_metademands_id' => $_POST['link_metademands_id']]);
            }
         }
      }
   }

   Html::back();


//} else if (isset($_POST['up'])) {
//   // Replace current parent task by parent's parent task
//   foreach ($_POST["up"] as $tasks_id => $parent_task) {
//
//      $parent_task = key($parent_task);
//
//      if ($task->can($tasks_id, UPDATE)) {
//         // Get parent data
//         $parentTaskData = new PluginMetademandsTask();
//         $parentTaskData->getFromDB($parent_task);
//         $task->update(['id' => $tasks_id, 'plugin_metademands_tasks_id' => $parentTaskData->fields['plugin_metademands_tasks_id']]);
//      }
//   }
//
//   Html::back();
//
//} else if (isset($_POST['down'])) {
//   // Replace current parent task by parent's parent task
//   foreach ($_POST["down"] as $tasks_id => $parent_task) {
//
//      $parent_task = key($parent_task);
//
//      if ($task->can($tasks_id, UPDATE)) {
//         // Get first child
//         $task->getFromDB($tasks_id);
//         $first_child_task = $task->getChildrenForLevel($parent_task, $task->fields['level']);
//         $first_child_task = array_shift($first_child_task);
//         // Current
//         $task->update(['id' => $tasks_id, 'plugin_metademands_tasks_id' => $first_child_task]);
//      }
//   }
//
//   Html::back();

} if (isset($_POST["update"])) {
    // Check update rights for clients
    $task->check(-1, UPDATE, $_POST);

    $input = $_POST;
    $input['type'] = $_POST['taskType'];
    $input['id'] = $_POST['tickettask_id'];
    $input['content'] = $_POST['content'];

    if ($tickettask->isMandatoryField($input) && $tickettask->update($input)) {

        $tasks_id    = $_POST['id'];
        $parent_task = $_POST['parent_tasks_id'] ?? 0;
        unset($input['content']);
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

        $input['name'] = $_POST['name'];
        $input['formatastable'] = $_POST['formatastable'];
        $input['useBlock'] = $_POST['useBlock'];
        $input['block_parent_ticket_resolution'] = $_POST['block_parent_ticket_resolution'];
        $input['id'] = $tasks_id;
        $input['plugin_metademands_tasks_id'] = $parent_task;
        if (!empty($input)) {
            $task->update($input);
        }
    }

    Html::back();
} elseif (isset($_POST["purge"])) {
    // Check update rights for clients
    $task->check(-1, UPDATE, $_POST);
    $task->delete($_POST);
    Html::back();
}
