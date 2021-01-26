<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2020 Teclib' and contributors.
 *
 * http://glpi-project.org
 *
 * based on GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2003-2014 by the INDEPNET Development Team.
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * GLPI is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * GLPI is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 * ---------------------------------------------------------------------
 */

include('../../../inc/includes.php');

Session::checkLoginUser();

if (!isset($_REQUEST['action'])) {
   exit;
}

if ($_REQUEST['action'] == 'change_task_state' || $_REQUEST['action'] == 'done_fail') {
   header("Content-Type: application/json; charset=UTF-8");
} else {
   header("Content-Type: text/html; charset=UTF-8");
}

//$objType    = $_REQUEST['parenttype']::getType();
//$foreignKey = $_REQUEST['parenttype']::getForeignKeyField();

switch ($_REQUEST['action']) {
   case "viewsubitem":
      global $CFG_GLPI;
      Html::header_nocache();
      $ticket_id      = $_REQUEST["tickets_id"];
      $metavalidation = new PluginMetademandsMetademandValidation();
      $metavalidation->getFromDBByCrit(['tickets_id' => $ticket_id]);
      echo "<form name='form_raz' id='form_raz' method='post' action='" . $CFG_GLPI["root_doc"] . "/plugins/metademands/ajax/timeline.php" . "' >";
      echo "<input type='hidden' name='action' id='action_validationMeta' value='validationMeta' />";
      echo "<input type='hidden' name='tickets_id' id='action_validationMeta' value='$ticket_id' />";
      echo "<table class='tab_cadre_fixe'>";
      echo "<tr class='tab_bg_1'>";
      echo "<th colspan='4'>";
      echo __("Metademand validation", 'metademands');
      echo "</th>";
      echo "</tr>";
      echo "<tr class='tab_bg_1'>";
      if ($metavalidation->fields["users_id"] == 0) {
         echo "<td>" . __('Create subtickets', 'metademands') . "</td><td>";
         Dropdown::showYesNo("create_subticket", 1);
         echo "</td>";
      } else if ($metavalidation->fields["users_id"] != 0 && $metavalidation->fields["validate"] == 2) {
         echo "<td>" . __('Create subtickets', 'metademands') . "</td><td>";
         Dropdown::showYesNo("create_subticket", 1, 0);
         echo "</td>";
      } else {
         echo "<td>" . __('Create subtickets', 'metademands') . "</td><td>";
         Dropdown::showYesNo("create_subticket", 1, 0);
         echo "</td>";
      }
      echo "<td colspan='2'>";
      if ($metavalidation->fields["users_id"] != 0) {
         $user = new User();
         echo sprintf(__('Validate by %s on %s'), User::getFriendlyNameById($metavalidation->fields["users_id"]), Html::convDateTime($metavalidation->fields["date"]));
      }
      echo "</td>";
      echo "</tr>";
      if ($metavalidation->fields["users_id"] == 0 || $metavalidation->fields["validate"] == 2) {
         echo "<tr class='tab_bg_1'>";
         echo "<td colspan='4' class='center'>";
         echo "<input type='submit' class='submit' name='btnAddAll' id='btnAddAll' ";

         echo "value='" . __("validate metademands", 'metademands') . "' />";
         echo "</td>";
         echo "</tr>";
      }

      Html::closeForm();

      //      Html::ajaxFooter();
      break;
   case "validationMeta":
      $ticket_id      = $_REQUEST["tickets_id"];
      $inputVal       = [];
      $metavalidation = new PluginMetademandsMetademandValidation();
      $metavalidation->getFromDBByCrit(['tickets_id' => $ticket_id]);
      $meta_tasks = json_decode($metavalidation->fields["tickets_to_create"], true);
      $ticket     = new Ticket();
      $ticket->getFromDB($ticket_id);
      $ticket->fields["_users_id_requester"] = Session::getLoginUserID();
      $users                                 = $ticket->getUsers(CommonITILActor::REQUESTER);
      foreach ($users as $user) {
         $ticket->fields["_users_id_requester"] = $user['users_id'];
      }
      $meta = new PluginMetademandsMetademand();
      $meta->getFromDB($metavalidation->getField("plugin_metademands_id"));
      if ($_REQUEST["create_subticket"] == 1) {
         if (!$meta->createSonsTickets($ticket_id,
                                       $ticket->fields,
                                       $ticket_id, $meta_tasks, 1)) {
            $KO[] = 1;

         }
         $inputVal['validate'] = 1;
      } else {
         foreach ($meta_tasks as $meta_task) {
            $ticket_task         = new TicketTask();
            $input               = [];
            $input['content']    = $meta_task['tickettasks_name'] . " " . $meta_task['content'];
            $input['tickets_id'] = $ticket_id;
            $ticket_task->add($input);
         }
         $inputVal['validate'] = 2;
      }

      $inputVal['id']       = $metavalidation->getID();
      $inputVal['users_id'] = Session::getLoginUserID();
      $inputVal['date']     = $_SESSION["glpi_currenttime"];;
      $metavalidation->update($inputVal);
      Html::back();
      //      Html::ajaxFooter();
      break;
}
