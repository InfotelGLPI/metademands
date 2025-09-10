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
    die("Sorry. You can't access this file directly");
}

class PluginMetademandsNotificationTargetInterticketfollowup extends NotificationTarget
{
    public $private_profiles = [];
    const TARGET_TICKET_TECH      = 5300;
    const TARGET_TICKET_GROUPTECH = 5301;

    public $html_tags = [

       '##interticketfollowup.description##',

    ];

    /**
     * @param $entity (default '')
     * @param $event (default '')
     * @param $object (default null)
     * @param $options   array
     **/
    public function __construct($entity = '', $event = '', $object = null, $options = [])
    {
        parent::__construct($entity, $event, $object, $options);
    }


    public function validateSendTo($event, array $infos, $notify_me = false, $emitter = null)
    {
        // Check global ones for notification to myself
        if (!parent::validateSendTo($event, $infos, $notify_me, $emitter)) {
            return false;
        }

        return true;
    }

    /**
     * Get notification subject prefix
     *
     * @param $event Event name (default '')
     *
     * @return string
     **/
    public function getSubjectPrefix($event = '')
    {
        $perso_tag = trim(Entity::getUsedConfig(
            'notification_subject_tag',
            $this->getEntity(),
            '',
            ''
        ));

        if (empty($perso_tag)) {
            $perso_tag = 'GLPI';
        }
        return sprintf("[$perso_tag #%07d] ", $this->obj->getField('id'));
    }

    /**
     * Get events related to Itil Object
     *
     * @return array of events (event key => event label)
     **@since 9.2
     *
     */
    public function getEvents()
    {
        $events = [
           'add_interticketfollowup' => __("New inter ticket followup", 'metademands'),
         //         'update_followup'   => __('Update of a inter ticket followup'),
         //         'delete_followup'   => __('Deletion of a inter ticket followup'),
        ];

        asort($events);
        return $events;
    }

    /**
     * Get additionnals targets for holiday
     */
    public function addNotificationTargets($event = '')
    {
        $this->addTarget(self::TARGET_TICKET_TECH, __('Ticket technician of target tickets', 'metademands'));
        $this->addTarget(self::TARGET_TICKET_GROUPTECH, __('Ticket group technician of target tickets', 'metademands'));
    }

    public function addSpecificTargets($data, $options)
    {
        $this->obj = $options['ticket'];
        switch ($data['items_id']) {
            case self::TARGET_TICKET_TECH:
                return $this->addTechOfTargets($options);
            case self::TARGET_TICKET_GROUPTECH:
                return $this->addGroupOfTargets($options);
        }
    }


    public function addGroupOfTargets($data)
    {
        global $DB;
        $this->obj = $data['ticket'];
        $item      = $this->obj;
        $inter     = new PluginMetademandsInterticketfollowup();
        $inter->getFromDB($data['interticketfollowup_id']);

        if ($inter->fields['targets_id'] == 0) {
            $first_tickets_id = PluginMetademandsInterticketfollowup::getFirstTicket($item->fields['id']);
            if ($first_tickets_id) {
                $ticket_metademand      = new PluginMetademandsTicket_Metademand();
                $ticket_metademand_data = $ticket_metademand->find(['tickets_id' => $first_tickets_id]);
                $tickets_found          = [];
                // If ticket is Parent : Check if all sons ticket are closed
                $list_tickets = "";
                if (count($ticket_metademand_data)) {
                    $ticket_metademand_data = reset($ticket_metademand_data);
                    $tickets_found          = PluginMetademandsTicket::getSonTickets(
                        $first_tickets_id,
                        $ticket_metademand_data['plugin_metademands_metademands_id']
                    );
                    $list_tickets           = [];
                    foreach ($tickets_found as $ticket_found) {
                        if ($ticket_found['tickets_id'] != $item->fields['id']) {
                            $list_tickets[] = $ticket_found['tickets_id'];
                        }
                    }
                    if ($item->fields['id'] != $first_tickets_id) {
                        $list_tickets[] = $first_tickets_id;
                    }
                }
                $tickets_id = $list_tickets;
            }
        } else {
            $tickets_id = [$inter->fields['targets_id'], $inter->fields['tickets_id']];
        }

        if ($tickets_id) {
            $ticket = new Ticket();

            $grouplinktable = "glpi_groups_tickets";
            $fkfield        = $ticket->getForeignKeyField();

            $iterator = $DB->request([
                                        'SELECT' => 'groups_id',
                                        'FROM'   => $grouplinktable,
                                        'WHERE'  => [
                                           $fkfield => $tickets_id,
                                           'type'   => CommonITILActor::ASSIGN
                                        ]
                                     ]);

            if (count($iterator)) {
                foreach ($iterator as $data) {
                    //Add the group in the notified users list
                    $this->addForGroup(0, $data['groups_id']);
                }
            }
        }
    }

