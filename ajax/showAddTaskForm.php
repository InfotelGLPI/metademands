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

use GlpiPlugin\Metademands\MailTask;
use GlpiPlugin\Metademands\MetademandTask;
use GlpiPlugin\Metademands\Task;
use GlpiPlugin\Metademands\TicketTask;

header("Content-Type: text/html; charset=UTF-8");
Html::header_nocache();

Session::checkLoginUser();

if (isset($_POST["taskType"])) {
   switch ($_POST["taskType"]) {
       case Task::MAIL_TYPE:
           MailTask::showMailTaskForm($_POST["plugin_metademands_metademands_id"], $_POST["taskType"]);
           break;
      case Task::TICKET_TYPE:
      case Task::TASK_TYPE:
         TicketTask::showTicketTaskForm($_POST["plugin_metademands_metademands_id"], true, $_POST["taskType"]);
         break;
      case Task::METADEMAND_TYPE:
         MetademandTask::showMetademandTaskForm($_POST["plugin_metademands_metademands_id"]);
         break;
   }
}
