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

use CommonDBChild;
use DBConnection;
use Migration;
use Session;

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access directly to this file");
}

/**
 * Class MetademandTask
 */
class MetademandTask extends CommonDBChild
{

    public static $rightname = 'plugin_metademands';

    public static $itemtype = Metademand::class;
    public static $items_id = 'plugin_metademands_metademands_id';


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
        return __('Task creation', 'metademands');
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
                        `entities_id`                       int {$default_key_sign} NOT NULL DEFAULT '0',
                        `plugin_metademands_metademands_id` int {$default_key_sign} NOT NULL DEFAULT '0',
                        `plugin_metademands_tasks_id`       int {$default_key_sign} NOT NULL DEFAULT '0',
                        PRIMARY KEY (`id`),
                        KEY `plugin_metademands_metademands_id` (`plugin_metademands_metademands_id`),
                        KEY `entities_id` (`entities_id`),
                        KEY `plugin_metademands_tasks_id` (`plugin_metademands_tasks_id`)
               ) ENGINE=InnoDB DEFAULT CHARSET={$default_charset} COLLATE={$default_collation} ROW_FORMAT=DYNAMIC;";

            $DB->doQuery($query);
        }

        //version 3.3.0
        if (!$DB->fieldExists($table, "entities_id")) {
            $migration->addField($table, "entities_id", "int {$default_key_sign} NOT NULL DEFAULT '0'");
            if (!isIndex($table, "entities_id")) {
                $migration->addKey($table, "entities_id");
            }
            $migration->migrationOneTable($table);
        }
        //version 3.3.0
        if (!isIndex($table, "plugin_metademands_metademands_id")) {
            $migration->addKey($table, "plugin_metademands_metademands_id");
        }
        if (!isIndex($table, "plugin_metademands_tasks_id")) {
            $migration->addKey($table, "plugin_metademands_tasks_id");
        }
    }

    public static function uninstall()
    {
        global $DB;

        $DB->dropTable(self::getTable(), true);
    }

    /**
     * @param $ID
     *
     * @throws \GlpitestSQLError
     */
    static function showMetademandTaskForm($ID)
    {
        // Avoid select of parent metademands
        $used = MetademandTask::getAncestorOfMetademandTask($ID);
        $used[] = $ID;

        echo Metademand::getTypeName(1) . "&nbsp;:&nbsp;";
        \Dropdown::show(
            Metademand::class,
            [
                'name' => 'link_metademands_id',
                'is_deleted' => 0,
                'is_active' => 1,
                'used' => $used,
                'condition' => [
                    'is_order' => 0,
                    'object_to_create' => 'Ticket'
                ]
            ]
        );

        unset($used[array_search($ID, $used)]);

        foreach ($used as $metademands_id) {
            if ($metademands_id > 0) {
                echo "<br><span style='color:red'>" . __(
                        'This demand is already used in',
                        'metademands'
                    ) . "&nbsp;:&nbsp;" .
                    \Dropdown::getDropdownName('glpi_plugin_metademands_metademands', $metademands_id) . "</span>";
            }
        }
    }

    /**
     * @param $tasks_id
     *
     * @return mixed
     * @throws \GlpitestSQLError
     */
