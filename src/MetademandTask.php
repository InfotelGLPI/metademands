<?php

/**
 * -------------------------------------------------------------------------
 * metademands plugin for GLPI
 * Copyright (C) 2018-2026 by the metademands Development Team.
 *
 * https://github.com/InfotelGLPI/metademands
 * -------------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of metademands.
 *
 * metademands is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * metademands is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with metademands. If not, see <http://www.gnu.org/licenses/>.
 * --------------------------------------------------------------------------
 */

namespace GlpiPlugin\Metademands;

use CommonDBChild;
use DBConnection;
use Glpi\Application\View\TemplateRenderer;
use Glpi\Exception\Http\AccessDeniedHttpException;
use Migration;
use Session;

/**
 * Class MetademandTask
 */
class MetademandTask extends CommonDBChild
{
    public static string $rightname = 'plugin_metademands';

    public static string $itemtype = Metademand::class;
    public static string $items_id = 'plugin_metademands_metademands_id';


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
                        `destination_entities_id`           int {$default_key_sign} DEFAULT NULL,
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
        // destination entity of the ticket created by the linked sub-metademand
        // (NULL = no override, use the active entity)
        if (!$DB->fieldExists($table, "destination_entities_id")) {
            $migration->addField($table, "destination_entities_id", "int {$default_key_sign} DEFAULT NULL");
            $migration->migrationOneTable($table);
        } else {
            // Legacy installs declared this column as a signed FK with a "-1"
            // sentinel meaning "active entity". That negative default makes it
            // incompatible with the migration:unsigned_keys console command.
            // Convert it to an unsigned nullable FK using NULL as the sentinel.
            // The column is nulled out *before* switching to unsigned, otherwise
            // MySQL would turn the signed "-1" into 4294967295 (or error out in
            // strict mode).
            $field_info = $DB->getField($table, "destination_entities_id");
            $is_signed  = isset($field_info["Type"])
                && stripos($field_info["Type"], "unsigned") === false;
            if ($is_signed) {
                $migration->changeField(
                    $table,
                    "destination_entities_id",
                    "destination_entities_id",
                    "int DEFAULT NULL",
                );
                $migration->migrationOneTable($table);
                $DB->update(
                    $table,
                    ["destination_entities_id" => null],
                    ["destination_entities_id" => -1],
                );
                $migration->changeField(
                    $table,
                    "destination_entities_id",
                    "destination_entities_id",
                    "int {$default_key_sign} DEFAULT NULL",
                );
                $migration->migrationOneTable($table);
            }
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

    public function prepareInputForAdd($input)
    {
        if (is_array($input)) {
            $input = $this->checkDestinationEntity($input);
        }

        return parent::prepareInputForAdd($input);
    }

    public function prepareInputForUpdate($input)
    {
        if (is_array($input)) {
            $input = $this->checkDestinationEntity($input);
        }

        return parent::prepareInputForUpdate($input);
    }

    /**
     * Normalize and revalidate the destination entity carried by the input.
     *
     * The value comes from a dropdown whose option list is restricted client side only, and it
     * becomes the entity of the ticket created by the linked sub-metademand (see
     * Metademand::addObjects()): it has to be confronted with the perimeter of the session on
     * every write path, and not only in front/task.form.php.
     *
     * @param array $input
     *
     * @return array
     */
    private function checkDestinationEntity(array $input): array
    {
        if (!array_key_exists('destination_entities_id', $input)) {
            return $input;
        }

        $entity = $input['destination_entities_id'];

        // The dropdown uses -1 as the "active entity" option; store it as NULL (no override).
        // Any real entity id (including the root, 0) is stored as-is.
        if (!is_numeric($entity) || (int) $entity < 0) {
            $input['destination_entities_id'] = null;

            return $input;
        }

        // Same criterion as the option list built by showMetademandTaskForm().
        if (!in_array((int) $entity, self::getAllowedDestinationEntities(), true)) {
            throw new AccessDeniedHttpException();
        }

        $input['destination_entities_id'] = (int) $entity;

        return $input;
    }

    /**
     * Criteria restricting the metademands that can be linked to a task of the metademand $ID:
     * neither the metademand itself nor one of its ancestors (that would create an endless chain
     * of tickets), and only active ticket demands.
     *
     * Single source of truth: the dropdown of showMetademandTaskForm() is built from these
     * criteria, and the write paths replay them on the posted value.
     *
     * @param int $ID metademand the task belongs to
     *
     * @return array
     */
    public static function getLinkableMetademandCriteria($ID): array
    {
        $used   = self::getAncestorOfMetademandTask($ID);
        $used[] = (int) $ID;

        return [
            'is_deleted'       => 0,
            'is_active'        => 1,
            'is_order'         => 0,
            'object_to_create' => 'Ticket',
            'NOT'              => ['id' => $used],
        ];
    }

    /**
     * Entities that can be picked as the destination entity of a linked sub-metademand.
     *
     * Every entity the current user can reach through their profile (recursively), and not only
     * the currently active ones: see the comment in showMetademandTaskForm().
     *
     * @return int[]
     */
    public static function getAllowedDestinationEntities(): array
    {
        return array_map('intval', \Profile_User::getUserEntities($_SESSION['glpiID'] ?? 0, true));
    }

    /**
     * @param $ID
     *
     * @throws \GlpitestSQLError
     */
    public static function showMetademandTaskForm($ID, $selected_entity = -1)
    {
        $criteria  = self::getLinkableMetademandCriteria($ID);
        $used      = $criteria['NOT']['id'];
        $condition = $criteria;
        unset($condition['NOT']);

        // $used loses the current metademand below, the dropdown still excludes it
        $dropdown_used = $used;

        // Destination entity of the ticket created by the linked sub-metademand
        // (-1 = no override, use the active entity of the requester).
        // We build the option list from every entity the current user can reach
        // through their profile (recursively) instead of using the standard AJAX
        // Entity::dropdown: the latter only lists the *currently active* entities
        // (both client- and server-side, via Session::getMatchingActiveEntities),
        // which would make it impossible to pick a destination entity outside the
        // one the administrator is currently positioned in.
        $entities = [-1 => __('Active entity', 'metademands')];
        foreach (self::getAllowedDestinationEntities() as $entity_id) {
            $entities[$entity_id] = \Dropdown::getDropdownName('glpi_entities', $entity_id);
        }
        unset($used[array_search($ID, $used)]);

        $ancestors = [];
        foreach ($used as $metademands_id) {
            if ($metademands_id > 0) {
                $ancestors[] = \Dropdown::getDropdownName('glpi_plugin_metademands_metademands', $metademands_id);
            }
        }

        TemplateRenderer::getInstance()->display('@metademands/metademandtask_form.html.twig', [
            'metademand_typename'  => Metademand::getTypeName(1),
            'metademand_itemtype'  => Metademand::class,
            'dropdown_used'        => $dropdown_used,
            'dropdown_condition'   => $condition,
            'entity_label'         => __('Destination entity', 'metademands'),
            'entities'             => $entities,
            'selected_entity'      => $selected_entity,
            'ancestors'            => $ancestors,
        ]);
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


    public static function getChildMetademandsToCreate($ID)
    {
        $tasks = new Task();
        $existing_tasks = $tasks->find(
            ["plugin_metademands_metademands_id" => $ID, "type" => Task::METADEMAND_TYPE],
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

    public static function setUsedTask($tasks_id, $used)
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
     * Flag the child metademands linked to the values of a field as used or not when
     * the field changes.
     *
     * Replaces the script the Fields classes generated: a marker read by
     * public/scripts/wizard_form.js (initTaskTrigger) binds the change handler,
     * which posts to ajax/set_session.php and updates the next button title.
     *
     * Modes, i.e. when a task is used:
     * - filled:   the field is not empty (text fields)
     * - select:   the selected value is the checked one, or any value when `any` (or a regex on its label)
     * - checked:  a checked input has the checked value, or any value when `any`
     * - switch:   the yes / no switch is on
     * - quantity: a quantity is above zero
     * - multiple: a selected option has the label of the checked value (or matches a regex)
     * - values:   the selected values contain the checked one
     *
     * @param array  $data    the field, with its options
     * @param array  $source  what the handler listens to: ['name' => ..., 'match' => 'exact'|'prefix'] or ['id' => ...]
     * @param string $mode    see above
     * @param array  $options
     *  - any:        checked values meaning "any value"
     *  - labels:     label of each checked value, for the multiple mode
     *  - defaults:   default values of the field, which flag their tasks as used at once
     *  - restore:    ['val' => ...] or ['check' => [...]], value kept in session
     *  - next_title: next button title when a task is used ('next' or 'savenext')
     *
     * @return void
     */
    public static function displayTaskTrigger(array $data, array $source, string $mode = 'filled', array $options = []): void
    {
        $check_values = $data['options'] ?? [];
        if (count($check_values) == 0) {
            return;
        }

        $any        = $options['any'] ?? [];
        $labels     = $options['labels'] ?? [];
        $defaults   = $options['defaults'] ?? [];
        $next_title = $options['next_title'] ?? 'next';

        $rules = [];
        foreach ($check_values as $idc => $check_value) {
            foreach ($check_value['plugin_metademands_tasks_id'] ?? [] as $tasks_id) {
                if (!$tasks_id) {
                    continue;
                }
                // Hide the child metademand until the field gets a matching value
                self::setUsedTask($tasks_id, 0);
                $rules[] = [
                    'tasks_id' => (int) $tasks_id,
                    'value'    => (string) $idc,
                    'any'      => in_array($idc, $any),
                    'regex'    => ($check_value['check_type_value'] ?? 0) == 2,
                    'label'    => (string) ($labels[$idc] ?? ''),
                ];
            }
        }
        if (count($rules) == 0) {
            return;
        }

        // Default values flag their tasks as used, the others as not used
        $initial_title = null;
        foreach ($rules as $rule) {
            foreach ($defaults as $default) {
                if ($rule['value'] == $default) {
                    if (self::setUsedTask($rule['tasks_id'], 1)) {
                        $initial_title = $next_title;
                    }
                } else {
                    self::setUsedTask($rule['tasks_id'], 0);
                }
            }
        }

        $config = [
            'source'     => $source,
            'mode'       => $mode,
            'rules'      => $rules,
            'url'        => PLUGIN_METADEMANDS_WEBDIR . '/ajax/set_session.php',
            'next_title' => $next_title,
        ];
        if (isset($options['restore'])) {
            $config['restore'] = $options['restore'];
        } elseif ($mode === 'filled' && isset($data['value']) && is_scalar($data['value'])) {
            $config['restore'] = ['val' => (string) $data['value']];
        }
        if ($initial_title !== null) {
            $config['initial_title'] = $initial_title;
        }

        TemplateRenderer::getInstance()->display('@metademands/wizard/task_trigger.html.twig', [
            'config' => $config,
        ]);
    }

    /**
     * Keys of the custom values checked by default.
     *
     * @param mixed $custom_values
     *
     * @return array
     */
    public static function getDefaultCustomValues($custom_values): array
    {
        $defaults = [];
        if (is_array($custom_values)) {
            foreach ($custom_values as $key => $custom_value) {
                if (($custom_value['is_default'] ?? 0) == 1) {
                    $defaults[] = $key;
                }
            }
        }

        return $defaults;
    }

    /**
     * Keys set to 1 in the serialized default values of a field.
     *
     * @param mixed $default
     *
     * @return array
     */
    public static function getDefaultValues($default): array
    {
        $values = FieldParameter::_unserialize($default ?? '');
        if (!is_array($values)) {
            return [];
        }

        return array_keys(array_filter($values, static fn($value) => $value == 1));
    }


    /**
     * @param       $metademands_id
     * @param array $id_found
     *
     * @return array
     * @throws \GlpitestSQLError
     */
    public static function getAncestorOfMetademandTask($metademands_id, $id_found = [])
    {
        global $DB;

        $metademandtask = new self();

        // Get next elements
        $criteria = [
            'SELECT' => [
                'glpi_plugin_metademands_tasks.plugin_metademands_metademands_id AS parent_metademands_id',
                'glpi_plugin_metademands_tasks.id AS tasks_id',
            ],
            'FROM' => 'glpi_plugin_metademands_tasks',
            'LEFT JOIN' => [
                'glpi_plugin_metademands_metademandtasks' => [
                    'ON' => [
                        'glpi_plugin_metademands_metademandtasks' => 'plugin_metademands_tasks_id',
                        'glpi_plugin_metademands_tasks' => 'id',
                    ],
                ],
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

    public function post_deleteFromDB()
    {
        $metademands_id = $this->fields['plugin_metademands_metademands_id'];

        // list of parents
        $metademands_parent = MetademandTask::getAncestorOfMetademandTask($metademands_id);

        $field = new Field();
        $fields = $field->find([
            'type' => 'parent_field',
            'plugin_metademands_metademands_id' => $metademands_id,
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
