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

use Ajax;
use CommonDBChild;
use CommonITILActor;
use DBConnection;
use DbUtils;
use Entity;
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

        // Default values
        $values = [
            'tickettask_id' => 0,
            'itilcategories_id' => 0,
            'parent_tasks_id' => 0,
            'plugin_metademands_tasks_id' => 0,
            'content' => " ",
            'name' => " ",
            'block_use' => 1,
            'useBlock' => 1,
            'block_parent_ticket_resolution' => 1,
            'formatastable' => 1,
            'entities_id' => 0,
            'is_recursive' => 0];

        // Init values
        foreach ($input as $key => $val) {
            $values[$key] = $val;
        }


        //      $values['block_use'] = json_decode($values['block_use']);
        $ticket = new \Ticket();

        // Restore saved value or override with page parameter
        if (isset($_SESSION["metademandsHelpdeskSaved"])) {
            foreach ($_SESSION["metademandsHelpdeskSaved"] as $name => $value) {
                $values[$name] = $value;
            }
            unset($_SESSION["metademandsHelpdeskSaved"]);
        }

        if ($values['block_use'] != null
            && !is_array($values['block_use'])) {
            $values['block_use'] = json_decode($values['block_use'], true);
        }
        if ($values['block_use'] == null) {
            $values['block_use'] = [];
        }

        // Clean text fields
        $values['name'] = stripslashes($values['name']);
        $values['type'] = $metademands->getField("type");

        if ($tasktype == Task::TICKET_TYPE) {
            // Get Template
            $tt = $ticket->getITILTemplateToUse(false, $values['type'], $values['itilcategories_id'], $values['entities_id']);
        }
        // In percent
        $colsize1 = '13';
        $colsize3 = '87';

        echo "<div>";
        if ($tasktype == Task::TICKET_TYPE) {
            echo "<table class='tab_cadre_fixe' id='mainformtable'>";

            echo "<tr class='tab_bg_1'>";

            echo "<th>" . sprintf(__('%1$s'), __('Use block', 'metademands')) . "</th>";
            echo "<td>";
            \Dropdown::showYesNo('useBlock', $values['useBlock']);
            echo "</td>";

            echo "<th>" . sprintf(__('%1$s'), __('Block to use', 'metademands')) . "</th>";
            echo "<td>";
            $field = new Field();
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
            \Dropdown::showFromArray(
                'block_use',
                $blocks,
                ['values' => $values['block_use'],
                    'width' => '100%',
                    'multiple' => true,
                    'entity' => $_SESSION['glpiactiveentities']]
            );
            echo "</td>";
            echo "</tr>";

            echo "<tr class='tab_bg_1'>";

            echo "<th>" . sprintf(__('%1$s'), __('Format the description of the childs ticket as a table', 'metademands')) . "</th>";
            echo "<td>";
            \Dropdown::showYesNo('formatastable', $values['formatastable']);
            echo "</td>";

            echo "<th>" . sprintf(__('%1$s'), __('Block parent ticket resolution', 'metademands')) . "</th>";
            echo "<td>";
            \Dropdown::showYesNo('block_parent_ticket_resolution', $values['block_parent_ticket_resolution']);
            echo "</td>";

            echo "</tr>";

            if ($canchangeorder) {
                echo "<tr class='tab_bg_1'>";
                echo "<th>" . __('Create after the task', 'metademands') . "</th>";
                echo "<td >";
                \Dropdown::show(
                    Task::class,
                    ['name' => 'parent_tasks_id',
                        'value' => $values['parent_tasks_id'],
                        'entity' => $metademands->fields["entities_id"],
                        'condition' => ['type' => Task::TICKET_TYPE,
                            'plugin_metademands_metademands_id' => $metademands->fields["id"],
                            'id' => ['<>', $values['plugin_metademands_tasks_id']]]]
                );
                echo "</td>";
                echo "<td colspan='2'></td>";
                echo "</tr>";
            }

            echo "<tr class='tab_bg_1'>";

            echo "<th>" . __('Entity') . "</th>";
            echo "<td>";
            //            Entity::dropdown(['name' => 'entities_id', 'value' => $values["entities_id"]]);

            $rand = Entity::dropdown(['name' => 'entities_id', 'value' => $values["entities_id"], 'on_change' => 'entity_cat()']);
            echo "<script type='text/javascript'>";
            echo "function entity_cat(){";
            $params = ['action' => 'showcategories',
                'entities_id' => '__VALUE__',
                'type' => $values['type'],
                'itilcategories_id' => $values['itilcategories_id'] ?? 0];
            Ajax::updateItemJsCode('ticket_category', PLUGIN_METADEMANDS_WEBDIR . '/ajax/showfieldsbyentity.php', $params, 'dropdown_entities_id' . $rand);

            $params = ['action' => 'users_id_requester',
                'entities_id' => '__VALUE__',
                'type' => $values['type'],
                'right' => $ticket->getDefaultActorRightSearch(CommonITILActor::REQUESTER),
                'users_id_requester' => $values['users_id_requester'] ?? 0];
            Ajax::updateItemJsCode('ticket_users_id_requester', PLUGIN_METADEMANDS_WEBDIR . '/ajax/showfieldsbyentity.php', $params, 'dropdown_entities_id' . $rand);

            $params = ['action' => 'users_id_observer',
                'entities_id' => '__VALUE__',
                'right' => $ticket->getDefaultActorRightSearch(CommonITILActor::OBSERVER),
                'users_id_observer' => $values['users_id_observer'] ?? 0];
            Ajax::updateItemJsCode('ticket_users_id_observer', PLUGIN_METADEMANDS_WEBDIR . '/ajax/showfieldsbyentity.php', $params, 'dropdown_entities_id' . $rand);

            $params = ['action' => 'users_id_assign',
                'entities_id' => '__VALUE__',
                'right' => $ticket->getDefaultActorRightSearch(CommonITILActor::ASSIGN),
                'users_id_assign' => $values['users_id_assign'] ?? 0];
            Ajax::updateItemJsCode('ticket_users_id_assign', PLUGIN_METADEMANDS_WEBDIR . '/ajax/showfieldsbyentity.php', $params, 'dropdown_entities_id' . $rand);

            $params = ['action' => 'groups_id_requester',
                'entities_id' => '__VALUE__',
                'condition' => ['is_requester' => 1],
                'groups_id_requester' => $values['groups_id_requester'] ?? 0];
            Ajax::updateItemJsCode('ticket_groups_id_requester', PLUGIN_METADEMANDS_WEBDIR . '/ajax/showfieldsbyentity.php', $params, 'dropdown_entities_id' . $rand);

            $params = ['action' => 'groups_id_observer',
                'entities_id' => '__VALUE__',
                'condition' => ['is_watcher' => 1],
                'groups_id_observer' => $values['groups_id_observer'] ?? 0];
            Ajax::updateItemJsCode('ticket_groups_id_observer', PLUGIN_METADEMANDS_WEBDIR . '/ajax/showfieldsbyentity.php', $params, 'dropdown_entities_id' . $rand);

            $params = ['action' => 'groups_id_assign',
                'entities_id' => '__VALUE__',
                'condition' => ['is_assign' => 1],
                'groups_id_assign' => $values['groups_id_assign'] ?? 0];
            Ajax::updateItemJsCode('ticket_groups_id_assign', PLUGIN_METADEMANDS_WEBDIR . '/ajax/showfieldsbyentity.php', $params, 'dropdown_entities_id' . $rand);
            echo "}";
            echo "</script>";
            echo "</td>";
            echo "</tr>";

            echo "</td>";

            echo "<th>" . sprintf(
                __('%1$s%2$s'),
                __('Category'),
                $tt->getMandatoryMark('itilcategories_id')
            ) . "</th>";
            echo "<td>";

            echo "<span id='ticket_category'>";

            $condition = [];
            switch ($values['type']) {
                case \Ticket::DEMAND_TYPE:
                    $condition = ['is_request' => 1];
                    break;

                default: // Ticket::INCIDENT_TYPE :
                    $condition = ['is_incident' => 1];
            }
            $opt = ['value' => $values['itilcategories_id'],
                'condition' => $condition,
                'entity' => $metademands->fields["entities_id"]];

            if ($values['itilcategories_id'] && $tt->isMandatoryField("itilcategories_id")) {
                $opt['display_emptychoice'] = false;
            }

            ITILCategory::dropdown($opt);

            echo "</span>";
            echo "</td>";

            echo "</table>";
        }
        echo "<table class='tab_cadre_fixe'>";
        echo "<tr class='tab_bg_1'>";
        echo "<th rowspan='3' width='$colsize1%'>" . __('Actors', 'metademands') . "</th>";
        if ($tasktype == Task::TICKET_TYPE) {
            if ($tt->isMandatoryField('_users_id_requester') || $tt->isMandatoryField('_groups_id_requester')) {
                echo "<th>" . __('Requester') . "</th>";
            } else {
                echo "<th>";
                echo "</th>";
            }
            if ($tt->isMandatoryField('_users_id_observer') || $tt->isMandatoryField('_groups_id_observer')) {
                echo "<th>" . __('Observer') . "</th>";
            } else {
                echo "<th>";
                echo "</th>";
            }
        }
        echo "<th>" . __('Assigned to') . "</th>";
        echo "</tr>";

        echo "<tr class='tab_bg_1'>";
        if ($tasktype == Task::TICKET_TYPE) {
            if ($tt->isMandatoryField('_users_id_requester')) {
                echo "<td>";
                // Requester user
                //         echo CommonITILObject::getActorIcon('user', CommonITILActor::REQUESTER) . '&nbsp;';
                echo "<span id='ticket_users_id_requester'>";
                echo $tt->getMandatoryMark('_users_id_requester');
                User::dropdown(['name' => 'users_id_requester',
                    'value' => $values['users_id_requester'] ?? 0,
                    'entity' => $metademands->fields["entities_id"],
                    'right' => $ticket->getDefaultActorRightSearch(CommonITILActor::REQUESTER)]);
                echo "</span>";
                echo "</td>";
            } else {
                echo "<td>";
                echo "</td>";
            }
            if ($tt->isMandatoryField('_users_id_observer')) {
                echo "<td>";
                // Observer user
                //         echo CommonITILObject::getActorIcon('user', CommonITILActor::OBSERVER) . '&nbsp;';
                echo "<span id='ticket_users_id_observer'>";
                echo $tt->getMandatoryMark('_users_id_observer');
                User::dropdown(['name' => 'users_id_observer',
                    'value' => $values['users_id_observer'] ?? 0,
                    'entity' => $metademands->fields["entities_id"],
                    'right' => $ticket->getDefaultActorRightSearch(CommonITILActor::OBSERVER)]);
                echo "</span>";
                echo "</td>";
            } else {
                echo "<td>";
                echo "</td>";
            }
        }
        echo "<td>";
        // Assign user
        //      echo CommonITILObject::getActorIcon('user', CommonITILActor::ASSIGN) . '&nbsp;';
        if ($tasktype == Task::TICKET_TYPE) {
            echo $tt->getMandatoryMark('_users_id_assign');
        }
        echo "<span id='ticket_users_id_assign'>";
        User::dropdown(['name' => 'users_id_assign',
            'value' => $values['users_id_assign'] ?? 0,
            'entity' => $metademands->fields["entities_id"],
            'right' => $ticket->getDefaultActorRightSearch(CommonITILActor::ASSIGN)]);
        echo "</span>";
        echo "</td>";
        echo "</tr>";

        echo "<tr class='tab_bg_1'>";
        if ($tasktype == Task::TICKET_TYPE) {
            if ($tt->isMandatoryField('_groups_id_requester')) {
                echo "<td>";
                // Requester Group
                //         echo CommonITILObject::getActorIcon('group', CommonITILActor::REQUESTER) . '&nbsp;';
                echo "<span id='ticket_groups_id_requester'>";
                echo $tt->getMandatoryMark('_groups_id_requester');
                \Dropdown::show('Group', ['name' => 'groups_id_requester',
                    'value' => $values['groups_id_requester'] ?? 0,
                    'entity' => $metademands->fields["entities_id"],
                    'condition' => ['is_requester' => 1]]);
                echo "</span>";
                echo "</td>";
            } else {
                echo "<td>";
                echo "</td>";
            }

            if ($tt->isMandatoryField('_groups_id_observer')) {
                echo "<td>";
                // Observer Group
                //         echo CommonITILObject::getActorIcon('group', CommonITILActor::OBSERVER) . '&nbsp;';
                echo "<span id='ticket_groups_id_observer'>";
                echo $tt->getMandatoryMark('_groups_id_observer');
                \Dropdown::show('Group', ['name' => 'groups_id_observer',
                    'value' => $values['groups_id_observer'] ?? 0,
                    'entity' => $metademands->fields["entities_id"],
                    'condition' => ['is_watcher' => 1]]);
                echo "</span>";
                echo "</td>";

            } else {
                echo "<td>";
                echo "</td>";
            }
        }
        echo "<td>";
        // Assign Group
        //      echo CommonITILObject::getActorIcon('group', CommonITILActor::ASSIGN) . '&nbsp;';
        echo "<span id='ticket_groups_id_assign'>";
        if ($tasktype == Task::TICKET_TYPE) {
            echo $tt->getMandatoryMark('_groups_id_assign');
        }
        \Dropdown::show('Group', ['name' => 'groups_id_assign',
            'value' => $values['groups_id_assign'] ?? 0,
            'entity' => $metademands->fields["entities_id"],
            'condition' => ['is_assign' => 1]]);
        echo "</span>";
        echo "</td>";
        echo "</tr>";
        echo "</table>";
        if ($tasktype == Task::TICKET_TYPE) {
            echo "<table class='tab_cadre_fixe'>";
            // Status
            if ($tt->isMandatoryField('status') || $tt->isMandatoryField('requesttypes_id')) {
                echo "<tr class='tab_bg_1'>";
                if ($tt->isMandatoryField('status')) {
                    echo "<th width='$colsize1%'>" . __('Status') . '&nbsp;:' . $tt->getMandatoryMark('status') . "</th>";
                    echo "<td>";

                    \Ticket::dropdownStatus(['value' => $values['status'] ?? \Ticket::INCOMING]);
                    echo "</td>";
                } else {
                    echo "<td colspan = '2'>";
                    echo "</td>";
                }
                echo "</tr>";

                // Request type
                if ($tt->isMandatoryField('requesttypes_id')) {
                    echo "<th width='$colsize1%'>" . __('Request source') . '&nbsp;:' . $tt->getMandatoryMark('requesttypes_id') . "</th>";
                    echo "<td>";
                    \Dropdown::show('RequestType', ['value' => $values['requesttypes_id'] ?? '']);
                    echo "</td>";
                } else {
                    echo "<td colspan = '2'>";
                    echo "</td>";
                }
                echo "</tr>";
            }

            //            if ($tt->isMandatoryField('actiontime') || $tt->isMandatoryField('itemtype')) {
            //                // Actiontime
            //                echo "<tr class='tab_bg_1'>";
            //                if ($tt->isMandatoryField('actiontime')) {
            //                    echo "<th width='$colsize1%'>" . __('Total duration') . '&nbsp;:' . $tt->getMandatoryMark('actiontime') . "</th>";
            //                    echo "<td>";
            //                    Dropdown::showTimeStamp('actiontime', ['addfirstminutes' => true,
            //                                                           'value'           => isset($values['actiontime']) ? $values['actiontime'] : '']);
            //                    echo "</td>";
            //                } else {
            //                    echo "<td colspan = '2'>";
            //                    echo "</td>";
            //                }
            //
            //                // Itemtype
            //                if ($tt->isMandatoryField('itemtype')) {
            //                    echo "<th width='$colsize1%'>" . __('Associated element') . '&nbsp;:' . $tt->getMandatoryMark('itemtype') . "</th>";
            //                    echo "<td>";
            //                    $dev_user_id  = 0;
            //                    $dev_itemtype = 0;
            //                    $dev_items_id = isset($values['itemtype']) ? $values['itemtype'] : '';
            //                    Ticket::dropdownAllDevices(
            //                        'itemtype',
            //                        $dev_itemtype,
            //                        $dev_items_id,
            //                        1,
            //                        $dev_user_id,
            //                        $metademands->fields["entities_id"]
            //                    );
            //                    echo "</td>";
            //                } else {
            //                    echo "<td colspan = '2'>";
            //                    echo "</td>";
            //                }
            //                echo "</tr>";
            //            }
            echo "</table>";
        }
        echo "<table class='tab_cadre_fixe'>";
        // Title
        echo "<tr class='tab_bg_1'>";
        if ($tasktype == Task::TICKET_TYPE) {
            echo "<th width='$colsize1%'>" . __('Title') . '&nbsp;' . $tt->getMandatoryMark('name') . "</th>";
        } else {
            echo "<th width='$colsize1%'>" . __('Title') . "</th>";
        }

        echo "<td width='$colsize3%'>";
        $name = $values['name'] ?? '';
        echo Html::input('name', ['value' => $name, 'size' => 90]);
        echo "</td>";
        echo "</tr>";

        // Description
        echo "<tr class='tab_bg_1'>";
        echo "<th width='$colsize1%'>" . __('Description') . "</th>";
        echo "<td width='$colsize3%'>";

        $rand = mt_rand();
        $rand_text = mt_rand();
        Html::initEditorSystem("content" . $rand, $rand, true);
        if (!isset($values['content'])) {
            $content = '';
        } else {
            $content = $values['content'];
        }
        echo "<div id='content$rand_text'>";
        Html::textarea(['name' => 'content',
            'value' => stripslashes($content),
            'id' => 'content' . $rand,
            'rows' => 3,
            'enable_richtext' => true,
            'enable_fileupload' => false,
            'enable_images' => false]);
        echo "</div>";

        if ($tasktype == Task::TICKET_TYPE
            && isset($tt->fields['id'])) {
            echo Html::hidden('_tickettemplates_id', ['value' => $tt->fields['id']]);
        }


        echo Html::hidden('tickettask_id', ['value' => $values['tickettask_id']]);

        echo "</td>";
        echo "</tr>";
        echo "</table>";

        echo "</div>";
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

        echo "<form name='form_ticket' method='post' action='" . Toolbox::getItemTypeFormURL(__CLASS__) . "?_in_modal=1&id=$ID' enctype=\"multipart/form-data\">";
        self::showTicketTaskForm($metademands->fields['id'], $solved, $tasks->fields['type'], $input);

        echo Html::hidden('plugin_metademands_tasks_id', ['value' => $this->fields['plugin_metademands_tasks_id']]);
        if (isset($tt->fields['id'])) {
            echo Html::hidden('_tickettemplates_id', ['value' => $tt->fields['id']]);
        }
        echo Html::hidden('type', ['value' => $metademands->fields['type']]);
        echo Html::hidden('entities_id', ['value' => $metademands->fields['entities_id']]);

        echo "<div><table class='tab_cadre_fixe'>";

        $options['canedit'] = $canedit;
        $options['candel'] = $solved;
        $this->showFormButtons($options);

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