//    static function getMetademandTaskName($tasks_id)
//    {
//        global $DB;
//
//        if ($tasks_id > 0) {
//
//            $criteria = [
//                'SELECT' => 'glpi_plugin_metademands_metademands.name',
//                'FROM' => 'glpi_plugin_metademands_metademands',
//                'LEFT JOIN'       => [
//                    'glpi_plugin_metademands_metademandtasks' => [
//                        'ON' => [
//                            'glpi_plugin_metademands_metademandtasks' => 'plugin_metademands_metademands_id',
//                            'glpi_plugin_metademands_metademands'          => 'id'
//                        ]
//                    ]
//                ],
//                'WHERE' => [
//                    'glpi_plugin_metademands_metademandtasks.plugin_metademands_tasks_id' => $tasks_id,
//                ],
//            ];
//            $iterator = $DB->request($criteria);
//            if (count($iterator) > 0) {
//                foreach ($iterator as $data) {
//                    return $data['name'];
//                }
//            }
//        }
//        return "";
//    }


    static function getChildMetademandsToCreate($ID)
    {
        $tasks = new Task();
        $existing_tasks = $tasks->find(
            ["plugin_metademands_metademands_id" => $ID, "type" => Task::METADEMAND_TYPE]
        );

        $childs = [];
        foreach ($existing_tasks as $k => $existing_task) {
            $mtasks = new self();
            if ($mtasks->getFromDBByCrit(['plugin_metademands_tasks_id' => $k])) {
                $_SESSION['metademands_child_meta'][$mtasks->fields["plugin_metademands_metademands_id"]] = $mtasks->fields["plugin_metademands_metademands_id"];
                $childs[] = $mtasks->fields["plugin_metademands_metademands_id"];
            }
        }
        return $childs;
    }

    static function setUsedTask($tasks_id, $used)
    {
        $tasks = new Task();
        if ($tasks->getFromDB($tasks_id)) {
            if ($tasks->fields['type'] == Task::METADEMAND_TYPE) {
                $metaTask = new MetademandTask();
                if ($metaTask->getFromDBByCrit(['plugin_metademands_tasks_id' => $tasks_id])) {

                    $idChild = $metaTask->getField('plugin_metademands_metademands_id');

                    if ($used == 0) {
                        $_SESSION['childs_metademands_hide'][$idChild] = $idChild;
                        return false;
                    } else {
                        unset($_SESSION['childs_metademands_hide'][$idChild]);
                        return true;
                    }
                }
            }
        }
        return false;
    }


    /**
     * @param       $metademands_id
     * @param array $id_found
     *
     * @return array
     * @throws \GlpitestSQLError
     */
    static function getAncestorOfMetademandTask($metademands_id, $id_found = [])
    {
        global $DB;

        $metademandtask = new self();

        // Get next elements
        $criteria = [
            'SELECT' => [
                'glpi_plugin_metademands_tasks.plugin_metademands_metademands_id AS parent_metademands_id',
                'glpi_plugin_metademands_tasks.id AS tasks_id'
            ],
            'FROM' => 'glpi_plugin_metademands_tasks',
            'LEFT JOIN' => [
                'glpi_plugin_metademands_metademandtasks' => [
                    'ON' => [
                        'glpi_plugin_metademands_metademandtasks' => 'plugin_metademands_tasks_id',
                        'glpi_plugin_metademands_tasks' => 'id'
                    ]
                ]
            ],
            'WHERE' => [
                'glpi_plugin_metademands_metademandtasks.plugin_metademands_metademands_id' => $metademands_id,
            ],
        ];
        $iterator = $DB->request($criteria);
        if (count($iterator) > 0) {
            foreach ($iterator as $data) {
                $id_found[] = $data['parent_metademands_id'];
                $id_found = $metademandtask->getAncestorOfMetademandTask($data['parent_metademands_id'], $id_found);
            }
        }

        return $id_found;
    }

    function post_deleteFromDB()
    {
        $metademands_id = $this->fields['plugin_metademands_metademands_id'];

        // list of parents
        $metademands_parent = MetademandTask::getAncestorOfMetademandTask($metademands_id);

        $field = new Field();
        $fields = $field->find([
            'type' => 'parent_field',
            'plugin_metademands_metademands_id' => $metademands_id
        ]);

        //delete of the metademand fields in the present child requests as father fields
        foreach ($fields as $data) {
            if (isset($data['parent_field_id']) && $field->getFromDB($data['parent_field_id'])) {
                if (!in_array($field->fields['plugin_metademands_metademands_id'], $metademands_parent)) {
                    $field->delete(['id' => $field->getID()]);
                }
            }
        }
    }
}
