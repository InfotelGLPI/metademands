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

/**
 * Class PluginMetademandsDraft
 */
class PluginMetademandsInterticketfollowup extends CommonDBTM {

   static $rightname = 'plugin_metademands';


   static function getFollowupForTicket($tickets_id) {

   }

//   static function getListTicket() {
//      $ticket_metademand      = new PluginMetademandsTicket_Metademand();
//      $ticket_metademand_data = $ticket_metademand->find(['tickets_id' => $ticket->fields['id']]);
//      $tickets_found          = [];
//      // If ticket is Parent : Check if all sons ticket are closed
//      if (count($ticket_metademand_data)) {
//         $ticket_metademand_data = reset($ticket_metademand_data);
//         $tickets_found          = PluginMetademandsTicket::getSonTickets($ticket->fields['id'],
//                                                                          $ticket_metademand_data['plugin_metademands_metademands_id']);
//
//      } else {
//         $ticket_task      = new PluginMetademandsTicket_Task();
//         $ticket_task_data = $ticket_task->find(['tickets_id' => $ticket->fields['id']]);
//
//         if (count($ticket_task_data)) {
//            $tickets_found = PluginMetademandsTicket::getAncestorTickets($ticket->fields['id'], true);
//         }
//      }
//   }

   static function getFirstTicket($tickets_id) {
      $ticket_metademand      = new PluginMetademandsTicket_Metademand();
      $ticket_metademand_data = $ticket_metademand->getFromDBByCrit(['tickets_id' => $tickets_id]);
      if ($ticket_metademand_data) {
        return $tickets_id;
      } else {
         $ticket_task = new PluginMetademandsTicket_Task();
         $ticket_task->getFromDBByCrit(['tickets_id' => $tickets_id]);
         return self::getFirstTicket($ticket_task->fields['parent_tickets_id']);
      }
   }


   static function getTargets($items_id) {
      $first_tickets_id = self::getFirstTicket($items_id);
      $ticket_metademand      = new PluginMetademandsTicket_Metademand();
      $ticket_metademand_data = $ticket_metademand->find(['tickets_id' => $first_tickets_id]);
      $tickets_found          = [];
      // If ticket is Parent : Check if all sons ticket are closed
      if (count($ticket_metademand_data)) {
         $ticket_metademand_data = reset($ticket_metademand_data);
         $tickets_found          = PluginMetademandsTicket::getSonTickets($first_tickets_id,
                                                                          $ticket_metademand_data['plugin_metademands_metademands_id']);
         $targets           = [];
         $ticket = new Ticket();
         $targets[-1] = __('All tickets','metademands');
         if($first_tickets_id != $items_id) {
            $ticket->getFromDB($first_tickets_id);
            $targets[$first_tickets_id] = $ticket->getFriendlyName();
         }
         foreach ($tickets_found as $ticket_found) {
            if ($ticket_found['tickets_id'] != $items_id) {
               $ticket->getFromDB($ticket_found['tickets_id']);
               $targets[$ticket_found['tickets_id']] = $ticket->getFriendlyName();
            }
         }

      }
      return $targets;
   }
   static function getlistItems($item) {
      $items_id = $item['item']->fields['id'];
      $first_tickets_id = self::getFirstTicket($items_id);
      $ticket_metademand      = new PluginMetademandsTicket_Metademand();
      $ticket_metademand_data = $ticket_metademand->find(['tickets_id' => $first_tickets_id]);
      $tickets_found          = [];
      // If ticket is Parent : Check if all sons ticket are closed
      if (count($ticket_metademand_data)) {
         $ticket_metademand_data = reset($ticket_metademand_data);
         $tickets_found          = PluginMetademandsTicket::getSonTickets($first_tickets_id,
                                                                          $ticket_metademand_data['plugin_metademands_metademands_id']);
         $list_tickets = [];
         foreach ($tickets_found as $ticket_found) {
            if($ticket_found['tickets_id'] != $items_id) {
               $list_tickets[] = $ticket_found['tickets_id'];
            }
         }
         if($items_id != $first_tickets_id) {
            $list_tickets[] = $first_tickets_id;
         }
         if(empty($list_tickets)) {
            $list_tickets = 0;
         }
         $follow = new self();
         $follows = $follow->find([
            'OR' => [

               'AND' => [
                  'tickets_id' => $list_tickets,
                  'targets_id' => -1
               ],
               ['targets_id' => $items_id],
               ['tickets_id' => $items_id],

            ],
            'AND' => [
               'OR' => [

                  'AND' => [
                     'tickets_id' => $list_tickets,
                     'targets_id' => -1
                  ],
                  ['targets_id' => $items_id],
                  ['tickets_id' => $items_id],

               ]
            ]
                                  ]);
      }
      $data = [];
      foreach ($follows as $follow) {
         $follow['can_edit'] = false;
         $data[$follow['date']."_interTicketFollowup_".$follow['id']] = [
            'type' => self::getType(),
            'item' => $follow
         ];
      }

      return $data;
   }

