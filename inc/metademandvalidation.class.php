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
 * Class PluginMetademandsField
 */
class PluginMetademandsMetademandValidation extends CommonDBTM {


   static $rightname = 'plugin_metademands';

   const TASK_CREATION   = 2; // waiting
   const TICKET_CREATION = 1; // waiting

   /**
    * functions mandatory
    * getTypeName(), canCreate(), canView()
    *
    * @param int $nb
    *
    * @return string
    */
   static function getTypeName($nb = 0) {
      return __('Metademands validation', 'metademands');
   }

   /**
    * @return bool|int
    */
   static function canView() {
      return Session::haveRight(self::$rightname, READ);
   }

   /**
    * @return bool
    */
   static function canCreate() {
      return Session::haveRightsOr(self::$rightname, [CREATE, UPDATE, DELETE]);
   }

   /**
    * Display tab for each users
    *
    * @param CommonGLPI $item
    * @param int        $withtemplate
    *
    * @return array|string
    */
   function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {

      //      if (!$withtemplate) {
      //         if ($item->getType() == 'PluginMetademandsMetademand') {
      //            if ($_SESSION['glpishow_count_on_tabs']) {
      //               $dbu = new DbUtils();
      //               return self::createTabEntry(self::getTypeName(),
      //                                           $dbu->countElementsInTable($this->getTable(),
      //                                                                      ["plugin_metademands_metademands_id" => $item->getID()]));
      //            }
      //            return self::getTypeName();
      //         }
      //      }
      return '';
   }

   /**
    * Display content for each users
    *
    * @static
    *
    * @param CommonGLPI $item
    * @param int        $tabnum
    * @param int        $withtemplate
    *
    * @return bool|true
    */
   static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {
      $field = new self();

      //      if (in_array($item->getType(), self::getTypes(true))) {
      //         $field->showForm(0, ["item" => $item]);
      //      }
      return true;
   }

   /**
    * @param array $options
    *
    * @return array
    * @see CommonGLPI::defineTabs()
    */
   function defineTabs($options = []) {

      $ong = [];
      $this->addDefaultFormTab($ong);
      //      $this->addStandardTab('PluginMetademandsFieldTranslation', $ong, $options);

      return $ong;
   }


   /**
    * @param       $ID
    * @param array $options
    *
    * @return bool
    * @throws \GlpitestSQLError
    */
   function showForm($ID, $options = []) {
      global $CFG_GLPI;

      if (!$this->canview()) {
         return false;
      }
      if (!$this->cancreate()) {
         return false;
      }
      Html::requireJs('tinymce');

      $metademand = new PluginMetademandsMetademand();

      if ($ID > 0) {
         $this->check($ID, READ);
         $metademand->getFromDB($this->fields['plugin_metademands_metademands_id']);
      } else {
         // Create item
         $item    = $options['item'];
         $canedit = $metademand->can($item->fields['id'], UPDATE);
         $this->getEmpty();
         $this->fields["plugin_metademands_metademands_id"] = $item->fields['id'];
         $this->fields['color']                             = '#000';
      }


      if ($ID > 0) {
         $this->showFormHeader(['colspan' => 2]);
      } else {
         echo "<div class='center first-bloc'>";
         echo "<form name='field_form' method='post' action='" . Toolbox::getItemTypeFormURL(__CLASS__) . "'>";
         echo "<table class='tab_cadre_fixe'>";
         echo "<tr class='tab_bg_1'>";
         echo "<th colspan='6'>" . __('Add a field', 'metademands') . "</th>";
         echo "</tr>";
      }


      if ($ID > 0) {
         $this->showFormButtons(['colspan' => 2]);

      } else {
         if ($canedit) {
            echo "<tr class='tab_bg_1'>";
            echo "<td class='tab_bg_2 center' colspan='6'>";
            echo "<input type='hidden' class='submit' name='plugin_metademands_metademands_id' value='" . $item->fields['id'] . "'>";
            echo "<input type='submit' class='submit' name='add' value='" . _sx('button', 'Add') . "'>";
            echo "</td>";
            echo "</tr>";
         }

         echo "</table>";
         Html::closeForm();
         echo "</div>";

      }
      return true;
   }