    public function addTechOfTargets($data)
    {
        global $DB, $CFG_GLPI;
        $this->obj = $data['ticket'];
        $item = $this->obj;
        $type = CommonITILActor::ASSIGN;
        $inter = new PluginMetademandsInterticketfollowup();
        $inter->getFromDB($data['interticketfollowup_id']);
        if ($inter->fields['targets_id'] == 0) {
            $first_tickets_id = PluginMetademandsInterticketfollowup::getFirstTicket($inter->fields['tickets_id']);
            $ticket_metademand = new PluginMetademandsTicket_Metademand();
            $ticket_metademand_data = $ticket_metademand->find(['tickets_id' => $first_tickets_id]);
            $tickets_found = [];
            // If ticket is Parent : Check if all sons ticket are closed
            if (count($ticket_metademand_data)) {
                $ticket_metademand_data = reset($ticket_metademand_data);
                $tickets_found = PluginMetademandsTicket::getSonTickets(
                    $first_tickets_id,
                    $ticket_metademand_data['plugin_metademands_metademands_id']
                );
                $list_tickets = [];
                foreach ($tickets_found as $ticket_found) {
                    if ($ticket_found['tickets_id'] != $inter->fields['tickets_id']) {
                        $list_tickets[] = $ticket_found['tickets_id'];
                    }
                }
                if ($inter->fields['tickets_id'] != $first_tickets_id) {
                    $list_tickets[] = $first_tickets_id;
                }
            }
            $tickets_id = $list_tickets;
        } else {
            $tickets_id = [$inter->fields['targets_id'], $inter->fields['tickets_id']];
        }


        $userlinktable = "glpi_tickets_users";
        $fkfield = $this->obj->getForeignKeyField();

        //Look for the user by his id
        $criteria = ['LEFT JOIN' => [
                User::getTable() => [
                    'ON' => [
                        $userlinktable => 'users_id',
                        User::getTable() => 'id'
                    ]
                ]
            ]] + $this->getDistinctUserCriteria() + $this->getProfileJoinCriteria();
        $criteria['FROM'] = $userlinktable;
        $criteria['FIELDS'] = array_merge(
            $criteria['FIELDS'],
            [
                "$userlinktable.use_notification AS notif",
                "$userlinktable.alternative_email AS altemail"
            ]
        );
        $criteria['WHERE']["$userlinktable.$fkfield"] = $tickets_id;
        $criteria['WHERE']["$userlinktable.type"] = $type;

        $iterator = $DB->request($criteria);

        if (count($iterator)) {
            foreach ($iterator as $data) {

                //Add the user email and language in the notified users list
                if ($data['notif']) {
                    $author_email = UserEmail::getDefaultForUser($data['users_id']);
                    $author_lang = $data["language"];
                    $author_id = $data['users_id'];

                    if (!empty($data['altemail'])
                        && ($data['altemail'] != $author_email)
                        && NotificationMailing::isUserAddressValid($data['altemail'])) {
                        $author_email = $data['altemail'];
                    }
                    if (empty($author_lang)) {
                        $author_lang = $CFG_GLPI["language"];
                    }
                    if (empty($author_id)) {
                        $author_id = -1;
                    }

                    $user = [
                        'language' => $author_lang,
                        'users_id' => $author_id
                    ];
                    if ($this->isMailMode()) {
                        $user['email'] = $author_email;
                    }
                    $this->addToRecipientsList($user);
                }
            }
        }

        // Anonymous user
        $iterator = $DB->request([
                                    'SELECT' => 'alternative_email',
                                    'FROM'   => $userlinktable,
                                    'WHERE'  => [
                                       $fkfield           => $this->obj->fields['id'],
                                       'users_id'         => 0,
                                       'use_notification' => 1,
                                       'type'             => $type
                                    ]
                                 ]);
        if (count($iterator)) {
            foreach ($iterator as $data) {
                if ($this->isMailMode()) {
                    if (NotificationMailing::isUserAddressValid($data['alternative_email'])) {
                        $this->addToRecipientsList([
                            'email' => $data['alternative_email'],
                            'language' => $CFG_GLPI["language"],
                            'users_id' => -1
                        ]);
                    }
                }
            }
        }
    }


    public function addDataForTemplate($event, $options = [])
    {
        $events    = $this->getAllEvents();
        $this->obj = $options['ticket'];
        $objettype = strtolower($this->obj->getType());

        // Get data from ITIL objects

        $this->data = $this->getDataForObject($this->obj, $options);


        $this->data["##$objettype.action##"] = $events[$event];


        $this->getTags();

        foreach ($this->tag_descriptions[parent::TAG_LANGUAGE] as $tag => $values) {
            if (!isset($this->data[$tag])) {
                $this->data[$tag] = $values['label'];
            }
        }
    }


