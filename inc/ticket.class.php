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

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

/**
 * Class PluginMetademandsTicket
 */
class PluginMetademandsTicket extends CommonDBTM {

   static $rightname = 'plugin_metademands';

   static $types = ['PluginMetademandsMetademand'];

   /**
    * functions mandatory
    * getTypeName(), canCreate(), canView()
    * */
   static function getTypeName($nb = 0) {
      return __('Linked ticket', 'metademands');
   }

   static function canView() {
      return Session::haveRight(self::$rightname, READ);
   }

   static function canCreate() {
      return Session::haveRightsOr(self::$rightname, [CREATE, UPDATE, DELETE]);
   }

   /**
    * @param \Ticket $ticket
    */
   static function emptyTicket(Ticket $ticket) {
      // Metademand redirection on ticket creation

      if (isset($_REQUEST['tickets_id'])
            && $_REQUEST['tickets_id'] == 0
            && isset($_REQUEST['type'])
            && isset($_REQUEST['itilcategories_id'])) {

         $myticket                             = new Ticket();
         $myticket->fields['id']               = 0;
         $myticket->input['type']              = $_REQUEST['type'];
         $myticket->input['itilcategories_id'] = $_REQUEST['itilcategories_id'];
         if ($url = PluginMetademandsMetademand::redirectForm($myticket, 'show')) {
            Html::redirect($url);
         }
      }
   }

   /**
    * @param \Ticket $ticket
    *
    * @return \Ticket
    * @throws \GlpitestSQLError
    */
   static function post_update_ticket(Ticket $ticket) {
      $metademand = new PluginMetademandsMetademand();

      if ($ticket->fields['status'] == Ticket::SOLVED || $ticket->fields['status'] == Ticket::CLOSED) {
         $metademand->addSonTickets($ticket->fields);
      }

      return $ticket;
   }

   /**
    * @param \Ticket $ticket
    *
    * @return \Ticket
    * @throws \GlpitestSQLError
    */
   static function pre_update_ticket(Ticket $ticket) {

      if (isset($ticket->input['status'])) {
         // Actions done on ticket close
         if ($ticket->input['status'] == Ticket::SOLVED || $ticket->input['status'] == Ticket::CLOSED) {
            self::checkSonTicketsStatus($ticket);
         }
      }

      $config_data = PluginMetademandsConfig::getInstance();

      if (isset($ticket->input['itilcategories_id']) && $config_data['simpleticket_to_metademand']) {
         $type = $ticket->fields["type"];
         if (isset($ticket->input['type'])) {
            $type = $ticket->input["type"];
         }

         $dbu = new DbUtils();
         if (!empty($ticket->input["itilcategories_id"])) {
            $cats = $dbu->getAllDataFromTable('glpi_plugin_metademands_metademands',
                 "`itilcategories_id` = ".$ticket->input["itilcategories_id"]." AND `type` = ".$type);

            // Metademand category found : redirection to wizard
            if (!empty($cats)) {
               $data = $dbu->getAllDataFromTable('glpi_plugin_metademands_tickets_metademands', "`tickets_id` = ".$ticket->input["id"]);

               if (empty($data)) {
                  $meta = reset($cats);

                  // Redirect if not linked to a resource contract type
                  //                  if (!countElementsInTable("glpi_plugin_metademands_metademands_resources", "`plugin_metademands_metademands_id`='".$meta["id"]."'")) {
                  //                     Html::redirect($CFG_GLPI["root_doc"]."/plugins/metademands/front/wizard.form.php?metademands_id=".$meta["id"]."&tickets_id=".$ticket->fields["id"]."&step=2");
                  //                  }
               }

               // Metademand category not found : if is ticket is meta, convert it to simple ticket
            } else {
               $data = $dbu->getAllDataFromTable('glpi_plugin_metademands_tickets_metademands', "`tickets_id` = ".$ticket->input["id"]);
               if (!empty($data)) {
                  $data       = reset($data);
                  $metademand = new PluginMetademandsMetademand();
                  $metademand->convertMetademandToTicket($ticket, $data['plugin_metademands_metademands_id']);
               }
            }
         }
      }

      // UPDATE ITILENVIRONMENT
      $environment = new PluginMetademandsITILEnvironment();
      $environment->updateITILEnvironmentForTicket(is_array($ticket->input) ? array_merge($ticket->fields,
                                                                                          $ticket->input) : $ticket->fields);

      // UPDATE ITILAPPLICATION
      $application = new PluginMetademandsITILApplication();
      $application->updateITILApplicationForTicket(is_array($ticket->input) ? array_merge($ticket->fields,
                                                                                          $ticket->input) : $ticket->fields);

      return $ticket;
   }

   /**
    * @param \Ticket $ticket
    */
   static function post_add_ticket(Ticket $ticket) {
      // ADD ITILENVIRONMENT
      $environment = new PluginMetademandsITILEnvironment();
      $environment->addITILEnvironmentForTicket(array_merge($ticket->input, $ticket->fields));

      // ADD ITILAPPLICATION
      $application = new PluginMetademandsITILApplication();
      $application->addITILApplicationForTicket(array_merge($ticket->input, $ticket->fields));
   }

