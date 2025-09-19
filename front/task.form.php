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

use GlpiPlugin\Metademands\Task;
use GlpiPlugin\Metademands\TicketTask;
use GlpiPlugin\Metademands\MetademandTask;
use GlpiPlugin\Metademands\MailTask;

Session::checkLoginUser();

if (empty($_GET["id"])) {
   $_GET["id"] = "";
}

$task           = new Task();
$tickettask     = new TicketTask();
$metademandtask = new MetademandTask();
$mailtask = new MailTask();

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
         $parenttask = new Task();
         $parenttask->getFromDB($_POST['plugin_metademands_tasks_id']);
         $_POST['level'] = $parenttask->fields['level'] + 1;
      }

      if ($tickettask->isMandatoryField($_POST) && $tasks_id = $task->add($_POST)) {
         if ($_POST['taskType'] == Task::TICKET_TYPE
             || $_POST['taskType'] == Task::TASK_TYPE) {
            $_POST['plugin_metademands_tasks_id'] = $tasks_id;
            $_POST['type']                        = \Ticket::DEMAND_TYPE;
            $tickettask->add($_POST);
         } else if($_POST['taskType'] == Task::METADEMAND_TYPE){
            if ($_POST['link_metademands_id']) {
               $metademandtask->add(['plugin_metademands_tasks_id'       => $tasks_id,
                                     'plugin_metademands_metademands_id' => $_POST['link_metademands_id']]);
            }
         } else if ($_POST['taskType'] == Task::MAIL_TYPE){
             $_POST['plugin_metademands_tasks_id'] = $tasks_id;
             $_POST['type']                        = \Ticket::DEMAND_TYPE;
             $mailtask->add($_POST);
         }
      }
   }

   Html::back();

} if (isset($_POST["update"])) {
    // Check update rights for clients
    $task->check(-1, UPDATE, $_POST);

    $input = $_POST;
    $input['type'] = $_POST['taskType'];
    $input['content'] = $_POST['content'];
    if ($input['type'] == Task::MAIL_TYPE) {
        $input['id'] = $_POST['mailtask_id'];
        if($mailtask->update($input)){
            if (!isset($_POST['block_use']) || $_POST['block_use'] == '') {
                $input['block_use'] = json_encode([]);
            } else {
                $input['block_use'] = json_encode($_POST['block_use']);
            }
            $input['id'] = $_POST['id'];
            $input['_no_message_link'] = 0;
            $task->update($input);
        }
    } else {
        $input['id'] = $_POST['tickettask_id'];
        if ($tickettask->isMandatoryField($input) && $tickettask->update($input)) {

            $tasks_id = $_POST['id'];
            $parent_task = $_POST['parent_tasks_id'] ?? 0;
            unset($input['content']);
            if (!isset($_POST['block_use']) || $_POST['block_use'] == '') {
                $input['block_use'] = json_encode([]);
            } else {
                $input['block_use'] = json_encode($_POST['block_use']);
            }

            if ($parent_task > 0) {
                $parenttask = new Task();
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
    }

    Html::back();
} elseif (isset($_POST["purge"])) {
    // Check update rights for clients
    $task->check(-1, UPDATE, $_POST);
    $task->delete($_POST);
    Html::back();
}
