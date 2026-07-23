<?php

/*
 -------------------------------------------------------------------------
 metademands plugin for GLPI
 Copyright (C) 2018-2026 by the metademands Development Team.

 https://github.com/InfotelGLPI/metademands
 -------------------------------------------------------------------------

 LICENSE

 This file is part of metademands.

 metademands is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 3 of the License, or
 (at your option) any later version.

 metademands is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with metademands. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 */

namespace GlpiPlugin\Metademands;

use Ajax;
use CommonDBChild;
use CommonDBTM;
use CommonGLPI;
use DBConnection;
use DbUtils;
use Glpi\Application\View\TemplateRenderer;
use Html;
use MassiveAction;
use Migration;
use Session;
use Toolbox;

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access directly to this file");
}

/**
 * Class Task
 */
class Task extends CommonDBChild
{
    public static $rightname = 'plugin_metademands';

    public static $itemtype = Metademand::class;
    public static $items_id = 'plugin_metademands_metademands_id';

    public const TICKET_TYPE     = 0;
    public const METADEMAND_TYPE = 1;
    public const TASK_TYPE       = 2;
    public const MAIL_TYPE = 3;

    public function canCreateItem(): bool
    {

        return true;

    }
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
        return __('Child creation', 'metademands');
    }


    public static function getIcon()
    {
        return "ti ti-pencil-plus";
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

    /**
     * @return bool|int
     */
    public static function canPurge(): bool
    {
        return Session::haveRight(self::$rightname, PURGE);
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
                        `name`                              varchar(255)                    DEFAULT NULL,
                        `completename`                      varchar(255)                    DEFAULT NULL,
                        `comment`                           text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
                        `entities_id`                       int {$default_key_sign} NOT NULL           DEFAULT '0',
                        `is_recursive`                      int                     NOT NULL           DEFAULT '0',
                        `level`                             int                     NOT NULL           DEFAULT '0',
                        `type`                              int                     NOT NULL           DEFAULT '0',
                        `ancestors_cache`                   text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
                        `sons_cache`                        text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
                        `plugin_metademands_tasks_id`       int {$default_key_sign} NOT NULL           DEFAULT '0',
                        `plugin_metademands_metademands_id` int {$default_key_sign} NOT NULL           DEFAULT '0',
                        `block_use`                         varchar(255)            NOT NULL           DEFAULT '[]',
                        `useBlock`                          tinyint                 NOT NULL           DEFAULT '1',
                        `formatastable`                     tinyint                 NOT NULL           DEFAULT '1',
                        `block_parent_ticket_resolution`    tinyint                 NOT NULL           DEFAULT '1',
                        PRIMARY KEY (`id`),
                        KEY `plugin_metademands_metademands_id` (`plugin_metademands_metademands_id`),
                        KEY `plugin_metademands_tasks_id` (`plugin_metademands_tasks_id`),
                        KEY `entities_id` (`entities_id`),
                        KEY `type` (`type`)
               ) ENGINE=InnoDB DEFAULT CHARSET={$default_charset} COLLATE={$default_collation} ROW_FORMAT=DYNAMIC;";

            $DB->doQuery($query);
        }

        $migration->dropForeignKeyContraint($table, 'glpi_plugin_metademands_tasks_ibfk_1');

        //version 2.7.5
        if (!$DB->fieldExists($table, "block_use")) {
            $migration->addField($table, "block_use", "varchar(255) NOT NULL DEFAULT '[]'");
            $migration->migrationOneTable($table);
        }
        //version 2.7.9
        if (!$DB->fieldExists($table, "hideTable")) {
            $migration->addField($table, "hideTable", "tinyint NOT NULL DEFAULT '0'");
            $migration->migrationOneTable($table);
        }
        if (!$DB->fieldExists($table, "useBlock")) {
            $migration->addField($table, "useBlock", "tinyint NOT NULL DEFAULT '1'");
            $migration->migrationOneTable($table);
        }

        //version 3.0.0
        if ($DB->fieldExists($table, "hideTable")) {
            $migration->changeField($table, 'hideTable', 'formatastable', "tinyint NOT NULL DEFAULT '1'");
            $migration->migrationOneTable($table);
            $query = $DB->buildUpdate(
                $table,
                [
                    'formatastable' => 1,
                ],
                [1],
            );
            $DB->doQuery($query);
        }

        //version 3.2.19
        $migration->changeField($table, 'formatastable', 'formatastable', "tinyint NOT NULL DEFAULT '1'");

        //version 3.3.0
        if (!$DB->fieldExists($table, "is_recursive")) {
            $migration->addField($table, "is_recursive", "int NOT NULL DEFAULT '0'");
            $migration->migrationOneTable($table);
        }

        //version 3.3.7
        if (!$DB->fieldExists($table, "block_parent_ticket_resolution")) {
            $migration->addField($table, "block_parent_ticket_resolution", "tinyint NOT NULL DEFAULT '1'");
            $migration->migrationOneTable($table);
        }
    }

    public static function uninstall()
    {
        global $DB;

        $DB->dropTable(self::getTable(), true);
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
        $dbu = new DbUtils();
        if ($item->getType() == Metademand::class) {
            if ($_SESSION['glpishow_count_on_tabs']) {
                return self::createTabEntry(
                    self::getTypeName(),
                    $dbu->countElementsInTable(
                        $this->getTable(),
                        ["plugin_metademands_metademands_id" => $item->getID()]
                    )
                );
            }
            return self::getTypeName();
        }
        return '';
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
     * @throws \GlpitestSQLError
     */
    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        $field = new self();

        if ($item->getType() == Metademand::class) {
            $field->showTaskslist($item);
        }
        return true;
    }

    /**
     * Print the field form
     *
     * @param $item
     *
     * @return bool (display)
     * @throws \GlpitestSQLError
     */
    //    public function showTaskslist($item)
    //    {
    //        global $CFG_GLPI;
    //
    //        if (!$this->canview()) {
    //            return false;
    //        }
    //        if (!$this->cancreate()) {
    //            return false;
    //        }
    //
    //        $canedit = $item->can($item->fields['id'], UPDATE);
    //        $solved  = true;
    //        if ($canedit) {
    //            // Check if metademand tasks has been already created
    //
    //        }
    //
    //
    //        $this->listTasks($item, $canedit, $solved);
    //    }


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
        if (!$this->canview()) {
            return false;
        }
        if (!$this->cancreate()) {
            return false;
        }

        //        $metademand = new Metademand();

        if (isset($options['parent']) && !empty($options['parent'])) {
            $item = $options['parent'];
        }

        if ($ID > 0) {

            $this->check($ID, READ);
            //            $metademand->getFromDB($this->fields['plugin_metademands_metademands_id']);
        } else {
            // Create item
            $options['itemtype'] = get_class($item);
            $options['items_id'] = $item->getID();
            //            $metademand->getFromDB($item->getID());
            // Create item
            $this->check(-1, CREATE, $options);
            $this->getEmpty();
        }

        $solved = false;
        if ($item->fields['maintenance_mode'] == 1) {
            $solved = true;
        } else {
            $solved = Ticket::isTicketSolved($item->getID());
        }

        $modal_rand = mt_rand();
        $form_html  = '';

        if ($solved) {
            ob_start();

            $this->showFormHeader($options);

            if ($ID > 0) {
                $valType = $this->fields['type'];
            } else {
                echo "<tr class='tab_bg_1'>";
                echo "<td class='center'>" . __('Task type', 'metademands') . "&nbsp;";

                $task_types = self::getTaskTypes($item->getID());

                // Only one metademand can be selected
                $metademand_tasks = $this->find([
                    'plugin_metademands_metademands_id' => $item->getID(),
                    'type' => self::METADEMAND_TYPE
                ]);
                if (count($metademand_tasks)) {
                    unset($task_types[self::METADEMAND_TYPE]);
                }

                $valType = 0;

                $rand = \Dropdown::showFromArray('taskType', $task_types, ['value' => $valType]);
                $params = [
                    'taskType' => '__VALUE__',
                    'plugin_metademands_metademands_id' => $item->getID()
                ];
                Ajax::updateItemOnSelectEvent(
                    "dropdown_taskType$rand",
                    "show_add_task_form",
                    PLUGIN_METADEMANDS_WEBDIR . "/ajax/showAddTaskForm.php",
                    $params
                );

                echo "</td>";
                echo "</tr>";
            }
            echo "<tr class='tab_bg_1'>";
            echo "<td class='center'>";
            echo "<span id='show_add_task_form'>";
            if ($ID > 0) {
                $type = $this->fields['type'];

                $tickettask = new TicketTask();
                $tickettask->getFromDBByCrit(["plugin_metademands_tasks_id" => $ID]);

                if ($type == self::TICKET_TYPE || $type == self::TASK_TYPE) {
                    $values = [
                        'tickettask_id' => $tickettask->getID(),
                        'itilcategories_id' => $tickettask->fields['itilcategories_id'],
                        'type' => $type,
                        'parent_tasks_id' => $this->fields['plugin_metademands_tasks_id'],
                        'plugin_metademands_tasks_id' => $ID,
                        'content' => $tickettask->fields['content'],
                        'name' => $this->fields['name'],
                        'block_use' => json_decode($this->fields['block_use'], true),
                        'useBlock' => $this->fields['useBlock'],
                        'block_parent_ticket_resolution' => $this->fields['block_parent_ticket_resolution'],
                        'formatastable' => $this->fields['formatastable'],
                        'entities_id' => $this->fields['entities_id'],
                        'is_recursive' => $this->fields['is_recursive'],
                        'users_id_requester' => $tickettask->fields['users_id_requester'],
                        'users_id_observer' => $tickettask->fields['users_id_observer'],
                        'users_id_assign' => $tickettask->fields['users_id_assign'],
                        'groups_id_requester' => $tickettask->fields['groups_id_requester'],
                        'groups_id_observer' => $tickettask->fields['groups_id_observer'],
                        'groups_id_assign' => $tickettask->fields['groups_id_assign'],
                        'status' => $tickettask->fields['status'],
                        'requesttypes_id' => $tickettask->fields['requesttypes_id'],
                    ];
                } elseif ($this->fields['type'] == self::MAIL_TYPE) {
                    $mailtask = new MailTask();
                    $mailtask->getFromDBByCrit(["plugin_metademands_tasks_id" => $ID]);
                    $values = [
                        'mailtask_id' => $mailtask->getID(),
                        'type' => $type,
                        'itilcategories_id' => $mailtask->fields['itilcategories_id'] ?? 0,
                        'plugin_metademands_tasks_id' => $ID,
                        'content' => $mailtask->fields['content'] ?? "",
                        'name' => $this->fields['name'],
                        'block_use' => json_decode($this->fields['block_use'], true),
                        'useBlock' => $this->fields['useBlock'],
                        'block_parent_ticket_resolution' => $this->fields['block_parent_ticket_resolution'],
                        'formatastable' => $this->fields['formatastable'],
                        'entities_id' => $this->fields['entities_id'],
                        'is_recursive' => $this->fields['is_recursive'],
                        'users_id_recipient' => $mailtask->fields['users_id_recipient'] ?? 0,
                        'groups_id_recipient' => $mailtask->fields['groups_id_recipient'] ?? 0,
                    ];
                    MailTask::showMailTaskForm($item->getID(), $type, $values);
                } else {
                    $values = [
                        'tickettask_id' => $tickettask->getID(),
                        'type' => $type,
                    ];
                }
                if ($type != Task::MAIL_TYPE) {
                    TicketTask::showTicketTaskForm($item->getID(), $solved, $type, $values);
                }
            } else {
                $type = "-1";
                if (Session::haveRight('ticket', CREATE)) {
                    $type = self::TICKET_TYPE;
                }
                if ($item->fields['force_create_tasks'] == 1) {
                    $type = self::TASK_TYPE;
                }
                if ($type > -1) {
                    TicketTask::showTicketTaskForm($item->getID(), $solved, $type);
                } else {
                    echo "<span style='color:red'>" . __("You don't have the ticket creation right", 'metademands') . "</span>";
                }
            }

            echo "</span>";
            echo "</td>";
            echo "</tr>";

            echo Html::hidden('plugin_metademands_metademands_id', ['value' => $item->getID()]);

            $this->showFormButtons(['colspan' => 3]);

            $form_html = ob_get_clean();
        }

        TemplateRenderer::getInstance()->display('@metademands/task_form.html.twig', [
            'modal_id'   => 'modal_task_' . $item->getID() . $modal_rand,
            'is_new'     => $ID <= 0,
            'form_html'  => $form_html,
            'not_solved' => !$solved,
        ]);

        return true;
    }


    public function sortByParentChild($data)
    {
        // Tableau pour stocker le résultat final
        $sortedData = [];

        // Tableau pour indexer les éléments par ID
        $indexedData = [];

        // Indexer les éléments par leur ID
        foreach ($data as $item) {
            $indexedData[$item['tasks_id']] = $item;
        }

        // Fonction récursive pour ajouter un parent et ses enfants au tableau trié
        function addParentAndChildren($item, &$sortedData, $indexedData)
        {
            $sortedData[] = $item;
            foreach ($indexedData as $child) {
                if ($child['parent_task'] == $item['tasks_id']) {
                    addParentAndChildren($child, $sortedData, $indexedData);
                }
            }
        }

        // Ajouter les éléments parents et leurs enfants au tableau trié
        foreach ($indexedData as $item) {
            if ($item['parent_task'] === 0) {
                addParentAndChildren($item, $sortedData, $indexedData);
            }
        }

        return $sortedData;
    }

    /**
     * @param $item
     *
     * @throws \GlpitestSQLError
     */
    private function showTaskslist($item)
    {
        global $CFG_GLPI;

        $tasks = $this->getTasks($item->getID());

        $solved = false;
        if ($item->fields['maintenance_mode'] == 1) {
            $solved = true;
        } else {
            $solved = Ticket::isTicketSolved($item->getID());
        }

        if (!$solved) {
            $metademands = new Metademand();
            $metademands->showDuplication($item->getID());
        }

        $rand = mt_rand();
        $canedit = $item->can($item->getID(), UPDATE);

        // Shared container that AJAX-loads the add/edit sub-item form
        $viewchild_id = "viewchild" . $item->getID() . $rand;
        $viewsubitem_url = $CFG_GLPI["root_doc"] . "/ajax/viewsubitem.php";

        // Build the JS function bodies once and hand them to the template. We keep
        // Ajax::updateItemJsCode (framework helper) rather than duplicating its logic.
        $scripts = [];
        if ($canedit && $solved) {
            $scripts[] = "function addchild" . $item->getID() . $rand . "() {"
                . Ajax::updateItemJsCode(
                    $viewchild_id,
                    $viewsubitem_url,
                    [
                        'type' => self::class,
                        'parenttype' => get_class($item),
                        $item->getForeignKeyField() => $item->getID(),
                        'id' => -1,
                        'solved' => $solved,
                    ],
                    "",
                    false
                )
                . "};";
        }

        $tags_modal = Ajax::createIframeModalWindow(
            'tags',
            PLUGIN_METADEMANDS_WEBDIR . "/front/tags.php?metademands_id=" . $item->getID(),
            [
                'title' => __('Show list of available tags'),
                'display' => false,
            ]
        );

        $entries = [];
        $tasks = $this->sortByParentChild($tasks);

        foreach ($tasks as $value) {
            $id = $value['tasks_id'];
            $is_basic = ($value['type'] == self::TICKET_TYPE
                || $value['type'] == self::TASK_TYPE
                || $value['type'] == self::MAIL_TYPE);

            $mailtask = null;
            if ($value['type'] == self::MAIL_TYPE) {
                $mailtask = new MailTask();
                $mailtask->getFromDBByCrit(['plugin_metademands_tasks_id' => $id]);
            }

            // Row highlight + optional link to a field option
            $row_class = "";
            $meta_class = "metademand_metademandtasks";
            $fieldopt = new FieldOption();
            if ($fieldopt->find(["plugin_metademands_tasks_id" => $id])) {
                if ($is_basic) {
                    $row_class = "linkedtooption";
                } else {
                    $row_class = "metatooption";
                    $meta_class = "";
                }
            }
            // Cells of a metademand row carry the meta css class, basic rows carry none
            $td_class = $is_basic ? "" : $meta_class;

            $row_style = "";
            if ($value['type'] == self::TICKET_TYPE && $value['level'] > 1) {
                $row_style = "background-color:#ebebeb";
            }

            // Clickable (edit) only for basic rows; metademand rows link to their form
            $edit_fn = "";
            if ($canedit && $is_basic) {
                $edit_fn = "viewEditchild" . $id . $rand;
                $scripts[] = "function " . $edit_fn . "() {"
                    . Ajax::updateItemJsCode(
                        $viewchild_id,
                        $viewsubitem_url,
                        [
                            'type' => self::class,
                            'parenttype' => get_class($item),
                            $item->getForeignKeyField() => $item->getID(),
                            'id' => $id,
                            'solved' => $solved,
                        ],
                        "",
                        false
                    )
                    . "};";
            }

            // Name
            if ($is_basic) {
                $name_html = $value['tickettasks_name'];
            } else {
                $name_html = "<a href='" . Toolbox::getItemTypeFormURL(Metademand::class)
                    . "?id=" . $value['link_metademands_id'] . "'>"
                    . \Dropdown::getDropdownName('glpi_plugin_metademands_metademands', $value['link_metademands_id'])
                    . "</a>";
            }

            // Entity: for a metademand sub-request, show the configured destination
            // entity of its ticket (destination_entities_id). The NULL sentinel means
            // "no override, use the requester's active entity at launch time".
            if ($value['type'] == self::METADEMAND_TYPE && !empty($value['link_metademands_id'])) {
                if (isset($value['destination_entities_id']) && $value['destination_entities_id'] !== null) {
                    $entity_name = \Dropdown::getDropdownName("glpi_entities", $value['destination_entities_id']);
                } else {
                    $entity_name = __('Active entity', 'metademands');
                }
            } else {
                $entity_name = \Dropdown::getDropdownName("glpi_entities", $value['entities_id']);
            }

            // Category
            $cat = "";
            if ($value['type'] == self::TICKET_TYPE) {
                if (isset($value['itilcategories_id']) && $value['itilcategories_id'] > 0) {
                    $cat = \Dropdown::getDropdownName("glpi_itilcategories", $value['itilcategories_id']);
                }
            } elseif ($value['type'] == self::MAIL_TYPE) {
                if (isset($mailtask->fields['itilcategories_id']) && $mailtask->fields['itilcategories_id'] > 0) {
                    $cat = \Dropdown::getDropdownName("glpi_itilcategories", $mailtask->fields['itilcategories_id']);
                }
            } elseif ($value['type'] == self::TASK_TYPE) {
                $cat = "---";
            }

            // Assigned to / recipients
            $techdata = "";
            if ($is_basic) {
                if ($value['type'] != self::MAIL_TYPE) {
                    if (isset($value['users_id_assign']) && $value['users_id_assign'] > 0) {
                        $techdata .= getUserName($value['users_id_assign'], 0, true);
                        $techdata .= "<br>";
                    }
                    if (isset($value['groups_id_assign']) && $value['groups_id_assign'] > 0) {
                        $techdata .= \Dropdown::getDropdownName("glpi_groups", $value['groups_id_assign']);
                    }
                }

                if ($value['type'] == self::MAIL_TYPE
                    && (isset($mailtask->fields['users_id_recipient']) || isset($mailtask->fields['groups_id_recipient']))
                    && ($mailtask->fields['users_id_recipient'] > 0 || $mailtask->fields['groups_id_recipient'] > 0)) {
                    $techdata .= __('Recipients', 'metademands') . " : <br>";
                    if (isset($mailtask->fields['users_id_recipient']) && $mailtask->fields['users_id_recipient'] > 0) {
                        $techdata .= getUserName($mailtask->fields['users_id_recipient'], 0, true);
                    }
                    if (isset($mailtask->fields['groups_id_recipient']) && $mailtask->fields['groups_id_recipient'] > 0) {
                        $techdata .= \Dropdown::getDropdownName("glpi_groups", $mailtask->fields['groups_id_recipient']);
                    }
                }
            }

            // Level
            if ($value['type'] == self::TICKET_TYPE) {
                $level_html = ($value['level'] > 1) ? $value['level'] : __('Root', 'metademands');
            } elseif ($value['type'] == self::TASK_TYPE) {
                $level_html = "---";
            } else {
                $level_html = __('Root', 'metademands');
            }

            // Blocks to use
            $blocks = json_decode($value['block_use']);
            if (!empty($blocks)) {
                $block_parts = [];
                foreach ($blocks as $block) {
                    $block_parts[] = sprintf(__("Block %s", 'metademands'), $block);
                }
                $block_html = implode(" <br>", $block_parts);
            } else {
                $block_html = __('All');
            }
            if ($value['type'] == self::TASK_TYPE) {
                $block_html = "---";
            }

            $entries[] = [
                'id' => $id,
                'parent' => $value['parent_task'],
                'level' => $value['level'],
                'row_class' => $row_class,
                'row_style' => $row_style,
                'td_class' => $td_class,
                'edit_fn' => $edit_fn,
                'name' => $name_html,
                'entity' => $entity_name,
                'type' => self::getTaskTypeName($value['type']),
                'category' => $cat,
                'assign' => $techdata,
                'level_label' => $level_html,
                'block' => $block_html,
                'useblock' => ($value['type'] == self::TASK_TYPE) ? "---" : \Dropdown::getYesNo($value['useBlock']),
                'formatastable' => ($value['type'] == self::TASK_TYPE) ? "---" : \Dropdown::getYesNo($value['formatastable']),
                'block_parent' => ($value['type'] == self::TASK_TYPE || $value['type'] == self::MAIL_TYPE)
                    ? "---"
                    : \Dropdown::getYesNo($value['block_parent_ticket_resolution']),
            ];
        }

        TemplateRenderer::getInstance()->display('@metademands/task_list.html.twig', [
            'item' => $item,
            'rand' => $rand,
            'canedit' => $canedit,
            'solved' => $solved,
            'itemtype' => self::class,
            'mass_container' => 'masstasks' . $rand,
            'viewchild_id' => $viewchild_id,
            'treetable_js' => PLUGIN_METADEMANDS_WEBDIR . "/lib/treetable/treetable.js",
            'scripts' => implode("\n", $scripts),
            'tags_modal' => $tags_modal,
            'entries' => $entries,
        ]);
    }

    /**
     * @param       $metademands_id
     * @param array $options
     *
     * @return array
     * @throws \GlpitestSQLError
     */
    public function getTasks($metademands_id, $options = [])
    {
        global $DB;

        $params['condition'] = [];
        foreach ($options as $key => $val) {
            $params[$key] = $val;
        }

        $tasks = [];

        $criteria = [
            'SELECT'    => [
                'glpi_plugin_metademands_tickettasks.id AS tickettasks_id',
                'glpi_plugin_metademands_tasks.name AS tickettasks_name',
                'glpi_plugin_metademands_metademandtasks.id AS metademandtask_id',
                'glpi_plugin_metademands_metademandtasks.plugin_metademands_metademands_id AS link_metademands_id',
                'glpi_plugin_metademands_metademandtasks.destination_entities_id AS destination_entities_id',
                'glpi_plugin_metademands_tasks.type',
                'glpi_plugin_metademands_tasks.plugin_metademands_tasks_id AS parent_task',
                'glpi_plugin_metademands_tasks.plugin_metademands_metademands_id AS parent_metademand',
                'glpi_plugin_metademands_tasks.id AS tasks_id',
                'glpi_plugin_metademands_tasks.completename AS tasks_completename',
                'glpi_plugin_metademands_tasks.level',
                'glpi_plugin_metademands_tasks.block_use',
                'glpi_plugin_metademands_tasks.formatastable',
                'glpi_plugin_metademands_tasks.useBlock',
                'glpi_plugin_metademands_tasks.block_parent_ticket_resolution',
                'glpi_plugin_metademands_tickettasks.itilcategories_id',
                'glpi_plugin_metademands_tickettasks.content',
                'glpi_plugin_metademands_tickettasks.status',
                'glpi_plugin_metademands_tickettasks.actiontime',
                'glpi_plugin_metademands_tickettasks.requesttypes_id',
                'glpi_plugin_metademands_tickettasks.groups_id_assign',
                'glpi_plugin_metademands_tickettasks.users_id_assign',
                'glpi_plugin_metademands_tickettasks.groups_id_observer',
                'glpi_plugin_metademands_tickettasks.users_id_observer',
                'glpi_plugin_metademands_tickettasks.groups_id_requester',
                'glpi_plugin_metademands_tickettasks.users_id_requester',
                'glpi_plugin_metademands_tasks.entities_id',
            ],
            'FROM'      => 'glpi_plugin_metademands_tasks',
            'LEFT JOIN'       => [
                'glpi_plugin_metademands_tickettasks' => [
                    'ON' => [
                        'glpi_plugin_metademands_tickettasks' => 'plugin_metademands_tasks_id',
                        'glpi_plugin_metademands_tasks'          => 'id',
                    ],
                ],
                'glpi_plugin_metademands_metademandtasks' => [
                    'ON' => [
                        'glpi_plugin_metademands_metademandtasks' => 'plugin_metademands_tasks_id',
                        'glpi_plugin_metademands_tasks'          => 'id',
                    ],
                ],
            ],
            'WHERE'       => ['glpi_plugin_metademands_tasks.plugin_metademands_metademands_id' => $metademands_id],
            'ORDERBY'       => ['glpi_plugin_metademands_tasks.id', 'glpi_plugin_metademands_tasks.completename'],
        ];

        if (count($params['condition']) > 0) {
            foreach ($params['condition'] as $cond => $value) {
                $criteria['WHERE'] = $criteria['WHERE'] + [$cond => $value];
            }
        }

        $iterator = $DB->request($criteria);
        if (count($iterator) > 0) {
            foreach ($iterator as $data) {
                $tasks[$data['tasks_id']] = $data;
            }
        }

        return $tasks;
    }


    /**
     * Get ticket types
     *
     * @return array of types
     **/
    public static function getTaskTypes($metademands_id = 0)
    {
        $metademands = new Metademand();

        if (Session::haveRight('ticket', CREATE)) {
            if ($metademands_id == 0) {
                $options[self::TICKET_TYPE] = __('Ticket');
            } elseif ($metademands->getFromDB($metademands_id)
                       && isset($metademands->fields['force_create_tasks'])
                       && $metademands->fields['force_create_tasks'] == 0) {
                $options[self::TICKET_TYPE] = __('Ticket');
                $options[self::MAIL_TYPE] = __('Mail');
            }
        }
        if ($metademands_id > 0
            && $metademands->getFromDB($metademands_id)
            && isset($metademands->fields['force_create_tasks'])
            && $metademands->fields['force_create_tasks'] == 1) {
            $options[self::TASK_TYPE] = __('Task');
            $options[self::MAIL_TYPE] = __('Mail');
        }
        if ($metademands->fields['object_to_create'] == 'Ticket' || $metademands_id == 0) {
            $options[self::METADEMAND_TYPE] = Metademand::getTypeName(1);
        }
        return $options;
    }

    /**
     * Get ticket type Name
     *
     * @param $value type ID
     **
     *
     * @return string
     * @return string
     */
    public static function getTaskTypeName($value)
    {
        switch ($value) {
            case self::TICKET_TYPE:
                return __('Ticket');
            case self::TASK_TYPE:
                return __('Task');
            case self::METADEMAND_TYPE:
                return Metademand::getTypeName(1);
            case self::MAIL_TYPE:
                return __('Mail');
        }
    }

    /**
     * Get a child for a level given
     *
     * @param int $tasks_id
     * @param int $search_level
     *
     * @return array child
     * @throws \GlpitestSQLError
     */
    public function getChildrenForLevel($tasks_id, $search_level)
    {
        $ChildrenForLevel = [];

        // If no child found get next task
        $task = new self();
        $task->getFromDB($tasks_id);
        $tasks_data = $task->getTasks($task->fields['plugin_metademands_metademands_id']);

        foreach ($tasks_data as $key => $values) {
            if ($values['level'] == $search_level
                && $values['parent_task'] == $tasks_id) {
                $ChildrenForLevel[] = $values['tasks_id'];
            }
        }
        if (count($ChildrenForLevel)) {
            return $ChildrenForLevel;
        }

        return false;
    }


    /**
     * @param $input
     *
     * @return array|bool
     */
    public function prepareInputForAdd($input)
    {
        if (isset($input['link_metademands_id']) && empty($input['link_metademands_id'])) {
            return [];
        }

        // cannot update a used metademand category
        if (isset($input['itilcategories_id']) && !empty($input['itilcategories_id'])) {
            $dbu  = new DbUtils();
            $meta = new Metademand();
            $meta->getFromDB($input["plugin_metademands_metademands_id"]);
            $metas = $dbu->getAllDataFromTable(
                'glpi_plugin_metademands_metademands',
                ["`itilcategories_id`" => $input["itilcategories_id"],
                    "`type`"              => $meta->getField("type")]
            );

            if (!empty($metas)) {
                $input = [];
                Session::addMessageAfterRedirect(__('The category is related to a demand. Thank you to select another', 'metademands'), false, ERROR);
                return false;
            }
        }

        $input = parent::prepareInputForAdd($input);

        return $input;
    }

    //   /**
    //    * @param $params
    //    * @param $protocol
    //    *
    //    * @return array
    //    */
    //   static function methodListTasktypes($params, $protocol) {
    //
    //      if (isset ($params['help'])) {
    //         return ['help' => 'bool,optional'];
    //      }
    //
    //      if (!Session::getLoginUserID()) {
    //         return PluginWebservicesMethodCommon::Error($protocol, WEBSERVICES_ERROR_NOTAUTHENTICATED);
    //      }
    //
    //      $tasks  = new self();
    //      $result = $tasks->getTaskTypes();
    //
    //      if (!Session::haveRight("ticket", CREATE)) {
    //         unset($result[0]);// unset ticket option
    //      }
    //
    //      $response = [];
    //      foreach ($result as $key => $taskType) {
    //         $response[] = ['id' => $key + 1, 'value' => $taskType];
    //      }
    //
    //      return $response;
    //   }

    /**
     * @param $metademands_id
     * @param $selected_value
     *
     * @throws \GlpitestSQLError
     * @throws \GlpitestSQLError
     */
    public static function showAllTasksDropdown($metademands_id, $selected_value, $display = true, $used_values = [])
    {
        $tasks      = new self();
        $tasks_data = $tasks->getTasks($metademands_id);
        $data       = [\Dropdown::EMPTY_VALUE];

        foreach ($tasks_data as $id => $value) {
            if ($value['type'] == Task::METADEMAND_TYPE) {
                $value['name'] = \Dropdown::getDropdownName('glpi_plugin_metademands_metademands', $value['link_metademands_id']);
                $data[$id]     = $value['name'];
            } elseif (!in_array($id, $used_values) || $id == $selected_value) {
                $value['name'] = $value['tickettasks_name'];
                $data[$id]     = $value['name'];
            }
        }

        return \Dropdown::showFromArray('plugin_metademands_tasks_id', $data, ['value'   => $selected_value,
            'tree'    => true,
            'display' => $display]);
    }

    /**
     * @return array
     */
    /**
     * @return array
     */
    public function rawSearchOptions()
    {
        $tab = [];

        $tab[] = [
            'id'   => 'common',
            'name' => self::getTypeName(1),
        ];

        $tab[] = [
            'id'            => '1',
            'table'         => $this->getTable(),
            'field'         => 'name',
            'name'          => __('Name'),
            'datatype'      => 'itemlink',
            'itemlink_type' => $this->getType(),
        ];

        $tab[] = [
            'id'       => '30',
            'table'    => $this->getTable(),
            'field'    => 'id',
            'name'     => __('ID'),
            'datatype' => 'number',
        ];

        return $tab;
    }

    /**
     * @return array
     */
    /**
     * @return array
     */
    public function getForbiddenStandardMassiveAction()
    {
        $forbidden = parent::getForbiddenStandardMassiveAction();

        if (!self::canCreate()) {
            $forbidden[] = 'delete';
            $forbidden[] = 'purge';
            $forbidden[] = 'restore';
        }
        $forbidden[] = 'add_transfer_list';
        $forbidden[] = 'move_under';
        $forbidden[] = 'update';
        $forbidden[] = 'merge';
        $forbidden[] = 'clone';
        $forbidden[] = 'amend_comment';

        return $forbidden;
    }

    public function getSpecificMassiveActions($checkitem = null)
    {
        $isadmin = static::canUpdate();
        $actions = parent::getSpecificMassiveActions($checkitem);

        if ($isadmin) {
            $actions['GlpiPlugin\Metademands\Task' . MassiveAction::CLASS_ACTION_SEPARATOR . 'updateBlock'] = _x('button', 'Update block to use', 'metademands');
        }

        return $actions;
    }

    /**
     * @param MassiveAction $ma
     *
     * @return bool|false
     */
    /**
     * @param MassiveAction $ma
     *
     * @return bool|false
     */
    public static function showMassiveActionsSubForm(MassiveAction $ma)
    {
        switch ($ma->getAction()) {
            case "updateBlock":
                $blocks = [];
                for ($i = 1; $i <= 20; $i++) {
                    if (!isset($blocks[$i])) {
                        $blocks[$i] = sprintf(__("Block %s", 'metademands'), $i);
                    }
                }
                ksort($blocks);

                \Dropdown::showFromArray(
                    'block_use',
                    $blocks,
                    [
                        'width'    => '100%',
                        'multiple' => true,
                    ]
                );
                echo Html::submit(_x('button', 'Post'), ['name'  => 'massiveaction',
                    'class' => 'btn btn-primary']);
                return true;
                break;
        }
        return parent::showMassiveActionsSubForm($ma);
    }

    /**
     * @param MassiveAction $ma
     * @param CommonDBTM    $item
     * @param array         $ids
     *
     * @return nothing|void
     * @since version 0.85
     *
     * @see CommonDBTM::processMassiveActionsForOneItemtype()
     *
     */
    public static function processMassiveActionsForOneItemtype(
        MassiveAction $ma,
        CommonDBTM $item,
        array         $ids
    ) {
        $task = new Task();
        $dbu  = new DbUtils();

        switch ($ma->getAction()) {
            case "updateBlock":
                $input = $ma->getInput();
                foreach ($ids as $key) {
                    $myvalue['block_use'] = json_encode($input['block_use']);
                    $myvalue['id']        = $key;
                    if ($task->update($myvalue)) {
                        $ma->itemDone($item->getType(), $key, MassiveAction::ACTION_OK);
                    } else {
                        $ma->itemDone($item->getType(), $key, MassiveAction::ACTION_KO);
                    }
                }

                break;
        }
    }

    public function cleanDBonPurge()
    {
        $field = new self();
        $field->deleteByCriteria(['plugin_metademands_tasks_id' => $this->fields['id']]);

        $temp = new FieldOption();
        $temp->deleteByCriteria(['plugin_metademands_tasks_id' => $this->fields['id']]);

        $temp = new Ticket_Task();
        $temp->deleteByCriteria(['plugin_metademands_tasks_id' => $this->fields['id']]);

        $temp = new TicketTask();
        $temp->deleteByCriteria(['plugin_metademands_tasks_id' => $this->fields['id']]);

        $temp = new MetademandTask();
        $temp->deleteByCriteria(['plugin_metademands_tasks_id' => $this->fields['id']]);
    }
}
