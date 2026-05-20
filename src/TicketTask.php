<?php

/*
 * @version $Id: HEADER 15930 2011-10-30 15:47:55Z tsmr $
 -------------------------------------------------------------------------
 Metademands plugin for GLPI
 Copyright (C) 2018-2026 by the Metademands Development Team.

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

use Ajax;
use CommonDBChild;
use CommonITILActor;
use DBConnection;
use DbUtils;
use Entity;
use Glpi\Application\View\TemplateRenderer;
use Html;
use ITILCategory;
use Migration;
use Session;
use Toolbox;
use User;

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access directly to this file");
}


/**
 * Class TicketTask
 */
class TicketTask extends CommonDBChild
{
    public static $rightname = 'plugin_metademands';

    public static $itemtype = Task::class;
    public static $items_id = 'plugin_metademands_tasks_id';

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
        $metademands = new Metademand();
        $metademands->getFromDB($metademands_id);

        $values = [
            'tickettask_id'                  => 0,
            'itilcategories_id'              => 0,
            'parent_tasks_id'                => 0,
            'plugin_metademands_tasks_id'    => 0,
            'content'                        => ' ',
            'name'                           => ' ',
            'block_use'                      => 1,
            'useBlock'                       => 1,
            'block_parent_ticket_resolution' => 1,
            'formatastable'                  => 1,
            'entities_id'                    => 0,
            'is_recursive'                   => 0,
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
        $tt             = null;
        if ($is_ticket_type) {
            $tt = $ticket->getITILTemplateToUse(false, $values['type'], $values['itilcategories_id'], $values['entities_id']);
        }

        // --- Section 1 : Bloc / Format / Entite / Categorie (TICKET_TYPE uniquement) ---
        $use_block_html                      = '';
        $block_use_html                      = '';
        $format_as_table_html                = '';
        $block_parent_ticket_resolution_html = '';
        $parent_tasks_html                   = '';
        $entity_html                         = '';
        $category_html                       = '';
        $category_mark                       = '';
        $tickettemplates_id                  = 0;

        if ($is_ticket_type) {
            ob_start();
            \Dropdown::showYesNo('useBlock', $values['useBlock']);
            $use_block_html = ob_get_clean();

            $field  = new Field();
            $fields = $field->find(["plugin_metademands_metademands_id" => $metademands_id]);
            $blocks = [];
            foreach ($fields as $f) {
                if (!isset($blocks[$f['rank']])) {
                    $blocks[intval($f['rank'])] = sprintf(__("Block %s", 'metademands'), $f["rank"]);
                }
            }
            ksort($blocks);
            if (!is_array($values['block_use'])) {
                $values['block_use'] = [$values['block_use']];
            }
            ob_start();
            \Dropdown::showFromArray('block_use', $blocks, [
                'values'   => $values['block_use'],
                'width'    => '100%',
                'multiple' => true,
                'entity'   => $_SESSION['glpiactiveentities'],
            ]);
            $block_use_html = ob_get_clean();

            ob_start();
            \Dropdown::showYesNo('formatastable', $values['formatastable']);
            $format_as_table_html = ob_get_clean();

            ob_start();
            \Dropdown::showYesNo('block_parent_ticket_resolution', $values['block_parent_ticket_resolution']);
            $block_parent_ticket_resolution_html = ob_get_clean();

            if ($canchangeorder) {
                ob_start();
                \Dropdown::show(Task::class, [
                    'name'      => 'parent_tasks_id',
                    'value'     => $values['parent_tasks_id'],
                    'entity'    => $metademands->fields["entities_id"],
                    'condition' => [
                        'type'                              => Task::TICKET_TYPE,
                        'plugin_metademands_metademands_id' => $metademands->fields["id"],
                        'id'                                => ['<>', $values['plugin_metademands_tasks_id']],
                    ],
                ]);
                $parent_tasks_html = ob_get_clean();
            }

            ob_start();
            $rand = Entity::dropdown([
                'name'      => 'entities_id',
                'value'     => $values["entities_id"],
                'on_change' => 'entity_cat()',
            ]);
            echo "<script type='text/javascript'>";
            echo "function entity_cat(){";
            $params = ['action' => 'showcategories', 'entities_id' => '__VALUE__', 'type' => $values['type'], 'itilcategories_id' => $values['itilcategories_id'] ?? 0];
            Ajax::updateItemJsCode('ticket_category', PLUGIN_METADEMANDS_WEBDIR . '/ajax/showfieldsbyentity.php', $params, 'dropdown_entities_id' . $rand);
            echo ";\n";
            $params = ['action' => 'users_id_requester', 'entities_id' => '__VALUE__', 'type' => $values['type'], 'right' => $ticket->getDefaultActorRightSearch(CommonITILActor::REQUESTER), 'users_id_requester' => $values['users_id_requester'] ?? 0];
            Ajax::updateItemJsCode('ticket_users_id_requester', PLUGIN_METADEMANDS_WEBDIR . '/ajax/showfieldsbyentity.php', $params, 'dropdown_entities_id' . $rand);
            echo ";\n";
            $params = ['action' => 'users_id_observer', 'entities_id' => '__VALUE__', 'right' => $ticket->getDefaultActorRightSearch(CommonITILActor::OBSERVER), 'users_id_observer' => $values['users_id_observer'] ?? 0];
            Ajax::updateItemJsCode('ticket_users_id_observer', PLUGIN_METADEMANDS_WEBDIR . '/ajax/showfieldsbyentity.php', $params, 'dropdown_entities_id' . $rand);
            echo ";\n";
            $params = ['action' => 'users_id_assign', 'entities_id' => '__VALUE__', 'type' => $values['type'], 'right' => $ticket->getDefaultActorRightSearch(CommonITILActor::ASSIGN), 'users_id_assign' => $values['users_id_assign'] ?? 0];
            Ajax::updateItemJsCode('ticket_users_id_assign', PLUGIN_METADEMANDS_WEBDIR . '/ajax/showfieldsbyentity.php', $params, 'dropdown_entities_id' . $rand);
            echo ";\n";
            $params = ['action' => 'groups_id_requester', 'entities_id' => '__VALUE__', 'condition' => ['is_requester' => 1], 'groups_id_requester' => $values['groups_id_requester'] ?? 0];
            Ajax::updateItemJsCode('ticket_groups_id_requester', PLUGIN_METADEMANDS_WEBDIR . '/ajax/showfieldsbyentity.php', $params, 'dropdown_entities_id' . $rand);
            echo ";\n";
            $params = ['action' => 'groups_id_observer', 'entities_id' => '__VALUE__', 'condition' => ['is_watcher' => 1], 'groups_id_observer' => $values['groups_id_observer'] ?? 0];
            Ajax::updateItemJsCode('ticket_groups_id_observer', PLUGIN_METADEMANDS_WEBDIR . '/ajax/showfieldsbyentity.php', $params, 'dropdown_entities_id' . $rand);
            echo ";\n";
            $params = ['action' => 'groups_id_assign', 'entities_id' => '__VALUE__', 'condition' => ['is_assign' => 1], 'groups_id_assign' => $values['groups_id_assign'] ?? 0];
            Ajax::updateItemJsCode('ticket_groups_id_assign', PLUGIN_METADEMANDS_WEBDIR . '/ajax/showfieldsbyentity.php', $params, 'dropdown_entities_id' . $rand);
            echo ";\n";
            echo "}";
            echo "</script>";
            $entity_html = ob_get_clean();

            $category_mark = $tt->getMandatoryMark('itilcategories_id');
            $condition = ($values['type'] == \Ticket::DEMAND_TYPE) ? ['is_request' => 1] : ['is_incident' => 1];
            $opt = ['value' => $values['itilcategories_id'], 'condition' => $condition, 'entity' => $metademands->fields["entities_id"]];
            if ($values['itilcategories_id'] && $tt->isMandatoryField("itilcategories_id")) {
                $opt['display_emptychoice'] = false;
            }
            ob_start();
            ITILCategory::dropdown($opt);
            $category_html = ob_get_clean();

            if (isset($tt->fields['id'])) {
                $tickettemplates_id = $tt->fields['id'];
            }
        }

        // --- Section 2 : Acteurs ---
        $show_requester_header    = false;
        $show_observer_header     = false;
        $requester_user_mark      = '';
        $requester_group_mark     = '';
        $observer_user_mark       = '';
        $observer_group_mark      = '';
        $assign_user_mark         = '';
        $assign_group_mark        = '';
        $users_id_requester_html  = '';
        $users_id_observer_html   = '';
        $groups_id_requester_html = '';
        $groups_id_observer_html  = '';

        if ($is_ticket_type) {
            $show_requester_header = $tt->isMandatoryField('_users_id_requester') || $tt->isMandatoryField('_groups_id_requester');
            $show_observer_header  = $tt->isMandatoryField('_users_id_observer')  || $tt->isMandatoryField('_groups_id_observer');
            $assign_user_mark  = $tt->getMandatoryMark('_users_id_assign');
            $assign_group_mark = $tt->getMandatoryMark('_groups_id_assign');

            if ($tt->isMandatoryField('_users_id_requester')) {
                $requester_user_mark = $tt->getMandatoryMark('_users_id_requester');
                ob_start();
                User::dropdown(['name' => 'users_id_requester', 'value' => $values['users_id_requester'] ?? 0, 'entity' => $metademands->fields["entities_id"], 'right' => $ticket->getDefaultActorRightSearch(CommonITILActor::REQUESTER)]);
                $users_id_requester_html = ob_get_clean();
            }

            if ($tt->isMandatoryField('_users_id_observer')) {
                $observer_user_mark = $tt->getMandatoryMark('_users_id_observer');
                ob_start();
                User::dropdown(['name' => 'users_id_observer', 'value' => $values['users_id_observer'] ?? 0, 'entity' => $metademands->fields["entities_id"], 'right' => $ticket->getDefaultActorRightSearch(CommonITILActor::OBSERVER)]);
                $users_id_observer_html = ob_get_clean();
            }

            if ($tt->isMandatoryField('_groups_id_requester')) {
                $requester_group_mark = $tt->getMandatoryMark('_groups_id_requester');
                ob_start();
                \Dropdown::show('Group', ['name' => 'groups_id_requester', 'value' => $values['groups_id_requester'] ?? 0, 'entity' => $metademands->fields["entities_id"], 'condition' => ['is_requester' => 1]]);
                $groups_id_requester_html = ob_get_clean();
            }

            if ($tt->isMandatoryField('_groups_id_observer')) {
                $observer_group_mark = $tt->getMandatoryMark('_groups_id_observer');
                ob_start();
                \Dropdown::show('Group', ['name' => 'groups_id_observer', 'value' => $values['groups_id_observer'] ?? 0, 'entity' => $metademands->fields["entities_id"], 'condition' => ['is_watcher' => 1]]);
                $groups_id_observer_html = ob_get_clean();
            }
        }

        ob_start();
        User::dropdown(['name' => 'users_id_assign', 'value' => $values['users_id_assign'] ?? 0, 'entity' => $metademands->fields["entities_id"], 'right' => $ticket->getDefaultActorRightSearch(CommonITILActor::ASSIGN)]);
        $users_id_assign_html = ob_get_clean();

        ob_start();
        \Dropdown::show('Group', ['name' => 'groups_id_assign', 'value' => $values['groups_id_assign'] ?? 0, 'entity' => $metademands->fields["entities_id"], 'condition' => ['is_assign' => 1]]);
        $groups_id_assign_html = ob_get_clean();

        // --- Section 3 : Statut / Type de demande (TICKET_TYPE uniquement) ---
        $show_status      = false;
        $show_requesttype = false;
        $status_mark      = '';
        $requesttype_mark = '';
        $status_html      = '';
        $requesttype_html = '';

        if ($is_ticket_type) {
            $show_status      = $tt->isMandatoryField('status');
            $show_requesttype = $tt->isMandatoryField('requesttypes_id');

            if ($show_status) {
                $status_mark = $tt->getMandatoryMark('status');
                ob_start();
                \Ticket::dropdownStatus(['value' => $values['status'] ?? \Ticket::INCOMING]);
                $status_html = ob_get_clean();
            }

            if ($show_requesttype) {
                $requesttype_mark = $tt->getMandatoryMark('requesttypes_id');
                ob_start();
                \Dropdown::show('RequestType', ['value' => $values['requesttypes_id'] ?? '']);
                $requesttype_html = ob_get_clean();
            }
        }

        TemplateRenderer::getInstance()->display('@metademands/tickettask_form_section.html.twig', [
            'is_ticket_type'                       => $is_ticket_type,
            'canchangeorder'                       => $canchangeorder,
            'use_block_html'                       => $use_block_html,
            'block_use_html'                       => $block_use_html,
            'format_as_table_html'                 => $format_as_table_html,
            'block_parent_ticket_resolution_html'  => $block_parent_ticket_resolution_html,
            'parent_tasks_html'                    => $parent_tasks_html,
            'entity_html'                          => $entity_html,
            'category_mark'                        => $category_mark,
            'category_html'                        => $category_html,
            'show_requester_header'                => $show_requester_header,
            'show_observer_header'                 => $show_observer_header,
            'requester_user_mark'                  => $requester_user_mark,
            'requester_group_mark'                 => $requester_group_mark,
            'observer_user_mark'                   => $observer_user_mark,
            'observer_group_mark'                  => $observer_group_mark,
            'assign_user_mark'                     => $assign_user_mark,
            'assign_group_mark'                    => $assign_group_mark,
            'users_id_requester_html'              => $users_id_requester_html,
            'users_id_observer_html'               => $users_id_observer_html,
            'users_id_assign_html'                 => $users_id_assign_html,
            'groups_id_requester_html'             => $groups_id_requester_html,
            'groups_id_observer_html'              => $groups_id_observer_html,
            'groups_id_assign_html'                => $groups_id_assign_html,
            'show_status'                          => $show_status,
            'show_requesttype'                     => $show_requesttype,
            'status_mark'                          => $status_mark,
            'requesttype_mark'                     => $requesttype_mark,
            'status_html'                          => $status_html,
            'requesttype_html'                     => $requesttype_html,
            'title_mark'                           => $is_ticket_type ? $tt->getMandatoryMark('name') : '',
            'name'                                 => $values['name'] ?? '',
            'content'                              => stripslashes($values['content'] ?? ''),
            'tickettask_id'                        => $values['tickettask_id'],
            'tickettemplates_id'                   => $tickettemplates_id,
        ]);
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

        ob_start();
        self::showTicketTaskForm($metademands->fields['id'], $solved, $tasks->fields['type'], $input);
        $form_content_html = ob_get_clean();

        TemplateRenderer::getInstance()->display('@metademands/tickettask_form.html.twig', [
            'form_action'        => Toolbox::getItemTypeFormURL(TicketTask::class),
            'field_id'           => $ID > 0 ? $ID : 0,
            'is_new'             => $ID <= 0,
            'tasks_id'           => $this->fields['plugin_metademands_tasks_id'],
            'type'               => $metademands->fields['type'],
            'entities_id'        => $metademands->fields['entities_id'],
            'tickettemplates_id' => $tt->fields['id'] ?? 0,
            'form_content_html'  => $form_content_html,
            'canedit'            => $canedit,
            'can_delete'         => $solved && $ID > 0,
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
            return ['ticket_template' => $tt->fields['id'],
                'mandatory_fields' => $mandatory_missing,
                'message' => $message];
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
                $metas = $dbu->getAllDataFromTable('glpi_plugin_metademands_metademands', ["`itilcategories_id`" => $input["itilcategories_id"],
                    "`type`" => $type]);

                if (!empty($metas)) {
                    $input = [];
                    Session::addMessageAfterRedirect(__('The category is related to a demand. Thank you to select another', 'metademands'), false, ERROR);
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
