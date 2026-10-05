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
use CommonITILActor;
use DBConnection;
use DbUtils;
use Glpi\Application\View\TemplateRenderer;
use Migration;
use Session;
use Toolbox;

/**
 * Class TicketTask
 */
class TicketTask extends CommonDBChild
{
    public static string $rightname = 'plugin_metademands';

    public static string $itemtype = Task::class;
    public static string $items_id = 'plugin_metademands_tasks_id';

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

        $default_charset = DBConnection::getDefaultCharset();
        $default_collation = DBConnection::getDefaultCollation();
        $default_key_sign = DBConnection::getDefaultPrimaryKeySignOption();
        $table = self::getTable();

        if (!$DB->tableExists($table)) {
            $query = "CREATE TABLE `$table` (
                        `id` int {$default_key_sign} NOT NULL auto_increment,
                        `entities_id`                 int {$default_key_sign} NOT NULL           DEFAULT '0',
                        `content`                     text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
                        `itilcategories_id`           int {$default_key_sign}                    DEFAULT '0',
                        `type`                        int          NOT NULL           DEFAULT '0',
                        `status`                      varchar(255)                    DEFAULT NULL,
                        `actiontime`                  int          NOT NULL           DEFAULT '0',
                        `requesttypes_id`             int {$default_key_sign} NOT NULL           DEFAULT '0',
                        `groups_id_assign`            int {$default_key_sign} NOT NULL           DEFAULT '0',
                        `users_id_assign`             int {$default_key_sign} NOT NULL           DEFAULT '0',
                        `groups_id_requester`         int {$default_key_sign} NOT NULL           DEFAULT '0',
                        `users_id_requester`          int {$default_key_sign} NOT NULL           DEFAULT '0',
                        `groups_id_observer`          int {$default_key_sign} NOT NULL           DEFAULT '0',
                        `users_id_observer`           int {$default_key_sign} NOT NULL           DEFAULT '0',
                        `plugin_metademands_tasks_id` int {$default_key_sign} NOT NULL           DEFAULT '0',
                        PRIMARY KEY (`id`),
                        KEY `plugin_metademands_tasks_id` (`plugin_metademands_tasks_id`),
                        KEY `itilcategories_id` (`itilcategories_id`),
                        KEY `groups_id_assign` (`groups_id_assign`),
                        KEY `users_id_assign` (`users_id_assign`),
                        KEY `groups_id_requester` (`groups_id_requester`),
                        KEY `users_id_requester` (`users_id_requester`),
                        KEY `groups_id_observer` (`groups_id_observer`),
                        KEY `users_id_observer` (`users_id_observer`)
               ) ENGINE=InnoDB DEFAULT CHARSET={$default_charset} COLLATE={$default_collation} ROW_FORMAT=DYNAMIC;";

