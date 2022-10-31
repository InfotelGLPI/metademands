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

/**
 *
 */
class PluginMetademandsNotificationTargetStepform extends NotificationTarget
{
    const TARGET_NEXT_GROUP = 6300;

    /**
     * Get events related to Itil Object
     *
     * @return array of events (event key => event label)
     **@since 9.2
     *
     */
    public function getEvents() {
        $events = [
            'new_step_form'      => __("A new form has been completed", 'metademands'),
            'reminder_step_form' => __('Reminder for a waiting form', 'metademands'),
        ];

        asort($events);
        return $events;
    }

    /**
     * Get additionnals targets for holiday
     */
    public function addNotificationTargets($event = '') {
        $this->addTarget(self::TARGET_NEXT_GROUP, __('Next group in charge of demand', 'metademands'));
    }

    /**
     * Add targets by a method not defined in NotificationTarget (specific to an itemtype)
     *
     * @param array $data Data
     * @param array $options Options
     *
     * @return void
     **/
    public function addSpecificTargets($data, $options) {

        switch ($data['items_id']) {
            case self::TARGET_NEXT_GROUP:
                return $this->addForGroup(0, $this->obj->fields['groups_id_dest']);
        }
    }

    public function addDataForTemplate($event, $options = []) {
        $events    = $this->getAllEvents();
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
    public function getDataForObject(CommonDBTM $item, array $options, $simple = false) {

        $meta = new PluginMetademandsMetademand();
        $meta->getFromDB($item->fields['plugin_metademands_metademands_id']);
        $objettypeMeta = PluginMetademandsMetademand::getType();
        $objettype     = $item::getType();

        $data["##$objettypeMeta.title##"]     = $meta->getField('name');
        $data["##$objettype.user_editor##"]   = Dropdown::getDropdownName(User::getTable(), $item->getField('users_id'));
        $data["##$objettype.nextgroup##"]     = Dropdown::getDropdownName(Group::getTable(), $item->getField('groups_id_dest'));
        $data["##$objettype.date##"]          = Html::convDateTime($item->getField('date'));
        $data["##$objettype.reminder_date##"] = Html::convDateTime($item->getField('reminder_date'));

        return $data;
    }

    public function getTags() {
        $objettype     = $this->obj->getType();
        $objettypeMeta = PluginMetademandsMetademand::getType();


        //Locales
        $tags = [$objettypeMeta . '.title'     => __('Title'),
                 $objettype . '.user_editor'   => __('Publisher', 'metademands'),
                 $objettype . '.nextgroup'     => __('Group in charge of the next step', 'metademands'),
                 $objettype . '.date'          => __('Date of the last step', 'metademands'),
                 $objettype . '.reminder_date' => __('Date of the next reminder', 'metademands'),
        ];

        foreach ($tags as $tag => $label) {
            $this->addTagToList(['tag'    => $tag,
                                 'label'  => $label,
                                 'value'  => true,
                                 'events' => parent::TAG_FOR_ALL_EVENTS]);
        }

        //Foreach global tags
        $tags = [];

        foreach ($tags as $tag => $label) {
            $this->addTagToList(['tag'     => $tag,
                                 'label'   => $label,
                                 'value'   => false,
                                 'foreach' => true]);
        }

        //Tags with just lang
        $tags = [];

        foreach ($tags as $tag => $label) {
            $this->addTagToList(['tag'   => $tag,
                                 'label' => $label,
                                 'value' => false,
                                 'lang'  => true]);
        }

        //Tags without lang
        $tags = [];

        foreach ($tags as $tag => $label) {
            $this->addTagToList(['tag'   => $tag,
                                 'label' => $label,
                                 'value' => true,
                                 'lang'  => false]);
        }
    }
}