   /**
    * @param \Ticket $ticket
    * @param bool    $with_message
    *
    * @return bool
    * @throws \GlpitestSQLError
    */
   static function checkSonTicketsStatus(Ticket $ticket, $with_message = true) {

      $ticket_metademand = new PluginMetademandsTicket_Metademand();
      $ticket_metademand_data = $ticket_metademand->find('`tickets_id` = '.$ticket->fields['id']);

      // If ticket is Parent : Check if all sons ticket are closed
      if (count($ticket_metademand_data)) {
         $ticket_metademand_data = reset($ticket_metademand_data);
         $tickets_found = self::getSonTickets($ticket->fields['id'],
                                              $ticket_metademand_data['plugin_metademands_metademands_id'],
                                              [], true);

         // If son tickets check status
         if (count($tickets_found)) {
            foreach ($tickets_found as $values) {
               $job = new Ticket();
               if (!empty($values['tickets_id'])) {
                  $job->getFromDB($values['tickets_id']);
               } else {
                  $job->getEmpty();
               }

               // No resolution or close if a son ticket is not solved or closed
               if ((!isset($job->fields['status']))
                       || ($job->fields['status'] != Ticket::SOLVED
                       && $job->fields['status'] != Ticket::CLOSED)) {
                  if ($with_message) {
                     Session::addMessageAfterRedirect(__('The demand can not be resolved or closed until all tasks are not resolved', 'metademands'), false, ERROR);
                  }
                  $ticket->input = ['id' => $ticket->fields['id']];
                  return false;
               }
            }
         }
      }

      return true;
   }


   /**
    * @param       $tickets_id
    * @param       $metademands_id
    * @param array $ticket_task_data
    * @param bool  $recursive
    *
    * @return array
    * @throws \GlpitestSQLError
    */
   static function getSonTickets($tickets_id, $metademands_id, $ticket_task_data = [], $recursive = false) {
      global $DB;

      // Search metademand son ticket : if found recursive call
      $query = "SELECT `glpi_plugin_metademands_metademandtasks`.`plugin_metademands_metademands_id` as metademands_id,
                       `glpi_plugin_metademands_tickets_metademands`.`tickets_id`,
                       `glpi_plugin_metademands_tickets_metademands`.`parent_tickets_id`
               FROM `glpi_plugin_metademands_tickets_metademands`
               RIGHT JOIN `glpi_plugin_metademands_metademandtasks`
                 ON (`glpi_plugin_metademands_metademandtasks`.`plugin_metademands_metademands_id` = `glpi_plugin_metademands_tickets_metademands`.`plugin_metademands_metademands_id`)
               WHERE `glpi_plugin_metademands_tickets_metademands`.`parent_tickets_id` = ". $tickets_id." 
               AND `glpi_plugin_metademands_tickets_metademands`.`tickets_id` != ".$tickets_id;
      $result = $DB->query($query);

      if ($DB->numrows($result)) {
         while ($data = $DB->fetch_assoc($result)) {
            $data['type'] = PluginMetademandsTask::METADEMAND_TYPE;
            $data['level'] = 1;
            $used = false;
            if (count($ticket_task_data)) {
               foreach ($ticket_task_data as $values) {
                  if ($values['tickets_id'] == $data['tickets_id']) {
                     $used = true;
                  }
               }
            }
            if (!$used) {
               $ticket_task_data[] = $data;
            }
            if ($recursive) {
               $ticket_task_data = self::getSonTickets($data['tickets_id'], $data['metademands_id'],
                                                       $ticket_task_data, $recursive);
            }
         }
      }

      // Get direct son ticket
      $query = "SELECT `glpi_plugin_metademands_tickets_tasks`.`tickets_id`,
                       `glpi_plugin_metademands_tickets_tasks`.`parent_tickets_id`,
                       `glpi_plugin_metademands_tickets_tasks`.`level`,
                       `glpi_plugin_metademands_tickets_tasks`.`plugin_metademands_tasks_id` as tasks_id
                  FROM glpi_plugin_metademands_tickets_tasks
                  WHERE `glpi_plugin_metademands_tickets_tasks`.`parent_tickets_id` = ". $tickets_id."";
      $result = $DB->query($query);

      if ($DB->numrows($result)) {
         while ($data = $DB->fetch_assoc($result)) {
            $data['type'] = PluginMetademandsTask::TICKET_TYPE;
            $ticket_task_data[] = $data;
            $ticket_task_data = self::getSonTickets($data['tickets_id'], 0, $ticket_task_data, $recursive);
         }
      }

      // Fill array with uncreated son tickets
      if (!empty($metademands_id)) {
         $task_data = [];
         $task = new PluginMetademandsTask();

         $parent_tasks_id = [];
         $parent_tickets_id[] = $tickets_id;
         foreach ($ticket_task_data as $values) {
            $parent_tickets_id[] = $values['tickets_id'];
            if (isset($values['tasks_id'])) {
               $parent_tasks_id[] = $values['tasks_id'];
            }
         }

         // Search tasks linked to a created ticket
         $query = "SELECT `glpi_plugin_metademands_tasks`.`name` as tasks_name,
                          `glpi_plugin_metademands_tickets_tasks`.`tickets_id`,
                          `glpi_plugin_metademands_tickets_tasks`.`parent_tickets_id`,
                          `glpi_plugin_metademands_tasks`.`level`,
                          `glpi_plugin_metademands_tasks`.`id` as tasks_id
                     FROM glpi_plugin_metademands_tasks
                     LEFT JOIN `glpi_plugin_metademands_tickets_tasks`
                        ON (`glpi_plugin_metademands_tickets_tasks`.`plugin_metademands_tasks_id` = `glpi_plugin_metademands_tasks`.`id`)
                     WHERE `glpi_plugin_metademands_tasks`.`type` = ".PluginMetademandsTask::TICKET_TYPE ."
                     AND `glpi_plugin_metademands_tasks`.`plugin_metademands_metademands_id` = ".$metademands_id."
                     AND `glpi_plugin_metademands_tickets_tasks`.`parent_tickets_id` IN ('".implode("','", $parent_tickets_id)."')
                     ORDER BY `glpi_plugin_metademands_tasks`.`completename`";
         $result = $DB->query($query);
         if ($DB->numrows($result)) {
            $count = 0;
            $dbu = new DbUtils();
            while ($data = $DB->fetch_assoc($result)) {
               $data['type'] = PluginMetademandsTask::TICKET_TYPE;
               $task_data[$count] = $data;
               $children = $dbu->getSonsOf($task->getTable(), $data['tasks_id']);

               foreach ($children as $child_tasks_id) {
                  if ($child_tasks_id != $data['tasks_id'] && !in_array($child_tasks_id, $parent_tasks_id)) {
                     // Remove recurrent data
                     foreach ($task_data as $key => $values) {
                        if ($values['tasks_id'] == $child_tasks_id) {
                           unset($task_data[$key]);
                        }
                     }

                     $task->getFromDB($child_tasks_id);
                     $count++;
                     $task_data[$count] = ['tasks_name'        => $task->fields['name'],
                                                'level'             => $task->fields['level'],
                                                'tickets_id'        => 0,
                                                'tasks_id'          => $child_tasks_id,
                                                'type'              => PluginMetademandsTask::TICKET_TYPE];
                  }
               }

               $count++;
            }
         }

         // Fill metademand tasks
         foreach ($ticket_task_data as $values) {
            if ($values['type'] == PluginMetademandsTask::METADEMAND_TYPE) {
               array_unshift($task_data, $values);
            }
         }

         $ticket_task_data = $task_data;
      }

      return $ticket_task_data;
   }

