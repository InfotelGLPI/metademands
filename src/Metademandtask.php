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
    static function getMetademandTaskName($tasks_id)
    {
        global $DB;

        if ($tasks_id > 0) {
            $query = "SELECT `glpi_plugin_metademands_metademands`.`name`
               FROM `glpi_plugin_metademands_metademands`
               LEFT JOIN `glpi_plugin_metademands_metademandtasks`
                  ON (`glpi_plugin_metademands_metademandtasks`.`plugin_metademands_metademands_id` = `glpi_plugin_metademands_metademands`.`id`)
               WHERE `glpi_plugin_metademands_metademandtasks`.`plugin_metademands_tasks_id` = " . $tasks_id;
            $result = $DB->doQuery($query);

            if ($DB->numrows($result)) {
                while ($data = $DB->fetchAssoc($result)) {
                    return $data['name'];
                }
            }
        }
    }


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
    }

    /**
     * @param $metademands_id
     *
     * @return mixed
     * @throws \GlpitestSQLError
     */
//   static function getSonMetademandTaskId($metademands_id) {
//      global $DB;
//
//      $res    = [];
//      $query  = "SELECT `glpi_plugin_metademands_metademandtasks`.`plugin_metademands_tasks_id` as tasks_id,
//                       `glpi_plugin_metademands_metademandtasks`.`plugin_metademands_metademands_id` as metademands_id
//               FROM `glpi_plugin_metademands_metademandtasks`
//               LEFT JOIN `glpi_plugin_metademands_tasks`
//                  ON (`glpi_plugin_metademands_tasks`.`id` = `glpi_plugin_metademands_metademandtasks`.`plugin_metademands_tasks_id`)
//               WHERE `glpi_plugin_metademands_tasks`.`plugin_metademands_metademands_id` = " . $metademands_id;
//      $result = $DB->doQuery($query);
//
//      if ($DB->numrows($result)) {
//         while ($data = $DB->fetchAssoc($result)) {
//            $res[$data['metademands_id']] = $data['tasks_id'];
//         }
//         return $res;
//      }
//   }

    /**
     * @param $metademands_id
     *
     * @return mixed
     * @throws \GlpitestSQLError
     */
    static function getMetademandTask_TaskId($metademands_id)
    {
        global $DB;

        $return = [];

        $query = "SELECT `glpi_plugin_metademands_metademandtasks`.`plugin_metademands_tasks_id` as tasks_id
               FROM `glpi_plugin_metademands_metademandtasks`
               WHERE `glpi_plugin_metademands_metademandtasks`.`plugin_metademands_metademands_id` = " . $metademands_id;
        $result = $DB->doQuery($query);

        if ($DB->numrows($result)) {
            while ($data = $DB->fetchAssoc($result)) {
                $return['tasks_id'][] = $data['tasks_id'];
            }
        }
        return $return['tasks_id'];
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
        $query = "SELECT `glpi_plugin_metademands_tasks`.`plugin_metademands_metademands_id` as parent_metademands_id,
                       `glpi_plugin_metademands_tasks`.`id` as tasks_id
          FROM `glpi_plugin_metademands_tasks`
          LEFT JOIN `glpi_plugin_metademands_metademandtasks`
              ON (`glpi_plugin_metademands_metademandtasks`.`plugin_metademands_tasks_id` = `glpi_plugin_metademands_tasks`.`id`)
          WHERE `glpi_plugin_metademands_metademandtasks`.`plugin_metademands_metademands_id` = '$metademands_id'";
        $result = $DB->doQuery($query);
        if ($DB->numrows($result)) {
            while ($data = $DB->fetchAssoc($result)) {
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
