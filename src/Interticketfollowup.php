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

namespace GlpiPlugin\Metademands;

use CommonITILObject;
use Document;
use Document_Item;
use ITILFollowup;
use Log;
use NotificationEvent;
use Session;

/**
 * Class Interticketfollowup
 */
use Glpi\Application\View\TemplateRenderer;
use Glpi\ContentTemplates\Parameters\CommonITILObjectParameters;
use Glpi\DBAL\QuerySubQuery;

class Interticketfollowup extends CommonITILObject
{
    public static $rightname = 'plugin_metademands_followup';


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
        return _n('Inter ticket followup', 'Inter ticket followups', $nb, 'metademands');
    }


    /**
     * @param $options
     *
     * @return array
     */
    public static function addToTimeline($options)
    {
        $item      = $options['item'];
        $itemtypes = [];

        $metaValidation = new MetademandValidation();
        $ticket_task    = new Ticket_Task();
        if ($item->fields['id'] > 0 && (($metaValidation->getFromDBByCrit(['tickets_id' => $item->fields['id']])
              && $metaValidation->fields['validate'] == MetademandValidation::TICKET_CREATION)
             || $ticket_task->find(['tickets_id' => $item->fields['id']])
             || $ticket_task->find(['parent_tickets_id' => $item->fields['id']]))
            && $_SESSION['glpiactiveprofile']['interface'] == 'central'
            && ($item->fields['status'] != \Ticket::SOLVED
                && $item->fields['status'] != \Ticket::CLOSED)
            && Session::haveRight("plugin_metademands_followup", READ)) {
            $itemtypes['interticketfollowup'] = [
                'type'  => Interticketfollowup::class,
                'class' => Interticketfollowup::class,
                'icon'  => 'ti ti-message',
                'label' => _n('Inter ticket followup', 'Inter ticket followups', 1, 'metademands'),
                'item'  => new Interticketfollowup(),
            ];
        }

        return $itemtypes;
    }

    /**
     * @return string
     */
    public static function getIcon()
    {
        return 'ti ti-message';
    }


    /**
     * @param $tickets_id
     *
     * @return mixed
     */
    public static function getFirstTicket($tickets_id)
    {
        $ticket_metademand      = new Ticket_Metademand();
        $ticket_metademand_data = $ticket_metademand->getFromDBByCrit(['tickets_id' => $tickets_id]);
        if ($ticket_metademand_data) {
            return $tickets_id;
        } else {
            $ticket_task = new Ticket_Task();
            $ticket_task->getFromDBByCrit(['tickets_id' => $tickets_id]);
            if (isset($ticket_task->fields['parent_tickets_id'])
                && $ticket_task->fields['parent_tickets_id'] > 0) {
                return self::getFirstTicket($ticket_task->fields['parent_tickets_id']);
            }
        }
        return false;
    }


    /**
     * @param $items_id
     *
     * @return array
     * @throws GlpitestSQLError
     */
    public static function getTargets($items_id)
    {
        $targets                = [];
        $first_tickets_id = self::getFirstTicket($items_id);

        if ($first_tickets_id) {
            $ticket_metademand      = new Ticket_Metademand();
            $ticket_metademand_data = $ticket_metademand->find(['tickets_id' => $first_tickets_id]);
            $tickets_found          = [];
            // If ticket is Parent : Check if all sons ticket are closed
            if (count($ticket_metademand_data)) {
                $ticket_metademand_data = reset($ticket_metademand_data);
                $tickets_found          = Ticket::getSonTickets(
                    $first_tickets_id,
                    $ticket_metademand_data['plugin_metademands_metademands_id'],
                    [],
                    true,
                );

                $ticket                 = new \Ticket();
                $targets[0]             = __('All tickets', 'metademands');
                if ($first_tickets_id != $items_id) {
                    $ticket->getFromDB($first_tickets_id);
                    $targets[$first_tickets_id] = $ticket->getFriendlyName();
                }
                foreach ($tickets_found as $ticket_found) {
                    if ($ticket_found['tickets_id'] != $items_id) {
                        if ($ticket->getFromDB($ticket_found['tickets_id'])) {
                            if ($ticket->fields['status'] != \Ticket::SOLVED
                                && $ticket->fields['status'] != \Ticket::CLOSED) {
                                $targets[$ticket_found['tickets_id']] = $ticket->getFriendlyName();
                            }
                        }


                    }
                }
            }
        }
        return $targets;
    }


    /**
     * @param $item
     *
     * @return array
     * @throws GlpitestSQLError
     */
    public static function getlistItems($item)
    {
        $self   = new self();
        $ticket = $item['item'];

        $items_id         = $item['item']->fields['id'];
        $origin_time_line = $item['timeline'];
        $first_tickets_id = self::getFirstTicket($items_id);
        if ($first_tickets_id) {
            $ticket_metademand      = new Ticket_Metademand();
            $ticket_metademand_data = $ticket_metademand->find(['tickets_id' => $first_tickets_id]);
            $tickets_found          = [];
            // If ticket is Parent : Check if all sons ticket are closed
            if (count($ticket_metademand_data)) {
                $ticket_metademand_data = reset($ticket_metademand_data);
                $tickets_found          = Ticket::getSonTickets(
                    $first_tickets_id,
                    $ticket_metademand_data['plugin_metademands_metademands_id']
                );
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
                            'targets_id' => 0,
                        ],
                        ['targets_id' => $items_id],
                        ['tickets_id' => $items_id],

                    ],
                    'AND' => [
                        'OR' => [

                            'AND' => [
                                'tickets_id' => $list_tickets,
                                'targets_id' => 0,
                            ],
                            ['targets_id' => $items_id],
                            ['tickets_id' => $items_id],

                        ],
                    ],
                ]);
            }

            foreach ($follows as $follow) {
                $follow['can_edit']                                      = ($follow['tickets_id'] == $items_id && $follow['users_id'] == Session::getLoginUserID()) ? true : false;
                $item['timeline'][self::getType() . "_" . $follow['id']] = [
                    'type'     => self::getType(),
                    'item'     => $follow,
                    'itiltype' => 'Interticketfollowup',
                ];
            }
            $document_item_obj = new Document_Item();
            //add documents to timeline
            $document_obj   = new Document();
            $doc_crit = $self->getInterAssociatedDocumentsCriteria($ticket, $list_tickets, Session::isCron());
            if ($doc_crit) {
                $document_items = $document_item_obj->find([
                    $doc_crit,
                    'timeline_position' => ['>', CommonITILObject::NO_TIMELINE],
                ]);
                foreach ($document_items as $document_item) {
                    $document_obj->getFromDB($document_item['documents_id']);

                    $date = $document_item['date'] ?? $document_item['date_creation'];

                    $item_doc = $document_obj->fields;
                    $item_doc['date'] = $date;
                    // #1476 - set date_mod and owner to attachment ones
                    $item_doc['date_mod'] = $document_item['date_mod'];
                    $item_doc['users_id'] = $document_item['users_id'];
                    $item_doc['documents_item_id'] = $document_item['id'];

                    $item_doc['timeline_position'] = $document_item['timeline_position'];
                    $docpath = GLPI_DOC_DIR . "/" . $document_obj->fields['filepath'];
                    $is_image = Document::isImage($docpath);
                    $sub_document = ['type' => 'Document_Item', 'item' => $item_doc];
                    if ($is_image) {
                        $sub_document['_is_image'] = true;
                        $sub_document['_size'] = getimagesize($docpath);
                    }
                    $item['timeline'][$document_item['itemtype'] . "_" . $document_item['items_id']]['documents'][]
                        = $sub_document;
                }
            }
        }
        return $item;
    }


    /**
     * @param $ID
     * @param $options   array
     **/
    public function showForm($ID, $options = [])
    {
        if ($this->isNewItem()) {
            $this->getEmpty();
        }

        $item       = $options['parent'];

        TemplateRenderer::getInstance()->display('@metademands/interticketfollowup_form.html.twig', [
            'item'               => $options['parent'],
            'subitem'            => $this,
            'targets_list'    => self::getTargets($item->getField('id')),
            'targets_name'    => __('Target followup', 'metademands'),
            'action'          => PLUGIN_METADEMANDS_WEBDIR . '/front/interticketfollowup.form.php',
        ]);
    }


    /**
     * Prepare input datas for adding the item
     *
     * @param array $input datas used to add the item
     *
     * @return array the modified $input array
     **/
    public function prepareInputForAdd($input)
    {
        if (empty($input['content'])
        ) {
            Session::addMessageAfterRedirect(
                __("You can't add a followup without description"),
                false,
                ERROR
            );
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


    public function post_addItem()
    {
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

        $parentitem = new \Ticket();
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
                'entities_id'            => $parentitem->getEntityID(),
            ];
            NotificationEvent::raiseEvent("add_interticketfollowup", $this, $options);
        }

        // Add log entry in the ITILObject
        $changes = [
            0,
            '',
            $this->fields['id'],
        ];

        Log::history(
            $this->getField('tickets_id'),
            get_class($parentitem),
            $changes,
            $this->getType(),
            Log::HISTORY_ADD_SUBITEM
        );
    }

    /**
     * Returns criteria that can be used to get documents related to current instance.
     *
     * @return array
     */
    public function getInterAssociatedDocumentsCriteria($item, $list_tickets, $bypass_rights = false): array
    {
        $items_id = $item->getID();
        $or_crits = [];
        // documents associated to followups
        if ($bypass_rights || self::canView()) {
            //         $fup_crits = [
            //            self::getTableField('tickets_id') => $item->getID(),
            //         ];

            $fup_crits[] = [
                'OR' => [

                    'AND' => [
                        self::getTableField('tickets_id') => $list_tickets,
                        self::getTableField('targets_id') => 0,
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
        if (empty($or_crits)) {
            return [];
        }
        return ['OR' => $or_crits];
    }

    public static function getDefaultValues($entity = 0)
    {
        // TODO: Implement getDefaultValues() method.
    }

    public static function getItemLinkClass(): string
    {
        // TODO: Implement getItemLinkClass() method.
    }

    public static function getContentTemplatesParametersClassInstance(): CommonITILObjectParameters
    {
        // TODO: Implement getContentTemplatesParametersClassInstance() method.
    }
}