   /**
    * @param       $tickets_id
    * @param bool  $only_metademand
    * @param array $ticket_task_data
    *
    * @return array
    * @throws \GlpitestSQLError
    */
   static function getAncestorTickets($tickets_id, $only_metademand = false, $ticket_task_data = []) {
      global $DB;

      // Get direct son ticket
      $query = "SELECT `glpi_plugin_metademands_tickets_tasks`.`tickets_id`,
                       `glpi_plugin_metademands_tickets_tasks`.`parent_tickets_id`
                  FROM glpi_plugin_metademands_tickets_tasks
                  WHERE `glpi_plugin_metademands_tickets_tasks`.`tickets_id` = ". $tickets_id."";
      $result = $DB->query($query);
      if ($DB->numrows($result)) {
         while ($data = $DB->fetch_assoc($result)) {
            if (!$only_metademand) {
               $data['type'] = PluginMetademandsTask::TICKET_TYPE;
               $ticket_task_data[] = $data;
            }
            $ticket_task_data = self::getAncestorTickets($data['parent_tickets_id'], $only_metademand, $ticket_task_data);
         }
      }

      // Search metademand parent ticket
      $query = "SELECT `glpi_plugin_metademands_tickets_metademands`.`tickets_id`
               FROM `glpi_plugin_metademands_tickets_metademands`
               WHERE `glpi_plugin_metademands_tickets_metademands`.`tickets_id` = ".$tickets_id;
      $result = $DB->query($query);
      if ($DB->numrows($result)) {
         while ($data = $DB->fetch_assoc($result)) {
            $data['type'] = PluginMetademandsTask::METADEMAND_TYPE;
            $data['level'] = 1;
            $data['tasks_completename'] = '';
            $ticket_task_data[] = $data;
         }
      }

      return $ticket_task_data;
   }

   /**
    * @param       $tickets_id
    * @param array $values
    *
    * @return array
    * @throws \GlpitestSQLError
    */
   function initApplicationEnvironment($tickets_id, $values = []) {
      $application = new PluginMetademandsITILApplication();
      $environment = new PluginMetademandsITILEnvironment();

      if ($tickets_id > 0) {
         $application->getITILApplicationForTicket($tickets_id);
         $environment->getITILEnvironmentForTicket($tickets_id);

         $values['plugin_metademands_itilapplications_id'] = $application->fields['plugin_metademands_itilapplications_id'];
         $values['plugin_metademands_itilenvironments_id'] = $environment->fields['plugin_metademands_itilenvironments_id'];
      }

      if (!isset($values['plugin_metademands_itilapplications_id'])) {
         $values['plugin_metademands_itilapplications_id'] = 0;
      }
      if (!isset($values['plugin_metademands_itilenvironments_id'])) {
         $values['plugin_metademands_itilenvironments_id'] = 0;
      }

      // Load ticket template
      $ticket = new Ticket();
      if ($tickets_id > 0) {
         $ticket->getFromDB($tickets_id);
      } else {
         $ticket->getEmpty();
      }

      foreach ($ticket->fields as $field => $value) {
         if (!isset($values[$field])) {
            $values[$field] = $value;
         }
      }

      $tt = $ticket->getTicketTemplateToUse(false, $values['type'], $values['itilcategories_id'], $values['entities_id']);

      // Predefined values
      if (isset($tt->predefined) && count($tt->predefined) && $tickets_id == 0) {
         foreach ($tt->predefined as $predeffield => $predefvalue) {
            $values[$predeffield] = $predefvalue;
         }
      }

      return [$values, $tt];
   }