            $DB->doQuery($query);
        }

        $migration->dropField($table, 'plugin_metademands_itilapplications_id');
        $migration->dropField($table, 'plugin_metademands_itilenvironments_id');

        //version 3.3.4
        if (!$DB->fieldExists($table, "entities_id")) {
            $migration->addField($table, "entities_id", "int {$default_key_sign} NOT NULL DEFAULT '0'");
            $migration->migrationOneTable($table);
        }
    }

    public static function uninstall()
    {
        global $DB;

        $DB->dropTable(self::getTable(), true);
    }


    /**
     * @param       $metademands_id
     * @param       $canchangeorder
     * @param array $input
     *
     * @throws \GlpitestSQLError
     */
    public static function showTicketTaskForm($metademands_id, $canchangeorder, $tasktype, $input = [])
    {
        TemplateRenderer::getInstance()->display(
            '@metademands/tickettask_form_section.html.twig',
            self::getTicketTaskFormContext($metademands_id, $canchangeorder, $tasktype, $input),
        );
    }

    /**
     * Values rendered by tickettask_form_section.html.twig, also included by tickettask_form.html.twig.
     *
     * @return array<string, mixed>
     */
    private static function getTicketTaskFormContext($metademands_id, $canchangeorder, $tasktype, $input = []): array
    {
        $metademands = new Metademand();
        $metademands->getFromDB($metademands_id);

        $values = [
            'tickettask_id' => 0,
            'itilcategories_id' => 0,
            'parent_tasks_id' => 0,
            'plugin_metademands_tasks_id' => 0,
            'content' => ' ',
            'name' => ' ',
            'block_use' => 1,
            'useBlock' => 1,
            'block_parent_ticket_resolution' => 1,
            'formatastable' => 1,
            'entities_id' => 0,
            'is_recursive' => 0,
        ];
        foreach ($input as $key => $val) {
            $values[$key] = $val;
        }

        $ticket = new \Ticket();

        if (isset($_SESSION["metademandsHelpdeskSaved"])) {
            foreach ($_SESSION["metademandsHelpdeskSaved"] as $name => $value) {
                $values[$name] = $value;
            }
            unset($_SESSION["metademandsHelpdeskSaved"]);
        }

        if ($values['block_use'] != null && !is_array($values['block_use'])) {
            $values['block_use'] = json_decode($values['block_use'], true);
        }
        if ($values['block_use'] == null) {
            $values['block_use'] = [];
        }

        $values['name'] = stripslashes($values['name']);
        $values['type'] = $metademands->getField("type");

        $is_ticket_type = ($tasktype == Task::TICKET_TYPE);
        $tt = null;
        if ($is_ticket_type) {
            $tt = $ticket->getITILTemplateToUse(
                false,
                $values['type'],
                $values['itilcategories_id'],
                $values['entities_id'],
            );
        }

        // The template renders every widget through the core macros: only values are passed
        $blocks = [];
        $entity_reload = [];
        $category_options = [];
        $tickettemplates_id = 0;

        if ($is_ticket_type) {
            $field = new Field();
            $fields = $field->find(["plugin_metademands_metademands_id" => $metademands_id]);
            foreach ($fields as $f) {
                if (!isset($blocks[$f['rank']])) {
                    $blocks[intval($f['rank'])] = sprintf(__("Block %s", 'metademands'), $f["rank"]);
                }
            }
            ksort($blocks);
            if (!is_array($values['block_use'])) {
                $values['block_use'] = [$values['block_use']];
            }

            // Changing the entity reloads the category and the actor dropdowns scoped to it,
            // through public/scripts/metademands_reload.js.
            $entity_url    = PLUGIN_METADEMANDS_WEBDIR . '/ajax/showfieldsbyentity.php';
            foreach (
                [
                    'ticket_category' => [
                        'action' => 'showcategories',
                        'type' => $values['type'],
                        'itilcategories_id' => $values['itilcategories_id'] ?? 0,
                    ],
                    'ticket_users_id_requester' => [
                        'action' => 'users_id_requester',
                        'type' => $values['type'],
                        'right' => $ticket->getDefaultActorRightSearch(CommonITILActor::REQUESTER),
                        'users_id_requester' => $values['users_id_requester'] ?? 0,
                    ],
                    'ticket_users_id_observer' => [
                        'action' => 'users_id_observer',
                        'right' => $ticket->getDefaultActorRightSearch(CommonITILActor::OBSERVER),
                        'users_id_observer' => $values['users_id_observer'] ?? 0,
                    ],
                    'ticket_users_id_assign' => [
                        'action' => 'users_id_assign',
                        'type' => $values['type'],
                        'right' => $ticket->getDefaultActorRightSearch(CommonITILActor::ASSIGN),
                        'users_id_assign' => $values['users_id_assign'] ?? 0,
                    ],
                    'ticket_groups_id_requester' => [
                        'action' => 'groups_id_requester',
                        'condition' => ['is_requester' => 1],
                        'groups_id_requester' => $values['groups_id_requester'] ?? 0,
                    ],
                    'ticket_groups_id_observer' => [
                        'action' => 'groups_id_observer',
                        'condition' => ['is_watcher' => 1],
                        'groups_id_observer' => $values['groups_id_observer'] ?? 0,
                    ],
                    'ticket_groups_id_assign' => [
                        'action' => 'groups_id_assign',
                        'condition' => ['is_assign' => 1],
                        'groups_id_assign' => $values['groups_id_assign'] ?? 0,
                    ],
                ] as $target => $params
            ) {
                $entity_reload[] = [
                    'target' => $target,
                    'url'    => $entity_url,
                    'params' => ['entities_id' => '__VALUE__'] + $params,
                ];
            }

            $category_options = [
                'condition' => ($values['type'] == \Ticket::DEMAND_TYPE) ? ['is_request' => 1] : ['is_incident' => 1],
                'entity' => $metademands->fields["entities_id"],
            ];
            if ($values['itilcategories_id'] && $tt->isMandatoryField("itilcategories_id")) {
                $category_options['display_emptychoice'] = false;
            }

            if (isset($tt->fields['id'])) {
                $tickettemplates_id = $tt->fields['id'];
            }
        }

        $is_mandatory = static fn(string $name): bool => $tt !== null && $tt->isMandatoryField($name);
        $initial_requester = (bool) $metademands->fields["initial_requester_childs_tickets"];

        return [
            'is_ticket_type' => $is_ticket_type,
            'canchangeorder' => $canchangeorder,
            'entities_id' => $metademands->fields["entities_id"],
            // Block / Format / Entity / Category
            'use_block' => $values['useBlock'],
            'blocks' => $blocks,
            'block_use' => $values['block_use'],
            'format_as_table' => $values['formatastable'],
            'block_parent_ticket_resolution' => $values['block_parent_ticket_resolution'],
            'task_itemtype' => Task::class,
            'parent_tasks_id' => $values['parent_tasks_id'],
            'parent_tasks_condition' => [
                'type' => Task::TICKET_TYPE,
                'plugin_metademands_metademands_id' => $metademands->fields["id"],
                'id' => ['<>', $values['plugin_metademands_tasks_id']],
            ],
            'task_entities_id' => $values["entities_id"],
            'entity_reload' => $entity_reload,
            'itilcategories_id' => $values['itilcategories_id'],
            'category_options' => $category_options,
            'category_required' => $is_mandatory('itilcategories_id'),
            // Actors
            'show_requester_header' => $is_mandatory('_users_id_requester')
                || $is_mandatory('_groups_id_requester')
                || ($is_ticket_type && !$initial_requester),
            'show_observer_header' => $is_mandatory('_users_id_observer') || $is_mandatory('_groups_id_observer'),
            'show_requester_user' => $is_mandatory('_users_id_requester') || !$initial_requester,
            'show_requester_group' => $is_mandatory('_groups_id_requester') || !$initial_requester,
            'requester_user_required' => $is_mandatory('_users_id_requester'),
            'requester_group_required' => $is_mandatory('_groups_id_requester'),
            'observer_user_required' => $is_mandatory('_users_id_observer'),
            'observer_group_required' => $is_mandatory('_groups_id_observer'),
            'assign_user_required' => $is_mandatory('_users_id_assign'),
            'assign_group_required' => $is_mandatory('_groups_id_assign'),
            'requester_right' => $ticket->getDefaultActorRightSearch(CommonITILActor::REQUESTER),
            'observer_right' => $ticket->getDefaultActorRightSearch(CommonITILActor::OBSERVER),
            'assign_right' => $ticket->getDefaultActorRightSearch(CommonITILActor::ASSIGN),
            'users_id_requester' => $values['users_id_requester'] ?? 0,
            'users_id_observer' => $values['users_id_observer'] ?? 0,
            'users_id_assign' => $values['users_id_assign'] ?? 0,
            'groups_id_requester' => $values['groups_id_requester'] ?? 0,
            'groups_id_observer' => $values['groups_id_observer'] ?? 0,
            'groups_id_assign' => $values['groups_id_assign'] ?? 0,
            // Status / Request source, only shown when the ticket template makes them mandatory
            'show_status' => $is_mandatory('status'),
            'show_requesttype' => $is_mandatory('requesttypes_id'),
            'status' => $values['status'] ?? \Ticket::INCOMING,
            'requesttypes_id' => $values['requesttypes_id'] ?? 0,
            // Title / Description
            'title_required' => $is_mandatory('name'),
            'name' => $values['name'] ?? '',
            'content' => stripslashes($values['content'] ?? ''),
            'tickettask_id' => $values['tickettask_id'],
            'tickettemplates_id' => $tickettemplates_id,
        ];
    }


    /**
     * Print the field form
     *
     * @param $ID integer ID of the item
     * @param $options array
     *     - target filename : where to go when done.
     *     - withtemplate boolean : template or basic item
     *
     * @return bool (display)
     * @throws \GlpitestSQLError
     */
    public function showForm($ID, $options = [])
    {
        if (!$this->canview() || !$this->cancreate()) {
            return false;
        }

        if ($ID > 0) {
            $this->check($ID, READ);
        } else {
            // Create item
            $this->check(-1, UPDATE);
            $this->getEmpty();
        }

        // Get associated meatdemands values
        $metademands = new Metademand();
        $this->getMetademandForTicketTask($ID, $metademands);

        $canedit = $metademands->can($metademands->getID(), UPDATE);

        // Check if metademand tasks has been already created
        $solved = Ticket::isTicketSolved($metademands->fields['id']);
        if ($metademands->fields['maintenance_mode'] == 1) {
            $solved = true;
        }
        if (!$solved && $canedit) {
            $metademands->showDuplication($metademands->fields['id']);
        }

        // Get associated tasks values
        $tasks = new Task();
        $tasks->getFromDB($this->fields['plugin_metademands_tasks_id']);

        $input = array_merge($tasks->fields, $this->fields);
        $input['plugin_metademands_tasks_id'] = $tasks->fields['id'];
        $input['parent_tasks_id'] = $tasks->fields['plugin_metademands_tasks_id'];

        // Get Template
        $ticket = new \Ticket();
        $tt = $ticket->getITILTemplateToUse(false, $input['type'], $input['itilcategories_id'], $input['entities_id']);

        TemplateRenderer::getInstance()->display('@metademands/tickettask_form.html.twig', [
            'form_action' => Toolbox::getItemTypeFormURL(TicketTask::class),
            'field_id' => $ID > 0 ? $ID : 0,
            'is_new' => $ID <= 0,
            'tasks_id' => $this->fields['plugin_metademands_tasks_id'],
            'type' => $metademands->fields['type'],
            'entities_id' => $metademands->fields['entities_id'],
            'tickettemplates_id' => $tt->fields['id'] ?? 0,
            'section' => self::getTicketTaskFormContext($metademands->fields['id'], $solved, $tasks->fields['type'], $input),
            'canedit' => $canedit,
            'can_delete' => $solved && $ID > 0,
        ]);
        return true;
    }

    /**
     * @param        $input
     * @param bool $showMessage
     * @param bool $webserviceMode
     * @param string $customMessage
     *
     * @return array|bool
     */
    public function isMandatoryField($input, $showMessage = true, $webserviceMode = false, $customMessage = '')
    {
        if (!$webserviceMode) {
            $_SESSION["metademandsHelpdeskSaved"] = $input;
        }

        $meta = new Metademand();
        if (isset($input["plugin_metademands_metademands_id"])) {
            $meta->getFromDB($input["plugin_metademands_metademands_id"]);

            $type = $meta->getField("type");
            $categid = 0;
            if (isset($input['itilcategories_id'])) {
                $categid = $input['itilcategories_id'];
            }

            // Get Template
            $ticket = new \Ticket();
            $tt = $ticket->getITILTemplateToUse(false, $type, $categid, $input['entities_id']);

            $message = '';
            $mandatory_missing = [];

            if (count($tt->mandatory)) {
                $fieldsname = $tt->getAllowedFieldsNames(true);
                foreach ($tt->mandatory as $key => $val) {
                    if (isset($input[$key])
                        && (empty($input[$key]) || $input[$key] == 'NULL')
                        && (!in_array($key, TicketField::$used_fields))) {
                        $mandatory_missing[$key] = $fieldsname[$val];
                    }
                }

                if (count($mandatory_missing)) {
                    if (empty($customMessage)) {
                        $message = __('Mandatory field') . "&nbsp;" . implode(", ", $mandatory_missing);
                    } else {
                        $message = $customMessage . "&nbsp;:&nbsp;" . implode(", ", $mandatory_missing);
                    }
                    if ($showMessage) {
                        Session::addMessageAfterRedirect($message, false, ERROR);
                    }
                    if (!$webserviceMode) {
                        return false;
                    }
                }
            }

            unset($_SESSION["metademandsHelpdeskSaved"]);
        }
        if (!$webserviceMode) {
            return true;
        } else {
            return [
                'ticket_template' => $tt->fields['id'],
                'mandatory_fields' => $mandatory_missing,
                'message' => $message,
            ];
        }
    }

    /**
     * @param array $input
     *
     * @return array|bool
     */
    /**
     * @param array $input
     *
     * @return array|bool
     */
    public function prepareInputForUpdate($input)
    {
        $this->getFromDB($input['id']);

        // Cannot update a used metademand category
        if (isset($input['itilcategories_id'])) {
            $type = $input["type"];
            if (isset($input['type'])) {
                $type = $input["type"];
            }
            if (!empty($input["itilcategories_id"])) {
                $dbu = new DbUtils();
                $metas = $dbu->getAllDataFromTable('glpi_plugin_metademands_metademands', [
                    "`itilcategories_id`" => $input["itilcategories_id"],
                    "`type`" => $type,
                ]);

                if (!empty($metas)) {
                    $input = [];
                    Session::addMessageAfterRedirect(
                        __('The category is related to a demand. Thank you to select another', 'metademands'),
                        false,
                        ERROR,
                    );
                    return false;
                }
            }
        }

        return $input;
    }

    /**
     * @param   $tasks_id
     * @param  $metademands
     *
     * @throws \GlpitestSQLError
     */
    public function getMetademandForTicketTask($tasks_id, Metademand $metademands)
    {
        global $DB;

        if ($tasks_id > 0) {
            $criteria = [
                'SELECT' => [
                    'glpi_plugin_metademands_metademands.*',
                ],
                'FROM' => 'glpi_plugin_metademands_tickettasks',
                'LEFT JOIN' => [
                    'glpi_plugin_metademands_tasks' => [
                        'ON' => [
                            'glpi_plugin_metademands_tickettasks' => 'plugin_metademands_tasks_id',
                            'glpi_plugin_metademands_tasks' => 'id',
                        ],
                    ],
                    'glpi_plugin_metademands_metademands' => [
                        'ON' => [
                            'glpi_plugin_metademands_tasks' => 'plugin_metademands_metademands_id',
                            'glpi_plugin_metademands_metademands' => 'id',
                        ],
                    ],
                ],
                'WHERE' => [
                    'glpi_plugin_metademands_tickettasks.id' => $tasks_id,
                ],
            ];
            $iterator = $DB->request($criteria);
            if (count($iterator) > 0) {
                foreach ($iterator as $data) {
                    $metademands->fields = $data;
                }
            } else {
                $metademands->getEmpty();
            }
        } else {
            $metademands->getEmpty();
        }
    }
}
