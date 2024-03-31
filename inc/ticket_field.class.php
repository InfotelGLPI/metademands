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

use Glpi\Toolbox\Sanitizer;

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access directly to this file");
}

/**
 * Class PluginMetademandsTicket_Field
 */
class PluginMetademandsTicket_Field extends CommonDBTM
{

    public $itemtype = 'PluginMetademandsMetademand';

    static $rightname = 'plugin_metademands';

    /**
     * functions mandatory
     * getTypeName(), canCreate(), canView()
     *
     * @param int $nb
     *
     * @return string
     */
    static function getTypeName($nb = 0)
    {
        return __('Wizard creation', 'metademands');
    }

    /**
     * @return bool|int
     */
    static function canView()
    {
        return Session::haveRight(self::$rightname, READ);
    }

    /**
     * @return bool
     */
    static function canCreate()
    {
        return Session::haveRightsOr(self::$rightname, [CREATE, UPDATE, DELETE]);
    }

    /**
     * @param $parent_fields
     * @param $values
     * @param $tickets_id
     */
    function setTicketFieldsValues($parent_fields, $values, $tickets_id, $linked_docs = [])
    {

        $ticket = new Ticket();
        $ticket->getFromDB($tickets_id);
        if (count($parent_fields)) {
            foreach ($parent_fields as $fields_id => $field) {

                $fieldparameter            = new PluginMetademandsFieldParameter();
                if ($fieldparameter->getFromDBByCrit(['plugin_metademands_fields_id' => $fields_id])) {
                    unset($fieldparameter->fields['plugin_metademands_fields_id']);
                    unset($fieldparameter->fields['id']);

                    $params = $fieldparameter->fields;
                    $field = array_merge($field, $params);
                    if (isset($fieldparameter->fields['default'])) {
                        $field['default_values'] = PluginMetademandsFieldParameter::_unserialize($fieldparameter->fields['default']);
                    }

                    if (isset($fieldparameter->fields['custom'])) {
                        $field['custom_values'] = PluginMetademandsFieldParameter::_unserialize($fieldparameter->fields['custom']);
                    }
                }

                $allowed_customvalues_types = PluginMetademandsFieldCustomvalue::$allowed_customvalues_types;
                $allowed_customvalues_items = PluginMetademandsFieldCustomvalue::$allowed_customvalues_items;

                if (isset($field['type'])
                    && in_array($field['type'], $allowed_customvalues_types)
                    || in_array($field['item'], $allowed_customvalues_items)) {
                    $field_custom = new PluginMetademandsFieldCustomvalue();
                    if ($customs = $field_custom->find(["plugin_metademands_fields_id" => $fields_id], "rank")) {
                        if (count($customs) > 0) {
                            $field['custom_values'] = $customs;
                        }
                    }
                }

                $field['value'] = '';
                if (isset($values[$fields_id]) && !is_array($values[$fields_id])) {

                    if ($field['type'] == "textarea"  && $field['use_richtext'] == 1) {
                        $field['value'] = Toolbox::convertTagToImage($values[$fields_id], $ticket, $linked_docs, false);
                        $field['value'] = Sanitizer::unsanitize($field['value']);
                        $field['value'] = Toolbox::addslashes_deep($field['value']);
                    } else {
                        $field['value'] = $values[$fields_id];
                    }

                } else if (isset($values[$fields_id]) && is_array($values[$fields_id])) {
                    $field['value'] = json_encode($values[$fields_id]);
                }
                $field['value2'] = '';
                if (isset($values[$fields_id . "-2"]) && !is_array($values[$fields_id . "-2"])) {
                    $field['value2'] = $values[$fields_id . "-2"];
                } else if (isset($values[$fields_id . "-2"]) && is_array($values[$fields_id . "-2"])) {
                    $field['value2'] = json_encode($values[$fields_id . "-2"]);
                }

                $this->add(['value' => Toolbox::addslashes_deep($field['value']),
                    'value2' => Toolbox::addslashes_deep($field['value2']),
                    'tickets_id' => $tickets_id,
                    'plugin_metademands_fields_id' => $fields_id]);
            }
        }
    }

    /**
     * @param $tasks_id
     * @param $parent_tickets_id
     *
     * @return bool
     * @throws \GlpitestSQLError
     */
    static function checkTicketCreation($tasks_id, $parent_tickets_id)
    {
        global $DB;

        $check = [];

        $query = "SELECT `glpi_plugin_metademands_fieldoptions`.`check_value`,
                       `glpi_plugin_metademands_fields`.`type`,
                       `glpi_plugin_metademands_fieldoptions`.`plugin_metademands_tasks_id`,
                       `glpi_plugin_metademands_tickets_fields`.`plugin_metademands_fields_id`,
                       `glpi_plugin_metademands_tickets_fields`.`value` as field_value
               FROM `glpi_plugin_metademands_tickets_fields`
               RIGHT JOIN `glpi_plugin_metademands_fields`
                  ON (`glpi_plugin_metademands_fields`.`id` = `glpi_plugin_metademands_tickets_fields`.`plugin_metademands_fields_id`)
              RIGHT JOIN `glpi_plugin_metademands_fieldoptions`
                  ON (`glpi_plugin_metademands_fields`.`id` = `glpi_plugin_metademands_fieldoptions`.`plugin_metademands_fields_id`)
               AND `glpi_plugin_metademands_tickets_fields`.`tickets_id` = " . $parent_tickets_id;
        $result = $DB->query($query);

        if ($DB->numrows($result)) {
            while ($data = $DB->fetchAssoc($result)) {

                $plugin_metademands_tasks_id = $data['plugin_metademands_tasks_id'];
                $check_values = $data['check_value'];
                if (is_array($tasks_id)) {
                    foreach ($tasks_id as $task) {
                        if ($task == $plugin_metademands_tasks_id) {
                            $test = self::isCheckValueOKFieldsLinks(PluginMetademandsFieldParameter::_unserialize($data['field_value']) ?? $data['field_value'], $check_values, $data['type']);
                            $check[] = ($test == false) ? 0 : 1;
                        }
                    }
                } else if ($tasks_id == $plugin_metademands_tasks_id) {
                    $test = self::isCheckValueOKFieldsLinks(PluginMetademandsFieldParameter::_unserialize($data['field_value']) ?? $data['field_value'], $check_values, $data['type']);
                    $check[] = ($test == false) ? 0 : 1;
                }
            }
        }

        if (in_array(1, $check)) {
            return true;
        } else if (in_array(0, $check)) {
            return false;
        }

        return true;
    }

