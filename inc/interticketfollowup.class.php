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
 * Class PluginMetademandsInterticketfollowup
 */
class PluginMetademandsInterticketfollowup extends CommonITILObject {

   static $rightname = 'plugin_metademands';


   /**
    * functions mandatory
    * getTypeName(), canCreate(), canView()
    *
    * @param int $nb
    *
    * @return string
    */
   static function getTypeName($nb = 0) {

      return _n('Inter ticket followup', 'Inter ticket followups', $nb, 'metademands');
   }


   /**
    * @param $options
    *
    * @return array
    */
   static function addToTimeline($options) {

      $item      = $options['item'];
      $itemtypes = [];

      $metaValidation = new PluginMetademandsMetademandValidation();
      $ticket_task    = new PluginMetademandsTicket_Task();
      if (($metaValidation->getFromDBByCrit(['tickets_id' => $item->fields['id']])
           || $ticket_task->find(['tickets_id' => $item->fields['id']]) || $ticket_task->find(['parent_tickets_id' => $item->fields['id']]))
          && $_SESSION['glpiactiveprofile']['interface'] == 'central'
          && ($item->fields['status'] != Ticket::SOLVED
              && $item->fields['status'] != Ticket::CLOSED)) {

         $itemtypes['interticketfollowup'] = [
            'type'  => 'PluginMetademandsInterticketfollowup',
            'class' => 'PluginMetademandsInterticketfollowup',
            'icon'  => 'fas fa-comments',
            'label' => _n('Inter ticket followup', 'Inter ticket followups', 1, 'metademands'),
            'item'  => new PluginMetademandsInterticketfollowup()
         ];
      }

      return $itemtypes;
   }

   /**
    * @return string
    */
   static function getIcon() {
      return 'fas fa-comments';
   }


   /**
    * @param $tickets_id
    *
    * @return mixed
    */
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


