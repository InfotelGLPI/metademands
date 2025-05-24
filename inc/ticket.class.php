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

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access directly to this file");
}

/**
 * Class PluginMetademandsTicket
 */
class PluginMetademandsTicket extends CommonDBTM
{
    public static $rightname = 'plugin_metademands';

    /**
     * functions mandatory
     * getTypeName(), canCreate(), canView()
     *
     * @param int $nb
     *
     * @return string
     */
    public static function getTypeName($nb = 0)
    {
        return __('Linked ticket', 'metademands');
    }

   /**
    * @return bool|int
    */
    public static function canView()
    {
        return Session::haveRight(self::$rightname, READ);
    }

   /**
    * @return bool
    */
    public static function canCreate()
    {
        return Session::haveRight(self::$rightname, CREATE);
    }

   /**
    * @param \Ticket $ticket
    */
    public static function emptyTicket(Ticket $ticket)
    {
        // Metademand redirection on ticket creation

        if (((isset($_REQUEST['id'])
          && $_REQUEST['id'] == 0) || (isset($_SESSION['glpiactiveprofile']['interface'])
                    && $_SESSION['glpiactiveprofile']['interface'] != 'central'))
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
    public static function post_update_ticket(Ticket $ticket)
    {
        $metademand        = new PluginMetademandsMetademand();
        $ticket_metademand = new PluginMetademandsTicket_Metademand();

        $ticket_metademand->getFromDBByCrit(['tickets_id' => $ticket->getID()]);

        if ($ticket->fields['status'] == Ticket::SOLVED
          || $ticket->fields['status'] == Ticket::CLOSED) {
            $metademand->addSonTickets($ticket->fields, $ticket_metademand);
        }

        self::manageMetademandStatusOnUpdateTicket($ticket_metademand, $ticket);

        return $ticket;
    }

   /**
    * @param $ticket_metademand
    * @param $ticket
    *
    * @return void
    */
    public static function manageMetademandStatusOnUpdateTicket($ticket_metademand, $ticket)
    {
        $parent_ticket = false;

        //parent or child
        if (count($ticket_metademand->fields) > 0) {
            $parent_ticket = true;
        } else {
            $ticket_parent_id  = self::getTicketIDOfMetademand($ticket->getID());
            $meta_to_not_close = self::childTicketsOpen($ticket_parent_id);
            if ($ticket_metademand->getFromDBByCrit(['parent_tickets_id' => $ticket_parent_id])) {
                $validationmeta = new PluginMetademandsMetademandValidation();
                $validation     = $validationmeta->getFromDBByCrit(['tickets_id' => $ticket_parent_id]);
                $validation_ok  = false;
                if ($validation) {
                    if (in_array(
                        $validationmeta->fields['validate'],
                        [PluginMetademandsMetademandValidation::TO_VALIDATE, PluginMetademandsMetademandValidation::TO_VALIDATE_WITHOUTTASK]
                    )) {
                        $validation_ok = true;
                    }
                }

                if (!$meta_to_not_close && !$validation_ok) {
                    $ticket_metademand->update(['id' => $ticket_metademand->getID(),
                                          'status' => PluginMetademandsTicket_Metademand::TO_CLOSED]);
                } elseif ($meta_to_not_close || $validation_ok) {
                    $ticket_metademand->update(['id' => $ticket_metademand->getID(),
                                          'status' => PluginMetademandsTicket_Metademand::RUNNING]);
                }
            }
        }

        //reopen or refused solution for parent with no sons
        if ($parent_ticket) {
            if ($ticket->getField('status') != Ticket::SOLVED
            && $ticket->getField('status') != Ticket::CLOSED
            && $ticket_metademand->getField('status') == PluginMetademandsTicket_Metademand::CLOSED) {
                $ticket_metademand->update(['id' => $ticket_metademand->getID(),
                    'status' => PluginMetademandsTicket_Metademand::RUNNING]);
            }
        }
    }

   /**
   * @param $ticket_id
   *
   * @return mixed
   */
    public static function getTicketIDOfMetademand($ticket_id)
    {
        $ticket_task = new PluginMetademandsTicket_Task();
        if ($ticket_task->getFromDBByCrit(['tickets_id' => $ticket_id])) {
            while ($ticket_task->fields['level'] != 1) {
                $ticket_task->getFromDBByCrit(['tickets_id' => $ticket_task->fields['parent_tickets_id']]);
            }
            return $ticket_task->fields['parent_tickets_id'];
        }
        return $ticket_id;
    }

   /**
    * @param $tickets_id
    *
    * @return bool
    */
    public static function childTicketsOpen($tickets_id)
    {
        $ticket_task  = new PluginMetademandsTicket_Task();
        $ticket_tasks = $ticket_task->find(['parent_tickets_id' => $tickets_id]);
        $status       = false;
        if (count($ticket_tasks) == 0) {
            return false;
        } else {
            $ticket = new Ticket();
            foreach ($ticket_tasks as $tt) {
                $ticket->getFromDB($tt['tickets_id']);
                if (isset($ticket->fields['status']) && in_array($ticket->fields['status'], Ticket::getNotSolvedStatusArray())) {
                    return true;
                } else {
                    $status = ($status || self::childTicketsOpen($tt['tickets_id']));
                    if ($status == true) {
                        return $status;
                    }
                }
            }
            return $status;
        }
    }

   /**
    * @param \Ticket $ticket
    *
    * @return \Ticket
    * @throws \GlpitestSQLError
    */
    public static function pre_update_ticket(Ticket $ticket)
    {
        if (isset($ticket->input['status'])) {
            // Actions done on ticket close
            if ($ticket->input['status'] == Ticket::SOLVED
             || $ticket->input['status'] == Ticket::CLOSED) {
                self::checkSonTicketsStatus($ticket);
            }
        }

//        $config_data = PluginMetademandsConfig::getInstance();

//        if (isset($ticket->input['itilcategories_id']) && $config_data['simpleticket_to_metademand']) {
//            $dbu = new DbUtils();
//            if (!empty($ticket->input["itilcategories_id"])) {
//                $data = $dbu->getAllDataFromTable(
//                    'glpi_plugin_metademands_tickets_metademands',
//                    ["`tickets_id`" => $ticket->input["id"]]
//                );
//                if (!empty($data) && $ticket->input['itilcategories_id'] != $ticket->fields['itilcategories_id']) {
//                    $data       = reset($data);
//                    $metademand = new PluginMetademandsMetademand();
//                    $metademand->convertMetademandToTicket($ticket, $data['plugin_metademands_metademands_id']);
//                }
//            }
//        }

        return $ticket;
    }


   /**
    * @param \Ticket $ticket
    * @param bool    $with_message
    *
    * @return bool
    * @throws \GlpitestSQLError
    */
    public static function checkSonTicketsStatus(Ticket $ticket, $with_message = true)
    {
        $ticket_metademand      = new PluginMetademandsTicket_Metademand();
        $ticket_metademand_data = $ticket_metademand->find(['tickets_id' => $ticket->fields['id']]);

        // If ticket is Parent : Check if all sons ticket are closed
        if (count($ticket_metademand_data)) {
            $ticket_metademand_data = reset($ticket_metademand_data);
            $tickets_found          = self::getSonTickets(
                $ticket->fields['id'],
                $ticket_metademand_data['plugin_metademands_metademands_id'],
                [],
                true,
                true
            );

            // If son tickets check status
            if (count($tickets_found)) {
                foreach ($tickets_found as $values) {
                    $job = new Ticket();
                    if (!empty($values['tickets_id'])) {
                        $ko = 0;
                        $ticket_tasks      = new PluginMetademandsTicket_Task();
                        if ($ticket_tasks->getFromDBByCrit(['tickets_id' => $values['tickets_id']])) {
                            $task = new PluginMetademandsTask();
                            if ($task->getFromDB($ticket_tasks->fields['plugin_metademands_tasks_id'])) {
                                if ($task->fields['block_parent_ticket_resolution'] == 1) {
                                    $ko = 1;
                                }
                            }
                        }

                        $job->getFromDB($values['tickets_id']);
                        // No resolution or close if a son ticket is not solved or closed
                        if ((!isset($job->fields['status']))
                          || ($job->fields['status'] != Ticket::SOLVED
                          && $job->fields['status'] != Ticket::CLOSED) && $ko == 1) {
                            if ($with_message) {
                                Session::addMessageAfterRedirect(__('The ticket cannot be resolved or closed until all child tickets are not resolved', 'metademands'), false, ERROR);
                            }
                            $ticket->input = ['id' => $ticket->fields['id']];
                            return false;
                        }
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
    public static function getSonTickets($tickets_id, $metademands_id, $ticket_task_data = [], $recursive = false, $seesolved = false)
    {
        global $DB;

        if (isset($tickets_id) && $tickets_id > 0) {
            // Search metademand son ticket : if found recursive call
            $query  = "SELECT `glpi_plugin_metademands_metademandtasks`.`plugin_metademands_metademands_id` as metademands_id,
                       `glpi_plugin_metademands_tickets_metademands`.`tickets_id`,
                       `glpi_plugin_metademands_tickets_metademands`.`parent_tickets_id`
               FROM `glpi_plugin_metademands_tickets_metademands`
               RIGHT JOIN `glpi_plugin_metademands_metademandtasks`
                 ON (`glpi_plugin_metademands_metademandtasks`.`plugin_metademands_metademands_id` = `glpi_plugin_metademands_tickets_metademands`.`plugin_metademands_metademands_id`)
               LEFT JOIN `glpi_tickets` ON (`glpi_tickets`.`id` =  `glpi_plugin_metademands_tickets_metademands`.`parent_tickets_id`)
               WHERE `glpi_tickets`.`is_deleted` = 0 
                 AND `glpi_plugin_metademands_tickets_metademands`.`parent_tickets_id` = " . $tickets_id . " 
               AND `glpi_plugin_metademands_tickets_metademands`.`tickets_id` != " . $tickets_id;

            if ($seesolved == false) {
                $query .= " AND `glpi_tickets`.`status` NOT IN (".Ticket::SOLVED.", ".Ticket::CLOSED.") ";
            }
            $result = $DB->query($query);

            if ($DB->numrows($result)) {
                while ($data = $DB->fetchAssoc($result)) {
                    $data['type']  = PluginMetademandsTask::METADEMAND_TYPE;
                    $data['level'] = 1;
                    $used          = false;
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
                        $ticket_task_data = self::getSonTickets(
                            $data['tickets_id'],
                            $data['metademands_id'],
                            $ticket_task_data,
                            $recursive,
                            $seesolved
                        );
                    }
                }
            }

            // Get direct son ticket
            $query  = "SELECT `glpi_plugin_metademands_tickets_tasks`.`tickets_id`,
                       `glpi_plugin_metademands_tickets_tasks`.`parent_tickets_id`,
                       `glpi_plugin_metademands_tickets_tasks`.`level`,
                       `glpi_plugin_metademands_tickets_tasks`.`plugin_metademands_tasks_id` as tasks_id
                  FROM glpi_plugin_metademands_tickets_tasks
                  LEFT JOIN `glpi_tickets` ON (`glpi_tickets`.`id` =  `glpi_plugin_metademands_tickets_tasks`.`parent_tickets_id`)
                  WHERE `glpi_plugin_metademands_tickets_tasks`.`parent_tickets_id` = " . $tickets_id . " 
                  AND `glpi_tickets`.`is_deleted` = 0";

            if ($seesolved == false) {
                $query .= " AND `glpi_tickets`.`status` NOT IN (".Ticket::SOLVED.", ".Ticket::CLOSED.") ";
            }
            $result = $DB->query($query);

            if ($DB->numrows($result)) {
                while ($data = $DB->fetchAssoc($result)) {
                    $data['type']       = PluginMetademandsTask::TICKET_TYPE;
                    $ticket_task_data[] = $data;
                    $ticket_task_data   = self::getSonTickets($data['tickets_id'], 0, $ticket_task_data, $recursive, $seesolved);
                }
            }

            // Fill array with uncreated son tickets
//            if (!empty($metademands_id)) {
//                $task_data           = [];
//                $task                = new PluginMetademandsTask();
//                $parent_tickets_id[] = $tickets_id;
//                foreach ($ticket_task_data as $values) {
//                    $parent_tickets_id[] = $values['tickets_id'];
//                }
//
//                // Search tasks linked to a created ticket
//                $query = "SELECT `glpi_plugin_metademands_tasks`.`name` as tasks_name,
//                          `glpi_plugin_metademands_tickets_tasks`.`tickets_id`,
//                          `glpi_plugin_metademands_tickets_tasks`.`parent_tickets_id`,
//                          `glpi_plugin_metademands_tasks`.`level`,
//       `glpi_plugin_metademands_tasks`.`plugin_metademands_metademands_id`,
//       `glpi_plugin_metademands_tickets_tasks`.`level` AS parent_level,
//                          `glpi_plugin_metademands_tasks`.`id` AS tasks_id
//                     FROM glpi_plugin_metademands_tasks
//                     LEFT JOIN `glpi_plugin_metademands_tickets_tasks`
//                        ON (`glpi_plugin_metademands_tickets_tasks`.`plugin_metademands_tasks_id` = `glpi_plugin_metademands_tasks`.`id`)
//                     WHERE `glpi_plugin_metademands_tasks`.`type` = " . PluginMetademandsTask::TICKET_TYPE . "
//                     AND `glpi_plugin_metademands_tasks`.`plugin_metademands_metademands_id` = " . $metademands_id . "
//                     AND `glpi_plugin_metademands_tickets_tasks`.`tickets_id` IN ('" . implode("','", $parent_tickets_id) . "')
//                     ORDER BY tasks_id";
//
//                $result = $DB->query($query);
//                $count  = 0;
//
//                if ($DB->numrows($result)) {
//                    while ($data = $DB->fetchAssoc($result)) {
//                        $data['type']                 = PluginMetademandsTask::TICKET_TYPE;
//                        $task_data[$data['tasks_id']] = $data;
//                        // If child task exists : son ticket creation
//                        $child_tasks_data = $task->getChildrenForLevel($data['tasks_id'], $data['parent_level'] + 1);
//                        if ($child_tasks_data !== false) {
//                            $tasks = [];
//                            foreach ($child_tasks_data as $child_tasks_id) {
//                                $tasks[] = $task->getTasks(
//                                    $data['plugin_metademands_metademands_id'],
//                                    ['condition' => ['glpi_plugin_metademands_tasks.id' => $child_tasks_id]]
//                                );
//                            }
//
//
//                            foreach ($tasks as $k => $v) {
//                                foreach ($v as $taskchild) {
//                                    if (PluginMetademandsTicket_Field::checkTicketCreation($taskchild['tasks_id'], $tickets_id)) {
//                                        $task_data[$taskchild['tasks_id']] = ['tasks_name' => $taskchild['tickettasks_name'],
//                                                                              'level'      => $taskchild['level'],
//                                                                              'tickets_id' => 0,
//                                                                              'tasks_id'   => $taskchild['tasks_id'],
//                                                                              'type'       => PluginMetademandsTask::TICKET_TYPE];
//                                        $count++;
//                                    }
//                                }
//                            }
//                        }
//                        $count++;
//                    }
//                }
//
//                // Fill metademand tasks
//                foreach ($ticket_task_data as $values) {
//                    if ($values['type'] == PluginMetademandsTask::METADEMAND_TYPE) {
//                        array_unshift($task_data, $values);
//                    }
//                }
//
//                $ticket_task_data = $task_data;
//            }
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
    public static function getAncestorTickets($tickets_id, $only_metademand = false, $ticket_task_data = [])
    {
        global $DB;
        $ticket_task_data = [];
        // Search metademand parent ticket
//        $query  = "SELECT `tickets_id`,`parent_tickets_id`
//                  FROM glpi_plugin_metademands_tickets_tasks
//                  WHERE `tickets_id` = " . $tickets_id . "";
//        $result = $DB->query($query);
//        if ($DB->numrows($result)) {
//            while ($data = $DB->fetchAssoc($result)) {
//                if (!$only_metademand) {
//                    $data['type']       = PluginMetademandsTask::TICKET_TYPE;
//                    $ticket_task_data[] = $data;
//                }
//            }
//        }

        // Search metademand parent ticket
        $query  = "SELECT `parent_tickets_id` as tickets_id
               FROM `glpi_plugin_metademands_tickets_metademands`
               WHERE `tickets_id` = " . $tickets_id;
        $result = $DB->query($query);
        if ($DB->numrows($result)) {
            while ($data = $DB->fetchAssoc($result)) {
                $data['type']               = PluginMetademandsTask::METADEMAND_TYPE;
                $data['level']              = 1;
                $data['tasks_completename'] = '';
                $ticket_task_data[]         = $data;
            }
        }

        return $ticket_task_data;
    }


//   /**
//    * @param $params
//    * @param $protocol
//    *
//    * @return array|bool
//    */
//    static function methodIsMandatoryFields($params, $protocol)
//    {
//
//        if (isset($params['help'])) {
//            return ['help'   => 'bool,optional',
//                 'values' => 'array,mandatory'];
//        }
//
//        if (!Session::getLoginUserID()) {
//            return PluginWebservicesMethodCommon::Error($protocol, WEBSERVICES_ERROR_NOTAUTHENTICATED);
//        }
//
//        if (!isset($params['values'])) {
//            return PluginWebservicesMethodCommon::Error($protocol, WEBSERVICES_ERROR_MISSINGPARAMETER, '', 'values');
//        }
//
//        if (!is_array($params['values'])) {
//            return PluginWebservicesMethodCommon::Error($protocol, WEBSERVICES_ERROR_BADPARAMETER, '', 'values not array');
//        }
//
//        $tickettask = new PluginMetademandsTicketTask();
//        $result     = $tickettask->isMandatoryField($params['values'], false, true);
//
//        return $result;
//    }


//   /**
//    * @param $params
//    * @param $protocol
//    *
//    * @return array
//    */
//    static function methodShowTicketForm($params, $protocol)
//    {
//
//        if (isset($params['help'])) {
//            return ['help'            => 'bool,optional',
//                 'ticket_template' => 'int,optional',
//                 'values'          => 'array,optional'];
//        }
//
//        if (!Session::getLoginUserID()) {
//            return PluginWebservicesMethodCommon::Error($protocol, WEBSERVICES_ERROR_NOTAUTHENTICATED);
//        }
//
//        if (!is_numeric($params['ticket_template'])) {
//            return PluginWebservicesMethodCommon::Error($protocol, WEBSERVICES_ERROR_BADPARAMETER, '', 'ticket_template');
//        }
//
//        if (!is_array($params['values'])) {
//            return PluginWebservicesMethodCommon::Error($protocol, WEBSERVICES_ERROR_BADPARAMETER, '', 'values');
//        }
//
//        ob_start();
//        self::showFormHelpdesk($params['ticket_template'], $params['values']);
//        $result = ob_get_clean();
//
//        $response = [$result];
//
//        return $response;
//    }

   /**
    * Print the helpdesk form
    *
    * @param       $ID int : ID of the user who want to display the Helpdesk
    * @param bool  $ticket_template int : ID ticket template for preview : false if not used for preview
    *
    * @param array $values
    *
    * @return bool (print the helpdesk)
    */
    public static function showFormHelpdesk($ticket_template = false, $values = [])
    {
        global $CFG_GLPI;

        if (!Session::haveRight("ticket", CREATE)) {
            return false;
        }

        $entities_id = $_SESSION['glpiactive_entity'];

        $fields = ['itilcategories_id' => 0,
                 'content'           => '',
                 'name'              => '',
                 'type'              => 0,
                 'urgency'           => 0,
                 'entities_id'       => $entities_id];

        $tt = new TicketTemplate();
        if ($ticket_template) {
            $tt->getFromDBWithData($ticket_template, true);
        } else {
            $tt->getEmpty();
        }

        if (!empty($values)) {
            foreach ($values as $key => $value) {
                $fields[$key] = $value;
            }
        }

        echo Html::hidden('_from_helpdesk', ['value' => 1]);
        echo Html::hidden('requesttypes_id', ['value' => RequestType::getDefault('helpdesk')]);
        echo Html::hidden('entities_id', ['value' => $entities_id]);

        echo "<div class='center'><table class='tab_cadre_fixe'>";
        // URGENCY
        if ($CFG_GLPI['urgency_mask'] != (1 << 3)) {
            echo "<tr class='tab_bg_1'>";
            echo "<td>" . __('Urgency');
            echo $tt->getMandatoryMark('urgency') . "</td>";
            echo "<td>";
            Ticket::dropdownUrgency("urgency");
            echo "</td></tr>";
        }

        // TITLE
        echo "<tr class='tab_bg_1'>";
        echo "<td>" . __('Title');
        echo $tt->getMandatoryMark('name');
        echo "</td>";
        echo "<td>";
        echo Html::input('name', ['value' => $fields['name'], 'size' => 80]);
        echo "</td></tr>";

        // CONTENT
        echo "<tr class='tab_bg_1'>";
        echo "<td>" . __('Description');
        echo $tt->getMandatoryMark('content');
        echo "</td>";
        echo "<td>";
        Html::textarea(['name'            => 'content',
                      'value'           => $fields['content'],
                      'cols'       => 80,
                      'rows'       => 14,
                      'enable_richtext' => false]);
        echo "</td></tr>";

        echo "</table></div>";
    }

   /**
    * @param $ID
    *
    * @return bool
    * @throws \GlpitestSQLError
    */
    public static function isTicketSolved($ID)
    {
        $ticket_metademand      = new PluginMetademandsTicket_Metademand();
        $ticket_metademand_data = $ticket_metademand->find(['plugin_metademands_metademands_id' => $ID,
                                                            'status' => PluginMetademandsTicket_Metademand::RUNNING]);

        $tickets = [];
        $solved  = true;

        if (!empty($ticket_metademand_data)) {
            foreach ($ticket_metademand_data as $meta) {
                $tickets_found = self::getSonTickets($meta['tickets_id'], 0, [], true);

                $job = new Ticket();
                if ($job->getfromDB($meta['tickets_id'])
                    && $job->fields['is_deleted'] == 0) {
                    $tickets[] = $meta['tickets_id'];
                }
                foreach ($tickets_found as $k => $v) {
                    $tickets[] = $v["tickets_id"];
                }
            }
            if (!empty($tickets)) {
                $solved = false;
//                $status = [Ticket::SOLVED, Ticket::CLOSED];
//                foreach ($tickets as $key => $val) {
//                    $job = new Ticket();
//                    if ($job->getfromDB($val)
//                        && $job->fields['is_deleted'] == 0) {
//                        if (!in_array($job->fields['status'], $status)) {
//
//                        }
//                    }
//                }
            }
        }

        return $solved;
    }

   /**
    * @param $params
    *
    * @return true
    */
    public static function uploadTicketDocument($params)
    {
        $document_name = addslashes($params['name']);

        $filename = tempnam(GLPI_DOC_DIR . '/_tmp', 'PWS');
        $toupload = self::uploadDocument($params, $filename, $document_name);

        return $toupload;
    }

   /**
    * This method manage upload of files into GLPI
    *
    * @param $params parameters
    * @param $filename name of the file on the filesystem
    * @param $document_name name of the document into glpi
    *
    * @return array or an Error
    */
    public static function uploadDocument($params, $filename, $document_name)
    {
        $files   = [];
        $content = null;

        if (isset($params['base64'])) {
            $content = base64_decode($params['base64']);
            if (!$content) {
                Session::addMessageAfterRedirect(__('Failed to send the file (probably too large)'), false, ERROR);
            }
            $files['name'] = basename($document_name);
        }

        $splitter  = explode(".", $filename);
        $splitter2 = explode(".", basename($files['name']));

        $filename = $splitter[0] . "." . $splitter2[1];

        @file_put_contents($filename, $content);

        $files['tmp_name'] = "/" . basename($filename);

        return $files;
    }

   /**
    * @param        $tickets_id
    * @param        $itilActorType
    * @param string $type
    *
    * @return array
    */
    public static function getUsedActors($tickets_id, $itilActorType, $type = 'users_id')
    {
        $resultFound = [];

        switch ($type) {
            case 'users_id':
                $item = new Ticket_User();
                break;
            case 'groups_id':
                $item = new Group_Ticket();
                break;
        }

        $dataActors = $item->getActors($tickets_id);

        if (isset($dataActors[$itilActorType])) {
            foreach ($dataActors[$itilActorType] as $data) {
                $resultFound[] = $data[$type];
            }
        }

        return $resultFound;
    }
}