    /**
     * @param $value
     * @param $check_values
     * @param $type
     *
     * @return bool
     */
    static function isCheckValueOK($value, $check_value, $type)
    {

        if (isset($check_value)) {
            switch ($type) {
                case 'title':
                    break;
                case 'title-block':
                    break;
                case 'informations':
                    break;
                case 'text':
                    PluginMetademandsText::isCheckValueOK($value, $check_value);
                    break;
                case 'textarea':
                    PluginMetademandsTextarea::isCheckValueOK($value, $check_value);
                    break;
                case 'dropdown_meta':
                    PluginMetademandsDropdownmeta::isCheckValueOK($value, $check_value);
                    break;
                case 'dropdown_object':
                    PluginMetademandsDropdownobject::isCheckValueOK($value, $check_value);
                    break;
                case 'dropdown':
                    PluginMetademandsDropdown::isCheckValueOK($value, $check_value);
                    break;
                case 'dropdown_multiple':
                    PluginMetademandsDropdownmultiple::isCheckValueOK($value, $check_value);
                    break;
                case 'radio':
                    PluginMetademandsRadio::isCheckValueOK($value, $check_value);
                    break;
                case 'checkbox':
                    PluginMetademandsCheckbox::isCheckValueOK($value, $check_value);
                    break;
                case 'yesno':
                    PluginMetademandsYesno::isCheckValueOK($value, $check_value);
                    break;
                case 'number':
                    break;
                case 'date':
                    break;
                case 'time':
                    break;
                case 'datetime':
                    break;
                case 'date_interval':
                    break;
                case 'datetime_interval':
                    break;
                case 'upload':
                    break;
                case 'link':
                    PluginMetademandsLink::isCheckValueOK($value, $check_value);
                    break;
                case 'basket':
                    break;
                default:
                    if ($check_value == PluginMetademandsField::$not_null && empty($value)) {
                        return false;
                    }
                    break;
            }
        }
        return true;
    }

    /**
     * @param $value
     * @param $check_value
     * @param $type
     * @return bool
     */
    static function isCheckValueOKFieldsLinks($field_value, $check_value, $type)
    {


        if (isset($check_value)) {
            switch ($type) {
                case 'yesno':
                case 'dropdown':
                case 'dropdown_object':
                case 'dropdown_meta':
                    if (($check_value == PluginMetademandsField::$not_null || $check_value == 0) && empty($field_value)) {
                        return false;
                    } else if ($check_value != $field_value
                        && ($check_value != PluginMetademandsField::$not_null && $check_value != 0)) {
                        return false;
                    }
                    break;
                case 'radio':
                    if (is_null($field_value)) {
                        return false;
                    } else if (strval($check_value) !== strval($field_value)) {
                        return false;
                    }
                    break;

                case 'checkbox':
                    if (!empty($field_value)) {
                        $ok = false;
                        if ($check_value == -1) {
                            $ok = true;
                        }
                        if (is_array($field_value)) {
                            foreach ($field_value as $key => $v) {
                                //                     if ($key != 0) {
                                if ($check_value == $key) {
                                    $ok = true;
                                }
                                //                     }
                            }
                        } else if (is_array(json_decode($field_value, true))) {
                            foreach (json_decode($field_value, true) as $key => $v) {
                                //                     if ($key != 0) {
                                if ($check_value == $key) {
                                    $ok = true;
                                }
                                //                     }
                            }
                        }
                        if (!$ok) {
                            return false;
                        }
                    } else {
                        return false;
                    }
                    break;
                case 'link':
                    if ((($check_value == PluginMetademandsField::$not_null || $check_value == 0) && empty($field_value))) {
                        return false;
                    }
                    break;
                case 'text':
                case 'textarea':
                    if (($check_value == 2 && $field_value != "")) {
                        return false;
                    } elseif ($check_value == 1 && $field_value == "") {
                        return false;
                    }
                    break;
                case 'dropdown_multiple':
                    if (empty($field_value)) {
                        $field_value = [];
                    }
                    if ($check_value == 0 && is_array($field_value) && count($field_value) == 0) {
                        return false;
                    }
                    if (is_array($field_value) && $check_value > 0 && !in_array($check_value, $field_value)) {
                        return false;
                    }
                    break;

                default:
                    if (!is_array($field_value) && $check_value == PluginMetademandsField::$not_null && empty($field_value)) {
                        return false;
                    }
                    if (is_array($field_value)) {
                        $ok = false;
                        foreach ($field_value as $key => $v) {
                            if ($check_value == $key) {
                                $ok = true;
                            }
                        }
                        if (!$ok) {
                            return false;
                        }
                    }
                    break;
            }
        }

        return true;
    }

}