   /**
    * @param       $tickets_id
    * @param array $values
    *
    * @return bool
    */
   function getApplicationEnvironment($tickets_id, $values = []) {
      global $CFG_GLPI;

      // validation des droits
      $config = PluginMetademandsConfig::getInstance();
      if (!$this->canview() || !Session::haveRight('ticket', READ) || !$config['enable_application_environment']) {
         return false;
      }

      list($values, $tt) = $this->initApplicationEnvironment($tickets_id, $values);

      echo '<tr class="tab_bg_1" id="plugin_metademands_ticket">';
      // APPLICATION
      echo '<th>';
      echo $tt->getBeginHiddenFieldText('plugin_metademands_itilapplications_id');
      echo sprintf(__('%1$s%2$s'), PluginMetademandsITILApplication::getTypeName(0), $tt->getMandatoryMark('plugin_metademands_itilapplications_id'));
      echo $tt->getEndHiddenFieldText('plugin_metademands_itilapplications_id');
      echo '</th>';
      echo '<td>';
      echo $tt->getBeginHiddenFieldText('plugin_metademands_itilapplications_id');
      if (Session::haveRight('ticket', UPDATE)) {
         $rand = mt_rand();

         $params = ['itilapplications_id' => '__VALUE__',
                         'entity_restrict'     => $values["entities_id"],
                         'itilenvironments_id' => $values['plugin_metademands_itilenvironments_id']];

         $opt = ['value'    => $values['plugin_metademands_itilapplications_id'],
                      'entity'   => $values["entities_id"],
                      'toupdate' => ['update_item' => ['value_fieldname' => "dropdown_plugin_metademands_itilapplications_id".$rand,
                                          'to_update'       => "show_environment$rand",
                                          'url'             => $CFG_GLPI["root_doc"]."/plugins/metademands/ajax/dropdownTicketEnvironments.php",
                                          'moreparams'      => $params]],
                      'rand'     => $rand];

         Dropdown::show('PluginMetademandsITILApplication', $opt);
      } else {
         echo Dropdown::getDropdownName('glpi_plugin_metademands_itilapplications', $values['plugin_metademands_itilapplications_id']);
      }
      echo $tt->getEndHiddenFieldText('plugin_metademands_itilapplications_id');
      echo '</td>';

      // ENVIRONMENT
      echo '<th>';
      echo $tt->getBeginHiddenFieldText('plugin_metademands_itilenvironments_id');
      echo sprintf(__('%1$s%2$s'), PluginMetademandsITILEnvironment::getTypeName(0), $tt->getMandatoryMark('plugin_metademands_itilenvironments_id'));
      echo $tt->getEndHiddenFieldText('plugin_metademands_itilenvironments_id');
      echo '</th>';
      echo '<td colspan="3">';
      echo $tt->getBeginHiddenFieldText('plugin_metademands_itilenvironments_id');
      if (Session::haveRight('ticket', UPDATE)) {
         $used = [];
         if ($values['plugin_metademands_itilapplications_id'] != 0 && $values['plugin_metademands_itilapplications_id'] != 1) {
            $used[] = 1;
         }
         $opt = ['value'  => $values['plugin_metademands_itilenvironments_id'],
                      'entity' => $values["entities_id"],
                      'used'   => $used];
         echo "<span id='show_environment$rand'>";
         Dropdown::show('PluginMetademandsITILEnvironment', $opt);
         echo "</span>";
      } else {
         echo Dropdown::getDropdownName('glpi_plugin_metademands_itilenvironments', $values['plugin_metademands_itilenvironments_id']);
      }
      echo $tt->getEndHiddenFieldText('plugin_metademands_itilenvironments_id');
      echo '</td>';
      echo '</tr>';
   }

   /**
    * @param       $tickets_id
    * @param array $values
    *
    * @return bool
    */
   function getHelpdeskApplicationEnvironment($tickets_id, $values = []) {
      global $CFG_GLPI;

      // validation des droits
      $config = PluginMetademandsConfig::getInstance();
      if (!$this->canview() || !Session::haveRight('ticket', READ) || !$config['enable_application_environment']) {
         return false;
      }

      list($values, $tt) = $this->initApplicationEnvironment($tickets_id, $values);

      echo '<tr class="tab_bg_1">';
      // APPLICATION
      echo '<td>';
      echo $tt->getBeginHiddenFieldText('plugin_metademands_itilapplications_id');
      echo sprintf(__('%1$s%2$s'), PluginMetademandsITILApplication::getTypeName(0), $tt->getMandatoryMark('plugin_metademands_itilapplications_id'));
      echo $tt->getEndHiddenFieldText('plugin_metademands_itilapplications_id');
      echo '</td>';
      echo '<td>';
      echo $tt->getBeginHiddenFieldText('plugin_metademands_itilapplications_id');
      $rand = mt_rand();

      $params = ['itilapplications_id' => '__VALUE__',
                      'entity_restrict'     => $values["entities_id"],
                      'itilenvironments_id' => $values['plugin_metademands_itilenvironments_id']];

      $opt = ['value'    => $values['plugin_metademands_itilapplications_id'],
                   'entity'   => $values["entities_id"],
                   'toupdate' => ['update_item'     => ['value_fieldname' => "dropdown_plugin_metademands_itilapplications_id".$rand,
                                       'to_update'       => "show_environment$rand",
                                       'url'             => $CFG_GLPI["root_doc"]."/plugins/metademands/ajax/dropdownTicketEnvironments.php",
                                       'moreparams'      => $params]],
                   'rand'     => $rand];

      Dropdown::show('PluginMetademandsITILApplication', $opt);
      echo $tt->getEndHiddenFieldText('plugin_metademands_itilenvironments_id');
      echo '</td>';
      echo "</tr><tr class='tab_bg_1'>";

      // ENVIRONMENT
      echo '<td>';
      echo $tt->getBeginHiddenFieldText('plugin_metademands_itilenvironments_id');
      echo sprintf(__('%1$s%2$s'), PluginMetademandsITILEnvironment::getTypeName(0), $tt->getMandatoryMark('plugin_metademands_itilenvironments_id'));
      echo $tt->getEndHiddenFieldText('plugin_metademands_itilenvironments_id');
      echo '</td>';
      echo '<td colspan="3">';
      echo $tt->getBeginHiddenFieldText('plugin_metademands_itilenvironments_id');
      $used = [];
      if ($values['plugin_metademands_itilapplications_id'] != 0 && $values['plugin_metademands_itilapplications_id'] != 1) {
         $used[] = 1;
      }
      $opt = ['value'  => $values['plugin_metademands_itilenvironments_id'],
                   'entity' => $values["entities_id"],
                   'used'   => $used];
      echo "<span id='show_environment$rand'>";
      Dropdown::show('PluginMetademandsITILEnvironment', $opt);
      echo "</span>";
      echo $tt->getEndHiddenFieldText('plugin_metademands_itilenvironments_id');
      echo '</td>';
      echo '</tr>';
   }