   function validateMeta($params) {
      $ticket_id = $params["tickets_id"];
      $inputVal  = [];

      $this->getFromDBByCrit(['tickets_id' => $ticket_id]);
      $meta_tasks = json_decode($this->fields["tickets_to_create"], true);
      foreach ($meta_tasks as $key => $val) {
         $meta_tasks[$key]['tickettasks_name']   = urldecode($val['tickettasks_name']);
         $meta_tasks[$key]['tasks_completename'] = urldecode($val['tasks_completename']);
         $meta_tasks[$key]['content']            = urldecode($val['content']);
      }
      $ticket = new Ticket();
      $ticket->getFromDB($ticket_id);
      $ticket->fields["_users_id_requester"] = Session::getLoginUserID();
      $users                                 = $ticket->getUsers(CommonITILActor::REQUESTER);
      foreach ($users as $user) {
         $ticket->fields["_users_id_requester"] = $user['users_id'];
      }
      $meta = new PluginMetademandsMetademand();
      $meta->getFromDB($this->getField("plugin_metademands_id"));
      if ($params["create_subticket"] == 1) {
         if (!$meta->createSonsTickets($ticket_id,
                                       $ticket->fields,
                                       $ticket_id, $meta_tasks, 1)) {
            $KO[] = 1;

         }
         $inputVal['validate'] = self::TICKET_CREATION;
      } else {
         foreach ($meta_tasks as $meta_task) {
            $ticket_task         = new TicketTask();
            $input               = [];
            $input['content']    = $meta_task['tickettasks_name'] . " " . $meta_task['content'];
            $input['tickets_id'] = $ticket_id;
            $ticket_task->add($input);
         }
         $inputVal['validate'] = self::TASK_CREATION;
      }

      $inputVal['id']       = $this->getID();
      $inputVal['users_id'] = Session::getLoginUserID();
      $inputVal['date']     = $_SESSION["glpi_currenttime"];;
      $this->update($inputVal);
   }

   function viewValidation($params) {
      global $CFG_GLPI;

      $ticket_id = $params["tickets_id"];
      $this->getFromDBByCrit(['tickets_id' => $ticket_id]);
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
      if ($this->fields["users_id"] == 0) {
         echo "<td>" . __('Create sub-tickets', 'metademands') . "</td><td>";
         echo "<input class='custom-control-input' type='radio' name='create_subticket' id='create_subticket[" . 1 . "]' value='1' checked>";
         echo "</td>";
         echo "<td>" . __('Create tasks', 'metademands') . "</td><td>";
         echo "<input class='custom-control-input' type='radio' name='create_subticket' id='create_subticket[" . 0 . "]' value='0'>";
         echo "</td>";
      } else if ($this->fields["users_id"] != 0 && $this->fields["validate"] == self::TASK_CREATION) {
         echo "<td>" . __('Create sub-tickets', 'metademands') . "</td><td>";
         echo "<input class='custom-control-input' type='radio' name='create_subticket' id='create_subticket[" . 1 . "]' value='1' checked>";
         echo "</td>";
         echo "<td>" . __('Create tasks', 'metademands') . "</td><td>";
         echo "<input class='custom-control-input' type='radio' name='create_subticket' id='create_subticket[" . 0 . "]' value='0' disabled>";
         echo "</td>";
      } else {
         echo "<td colspan='4'>" . __('Sub-tickets are already created', 'metademands') . "</td>";


      }
      echo "</tr>";
      if ($this->fields["users_id"] != 0) {
         echo "<tr class='tab_bg_1'>";
         echo "<td colspan='4'>";
         $user = new User();
         echo sprintf(__('Validated by %s on %s', 'metademands'), User::getFriendlyNameById($this->fields["users_id"]), Html::convDateTime($this->fields["date"]));
         echo "</td>";
         echo "</tr>";
      }

      if ($this->fields["users_id"] == 0 || $this->fields["validate"] == self::TASK_CREATION) {
         echo "<tr class='tab_bg_1'>";
         echo "<td colspan='4' class='center'>";
         echo "<input type='submit' class='submit' name='btnAddAll' id='btnAddAll' ";

         echo "value='" . __("Validate metademands", 'metademands') . "' />";
         echo "</td>";
         echo "</tr>";
      }
      //      foreach ($data['custom_values'] as $key => $label) {
      //         $field .= "<div class='custom-control custom-radio $inline'>";
      //
      //         $checked = "";
      //         if ($value != NULL && $value == $key) {
      //            $checked = $value == $key ? 'checked' : '';
      //         } elseif ($value == NULL && isset($defaults[$key]) && $on_basket == false) {
      //            $checked = ($defaults[$key] == 1) ? 'checked' : '';
      //         }
      //         $field .= "<input class='custom-control-input' type='radio' name='" . $namefield . "[" . $data['id'] . "]' id='" . $namefield . "[" . $data['id'] . "][" . $key . "]' value='$key' $checked>";
      //         $nbr++;
      //         $field .= "&nbsp;<label class='custom-control-label' for='" . $namefield . "[" . $data['id'] . "][" . $key . "]'>$label</label>";
      //         if (isset($data['comment_values'][$key]) && !empty($data['comment_values'][$key])) {
      //            $field .= "&nbsp;<span style='vertical-align: bottom;'>";
      //            $field .= Html::showToolTip($data['comment_values'][$key],
      //                                        ['awesome-class' => 'fa-info-circle',
      //                                         'display'       => false]);
      //            $field .= "</span>";
      //         }
      //         $field .= "</div>";
      //      }
      Html::closeForm();
   }

}