    /**
     * Get data from an item
     *
     * @param CommonDBTM $item Object instance
     * @param array      $options Options
     * @param boolean    $simple (false by default)
     *
     * @return array
     **/
    public function getDataForObject(CommonDBTM $item, array $options, $simple = false)
    {
        global $CFG_GLPI, $DB;

        $item      = $options['ticket'];
        $objettype = strtolower($item->getType());

        $data["##$objettype.title##"]       = $item->getField('name');
        $data["##$objettype.content##"]     = $item->getField('content');
        $data["##$objettype.description##"] = $item->getField('content');
        $data["##$objettype.id##"]          = sprintf("%07d", $item->getField("id"));

        $data["##$objettype.url##"]
           = $this->formatURL(
               $options['additionnaloption']['usertype'],
               $objettype . "_" . $item->getField("id")
           );

        $tab = '$1';
        $data["##$objettype.urlapprove##"]
             = $this->formatURL(
                 $options['additionnaloption']['usertype'],
                 $objettype . "_" . $item->getField("id") . "_" .
                 $item->getType() . $tab
             );

        $entity = new Entity();
        if ($entity->getFromDB($this->getEntity())) {
            $data["##$objettype.entity##"]          = $entity->getField('completename');
            $data["##$objettype.shortentity##"]     = $entity->getField('name');
            $data["##$objettype.entity.phone##"]    = $entity->getField('phonenumber');
            $data["##$objettype.entity.fax##"]      = $entity->getField('fax');
            $data["##$objettype.entity.website##"]  = $entity->getField('website');
            $data["##$objettype.entity.email##"]    = $entity->getField('email');
            $data["##$objettype.entity.address##"]  = $entity->getField('address');
            $data["##$objettype.entity.postcode##"] = $entity->getField('postcode');
            $data["##$objettype.entity.town##"]     = $entity->getField('town');
            $data["##$objettype.entity.state##"]    = $entity->getField('state');
            $data["##$objettype.entity.country##"]  = $entity->getField('country');
        }

        $data["##$objettype.storestatus##"] = $item->getField('status');
        $data["##$objettype.status##"]      = $item->getStatus($item->getField('status'));

        $data["##$objettype.urgency##"]  = $item->getUrgencyName($item->getField('urgency'));
        $data["##$objettype.impact##"]   = $item->getImpactName($item->getField('impact'));
        $data["##$objettype.priority##"] = $item->getPriorityName($item->getField('priority'));
        $data["##$objettype.time##"]     = $item->getActionTime($item->getField('actiontime'));

        $data["##$objettype.creationdate##"] = Html::convDateTime($item->getField('date'));
        $data["##$objettype.closedate##"]    = Html::convDateTime($item->getField('closedate'));
        $data["##$objettype.solvedate##"]    = Html::convDateTime($item->getField('solvedate'));
        $data["##$objettype.duedate##"]      = Html::convDateTime($item->getField('time_to_resolve'));

        $data["##$objettype.category##"] = '';
        if ($item->getField('itilcategories_id')) {
            $data["##$objettype.category##"]
               = Dropdown::getDropdownName(
                   'glpi_itilcategories',
                   $item->getField('itilcategories_id')
               );
        }

        $data["##$objettype.authors##"] = '';
        $data['authors']                = [];
        if ($item->countUsers(CommonITILActor::REQUESTER)) {
            $users = [];
            foreach ($item->getUsers(CommonITILActor::REQUESTER) as $tmpusr) {
                $uid      = $tmpusr['users_id'];
                $user_tmp = new User();
                if ($uid
                    && $user_tmp->getFromDB($uid)) {
                    $users[] = $user_tmp->getName();

                    $tmp                    = [];
                    $tmp['##author.id##']   = $uid;
                    $tmp['##author.name##'] = $user_tmp->getName();

                    if ($user_tmp->getField('locations_id')) {
                        $tmp['##author.location##']
                           = Dropdown::getDropdownName(
                               'glpi_locations',
                               $user_tmp->getField('locations_id')
                           );
                    } else {
                        $tmp['##author.location##'] = '';
                    }

                    if ($user_tmp->getField('usertitles_id')) {
                        $tmp['##author.title##']
                           = Dropdown::getDropdownName(
                               'glpi_usertitles',
                               $user_tmp->getField('usertitles_id')
                           );
                    } else {
                        $tmp['##author.title##'] = '';
                    }

                    if ($user_tmp->getField('usercategories_id')) {
                        $tmp['##author.category##']
                           = Dropdown::getDropdownName(
                               'glpi_usercategories',
                               $user_tmp->getField('usercategories_id')
                           );
                    } else {
                        $tmp['##author.category##'] = '';
                    }

                    $tmp['##author.email##']  = $user_tmp->getDefaultEmail();
                    $tmp['##author.mobile##'] = $user_tmp->getField('mobile');
                    $tmp['##author.phone##']  = $user_tmp->getField('phone');
                    $tmp['##author.phone2##'] = $user_tmp->getField('phone2');
                    $data['authors'][]        = $tmp;
                } else {
                    // Anonymous users only in xxx.authors, not in authors
                    $users[] = $tmpusr['alternative_email'];
                }
            }
            $data["##$objettype.authors##"] = implode(', ', $users);
        }

        $data["##$objettype.suppliers##"] = '';
        $data['suppliers']                = [];
        if ($item->countSuppliers(CommonITILActor::ASSIGN)) {
            $suppliers = [];
            foreach ($item->getSuppliers(CommonITILActor::ASSIGN) as $tmpspplier) {
                $sid      = $tmpspplier['suppliers_id'];
                $supplier = new Supplier();
                if ($sid
                    && $supplier->getFromDB($sid)) {
                    $suppliers[] = $supplier->getName();

                    $tmp                          = [];
                    $tmp['##supplier.id##']       = $sid;
                    $tmp['##supplier.name##']     = $supplier->getName();
                    $tmp['##supplier.email##']    = $supplier->getField('email');
                    $tmp['##supplier.phone##']    = $supplier->getField('phonenumber');
                    $tmp['##supplier.fax##']      = $supplier->getField('fax');
                    $tmp['##supplier.website##']  = $supplier->getField('website');
                    $tmp['##supplier.email##']    = $supplier->getField('email');
                    $tmp['##supplier.address##']  = $supplier->getField('address');
                    $tmp['##supplier.postcode##'] = $supplier->getField('postcode');
                    $tmp['##supplier.town##']     = $supplier->getField('town');
                    $tmp['##supplier.state##']    = $supplier->getField('state');
                    $tmp['##supplier.country##']  = $supplier->getField('country');
                    $tmp['##supplier.comments##'] = $supplier->getField('comment');

                    $tmp['##supplier.type##'] = '';
                    if ($supplier->getField('suppliertypes_id')) {
                        $tmp['##supplier.type##']
                           = Dropdown::getDropdownName(
                               'glpi_suppliertypes',
                               $supplier->getField('suppliertypes_id')
                           );
                    }

                    $data['suppliers'][] = $tmp;
                }
            }
            $data["##$objettype.suppliers##"] = implode(', ', $suppliers);
        }

        $data["##$objettype.openbyuser##"] = '';
        if ($item->getField('users_id_recipient')) {
            $user_tmp = new User();
            $user_tmp->getFromDB($item->getField('users_id_recipient'));
            $data["##$objettype.openbyuser##"] = $user_tmp->getName();
        }

        $data["##$objettype.lastupdater##"] = '';
        if ($item->getField('users_id_lastupdater')) {
            $user_tmp = new User();
            $user_tmp->getFromDB($item->getField('users_id_lastupdater'));
            $data["##$objettype.lastupdater##"] = $user_tmp->getName();
        }

        $data["##$objettype.assigntousers##"] = '';
        if ($item->countUsers(CommonITILActor::ASSIGN)) {
            $users = [];
            foreach ($item->getUsers(CommonITILActor::ASSIGN) as $tmp) {
                $uid      = $tmp['users_id'];
                $user_tmp = new User();
                if ($user_tmp->getFromDB($uid)) {
                    $users[$uid] = $user_tmp->getName();
                }
            }
            $data["##$objettype.assigntousers##"] = implode(', ', $users);
        }

        $data["##$objettype.assigntosupplier##"] = '';
        if ($item->countSuppliers(CommonITILActor::ASSIGN)) {
            $suppliers = [];
            foreach ($item->getSuppliers(CommonITILActor::ASSIGN) as $tmp) {
                $uid          = $tmp['suppliers_id'];
                $supplier_tmp = new Supplier();
                if ($supplier_tmp->getFromDB($uid)) {
                    $suppliers[$uid] = $supplier_tmp->getName();
                }
            }
            $data["##$objettype.assigntosupplier##"] = implode(', ', $suppliers);
        }

        $data["##$objettype.groups##"] = '';
        if ($item->countGroups(CommonITILActor::REQUESTER)) {
            $groups = [];
            foreach ($item->getGroups(CommonITILActor::REQUESTER) as $tmp) {
                $gid          = $tmp['groups_id'];
                $groups[$gid] = Dropdown::getDropdownName('glpi_groups', $gid);
            }
            $data["##$objettype.groups##"] = implode(', ', $groups);
        }

        $data["##$objettype.observergroups##"] = '';
        if ($item->countGroups(CommonITILActor::OBSERVER)) {
            $groups = [];
            foreach ($item->getGroups(CommonITILActor::OBSERVER) as $tmp) {
                $gid          = $tmp['groups_id'];
                $groups[$gid] = Dropdown::getDropdownName('glpi_groups', $gid);
            }
            $data["##$objettype.observergroups##"] = implode(', ', $groups);
        }

        $data["##$objettype.observerusers##"] = '';
        if ($item->countUsers(CommonITILActor::OBSERVER)) {
            $users = [];
            foreach ($item->getUsers(CommonITILActor::OBSERVER) as $tmp) {
                $uid      = $tmp['users_id'];
                $user_tmp = new User();
                if ($uid
                    && $user_tmp->getFromDB($uid)) {
                    $users[] = $user_tmp->getName();
                } else {
                    $users[] = $tmp['alternative_email'];
                }
            }
            $data["##$objettype.observerusers##"] = implode(', ', $users);
        }

        $data["##$objettype.assigntogroups##"] = '';
        if ($item->countGroups(CommonITILActor::ASSIGN)) {
            $groups = [];
            foreach ($item->getGroups(CommonITILActor::ASSIGN) as $tmp) {
                $gid          = $tmp['groups_id'];
                $groups[$gid] = Dropdown::getDropdownName('glpi_groups', $gid);
            }
            $data["##$objettype.assigntogroups##"] = implode(', ', $groups);
        }

        $data["##$objettype.solution.type##"]        = '';
        $data["##$objettype.solution.description##"] = '';

        $itilsolution = new ITILSolution();
        $solution     = $itilsolution->getFromDBByRequest([
                                                             'WHERE' => [
                                                                'itemtype' => $objettype,
                                                                'items_id' => $item->fields['id']
                                                             ],
                                                             'ORDER' => 'date_creation DESC',
                                                             'LIMIT' => 1
                                                          ]);

        if ($solution) {
            if ($itilsolution->getField('solutiontypes_id')) {
                $data["##$objettype.solution.type##"] = Dropdown::getDropdownName(
                    'glpi_solutiontypes',
                    $itilsolution->getField('solutiontypes_id')
                );
            }

            $data["##$objettype.solution.description##"] = $itilsolution->getField('content');
        }

        // Complex mode
        if (!$simple) {
            $followup_restrict             = [];
            $followup_restrict['items_id'] = $item->getField('id');
            if (!isset($options['additionnaloption']['show_private'])
                || !$options['additionnaloption']['show_private']) {
                $followup_restrict['is_private'] = 0;
            }
            $followup_restrict['itemtype'] = $objettype;

            //Followup infos
            $followups         = getAllDataFromTable(
                'glpi_itilfollowups',
                [
                                       'WHERE' => $followup_restrict,
                                       'ORDER' => ['date_mod DESC', 'id ASC']
                                    ]
            );
            $data['followups'] = [];
            foreach ($followups as $followup) {
                $tmp                           = [];
                $tmp['##followup.isprivate##'] = Dropdown::getYesNo($followup['is_private']);

                // Check if the author need to be anonymized
                if (Entity::getUsedConfig('anonymize_support_agents', $item->getField('entities_id'))
                    && ITILFollowup::getById($followup['id'])->isFromSupportAgent()
                ) {
                    $tmp['##followup.author##'] = __("Helpdesk");
                } else {
                    $tmp['##followup.author##'] = getUserName($followup['users_id'], 0, true);
                }

                $tmp['##followup.requesttype##'] = Dropdown::getDropdownName(
                    'glpi_requesttypes',
                    $followup['requesttypes_id']
                );
                $tmp['##followup.date##']        = Html::convDateTime($followup['date']);
                $tmp['##followup.description##'] = $followup['content'];

                $data['followups'][] = $tmp;
            }

            $data["##$objettype.numberoffollowups##"] = count($data['followups']);

            $items_id               = $item->fields['id'];
            $first_tickets_id       = PluginMetademandsInterticketfollowup::getFirstTicket($items_id);
            $ticket_metademand      = new PluginMetademandsTicket_Metademand();
            $ticket_metademand_data = $ticket_metademand->find(['tickets_id' => $first_tickets_id]);
            $tickets_found          = [];
            // If ticket is Parent : Check if all sons ticket are closed
            if (count($ticket_metademand_data)) {
                $ticket_metademand_data = reset($ticket_metademand_data);
                $tickets_found          = PluginMetademandsTicket::getSonTickets(
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
                $followup_restrict_intern               = [];
                $followup_restrict_intern['tickets_id'] = $item->getField('id');
                $followup_restrict_intern['itemtype']   = $objettype;

                //Followup infos
                $followups_intern         = getAllDataFromTable(
                    'glpi_plugin_metademands_interticketfollowups',
                    [
                                                                     'WHERE' => [
                                                                        'OR'  => [

                                                                           'AND' => [
                                                                              'tickets_id' => $list_tickets,
                                                                              'targets_id' => 0
                                                                           ],
                                                                           ['targets_id' => $items_id],
                                                                           ['tickets_id' => $items_id],

                                                                        ],
                                                                        'AND' => [
                                                                           'OR' => [

                                                                              'AND' => [
                                                                                 'tickets_id' => $list_tickets,
                                                                                 'targets_id' => 0
                                                                              ],
                                                                              ['targets_id' => $items_id],
                                                                              ['tickets_id' => $items_id],

                                                                           ]
                                                                        ]
                                                                     ],
                                                                     'ORDER' => ['date_mod DESC', 'id ASC']
                                                                  ]
                );
                $data['followups_intern'] = [];
                foreach ($followups_intern as $followup_intern) {
                    $tmp = [];


                    // Check if the author need to be anonymized
                    if (Entity::getUsedConfig('anonymize_support_agents', $item->getField('entities_id'))
                        && ITILFollowup::getById($followup_intern['id'])->isFromSupportAgent()
                    ) {
                        $tmp['##followup_intern.author##'] = __("Helpdesk");
                    } else {
                        $tmp['##followup_intern.author##'] = getUserName($followup_intern['users_id'], 0, true);
                    }

                    $tmp['##followup_intern.requesttype##'] = Dropdown::getDropdownName(
                        'glpi_requesttypes',
                        $followup_intern['requesttypes_id']
                    );
                    $tmp['##followup_intern.date##']        = Html::convDateTime($followup_intern['date']);
                    $tmp['##followup_intern.description##'] = $followup_intern['content'];

                    $data['followups_intern'][] = $tmp;
                }

                $data["##$objettype.numberoffollowups_intern##"] = count($data['followups_intern']);
            }
            $data['log'] = [];
            // Use list_limit_max or load the full history ?
            foreach (Log::getHistoryData($item, 0, $CFG_GLPI['list_limit_max']) as $log) {
                $tmp                               = [];
                $tmp["##$objettype.log.date##"]    = $log['date_mod'];
                $tmp["##$objettype.log.user##"]    = $log['user_name'];
                $tmp["##$objettype.log.field##"]   = $log['field'];
                $tmp["##$objettype.log.content##"] = $log['change'];
                $data['log'][]                     = $tmp;
            }

            $data["##$objettype.numberoflogs##"] = count($data['log']);

            // Get unresolved items
            $restrict = [
               'NOT' => [
                  $item->getTable() . '.status' => array_merge(
                      $item->getSolvedStatusArray(),
                      $item->getClosedStatusArray()
                  )
               ]
            ];

            if ($item->maybeDeleted()) {
                $restrict[$item->getTable() . '.is_deleted'] = 0;
            }

            $data["##$objettype.numberofunresolved##"]
               = countElementsInTableForEntity($item->getTable(), $this->getEntity(), $restrict, false);

            // Document
            $iterator = $DB->request([
                                        'SELECT'    => 'glpi_documents.*',
                                        'FROM'      => 'glpi_documents',
                                        'LEFT JOIN' => [
                                           'glpi_documents_items' => [
                                              'ON' => [
                                                 'glpi_documents_items' => 'documents_id',
                                                 'glpi_documents'       => 'id'
                                              ]
                                           ]
                                        ],
                                        'WHERE'     => [
                                           $item->getAssociatedDocumentsCriteria(),
                                           'timeline_position' => ['>', CommonITILObject::NO_TIMELINE], // skip inlined images
                                        ]
                                     ]);

            $data["documents"] = [];
            $addtodownloadurl  = '';
            if ($item->getType() == 'Ticket') {
                $addtodownloadurl = "%2526tickets_id=" . $item->fields['id'];
            }
            while ($row = $iterator->next()) {
                $tmp                      = [];
                $tmp['##document.id##']   = $row['id'];
                $tmp['##document.name##'] = $row['name'];
                $tmp['##document.weblink##']
                                          = $row['link'];

                $tmp['##document.url##'] = $this->formatURL(
                    $options['additionnaloption']['usertype'],
                    "document_" . $row['id']
                );
                $downloadurl             = "/front/document.send.php?docid=" . $row['id'];

                $tmp['##document.downloadurl##']
                   = $this->formatURL(
                       $options['additionnaloption']['usertype'],
                       $downloadurl . $addtodownloadurl
                   );
                $tmp['##document.heading##']
                   = Dropdown::getDropdownName(
                       'glpi_documentcategories',
                       $row['documentcategories_id']
                   );

                $tmp['##document.filename##']
                   = $row['filename'];

                $data['documents'][] = $tmp;
            }

            $data["##$objettype.urldocument##"]
               = $this->formatURL(
                   $options['additionnaloption']['usertype'],
                   $objettype . "_" . $item->getField("id") . '_Document_Item$1'
               );

            $data["##$objettype.numberofdocuments##"]
               = count($data['documents']);

            //costs infos
            $costtype = $item->getType() . 'Cost';
            $costs    = $costtype::getCostsSummary($costtype, $item->getField("id"));

            $data["##$objettype.costfixed##"]    = $costs['costfixed'];
            $data["##$objettype.costmaterial##"] = $costs['costmaterial'];
            $data["##$objettype.costtime##"]     = $costs['costtime'];
            $data["##$objettype.totalcost##"]    = $costs['totalcost'];

            $costs         = getAllDataFromTable(
                getTableForItemType($costtype),
                [
                                                 'WHERE' => [$item->getForeignKeyField() => $item->getField('id')],
                                                 'ORDER' => ['begin_date DESC', 'id ASC']
                                              ]
            );
            $data['costs'] = [];
            foreach ($costs as $cost) {
                $tmp                          = [];
                $tmp['##cost.name##']         = $cost['name'];
                $tmp['##cost.comment##']      = $cost['comment'];
                $tmp['##cost.datebegin##']    = Html::convDate($cost['begin_date']);
                $tmp['##cost.dateend##']      = Html::convDate($cost['end_date']);
                $tmp['##cost.time##']         = $item->getActionTime($cost['actiontime']);
                $tmp['##cost.costtime##']     = Html::formatNumber($cost['cost_time']);
                $tmp['##cost.costfixed##']    = Html::formatNumber($cost['cost_fixed']);
                $tmp['##cost.costmaterial##'] = Html::formatNumber($cost['cost_material']);
                $tmp['##cost.totalcost##']    = CommonITILCost::computeTotalCost(
                    $cost['actiontime'],
                    $cost['cost_time'],
                    $cost['cost_fixed'],
                    $cost['cost_material']
                );
                $tmp['##cost.budget##']       = Dropdown::getDropdownName(
                    'glpi_budgets',
                    $cost['budgets_id']
                );
                $data['costs'][]              = $tmp;
            }
            $data["##$objettype.numberofcosts##"] = count($data['costs']);

            //Task infos
            $tasktype = $item->getType() . 'Task';
            $taskobj  = new $tasktype();
            $restrict = [$item->getForeignKeyField() => $item->getField('id')];
            if ($taskobj->maybePrivate()
                && (!isset($options['additionnaloption']['show_private'])
                    || !$options['additionnaloption']['show_private'])) {
                $restrict['is_private'] = 0;
            }

            $tasks         = getAllDataFromTable(
                $taskobj->getTable(),
                [
                                       'WHERE' => $restrict,
                                       'ORDER' => ['date_mod DESC', 'id ASC']
                                    ]
            );
            $data['tasks'] = [];
            foreach ($tasks as $task) {
                $tmp                = [];
                $tmp['##task.id##'] = $task['id'];
                if ($taskobj->maybePrivate()) {
                    $tmp['##task.isprivate##'] = Dropdown::getYesNo($task['is_private']);
                }
                $tmp['##task.author##'] = getUserName($task['users_id'], 0, true);

                $tmp_taskcatinfo                 = Dropdown::getDropdownName(
                    'glpi_taskcategories',
                    $task['taskcategories_id'],
                    true,
                    true,
                    false
                );
                $tmp['##task.categoryid##']      = $task['taskcategories_id'];
                $tmp['##task.category##']        = $tmp_taskcatinfo['name'];
                $tmp['##task.categorycomment##'] = $tmp_taskcatinfo['comment'];

                $tmp['##task.date##']        = Html::convDateTime($task['date']);
                $tmp['##task.description##'] = $task['content'];
                $tmp['##task.time##']        = Ticket::getActionTime($task['actiontime']);
                $tmp['##task.status##']      = Planning::getState($task['state']);

                $tmp['##task.user##']  = getUserName($task['users_id_tech'], 0, true);
                $tmp['##task.group##'] = Dropdown::getDropdownName("glpi_groups", $task['groups_id_tech']);
                $tmp['##task.begin##'] = "";
                $tmp['##task.end##']   = "";
                if (!is_null($task['begin'])) {
                    $tmp['##task.begin##'] = Html::convDateTime($task['begin']);
                    $tmp['##task.end##']   = Html::convDateTime($task['end']);
                }

                $data['tasks'][] = $tmp;
            }

            $data["##$objettype.numberoftasks##"] = count($data['tasks']);
        }
        return $data;
    }

    public function getTags()
    {
        $itemtype  = $this->obj->getType();
        $this->obj = new Ticket();
        $itemtype  = Ticket::getType();
        $objettype = strtolower($itemtype);

        //Locales
        $tags = [$objettype . '.id'                   => __('ID'),
                 $objettype . '.title'                => __('Title'),
                 $objettype . '.url'                  => __('URL'),
                 $objettype . '.category'             => __('Category'),
                 $objettype . '.content'              => __('Description'),
                 $objettype . '.description'          => sprintf(
                     __('%1$s: %2$s'),
                     $this->obj->getTypeName(1),
                     __('Description')
                 ),
                 $objettype . '.status'               => __('Status'),
                 $objettype . '.urgency'              => __('Urgency'),
                 $objettype . '.impact'               => __('Impact'),
                 $objettype . '.priority'             => __('Priority'),
                 $objettype . '.time'                 => __('Total duration'),
                 $objettype . '.creationdate'         => __('Opening date'),
                 $objettype . '.closedate'            => __('Closing date'),
                 $objettype . '.solvedate'            => __('Date of solving'),
                 $objettype . '.duedate'              => __('Time to resolve'),
                 $objettype . '.authors'              => _n('Requester', 'Requesters', Session::getPluralNumber()),
                 'author.id'                          => __('Requester ID'),
                 'author.name'                        => _n('Requester', 'Requesters', 1),
                 'author.location'                    => __('Requester location'),
                 'author.mobile'                      => __('Mobile phone'),
                 'author.phone'                       => Phone::getTypeName(1),
                 'author.phone2'                      => __('Phone 2'),
                 'author.email'                       => _n('Email', 'Emails', 1),
                 'author.title'                       => _x('person', 'Title'),
                 'author.category'                    => __('Category'),
                 $objettype . '.suppliers'            => _n('Supplier', 'Suppliers', Session::getPluralNumber()),
                 'supplier.id'                        => __('Supplier ID'),
                 'supplier.name'                      => Supplier::getTypeName(1),
                 'supplier.phone'                     => Phone::getTypeName(1),
                 'supplier.fax'                       => __('Fax'),
                 'supplier.website'                   => __('Website'),
                 'supplier.email'                     => _n('Email', 'Emails', 1),
                 'supplier.address'                   => __('Address'),
                 'supplier.postcode'                  => __('Postal code'),
                 'supplier.town'                      => __('City'),
                 'supplier.state'                     => _x('location', 'State'),
                 'supplier.country'                   => __('Country'),
                 'supplier.comments'                  => _n('Comment', 'Comments', Session::getPluralNumber()),
                 'supplier.type'                      => SupplierType::getTypeName(1),
                 $objettype . '.openbyuser'           => __('Writer'),
                 $objettype . '.lastupdater'          => __('Last updater'),
                 $objettype . '.assigntousers'        => __('Assigned to technicians'),
                 $objettype . '.assigntosupplier'     => __('Assigned to a supplier'),
                 $objettype . '.groups'               => _n(
                     'Requester group',
                     'Requester groups',
                     Session::getPluralNumber()
                 ),
                 $objettype . '.observergroups'       => _n('Observer group', 'Observer groups', Session::getPluralNumber()),
                 $objettype . '.assigntogroups'       => __('Assigned to groups'),
                 $objettype . '.solution.type'        => SolutionType::getTypeName(1),
                 $objettype . '.solution.description' => ITILSolution::getTypeName(1),
                 $objettype . '.observerusers'        => _n('Observer', 'Observers', Session::getPluralNumber()),
                 $objettype . '.action'               => _n('Event', 'Events', 1),
                 'followup.date'                      => __('Opening date'),
                 'followup.isprivate'                 => __('Private'),
                 'followup.author'                    => __('Writer'),
                 'followup.description'               => __('Description'),
                 'followup.requesttype'               => RequestType::getTypeName(1),

                 'followup_intern.date' => __('Opening date'),

                 'followup_intern.author'      => __('Writer'),
                 'followup_intern.description' => __('Description'),
                 'followup_intern.target'      => __('Target ticket(s)', 'metademands'),

                 $objettype . '.numberoffollowups'        => _x('quantity', 'Number of followups'),
                 $objettype . '.numberoffollowups_intern' => _x('quantity', 'Number of inter ticket followups', 'metademands'),
                 $objettype . '.numberofunresolved'       => __('Number of unresolved items'),
                 $objettype . '.numberofdocuments'        => _x('quantity', 'Number of documents'),
                 $objettype . '.costtime'                 => __('Time cost'),
                 $objettype . '.costfixed'                => __('Fixed cost'),
                 $objettype . '.costmaterial'             => __('Material cost'),
                 $objettype . '.totalcost'                => __('Total cost'),
                 $objettype . '.numberofcosts'            => __('Number of costs'),
                 'cost.name'                              => sprintf(
                     __('%1$s: %2$s'),
                     _n('Cost', 'Costs', 1),
                     __('Name')
                 ),
                 'cost.comment'                           => sprintf(
                     __('%1$s: %2$s'),
                     _n('Cost', 'Costs', 1),
                     __('Comments')
                 ),
                 'cost.datebegin'                         => sprintf(
                     __('%1$s: %2$s'),
                     _n('Cost', 'Costs', 1),
                     __('Begin date')
                 ),
                 'cost.dateend'                           => sprintf(
                     __('%1$s: %2$s'),
                     _n('Cost', 'Costs', 1),
                     __('End date')
                 ),
                 'cost.time'                              => sprintf(
                     __('%1$s: %2$s'),
                     _n('Cost', 'Costs', 1),
                     __('Duration')
                 ),
                 'cost.costtime'                          => sprintf(
                     __('%1$s: %2$s'),
                     _n('Cost', 'Costs', 1),
                     __('Time cost')
                 ),
                 'cost.costfixed'                         => sprintf(
                     __('%1$s: %2$s'),
                     _n('Cost', 'Costs', 1),
                     __('Fixed cost')
                 ),
                 'cost.costmaterial'                      => sprintf(
                     __('%1$s: %2$s'),
                     _n('Cost', 'Costs', 1),
                     __('Material cost')
                 ),
                 'cost.totalcost'                         => sprintf(
                     __('%1$s: %2$s'),
                     _n('Cost', 'Costs', 1),
                     __('Total cost')
                 ),
                 'cost.budget'                            => sprintf(
                     __('%1$s: %2$s'),
                     _n('Cost', 'Costs', 1),
                     Budget::getTypeName(1)
                 ),
                 'task.author'                            => __('Writer'),
                 'task.isprivate'                         => __('Private'),
                 'task.date'                              => __('Opening date'),
                 'task.description'                       => __('Description'),
                 'task.categoryid'                        => __('Category id'),
                 'task.category'                          => __('Category'),
                 'task.categorycomment'                   => __('Category comment'),
                 'task.time'                              => __('Total duration'),
                 'task.user'                              => __('User assigned to task'),
                 'task.group'                             => __('Group assigned to task'),
                 'task.begin'                             => __('Start date'),
                 'task.end'                               => __('End date'),
                 'task.status'                            => __('Status'),
                 $objettype . '.numberoftasks'            => _x('quantity', 'Number of tasks'),
                 $objettype . '.entity.phone'             => sprintf(
                     __('%1$s (%2$s)'),
                     Entity::getTypeName(1),
                     Phone::getTypeName(1)
                 ),
                 $objettype . '.entity.fax'               => sprintf(
                     __('%1$s (%2$s)'),
                     Entity::getTypeName(1),
                     __('Fax')
                 ),
                 $objettype . '.entity.website'           => sprintf(
                     __('%1$s (%2$s)'),
                     Entity::getTypeName(1),
                     __('Website')
                 ),
                 $objettype . '.entity.email'             => sprintf(
                     __('%1$s (%2$s)'),
                     Entity::getTypeName(1),
                     _n('Email', 'Emails', 1)
                 ),
                 $objettype . '.entity.address'           => sprintf(
                     __('%1$s (%2$s)'),
                     Entity::getTypeName(1),
                     __('Address')
                 ),
                 $objettype . '.entity.postcode'          => sprintf(
                     __('%1$s (%2$s)'),
                     Entity::getTypeName(1),
                     __('Postal code')
                 ),
                 $objettype . '.entity.town'              => sprintf(
                     __('%1$s (%2$s)'),
                     Entity::getTypeName(1),
                     __('City')
                 ),
                 $objettype . '.entity.state'             => sprintf(
                     __('%1$s (%2$s)'),
                     Entity::getTypeName(1),
                     _x('location', 'State')
                 ),
                 $objettype . '.entity.country'           => sprintf(
                     __('%1$s (%2$s)'),
                     Entity::getTypeName(1),
                     __('Country')
                 ),
        ];

        foreach ($tags as $tag => $label) {
            $this->addTagToList(['tag'    => $tag,
                                 'label'  => $label,
                                 'value'  => true,
                                 'events' => parent::TAG_FOR_ALL_EVENTS]);
        }

        //Foreach global tags
        $tags = ['log'              => __('Historical'),
                 'followups'        => _n('Followup', 'Followups', Session::getPluralNumber()),
                 'followups_intern' => PluginMetademandsInterticketfollowup::getTypeName(2),
                 'tasks'            => _n('Task', 'Tasks', Session::getPluralNumber()),
                 'costs'            => _n('Cost', 'Costs', Session::getPluralNumber()),
                 'authors'          => _n('Requester', 'Requesters', Session::getPluralNumber()),
                 'suppliers'        => _n('Supplier', 'Suppliers', Session::getPluralNumber())];

        foreach ($tags as $tag => $label) {
            $this->addTagToList(['tag'     => $tag,
                                 'label'   => $label,
                                 'value'   => false,
                                 'foreach' => true]);
        }

        //Tags with just lang
        $tags = [$objettype . '.days'               => _n('Day', 'Days', Session::getPluralNumber()),
                 $objettype . '.attribution'        => __('Assigned to'),
                 $objettype . '.entity'             => Entity::getTypeName(1),
                 $objettype . '.nocategoryassigned' => __('No defined category'),
                 $objettype . '.log'                => __('Historical'),
                 $objettype . '.tasks'              => _n('Task', 'Tasks', Session::getPluralNumber()),
                 $objettype . '.costs'              => _n('Cost', 'Costs', Session::getPluralNumber())];

        foreach ($tags as $tag => $label) {
            $this->addTagToList(['tag'   => $tag,
                                 'label' => $label,
                                 'value' => false,
                                 'lang'  => true]);
        }

        //Tags without lang
        $tags = [$objettype . '.urlapprove'   => __('Web link to approval the solution'),
                 $objettype . '.entity'       => sprintf(
                     __('%1$s (%2$s)'),
                     Entity::getTypeName(1),
                     __('Complete name')
                 ),
                 $objettype . '.shortentity'  => sprintf(
                     __('%1$s (%2$s)'),
                     Entity::getTypeName(1),
                     __('Name')
                 ),
                 $objettype . '.numberoflogs' => sprintf(
                     __('%1$s: %2$s'),
                     __('Historical'),
                     _x('quantity', 'Number of items')
                 ),
                 $objettype . '.log.date'     => sprintf(
                     __('%1$s: %2$s'),
                     __('Historical'),
                     _n('Date', 'Dates', 1)
                 ),
                 $objettype . '.log.user'     => sprintf(
                     __('%1$s: %2$s'),
                     __('Historical'),
                     User::getTypeName(1)
                 ),
                 $objettype . '.log.field'    => sprintf(
                     __('%1$s: %2$s'),
                     __('Historical'),
                     _n('Field', 'Fields', 1)
                 ),
                 $objettype . '.log.content'  => sprintf(
                     __('%1$s: %2$s'),
                     __('Historical'),
                     _x('name', 'Update')
                 ),
                 'document.url'               => sprintf(
                     __('%1$s: %2$s'),
                     Document::getTypeName(1),
                     __('URL')
                 ),
                 'document.downloadurl'       => sprintf(
                     __('%1$s: %2$s'),
                     Document::getTypeName(1),
                     __('Download URL')
                 ),
                 'document.heading'           => sprintf(
                     __('%1$s: %2$s'),
                     Document::getTypeName(1),
                     __('Heading')
                 ),
                 'document.id'                => sprintf(
                     __('%1$s: %2$s'),
                     Document::getTypeName(1),
                     __('ID')
                 ),
                 'document.filename'          => sprintf(
                     __('%1$s: %2$s'),
                     Document::getTypeName(1),
                     __('File')
                 ),
                 'document.weblink'           => sprintf(
                     __('%1$s: %2$s'),
                     Document::getTypeName(1),
                     __('Web link')
                 ),
                 'document.name'              => sprintf(
                     __('%1$s: %2$s'),
                     Document::getTypeName(1),
                     __('Name')
                 ),
                 $objettype . '.urldocument'  => sprintf(
                     __('%1$s: %2$s'),
                     Document::getTypeName(Session::getPluralNumber()),
                     __('URL')
                 )];

        foreach ($tags as $tag => $label) {
            $this->addTagToList(['tag'   => $tag,
                                 'label' => $label,
                                 'value' => true,
                                 'lang'  => false]);
        }

        //Tickets with a fixed set of values
        $status         = $this->obj->getAllStatusArray(false);
        $allowed_ticket = [];
        foreach ($status as $key => $value) {
            $allowed_ticket[] = $key;
        }

        $tags = [$objettype . '.storestatus' => ['text' => __('Status value in database'),
                                                 'allowed_values'
                                                        => $allowed_ticket]];
        foreach ($tags as $tag => $label) {
            $this->addTagToList(['tag'            => $tag,
                                 'label'          => $label['text'],
                                 'value'          => true,
                                 'lang'           => false,
                                 'allowed_values' => $label['allowed_values']]);
        }
    }
}