   /**
    * @param       $tickets_id
    * @param array $values
    *
    * @return bool
    */
   function getHelpdeskApplicationEnvironmentForCatalogueService($tickets_id, $values = []) {
      global $CFG_GLPI;

      // validation des droits
      $config = PluginMetademandsConfig::getInstance();
      if (!$this->canview() || !Session::haveRight('ticket', READ) || !$config['enable_application_environment']) {
         return false;
      }

      list($values, $tt) = $this->initApplicationEnvironment($tickets_id, $values);

      echo "<div class=\"form-group\">";
      // APPLICATION
      echo "<label class=\"bt-col-md-4 control-label\">";
      echo $tt->getBeginHiddenFieldText('plugin_metademands_itilapplications_id');
      echo sprintf(__('%1$s%2$s'), PluginMetademandsITILApplication::getTypeName(0), $tt->getMandatoryMark('plugin_metademands_itilapplications_id'));
      echo $tt->getEndHiddenFieldText('plugin_metademands_itilapplications_id');
      echo "</label>";
      echo "<div class=\"bt-col-md-4 selectContainer\">";
      echo "<div class=\"input-group\">";
      echo $tt->getBeginHiddenFieldText('plugin_metademands_itilapplications_id');
      $rand = mt_rand();

      $params = ['itilapplications_id' => '__VALUE__',
                 'entity_restrict'     => $values["entities_id"],
                 'itilenvironments_id' => $values['plugin_metademands_itilenvironments_id']];

      $opt = ['value'    => $values['plugin_metademands_itilapplications_id'],
              'entity'   => $values["entities_id"],
              'toupdate' => ['update_item'     => ['value_fieldname' => "dropdown_plugin_metademands_itilapplications_id".$rand,
                                                   'to_update'       => "show_environment$rand",
                                                   'url'             => $CFG_GLPI["root_doc"]."/plugins/metademands/ajax/dropdownTicketEnvironments.php",
                                                   'moreparams'      => $params]],
              'rand'     => $rand];

      Dropdown::show('PluginMetademandsITILApplication', $opt);
      echo $tt->getEndHiddenFieldText('plugin_metademands_itilenvironments_id');
      echo "</div>";
      echo "</div>";
      echo "<br><br>";
      // ENVIRONMENT
      echo "<label class=\"bt-col-md-4 control-label\">";
      echo $tt->getBeginHiddenFieldText('plugin_metademands_itilenvironments_id');
      echo sprintf(__('%1$s%2$s'), PluginMetademandsITILEnvironment::getTypeName(0), $tt->getMandatoryMark('plugin_metademands_itilenvironments_id'));
      echo $tt->getEndHiddenFieldText('plugin_metademands_itilenvironments_id');
      echo "</label>";
      echo "<div class=\"bt-col-md-4 selectContainer\">";
      echo "<div class=\"input-group\">";
      echo $tt->getBeginHiddenFieldText('plugin_metademands_itilenvironments_id');
      $used = [];
      if ($values['plugin_metademands_itilapplications_id'] != 0 && $values['plugin_metademands_itilapplications_id'] != 1) {
         $used[] = 1;
      }
      $opt = ['value'  => $values['plugin_metademands_itilenvironments_id'],
              'entity' => $values["entities_id"],
              'used'   => $used];
      echo "<span id='show_environment$rand'>";
      Dropdown::show('PluginMetademandsITILEnvironment', $opt);
      echo "</span>";
      echo $tt->getEndHiddenFieldText('plugin_metademands_itilenvironments_id');
      echo "</div>";
      echo "</div>";
      echo "</div>";
   }