   function showForm($ID, $options = []) {
      global $CFG_GLPI;

      if ($this->isNewItem()) {
         $this->getEmpty();
      }

      if (!isset($options['item']) && isset($options['parent'])) {
         //when we came from aja/viewsubitem.php
         $options['item'] = $options['parent'];
      }
      $options['formoptions'] = ($options['formoptions'] ?? '') . ' data-track-changes=true';

      $item = $options['item'];
      $this->item = $item;

      if ($ID > 0) {
         $this->check($ID, READ);
      } else {
         // Create item
         $options['itemtype'] = $item->getType();
         $options['tickets_id'] = $item->getField('id');
         $this->check(-1, CREATE, $options);
      }
//      $tech = (Session::haveRight(self::$rightname, self::ADDALLTICKET)
//               || $item->isUser(CommonITILActor::ASSIGN, Session::getLoginUserID())
//               || (isset($_SESSION["glpigroups"])
//                   && $item->haveAGroup(CommonITILActor::ASSIGN, $_SESSION['glpigroups'])));
      $tech = true;

      $requester = ($item->isUser(CommonITILActor::REQUESTER, Session::getLoginUserID())
                    || (isset($_SESSION["glpigroups"])
                        && $item->haveAGroup(CommonITILActor::REQUESTER, $_SESSION['glpigroups'])));

      $reopen_case = false;
      if ($this->isNewID($ID)) {
         if ($item->canReopen()) {
            $reopen_case = true;
            echo "<div class='center b'>".__('If you want to reopen the ticket, you must specify a reason')."</div>";
         }

         // the reqester triggers the reopening on close/solve/waiting status
         if ($requester
             && in_array($item->fields['status'], $item::getReopenableStatusArray())) {
            $reopen_case = true;
         }
      }

      $cols    = 100;
      $rows    = 10;

      if ($tech) {
         $this->showFormHeader($options);

         $rand       = mt_rand();
         $content_id = "content$rand";

         echo "<tr class='tab_bg_1'>";
         echo "<td rowspan='3'>";

         Html::textarea(['name'              => 'content',
                         'value'             => $this->fields["content"],
                         'rand'              => $rand,
                         'editor_id'         => $content_id,
                         'enable_fileupload' => true,
                         'enable_richtext'   => true,
                         'cols'              => $cols,
                         'rows'              => $rows]);

         if ($this->fields["date"]) {
            echo "</td><td>"._n('Date', 'Dates', 1)."</td>";
            echo "<td>".Html::convDateTime($this->fields["date"]);
         } else {

            echo "</td><td colspan='2'>&nbsp;";
         }
         echo Html::hidden('itemtype', ['value' => $item->getType()]);
         echo Html::hidden('tickets_id', ['value' => $item->getID()]);
         // Reopen case
         if ($reopen_case) {
            echo "<input type='hidden' name='add_reopen' value='1'>";
         }

         echo "</td></tr>\n";

         echo "<tr class='tab_bg_1'></tr>";
         echo "<tr class='tab_bg_1' style='vertical-align: top'>";
         echo "<td colspan='4'>";
//         echo "<div class='fa-label'>
//            <i class='fas fa-reply fa-fw'
//               title='"._n('Followup template', 'Followup templates', Session::getPluralNumber())."'></i>";
//         $this->fields['itilfollowuptemplates_id'] = 0;
////         ITILFollowupTemplate::dropdown([
////                                           'value'     => $this->fields['itilfollowuptemplates_id'],
////                                           'entity'    => $this->getEntityID(),
////                                           'on_change' => "itilfollowuptemplate_update$rand(this.value)"
////                                        ]);
//         echo "</div>";

//         $ajax_url = $CFG_GLPI["root_doc"]."/ajax/itilfollowup.php";
//         $JS = <<<JAVASCRIPT
//            function itilfollowuptemplate_update{$rand}(value) {
//               $.ajax({
//                  url: '{$ajax_url}',
//                  type: 'POST',
//                  data: {
//                     itilfollowuptemplates_id: value
//                  }
//               }).done(function(data) {
//                  var requesttypes_id = isNaN(parseInt(data.requesttypes_id))
//                     ? 0
//                     : parseInt(data.requesttypes_id);
//
//                  // set textarea content
//                  if (tasktinymce = tinymce.get("{$content_id}")) {
//                     tasktinymce.setContent(data.content);
//                  }
//                  // set category
//                  $("#dropdown_requesttypes_id{$rand}").trigger("setValue", requesttypes_id);
//                  // set is_private
//                  $("#is_privateswitch{$rand}")
//                     .prop("checked", data.is_private == "0"
//                        ? false
//                        : true);
//               });
//            }
//JAVASCRIPT;
//         echo Html::scriptBlock($JS);

         echo "<div class='fa-label'>
            <i class='fas fa-bullseye fa-fw'
               title='".__('Target followup','metademands')."'></i>";
         Dropdown::showFromArray('targets_id',self::getTargets($item->getField('id')),[]);
//         RequestType::dropdown([
//                                  'value'     => $this->fields["requesttypes_id"],
//                                  'condition' => ['is_active' => 1, 'is_itilfollowup' => 1],
//                                  'rand'      => $rand,
//                               ]);
         echo "</div>";

         echo "<div class='fa-label'>
            <i class='fas fa-lock fa-fw' title='".__('Private')."'></i>";
         echo "<span class='switch pager_controls'>
            <label for='is_privateswitch$rand' title='".__('Private')."'>
               <input type='hidden' name='is_private' value='0'>
               <input type='checkbox' id='is_privateswitch$rand' name='is_private' value='1'".
              ($this->fields["is_private"]
                 ? "checked='checked'"
                 : "")."
               >
               <span class='lever'></span>
            </label>
         </span>";
         echo "</div></td></tr>";

         $this->showFormButtons($options);

      } else {
         $options['colspan'] = 1;

         $this->showFormHeader($options);

         $rand = mt_rand();
         $rand_text = mt_rand();
         $content_id = "content$rand";
         echo "<tr class='tab_bg_1'>";
         echo "<td class='middle right'>".__('Description')."</td>";
         echo "<td class='center middle'>";

         Html::textarea(['name'              => 'content',
                         'value'             => $this->fields["content"],
                         'rand'              => $rand_text,
                         'editor_id'         => $content_id,
                         'enable_fileupload' => true,
                         'enable_richtext'   => true,
                         'cols'              => $cols,
                         'rows'              => $rows]);

         echo Html::hidden('itemtype', ['value' => $item->getType()]);
         echo Html::hidden('items_id', ['value' => $item->getID()]);
         echo Html::hidden('requesttypes_id', ['value' => RequestType::getDefault('followup')]);
         // Reopen case
         if ($reopen_case) {
            echo "<input type='hidden' name='add_reopen' value='1'>";
         }

         echo "</td></tr>\n";

         $this->showFormButtons($options);
      }
      return true;
   }

   function prepareInputForAdd($input) {



      if (empty($input['content'])
          && !isset($input['add_close'])
          && !isset($input['add_reopen'])) {
         Session::addMessageAfterRedirect(__("You can't add a followup without description"),
                                          false, ERROR);
         return false;
      }


      $input['_close'] = 0;

      if (!isset($input["users_id"])) {
         $input["users_id"] = 0;
         if ($uid = Session::getLoginUserID()) {
            $input["users_id"] = $uid;
         }
      }




      if (!isset($input["is_private"])) {
         $input['is_private'] = 0;
      }



      $itemtype = $input['itemtype'];
      $input['timeline_position'] = $itemtype::getTimelinePosition($input["items_id"], $this->getType(), $input["users_id"]);

      if (!isset($input['date'])) {
         $input["date"] = $_SESSION["glpi_currenttime"];
      }
      return $input;
   }
}