   /**
    * @param $items_id
    *
    * @return array
    * @throws \GlpitestSQLError
    */
   static function getTargets($items_id) {
      $first_tickets_id       = self::getFirstTicket($items_id);
      $ticket_metademand      = new PluginMetademandsTicket_Metademand();
      $ticket_metademand_data = $ticket_metademand->find(['tickets_id' => $first_tickets_id]);
      $tickets_found          = [];
      // If ticket is Parent : Check if all sons ticket are closed
      if (count($ticket_metademand_data)) {
         $ticket_metademand_data = reset($ticket_metademand_data);
         $tickets_found          = PluginMetademandsTicket::getSonTickets($first_tickets_id,
                                                                          $ticket_metademand_data['plugin_metademands_metademands_id']);
         $targets                = [];
         $ticket                 = new Ticket();
         $targets[-1]            = __('All tickets', 'metademands');
         if ($first_tickets_id != $items_id) {
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


   /**
    * @param $item
    *
    * @return array
    * @throws \GlpitestSQLError
    */
   static function getlistItems($item) {

      $self   = new self();
      $ticket = $item['item'];

      $items_id = $item['item']->fields['id'];
      $origin_time_line = $item['timeline'];
      $first_tickets_id       = self::getFirstTicket($items_id);
      $ticket_metademand      = new PluginMetademandsTicket_Metademand();
      $ticket_metademand_data = $ticket_metademand->find(['tickets_id' => $first_tickets_id]);
      $tickets_found          = [];
      // If ticket is Parent : Check if all sons ticket are closed
      if (count($ticket_metademand_data)) {
         $ticket_metademand_data = reset($ticket_metademand_data);
         $tickets_found          = PluginMetademandsTicket::getSonTickets($first_tickets_id,
                                                                          $ticket_metademand_data['plugin_metademands_metademands_id']);
         $list_tickets           = [];
         foreach ($tickets_found as $ticket_found) {
            if ($ticket_found['tickets_id'] != $items_id) {
               $list_tickets[] = $ticket_found['tickets_id'];
            }
         }
         if ($items_id != $first_tickets_id) {
            $list_tickets[] = $first_tickets_id;
         }
         if (empty($list_tickets)) {
            $list_tickets = 0;
         }
         $follow  = new self();
         $follows = $follow->find([
                                     'OR'  => [

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

      foreach ($follows as $follow) {
         $follow['can_edit']                                                          = ($follow['tickets_id'] == $items_id && $follow['users_id'] == Session::getLoginUserID()) ? true : false;
         $item['timeline'][self::getType() . "_" . $follow['id']] = [
            'type'     => self::getType(),
            'item'     => $follow,
            'itiltype' => 'Interticketfollowup'
         ];
      }
      $document_item_obj = new Document_Item();
      //add documents to timeline
      $document_obj   = new Document();
      $document_items = $document_item_obj->find([
                                                    $self->getInterAssociatedDocumentsCriteria($ticket, $list_tickets),
                                                    'timeline_position' => ['>', CommonITILObject::NO_TIMELINE]
                                                 ]);
      foreach ($document_items as $document_item) {
         $document_obj->getFromDB($document_item['documents_id']);

         $date = $document_item['date'] ?? $document_item['date_creation'];

         $item_doc         = $document_obj->fields;
         $item_doc['date'] = $date;
         // #1476 - set date_mod and owner to attachment ones
         $item_doc['date_mod']          = $document_item['date_mod'];
         $item_doc['users_id']          = $document_item['users_id'];
         $item_doc['documents_item_id'] = $document_item['id'];

         $item_doc['timeline_position'] = $document_item['timeline_position'];
         $docpath = GLPI_DOC_DIR . "/" .  $document_obj->fields['filepath'];
         $is_image = Document::isImage($docpath);
         $sub_document = ['type' => 'Document_Item', 'item' => $item_doc];
         if ($is_image) {
            $sub_document['_is_image'] = true;
            $sub_document['_size'] = getimagesize($docpath);
         }
         $item['timeline'][$document_item['itemtype']. "_" . $document_item['items_id']]['documents'][]
            = $sub_document;
      }

      $timeline = $item['timeline'];

//      return $timeline;

   }


   /**
    * @param $ID
    * @param $options   array
    **/
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

      $item       = $options['item'];
      $this->item = $item;

      if ($ID > 0) {
         $this->check($ID, READ);
      } else {
         // Create item
         $options['itemtype']   = $item->getType();
         $options['tickets_id'] = $item->getField('id');
         $this->check(-1, CREATE, $options);
      }

      $requester = ($item->isUser(CommonITILActor::REQUESTER, Session::getLoginUserID())
                    || (isset($_SESSION["glpigroups"])
                        && $item->haveAGroup(CommonITILActor::REQUESTER, $_SESSION['glpigroups'])));

      $reopen_case = false;
      if ($this->isNewID($ID)) {
         if ($item->canReopen()) {
            $reopen_case = true;
            echo "<div class='center b'>" . __('If you want to reopen the ticket, you must specify a reason') . "</div>";
         }

         // the reqester triggers the reopening on close/solve/waiting status
         if ($requester
             && in_array($item->fields['status'], $item::getReopenableStatusArray())) {
            $reopen_case = true;
         }
      }

      $cols = 100;
      $rows = 10;

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
         echo "</td><td>" . _n('Date', 'Dates', 1) . "</td>";
         echo "<td>" . Html::convDateTime($this->fields["date"]);
      } else {

         echo "</td><td colspan='2'>&nbsp;";
      }
      echo Html::hidden('itemtype', ['value' => $item->getType()]);
      echo Html::hidden('tickets_id', ['value' => $item->getID()]);
      // Reopen case
      if ($reopen_case) {
         echo Html::hidden('add_reopen', ['value' => '1']);
      }

      echo "</td></tr>\n";

      echo "<tr class='tab_bg_1'></tr>";
      echo "<tr class='tab_bg_1' style='vertical-align: top'>";
      echo "<td colspan='4'>";

      echo "<div class='fa-label'>
            <i class='fas fa-bullseye fa-fw'
               title='" . __('Target followup', 'metademands') . "'></i>";
      Dropdown::showFromArray('targets_id', self::getTargets($item->getField('id')), []);
      //         RequestType::dropdown([
      //                                  'value'     => $this->fields["requesttypes_id"],
      //                                  'condition' => ['is_active' => 1, 'is_itilfollowup' => 1],
      //                                  'rand'      => $rand,
      //                               ]);
      echo "</div>";


      echo "</td></tr>";

      $this->showFormButtons($options);
   }


   /**
    * Prepare input datas for adding the item
    *
    * @param array $input datas used to add the item
    *
    * @return array the modified $input array
    **/
   function prepareInputForAdd($input) {

      if (empty($input['content'])
      ) {
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

      $itemtype                   = $input['itemtype'];
      $input['timeline_position'] = $itemtype::getTimelinePosition($input["tickets_id"], ITILFollowup::getType(), $input["users_id"]);

      if (!isset($input['date'])) {
         $input["date"] = $_SESSION["glpi_currenttime"];
      }
      return $input;
   }


   function post_addItem() {

      global $CFG_GLPI;

      // Add screenshots if needed, without notification
      $this->input = $this->addFiles($this->input, [
         'force_update'  => true,
         'name'          => 'content',
         'content_field' => 'content',
         'date'          => $this->fields['date'],
      ]);

      // Add documents if needed, without notification
      $this->input = $this->addFiles($this->input, [
         'force_update' => true,
         'date'         => $this->fields['date'],
      ]);

      $donotif = !isset($this->input['_disablenotif']) && $CFG_GLPI["use_notifications"];

      //      // Check if stats should be computed after this change
      //      $no_stat = isset($this->input['_do_not_compute_takeintoaccount']);
      $no_stat = true;

      $parentitem = new Ticket();
      $parentitem->updateDateMod(
         $this->input["tickets_id"],
         $no_stat,
         $this->input["users_id"]
      );


      //manage reopening of ITILObject
      $reopened = false;
      if (!isset($this->input['_status'])) {
         $this->input['_status'] = $parentitem->fields["status"];
      }

      if ($donotif) {
         $options = ['interticketfollowup_id' => $this->fields["id"],
                     'ticket'                 => $parentitem,
                     'entities_id'            => $parentitem->getEntityID()
         ];
         NotificationEvent::raiseEvent("add_interticketfollowup", $this, $options);
      }

      // Add log entry in the ITILObject
      $changes = [
         0,
         '',
         $this->fields['id'],
      ];

      Log::history($this->getField('items_id'), get_class($parentitem), $changes, $this->getType(),
                   Log::HISTORY_ADD_SUBITEM);
   }

   /**
    * Returns criteria that can be used to get documents related to current instance.
    *
    * @return array
    */
   public function getInterAssociatedDocumentsCriteria($item, $list_tickets, $bypass_rights = false): array {

      $items_id = $item->getID();
      // documents associated to followups
      if ($bypass_rights || self::canView()) {
         //         $fup_crits = [
         //            self::getTableField('tickets_id') => $item->getID(),
         //         ];

         $fup_crits[] = [
            'OR' => [

               'AND' => [
                  self::getTableField('tickets_id') => $list_tickets,
                  self::getTableField('targets_id') => -1
               ],
               [self::getTableField('targets_id') => $items_id],
               [self::getTableField('tickets_id') => $items_id],

            ],
         ];


         $or_crits[] = [
            Document_Item::getTableField('itemtype') => self::getType(),
            Document_Item::getTableField('items_id') => new QuerySubQuery(
               [
                  'SELECT' => 'id',
                  'FROM'   => self::getTable(),
                  'WHERE'  => $fup_crits,
               ]
            ),
         ];
      }

      return ['OR' => $or_crits];
   }

   public static function getTaskClass() {
      // TODO: Implement getTaskClass() method.
   }

   public static function getDefaultValues($entity = 0) {
      // TODO: Implement getDefaultValues() method.
   }

   public static function getItemLinkClass(): string {
      // TODO: Implement getItemLinkClass() method.
   }

   public static function getContentTemplatesParametersClass(): string {
      // TODO: Implement getContentTemplatesParametersClass() method.
   }
}