   /**
    * @param       $tickets_id
    * @param       $input
    * @param array $options
    */
   function getFamily($tickets_id, $input, $options = []) {

      // Init params
      $dbu = new DbUtils();
      $params = [];
      foreach ($options as $key => $val) {
         $params[$key] = $val;
      }

      $ticket = new Ticket();
      if ($tickets_id) {
         $ticket->getFromDB($tickets_id);
      } else {
         $ticket->getEmpty($tickets_id);
      }

      foreach ($ticket->fields as $key => $val) {
         if (!isset($input[$key])) {
            $input[$key] = $ticket->fields[$key];
         }
      }

      if (!isset($input['entities_id'])) {
         $input['entities_id'] = 0;
      }

      if (!isset($input['families_id'])) {
         $ancestors_id         = $dbu->getAncestorsOf('glpi_itilcategories', $input['itilcategories_id']);
         $input['families_id'] = array_shift($ancestors_id);
      }

      // Validation des droits
      $config = PluginMetademandsConfig::getInstance();
      if (!$this->canview() || !$config['enable_families']) {
         return false;
      }

      // Load ticket template
      $tt = $ticket->getTicketTemplateToUse(false, $input['type'], $input['itilcategories_id'], $input['entities_id']);

      $canupdate = Session::haveRight('ticket', UPDATE);
      $canupdate_descr = $canupdate
                   || (($input['status'] == Ticket::INCOMING)
                       && $ticket->isUser(CommonITILActor::REQUESTER, Session::getLoginUserID())
                       && ($ticket->numberOfFollowups() == 0)
                       && ($ticket->numberOfTasks() == 0));

      // Permit to set category when creating ticket without update right
      if ($canupdate
          || !$tickets_id
          || $canupdate_descr) {

         $opt = ['value'  => $input["itilcategories_id"],
                      'entity' => $input["entities_id"]];

         if (Session::getCurrentInterface() == "helpdesk") {
            $opt['condition'] = "`is_helpdeskvisible`='1' AND ";
         } else {
            $opt['condition'] = '';
         }

         /// If category mandatory, no empty choice
         /// no empty choice is default value set on ticket creation, else yes
         if (($tickets_id || $input['itilcategories_id'])
             && $tt->isMandatoryField("itilcategories_id")
             && ($input["itilcategories_id"] > 0)) {
            $opt['display_emptychoice'] = false;
         }

         // Request or incident
         switch ($input["type"]) {
            case Ticket::INCIDENT_TYPE :
               $opt['condition'] .= "`is_incident`='1'";
               break;

            case Ticket::DEMAND_TYPE :
               $opt['condition'] .= "`is_request`='1'";
               break;

            default :
               break;
         }

         // Show Type
         if (!isset($input['displayType'])) {
            echo "<th>".sprintf(__('%1$s%2$s'), __('Type'), $tt->getMandatoryMark('type'))."</th>";
            echo "<td>";
            // Permit to set type when creating ticket without update right
            if ($canupdate || !$tickets_id) {
               $optType = ['value' => $input["type"]];
               if (!$tickets_id) {
                  $optType['on_change'] = 'this.form.submit()';
               }
               Ticket::dropdownType('type', $optType);
            } else {
               echo Ticket::getTicketTypeName($input["type"]);
            }
            echo "</td>";
         }

         // Show family
         echo "<th>";
         echo __('Family', 'metademands');
         echo "</th>";
         echo "<td>";
         $optFamily              = $opt;
         $optFamily['name']      = 'families_id';
         $optFamily['condition'] .= ' AND `level` = 1';
         $optFamily['value']     = isset($input['families_id']) ? $input['families_id'] : 0;
         $optFamily['addicon']   = false;
         unset($optFamily['on_change']);
         ITILCategory::dropdown($optFamily);
         echo "</td>";

         // Show category
         $opt['condition'] .= " AND `level` != 1";
         if (!empty($input['families_id'])) {
            $children = $dbu->getSonsOf('glpi_itilcategories', $input['families_id']);
            $opt['condition'] .= " AND `id` IN ('".implode("','", $children)."')";
         }
         echo "<th>".sprintf(__('%1$s%2$s'), __('Category'),
                     $tt->getMandatoryMark('itilcategories_id'))."</th>";
         echo "<td>";
         echo "<span id='show_category_by_type'>";
         if (!$tickets_id) {
            $opt['on_change'] = 'this.form.submit();';
         }
         ITILCategory::dropdown($opt);
         echo "</span>";

         if (isset($input['displayType'])) {
            echo "<input type='hidden' name='type' value='".$input["type"]."'>";
            echo "<input type='hidden' name='displayType' value='".$input["displayType"]."'>";
         }
         echo "</td>";

      } else {
         echo "<th>".sprintf(__('%1$s%2$s'), __('Type'), $tt->getMandatoryMark('type'))."</th>";
         echo "<td>";
         echo Ticket::getTicketTypeName($input["type"]);
         echo "</td>";
         echo "<th>";
         echo __('Family', 'metademands');
         echo "</th>";
         echo "<td>";
         echo Dropdown::getDropdownName("glpi_itilcategories", isset($input['families_id']) ? $input['families_id'] : 0);
         echo "</td>";
         echo "<th>".sprintf(__('%1$s%2$s'), __('Category'), $tt->getMandatoryMark('itilcategories_id'))."</th>";
         echo "<td>";
         echo Dropdown::getDropdownName("glpi_itilcategories", $input["itilcategories_id"]);
         echo "</td>";
      }
   }

   /**
    * @param       $tickets_id
    * @param       $input
    * @param array $options
    */
   function getHelpdeskFamily($tickets_id, $input, $options = []) {

      // Init params
      $dbu = new DbUtils();
      $params = [];
      foreach ($options as $key => $val) {
         $params[$key] = $val;
      }

      $ticket = new Ticket();
      if ($tickets_id) {
         $ticket->getFromDB($tickets_id);
      } else {
         $ticket->getEmpty($tickets_id);
      }

      if (!isset($input['entities_id'])) {
         $input['entities_id'] = 0;
      }

      if (!isset($input['families_id'])) {
         $ancestors_id         = $dbu->getAncestorsOf('glpi_itilcategories', $input['itilcategories_id']);
         $input['families_id'] = array_shift($ancestors_id);
      }

      // Validation des droits
      $config = PluginMetademandsConfig::getInstance();
      if (!$this->canview() || !Session::haveRight('ticket', READ) || !$config['enable_families']) {
         return false;
      }

      // Load ticket template
      $tt = $ticket->getTicketTemplateToUse(false, $input['type'], $input['itilcategories_id'], $input['entities_id']);

      echo "<tr class='tab_bg_1'>";
      echo "<td>".sprintf(__('%1$s%2$s'), __('Type'), $tt->getMandatoryMark('type'))."</td>";
      echo "<td>";
      Ticket::dropdownType('type', ['value'     => $input['type'],
                                       'on_change' => 'this.form.submit()', 'readonly' => true]);
      echo "</td></tr>";

      // Show family
      echo "<tr class='tab_bg_1'>";
      echo "<td>";
      echo __('Family', 'metademands');
      echo "</td>";
      echo "<td>";
      $condition = "`is_helpdeskvisible`='1'";
      switch ($input['type']) {
         case Ticket::DEMAND_TYPE :
            $condition .= " AND `is_request`='1'";
            break;

         default: // Ticket::INCIDENT_TYPE :
            $condition .= " AND `is_incident`='1'";
      }

      $opt = ['value'     => $input['itilcategories_id'],
                   'condition' => $condition];

      $optFamily            = $opt;
      $optFamily['name']    = 'families_id';
      $optFamily['condition'] .= ' AND `level` = 1';
      $optFamily['value']   = isset($input['families_id']) ? $input['families_id'] : 0;
      $optFamily['addicon'] = false;
      unset($optFamily['on_change']);
      ITILCategory::dropdown($optFamily);
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".sprintf(__('%1$s%2$s'), __('Category'), $tt->getMandatoryMark('itilcategories_id'))."</td>";
      echo "<td>";

      if ($input['itilcategories_id'] && $tt->isMandatoryField("itilcategories_id")) {
         $opt['display_emptychoice'] = false;
      }
      $opt['condition'] .= " AND `level` != 1";
      if (!empty($input['families_id'])) {
         $children = $dbu->getSonsOf('glpi_itilcategories', $input['families_id']);
         $opt['condition'] .= " AND `id` IN ('".implode("','", $children)."')";
      }
      if (!$tickets_id) {
         $opt['on_change'] = 'this.form.submit();';
      }
      ITILCategory::dropdown($opt);
      echo "</td></tr>";
   }

