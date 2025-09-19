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
use CommonDBTM;
use CommonITILActor;
use Html;
use Plugin;
use Session;
use Ticket_User;
use Toolbox;
use PluginFieldsContainer;
use PluginFieldsField;
use User;

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access directly to this file");
}

/**
 * Class MetademandValidation
 */
class MetademandValidation extends CommonDBTM
{
    public static $rightname = 'plugin_metademands_validatemeta';

    const VALIDATE_WITHOUT_TASK = 3; // meta validate without task
    const TASK_CREATION = 2; // task_created
    const TICKET_CREATION = 1; // tickets_created
    const TO_VALIDATE = 0; // waiting
    const TO_VALIDATE_WITHOUTTASK = -1; // waiting without ticket

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
        return __('Metademand validation', 'metademands');
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
        return Session::haveRight(self::$rightname, READ);
    }


    /**
     * @param       $ID
     * @param array $options
     *
     * @return bool
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
        Html::requireJs('tinymce');

        $metademand = new Metademand();

        if ($ID > 0) {
            $this->check($ID, READ);
            $metademand->getFromDB($this->fields['plugin_metademands_metademands_id']);
        } else {
            // Create item
            $item = $options['item'];
            $canedit = $metademand->can($item->fields['id'], UPDATE);
            $this->getEmpty();
            $this->fields["plugin_metademands_metademands_id"] = $item->fields['id'];
            $this->fields['color'] = '#000';
        }


        if ($ID > 0) {
            $this->showFormHeader(['colspan' => 2]);
        } else {
            echo "<div class='center first-bloc'>";
            echo "<form name='field_form' method='post' action='" . Toolbox::getItemTypeFormURL(__CLASS__) . "'>";
            echo "<table class='tab_cadre_fixe'>";
            echo "<tr class='tab_bg_1'>";
            echo "<th colspan='6'>" . __('Add a field', 'metademands') . "</th>";
            echo "</tr>";
        }


        if ($ID > 0) {
            $this->showFormButtons(['colspan' => 2]);
        } else {
            if ($canedit) {
                echo "<tr class='tab_bg_1'>";
                echo "<td class='tab_bg_2 center' colspan='6'>";
                echo Html::hidden('plugin_metademands_metademands_id', ['value' => $item->fields['id']]);
                echo Html::submit(_sx('button', 'Add'), ['name' => 'add', 'class' => 'btn btn-primary']);
                echo "</td>";
                echo "</tr>";
            }

            echo "</table>";
            Html::closeForm();
            echo "</div>";
        }
        return true;
    }

    public function validateMeta($params)
    {
        $ticket_id = $params["tickets_id"];
        $inputVal = [];

        $this->getFromDBByCrit(['tickets_id' => $ticket_id]);
        $meta_tasks = json_decode($this->fields["tickets_to_create"], true);
        if (is_array($meta_tasks)) {
            foreach ($meta_tasks as $key => $val) {
                $task = new TicketTask();
                if (isset($val['tickettasks_id'])
                    && $task->getFromDB($val['tickettasks_id'])) {
                    $meta_tasks[$key]['tickettasks_name'] = urldecode($val['tickettasks_name']);
                    if ($val['tasks_completename'] != null) {
                        $meta_tasks[$key]['tasks_completename'] = urldecode($val['tasks_completename']);
                    }
                    if (!empty($val['content'])) {
                        $meta_tasks[$key]['content'] = urldecode($val['content']);
                    }
                } else {
                    unset($meta_tasks[$key]);
                }
            }
        }

        $ticket = new \Ticket();
        $ticket->getFromDB($ticket_id);
        //      $ticket->fields["_users_id_requester"] = Session::getLoginUserID();
        $users = $ticket->getUsers(CommonITILActor::REQUESTER);
        foreach ($users as $user) {
            $ticket->fields["_users_id_requester"] = $user['users_id'];
        }
        $meta = new Metademand();
        $meta->getFromDB($this->getField("plugin_metademands_metademands_id"));

        $values_form = [];
        $ticket_field = new Ticket_Field();
        $fields = $ticket_field->find(['tickets_id' => $ticket_id]);
        foreach ($fields as $f) {
            $values_form[$f['plugin_metademands_fields_id']] = json_decode($f['value']);
            if ($values_form[$f['plugin_metademands_fields_id']] === null) {
                $values_form[$f['plugin_metademands_fields_id']] = $f['value'];
            }
            $f['plugin_metademands_fields_id'];
        }
        $inputField = [];
        $inputFieldMain = [];
        if (Plugin::isPluginActive('fields')) {
            $pluginfield = new Pluginfields();
            $pluginfields = $pluginfield->find(['plugin_metademands_metademands_id' => $meta->getID()]);

            foreach ($pluginfields as $plfield) {
                $fields_field = new PluginFieldsField();
                $fields_container = new PluginFieldsContainer();
                if ($fields_field->getFromDB($plfield['plugin_fields_fields_id'])) {
                    if ($fields_container->getFromDB($fields_field->fields['plugin_fields_containers_id'])) {
                        if ($fields_container->fields['type'] == 'tab') {
                            if (isset($values_form[$plfield['plugin_metademands_fields_id']])) {
                                if ($fields_field->fields['type'] == 'dropdown') {
                                    if (!isset($inputField[$fields_field->fields['plugin_fields_containers_id']]["plugin_fields_" . $fields_field->fields['name'] . "dropdowns_id"])
                                        || empty($inputField[$fields_field->fields['plugin_fields_containers_id']]["plugin_fields_" . $fields_field->fields['name'] . "dropdowns_id"])) {
                                        $val_f = 0;
                                        if ($values_form[$plfield['plugin_metademands_fields_id']] == "") {
                                            $values_form[$plfield['plugin_metademands_fields_id']] = 0;
                                        }
                                        $className = 'PluginFields' . ucfirst($fields_field->fields['name']) . 'Dropdown';
                                        if (getItemForItemtype($className)) {
                                            $classf = new $className();
                                            $valuesf = $classf->find();

                                            $field_custom = new FieldCustomvalue();
                                            if ($customs = $field_custom->find(
                                                ["plugin_metademands_fields_id" => $plfield['plugin_metademands_fields_id']]
                                            )) {
                                                if (count($customs) > 0) {
                                                    foreach ($customs as $custom) {
                                                        foreach ($valuesf as $valuef) {
                                                            if ($custom['name'] == $valuef['name']) {
                                                                $val_f = $valuef['id'];
                                                            }
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                        $inputField[$fields_field->fields['plugin_fields_containers_id']]["plugin_fields_" . $fields_field->fields['name'] . "dropdowns_id"] = $val_f;
                                    }
                                } elseif ($fields_field->fields['type'] == 'yesno') {
                                    $val = $values_form[$plfield['plugin_metademands_fields_id']];
//                                    if (is_int($val)) {
//                                        $val = $val - 1;
//                                    } else {
//                                        if (!isset($inputField[$fields_field->fields['plugin_fields_containers_id']][$fields_field->fields['name']])
//                                            || empty($inputField[$fields_field->fields['plugin_fields_containers_id']][$fields_field->fields['name']])) {
//                                            $val = 0;
//                                        } else {
//                                            $val = $inputField[$fields_field->fields['plugin_fields_containers_id']][$fields_field->fields['name']];
//                                        }
//                                    }
                                    $inputField[$fields_field->fields['plugin_fields_containers_id']][$fields_field->fields['name']] = $val;
                                } else {
                                    if (!isset($inputField[$fields_field->fields['plugin_fields_containers_id']][$fields_field->fields['name']]) ||
                                        empty($inputField[$fields_field->fields['plugin_fields_containers_id']][$fields_field->fields['name']])) {
                                        $inputField[$fields_field->fields['plugin_fields_containers_id']][$fields_field->fields['name']] = $values_form[$plfield['plugin_metademands_fields_id']];
                                    }
                                }
                            }
                        }

                        if ($fields_container->fields['type'] == 'dom') {
                            if (isset($values_form[$plfield['plugin_metademands_fields_id']])) {
                                if ($fields_field->fields['type'] == 'dropdown') {
                                    $val_f = 0;
                                    if ($values_form[$plfield['plugin_metademands_fields_id']] == "") {
                                        $values_form[$plfield['plugin_metademands_fields_id']] = 0;
                                    }
                                    $className = 'PluginFields' . ucfirst($fields_field->fields['name']) . 'Dropdown';
                                    if (getItemForItemtype($className)) {
                                        $classf = new $className();
                                        $valuesf = $classf->find();

                                        $field_custom = new FieldCustomvalue();
                                        if ($customs = $field_custom->find(
                                            ["plugin_metademands_fields_id" => $plfield['plugin_metademands_fields_id']]
                                        )) {
                                            if (count($customs) > 0) {
                                                foreach ($customs as $custom) {
                                                    foreach ($valuesf as $valuef) {
                                                        if ($custom['name'] == $valuef['name']) {
                                                            $val_f = $valuef['id'];
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                    }
                                    $inputFieldMain["plugin_fields_" . $fields_field->fields['name'] . "dropdowns_id"] = $val_f;
                                } elseif ($fields_field->fields['type'] == 'yesno') {
                                    $val = $values_form[$plfield['plugin_metademands_fields_id']];
//                                    if (is_int($val)) {
//                                        $val = $val - 1;
//                                    }
                                    $inputFieldMain[$fields_field->fields['name']] = $val;
                                } else {
                                    $inputFieldMain[$fields_field->fields['name']] = $values_form[$plfield['plugin_metademands_fields_id']];
                                }
                            }
                        }
                    }
                }
            }
        }

        if ($params["create_subticket"] == 1) {
            if (!Metademand::createSonsTickets(
                $meta->getID(),
                $ticket_id,
                $ticket->fields,
                $ticket_id,
                $meta_tasks,
                1,
                $inputField,
                $inputFieldMain
            )) {
                $KO[] = 1;
            }
            $inputVal['validate'] = self::TICKET_CREATION;
        } elseif ($params["create_subticket"] == 0) {
            if (is_array($meta_tasks)) {
                foreach ($meta_tasks as $meta_task) {
                    if (Ticket_Field::checkTicketCreation($meta_task['tasks_id'], $ticket_id)) {
                        $ticket_task = new TicketTask();
                        $input = [];
                        $input['content'] = $meta_task['tickettasks_name'] . " " . $meta_task['content'];
                        $input['tickets_id'] = $ticket_id;
                        $input['groups_id_tech'] = $meta_task["groups_id_assign"];
                        $input['users_id_tech'] = $meta_task["users_id_assign"];
                        $ticket_task->add($input);
                    }
                }
            }
            $input = [];
            $input['id'] = $ticket_id;
            $input['_itil_assign']["_type"] = "group";
            $input['_itil_assign']["groups_id"] = $params["group_to_assign"];

            $ticket->update($input);

            $where_keep = [
                'tickets_id' => $ticket_id,
                'type' => CommonITILActor::ASSIGN
            ];
            $ticket_user = new Ticket_User();
            $found = $ticket_user->find($where_keep);
            foreach ($found as $id => $tu) {
                //delete user
                $ticket_user->delete(['id' => $id]);
            }

            $inputVal['validate'] = self::TASK_CREATION;
        } else {
            $input = [];
            $input['id'] = $ticket_id;
            $input['_itil_assign']["_type"] = "group";
            $input['_itil_assign']["groups_id"] = $params["group_to_assign"];

            $ticket->update($input);

            $where_keep = [
                'tickets_id' => $ticket_id,
                'type' => CommonITILActor::ASSIGN
            ];
            $ticket_user = new Ticket_User();
            $found = $ticket_user->find($where_keep);
            foreach ($found as $id => $tu) {
                //delete user
                $ticket_user->delete(['id' => $id]);
            }

            $inputVal['validate'] = self::VALIDATE_WITHOUT_TASK;
        }

        $inputVal['id'] = $this->getID();
        $inputVal['users_id'] = Session::getLoginUserID();
        $inputVal['date'] = $_SESSION["glpi_currenttime"];
        $this->update($inputVal);

        if ($inputVal['validate'] == self::TASK_CREATION) {
            echo "<div class='alert alert-success alert-important d-flex'>" . __(
                'Tasks are created',
                'metademands'
            ) . "</div>";
        } elseif ($inputVal['validate'] == self::TICKET_CREATION) {
            echo "<div class='alert alert-success alert-important d-flex'>" . __(
                'Sub-tickets are created',
                'metademands'
            ) . "</div>";
        } elseif ($inputVal['validate'] == self::VALIDATE_WITHOUT_TASK) {
            echo "<div class='alert alert-success alert-important d-flex'>" . __(
                'The metademand is validated and affected',
                'metademands'
            ) . "</div>";
        }
    }

    public static function showActionsForm($params)
    {
        $item = $params['item'];
        $metaValidation = new MetademandValidation();
        if ($item->fields['id'] > 0
            && $metaValidation->getFromDBByCrit(['tickets_id' => $item->fields['id']])
            && $_SESSION['glpiactiveprofile']['interface'] == 'central'
            && ($item->fields['status'] != \Ticket::SOLVED
                && $item->fields['status'] != \Ticket::CLOSED)
            && $item->fields['is_deleted'] != 1
            && Session::haveRight('plugin_metademands_validatemeta', READ)
            && Session::getCurrentInterface() == 'central') {
            $style = "btn-green";
            $title = "";
            if ($metaValidation->fields["validate"] == self::TO_VALIDATE
                || $metaValidation->fields["validate"] == self::TO_VALIDATE_WITHOUTTASK) {
                $style = "btn-orange";
                $title = __('Metademand validation', 'metademands');
            }
            echo "<li><button class='btn primary answer-action $style' data-bs-toggle='modal' data-bs-target='#metavalidation'>"
                . "<i class='ti ti-thumb-up' style='margin-left: 10px;'></i>".$title."</button></li>";

            echo Ajax::createIframeModalWindow(
                'metavalidation',
                PLUGIN_METADEMANDS_WEBDIR . '/front/metademandvalidation.form.php?tickets_id=' . $item->fields['id'],
                [
                    'title' => __('Metademand validation', 'metademands'),
                    'display' => false,
                    'width' => 200,
                    'height' => 400,
                    'reloadonclose' => true
                ]
            );
        }
    }

    public function viewValidation($params)
    {
        $ticket_id = $params["tickets_id"];
        $this->getFromDBByCrit(['tickets_id' => $ticket_id]);
        $ticket = new \Ticket();
        $ticket->getFromDB($ticket_id);
        echo "<form name='form_raz' id='form_raz' method='post'
      action='" . PLUGIN_METADEMANDS_WEBDIR . "/front/metademandvalidation.form.php" . "' >";
        echo Html::hidden('action', ['id' => 'action_validationMeta', 'value' => 'validationMeta']);
        echo Html::hidden('tickets_id', ['id' => 'action_validationMeta', 'value' => $ticket_id]);
        echo "<table class='tab_cadre_fixe'>";
        echo "<tr class='tab_bg_1 center'>";
        echo "<th colspan='2'>";
        echo __("Metademand validation", 'metademands');
        echo "</th>";
        echo "</tr>";
        echo "<tr class='tab_bg_1 center'>";

        if ($this->fields["users_id"] == 0
            && $this->fields["validate"] == self::TO_VALIDATE) {
            $metademands = new Metademand();
            if ($metademands->getFromDB($this->fields["plugin_metademands_metademands_id"])
                && $metademands->fields['force_create_tasks'] == 0) {
                echo "<td>" . __('Create sub-tickets', 'metademands') . " &nbsp;";
                echo "<input type='radio' name='create_subticket' id='create_subticket' value='1' checked>";
                echo "</td>";
                echo "<td>" . __('Create tasks', 'metademands') . "&nbsp;";
                echo "<input type='radio' name='create_subticket' id='create_subticket2' value='0'>";
                echo "</td>";
            } else {
                echo "<td>" . __('Create tasks', 'metademands') . "&nbsp;";
                echo "<input type='radio' name='create_subticket' id='create_subticket2' value='0'>";
                echo "</td>";
            }

            echo "</tr>";
            echo "<tr class='tab_bg_1 center' id='to_update_group'>";

            Ajax::updateItemOnEvent(
                'create_subticket',
                'to_update_group',
                PLUGIN_METADEMANDS_WEBDIR . "/ajax/displayGroupField.php",
                [
                    "create_subticket" => '__VALUE__',
                    'tickets_id' => $ticket_id
                ]
            );
            Ajax::updateItemOnEvent(
                'create_subticket2',
                'to_update_group',
                PLUGIN_METADEMANDS_WEBDIR . "/ajax/displayGroupField.php",
                [
                    "create_subticket" => '__VALUE__',
                    'tickets_id' => $ticket_id
                ]
            );
        } elseif ($this->fields["users_id"] == 0
            && $this->fields["validate"] == self::TO_VALIDATE_WITHOUTTASK) {
            echo "<td colspan='2'>" . __('Attribute ticket to ', 'metademands') . " &nbsp;";
            echo Html::hidden("create_subticket", ["value" => 2]);
            $group = 0;
            foreach ($ticket->getGroups(CommonITILActor::ASSIGN) as $d) {
                $group = $d['groups_id'];
            }
            \Group::dropdown([
                'condition' => ['is_assign' => 1],
                'name' => 'group_to_assign',
                'value' => $group
            ]);
            echo "</td>";
        } elseif ($this->fields["users_id"] != 0
            && $this->fields["validate"] == self::TASK_CREATION) {
            echo "<div class='alert alert-success d-flex'>" . __('Tasks are created', 'metademands') . "</div>";
        } elseif ($this->fields["users_id"] != 0
            && $this->fields["validate"] == self::VALIDATE_WITHOUT_TASK) {
        } else {
            echo "<div class='alert alert-success d-flex'>" . __('Sub-tickets are created', 'metademands') . "</div>";
        }
        echo "</tr>";
        if ($this->fields["users_id"] != 0) {
            echo "<tr class='tab_bg_1 center'>";
            echo "<td colspan='4'>";
            echo sprintf(
                __('Validated by %s on %s', 'metademands'),
                User::getFriendlyNameById($this->fields["users_id"]),
                Html::convDateTime($this->fields["date"])
            );
            echo "</td>";
            echo "</tr>";
        }

        if ($this->fields["users_id"] == 0
        ) {
            echo "<tr class='tab_bg_1'>";
            echo "<td colspan='2' class='center'>";
            echo Html::submit(
                __("Validate metademands", 'metademands'),
                ['name' => 'btnAddAll', 'class' => 'btn btn-primary']
            );
            echo "</td>";
            echo "</tr>";
        }
        Html::closeForm();
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
            case 'validate':
                $options['name'] = $name;
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
     * @return a string
     **@since version 0.83
     *
     */
    public static function getSpecificValueToDisplay($field, $values, array $options = [])
    {
        if (!is_array($values)) {
            $values = [$field => $values];
        }
        switch ($field) {
            case 'validate':
                $style = "style='background-color: " . self::getStatusColor($values[$field]) . ";'";
                $out = "<div class='center' $style>";
                $out .= self::getStatusName($values[$field]);
                $out .= "</div>";
                return $out;
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
        $p['name'] = 'validate';
        $p['value'] = 0;
        $p['showtype'] = 'normal';
        $p['display'] = true;
        $p['display_emptychoice'] = false;
        if (is_array($options) && count($options)) {
            foreach ($options as $key => $val) {
                $p[$key] = $val;
            }
        }

        $values = [];
        //      $values[0]               = static::getStatusName(0);
        $values[self::TO_VALIDATE_WITHOUTTASK] = static::getStatusName(self::TO_VALIDATE_WITHOUTTASK);
        $values[self::TO_VALIDATE] = static::getStatusName(self::TO_VALIDATE);
        $values[self::TICKET_CREATION] = static::getStatusName(self::TICKET_CREATION);
        $values[self::TASK_CREATION] = static::getStatusName(self::TASK_CREATION);
        $values[self::VALIDATE_WITHOUT_TASK] = static::getStatusName(self::VALIDATE_WITHOUT_TASK);

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
            case self::TO_VALIDATE:
                return __('To validate', 'metademands');
            case self::TICKET_CREATION:
                return __('Child tickets created', 'metademands');
            case self::TASK_CREATION:
                return __('Tasks created', 'metademands');
            case self::TO_VALIDATE_WITHOUTTASK:
                return __('To validate without child', 'metademands');
            case self::VALIDATE_WITHOUT_TASK:
                return __('Validate without child', 'metademands');
            default:
                // Return $value if not define
                return __('Not subject to validation', 'metademands');
        }
    }

    /**
     * @param $value
     *
     * @return string
     */
    public static function getStatusColor($value)
    {
        switch ($value) {
            case self::TO_VALIDATE_WITHOUTTASK:
            case self::TO_VALIDATE:
                return "orange";
            case self::VALIDATE_WITHOUT_TASK:
            case self::TASK_CREATION:
            case self::TICKET_CREATION:
                return "forestgreen";
            default:
                // Return $value if not define
                return "";
        }
    }
}
