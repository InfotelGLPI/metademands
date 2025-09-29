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

namespace GlpiPlugin\Metademands;

use CommonDBTM;
use CommonITILObject;
use Glpi\RichText\RichText;
use Group_User;
use Search;
use Session;
use CommonGLPI;
use User;
use UserEmail;
use PluginResourcesResource;

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access directly to this file");
}

/**
 * Class Ticket_Metademand
 */
class Ticket_Metademand extends CommonDBTM
{
    public static $rightname = 'plugin_metademands';
    public const RUNNING   = 1;
    public const TO_CLOSED = 2;
    public const CLOSED    = 3;

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
        return __('Task creation', 'metademands');
    }

    /**
     * @return bool|int
     */
    public static function canView(): bool
    {
        return Session::haveRight(self::$rightname, READ);
    }

    /**
     * @return bool
     */
    public static function canCreate(): bool
    {
        return Session::haveRightsOr(self::$rightname, [CREATE, UPDATE, DELETE]);
    }

    public static function getIcon()
    {
        return "ti ti-link";
    }

    /**
     * Display tab for each users
     *
     * @param CommonGLPI $item
     * @param int        $withtemplate
     *
     * @return array|string
     */
    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        global $DB;
        if (!$withtemplate) {
            if ($item->getType() == Metademand::class) {
                if ($_SESSION['glpishow_count_on_tabs']) {
                    $query = self::countTicketsInTable($item->getID());
                    $result  = $DB->doQuery($query);
                    $numrows = $DB->numrows($result);

                    return self::createTabEntry(
                        __('Linked opened tickets', 'metademands'),
                        $numrows
                    );
                }
                return __('Linked opened tickets', 'metademands');
            }
        }
        return '';
    }


    /**
     * @param $meta_id
     *
     * @return string
     */
    public static function countTicketsInTable($meta_id)
    {

        $status  = CommonITILObject::INCOMING . ", " . CommonITILObject::PLANNED . ", "
                 . CommonITILObject::ASSIGNED . ", " . CommonITILObject::WAITING;

        $query = "SELECT DISTINCT `glpi_tickets`.`id`
                FROM `glpi_tickets`
                LEFT JOIN `glpi_tickets_users`
                  ON (`glpi_tickets`.`id` = `glpi_tickets_users`.`tickets_id`)
                LEFT JOIN `glpi_plugin_metademands_tickets_metademands`
                  ON (`glpi_tickets`.`id` = `glpi_plugin_metademands_tickets_metademands`.`tickets_id`)
                LEFT JOIN `glpi_groups_tickets`
                  ON (`glpi_tickets`.`id` = `glpi_groups_tickets`.`tickets_id`)";

        $query .= "WHERE `glpi_tickets`.`is_deleted` = 0
      AND `glpi_plugin_metademands_tickets_metademands`.`plugin_metademands_metademands_id` = $meta_id
      AND (`glpi_tickets`.`status` IN ($status)) "
                . getEntitiesRestrictRequest("AND", "glpi_tickets");
        $query .= " ORDER BY id DESC";

        return $query;
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
    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        global $DB;

        if (!Session::haveRight("ticket", \Ticket::READALL)
          && !Session::haveRight("ticket", \Ticket::READASSIGN)
          && !Session::haveRight("ticket", CREATE)) {
            return false;
        }

        $query = self::countTicketsInTable($item->getID());
        $result  = $DB->doQuery($query);
        $numrows = $DB->numrows($result);

        if ($numrows > 0) {
            $rand = mt_rand();

            echo "<table class='tab_cadre_fixe'>";

            \Ticket::commonListHeader(Search::HTML_OUTPUT, 'mass' . __CLASS__ . $rand);
            for ($i = 0; $i < $numrows; $i++) {
                $ID = $DB->result($result, $i, "id");

                \Ticket::showShort(
                    $ID,
                    [
                        'output_type' => Search::HTML_OUTPUT,
                        'row_num' => $i,
                        'type_for_massiveaction' => __CLASS__,
                        'id_for_massiveaction'   => $ID,
                    ]
                );
            }
            echo "</table>";
        } else {
            echo "<div class='alert alert-important alert-info center'>" . __('No results found') . "</div>";
        }
        return true;
    }

    /**
     * @param $field
     * @param $name (default '')
     * @param $values (default '')
     * @param $options   array
     *
     * @return string
     * *@since version 0.84
     *
     */
    public static function getSpecificValueToSelect($field, $name = '', $values = '', array $options = [])
    {

        if (!is_array($values)) {
            $values = [$field => $values];
        }
        $options['display'] = false;

        switch ($field) {
            case 'status':
                $options['name']  = $name;
                $options['value'] = $values[$field];
                //            $options['withmajor'] = 1;
                return self::dropdownStatus($options);
                break;
        }
        return parent::getSpecificValueToSelect($field, $name, $values, $options);
    }

    /**
     * display a value according to a field
     *
     * @param $field     String         name of the field
     * @param $values    String / Array with the value to display
     * @param $options   Array          of option
     *
     * @return string string
     **@since version 0.83
     *
     */
    public static function getSpecificValueToDisplay($field, $values, array $options = [])
    {

        if (!is_array($values)) {
            $values = [$field => $values];
        }
        switch ($field) {
            case 'status':
                return self::getStatusName($values[$field]);
                break;
        }
        return parent::getSpecificValueToDisplay($field, $values, $options);
    }


    /**
     * @param array $options
     *
     * @return int|string
     */
    public static function dropdownStatus(array $options = [])
    {

        $p['name']     = 'status';
        $p['value']    = 0;
        $p['showtype'] = 'normal';
        $p['display']  = true;

        if (is_array($options) && count($options)) {
            foreach ($options as $key => $val) {
                $p[$key] = $val;
            }
        }

        $values                  = [];
        $values[0]               = static::getStatusName(0);
        $values[self::RUNNING]   = static::getStatusName(self::RUNNING);
        $values[self::TO_CLOSED] = static::getStatusName(self::TO_CLOSED);
        $values[self::CLOSED]    = static::getStatusName(self::CLOSED);

        return \Dropdown::showFromArray($p['name'], $values, $p);
    }


    /**
     * @param $value
     *
     * @return string
     */
    public static function getStatusName($value)
    {

        switch ($value) {
            case self::RUNNING:
                return _x('status', 'In progress', 'metademands');

            case self::TO_CLOSED:
                return _x('status', 'To close', 'metademands');

            case self::CLOSED:
                return _x('status', 'Closed', 'metademands');

            default:
                // Return $value if not define
                return \Dropdown::EMPTY_VALUE;
        }
    }

    public static function createSonsObjects($metademand, $values_form, $parent_tickets_id, $parent_fields, $parent_ticketfields, $tasklevel, $inputField, $inputFieldMain)
    {

        if (isset($line['tasks'])
            && is_array($line['tasks'])
            && count($line['tasks'])) {
            if ($metademand->fields["validation_subticket"] == 0) {
                $ticket2 = new \Ticket();
                $ticket2->getFromDB($parent_tickets_id);
                $parent_fields["requesttypes_id"] = $ticket2->fields['requesttypes_id'];
                foreach ($line['tasks'] as $key => $l) {
                    if ($l['type'] != Task::MAIL_TYPE) {
                        //replace #id# in title with the value
                        do {
                            $match = Metademand::getBetween($l['tickettasks_name'], '[', ']');
                            if (empty($match)) {
                                $explodeTitle = [];
                                $explodeTitle = explode("#", $l['tickettasks_name']);
                                foreach ($explodeTitle as $title) {
                                    if (isset($values['fields'][$title])) {
                                        $field = new Field();
                                        $field->getFromDB($title);
                                        $fields = $field->fields;
                                        $fields['value'] = '';

                                        $fields['value'] = $values['fields'][$title];

                                        $fields['value2'] = '';
                                        if (($fields['type'] == 'date_interval' || $fields['type'] == 'datetime_interval') && isset($values['fields'][$title . '-2'])) {
                                            $fields['value2'] = $values['fields'][$title . '-2'];
                                        }
                                        $result = [];
                                        $result['content'] = "";
                                        $result[$fields['rank']]['content'] = "";
                                        $result[$fields['rank']]['display'] = false;
                                        $parent_fields_id = 0;
                                        $value = Metademand::getContentWithField([], 0, $fields, $result, $parent_fields_id, true);
                                        $line['tasks'][$key]['tickettasks_name'] = str_replace("#" . $title . "#", $value, $line['tasks'][$key]['tickettasks_name']);
                                    } else {
                                        $explodeTitle2 = explode(".", $title);

                                        if (isset($values['fields'][$explodeTitle2[0]])) {
                                            $field_object = new Field();
                                            if ($field_object->getFromDB($explodeTitle2[0])) {
                                                if ($field_object->fields['type'] == "dropdown_object" && $field_object->fields['item'] == User::getType()) {
                                                    $users_id = $values['fields'][$explodeTitle2[0]];
                                                    $line['tasks'][$key]['tickettasks_name'] = Metademand::getContentForUser($explodeTitle2[1], $users_id, $_SESSION['glpiactive_entity'], $title, $line['tasks'][$key]['tickettasks_name']);
                                                }
                                            }
                                        }
                                        $users_id = $parent_fields['_users_id_requester'];
                                        $line['tasks'][$key]['tickettasks_name'] = Metademand::getContentForUser($title, $users_id, $_SESSION['glpiactive_entity'], $title, $line['tasks'][$key]['tickettasks_name'], true);
                                    }
                                }
                            } else {
                                $explodeVal = [];
                                $explodeVal = explode("|", $match);
                                $find = false;
                                $val_to_replace = "";
                                foreach ($explodeVal as $str) {
                                    $explodeTitle = explode("#", $str);
                                    foreach ($explodeTitle as $title) {
                                        if (isset($values['fields'][$title])) {
                                            $field = new Field();
                                            $field->getFromDB($title);
                                            $fields = $field->fields;
                                            $fields['value'] = '';

                                            $fields['value'] = $values['fields'][$title];

                                            $fields['value2'] = '';
                                            if (($fields['type'] == 'date_interval' || $fields['type'] == 'datetime_interval') && isset($values['fields'][$title . '-2'])) {
                                                $fields['value2'] = $values['fields'][$title . '-2'];
                                            }
                                            $result = [];
                                            $result['content'] = "";
                                            $result[$fields['rank']]['content'] = "";
                                            $result[$fields['rank']]['display'] = false;
                                            $parent_fields_id = 0;
                                            $value = Metademand::getContentWithField([], 0, $fields, $result, $parent_fields_id, true);
                                            $str = str_replace("#" . $title . "#", $value, $str);
                                            if (!is_null($value) && !empty($value)) {
                                                $find = true;
                                            }
                                        } else {
                                            $explodeTitle2 = explode(".", $title);

                                            if (isset($values['fields'][$explodeTitle2[0]])) {
                                                $field_object = new Field();
                                                if ($field_object->getFromDB($explodeTitle2[0])) {
                                                    if ($field_object->fields['type'] == "dropdown_object" && $field_object->fields['item'] == User::getType()) {
                                                        $users_id = $values['fields'][$explodeTitle2[0]];
                                                        $str = Metademand::getContentForUser($explodeTitle2[1], $users_id, $_SESSION['glpiactive_entity'], $title, $str);
                                                    }
                                                }
                                            }
                                            $users_id = $parent_fields['_users_id_requester'];
                                            $str = Metademand::getContentForUser($title, $users_id, $_SESSION['glpiactive_entity'], $title, $str, true);
                                        }
                                    }
                                    if ($find == true) {
                                        break;
                                    }
                                }

                                if (str_contains($match, "#")) {
                                    $line['tasks'][$key]['tickettasks_name'] = str_replace("[" . $match . "]", $str, $line['tasks'][$key]['tickettasks_name']);
                                    $l['tickettasks_name'] = str_replace("[" . $match . "]", $str, $l['tickettasks_name']);
                                } else {
                                    $line['tasks'][$key]['tickettasks_name'] = str_replace("[" . $match . "]", "<@" . $str . "@>", $line['tasks'][$key]['tickettasks_name']);
                                    $l['tickettasks_name'] = str_replace("[" . $match . "]", "<@" . $str . "@>", $l['tickettasks_name']);
                                }
                                //                                    $value['value'] = str_replace("[".$match."]", $str,  $value['value']);
                            }
                        } while (!empty($match));

                        $line['tasks'][$key]['tickettasks_name'] = str_replace("<@", "[", $line['tasks'][$key]['tickettasks_name']);
                        $line['tasks'][$key]['tickettasks_name'] = str_replace("@>", "]", $line['tasks'][$key]['tickettasks_name']);
                        $l['tickettasks_name'] = str_replace("<@", "[", $l['tickettasks_name']);
                        $l['tickettasks_name'] = str_replace("@>", "]", $l['tickettasks_name']);

                        $explodeTitle = explode("#", $l['tickettasks_name']);
                        foreach ($explodeTitle as $title) {
                            if (isset($values['fields'][$title])) {
                                $field = new Field();
                                $field->getFromDB($title);
                                $fields = $field->fields;
                                $fields['value'] = '';

                                $fields['value'] = $values['fields'][$title];

                                $fields['value2'] = '';
                                if (($fields['type'] == 'date_interval'
                                        || $fields['type'] == 'datetime_interval')
                                    && isset($values['fields'][$title . '-2'])) {
                                    $fields['value2'] = $values['fields'][$title . '-2'];
                                }
                                $result = [];
                                $result['content'] = "";
                                $result[$fields['rank']]['content'] = "";
                                $result[$fields['rank']]['display'] = false;
                                $parent_fields_id = 0;
                                $value = Metademand::getContentWithField([], 0, $fields, $result, $parent_fields_id, true);
                                $line['tasks'][$key]['tickettasks_name'] = str_replace("#" . $title . "#", $value, $line['tasks'][$key]['tickettasks_name']);
                            } else {
                                $explodeTitle2 = explode(".", $title);

                                if (isset($values['fields'][$explodeTitle2[0]])) {
                                    $field_object = new Field();
                                    if ($field_object->getFromDB($explodeTitle2[0])) {
                                        if ($field_object->fields['type'] == "dropdown_object" && $field_object->fields['item'] == User::getType()) {
                                            $users_id = $values['fields'][$explodeTitle2[0]];
                                            $line['tasks'][$key]['tickettasks_name'] = Metademand::getContentForUser($explodeTitle2[1], $users_id, $_SESSION['glpiactive_entity'], $title, $line['tasks'][$key]['tickettasks_name']);
                                        }
                                    }
                                }
                                $users_id = $parent_fields['_users_id_requester'];
                                $line['tasks'][$key]['tickettasks_name'] = Metademand::getContentForUser($title, $users_id, $_SESSION['glpiactive_entity'], $title, $line['tasks'][$key]['tickettasks_name'], true);
                            }
                        }


                        //replace #id# in content with the value
                        do {
                            $match = Metademand::getBetween($l['content'], '[', ']');
                            if (empty($match) && $l['content'] != null) {
                                $explodeContent = explode("#", $l['content']);
                                foreach ($explodeContent as $content) {
                                    if (isset($values['fields'][$content])) {
                                        $field = new Field();
                                        $field->getFromDB($content);
                                        $fields = $field->fields;
                                        $fields['value'] = '';

                                        $fields['value'] = $values['fields'][$content];

                                        $fields['value2'] = '';
                                        if (($fields['type'] == 'date_interval' || $fields['type'] == 'datetime_interval') && isset($values['fields'][$content . '-2'])) {
                                            $fields['value2'] = $values['fields'][$content . '-2'];
                                        }
                                        $result = [];
                                        $result['content'] = "";
                                        $result[$fields['rank']]['content'] = "";
                                        $result[$fields['rank']]['display'] = false;
                                        $parent_fields_id = 0;
                                        $value = Metademand::getContentWithField([], 0, $fields, $result, $parent_fields_id, true);
                                        if ($fields['type'] == "textarea") {
                                            if ($line['tasks'][$key]["formatastable"] == 0) {
                                                $value = str_replace("\\n", '","', $value);
                                            }
                                        }
                                        if ($value != null) {
                                            $line['tasks'][$key]['content'] = str_replace(
                                                "#" . $content . "#",
                                                $value,
                                                $line['tasks'][$key]['content']
                                            );
                                        }
                                    } else {
                                        $explodeContent2 = explode(".", $content);

                                        if (isset($values['fields'][$explodeContent2[0]])) {
                                            $field_object = new Field();
                                            if ($field_object->getFromDB($explodeContent2[0])) {
                                                if ($field_object->fields['type'] == "dropdown_object" && $field_object->fields['item'] == User::getType()) {
                                                    $users_id = $values['fields'][$explodeContent2[0]];
                                                    $line['tasks'][$key]['content'] = Metademand::getContentForUser($explodeContent2[1], $users_id, $_SESSION['glpiactive_entity'], $content, $line['tasks'][$key]['content']);
                                                }
                                            }
                                        }
                                        $users_id = $parent_fields['_users_id_requester'];
                                        $line['tasks'][$key]['content'] = Metademand::getContentForUser($content, $users_id, $_SESSION['glpiactive_entity'], $content, $line['tasks'][$key]['content'], true);
                                    }
                                }
                            } else {
                                $explodeVal = [];
                                $explodeVal = explode("|", $match);
                                $find = false;
                                $val_to_replace = "";
                                foreach ($explodeVal as $str) {
                                    $explodeContent = explode("#", $str);
                                    foreach ($explodeContent as $content) {
                                        if (isset($values['fields'][$content])) {
                                            $field = new Field();
                                            $field->getFromDB($content);
                                            $fields = $field->fields;
                                            $fields['value'] = '';

                                            $fields['value'] = $values['fields'][$content];

                                            $fields['value2'] = '';
                                            if (($fields['type'] == 'date_interval' || $fields['type'] == 'datetime_interval') && isset($values['fields'][$content . '-2'])) {
                                                $fields['value2'] = $values['fields'][$content . '-2'];
                                            }
                                            $result = [];
                                            $result['content'] = "";
                                            $result[$fields['rank']]['content'] = "";
                                            $result[$fields['rank']]['display'] = false;
                                            $parent_fields_id = 0;
                                            $value = Metademand::getContentWithField([], 0, $fields, $result, $parent_fields_id, true);
                                            if ($fields['type'] == "textarea") {
                                                if ($line['tasks'][$key]["formatastable"] == 0) {
                                                    $value = str_replace("\\n", '","', $value);
                                                }
                                            }
                                            $str = str_replace("#" . $content . "#", $value, $str);
                                            if (!is_null($value) && !empty($value)) {
                                                $find = true;
                                            }
                                        } else {
                                            $explodeContent2 = explode(".", $content);

                                            if (isset($values['fields'][$explodeContent2[0]])) {
                                                $field_object = new Field();
                                                if ($field_object->getFromDB($explodeContent2[0])) {
                                                    if ($field_object->fields['type'] == "dropdown_object" && $field_object->fields['item'] == User::getType()) {
                                                        $users_id = $values['fields'][$explodeContent2[0]];
                                                        $str = Metademand::getContentForUser($explodeContent2[1], $users_id, $_SESSION['glpiactive_entity'], $content, $str);
                                                    }
                                                }
                                            }
                                            $users_id = $parent_fields['_users_id_requester'];
                                            $str = Metademand::getContentForUser($content, $users_id, $_SESSION['glpiactive_entity'], $content, $str, true);
                                        }
                                    }
                                    if ($find == true) {
                                        break;
                                    }
                                }
                                //                                    $line['tasks'][$key]['content'] = str_replace("[" . $match . "]", $str, $line['tasks'][$key]['content']);
                                if (str_contains($match, "#")) {
                                    $line['tasks'][$key]['content'] = str_replace("[" . $match . "]", $str, $line['tasks'][$key]['content']);
                                    $l['content'] = str_replace("[" . $match . "]", $str, $l['content']);
                                } else {
                                    if ($line['tasks'][$key]['content'] != null) {
                                        $line['tasks'][$key]['content'] = str_replace("[" . $match . "]", "<@" . $str . "@>", $line['tasks'][$key]['content']);
                                    }
                                    if ($l['content'] != null) {
                                        $l['content'] = str_replace("[" . $match . "]", "<@" . $str . "@>", $l['content']);
                                    }
                                }
                                //                                    $value['value'] = str_replace("[".$match."]", $str,  $value['value']);
                            }
                        } while (!empty($match));

                        if ($line['tasks'][$key]['content'] != null) {
                            $line['tasks'][$key]['content'] = str_replace("<@", "[", $line['tasks'][$key]['content']);
                            $line['tasks'][$key]['content'] = str_replace("@>", "]", $line['tasks'][$key]['content']);
                        }
                        if ($l['content'] != null) {
                            $l['content'] = str_replace("<@", "[", $l['content']);
                            $l['content'] = str_replace("@>", "]", $l['content']);
                        }
                        if ($l['content'] != null) {
                            $explodeContent = explode("#", $l['content']);
                            foreach ($explodeContent as $content) {
                                if (isset($values['fields'][$content])) {
                                    $field = new Field();
                                    $field->getFromDB($content);
                                    $fields = $field->fields;
                                    $fields['value'] = '';

                                    $fields['value'] = $values['fields'][$content];

                                    $fields['value2'] = '';
                                    if (($fields['type'] == 'date_interval'
                                            || $fields['type'] == 'datetime_interval')
                                        && isset($values['fields'][$content . '-2'])) {
                                        $fields['value2'] = $values['fields'][$content . '-2'];
                                    }
                                    $result = [];
                                    $result['content'] = "";
                                    $result[$fields['rank']]['content'] = "";
                                    $result[$fields['rank']]['display'] = false;
                                    $parent_fields_id = 0;
                                    $value = Metademand::getContentWithField([], 0, $fields, $result, $parent_fields_id, true);
                                    if ($fields['type'] == "textarea") {
                                        if ($line['tasks'][$key]["formatastable"] == 0) {
                                            $value = str_replace("\\n", '","', $value);
                                        }
                                    }
                                    if ($value != null) {
                                        $line['tasks'][$key]['content'] = str_replace("#" . $content . "#", $value, $line['tasks'][$key]['content']);
                                    }
                                } else {
                                    $explodeContent2 = explode(".", $content);

                                    if (isset($values['fields'][$explodeContent2[0]])) {
                                        $field_object = new Field();
                                        if ($field_object->getFromDB($explodeContent2[0])) {
                                            if ($field_object->fields['type'] == "dropdown_object" && $field_object->fields['item'] == User::getType()) {
                                                $users_id = $values['fields'][$explodeContent2[0]];
                                                $line['tasks'][$key]['content'] = Metademand::getContentForUser($explodeContent2[1], $users_id, $_SESSION['glpiactive_entity'], $content, $line['tasks'][$key]['content']);
                                            }
                                        }
                                    }
                                    $users_id = $parent_fields['_users_id_requester'];
                                    $line['tasks'][$key]['content'] = Metademand::getContentForUser($content, $users_id, $_SESSION['glpiactive_entity'], $content, $line['tasks'][$key]['content'], true);
                                }
                            }
                        }
                    } else {
                        $mail = new MailTask();
                        $mail->getFromDBByCrit(["plugin_metademands_tasks_id" => $l['tasks_id']]);
                        if ($l['useBlock']) {
                            $blocks_use = json_decode($l['block_use']);
                            if (!empty($blocks_use)) {
                                foreach ($line['form'] as $i => $f) {
                                    if (!in_array($f['rank'], $blocks_use)) {
                                        unset($line['form'][$i]);
                                        unset($values_form[$i]);
                                    }
                                }
                                $values[$metademand->getID()] = $values_form[0]['fields'];
                                $parent_fields_content = Metademand::formatFields(
                                    $line['form'],
                                    $metademand->getID(),
                                    $values,
                                    ['formatastable' => $l['formatastable']]
                                );
                            } else {
                                $parent_fields_content['content'] = $parent_fields['content'];
                            }
                        }
                        $content = "";
                        $son_ticket_data['content'] = $mail->fields['content'];
                        if (!empty($son_ticket_data['content'])) {
                            if (isset($task->fields['formatastable']) && $task->fields['formatastable'] == true) {
                                $content = "<table class='tab_cadre_fixe' style='width: 100%;'>";
                                $content .= "<tr><th colspan='2'>" . __('Child Ticket', 'metademands')
                                    . "</th></tr><tr><td colspan='2'>";
                            }

                            $content .= RichText::getSafeHtml($son_ticket_data['content']);

                            if (isset($task->fields['formatastable']) && $task->fields['formatastable'] == true) {
                                $content .= "</td></tr></table><br>";
                            }
                        }
                        if (!empty($parent_fields_content['content'])) {
                            $content .= "<table class='tab_cadre_fixe' style='width: 100%;'><tr><th colspan='2'>";
                            $content .= _n('Parent tickets', 'Parent tickets', 1, 'metademands')
                                . "</th></tr><tr><td colspan='2'>" . RichText::getSafeHtml($parent_fields_content['content']);
                            $content .= "</td></tr></table><br>";
                        }
                        //replace #id# in title with the value
                        do {
                            $match = Metademand::getBetween($l['tickettasks_name'], '[', ']');
                            if (empty($match)) {
                                $explodeTitle = [];
                                $explodeTitle = explode("#", $l['tickettasks_name']);
                                foreach ($explodeTitle as $title) {
                                    if (isset($values['fields'][$title])) {
                                        $field = new Field();
                                        $field->getFromDB($title);
                                        $fields = $field->fields;
                                        $fields['value'] = '';

                                        $fields['value'] = $values['fields'][$title];

                                        $fields['value2'] = '';
                                        if (($fields['type'] == 'date_interval' || $fields['type'] == 'datetime_interval') && isset($values['fields'][$title . '-2'])) {
                                            $fields['value2'] = $values['fields'][$title . '-2'];
                                        }
                                        $result = [];
                                        $result['content'] = "";
                                        $result[$fields['rank']]['content'] = "";
                                        $result[$fields['rank']]['display'] = false;
                                        $parent_fields_id = 0;
                                        $value = Metademand::getContentWithField([], 0, $fields, $result, $parent_fields_id, true);
                                        $line['tasks'][$key]['tickettasks_name'] = str_replace("#" . $title . "#", $value, $line['tasks'][$key]['tickettasks_name']);
                                    } else {
                                        $explodeTitle2 = explode(".", $title);

                                        if (isset($values['fields'][$explodeTitle2[0]])) {
                                            $field_object = new Field();
                                            if ($field_object->getFromDB($explodeTitle2[0])) {
                                                if ($field_object->fields['type'] == "dropdown_object" && $field_object->fields['item'] == User::getType()) {
                                                    $users_id = $values['fields'][$explodeTitle2[0]];
                                                    $line['tasks'][$key]['tickettasks_name'] = Metademand::getContentForUser($explodeTitle2[1], $users_id, $title, $line['tasks'][$key]['tickettasks_name']);
                                                }
                                            }
                                        }
                                        $users_id = $parent_fields['_users_id_requester'];
                                        $line['tasks'][$key]['tickettasks_name'] = Metademand::getContentForUser($title, $users_id, $title, $line['tasks'][$key]['tickettasks_name'], true);
                                    }
                                }
                            } else {
                                $explodeVal = [];
                                $explodeVal = explode("|", $match);
                                $find = false;
                                $val_to_replace = "";
                                foreach ($explodeVal as $str) {
                                    $explodeTitle = explode("#", $str);
                                    foreach ($explodeTitle as $title) {
                                        if (isset($values['fields'][$title])) {
                                            $field = new Field();
                                            $field->getFromDB($title);
                                            $fields = $field->fields;
                                            $fields['value'] = '';

                                            $fields['value'] = $values['fields'][$title];

                                            $fields['value2'] = '';
                                            if (($fields['type'] == 'date_interval' || $fields['type'] == 'datetime_interval') && isset($values['fields'][$title . '-2'])) {
                                                $fields['value2'] = $values['fields'][$title . '-2'];
                                            }
                                            $result = [];
                                            $result['content'] = "";
                                            $result[$fields['rank']]['content'] = "";
                                            $result[$fields['rank']]['display'] = false;
                                            $parent_fields_id = 0;
                                            $value = Metademand::getContentWithField([], 0, $fields, $result, $parent_fields_id, true);
                                            $str = str_replace("#" . $title . "#", $value, $str);
                                            if (!is_null($value) && !empty($value)) {
                                                $find = true;
                                            }
                                        } else {
                                            $explodeTitle2 = explode(".", $title);

                                            if (isset($values['fields'][$explodeTitle2[0]])) {
                                                $field_object = new Field();
                                                if ($field_object->getFromDB($explodeTitle2[0])) {
                                                    if ($field_object->fields['type'] == "dropdown_object" && $field_object->fields['item'] == User::getType()) {
                                                        $users_id = $values['fields'][$explodeTitle2[0]];
                                                        $str = Metademand::getContentForUser($explodeTitle2[1], $users_id, $title, $str);
                                                    }
                                                }
                                            }
                                            $users_id = $parent_fields['_users_id_requester'];
                                            $str = Metademand::getContentForUser($title, $users_id, $title, $str, true);
                                        }
                                    }
                                    if ($find == true) {
                                        break;
                                    }
                                }

                                if (str_contains($match, "#")) {
                                    $line['tasks'][$key]['tickettasks_name'] = str_replace("[" . $match . "]", $str, $line['tasks'][$key]['tickettasks_name']);
                                    $l['tickettasks_name'] = str_replace("[" . $match . "]", $str, $l['tickettasks_name']);
                                } else {
                                    $line['tasks'][$key]['tickettasks_name'] = str_replace("[" . $match . "]", "<@" . $str . "@>", $line['tasks'][$key]['tickettasks_name']);
                                    $l['tickettasks_name'] = str_replace("[" . $match . "]", "<@" . $str . "@>", $l['tickettasks_name']);
                                }
                                //                                    $value['value'] = str_replace("[".$match."]", $str,  $value['value']);
                            }
                        } while (!empty($match));
                        //replace #id# for content
                        do {
                            $match = Metademand::getBetween($son_ticket_data['content'], '[', ']');
                            if (empty($match) && $son_ticket_data['content'] != null) {
                                $explodeContent = explode("#", $son_ticket_data['content']);
                                foreach ($explodeContent as $content) {
                                    if (isset($values['fields'][$content])) {
                                        $field = new Field();
                                        $field->getFromDB($content);
                                        $fields = $field->fields;
                                        $fields['value'] = '';

                                        $fields['value'] = $values['fields'][$content];

                                        $fields['value2'] = '';
                                        if (($fields['type'] == 'date_interval' || $fields['type'] == 'datetime_interval') && isset($values['fields'][$content . '-2'])) {
                                            $fields['value2'] = $values['fields'][$content . '-2'];
                                        }
                                        $result = [];
                                        $result['content'] = "";
                                        $result[$fields['rank']]['content'] = "";
                                        $result[$fields['rank']]['display'] = false;
                                        $parent_fields_id = 0;
                                        $value = Metademand::getContentWithField([], 0, $fields, $result, $parent_fields_id, true);
                                        if ($fields['type'] == "textarea") {
                                            if ($line['tasks'][$key]["formatastable"] == 0) {
                                                $value = str_replace("\\n", '","', $value);
                                            }
                                        }
                                        $line['tasks'][$key]['content'] = str_replace("#" . $content . "#", $value, $son_ticket_data['content']);
                                    } else {
                                        $explodeContent2 = explode(".", $content);

                                        if (isset($values['fields'][$explodeContent2[0]])) {
                                            $field_object = new Field();
                                            if ($field_object->getFromDB($explodeContent2[0])) {
                                                if ($field_object->fields['type'] == "dropdown_object" && $field_object->fields['item'] == User::getType()) {
                                                    $users_id = $values['fields'][$explodeContent2[0]];
                                                    $line['tasks'][$key]['content'] = Metademand::getContentForUser($explodeContent2[1], $users_id, $content, $son_ticket_data['content']);
                                                }
                                            }
                                        }
                                        $users_id = $parent_fields['_users_id_requester'];
                                        $line['tasks'][$key]['content'] = Metademand::getContentForUser($content, $users_id, $content, $son_ticket_data['content'], true);
                                    }
                                }
                            } else {
                                $explodeVal = [];
                                $explodeVal = explode("|", $match);
                                $find = false;
                                $val_to_replace = "";
                                foreach ($explodeVal as $str) {
                                    $explodeContent = explode("#", $str);
                                    foreach ($explodeContent as $content) {
                                        if (isset($values['fields'][$content])) {
                                            $field = new Field();
                                            $field->getFromDB($content);
                                            $fields = $field->fields;
                                            $fields['value'] = '';

                                            $fields['value'] = $values['fields'][$content];

                                            $fields['value2'] = '';
                                            if (($fields['type'] == 'date_interval' || $fields['type'] == 'datetime_interval') && isset($values['fields'][$content . '-2'])) {
                                                $fields['value2'] = $values['fields'][$content . '-2'];
                                            }
                                            $result = [];
                                            $result['content'] = "";
                                            $result[$fields['rank']]['content'] = "";
                                            $result[$fields['rank']]['display'] = false;
                                            $parent_fields_id = 0;
                                            $value = Metademand::getContentWithField([], 0, $fields, $result, $parent_fields_id, true);
                                            if ($fields['type'] == "textarea") {
                                                if ($line['tasks'][$key]["formatastable"] == 0) {
                                                    $value = str_replace("\\n", '","', $value);
                                                }
                                            }
                                            $str = str_replace("#" . $content . "#", $value, $str);
                                            if (!is_null($value) && !empty($value)) {
                                                $find = true;
                                            }
                                        } else {
                                            $explodeContent2 = explode(".", $content);

                                            if (isset($values['fields'][$explodeContent2[0]])) {
                                                $field_object = new Field();
                                                if ($field_object->getFromDB($explodeContent2[0])) {
                                                    if ($field_object->fields['type'] == "dropdown_object" && $field_object->fields['item'] == User::getType()) {
                                                        $users_id = $values['fields'][$explodeContent2[0]];
                                                        $str = Metademand::getContentForUser($explodeContent2[1], $users_id, $content, $str);
                                                    }
                                                }
                                            }
                                            $users_id = $parent_fields['_users_id_requester'];
                                            $str = Metademand::getContentForUser($content, $users_id, $content, $str, true);
                                        }
                                    }
                                    if ($find == true) {
                                        break;
                                    }
                                }
                                //                                    $line['tasks'][$key]['content'] = str_replace("[" . $match . "]", $str, $line['tasks'][$key]['content']);
                                if (str_contains($match, "#")) {
                                    $line['tasks'][$key]['content'] = str_replace("[" . $match . "]", $str, $son_ticket_data['content']);
                                    $l['content'] = str_replace("[" . $match . "]", $str, $l['content']);
                                } else {
                                    if ($line['tasks'][$key]['content'] != null) {
                                        $line['tasks'][$key]['content'] = str_replace("[" . $match . "]", "<@" . $str . "@>", $son_ticket_data['content']);
                                    }
                                    if ($l['content'] != null) {
                                        $l['content'] = str_replace("[" . $match . "]", "<@" . $str . "@>", $son_ticket_data['content']);
                                    }
                                }
                                //                                    $value['value'] = str_replace("[".$match."]", $str,  $value['value']);
                            }
                        } while (!empty($match));

                        if ($line['tasks'][$key]['content'] != null) {
                            $line['tasks'][$key]['content'] = str_replace("<@", "[", $line['tasks'][$key]['content']);
                            $line['tasks'][$key]['content'] = str_replace("@>", "]", $line['tasks'][$key]['content']);
                        }
                        if ($l['content'] != null) {
                            $l['content'] = str_replace("<@", "[", $l['content']);
                            $l['content'] = str_replace("@>", "]", $l['content']);
                        }
                        if ($l['content'] != null) {
                            $explodeContent = explode("#", $l['content']);
                            foreach ($explodeContent as $content) {
                                if (isset($values['fields'][$content])) {
                                    $field = new Field();
                                    $field->getFromDB($content);
                                    $fields = $field->fields;
                                    $fields['value'] = '';

                                    $fields['value'] = $values['fields'][$content];

                                    $fields['value2'] = '';
                                    if (($fields['type'] == 'date_interval'
                                            || $fields['type'] == 'datetime_interval')
                                        && isset($values['fields'][$content . '-2'])) {
                                        $fields['value2'] = $values['fields'][$content . '-2'];
                                    }
                                    $result = [];
                                    $result['content'] = "";
                                    $result[$fields['rank']]['content'] = "";
                                    $result[$fields['rank']]['display'] = false;
                                    $parent_fields_id = 0;
                                    $value = Metademand::getContentWithField([], 0, $fields, $result, $parent_fields_id, true);
                                    if ($fields['type'] == "textarea") {
                                        if ($line['tasks'][$key]["formatastable"] == 0) {
                                            $value = str_replace("\\n", '","', $value);
                                        }
                                    }
                                    $line['tasks'][$key]['content'] = str_replace("#" . $content . "#", $value, $line['tasks'][$key]['content']);
                                } else {
                                    $explodeContent2 = explode(".", $content);

                                    if (isset($values['fields'][$explodeContent2[0]])) {
                                        $field_object = new Field();
                                        if ($field_object->getFromDB($explodeContent2[0])) {
                                            if ($field_object->fields['type'] == "dropdown_object" && $field_object->fields['item'] == User::getType()) {
                                                $users_id = $values['fields'][$explodeContent2[0]];
                                                $line['tasks'][$key]['content'] = Metademand::getContentForUser($explodeContent2[1], $users_id, $content, $line['tasks'][$key]['content']);
                                            }
                                        }
                                    }
                                    $users_id = $parent_fields['_users_id_requester'];
                                    $line['tasks'][$key]['content'] = Metademand::getContentForUser($content, $users_id, $content, $line['tasks'][$key]['content'], true);
                                }
                            }
                        }
                        $recipients = [];
                        $email = new UserEmail();
                        $user = new User();
                        if (isset($mail->fields['groups_id_recipient']) && $mail->fields['groups_id_recipient'] > 0) {
                            $users = Group_User::getGroupUsers($mail->fields['groups_id_recipient']);
                            foreach ($users as $usr) {
                                $address = $email->find(['users_id' => $usr['id']], [], 1);
                                if (count($address) > 0) {
                                    foreach ($address as $id => $adr) {
                                        $recipients[$usr['id']]['email'] = $adr['email'];
                                    }
                                    $recipients[$usr['id']]['name'] = $usr['realname'] . " " . $usr['firstname'];
                                }
                            }
                        }
                        if (isset($mail->fields['users_id_recipient']) && $mail->fields['users_id_recipient'] > 0) {
                            $address = $email->find(['users_id' => $mail->fields['users_id_recipient']], [], 1);
                            $user->getFromDB($mail->fields['users_id_recipient']);
                            if (count($address) > 0) {
                                foreach ($address as $id => $adr) {
                                    $recipients[$user->fields['id']]['email'] = $adr['email'];
                                    $recipients[$user->fields['id']]['name'] = $user->fields['realname'] . " " . $user->fields['firstname'];
                                }
                            }
                        }
                        if (count($recipients) > 0) {
                            MailTask::sendMail($line['tasks'][$key]['tickettasks_name'], $recipients, $line['tasks'][$key]['content']);
                        }

                        unset($line['tasks'][$key]);
                    }
                }
                if ($metademand->fields['force_create_tasks'] == 0) {
                    //first sons
                    if (!Metademand::createSonsTickets(
                        $metademand->getID(),
                        $parent_tickets_id,
                        Metademand::mergeFields(
                            $parent_fields,
                            $parent_ticketfields
                        ),
                        $parent_tickets_id,
                        $line['tasks'],
                        $tasklevel,
                        $inputField,
                        $inputFieldMain
                    )) {
                        $KO[] = 1;
                    }
                } else {
                    $meta_tasks = $line['tasks'];
                    if (is_array($meta_tasks)) {
                        foreach ($meta_tasks as $meta_task) {
                            if (Ticket_Field::checkTicketCreation($meta_task['tasks_id'], $parent_tickets_id)) {
                                $ticket_task = new TicketTask();
                                $input = [];
                                $input['content'] = $meta_task['tickettasks_name'] . " " . $meta_task['content'];
                                $input['tickets_id'] = $parent_tickets_id;
                                $input['groups_id_tech'] = $meta_task["groups_id_assign"];
                                $input['users_id_tech'] = $meta_task["users_id_assign"];
                                if (!$ticket_task->add($input)) {
                                    $KO[] = 1;
                                }
                            }
                        }
                    }
                }
            } else {
                $metaValid = new MetademandValidation();
                $paramIn["tickets_id"] = $parent_tickets_id;
                $paramIn["plugin_metademands_metademands_id"] = $metademand->getID();
                $paramIn["users_id"] = 0;
                $paramIn["validate"] = MetademandValidation::TO_VALIDATE;
                $paramIn["date"] = date("Y-m-d H:i:s");

                foreach ($line['tasks'] as $key => $l) {
                    //replace #id# in title with the value
                    do {
                        if (isset($resource_id)) {
                            $resource = new PluginResourcesResource();
                            if ($resource->getFromDB($resource_id)) {
                                $line['tasks'][$key]['tickettasks_name'] .= " - " . $resource->getField('name') . " " . $resource->getField('firstname');
                            }
                            $line['tasks'][$key]['items_id'] = ['PluginResourcesResource' => [$resource_id]];
                        }
                        $match = Metademand::getBetween($l['tickettasks_name'], '[', ']');
                        if (empty($match)) {
                            $explodeTitle = [];
                            $explodeTitle = explode("#", $l['tickettasks_name']);
                            foreach ($explodeTitle as $title) {
                                if (isset($values['fields'][$title])) {
                                    $field = new Field();
                                    $field->getFromDB($title);
                                    $fields = $field->fields;
                                    $fields['value'] = '';

                                    $fields['value'] = $values['fields'][$title];

                                    $fields['value2'] = '';
                                    if (($fields['type'] == 'date_interval' || $fields['type'] == 'datetime_interval') && isset($values['fields'][$title . '-2'])) {
                                        $fields['value2'] = $values['fields'][$title . '-2'];
                                    }
                                    $result = [];
                                    $result['content'] = "";
                                    $result[$fields['rank']]['content'] = "";
                                    $result[$fields['rank']]['display'] = false;
                                    $parent_fields_id = 0;
                                    $value = Metademand::getContentWithField([], 0, $fields, $result, $parent_fields_id, true);
                                    if ($value != null) {
                                        $line['tasks'][$key]['tickettasks_name'] = str_replace("#" . $title . "#", $value, $line['tasks'][$key]['tickettasks_name']);
                                    }
                                } else {
                                    $explodeTitle2 = explode(".", $title);

                                    if (isset($values['fields'][$explodeTitle2[0]])) {
                                        $field_object = new Field();
                                        if ($field_object->getFromDB($explodeTitle2[0])) {
                                            if ($field_object->fields['type'] == "dropdown_object" && $field_object->fields['item'] == User::getType()) {
                                                $users_id = $values['fields'][$explodeTitle2[0]];
                                                $line['tasks'][$key]['tickettasks_name'] = Metademand::getContentForUser($explodeTitle2[1], $users_id, $_SESSION['glpiactive_entity'], $title, $line['tasks'][$key]['tickettasks_name']);
                                            }
                                        }
                                    }
                                    $users_id = $parent_fields['_users_id_requester'];
                                    $line['tasks'][$key]['tickettasks_name'] = Metademand::getContentForUser($title, $users_id, $_SESSION['glpiactive_entity'], $title, $line['tasks'][$key]['tickettasks_name'], true);
                                }
                            }
                        } else {
                            $explodeVal = [];
                            $explodeVal = explode("|", $match);
                            $find = false;
                            $val_to_replace = "";
                            foreach ($explodeVal as $str) {
                                $explodeTitle = explode("#", $str);
                                foreach ($explodeTitle as $title) {
                                    if (isset($values['fields'][$title])) {
                                        $field = new Field();
                                        $field->getFromDB($title);
                                        $fields = $field->fields;
                                        $fields['value'] = '';

                                        $fields['value'] = $values['fields'][$title];

                                        $fields['value2'] = '';
                                        if (($fields['type'] == 'date_interval' || $fields['type'] == 'datetime_interval') && isset($values['fields'][$title . '-2'])) {
                                            $fields['value2'] = $values['fields'][$title . '-2'];
                                        }
                                        $result = [];
                                        $result['content'] = "";
                                        $result[$fields['rank']]['content'] = "";
                                        $result[$fields['rank']]['display'] = false;
                                        $parent_fields_id = 0;
                                        $value = Metademand::getContentWithField([], 0, $fields, $result, $parent_fields_id, true);
                                        $str = str_replace("#" . $title . "#", $value, $str);
                                        if (!is_null($value) && !empty($value)) {
                                            $find = true;
                                        }
                                    } else {
                                        $explodeTitle2 = explode(".", $title);

                                        if (isset($values['fields'][$explodeTitle2[0]])) {
                                            $field_object = new Field();
                                            if ($field_object->getFromDB($explodeTitle2[0])) {
                                                if ($field_object->fields['type'] == "dropdown_object" && $field_object->fields['item'] == User::getType()) {
                                                    $users_id = $values['fields'][$explodeTitle2[0]];
                                                    $str = Metademand::getContentForUser($explodeTitle2[1], $users_id, $_SESSION['glpiactive_entity'], $title, $str);
                                                }
                                            }
                                        }
                                        $users_id = $parent_fields['_users_id_requester'];
                                        $str = Metademand::getContentForUser($title, $users_id, $_SESSION['glpiactive_entity'], $title, $str, true);
                                    }
                                }
                                if ($find == true) {
                                    break;
                                }
                            }

                            if (str_contains($match, "#")) {
                                $line['tasks'][$key]['tickettasks_name'] = str_replace("[" . $match . "]", $str, $line['tasks'][$key]['tickettasks_name']);
                                $l['tickettasks_name'] = str_replace("[" . $match . "]", $str, $l['tickettasks_name']);
                            } else {
                                $line['tasks'][$key]['tickettasks_name'] = str_replace("[" . $match . "]", "<@" . $str . "@>", $line['tasks'][$key]['tickettasks_name']);
                                $l['tickettasks_name'] = str_replace("[" . $match . "]", "<@" . $str . "@>", $l['tickettasks_name']);
                            }
                            //                                    $value['value'] = str_replace("[".$match."]", $str,  $value['value']);
                        }
                    } while (!empty($match));

                    $line['tasks'][$key]['tickettasks_name'] = str_replace("<@", "[", $line['tasks'][$key]['tickettasks_name']);
                    $line['tasks'][$key]['tickettasks_name'] = str_replace("@>", "]", $line['tasks'][$key]['tickettasks_name']);
                    $l['tickettasks_name'] = str_replace("<@", "[", $l['tickettasks_name']);
                    $l['tickettasks_name'] = str_replace("@>", "]", $l['tickettasks_name']);

                    $explodeTitle = explode("#", $l['tickettasks_name']);
                    foreach ($explodeTitle as $title) {
                        if (isset($values['fields'][$title])) {
                            $field = new Field();
                            $field->getFromDB($title);
                            $fields = $field->fields;
                            $fields['value'] = '';

                            $fields['value'] = $values['fields'][$title];

                            $fields['value2'] = '';
                            if (($fields['type'] == 'date_interval'
                                    || $fields['type'] == 'datetime_interval')
                                && isset($values['fields'][$title . '-2'])) {
                                $fields['value2'] = $values['fields'][$title . '-2'];
                            }
                            $result = [];
                            $result['content'] = "";
                            $result[$fields['rank']]['content'] = "";
                            $result[$fields['rank']]['display'] = false;

                            $parent_fields_id = 0;
                            $value = Metademand::getContentWithField([], 0, $fields, $result, $parent_fields_id, true);
                            if ($value != null) {
                                $line['tasks'][$key]['tickettasks_name'] = str_replace("#" . $title . "#", $value, $line['tasks'][$key]['tickettasks_name']);
                            }
                        } else {
                            $explodeTitle2 = explode(".", $title);

                            if (isset($values['fields'][$explodeTitle2[0]])) {
                                $field_object = new Field();
                                if ($field_object->getFromDB($explodeTitle2[0])) {
                                    if ($field_object->fields['type'] == "dropdown_object" && $field_object->fields['item'] == User::getType()) {
                                        $users_id = $values['fields'][$explodeTitle2[0]];
                                        $line['tasks'][$key]['tickettasks_name'] = Metademand::getContentForUser($explodeTitle2[1], $users_id, $_SESSION['glpiactive_entity'], $title, $line['tasks'][$key]['tickettasks_name']);
                                    }
                                }
                            }

                            $users_id = $parent_fields['_users_id_requester'];
                            $line['tasks'][$key]['tickettasks_name'] = Metademand::getContentForUser($title, $users_id, $_SESSION['glpiactive_entity'], $title, $line['tasks'][$key]['tickettasks_name'], true);
                        }
                    }

                    //replace #id# in content with the value
                    do {
                        $match = Metademand::getBetween($l['content'], '[', ']');
                        if (empty($match)) {
                            if ($l['content'] != null) {
                                $explodeContent = explode("#", $l['content']);
                                foreach ($explodeContent as $content) {
                                    if (isset($values['fields'][$content])) {
                                        $field = new Field();
                                        $field->getFromDB($content);
                                        $fields = $field->fields;
                                        $fields['value'] = '';

                                        $fields['value'] = $values['fields'][$content];

                                        $fields['value2'] = '';
                                        if (($fields['type'] == 'date_interval' || $fields['type'] == 'datetime_interval') && isset($values['fields'][$content . '-2'])) {
                                            $fields['value2'] = $values['fields'][$content . '-2'];
                                        }
                                        $result = [];
                                        $result['content'] = "";
                                        $result[$fields['rank']]['content'] = "";
                                        $result[$fields['rank']]['display'] = false;
                                        $parent_fields_id = 0;
                                        $value = Metademand::getContentWithField([], 0, $fields, $result, $parent_fields_id, true);
                                        if ($fields['type'] == "textarea") {
                                            if ($line['tasks'][$key]["formatastable"] == 0) {
                                                $value = str_replace("\\n", '","', $value);
                                            }
                                        }
                                        $line['tasks'][$key]['content'] = str_replace("#" . $content . "#", $value, $line['tasks'][$key]['content']);
                                    } else {
                                        $explodeContent2 = explode(".", $content);

                                        if (isset($values['fields'][$explodeContent2[0]])) {
                                            $field_object = new Field();
                                            if ($field_object->getFromDB($explodeContent2[0])) {
                                                if ($field_object->fields['type'] == "dropdown_object" && $field_object->fields['item'] == User::getType()) {
                                                    $users_id = $values['fields'][$explodeContent2[0]];
                                                    $line['tasks'][$key]['content'] = Metademand::getContentForUser($explodeContent2[1], $users_id, $_SESSION['glpiactive_entity'], $content, $line['tasks'][$key]['content']);
                                                }
                                            }
                                        }
                                        $users_id = $parent_fields['_users_id_requester'];
                                        $line['tasks'][$key]['content'] = Metademand::getContentForUser($content, $users_id, $_SESSION['glpiactive_entity'], $content, $line['tasks'][$key]['content'], true);
                                    }
                                }
                            }
                        } else {
                            $explodeVal = [];
                            $explodeVal = explode("|", $match);
                            $find = false;
                            $val_to_replace = "";
                            foreach ($explodeVal as $str) {
                                $explodeContent = explode("#", $str);
                                foreach ($explodeContent as $content) {
                                    if (isset($values['fields'][$content])) {
                                        $field = new Field();
                                        $field->getFromDB($content);
                                        $fields = $field->fields;
                                        $fields['value'] = '';

                                        $fields['value'] = $values['fields'][$content];

                                        $fields['value2'] = '';
                                        if (($fields['type'] == 'date_interval' || $fields['type'] == 'datetime_interval') && isset($values['fields'][$content . '-2'])) {
                                            $fields['value2'] = $values['fields'][$content . '-2'];
                                        }
                                        $result = [];
                                        $result['content'] = "";
                                        $result[$fields['rank']]['content'] = "";
                                        $result[$fields['rank']]['display'] = false;
                                        $parent_fields_id = 0;
                                        $value = Metademand::getContentWithField([], 0, $fields, $result, $parent_fields_id, true);
                                        if ($fields['type'] == "textarea") {
                                            if ($line['tasks'][$key]["formatastable"] == 0) {
                                                $value = str_replace("\\n", '","', $value);
                                            }
                                        }

                                        $str = str_replace("#" . $content . "#", $value, $str);
                                        if (!is_null($value) && !empty($value)) {
                                            $find = true;
                                        }
                                    } else {
                                        $explodeContent2 = explode(".", $content);

                                        if (isset($values['fields'][$explodeContent2[0]])) {
                                            $field_object = new Field();
                                            if ($field_object->getFromDB($explodeContent2[0])) {
                                                if ($field_object->fields['type'] == "dropdown_object" && $field_object->fields['item'] == User::getType()) {
                                                    $users_id = $values['fields'][$explodeContent2[0]];
                                                    $str = Metademand::getContentForUser($explodeContent2[1], $users_id, $_SESSION['glpiactive_entity'], $content, $str);
                                                }
                                            }
                                        }
                                        $users_id = $parent_fields['_users_id_requester'];
                                        $str = Metademand::getContentForUser($content, $users_id, $_SESSION['glpiactive_entity'], $content, $str, true);
                                    }
                                }
                                if ($find == true) {
                                    break;
                                }
                            }

                            if (str_contains($match, "#")) {
                                $line['tasks'][$key]['content'] = str_replace("[" . $match . "]", $str, $line['tasks'][$key]['content']);
                                $l['content'] = str_replace("[" . $match . "]", $str, $l['content']);
                            } else {
                                $line['tasks'][$key]['content'] = str_replace("[" . $match . "]", "<@" . $str . "@>", $line['tasks'][$key]['content']);
                                $l['content'] = str_replace("[" . $match . "]", "<@" . $str . "@>", $l['content']);
                            }
                            //                                    $value['value'] = str_replace("[".$match."]", $str,  $value['value']);
                        }
                    } while (!empty($match));

                    $line['tasks'][$key]['content'] = str_replace("<@", "[", $line['tasks'][$key]['content']);
                    $line['tasks'][$key]['content'] = str_replace("@>", "]", $line['tasks'][$key]['content']);
                    $l['content'] = str_replace("<@", "[", $l['content']);
                    $l['content'] = str_replace("@>", "]", $l['content']);

                    $explodeContent = explode("#", $l['content']);
                    foreach ($explodeContent as $content) {
                        if (isset($values['fields'][$content])) {
                            $field = new Field();
                            $field->getFromDB($content);
                            $fields = $field->fields;
                            $fields['value'] = '';

                            $fields['value'] = $values['fields'][$content];

                            $fields['value2'] = '';
                            if (($fields['type'] == 'date_interval'
                                    || $fields['type'] == 'datetime_interval')
                                && isset($values['fields'][$content . '-2'])) {
                                $fields['value2'] = $values['fields'][$content . '-2'];
                            }
                            $result = [];
                            $result['content'] = "";
                            $result[$fields['rank']]['content'] = "";
                            $result[$fields['rank']]['display'] = false;
                            $parent_fields_id = 0;
                            $value = Metademand::getContentWithField([], 0, $fields, $result, $parent_fields_id, true);
                            if ($fields['type'] == "textarea") {
                                if ($line['tasks'][$key]["formatastable"] == 0) {
                                    $value = str_replace("\\n", '","', $value);
                                }
                            }
                            $line['tasks'][$key]['content'] = str_replace("#" . $content . "#", $value, $line['tasks'][$key]['content']);
                        } else {
                            $explodeContent2 = explode(".", $content);

                            if (isset($values['fields'][$explodeContent2[0]])) {
                                $field_object = new Field();
                                if ($field_object->getFromDB($explodeContent2[0])) {
                                    if ($field_object->fields['type'] == "dropdown_object" && $field_object->fields['item'] == User::getType()) {
                                        $users_id = $values['fields'][$explodeContent2[0]];
                                        $line['tasks'][$key]['content'] = Metademand::getContentForUser($explodeContent2[1], $users_id, $_SESSION['glpiactive_entity'], $content, $line['tasks'][$key]['content']);
                                    }
                                }
                            }
                            $users_id = $parent_fields['_users_id_requester'];
                            $line['tasks'][$key]['content'] = Metademand::getContentForUser($content, $users_id, $_SESSION['glpiactive_entity'], $content, $line['tasks'][$key]['content'], true);
                        }
                    }
                }

                $tasks = $line['tasks'];
                foreach ($tasks as $key => $val) {
                    if (Ticket_Field::checkTicketCreation($val['tasks_id'], $parent_tickets_id)) {
                        $tasks[$key]['tickettasks_name'] = addslashes(urlencode($val['tickettasks_name']));
                        if (isset($input['items_id']['PluginResourcesResource'])) {
                            if ($resource->getFromDB($resource_id)) {
                                $tasks[$key]['tickettasks_name'] .= " " . $resource->fields['name'] . " " . $resource->fields['firstname'];
                                $tasks[$key]['items_id'] = ['PluginResourcesResource' => [$resource_id]];
                            }
                        }
                        if ($val['tasks_completename'] != null) {
                            $tasks[$key]['tasks_completename'] = addslashes(urlencode($val['tasks_completename']));
                        }
                        $tasks[$key]['content'] = addslashes(urlencode($val['content']));
                        $tasks[$key]['block_use'] = json_decode($val["block_use"], true);
                    } else {
                        unset($tasks[$key]);
                    }
                }

                $paramIn["tickets_to_create"] = json_encode($tasks);
                if ($metaValid->getFromDBByCrit(['tickets_id' => $paramIn["tickets_id"]])) {
                    $paramIn['id'] = $metaValid->getID();
                    $metaValid->update($paramIn);
                } else {
                    $metaValid->add($paramIn);
                }
            }
        } else {
            if ($metademand->fields["validation_subticket"] == 1) {
                $metaValid = new MetademandValidation();
                $paramIn["tickets_id"] = $parent_tickets_id;
                $paramIn["plugin_metademands_metademands_id"] = $metademand->getID();
                $paramIn["users_id"] = 0;
                $paramIn["validate"] = MetademandValidation::TO_VALIDATE_WITHOUTTASK;
                $paramIn["date"] = date("Y-m-d H:i:s");

                $paramIn["tickets_to_create"] = "";
                if ($metaValid->getFromDBByCrit(['tickets_id' => $paramIn["tickets_id"]])) {
                    $paramIn['id'] = $metaValid->getID();
                    $metaValid->update($paramIn);
                } else {
                    $metaValid->add($paramIn);
                }
            }
        }
    }

    /**
     * Check Tool for change Status of metademands without opened tickets
     * @param $ticket
     * @return void
     */
    static function changeMetademandGlobalStatus($ticket) {

        $ticket_metademand = new self();
        $ticket_metademand_data = $ticket_metademand->find(['parent_tickets_id' => $ticket->fields['id']]);
        $tickets_founded = [];
        $metademands_id = 0;
        // If ticket is Parent : Check if all sons ticket are closed
        if (count($ticket_metademand_data)) {
            $ticket_metademand_data = reset($ticket_metademand_data);
            $tickets_founded = Ticket::getSonTickets(
                $ticket->fields['id'],
                $ticket_metademand_data['plugin_metademands_metademands_id'],
                [],
                true,
                true
            );
            $metademands_id = $ticket_metademand_data['plugin_metademands_metademands_id'];
        } else {
            $ticket_task = new Ticket_Task();
            $ticket_task_data = $ticket_task->find(['tickets_id' => $ticket->fields['id']]);

            if (count($ticket_task_data)) {
                $tickets_founded = Ticket::getAncestorTickets($ticket->fields['id'], true);
            }
        }

        $tickets_list = [];
        $tickets_next = [];

        $statuses = [];
        if (is_array($tickets_founded)
            && count($tickets_founded)
            && $metademands_id > 0) {
            foreach ($tickets_founded as $tickets) {
                if (!empty($tickets['tickets_id'])) {
                    $tickets_list[] = $tickets;
                } else {
                    if (isset($tickets['tickets_id']) && $tickets['tickets_id'] == 0) {
                        $tickets_next[] = $tickets;
                    }
                }
            }
            if (count($tickets_list)) {
                foreach ($tickets_list as $values) {
                    $childticket = new \Ticket();
                    $childticket->getFromDB($values['tickets_id']);

                    $statuses[] = $childticket->fields['status'];
                }
            }
        }
        if (count($tickets_next) == 0) {
            $not_change_status = 0;
            foreach ($statuses as $status) {
                if (!in_array($status, [\Ticket::CLOSED])) {
                    $not_change_status++;
                }
            }
            $metaStatus = new self();
            if ($metaStatus->getFromDBByCrit(['tickets_id' => $ticket->fields['id']])
                && Session::haveRight('plugin_metademands', READ)
                && Session::getCurrentInterface() == 'central') {
                if ($not_change_status == 0) {
                    $validationmeta = new MetademandValidation();
                    $validation = $validationmeta->getFromDBByCrit(['tickets_id' => $ticket->fields['id']]);
                    $validation_todo = false;
                    if ($validation) {
                        if (in_array(
                            $validationmeta->fields['validate'],
                            [
                                MetademandValidation::TO_VALIDATE,
                                MetademandValidation::TO_VALIDATE_WITHOUTTASK
                            ]
                        )) {
                            $validation_todo = true;
                        }
                    }

                    if (!$validation_todo) {
                        $metaStatus->update(
                            [
                                'id' => $metaStatus->fields['id'],
                                'status' => self::TO_CLOSED
                            ]
                        );
                    } elseif ($validation_todo) {
                        $metaStatus->update(
                            [
                                'id' => $metaStatus->fields['id'],
                                'status' => self::RUNNING
                            ]
                        );
                    }
                } else {
                    $metaStatus->update(
                        ['id' => $metaStatus->fields['id'], 'status' => self::RUNNING]
                    );
                }
            }
        }
    }
}
