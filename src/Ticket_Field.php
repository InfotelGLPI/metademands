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
use DBConnection;
use Migration;
use Session;
use Toolbox;

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access directly to this file");
}

/**
 * Class Ticket_Field
 */
class Ticket_Field extends CommonDBTM
{

    public $itemtype = Metademand::class;

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
    static function canView(): bool
    {
        return Session::haveRight(self::$rightname, READ);
    }

    /**
     * @return bool
     */
    static function canCreate(): bool
    {
        return Session::haveRightsOr(self::$rightname, [CREATE, UPDATE, DELETE]);
    }

    public static function install(Migration $migration)
    {
        global $DB;

        $default_charset   = DBConnection::getDefaultCharset();
        $default_collation = DBConnection::getDefaultCollation();
        $default_key_sign  = DBConnection::getDefaultPrimaryKeySignOption();
        $table  = self::getTable();

        if (!$DB->tableExists($table)) {
            $query = "CREATE TABLE `$table` (
                        `id` int {$default_key_sign} NOT NULL auto_increment,
                        `value`                        text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
                        `value2`                       text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
                        `tickets_id`                   int {$default_key_sign} NOT NULL           DEFAULT '0',
                        `plugin_metademands_fields_id` int {$default_key_sign} NOT NULL           DEFAULT '0',
                        PRIMARY KEY (`id`),
                        KEY `plugin_metademands_fields_id` (`plugin_metademands_fields_id`),
                        KEY `tickets_id` (`tickets_id`)
               ) ENGINE=InnoDB DEFAULT CHARSET={$default_charset} COLLATE={$default_collation} ROW_FORMAT=DYNAMIC;";

            $DB->doQuery($query);
        }

        //version 3.2.0
        if (!$DB->fieldExists($table, "value2")) {
            $migration->addField($table, "value2", "text COLLATE utf8mb4_unicode_ci DEFAULT NULL");
            $migration->migrationOneTable($table);
        }

        //version 3.3.0
        if ($DB->fieldExists($table, "color")) {
            $migration->dropField($table, "color");
            if (!isIndex($table, "plugin_metademands_fields_id")) {
                $migration->addKey($table, "plugin_metademands_fields_id");
            }
            if (!isIndex($table, "tickets_id")) {
                $migration->addKey($table, "tickets_id");
            }
            $migration->migrationOneTable($table);
        }
    }

    public static function uninstall()
    {
        global $DB;

        $DB->dropTable(self::getTable(), true);
    }

    /**
     * @param $parent_fields
     * @param $values
     * @param $tickets_id
     */
    function setTicketFieldsValues($parent_fields, $values, $tickets_id, $linked_docs = [])
    {

        $ticket = new \Ticket();
        $ticket->getFromDB($tickets_id);
        if (count($parent_fields)) {
            foreach ($parent_fields as $fields_id => $field) {

                $fieldparameter            = new FieldParameter();
                if ($fieldparameter->getFromDBByCrit(['plugin_metademands_fields_id' => $fields_id])) {
                    unset($fieldparameter->fields['plugin_metademands_fields_id']);
                    unset($fieldparameter->fields['id']);

                    $params = $fieldparameter->fields;
                    $field = array_merge($field, $params);
                    if (isset($fieldparameter->fields['default'])) {
                        $field['default_values'] = FieldParameter::_unserialize($fieldparameter->fields['default']);
                    }

                    if (isset($fieldparameter->fields['custom'])) {
                        $field['custom_values'] = FieldParameter::_unserialize($fieldparameter->fields['custom']);
                    }
                }

                $allowed_customvalues_types = FieldCustomvalue::$allowed_customvalues_types;
                $allowed_customvalues_items = FieldCustomvalue::$allowed_customvalues_items;

                if (isset($field['type'])
                    && in_array($field['type'], $allowed_customvalues_types)
                    || in_array($field['item'], $allowed_customvalues_items)) {
                    $field_custom = new FieldCustomvalue();
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

                $this->add(['value' => $field['value'],
                    'value2' => $field['value2'],
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

        $iterator = $DB->request([
            'SELECT'    => [
                'glpi_plugin_metademands_fieldoptions.check_value',
                'glpi_plugin_metademands_fields.type',
                'glpi_plugin_metademands_fieldoptions.plugin_metademands_tasks_id',
                'glpi_plugin_metademands_tickets_fields.plugin_metademands_fields_id',
                'glpi_plugin_metademands_tickets_fields.value AS field_value',
            ],
            'FROM'      => 'glpi_plugin_metademands_tickets_fields',
            'RIGHT JOIN'       => [
                'glpi_plugin_metademands_fields' => [
                    'ON' => [
                        'glpi_plugin_metademands_fields' => 'id',
                        'glpi_plugin_metademands_tickets_fields'          => 'plugin_metademands_fields_id'
                    ]
                ],
                'glpi_plugin_metademands_fieldoptions' => [
                    'ON' => [
                        'glpi_plugin_metademands_fields' => 'id',
                        'glpi_plugin_metademands_fieldoptions'          => 'plugin_metademands_fields_id', [
                            'AND' => [
                                'glpi_plugin_metademands_tickets_fields.tickets_id' => $parent_tickets_id,
                            ],
                        ],
                    ]
                ]
            ]
        ]);

        if (count($iterator) > 0) {
            foreach ($iterator as $data) {

                $plugin_metademands_tasks_id = $data['plugin_metademands_tasks_id'];
                $check_values = $data['check_value'];
                if (is_array($tasks_id)) {
                    foreach ($tasks_id as $task) {
                        if ($task == $plugin_metademands_tasks_id) {
                            $test = self::isCheckValueOKFieldsLinks(FieldParameter::_unserialize($data['field_value']) ?? $data['field_value'], $check_values, $data['type']);
                            $check[] = ($test == false) ? 0 : 1;
                        }
                    }
                } else if ($tasks_id == $plugin_metademands_tasks_id) {
                    $test = self::isCheckValueOKFieldsLinks(FieldParameter::_unserialize($data['field_value']) ?? $data['field_value'], $check_values, $data['type']);
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

        $class = Field::getClassFromType($type);

        if (isset($check_value)) {
            switch ($type) {
                case 'informations':
                case 'title-block':
                case 'basket':
                case 'upload':
                case 'datetime_interval':
                case 'date_interval':
                case 'datetime':
                case 'time':
                case 'date':
                case 'freetable':
                case 'range':
                case 'number':
                case 'title':
                    break;
                case 'link':
                case 'yesno':
                case 'checkbox':
                case 'tel':
                case 'email':
                case 'url':
                case 'textarea':
                case 'dropdown_object':
                case 'dropdown_ldap':
                case 'dropdown_meta':
                case 'dropdown_multiple':
                case 'dropdown':
                case 'radio':
                case 'text':
                    $class::isCheckValueOK($value, $check_value);
                    break;
                default:
                    if ($check_value == Field::$not_null && empty($value)) {
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
                case 'dropdown_ldap':
                case 'dropdown_meta':
                    if (($check_value == Field::$not_null || $check_value == 0) && empty($field_value)) {
                        return false;
                    } else if ($check_value != $field_value
                        && ($check_value != Field::$not_null && $check_value != 0)) {
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
                    if ((($check_value == Field::$not_null || $check_value == 0) && empty($field_value))) {
                        return false;
                    }
                    break;
                case 'text':
                case 'tel':
                case 'email':
                case 'url':
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
                    if (!is_array($field_value) && $check_value == Field::$not_null && empty($field_value)) {
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