   /**
    * @return array
    */
   function addRuleFields() {
      $actions = [];

      $itilapplication = new PluginMetademandsITILApplication();
      $searchOptions   = $itilapplication->getAddSearchOptions();
      foreach ($searchOptions as $key => $val) {
         if (isset($val['field_name'])) {
            $actions[$val['field_name']]['name']          = $val['name'];
            $actions[$val['field_name']]['type']          = $val['datatype'];
            $actions[$val['field_name']]['table']         = $val['table'];
            $actions[$val['field_name']]['force_actions'] = ['assign'];
         }
      }

      $itilenvironment = new PluginMetademandsITILEnvironment();
      $searchOptions   = $itilenvironment->getAddSearchOptions();
      foreach ($searchOptions as $key => $val) {
         if (isset($val['field_name'])) {
            $actions[$val['field_name']]['name']          = $val['name'];
            $actions[$val['field_name']]['type']          = $val['datatype'];
            $actions[$val['field_name']]['table']         = $val['table'];
            $actions[$val['field_name']]['force_actions'] = ['assign'];
         }
      }

      return $actions;
   }

   /**
    * @param \NotificationTargetTicket $target
    *
    * @throws \GlpitestSQLError
    */
   static function addNotificationDatas(NotificationTargetTicket $target) {
      $application = new PluginMetademandsITILApplication();
      $environment = new PluginMetademandsITILEnvironment();

      $application->getITILApplicationForTicket($target->obj->fields['id']);
      $environment->getITILEnvironmentForTicket($target->obj->fields['id']);

      $target->data['##lang.ticket.itilapplications_id##'] = PluginMetademandsITILApplication::getTypeName(2);
      $target->data['##ticket.itilapplications_id##']      = $application->fields['plugin_metademands_itilapplications_id'];
      $target->data['##lang.ticket.itilenvironments_id##'] = PluginMetademandsITILApplication::getTypeName(2);
      $target->data['##ticket.itilenvironments_id##']      = $environment->fields['plugin_metademands_itilenvironments_id'];
   }

   /**
    * @param $params
    * @param $protocol
    *
    * @return array|bool
    */
   static function methodIsMandatoryFields($params, $protocol) {

      if (isset ($params['help'])) {
         return [  'help'            => 'bool,optional',
                        'values'          => 'array,mandatory'];
      }

      if (!Session::getLoginUserID()) {
         return PluginWebservicesMethodCommon::Error($protocol, WEBSERVICES_ERROR_NOTAUTHENTICATED);
      }

      if (!isset($params['values'])) {
         return PluginWebservicesMethodCommon::Error($protocol, WEBSERVICES_ERROR_MISSINGPARAMETER, '', 'values');
      }

      if (!is_array($params['values'])) {
         return PluginWebservicesMethodCommon::Error($protocol, WEBSERVICES_ERROR_BADPARAMETER, '', 'values not array');
      }

      $tickettask = new PluginMetademandsTicketTask();
      $result = $tickettask->isMandatoryField($params['values'], false, true);

      return $result;
   }


   /**
    * @param $params
    * @param $protocol
    *
    * @return array
    */
   static function methodShowTicketForm($params, $protocol) {

      if (isset ($params['help'])) {
         return [  'help'              => 'bool,optional',
                        'ticket_template'   => 'int,optional',
                        'values'            => 'array,optional'];
      }

      if (!Session::getLoginUserID()) {
         return PluginWebservicesMethodCommon::Error($protocol, WEBSERVICES_ERROR_NOTAUTHENTICATED);
      }

      if (!is_numeric($params['ticket_template'])) {
         return PluginWebservicesMethodCommon::Error($protocol, WEBSERVICES_ERROR_BADPARAMETER, '', 'ticket_template');
      }

      if (!is_array($params['values'])) {
         return PluginWebservicesMethodCommon::Error($protocol, WEBSERVICES_ERROR_BADPARAMETER, '', 'values');
      }

      ob_start();
      self::showFormHelpdesk(Session::getLoginUserID(), $params['ticket_template'], $params['values']);
      $result = ob_get_clean();

      $response = [$result];

      return $response;
   }

      /**
    * Print the helpdesk form
    *
    * @param $ID int : ID of the user who want to display the Helpdesk
    * @param $ticket_template int : ID ticket template for preview : false if not used for preview
    *
    * @return nothing (print the helpdesk)
   **/
   static function showFormHelpdesk($ID, $ticket_template = false, $values = []) {
      global $CFG_GLPI;

      if (!Session::haveRight("ticket", Create)) {
         return false;
      }

      $entities_id = $_SESSION['glpiactive_entity'];

      $fields = ['itilcategories_id' => 0,
                      'itilapplications_id' => 0,
                      'itilenvironments_id' => 0,
                      'content' => '',
                      'name' => '',
                      'type' => 0,
                      'urgency' => 0,
                      'entities_id' => $entities_id];

      $tt = new TicketTemplate();
      if ($ticket_template) {
         $tt->getFromDBWithDatas($ticket_template, true);
      } else {
         $tt->getEmpty();
      }

      if (!empty($values)) {
         foreach ($values as $key => $value) {
            $fields[$key] = $value;
         }
      }

      echo "<input type='hidden' name='_from_helpdesk' value='1'>";
      echo "<input type='hidden' name='requesttypes_id' value='".RequestType::getDefault('helpdesk')."'>";
      echo "<input type='hidden' name='entities_id' value='".$entities_id."'>";

      echo "<div class='center'><table class='tab_cadre_fixe'>";
      // URGENCY
      if ($CFG_GLPI['urgency_mask']!=(1<<3)) {
            echo "<tr class='tab_bg_1'>";
            echo "<td>".__('Urgency');
            echo $tt->getMandatoryMark('urgency')."</td>";
            echo "<td>";
            Ticket::dropdownUrgency("urgency");
            echo "</td></tr>";

      }

      // TITLE
      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Title');
      echo $tt->getMandatoryMark('name');
      echo "</td>";
      echo "<td><input type='text' maxlength='250' size='80' name='name'
                       value=\"".$fields['name']."\"></td></tr>";

      // CONTENT
      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Description');
      echo $tt->getMandatoryMark('content');
      echo "</td>";
      echo "<td><textarea name='content' cols='80' rows='14'>".$fields['content']."</textarea>";
      echo "</td></tr>";

      echo "</table></div>";
   }

   /**
    * @param $ticket_metademand_data
    *
    * @return bool
    */
   static function isTicketSolved($ticket_metademand_data) {
      $tickets = [];
      $solved = true;
      if (!empty($ticket_metademand_data)) {
         foreach ($ticket_metademand_data as $meta) {
            $tickets_found = PluginMetademandsTicket::getSonTickets($meta['tickets_id'], 0, [], true);

            $tickets[] = $meta['tickets_id'];
            foreach ($tickets_found as $k => $v) {
               $tickets[] = $v["tickets_id"];
            }
         }
         if (!empty($tickets)) {
            $status = [Ticket::SOLVED, Ticket::CLOSED];
            foreach ($tickets as $key => $val) {
               $job = new Ticket();
               if ($job->getfromDB($val)) {
                  if (!in_array($job->fields['status'], $status)) {
                     $solved = false;
                  }
               }
            }
         }
      }

      return $solved;
   }

   /**
    * @param $params
    *
    * @return true
    */
   static function uploadTicketDocument($params) {

      $document_name = addslashes($params['name']);

      $filename = tempnam(GLPI_DOC_DIR . '/_tmp', 'PWS');
      $toupload = self::uploadDocument($params, $filename, $document_name);

      return $toupload;
   }

   /**
    * This method manage upload of files into GLPI
    *
    * @param $params parameters
    * @param $protocol the protocol used for remote call
    * @param $filename name of the file on the filesystem
    * @param $document_name name of the document into glpi
    *
    * @return true or an Error
   **/
   static function uploadDocument($params, $filename, $document_name) {

      $files   = [];
      $content = null;

      if (isset($params['base64'])) {
         $content = base64_decode($params['base64']);
         if (!$content) {
            Session::addMessageAfterRedirect(__('Failed to send the file (probably too large)'), false, ERROR);
         }
         $files['name'] = basename($document_name);
      }

      $splitter = explode(".", $filename);
      $splitter2 = explode(".", basename($files['name']));

      $filename = $splitter[0].".".$splitter2[1];

      @file_put_contents($filename, $content);

      $files['tmp_name'] = "/".basename($filename);

      return $files;
   }

   /**
    * @param        $tickets_id
    * @param        $itilActorType
    * @param string $type
    *
    * @return array
    */
   static function getUsedActors($tickets_id, $itilActorType, $type = 'users_id') {
      $resultFound = [];

      switch ($type) {
         case 'users_id': $item = new Ticket_User(); break;
         case 'groups_id': $item = new Group_Ticket(); break;
      }

      $dataActors = $item->getActors($tickets_id);

      if (isset($dataActors[$itilActorType])) {
         foreach ($dataActors[$itilActorType] as $data) {
            $resultFound[] = $data[$type];
         }
      }

      return $resultFound;
   }

   /**
    * Add allowed fields for ticket templates
    *
    * @param string $allowed_fields
    * @return string
    */
   static function getAllowedFields($allowed_fields) {

      $itilapplication = new PluginMetademandsITILApplication();
      $searchOptions = $itilapplication->getAddSearchOptions();
      foreach ($searchOptions as $key => $val) {
         if (isset($val['name'])) {
            $allowed_fields[$key] = $val['field_name'];
         }
      }

      $itilenvironment = new PluginMetademandsITILEnvironment();
      $searchOptions = $itilenvironment->getAddSearchOptions();
      foreach ($searchOptions as $key => $val) {
         if (isset($val['name'])) {
            $allowed_fields[$key] = $val['field_name'];
         }
      }

      return $allowed_fields;
   }

}